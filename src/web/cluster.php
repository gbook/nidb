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
	$clustertype = GetVariable("clustertype");
	if (!in_array($clustertype, array('slurm', 'pipelines'))) $clustertype = 'sge';

	$action = GetVariable("action");

	DisplayClusterPage($clustertype, $action);


	/* -------------------------------------------- */
	/* ------- DisplayClusterPage ----------------- */
	/* -------------------------------------------- */
	function DisplayClusterPage($clustertype, $action) {
		$sgeactive       = ($clustertype === 'sge')       ? 'primary' : 'basic';
		$slurmactive     = ($clustertype === 'slurm')     ? 'primary' : 'basic';
		$pipelinesactive = ($clustertype === 'pipelines') ? 'primary' : 'basic';
		?>
		<div class="ui container">
			<div style="display:flex; align-items:center; gap:10px; margin-bottom:16px">
				<h3 class="ui header" style="margin:0">Compute cluster</h3>
				<div class="ui buttons">
					<a href="cluster.php?clustertype=sge"   class="ui <?=$sgeactive?>   button">SGE</a>
					<a href="cluster.php?clustertype=slurm" class="ui <?=$slurmactive?> button">Slurm</a>
				</div>
				<div class="ui buttons">
					<a href="cluster.php?clustertype=pipelines" class="ui <?=$pipelinesactive?> button">Pipelines</a>
				</div>

				<div style="margin-left:auto; display:flex; align-items:center; gap:10px">
					<span style="color:#888">Page loaded <?=date('M j, Y g:i:s a')?></span>
					<button type="button" class="ui basic button" onclick="location.reload()"><i class="sync icon"></i> Refresh</button>
				</div>
			</div>

			<? if ($clustertype === 'slurm') { ?>
				<? DisplaySlurmTabs($action); ?>
			<? } elseif ($clustertype === 'pipelines') { ?>
				<? DisplayPipelines(); ?>
			<? } else { ?>
				<? DisplaySGETabs($action); ?>
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
				body: 'action=markanalysescomplete&analysisids=' + encodeURIComponent(JSON.stringify(ids))
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
				body: 'action=deleteanalyses&analysisids=' + encodeURIComponent(JSON.stringify(ids))
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
		DisplaySlurmCommand(array($cmdInfo, $cmdQueue));

		$rawInfo  = SlurmSSH($cluster, $cmdInfo);
		$rawQueue = SlurmSSH($cluster, $cmdQueue);

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
