<?
 // ------------------------------------------------------------------------------
 // NiDB cluster.php
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
		<title>NiDB - Cluster</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$tab = GetVariable("tab");

	/* ----- determine action ----- */
	switch ($action) {
		case 'viewslurm':
		case 'viewpipelines':
		case 'viewdiskusage':
		case 'viewnodes':
			break;
		default:
			$action = 'viewsge';
	}

	DisplayClusterPage($action, $tab);


	/* -------------------------------------------- */
	/* ------- DisplayClusterPage ----------------- */
	/* -------------------------------------------- */
	function DisplayClusterPage($action, $tab) {
		$sgeactive       = ($action === 'viewsge')       ? 'primary' : 'basic';
		$slurmactive     = ($action === 'viewslurm')     ? 'primary' : 'basic';
		$pipelinesactive = ($action === 'viewpipelines') ? 'primary' : 'basic';
		$diskusageactive = ($action === 'viewdiskusage') ? 'primary' : 'basic';
		$nodesactive     = ($action === 'viewnodes')     ? 'primary' : 'basic';
		?>
		<div class="ui container">
			<div style="display:flex; align-items:center; gap:10px; margin-bottom:16px">
				<h3 class="ui header" style="margin:0">Compute cluster</h3>
				<div class="ui buttons">
					<a href="cluster.php?action=viewsge"   class="ui <?=$sgeactive?>   button">SGE</a>
					<a href="cluster.php?action=viewslurm" class="ui <?=$slurmactive?> button">Slurm</a>
				</div>
				<div class="ui buttons">
					<a href="cluster.php?action=viewpipelines" class="ui <?=$pipelinesactive?> button">Pipelines</a>
					<a href="cluster.php?action=viewdiskusage" class="ui <?=$diskusageactive?> button">Disk usage</a>
					<a href="cluster.php?action=viewnodes" class="ui <?=$nodesactive?> button">Nodes</a>
				</div>

				<div style="margin-left:auto; display:flex; align-items:center; gap:10px">
					<span style="color:#888">Page loaded <?=date('M j, Y g:i:s a')?></span>
					<button type="button" class="ui basic button" onclick="location.reload()"><i class="sync icon"></i> Refresh</button>
				</div>
			</div>

			<? if ($action === 'viewslurm') { ?>
				<? DisplaySlurmTabs($tab); ?>
			<? } elseif ($action === 'viewpipelines') { ?>
				<? DisplayPipelines(); ?>
			<? } elseif ($action === 'viewdiskusage') { ?>
				<? DisplayDiskUsage(); ?>
			<? } elseif ($action === 'viewnodes') { ?>
				<? DisplayNodeMonitor(); ?>
			<? } else { ?>
				<? DisplaySGETabs($tab); ?>
			<? } ?>
		</div>

		<script>
			$(document).ready(function() {
				$('.tabular.menu .item').tab();
			});
		</script>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayNodeMonitor ----------------- */
	/* -------------------------------------------- */
	/* load, CPU, memory, network, and disk history of the monitored cluster nodes, recorded every minute by the
	   'cluster' module (doc/cluster-monitor.md). Nodes are managed in clustersettings.php. Both views are grids of
	   sparklines on a shared time axis: the overview has one row per node for a selected metric, and the node detail
	   view has one row per metric */
	function DisplayNodeMonitor() {
		$ranges = NodeMonitorRanges();
		$range = GetVariable("range");
		if (!is_string($range) || !isset($ranges[$range])) $range = '24h';
		$metrics = NodeOverviewMetrics();
		$metric = GetVariable("metric");
		if (!is_string($metric) || !isset($metrics[$metric])) $metric = 'load1';
		$scale = (GetVariable("scale") === 'node') ? 'node' : 'shared';
		$style = GetVariable("style");
		if (!in_array($style, array('line', 'area', 'heat'), true)) $style = 'line';
		$nodeid = (int)GetVariable("nodeid");

		/* the overview's metric and scale are carried through the links, so going to a node and back keeps them */
		$keep = "&metric=" . urlencode($metric) . "&scale=" . urlencode($scale) . "&style=" . urlencode($style);

		?>
		<link rel="stylesheet" href="scripts/uplot/uPlot.min.css">
		<script src="scripts/uplot/uPlot.iife.min.js"></script>
		<style>
			.sparkgrid { margin-top: 4px; }
			.sparkrow { display: flex; align-items: center; min-height: 44px; border-bottom: 1px solid #eeede9; }
			.sparkrow:hover { background: #f7f7f5; }
			.sparkrow.sparkhead, .sparkrow.sparkfoot { min-height: 0; border-bottom: 1px solid #d4d3cf; font-size: 0.85em; color: #52514e; }
			.sparkrow.sparkfoot { border-bottom: none; border-top: 1px solid #d4d3cf; margin-top: -1px; }
			.sparkrow.sparkhead:hover, .sparkrow.sparkfoot:hover { background: none; }
			.sparkname { flex: 0 0 220px; padding: 3px 10px 3px 6px; overflow: hidden; line-height: 1.3; }
			.sparkname .sparklabel { display: block; font-weight: bold; color: #1f1e1c; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
			.sparkname a.sparklabel { color: #2a78d6; }
			.sparksub { font-size: 0.8em; color: #52514e; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
			.sparksub i.icon { margin: 0 3px 0 0; }
			.sparkval { flex: 0 0 130px; padding-right: 12px; text-align: right; line-height: 1.3; font-variant-numeric: tabular-nums; white-space: nowrap; overflow: hidden; }
			.sparkval b { color: #1f1e1c; }
			.sparkvalsub { font-size: 0.8em; color: #52514e; }
			.sparkplot { flex: 1 1 auto; min-width: 0; overflow: hidden; }
			.sparknodata { color: #888; font-size: 0.85em; font-style: italic; padding-left: 10px; }
			.sparkgroup { padding: 14px 6px 4px 6px; font-weight: bold; color: #1f1e1c; border-bottom: 1px solid #d4d3cf; }
			.nodestatuslabel { font-size: 0.8rem; font-weight: normal; color: #52514e; white-space: nowrap; }
		</style>
		<div style="display:flex; align-items:center; gap:10px; margin-bottom:14px">
			<? if ($nodeid > 0) { ?>
				<a href="cluster.php?action=viewnodes&range=<?=urlencode($range)?><?=$keep?>" class="ui basic button noderangelink"><i class="arrow left icon"></i> All nodes</a>
			<? } ?>
			<div class="ui small buttons">
				<? foreach ($ranges as $key => $r) { ?>
					<a href="cluster.php?action=viewnodes&range=<?=urlencode($key)?><?=($nodeid > 0) ? "&nodeid=$nodeid" : ""?><?=$keep?>" class="ui <?=($key == $range) ? 'primary' : 'basic'?> button noderangelink"><?=$r['label']?></a>
				<? } ?>
			</div>
			<? if (isAdmin()) { ?>
				<a href="clustersettings.php" style="margin-left:auto"><i class="cog icon"></i>Manage nodes</a>
			<? } ?>
		</div>
		<? NodeSparkGridScript(); ?>
		<?
		if ($nodeid > 0)
			DisplayNodeDetail($nodeid, $range);
		else
			DisplayNodeOverview($range, $metric, $scale, $style);
	}


	/* -------------------------------------------- */
	/* ------- NodeMonitorRanges ------------------ */
	/* -------------------------------------------- */
	function NodeMonitorRanges() {
		return array(
			'1h'  => array('label' => '1 hour',   'seconds' => 3600),
			'6h'  => array('label' => '6 hours',  'seconds' => 21600),
			'24h' => array('label' => '24 hours', 'seconds' => 86400),
			'7d'  => array('label' => '7 days',   'seconds' => 604800),
			'30d' => array('label' => '30 days',  'seconds' => 2592000)
		);
	}


	/* -------------------------------------------- */
	/* ------- NodeOverviewMetrics ---------------- */
	/* -------------------------------------------- */
	/* the metrics that can be selected in the node overview. 'sql' is the aggregate for GetNodeSamples, 'col' is the
	   cluster_node_samples column for the latest value, and 'kind' picks the formatting and y scale (load, pct, rate) */
	function NodeOverviewMetrics() {
		return array(
			'load1'     => array('button' => 'Load 1m',   'label' => 'Load (1 min)',     'sql' => 'avg(load1)',          'col' => 'load1',          'kind' => 'load'),
			'load5'     => array('button' => 'Load 5m',   'label' => 'Load (5 min)',     'sql' => 'avg(load5)',          'col' => 'load5',          'kind' => 'load'),
			'load15'    => array('button' => 'Load 15m',  'label' => 'Load (15 min)',    'sql' => 'avg(load15)',         'col' => 'load15',         'kind' => 'load'),
			'mem'       => array('button' => 'Mem % used', 'label' => 'Memory used',     'sql' => 'avg(100 * mem_used_mb / nullif(mem_total_mb, 0))', 'col' => '', 'kind' => 'pct'),
			'netrx'     => array('button' => 'Net RX',    'label' => 'Network received', 'sql' => 'avg(net_rx_bps)',     'col' => 'net_rx_bps',     'kind' => 'rate'),
			'nettx'     => array('button' => 'Net TX',    'label' => 'Network sent',     'sql' => 'avg(net_tx_bps)',     'col' => 'net_tx_bps',     'kind' => 'rate'),
			'diskread'  => array('button' => 'Disk read', 'label' => 'Disk read',        'sql' => 'avg(disk_read_bps)',  'col' => 'disk_read_bps',  'kind' => 'rate'),
			'diskwrite' => array('button' => 'Disk write', 'label' => 'Disk write',      'sql' => 'avg(disk_write_bps)', 'col' => 'disk_write_bps', 'kind' => 'rate')
		);
	}


	/* -------------------------------------------- */
	/* ------- NodeJSON --------------------------- */
	/* -------------------------------------------- */
	/* json_encode for data written into a <script> block. Node names and errors are user-entered, so <, >, &, and
	   quotes are escaped so they can't close the script tag */
	function NodeJSON($data) {
		return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE);
	}


	/* -------------------------------------------- */
	/* ------- GetNodeStatus ---------------------- */
	/* -------------------------------------------- */
	/* status of a cluster_nodes row (which includes a 'seenago' column, in seconds).
	   Returns array(color, icon, label, details, failed). The color is from the status palette, and is always shown with
	   its icon and label so it never carries meaning alone */
	function GetNodeStatus($row) {
		$status = $row['last_status'] ?? '';
		$seenago = $row['seenago'];
		if (!$row['enabled']) return array('#9e9d98', 'minus circle', 'Disabled', 'Not being checked', false);
		if ($status == '') return array('#9e9d98', 'circle outline', 'Not checked yet', 'Waiting for the first check', false);
		if ($status != 'ok') return array('#d03b3b', 'times circle', ucfirst($status), $row['last_error'] ?? '', true);
		if (($seenago === null) || ((int)$seenago > 300)) return array('#9e9d98', 'clock outline', 'No recent data', 'Is the cluster module running?', false);
		return array('#0ca30c', 'check circle', 'OK', 'Answered the last check', false);
	}


	/* -------------------------------------------- */
	/* ------- GetNodeSamples --------------------- */
	/* -------------------------------------------- */
	/* samples for one node (or all nodes if $nodeid is 0) since $start, averaged into buckets of $bucket seconds.
	   Returns array(times, array(nodeid => array(column => values))). Every node gets the same time axis, with null where
	   there's no sample, so the charts show gaps instead of drawing across outages */
	function GetNodeSamples($nodeid, $start, $bucket, $columns) {
		$bucket = max(60, (int)$bucket);
		$first = (int)(floor($start / $bucket) * $bucket);
		$last = (int)(floor(time() / $bucket) * $bucket);
		$times = array();
		for ($t = $first; $t <= $last; $t += $bucket) $times[] = $t;
		$index = array_flip($times);

		/* $columns are 'alias' => 'sql expression', from the callers (not user input) */
		$select = array();
		foreach ($columns as $alias => $expr) $select[] = "$expr '$alias'";
		$sqlstring = "select clusternode_id, floor(unix_timestamp(sample_time) / ?) * ? 'bucket', " . implode(", ", $select) . " from cluster_node_samples where sample_time >= from_unixtime(?)";
		$params = array($bucket, $bucket, $first);
		$types = 'iii';
		if ($nodeid > 0) {
			$sqlstring .= " and clusternode_id = ?";
			$params[] = (int)$nodeid;
			$types .= 'i';
		}
		$sqlstring .= " group by clusternode_id, bucket";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, $types, ...$params);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);

		$data = array();
		$empty = array_fill(0, count($times), null);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$id = (int)$row['clusternode_id'];
			$b = (int)$row['bucket'];
			if (!isset($index[$b])) continue;
			if (!isset($data[$id])) {
				foreach ($columns as $alias => $expr) $data[$id][$alias] = $empty;
			}
			foreach ($columns as $alias => $expr)
				$data[$id][$alias][$index[$b]] = ($row[$alias] === null) ? null : round((float)$row[$alias], 2);
		}
		mysqli_stmt_close($stmt);

		return array($times, $data);
	}


	/* -------------------------------------------- */
	/* ------- NodeSparkGridScript ---------------- */
	/* -------------------------------------------- */
	/* the javascript shared by the overview and detail views: formatters, and NodeSpark.build(), which draws a grid of
	   sparklines with one row per node or metric. All rows share the time axis (shown above, and below long grids), a
	   crosshair (hovering shows every row's value at that time, and the time in the header), and zoom (drag across any
	   row, double-click to reset). Each row is a single series, so all use the same color; a dashed gray line is the
	   row's reference (cores, total memory). Text is set with textContent.
	   A row is { group: 'heading' }, or { label, href, title, status: {color, icon, label, details}, sub, values, ref,
	   fmt, refFmt, current, currentRef, yTop, levelTop }.
	   opts.style picks how values are drawn: 'line' (default, one color), 'area' (line and area under it shaded
	   green/yellow/red by height), or 'heat' (one cell per time point colored by its value, no line). For both color
	   styles the scale runs from 0 (green) to the row's levelTop (red), defaulting to yTop */
	function NodeSparkGridScript() {
		?>
		<script>
			const NodeSpark = (function() {
				const color = '#2a78d6', fill = 'rgba(42,120,214,0.12)', refColor = '#9e9d98', axisColor = '#52514e';
				const height = 40;
				const pad = [4, 12, 4, 12];

				/* low -> middle -> high color scale for the area and heat styles */
				const heatStops = [[12, 163, 12], [237, 161, 0], [227, 73, 72]];
				const heatCSS = 'linear-gradient(to right, rgb(12,163,12), rgb(237,161,0), rgb(227,73,72))';
				function heatColor(f, a) {
					f = Math.min(1, Math.max(0, f || 0));
					const s = (f < 0.5) ? 0 : 1;
					const k = (f < 0.5) ? f * 2 : (f - 0.5) * 2;
					const c = heatStops[s].map(function(v, j) { return Math.round(v + (heatStops[s + 1][j] - v) * k); });
					return 'rgba(' + c[0] + ',' + c[1] + ',' + c[2] + ',' + ((a == null) ? 1 : a) + ')';
				}

				const fmt = {
					num: function(v) { return v.toFixed(2); },
					int: function(v) { return Math.round(v).toLocaleString(); },
					pct: function(v) { return v.toFixed(1) + '%'; },
					mem: function(v) { return (Math.abs(v) >= 1048576) ? (v/1048576).toFixed(2) + ' TiB' : ((Math.abs(v) >= 1024) ? (v/1024).toFixed(1) + ' GiB' : v.toFixed(0) + ' MiB'); },
					rate: function(v) {
						const units = ['B/s', 'KiB/s', 'MiB/s', 'GiB/s'];
						let i = 0;
						while ((Math.abs(v) >= 1024) && (i < units.length - 1)) { v /= 1024; i++; }
						return v.toFixed((i == 0) ? 0 : 1) + ' ' + units[i];
					},
					time: function(t) { return new Date(t * 1000).toLocaleString([], { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }); }
				};

				/* largest non-null value in any of the arrays (0 if none) */
				function max() {
					let m = 0;
					for (let a = 0; a < arguments.length; a++) {
						const arr = arguments[a];
						if (!arr) continue;
						for (let i = 0; i < arr.length; i++) if ((arr[i] != null) && (arr[i] > m)) m = arr[i];
					}
					return m;
				}

				function div(cls, text) {
					const e = document.createElement('div');
					e.className = cls;
					if (text != null) e.textContent = text;
					return e;
				}

				function plotWidth(el) { return Math.max(120, el.clientWidth); }

				function build(el, t, rows, nameHeader, opts) {
					const style = (opts && opts.style) ? opts.style : 'line';
					const step = (t.length > 1) ? (t[1] - t[0]) : 60; /* seconds per time point */
					if (typeof uPlot === 'undefined') { el.innerHTML = '<span class="ui red text">Chart library (uPlot) failed to load</span>'; return; }

					(el._charts || []).forEach(function(c) { c.destroy(); });
					el.innerHTML = '';
					const charts = [];
					el._charts = charts;
					const makers = [];
					const syncKey = 'nodespark-' + (el.id || 'grid');

					/* zooming any row sets the same time range on all of them */
					let syncing = false;
					const zoomSync = { hooks: { setScale: [function(u, key) {
						if ((key !== 'x') || syncing) return;
						syncing = true;
						charts.forEach(function(c) { if (c !== u) c.setScale('x', { min: u.scales.x.min, max: u.scales.x.max }); });
						syncing = false;
					}] } };

					/* a row with only the time axis. side 0 is the top, 2 the bottom */
					function axisRow(cls, nameText, valEl, side) {
						const row = div('sparkrow ' + cls);
						row.appendChild(div('sparkname', nameText));
						row.appendChild(valEl || div('sparkval'));
						const plot = div('sparkplot');
						row.appendChild(plot);
						el.appendChild(row);
						makers.push(function() {
							const u = new uPlot({
								width: plotWidth(plot), height: 26,
								padding: [(side == 0) ? 0 : 2, pad[1], (side == 2) ? 0 : 2, pad[3]],
								legend: { show: false },
								cursor: { show: false, drag: { x: false, y: false } },
								select: { show: false },
								plugins: [zoomSync],
								scales: { x: { time: true }, y: { range: function() { return [0, 1]; } } },
								axes: [{ side: side, stroke: axisColor, grid: { show: false }, ticks: { size: 4, stroke: '#d4d3cf' }, size: 22, gap: 2 }, { show: false }],
								series: [{}, { show: false }]
							}, [t, t.map(function() { return null; })], plot);
							u._h = 26;
							charts.push(u);
						});
					}

					const timeEl = div('sparkval', 'Latest');
					axisRow('sparkhead', nameHeader, timeEl, 0);

					let datarows = 0;
					rows.forEach(function(r) {
						if (r.group !== undefined) {
							el.appendChild(div('sparkgroup', r.group));
							return;
						}
						datarows++;
						const row = div('sparkrow');

						const name = div('sparkname');
						const label = document.createElement(r.href ? 'a' : 'span');
						label.className = 'sparklabel';
						label.textContent = r.label;
						if (r.href) label.href = r.href;
						if (r.title) label.title = r.title;
						name.appendChild(label);
						if (r.status) {
							const st = div('sparksub');
							const icon = document.createElement('i');
							icon.className = r.status.icon + ' icon';
							icon.style.color = r.status.color;
							st.appendChild(icon);
							st.appendChild(document.createTextNode(r.status.label + ((r.status.failed && r.status.details) ? ': ' + r.status.details : '')));
							if (r.status.details) st.title = r.status.details;
							name.appendChild(st);
						}
						else if (r.sub) {
							name.appendChild(div('sparksub', r.sub));
						}
						row.appendChild(name);

						/* the value column shows the latest value, or the value under the crosshair */
						const val = div('sparkval');
						const vmain = document.createElement('b');
						const vsub = div('sparkvalsub');
						val.appendChild(vmain);
						val.appendChild(vsub);
						row.appendChild(val);
						function show(i) {
							const v = (i == null) ? r.current : r.values[i];
							const ref = (i == null) ? r.currentRef : (r.ref ? r.ref[i] : null);
							vmain.textContent = (v == null) ? ((i == null) ? '-' : 'no data') : r.fmt(v);
							vsub.textContent = ((ref != null) && r.refFmt) ? r.refFmt(ref) : ' ';
						}
						show(null);

						const plot = div('sparkplot');
						row.appendChild(plot);
						el.appendChild(row);

						if (r.values.every(function(v) { return v == null; })) {
							plot.appendChild(div('sparknodata', 'No samples in this time range'));
							return;
						}

						makers.push(function() {
							const levelTop = (r.levelTop > 0) ? r.levelTop : r.yTop;

							/* vertical gradient from 0 (green) to levelTop (red), so the area's color follows its height */
							function gradient(u, a) {
								const y0 = u.valToPos(0, 'y', true);
								let y1 = u.valToPos(levelTop, 'y', true);
								if (!isFinite(y0) || !isFinite(y1)) return heatColor(0, a);
								if (y1 == y0) y1 = y0 - 1;
								const g = u.ctx.createLinearGradient(0, y0, 0, y1);
								g.addColorStop(0, heatColor(0, a));
								g.addColorStop(0.5, heatColor(0.5, a));
								g.addColorStop(1, heatColor(1, a));
								return g;
							}

							/* one full-height cell per time point, colored by its value */
							function drawHeat(u) {
								const ctx = u.ctx, b = u.bbox;
								ctx.save();
								ctx.beginPath();
								ctx.rect(b.left, b.top, b.width, b.height);
								ctx.clip();
								for (let i = 0; i < t.length; i++) {
									const v = r.values[i];
									if (v == null) continue;
									const x0 = Math.round(u.valToPos(t[i], 'x', true));
									const x1 = Math.round(u.valToPos(t[i] + step, 'x', true));
									if ((x1 < b.left) || (x0 > b.left + b.width)) continue;
									ctx.fillStyle = heatColor(v / levelTop, 1);
									ctx.fillRect(x0, b.top, Math.max(1, x1 - x0), b.height);
								}
								ctx.restore();
							}

							let main = { stroke: color, fill: fill, width: 1.5, points: { show: false } };
							if (style == 'area')
								main = { stroke: function(u) { return gradient(u, 1); }, fill: function(u) { return gradient(u, 0.35); }, width: 1.5, points: { show: false } };
							else if (style == 'heat')
								main = { stroke: 'transparent', width: 0, points: { show: false } };
							const series = [{}, main];
							const data = [t, r.values];
							if (r.ref && (style != 'heat')) {
								series.push({ stroke: refColor, width: 1, dash: [3, 3], points: { show: false } });
								data.push(r.ref);
							}
							const u = new uPlot({
								width: plotWidth(plot), height: height,
								padding: pad,
								legend: { show: false },
								cursor: { x: true, y: false, points: { show: false }, drag: { x: true, y: false }, sync: { key: syncKey } },
								plugins: [zoomSync],
								hooks: {
									setCursor: [function(u) {
										const i = u.cursor.idx;
										const off = ((i == null) || (u.cursor.left == null) || (u.cursor.left < 0));
										show(off ? null : i);
										timeEl.textContent = off ? 'Latest' : fmt.time(t[i]);
									}],
									draw: (style == 'heat') ? [drawHeat] : []
								},
								axes: [{ show: false }, { show: false }],
								scales: { x: { time: true }, y: { range: function() { return [0, r.yTop]; } } },
								series: series
							}, data, plot);
							u._h = height;
							charts.push(u);
						});
					});

					if (datarows > 12) axisRow('sparkfoot', '', null, 2);

					/* charts are made after the rows are in the page, so they know their width */
					makers.forEach(function(f) { f(); });

					if (!el._resizeBound) {
						el._resizeBound = true;
						let timer = null;
						window.addEventListener('resize', function() {
							clearTimeout(timer);
							timer = setTimeout(function() {
								(el._charts || []).forEach(function(c) { c.setSize({ width: plotWidth(c.root.parentNode), height: c._h }); });
							}, 150);
						});
					}
				}

				return { fmt: fmt, max: max, build: build, heatCSS: heatCSS };
			})();
		</script>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayNodeOverview ---------------- */
	/* -------------------------------------------- */
	/* one row per node, grouped by cluster, with a sparkline of the selected metric. The metric buttons switch the rows
	   without reloading the page */
	function DisplayNodeOverview($range, $metric, $scale, $style) {
		$ranges = NodeMonitorRanges();
		$seconds = $ranges[$range]['seconds'];
		$metrics = NodeOverviewMetrics();

		$sqlstring = "select n.*, c.cluster_name, timestampdiff(second, n.last_seen, now()) 'seenago' from cluster_nodes n left join compute_cluster c on n.computecluster_id = c.computecluster_id order by c.cluster_name, n.hostname";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$clusters = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
			$clusters[(int)$row['computecluster_id']][] = $row;

		if (count($clusters) == 0) {
			?>
			<div class="ui message">
				No cluster nodes are being monitored.
				<? if (isAdmin()) { ?>Add nodes in <a href="clustersettings.php">cluster settings</a>.<? } else { ?>An administrator can add nodes in the cluster settings.<? } ?>
			</div>
			<?
			return;
		}

		/* the most recent sample for each node, from the last 10 minutes */
		$latest = array();
		$sqlstring = "select * from cluster_node_samples where sample_time >= now() - interval 10 minute order by sample_time";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
			$latest[(int)$row['clusternode_id']] = $row;

		/* about 300 points per row */
		$bucket = max(60, (int)(ceil($seconds / 300 / 60) * 60));
		$columns = array('cores' => 'max(num_cores)');
		foreach ($metrics as $key => $m) $columns[$key] = $m['sql'];
		list($times, $samples) = GetNodeSamples(0, time() - $seconds, $bucket, $columns);
		$empty = array_fill(0, count($times), null);

		/* grouped by cluster ID, so clusters with the same (or a blank) name stay separate */
		$groups = array();
		foreach ($clusters as $clusterid => $nodes) {
			$clustername = trim($nodes[0]['cluster_name'] ?? '');
			$group = array('label' => ($clustername == '') ? '(blank cluster name)' : $clustername, 'nodes' => array());
			foreach ($nodes as $node) {
				$id = (int)$node['clusternode_id'];
				list($color, $icon, $statuslabel, $statusdetails, $failed) = GetNodeStatus($node);
				$s = $latest[$id] ?? null;

				$cur = array('cores' => ($s && ($s['num_cores'] !== null)) ? (int)$s['num_cores'] : null);
				foreach ($metrics as $key => $m) {
					if ($key == 'mem')
						$cur[$key] = ($s && ($s['mem_used_mb'] !== null) && ((float)$s['mem_total_mb'] > 0)) ? round(100 * (float)$s['mem_used_mb'] / (float)$s['mem_total_mb'], 1) : null;
					else
						$cur[$key] = ($s && ($s[$m['col']] !== null)) ? (float)$s[$m['col']] : null;
				}

				$series = array();
				foreach ($columns as $key => $expr) $series[$key] = $samples[$id][$key] ?? $empty;

				$group['nodes'][] = array(
					'id' => $id,
					'name' => (($node['display_name'] ?? '') != '') ? $node['display_name'] : $node['hostname'],
					'hostname' => $node['hostname'],
					'status' => array('color' => $color, 'icon' => $icon, 'label' => $statuslabel, 'details' => $statusdetails, 'failed' => $failed),
					'cur' => $cur,
					'series' => $series
				);
			}
			$groups[] = $group;
		}

		$metricinfo = array();
		foreach ($metrics as $key => $m) $metricinfo[$key] = array('label' => $m['label'], 'kind' => $m['kind']);
		?>
		<div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap">
			<div class="ui small buttons" id="nodeMetricButtons">
				<? foreach ($metrics as $key => $m) { ?>
					<button type="button" class="ui <?=($key == $metric) ? 'primary' : 'basic'?> button" data-metric="<?=htmlspecialchars($key)?>" title="<?=htmlspecialchars($m['label'])?>"><?=htmlspecialchars($m['button'])?></button>
				<? } ?>
			</div>
			<div class="ui small buttons" id="nodeStyleButtons">
				<? foreach (array('line' => 'Line', 'area' => 'Area', 'heat' => 'Heat strip') as $key => $label) { ?>
					<button type="button" class="ui <?=($key == $style) ? 'primary' : 'basic'?> button" data-style="<?=$key?>"><?=$label?></button>
				<? } ?>
			</div>
			<div class="ui checkbox" id="nodeSharedScaleBox">
				<input type="checkbox" id="nodeSharedScale" <?=($scale == 'shared') ? 'checked' : ''?>>
				<label for="nodeSharedScale">Same scale for all nodes</label>
			</div>
		</div>
		<div id="nodeHeatLegend" style="display:none; align-items:center; gap:8px; margin-top:10px; font-size:0.85em; color:#52514e">
			<span>Low</span>
			<span id="nodeHeatBar" style="display:inline-block; width:160px; height:10px; border-radius:2px"></span>
			<span>High</span>
			<span id="nodeHeatText"></span>
		</div>
		<div class="sparkgrid" id="nodeOverviewGrid"></div>
		<div class="ui small grey text" style="margin-top: 10px">
			Values are averaged over <?=($bucket >= 3600) ? round($bucket/3600, 1) . " hour" : round($bucket/60) . " minute"?> intervals. Hover to see past values; drag across a row to zoom in, double-click to zoom out. For load, the dashed line is the number of cores. Gaps are times the node didn't answer. Click a node to see all of its metrics.
		</div>
		<script>
			$(document).ready(function(){
				const d = <?=NodeJSON(array('t' => $times, 'range' => $range, 'metrics' => $metricinfo, 'groups' => $groups))?>;
				let metric = <?=NodeJSON($metric)?>;
				let shared = <?=($scale == 'shared') ? 'true' : 'false'?>;
				let style = <?=NodeJSON($style)?>;
				document.getElementById('nodeHeatBar').style.background = NodeSpark.heatCSS;
				const gridEl = document.getElementById('nodeOverviewGrid');
				const scaleBox = document.getElementById('nodeSharedScale');
				const F = NodeSpark.fmt;
				const coresFmt = function(c) { return c.toFixed(0) + ' cores'; };

				function render() {
					const kind = d.metrics[metric].kind;
					const fmtv = (kind == 'load') ? F.num : ((kind == 'pct') ? F.pct : F.rate);
					/* top of each row's y scale: 100% for percents, otherwise the largest value (or the cores, for load), with
					   a floor so a nearly idle node doesn't fill its row with noise */
					const floor = (kind == 'load') ? 1 : 1024;
					const topOf = function(n) {
						if (kind == 'pct') return 100;
						return Math.max(floor, NodeSpark.max(n.series[metric], (kind == 'load') ? n.series.cores : null) * 1.05);
					};
					/* the value shown as red in the area and heat styles: the core count for load (load >= cores is a
					   saturated node), 100% for percents, and the top of the row's scale for rates */
					const levelTopOf = function(n, yTop) {
						if (kind == 'pct') return 100;
						if (kind == 'load') {
							const cores = NodeSpark.max(n.series.cores) || n.cur.cores;
							if (cores > 0) return cores;
						}
						return yTop;
					};
					let sharedTop = 0;
					d.groups.forEach(function(g) { g.nodes.forEach(function(n) { sharedTop = Math.max(sharedTop, topOf(n)); }); });

					const rows = [];
					d.groups.forEach(function(g) {
						rows.push({ group: g.label });
						g.nodes.forEach(function(n) {
							rows.push({
								label: n.name,
								title: n.hostname,
								href: 'cluster.php?action=viewnodes&nodeid=' + n.id + '&range=' + encodeURIComponent(d.range) + '&metric=' + encodeURIComponent(metric) + '&scale=' + (shared ? 'shared' : 'node') + '&style=' + style,
								status: n.status,
								values: n.series[metric],
								ref: (kind == 'load') ? n.series.cores : null,
								fmt: fmtv,
								refFmt: (kind == 'load') ? coresFmt : null,
								current: n.cur[metric],
								currentRef: (kind == 'load') ? n.cur.cores : null,
								yTop: shared ? sharedTop : topOf(n),
								levelTop: levelTopOf(n, shared ? sharedTop : topOf(n))
							});
						});
					});
					NodeSpark.build(gridEl, d.t, rows, 'Node · ' + d.metrics[metric].label, { style: style });
					document.getElementById('nodeHeatLegend').style.display = (style == 'line') ? 'none' : 'flex';
					document.getElementById('nodeHeatText').textContent = (kind == 'load') ? '(0 to load = number of cores)' : ((kind == 'pct') ? '(0 to 100%)' : (shared ? '(0 to the largest value of all nodes)' : '(0 to the largest value of each node)'));
					scaleBox.disabled = (kind == 'pct');
					document.getElementById('nodeSharedScaleBox').classList.toggle('disabled', kind == 'pct');
				}

				/* keep the selection in the page URL and the range links, so a reload or range change keeps it */
				function remember() {
					const set = function(href) {
						const u = new URL(href, location.href);
						u.searchParams.set('metric', metric);
						u.searchParams.set('scale', shared ? 'shared' : 'node');
						u.searchParams.set('style', style);
						return u.toString();
					};
					history.replaceState(null, '', set(location.href));
					document.querySelectorAll('a.noderangelink').forEach(function(a) { a.href = set(a.href); });
				}

				document.querySelectorAll('#nodeMetricButtons button').forEach(function(b) {
					b.addEventListener('click', function() {
						metric = b.getAttribute('data-metric');
						document.querySelectorAll('#nodeMetricButtons button').forEach(function(o) {
							o.classList.toggle('primary', o === b);
							o.classList.toggle('basic', o !== b);
						});
						remember();
						render();
					});
				});
				document.querySelectorAll('#nodeStyleButtons button').forEach(function(b) {
					b.addEventListener('click', function() {
						style = b.getAttribute('data-style');
						document.querySelectorAll('#nodeStyleButtons button').forEach(function(o) {
							o.classList.toggle('primary', o === b);
							o.classList.toggle('basic', o !== b);
						});
						remember();
						render();
					});
				});
				scaleBox.addEventListener('change', function() {
					shared = scaleBox.checked;
					remember();
					render();
				});

				render();
			});
		</script>
		<?
	}


	/* -------------------------------------------- */
	/* ------- FormatMB --------------------------- */
	/* -------------------------------------------- */
	function FormatMB($mb) {
		if ($mb === null) return "-";
		$mb = (float)$mb;
		if ($mb >= 1048576) return round($mb / 1048576, 1) . " TiB";
		if ($mb >= 1024) return round($mb / 1024, 1) . " GiB";
		return round($mb) . " MiB";
	}


	/* -------------------------------------------- */
	/* ------- DisplayNodeDetail ------------------ */
	/* -------------------------------------------- */
	/* every metric for one node, one sparkline row per metric, grouped (load, CPU, memory, network, disk, processes) */
	function DisplayNodeDetail($nodeid, $range) {
		$ranges = NodeMonitorRanges();
		$seconds = $ranges[$range]['seconds'];

		$sqlstring = "select n.*, c.cluster_name, timestampdiff(second, n.last_seen, now()) 'seenago' from cluster_nodes n left join compute_cluster c on n.computecluster_id = c.computecluster_id where n.clusternode_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $nodeid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($nodeid));
		$node = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$node) {
			?><div class="ui message">Node not found</div><?
			return;
		}

		/* the most recent sample, for the header and the 'latest' column */
		$sqlstring = "select * from cluster_node_samples where clusternode_id = ? order by sample_time desc limit 1";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $nodeid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($nodeid));
		$s = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		list($color, $icon, $statuslabel, $statusdetails, $failed) = GetNodeStatus($node);
		$name = (($node['display_name'] ?? '') != '') ? $node['display_name'] : $node['hostname'];

		$uptime = "-";
		if ($s && ($s['uptime_sec'] !== null)) {
			$u = (int)$s['uptime_sec'];
			$uptime = ($u >= 86400) ? floor($u / 86400) . "d " . floor(($u % 86400) / 3600) . "h" : floor($u / 3600) . "h " . floor(($u % 3600) / 60) . "m";
		}

		/* about 1000 points per row. The 1 hour range shows every sample */
		$bucket = max(60, (int)(ceil($seconds / 1000 / 60) * 60));
		list($times, $samples) = GetNodeSamples($nodeid, time() - $seconds, $bucket, array(
			'load1' => 'avg(load1)',
			'load1max' => 'max(load1)',
			'load5' => 'avg(load5)',
			'load15' => 'avg(load15)',
			'cores' => 'max(num_cores)',
			'cpubusy' => 'avg(cpu_busy_pct)',
			'cpuuser' => 'avg(cpu_user_pct)',
			'cpusystem' => 'avg(cpu_system_pct)',
			'cpuiowait' => 'avg(cpu_iowait_pct)',
			'cpusteal' => 'avg(cpu_steal_pct)',
			'mempct' => 'avg(100 * mem_used_mb / nullif(mem_total_mb, 0))',
			'memused' => 'avg(mem_used_mb)',
			'memcache' => 'avg(mem_cache_mb)',
			'memtotal' => 'max(mem_total_mb)',
			'swapused' => 'avg(swap_used_mb)',
			'swaptotal' => 'max(swap_total_mb)',
			'netrx' => 'avg(net_rx_bps)',
			'nettx' => 'avg(net_tx_bps)',
			'diskread' => 'avg(disk_read_bps)',
			'diskwrite' => 'avg(disk_write_bps)',
			'procsrun' => 'avg(procs_running)',
			'procstotal' => 'avg(procs_total)'
		));
		$chartData = $samples[$nodeid] ?? null;

		/* latest values, with the same keys as the chart data */
		$cur = array();
		$curcols = array('load1' => 'load1', 'load1max' => 'load1', 'load5' => 'load5', 'load15' => 'load15', 'cores' => 'num_cores',
			'cpubusy' => 'cpu_busy_pct', 'cpuuser' => 'cpu_user_pct', 'cpusystem' => 'cpu_system_pct', 'cpuiowait' => 'cpu_iowait_pct', 'cpusteal' => 'cpu_steal_pct',
			'memused' => 'mem_used_mb', 'memcache' => 'mem_cache_mb', 'memtotal' => 'mem_total_mb', 'swapused' => 'swap_used_mb', 'swaptotal' => 'swap_total_mb',
			'netrx' => 'net_rx_bps', 'nettx' => 'net_tx_bps', 'diskread' => 'disk_read_bps', 'diskwrite' => 'disk_write_bps',
			'procsrun' => 'procs_running', 'procstotal' => 'procs_total');
		foreach ($curcols as $key => $col)
			$cur[$key] = ($s && ($s[$col] !== null)) ? (float)$s[$col] : null;
		$cur['mempct'] = ($s && ($s['mem_used_mb'] !== null) && ((float)$s['mem_total_mb'] > 0)) ? round(100 * (float)$s['mem_used_mb'] / (float)$s['mem_total_mb'], 1) : null;
		?>
		<div class="ui segment">
			<h3 class="ui header" style="margin-bottom: 6px">
				<?=htmlspecialchars($name)?>
				<span class="nodestatuslabel" style="margin-left:10px" title="<?=htmlspecialchars($statusdetails)?>"><i class="<?=$icon?> icon" style="color:<?=$color?>; margin:0 3px 0 0; font-size:1em"></i><?=htmlspecialchars($statuslabel)?></span>
				<div class="sub header"><tt><?=htmlspecialchars($node['hostname'])?></tt> &middot; <?=htmlspecialchars($node['cluster_name'] ?? '')?><?=(($node['node_desc'] ?? '') != '') ? ' &middot; ' . htmlspecialchars($node['node_desc']) : ''?></div>
			</h3>
			<? if ($failed && ($statusdetails != '')) { ?>
				<div class="ui small negative message" style="white-space: pre-wrap"><?=htmlspecialchars($statusdetails)?></div>
			<? } ?>
			<div class="ui tiny horizontal statistics" style="margin: 10px 0 0 0; flex-wrap: wrap; gap: 0 18px">
				<div class="statistic"><div class="value"><?=($s && $s['num_cores'] !== null) ? (int)$s['num_cores'] : '-'?></div><div class="label">cores</div></div>
				<div class="statistic"><div class="value"><?=($s) ? FormatMB($s['mem_total_mb']) : '-'?></div><div class="label">memory</div></div>
				<div class="statistic"><div class="value"><?=($s && $s['swap_total_mb'] !== null) ? FormatMB($s['swap_total_mb']) : '-'?></div><div class="label">swap</div></div>
				<div class="statistic"><div class="value"><?=$uptime?></div><div class="label">uptime</div></div>
				<div class="statistic"><div class="value"><?=($s && $s['procs_total'] !== null) ? number_format((int)$s['procs_total']) : '-'?></div><div class="label">processes</div></div>
			</div>
			<div class="ui small grey text" style="margin-top: 6px">Status: <?=htmlspecialchars($statuslabel)?> &middot; Last seen <?=htmlspecialchars($node['last_seen'] ?? 'never')?> &middot; Last checked <?=htmlspecialchars($node['last_checked'] ?? 'never')?><?=($s) ? ' &middot; Latest values from ' . htmlspecialchars($s['sample_time']) : ''?></div>
		</div>

		<? if ($chartData === null) { ?>
			<div class="ui message">No samples recorded for this node in the last <?=$ranges[$range]['label']?></div>
			<? return; ?>
		<? } ?>

		<div class="ui segment">
			<div class="sparkgrid" id="nodeDetailGrid"></div>
			<div class="ui small grey text" style="margin-top: 10px">
				<?=($bucket > 60) ? "Each point is the average of " . round($bucket / 60) . " minutes of samples. " : "One sample per minute. "?>Rows in the same group share a scale. Dashed lines are the number of cores (load) and the total (memory, swap). Hover to see past values; drag across a row to zoom in, double-click to zoom out. Gaps are times the node didn't answer.
			</div>
			<button type="button" class="ui small basic button" id="nodeTableToggle" style="margin-top: 10px"><i class="table icon"></i> Show data table</button>
			<div id="nodeTable" style="display: none; margin-top: 10px; max-height: 500px; overflow: auto"></div>
		</div>
		<script>
			$(document).ready(function(){
				const t = <?=NodeJSON($times)?>;
				const d = <?=NodeJSON($chartData)?>;
				const cur = <?=NodeJSON($cur)?>;
				const bucket = <?=(int)$bucket?>;
				const F = NodeSpark.fmt;
				const max = NodeSpark.max;
				const coresFmt = function(c) { return c.toFixed(0) + ' cores'; };
				const totalFmt = function(c) { return 'of ' + F.mem(c); };

				/* a metric row. Rows in a group are given the same y scale (top) so they can be compared */
				const row = function(label, key, fmt, top, extra) {
					return Object.assign({ label: label, values: d[key], fmt: fmt, current: cur[key], yTop: top }, extra || {});
				};
				const loadRef = { ref: d.cores, refFmt: coresFmt, currentRef: cur.cores };
				const loadTop = Math.max(1, max(d.load1, d.load1max, d.load5, d.load15, d.cores) * 1.05);
				const memTop = Math.max(1, max(d.memused, d.memcache, d.memtotal) * 1.05);
				const swapTop = Math.max(1, max(d.swapused, d.swaptotal) * 1.05);
				const netTop = Math.max(1024, max(d.netrx, d.nettx) * 1.05);
				const diskTop = Math.max(1024, max(d.diskread, d.diskwrite) * 1.05);

				const rows = [{ group: 'Load' }, row('Load 1 min', 'load1', F.num, loadTop, loadRef)];
				if (bucket > 60) rows.push(row('Load 1 min (peak)', 'load1max', F.num, loadTop, Object.assign({ sub: 'highest in each interval' }, loadRef)));
				rows.push(
					row('Load 5 min', 'load5', F.num, loadTop, loadRef),
					row('Load 15 min', 'load15', F.num, loadTop, loadRef),
					{ group: 'CPU' },
					row('Busy', 'cpubusy', F.pct, 100, { sub: '% of CPU time, all cores' }),
					row('User', 'cpuuser', F.pct, 100),
					row('System', 'cpusystem', F.pct, 100),
					row('I/O wait', 'cpuiowait', F.pct, 100),
					row('Steal', 'cpusteal', F.pct, 100),
					{ group: 'Memory' },
					row('Used %', 'mempct', F.pct, 100),
					row('Used', 'memused', F.mem, memTop, { ref: d.memtotal, refFmt: totalFmt, currentRef: cur.memtotal }),
					row('Cache', 'memcache', F.mem, memTop),
					row('Swap used', 'swapused', F.mem, swapTop, { ref: d.swaptotal, refFmt: totalFmt, currentRef: cur.swaptotal }),
					{ group: 'Network' },
					row('Received (RX)', 'netrx', F.rate, netTop),
					row('Sent (TX)', 'nettx', F.rate, netTop),
					{ group: 'Disk' },
					row('Read', 'diskread', F.rate, diskTop),
					row('Write', 'diskwrite', F.rate, diskTop),
					{ group: 'Processes' },
					row('Running', 'procsrun', F.num, Math.max(1, max(d.procsrun) * 1.05)),
					row('Total', 'procstotal', F.int, Math.max(1, max(d.procstotal) * 1.05))
				);
				NodeSpark.build(document.getElementById('nodeDetailGrid'), t, rows, 'Metric');

				/* the same data as a table (newest first), so every value can be read without hovering. Built the first time
				   it's opened. Text is set with textContent */
				const fmtOr = function(f) { return function(v) { return (v == null) ? '-' : f(v); }; };
				const tableCols = [
					['Load 1', 'load1', F.num], ['Load 5', 'load5', F.num], ['Load 15', 'load15', F.num], ['Cores', 'cores', F.int],
					['CPU busy', 'cpubusy', F.pct], ['User', 'cpuuser', F.pct], ['System', 'cpusystem', F.pct], ['I/O wait', 'cpuiowait', F.pct], ['Steal', 'cpusteal', F.pct],
					['Mem used %', 'mempct', F.pct], ['Mem used', 'memused', F.mem], ['Cache', 'memcache', F.mem], ['Total', 'memtotal', F.mem], ['Swap used', 'swapused', F.mem],
					['Net received', 'netrx', F.rate], ['Net sent', 'nettx', F.rate], ['Disk read', 'diskread', F.rate], ['Disk write', 'diskwrite', F.rate],
					['Procs running', 'procsrun', F.num], ['Procs total', 'procstotal', F.int]
				];
				let tableBuilt = false;
				const tableEl = document.getElementById('nodeTable');
				const toggleEl = document.getElementById('nodeTableToggle');
				toggleEl.addEventListener('click', function() {
					const show = (tableEl.style.display === 'none');
					if (show && !tableBuilt) {
						const table = document.createElement('table');
						table.className = 'ui very compact celled striped small table';
						const thead = table.createTHead().insertRow();
						['Time'].concat(tableCols.map(function(c) { return c[0]; })).forEach(function(h) {
							const th = document.createElement('th');
							th.textContent = h;
							th.style.position = 'sticky';
							th.style.top = '0';
							thead.appendChild(th);
						});
						const tbody = table.createTBody();
						for (let i = t.length - 1; i >= 0; i--) {
							/* skip times with no samples */
							if (tableCols.every(function(c) { return d[c[1]][i] == null; })) continue;
							const tr = tbody.insertRow();
							tr.insertCell().textContent = new Date(t[i] * 1000).toLocaleString();
							tableCols.forEach(function(c) {
								const cell = tr.insertCell();
								cell.textContent = fmtOr(c[2])(d[c[1]][i]);
								cell.style.textAlign = 'right';
								cell.style.fontVariantNumeric = 'tabular-nums';
								cell.style.whiteSpace = 'nowrap';
							});
						}
						tableEl.appendChild(table);
						tableBuilt = true;
					}
					tableEl.style.display = show ? 'block' : 'none';
					toggleEl.innerHTML = show ? '<i class="table icon"></i> Hide data table' : '<i class="table icon"></i> Show data table';
				});
			});
		</script>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayDiskUsage ------------------- */
	/* -------------------------------------------- */
	/* total/used/free disk space for the analysis directories (analysisdir and analysisdirb from nidb.cfg) */
	function DisplayDiskUsage() {
		$dirs = array(
			'analysisdir'  => $GLOBALS['cfg']['analysisdir'] ?? '',
			'analysisdirb' => $GLOBALS['cfg']['analysisdirb'] ?? ''
		);
		?>
		<table class="ui very compact celled grey table">
			<thead>
				<tr>
					<th>Config name</th>
					<th>Path</th>
					<th class="right aligned">Total</th>
					<th class="right aligned">Used</th>
					<th class="right aligned">Free</th>
					<th>Usage</th>
				</tr>
			</thead>
			<tbody>
			<?
			foreach ($dirs as $configname => $path) {
				$total = false;
				$free = false;
				if (($path != '') && @file_exists($path)) {
					$total = @disk_total_space($path);
					$free = @disk_free_space($path);
				}
				?>
				<tr>
					<td class="tt"><?=$configname?></td>
					<td class="tt"><?= ($path == '') ? '<span style="color:#999">(not set)</span>' : htmlspecialchars($path) ?></td>
				<?
				if ($path == '') {
					?><td colspan="4"></td><?
				}
				elseif (($total === false) || ($total === null) || ($total <= 0)) {
					?><td colspan="4"><i class="red exclamation circle icon"></i> Path not found or disk space unavailable</td><?
				}
				else {
					if (($free === false) || ($free === null) || ($free < 0))
						$free = 0;
					$used = $total - $free;
					$usedpct = round(($used / $total) * 100);

					/* green normally, orange when getting full, red when nearly full */
					$color = "#21ba45";
					if ($usedpct >= 90) $color = "#db2828";
					elseif ($usedpct >= 75) $color = "#f2711c";
					?>
					<td class="right aligned"><?=HumanReadableFilesize($total)?></td>
					<td class="right aligned"><?=HumanReadableFilesize($used)?></td>
					<td class="right aligned"><?=HumanReadableFilesize($free)?></td>
					<td>
						<div style="position:relative; width:200px; max-width:100%; height:16px; background:#e8e8e8; border-radius:3px; overflow:hidden;">
							<div style="position:absolute; left:0; top:0; height:100%; width:<?=$usedpct?>%; background:<?=$color?>;"></div>
							<div style="position:absolute; width:100%; text-align:center; font-size:11px; line-height:16px; color:#333;"><?=$usedpct?>%</div>
						</div>
					</td>
					<?
				}
				?>
				</tr>
				<?
			}
			?>
			</tbody>
		</table>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayPipelines ------------------- */
	/* -------------------------------------------- */
	/* pipeline analyses that are currently queued or running (pending/processing/submitted) */
	function DisplayPipelines() {
		$sqlstring = "select a.analysis_id, a.pipeline_id, p.pipeline_name, u.username, s.uid, st.study_num, a.analysis_startdate, a.analysis_clusterstartdate, a.analysis_status, a.analysis_statusmessage, p.pipeline_clustertype, a.analysis_qsubid, a.analysis_hostname from analysis a left join pipelines p on a.pipeline_id = p.pipeline_id left join users u on p.pipeline_admin = u.user_id left join studies st on a.study_id = st.study_id left join enrollment e on st.enrollment_id = e.enrollment_id left join subjects s on e.subject_id = s.subject_id where a.analysis_status in ('pending', 'processing', 'submitted') order by a.analysis_clusterstartdate desc, a.analysis_startdate desc";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$rows = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$rows[] = array(
				'analysisid'       => (int)($row['analysis_id'] ?? 0),
				'pipelineid'       => (int)($row['pipeline_id'] ?? 0),
				'pipeline'         => (string)($row['pipeline_name'] ?? ''),
				'owner'            => (string)($row['username'] ?? ''),
				'study'            => (string)(($row['uid'] ?? '') . ($row['study_num'] ?? '')),
				'startdate'        => (string)($row['analysis_startdate'] ?? ''),
				'clusterstartdate' => (string)($row['analysis_clusterstartdate'] ?? ''),
				'status'           => (string)($row['analysis_status'] ?? ''),
				'statusmessage'    => (string)($row['analysis_statusmessage'] ?? ''),
				'clustertype'      => (string)($row['pipeline_clustertype'] ?? ''),
				'qsubid'           => (string)($row['analysis_qsubid'] ?? ''),
				'hostname'         => (string)($row['analysis_hostname'] ?? ''),
			);
		}
		$rowsJson = json_encode($rows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
		?>
		<!-- ag-grid v36 uses the Theming API (styles injected by the JS); no CSS import or theme class needed -->

		<!-- break out of the constrained .ui.container so the grid spans (nearly) the full page width -->
		<div style="width:96vw; margin-left:calc(50% - 48vw)">
			<div style="display:flex; align-items:center; gap:10px; margin-bottom:8px">
				<input type="text" id="pipelineFilter" placeholder="Search..." oninput="pipelineGridApi.setGridOption('quickFilterText', this.value)" style="padding:5px 8px; width:250px; border:1px solid #ccc; border-radius:4px">
				<span style="color:#888">Displaying <span id="pipelineCount"><?=count($rows)?></span> analyses</span>
				<div class="ui selection dropdown" id="withSelectedDropdown" style="margin-left:auto">
					<input type="hidden" id="withSelectedValue">
					<i class="dropdown icon"></i>
					<div class="default text">With selected...</div>
					<div class="menu">
						<div class="item" data-value="markanalysescomplete">Mark analyses complete</div>
						<div class="item" data-value="deleteanalyses">Delete analysis</div>
					</div>
				</div>
			</div>
			<div id="pipelinesGrid" style="height:60vh; width:100%"></div>
		</div>

		<script src="//cdn.jsdelivr.net/npm/ag-grid-community@36/dist/ag-grid-community.min.js"></script>
		<script>
		let pipelineGridApi;
		const CSRF_TOKEN = <?=json_encode($_SESSION['csrftoken'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)?>;
		(function() {
			const rowData = <?=$rowsJson?>;

			const columnDefs = [
				{ field: 'pipeline', headerName: 'Pipeline', flex: 2, cellRenderer: function(params) {
						const a = document.createElement('a');
						a.href = 'pipelines.php?pipelineid=' + encodeURIComponent(params.data.pipelineid);
						a.textContent = params.value || '';
						return a;
					}
				},
				{ field: 'owner',            headerName: 'Owner',               flex: 1 },
				{ field: 'study',            headerName: 'Study',               flex: 1 },
				{ field: 'startdate',        headerName: 'Analysis start date', flex: 1 },
				{ field: 'clusterstartdate', headerName: 'Cluster start date',  flex: 1 },
				{ field: 'status',           headerName: 'Status',              width: 130 },
				{ field: 'statusmessage',    headerName: 'Status message',      flex: 1 },
				{ field: 'clustertype',      headerName: 'Cluster type',        width: 130 },
				{ field: 'qsubid',           headerName: 'Qsub ID',             width: 130 },
				{ field: 'hostname',         headerName: 'Hostname',            flex: 1 },
			];

			const gridOptions = {
				columnDefs: columnDefs,
				rowData: rowData,
				defaultColDef: { sortable: true, filter: true, resizable: true },
				/* v36 object-form selection: adds a checkbox column + header select-all checkbox.
				   enableClickSelection defaults to false, so clicking the Pipeline link (or a row)
				   does not change the selection - only the checkbox does. */
				rowSelection: { mode: 'multiRow', checkboxes: true, headerCheckbox: true },
				/* keep the "Displaying X analyses" count in sync with the rows actually shown
				   (updates as the quick filter narrows the grid) */
				onModelUpdated: function(params) {
					const el = document.getElementById('pipelineCount');
					if (el) el.textContent = params.api.getDisplayedRowCount();
				},
				/* no theme set -> v36 defaults to the Quartz theme via the Theming API */
			};

			const gridDiv = document.getElementById('pipelinesGrid');
			pipelineGridApi = agGrid.createGrid(gridDiv, gridOptions);
		})();

		/* returns the data objects for the currently checked rows */
		function getSelectedPipelineRows() {
			return pipelineGridApi ? pipelineGridApi.getSelectedRows() : [];
		}

		/* mark the selected analyses complete with a message */
		function markSelectedAnalysesComplete() {
			const rows = getSelectedPipelineRows();
			const ids = rows.map(function(r) { return r.analysisid; }).filter(function(id) { return id > 0; });
			if (ids.length === 0) { alert('No rows are selected.'); return; }
			if (!confirm('Mark ' + ids.length + ' selected analys' + (ids.length === 1 ? 'is' : 'es') + ' as complete?')) return;

			fetch('ajaxapi.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: 'action=markanalysescomplete&csrftoken=' + encodeURIComponent(CSRF_TOKEN) + '&analysisids=' + encodeURIComponent(JSON.stringify(ids))
			})
				.then(function(r) { return r.ok ? r.json() : null; })
				.then(function(d) {
					if (d && d.ok) location.reload();   /* completed rows drop out of the pending/processing/submitted filter */
					else alert('Error marking analyses as complete' + (d && d.error ? ': ' + d.error : '.'));
				})
				.catch(function(e) { alert('Error marking analyses as complete: ' + e); });
		}

		/* queue the selected analyses for permanent deletion */
		function deleteSelectedAnalyses() {
			const rows = getSelectedPipelineRows();
			const ids = rows.map(function(r) { return r.analysisid; }).filter(function(id) { return id > 0; });
			if (ids.length === 0) { alert('No rows are selected.'); return; }
			if (!confirm('Delete ' + ids.length + ' selected analys' + (ids.length === 1 ? 'is' : 'es') + '?\n\nThe analysis data will be queued for permanent deletion.')) return;

			fetch('ajaxapi.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: 'action=deleteanalyses&csrftoken=' + encodeURIComponent(CSRF_TOKEN) + '&analysisids=' + encodeURIComponent(JSON.stringify(ids))
			})
				.then(function(r) { return r.ok ? r.json() : null; })
				.then(function(d) {
					if (d && d.ok) location.reload();
					else alert('Error deleting analyses' + (d && d.error ? ': ' + d.error : '.'));
				})
				.catch(function(e) { alert('Error deleting analyses: ' + e); });
		}

		/* wire the 'With selected...' dropdown. Explicit init in case it renders after the global
		   .ui.dropdown setup has run. Reset to the placeholder after each action. */
		$(function() {
			$('#withSelectedDropdown').dropdown({
				onChange: function(value) {
					if (value === 'markanalysescomplete') markSelectedAnalysesComplete();
					else if (value === 'deleteanalyses') deleteSelectedAnalyses();
					if (value !== '') $('#withSelectedDropdown').dropdown('clear');
				}
			});
		});
		</script>
		<?
	}


	/* ============================================================ */
	/*  SGE                                                          */
	/* ============================================================ */

	/* -------------------------------------------- */
	/* ------- DisplaySGETabs --------------------- */
	/* -------------------------------------------- */
	function DisplaySGETabs($activetab) {
		$validtabs = array('qstatjobs', 'qstatusage', 'nodes', 'queues');
		if (!in_array($activetab, $validtabs)) $activetab = 'qstatjobs';

		$tabs = [
			'qstatjobs'   => '<tt>qstat</tt>&nbsp;jobs',
			'qstatusage'  => '<tt>qstat</tt>&nbsp;usage',
			'nodes'       => 'Nodes',
			'queues'      => 'Queues',
		];
		?>
		<div class="ui top attached tabular menu large">
			<? foreach ($tabs as $key => $label) { ?>
				<a class="<?=($activetab===$key?'active':'')?> item" data-tab="<?=$key?>"><?=$label?></a>
			<? } ?>
		</div>

		<div class="ui bottom attached <?=($activetab==='qstatjobs'?'active':'')?> tab segment" data-tab="qstatjobs">
			<? DisplayQstatJobs(); ?>
		</div>
		<div class="ui bottom attached <?=($activetab==='qstatusage'?'active':'')?> tab segment" data-tab="qstatusage">
			<? DisplayQstatUsage(); ?>
		</div>
		<div class="ui bottom attached <?=($activetab==='nodes'?'active':'')?> tab segment" data-tab="nodes">
			<? DisplayNodes(); ?>
		</div>
		<div class="ui bottom attached <?=($activetab==='queues'?'active':'')?> tab segment" data-tab="queues">
			<? DisplayQueues(); ?>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- GetClusterStats (SGE) -------------- */
	/* -------------------------------------------- */
	function GetClusterStats() {
		$command = "ssh " . $GLOBALS['cfg']['clustersubmithost'] . " qstat -f -u '*' 2>&1";
		$statsoutput = explode("\n", shell_exec($command));

		$hostname = $queue = "";
		$hostnames = $queues = array();
		$report = array();

		foreach ($statsoutput as $line) {
			$line = trim($line);
			if (!strstr($line, '------')) {
				if (trim($line == "")) {
					break;
				}
				if (strstr($line, 'queuename')) {
					continue;
				}

				if (strstr($line, '@')) {
					list($queuehost, $unk, $usage, $cpu, $arch, $states) = preg_split('/\s+/', $line);
					list($queue, $hostname) = explode('@', $queuehost);
					list($slotsres, $slotsused, $slotsavailable) = explode('/', $usage);
					$report[$hostname]['queues'][$queue] = null;
					$report[$hostname]['cpu'] = $cpu;
					$report[$hostname]['arch'] = $arch;
					$report[$hostname]['queues'][$queue]['slotsused'] = $slotsused;
					$report[$hostname]['queues'][$queue]['slotsavailable'] = $slotsavailable;
					$report[$hostname]['states'] = $states;

					if ((!isset($hostnames)) || (!in_array($hostname, $hostnames))) {
						$hostnames[] = $hostname;
					}
					if ((!isset($queues)) || (!in_array($queue, $queues))) {
						$queues[] = $queue;
					}
				}
				else {
					$report[$hostname]['queues'][$queue]['jobs'][] = $line;
					$report[$hostname]['queues'][$queue]['slotsused'] = $slotsused;
					$report[$hostname]['queues'][$queue]['slotsavailable'] = $slotsavailable;
				}
			}
		}
		sort($hostnames);
		sort($queues);

		return array($statsoutput, $report, $queues, $hostnames);
	}


	/* -------------------------------------------- */
	/* ------- DisplayQstatJobs ------------------- */
	/* -------------------------------------------- */
	function DisplayQstatJobs() {
		$command = "ssh " . $GLOBALS['cfg']['clustersubmithost'] . " qstat 2>&1";
		$statsoutput = explode("\n", shell_exec($command));
		?>
		<div class="ui fluid basic segment" style="padding: 0;">
			<div class="ui styled segment" style="font-family: monospace; white-space: pre; overflow-x: auto;"><?
			foreach ($statsoutput as $line) {
				$line = trim($line);
				echo "$line\n";
			}
		?>
			</div>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayQstatUsage ------------------ */
	/* -------------------------------------------- */
	function DisplayQstatUsage() {
		$command = "ssh " . $GLOBALS['cfg']['clustersubmithost'] . " qstat -f -u '*' 2>&1";
		$statsoutput = explode("\n", shell_exec($command));
		?>
		<div class="ui fluid basic segment" style="padding: 0;">
			<div class="ui styled segment" style="font-family: monospace; white-space: pre; overflow-x: auto;"><?
			foreach ($statsoutput as $line) {
				if (!strstr($line, '------')) {
					$line = trim($line);
					echo "$line\n";
				}
			}
		?>
			</div>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayNodes (SGE) ----------------- */
	/* -------------------------------------------- */
	function DisplayNodes() {
		list($statsoutput, $report, $queues, $hostnames) = GetClusterStats();

		$slotsusedcolor = "e89b9f";
		$slotsunusedcolor = "EEEEEE";
		$totalClusterSlotsAvailable = 0;
		$totalClusterSlotsUsed = 0;
		?>
		<table class="ui small very compact celled grey table">
			<thead>
				<tr>
					<th>Node</th>
					<th>Arch</th>
					<th>States</th>
					<th>Load</th>
					<th>Total slots</th>
					<th>% slots in use</th>
				</tr>
			</thead>
			<?
				foreach ($hostnames as $hostname) {
					$slotsavailable = 0;
					$slotsused = 0;
					foreach ($report[$hostname]['queues'] as $queue => $info) {
						$slotsavailable += $info['slotsavailable'];
						$slotsused += $info['slotsused'];
					}

					$totalClusterSlotsAvailable += $slotsavailable;
					$totalClusterSlotsUsed += $slotsused;

					$load = $report[$hostname]['cpu'];
					$arch = $report[$hostname]['arch'];
					$states = $report[$hostname]['states'];
					?>
					<tr>
						<td><?=$hostname?></td>
						<td><?=$arch?></td>
						<td><?=$states?></td>
						<td><?=$load?></td>
						<td><?=$slotsavailable?></td>
						<td><img src="ajaxapi.php?action=horizontalchart&b=yes&w=200&h=10&v=<?=$slotsused?>,<?=($slotsavailable-$slotsused)?>&c=<?=$slotsusedcolor?>,<?=$slotsunusedcolor?>"> &nbsp; <span class="tiny"><?=$slotsused?> of <?=$slotsavailable?></span></td>
					</tr>
					<?
				}
			?>
			<tfoot>
				<tr>
					<td>Totals</td>
					<td></td>
					<td></td>
					<td></td>
					<td><?=$totalClusterSlotsAvailable?></td>
					<td><img src="ajaxapi.php?action=horizontalchart&b=yes&w=200&h=10&v=<?=$totalClusterSlotsUsed?>,<?=($totalClusterSlotsAvailable-$totalClusterSlotsUsed)?>&c=darkred,<?=$slotsunusedcolor?>"> &nbsp; <?=$totalClusterSlotsUsed?> of <?=$totalClusterSlotsAvailable?></td>
				</tr>
			</tfoot>
		</table>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayQueues (SGE) ---------------- */
	/* -------------------------------------------- */
	function DisplayQueues() {
		list($statsoutput, $report, $queues, $hostnames) = GetClusterStats();

		$slotsusedcolor = "FF4500";
		$slotsunusedcolor = "EEEEEE";
		?>
		<table class="ui table">
			<tr>
				<td valign="top">
					<table border="0">
						<tr>
							<td>Queue</td>
						<?
							foreach ($queues as $queue) {
								$slotsused = 0;
								$slotsunused = 0;

								foreach ($hostnames as $hostname) {
									if (isset($report[$hostname]['queues'][$queue])) {
										echo "<pre>";
										$slotsused += $report[$hostname]['queues'][$queue]['slotsused'];
										$slotsunused += $report[$hostname]['queues'][$queue]['slotsavailable'];
										echo "</pre>";
									}
								}
								?>
								<tr>
									<td><b><?=$queue?></b> &nbsp;</td>
									<td>
										<img src="ajaxapi.php?action=horizontalchart&b=yes&w=600&h=25&v=<?=$slotsused?>,<?=($slotsunused-$slotsused)?>&c=<?=$slotsusedcolor?>,<?=$slotsunusedcolor?>">
										<? if (($slotsused == 0) && ($slotsunused == 0)) { echo "Idle"; } else { echo "$slotsused of $slotsunused"; } ?>
									</td>
								</tr>
								<?
							}
						?>
					</table>
				</td>
				<td valign="top"></td>
			</tr>
		</table>
		<?
	}


	/* ============================================================ */
	/*  Slurm                                                        */
	/* ============================================================ */

	/* -------------------------------------------- */
	/* ------- GetSlurmCluster -------------------- */
	/* -------------------------------------------- */
	function GetSlurmCluster() {
		$sqlstring = "SELECT * FROM compute_cluster WHERE cluster_type = 'slurm' ORDER BY cluster_name LIMIT 1";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		return mysqli_fetch_array($result, MYSQLI_ASSOC);
	}


	/* -------------------------------------------- */
	/* ------- SlurmSSH -------------------------- */
	/* -------------------------------------------- */
	function SlurmSSH($cluster, $cmd) {
		$user = $cluster['submithost_username'];
		$host = $cluster['submit_hostname'];
		if ($user === '' || $host === '') return '';
		$safecmd = escapeshellarg($cmd);
		return shell_exec("ssh {$user}@{$host} {$safecmd} 2>&1");
	}


	/* -------------------------------------------- */
	/* ------- SlurmMemToMB ----------------------- */
	/* -------------------------------------------- */
	/* Converts a Slurm memory string to megabytes. Handles K/M/G/T suffixes and the trailing
	   per-node/per-cpu markers (n/c) that ReqMem can carry (e.g. '4Gn', '4000Mc', '1234K'); a bare
	   number is assumed to already be MB (sacct is queried with --units=M). Returns 0 for blanks. */
	function SlurmMemToMB($s) {
		$s = trim((string)$s);
		if ($s === '' || !preg_match('/[0-9]/', $s)) return 0.0;
		$s = rtrim($s, 'ncNC');   /* drop per-node / per-cpu marker */
		if (!preg_match('/^([0-9]*\.?[0-9]+)\s*([KMGT]?)/i', $s, $m)) return 0.0;
		$val = (float)$m[1];
		switch (strtoupper($m[2])) {
			case 'K': return $val / 1024.0;
			case 'G': return $val * 1024.0;
			case 'T': return $val * 1024.0 * 1024.0;
			default:  return $val;   /* M or bare (already MB) */
		}
	}


	/* -------------------------------------------- */
	/* ------- DisplaySlurmCommand --------------- */
	/* -------------------------------------------- */
	/* renders the command(s) used to populate a tab, at the top of the block */
	function DisplaySlurmCommand($cmds) {
		if (!is_array($cmds)) $cmds = array($cmds);
		?>
		<div class="slurm-cmd" style="margin:0 0 10px 0; background:#f7f7f7; border-left:3px solid #2185d0; padding:6px 10px; font-family:monospace; font-size:.85em; overflow-x:auto; white-space:nowrap">
			<? foreach ($cmds as $c) { ?>
				<div><i class="terminal icon" style="color:#2185d0"></i> <?=htmlspecialchars($c)?></div>
			<? } ?>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplaySlurmTabs ------------------- */
	/* -------------------------------------------- */
	function DisplaySlurmTabs($activetab) {
		$cluster = GetSlurmCluster();
		if (!$cluster) {
			?>
			<div class="ui warning message">
				<div class="header">No Slurm cluster configured</div>
				<p>No cluster of type <b>slurm</b> was found. Slurm credentials (submit hostname and username) are managed on the <a href="clustersettings.php">Cluster settings</a> page.</p>
			</div>
			<?
			return;
		}

		$validtabs = array('summary', 'jobs', 'nodes', 'partitions', 'history');
		if (!in_array($activetab, $validtabs)) $activetab = 'summary';

		$tabs = [
			'summary'    => 'Summary',
			'jobs'       => 'Jobs',
			'nodes'      => 'Nodes',
			'partitions' => 'Partitions',
			'history'    => 'Recent jobs',
		];
		?>
		<div class="ui tiny message" style="margin-bottom:8px">
			<i class="server icon"></i>
			Cluster: <b><?=htmlspecialchars($cluster['cluster_name'])?></b> &nbsp;&mdash;&nbsp;
			<tt><?=htmlspecialchars($cluster['submithost_username'])?>@<?=htmlspecialchars($cluster['submit_hostname'])?></tt>
		</div>

		<div class="ui top attached tabular menu large">
			<? foreach ($tabs as $key => $label) { ?>
				<a class="<?=($activetab===$key?'active':'')?> item" data-tab="slurm-<?=$key?>"><?=$label?></a>
			<? } ?>
		</div>

		<div class="ui bottom attached <?=($activetab==='summary'?'active':'')?> tab segment" data-tab="slurm-summary">
			<? DisplaySlurmSummary($cluster); ?>
		</div>
		<div class="ui bottom attached <?=($activetab==='jobs'?'active':'')?> tab segment" data-tab="slurm-jobs">
			<? DisplaySlurmJobs($cluster); ?>
		</div>
		<div class="ui bottom attached <?=($activetab==='nodes'?'active':'')?> tab segment" data-tab="slurm-nodes">
			<? DisplaySlurmNodes($cluster); ?>
		</div>
		<div class="ui bottom attached <?=($activetab==='partitions'?'active':'')?> tab segment" data-tab="slurm-partitions">
			<? DisplaySlurmPartitions($cluster); ?>
		</div>
		<div class="ui bottom attached <?=($activetab==='history'?'active':'')?> tab segment" data-tab="slurm-history">
			<? DisplaySlurmHistory($cluster); ?>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplaySlurmJobs ------------------- */
	/* -------------------------------------------- */
	function DisplaySlurmJobs($cluster) {
		$cmd = 'squeue --all';
		DisplaySlurmCommand($cmd);
		$output = SlurmSSH($cluster, $cmd);
		?>
		<div class="ui fluid basic segment" style="padding:0">
			<div class="ui styled segment" style="font-family:monospace; white-space:pre; overflow-x:auto"><?=htmlspecialchars($output)?></div>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplaySlurmNodes ------------------ */
	/* -------------------------------------------- */
	function DisplaySlurmNodes($cluster) {
		/* fetch CPU/state/load/memory and GRES in two passes, keyed by node name */
		$cmdMain = 'sinfo -N --format="%N|%T|%O|%C|%m" --noheader';
		$cmdGres = 'sinfo -N --format="%N|%G" --noheader';
		DisplaySlurmCommand(array($cmdMain, $cmdGres));

		$raw = SlurmSSH($cluster, $cmdMain);
		$lines = array_filter(array_map('trim', explode("\n", $raw)));

		$rawGres = SlurmSSH($cluster, $cmdGres);
		$gresMap = [];
		foreach (array_filter(array_map('trim', explode("\n", $rawGres))) as $gl) {
			$gp = explode('|', $gl, 2);
			if (count($gp) === 2) $gresMap[trim($gp[0])] = trim($gp[1]);
		}

		$usedcolor   = "e89b9f";
		$unusedcolor = "EEEEEE";
		$totalUsed = $totalCPUs = 0;
		$hasGPU = false;

		$seen = [];
		$rows = [];
		foreach ($lines as $line) {
			$parts = explode('|', $line);
			if (count($parts) < 5) continue;
			list($node, $state, $load, $cpus, $mem) = $parts;
			if (isset($seen[$node])) continue;
			$seen[$node] = true;
			$cpuparts  = explode('/', $cpus);
			$allocated = (int)($cpuparts[0] ?? 0);
			$total     = (int)($cpuparts[3] ?? 0);
			$totalUsed += $allocated;
			$totalCPUs += $total;
			$gres = $gresMap[$node] ?? '';
			$gpu = ($gres === '' || $gres === '(null)') ? '' : $gres;
			if ($gpu !== '') $hasGPU = true;
			$rows[] = compact('node', 'state', 'load', 'allocated', 'total', 'mem', 'gpu');
		}
		?>
		<? if (empty($rows)) { ?>
			<div class="ui placeholder segment"><div class="ui icon header"><i class="server icon"></i>No node data returned</div></div>
		<? } else { ?>
		<table class="ui small very compact celled grey table">
			<thead>
				<tr>
					<th>Node</th>
					<th>State</th>
					<th>Load</th>
					<th>Memory</th>
					<? if ($hasGPU) { ?><th>GPU</th><? } ?>
					<th>Total cores</th>
					<th>Cores in use</th>
				</tr>
			</thead>
			<tbody>
			<? foreach ($rows as $r) {
				$idle = $r['total'] - $r['allocated'];
				$statecolor = '';
				if ($r['state'] === 'idle')      $statecolor = 'style="color:#21ba45"';
				elseif ($r['state'] === 'down')  $statecolor = 'style="color:#db2828"';
				elseif ($r['state'] === 'mixed' || $r['state'] === 'allocated') $statecolor = 'style="color:#f2711c"';

				/* highlight the whole row when the load average exceeds the node's core count
				   (i.e. the node is oversubscribed). Load can be "N/A" on down nodes, so require
				   a numeric value and a nonzero core count before comparing. */
				$overloaded = (is_numeric($r['load']) && ($r['total'] > 0) && ((float)$r['load'] > $r['total']));
				$rowstyle = $overloaded ? ' style="background-color:#fbe3e4"' : '';
				?>
				<tr<?=$rowstyle?>>
					<td><?=htmlspecialchars($r['node'])?></td>
					<td <?=$statecolor?>><?=htmlspecialchars($r['state'])?></td>
					<td><?=htmlspecialchars($r['load'])?></td>
					<td><?=number_format((int)$r['mem'])?> MB</td>
					<? if ($hasGPU) { ?>
						<td><?=$r['gpu'] !== '' ? htmlspecialchars($r['gpu']) : '<span style="color:#ccc">—</span>'?></td>
					<? } ?>
					<td><?=$r['total']?></td>
					<td>
						<img src="ajaxapi.php?action=horizontalchart&b=yes&w=200&h=10&v=<?=$r['allocated']?>,<?=$idle?>&c=<?=$usedcolor?>,<?=$unusedcolor?>">
						&nbsp;<span class="tiny"><?=$r['allocated']?> of <?=$r['total']?></span>
					</td>
				</tr>
			<? } ?>
			</tbody>
			<tfoot>
				<tr>
					<td><b>Totals</b></td>
					<td></td>
					<td></td>
					<td></td>
					<? if ($hasGPU) { ?><td></td><? } ?>
					<td><?=$totalCPUs?></td>
					<td>
						<img src="ajaxapi.php?action=horizontalchart&b=yes&w=200&h=10&v=<?=$totalUsed?>,<?=($totalCPUs-$totalUsed)?>&c=darkred,<?=$unusedcolor?>">
						&nbsp;<?=$totalUsed?> of <?=$totalCPUs?>
					</td>
				</tr>
			</tfoot>
		</table>
		<? } ?>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplaySlurmPartitions ------------ */
	/* -------------------------------------------- */
	function DisplaySlurmPartitions($cluster) {
		/* sinfo -s gives partition summary: PARTITION AVAIL TIMELIMIT NODES(A/I/O/T) NODELIST */
		$cmd = 'sinfo -s --format="%P|%a|%l|%C" --noheader';
		DisplaySlurmCommand($cmd);
		$raw = SlurmSSH($cluster, $cmd);
		$lines = array_filter(array_map('trim', explode("\n", $raw)));

		$usedcolor   = "FF4500";
		$unusedcolor = "EEEEEE";

		$rows = [];
		foreach ($lines as $line) {
			$parts = explode('|', $line);
			if (count($parts) < 4) continue;
			list($partition, $avail, $timelimit, $cpus) = $parts;
			/* %C here is A/I/O/T CPUs across the partition */
			$cpuparts = explode('/', $cpus);
			$allocated = (int)($cpuparts[0] ?? 0);
			$total     = (int)($cpuparts[3] ?? 0);
			$rows[] = compact('partition', 'avail', 'timelimit', 'allocated', 'total');
		}
		?>
		<? if (empty($rows)) { ?>
			<div class="ui placeholder segment"><div class="ui icon header"><i class="sitemap icon"></i>No partition data returned</div></div>
		<? } else { ?>
		<table class="ui small very compact celled grey table">
			<thead>
				<tr>
					<th>Partition</th>
					<th>Available</th>
					<th>Time limit</th>
					<th>Total cores</th>
					<th>Cores in use</th>
				</tr>
			</thead>
			<tbody>
			<? foreach ($rows as $r) {
				$idle = $r['total'] - $r['allocated'];
				?>
				<tr>
					<td><b><?=htmlspecialchars(rtrim($r['partition'], '*'))?></b><?=substr($r['partition'], -1) === '*' ? ' <span class="ui tiny label">default</span>' : ''?></td>
					<td><?=htmlspecialchars($r['avail'])?></td>
					<td><?=htmlspecialchars($r['timelimit'])?></td>
					<td><?=$r['total']?></td>
					<td>
						<img src="ajaxapi.php?action=horizontalchart&b=yes&w=300&h=18&v=<?=$r['allocated']?>,<?=$idle?>&c=<?=$usedcolor?>,<?=$unusedcolor?>">
						&nbsp;<? if ($r['total'] == 0) { echo "—"; } else { echo $r['allocated'] . " of " . $r['total']; } ?>
					</td>
				</tr>
			<? } ?>
			</tbody>
		</table>
		<? } ?>
		<?
	}
	/* -------------------------------------------- */
	/* ------- DisplaySlurmSummary --------------- */
	/* -------------------------------------------- */
	function DisplaySlurmSummary($cluster) {
		/* node counts by state from sinfo -s */
		$cmdInfo  = 'sinfo -s --format="%P|%a|%D|%T" --noheader';
		/* job counts by state */
		$cmdQueue = 'squeue -a --format="%T" --noheader';
		/* Per-job requested vs max-used memory for running jobs. squeue supplies the running-job
		   list, node and requested memory; MaxRSS (live memory used) comes from sstat, because
		   sacct does not report MaxRSS while a job is still running. */
		$cmdJobs = 'squeue --states=RUNNING -a --format="%i|%u|%N|%m" --noheader';
		/* the actual sstat is built from the running job ids below; show a representative form */
		$cmdMemShow = 'sstat -a --parsable2 --noheader --format=JobID,MaxRSS -j <running job ids>';
		DisplaySlurmCommand(array($cmdInfo, $cmdQueue, $cmdJobs, $cmdMemShow));

		$rawInfo  = SlurmSSH($cluster, $cmdInfo);
		$rawQueue = SlurmSSH($cluster, $cmdQueue);
		$rawJobs  = SlurmSSH($cluster, $cmdJobs);

		/* running jobs keyed by job id (from squeue) */
		$memJobs = array();
		$runningIds = array();
		foreach (array_filter(array_map('trim', explode("\n", $rawJobs))) as $line) {
			$f = explode('|', $line);
			if (count($f) < 4) continue;
			list($jid, $juser, $node, $reqmem) = $f;
			$jid = trim($jid);
			if ($jid === '') continue;
			$memJobs[$jid] = array('jobid' => $jid, 'user' => trim($juser), 'node' => trim($node), 'reqmem' => SlurmMemToMB($reqmem), 'maxrss' => 0.0);
			$runningIds[] = $jid;
		}

		/* live MaxRSS via sstat for all running jobs in one call. Sanitize the ids (digits and the
		   array-task underscore only) before they go into the command. Step lines are 'JobID.step'. */
		$safeIds = array();
		foreach ($runningIds as $rid) {
			$rid = preg_replace('/[^0-9_]/', '', $rid);
			if ($rid !== '') $safeIds[] = $rid;
		}
		if (count($safeIds) > 0) {
			$cmdMem = 'sstat -a --parsable2 --noheader --format=JobID,MaxRSS -j ' . implode(',', $safeIds);
			$rawMem = SlurmSSH($cluster, $cmdMem);
			foreach (array_filter(array_map('trim', explode("\n", $rawMem))) as $line) {
				$f = explode('|', $line);
				if (count($f) < 2) continue;
				$base = strtok(trim($f[0]), '.');
				if (isset($memJobs[$base])) {
					$rss = SlurmMemToMB($f[1]);
					if ($rss > $memJobs[$base]['maxrss']) $memJobs[$base]['maxrss'] = $rss;
				}
			}
		}

		/* index -> list for sorting */
		$memJobs = array_values($memJobs);
		/* sort by node, then job id */
		usort($memJobs, function($a, $b) {
			$c = strnatcasecmp($a['node'], $b['node']);
			return $c !== 0 ? $c : strnatcasecmp($a['jobid'], $b['jobid']);
		});
		/* the largest of any requested/used value sets the full-width scale so rows are comparable */
		$memScaleMax = 0.0;
		foreach ($memJobs as $j) { $memScaleMax = max($memScaleMax, $j['maxrss'], $j['reqmem']); }

		/* Cross-reference each slurm job id against NiDB analyses (analysis.analysis_qsubid). Where a
		   match exists, show its pipeline name and UID/study number. One query for all running ids. */
		$analysisByQsub = array();
		$qsubInts = array();
		foreach ($memJobs as $j) { $q = (int)$j['jobid']; if ($q > 0) $qsubInts[$q] = true; }
		if (count($qsubInts) > 0) {
			$qsubids = array_keys($qsubInts);
			$idlist = implode(',', array_fill(0, count($qsubids), '?'));
			$sqlstring = "select a.analysis_qsubid, p.pipeline_name, s.uid, st.study_num from analysis a left join pipelines p on a.pipeline_id = p.pipeline_id left join studies st on a.study_id = st.study_id left join enrollment e on st.enrollment_id = e.enrollment_id left join subjects s on e.subject_id = s.subject_id where a.analysis_qsubid in ($idlist)";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, str_repeat('i', count($qsubids)), ...$qsubids);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $qsubids);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$analysisByQsub[(string)$row['analysis_qsubid']] = array(
					'pipeline' => (string)($row['pipeline_name'] ?? ''),
					'study'    => (string)(($row['uid'] ?? '') . ($row['study_num'] ?? '')),
				);
			}
		}

		/* tally jobs by state */
		$jobCounts = [];
		foreach (array_filter(array_map('trim', explode("\n", $rawQueue))) as $state) {
			$jobCounts[$state] = ($jobCounts[$state] ?? 0) + 1;
		}
		$running = $jobCounts['RUNNING']  ?? 0;
		$pending = $jobCounts['PENDING']  ?? 0;
		$other   = array_sum($jobCounts) - $running - $pending;

		/* parse partition summary for node counts (A/I/O/T) */
		$partRows = [];
		foreach (array_filter(array_map('trim', explode("\n", $rawInfo))) as $line) {
			$parts = explode('|', $line);
			if (count($parts) < 4) continue;
			list($partition, $avail, $nodes, $states) = $parts;
			$np = explode('/', $nodes);
			$partRows[] = [
				'partition' => rtrim($partition, '*'),
				'default'   => substr($partition, -1) === '*',
				'avail'     => $avail,
				'allocated' => (int)($np[0] ?? 0),
				'idle'      => (int)($np[1] ?? 0),
				'other'     => (int)($np[2] ?? 0),
				'total'     => (int)($np[3] ?? 0),
			];
		}
		$totalNodes     = array_sum(array_column($partRows, 'total'));
		$allocatedNodes = array_sum(array_column($partRows, 'allocated'));
		$idleNodes      = array_sum(array_column($partRows, 'idle'));
		$otherNodes     = array_sum(array_column($partRows, 'other'));
		?>

		<!-- job summary -->
		<h4 class="ui dividing header">Jobs</h4>
		<div style="margin-bottom:18px; line-height:1.9">
			<b style="<?=$running>0?'color:#21ba45':''?>"><?=$running?></b> running
			&nbsp;&middot;&nbsp;
			<b style="<?=$pending>0?'color:#f2711c':''?>"><?=$pending?></b> pending
			&nbsp;&middot;&nbsp;
			<b><?=$other?></b> other
		</div>

		<!-- node summary -->
		<h4 class="ui dividing header">Nodes</h4>
		<div style="margin-bottom:18px; line-height:1.9">
			<b><?=$totalNodes?></b> total
			&nbsp;&middot;&nbsp;
			<b style="<?=$allocatedNodes>0?'color:#f2711c':''?>"><?=$allocatedNodes?></b> allocated
			&nbsp;&middot;&nbsp;
			<b style="<?=$idleNodes>0?'color:#21ba45':''?>"><?=$idleNodes?></b> idle
			&nbsp;&middot;&nbsp;
			<b style="<?=$otherNodes>0?'color:#db2828':''?>"><?=$otherNodes?></b> other / down
		</div>

		<!-- per-partition node breakdown -->
		<? if (!empty($partRows)) { ?>
		<h4 class="ui dividing header">Nodes per partition</h4>
		<table class="ui small very compact celled grey table">
			<thead>
				<tr>
					<th>Partition</th>
					<th>Available</th>
					<th>Total nodes</th>
					<th>Allocated</th>
					<th>Idle</th>
					<th>Other/down</th>
				</tr>
			</thead>
			<tbody>
			<? foreach ($partRows as $p) { ?>
				<tr>
					<td><b><?=htmlspecialchars($p['partition'])?></b><?=$p['default']?' <span class="ui tiny label">default</span>':''?></td>
					<td><?=htmlspecialchars($p['avail'])?></td>
					<td><?=$p['total']?></td>
					<td><?=$p['allocated']?></td>
					<td><?=$p['idle']?></td>
					<td><?=$p['other']?></td>
				</tr>
			<? } ?>
			</tbody>
		</table>
		<? } ?>

		<!-- per-job memory usage: light-red bar = max memory used, light-gray = requested minimum overlaid on the left -->
		<h4 class="ui dividing header">Memory usage by running job</h4>
		<? if (empty($memJobs)) { ?>
			<div class="ui info message">No running jobs, or accounting data (sacct) is not available.</div>
		<? } else {
			$memChartW = 300;   /* full-width in pixels, = the largest requested/used value */
			?>
			<table class="ui small very compact celled grey table">
				<thead>
					<tr>
						<th>Node</th>
						<th>Job</th>
						<th>User</th>
						<th>Pipeline</th>
						<th>Study</th>
						<th>Memory <span style="font-weight:normal; color:#888">(<span style="color:#c23b3b">&#9646;</span> max used &nbsp; <span style="color:#999">&#9646;</span> requested)</span></th>
					</tr>
				</thead>
				<tbody>
				<? foreach ($memJobs as $j) {
					$redW  = ($memScaleMax > 0) ? round(($j['maxrss'] / $memScaleMax) * $memChartW) : 0;
					$grayW = ($memScaleMax > 0) ? round(($j['reqmem'] / $memScaleMax) * $memChartW) : 0;
					$match = $analysisByQsub[(string)(int)$j['jobid']] ?? null;
					?>
					<tr>
						<td><?=htmlspecialchars($j['node'])?></td>
						<td><?=htmlspecialchars($j['jobid'])?></td>
						<td><?=htmlspecialchars($j['user'])?></td>
						<td><?=htmlspecialchars($match['pipeline'] ?? '')?></td>
						<td><?=htmlspecialchars($match['study'] ?? '')?></td>
						<td>
							<div style="position:relative; width:<?=$memChartW?>px; max-width:100%; height:16px; background:#f4f4f4; border:1px solid #eee">
								<div style="position:absolute; left:0; top:0; height:100%; width:<?=$redW?>px; background:#f5a9ad" title="Max used: <?=number_format($j['maxrss'])?> MB"></div>
								<div style="position:absolute; left:0; top:0; height:100%; width:<?=$grayW?>px; background:#c8c8c8" title="Requested: <?=number_format($j['reqmem'])?> MB"></div>
							</div>
							<span class="tiny" style="color:#888"><?=number_format($j['reqmem'])?> MB req &middot; <?=number_format($j['maxrss'])?> MB max</span>
						</td>
					</tr>
				<? } ?>
				</tbody>
			</table>
		<? } ?>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplaySlurmHistory --------------- */
	/* -------------------------------------------- */
	function DisplaySlurmHistory($cluster) {
		$start = date('Y-m-dT00:00:00', strtotime('-24 hours'));
		$cmd   = 'sacct -X -a --starttime=' . $start .
			' --format=JobID,JobName%30,User%15,Partition%15,State%12,Elapsed,CPUTime,NodeList%20 --noheader --parsable2';
		DisplaySlurmCommand($cmd);
		$raw   = SlurmSSH($cluster, $cmd);
		$lines = array_filter(array_map('trim', explode("\n", $raw)));

		$stateColors = [
			'COMPLETED'  => '#21ba45',
			'RUNNING'    => '#2185d0',
			'PENDING'    => '#f2711c',
			'FAILED'     => '#db2828',
			'CANCELLED'  => '#767676',
			'TIMEOUT'    => '#a333c8',
		];
		?>
		<? if (empty($lines)) { ?>
			<div class="ui info message">No jobs found in the past 24 hours.</div>
		<? } else { ?>
		<table class="ui small very compact celled grey table">
			<thead>
				<tr>
					<th>Job ID</th>
					<th>Job name</th>
					<th>User</th>
					<th>Partition</th>
					<th>State</th>
					<th>Elapsed</th>
					<th>Core time</th>
					<th>Nodes</th>
				</tr>
			</thead>
			<tbody>
			<? foreach ($lines as $line) {
				$f = explode('|', $line);
				if (count($f) < 8) continue;
				list($jobid, $jobname, $user, $partition, $state, $elapsed, $cputime, $nodelist) = $f;
				$basestate = explode(' ', $state)[0]; /* strip "by USER" suffix on CANCELLED */
				$color = $stateColors[$basestate] ?? '#555';
				?>
				<tr>
					<td><tt><?=htmlspecialchars($jobid)?></tt></td>
					<td><?=htmlspecialchars($jobname)?></td>
					<td><?=htmlspecialchars($user)?></td>
					<td><?=htmlspecialchars($partition)?></td>
					<td style="color:<?=$color?>;font-weight:bold"><?=htmlspecialchars($state)?></td>
					<td><tt><?=htmlspecialchars($elapsed)?></tt></td>
					<td><tt><?=htmlspecialchars($cputime)?></tt></td>
					<td><?=htmlspecialchars($nodelist)?></td>
				</tr>
			<? } ?>
			</tbody>
		</table>
		<? } ?>
		<?
	}
?>

<br><br><br><br>

<? include("footer.php") ?>
