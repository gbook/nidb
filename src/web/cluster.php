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
	if ($clustertype !== 'slurm') $clustertype = 'sge';

	$action = GetVariable("action");

	DisplayClusterPage($clustertype, $action);


	/* -------------------------------------------- */
	/* ------- DisplayClusterPage ----------------- */
	/* -------------------------------------------- */
	function DisplayClusterPage($clustertype, $action) {
		$sgeactive   = ($clustertype === 'sge')   ? 'primary' : 'basic';
		$slurmactive = ($clustertype === 'slurm') ? 'primary' : 'basic';
		?>
		<div class="ui container">
			<div style="display:flex; align-items:center; gap:10px; margin-bottom:16px">
				<h3 class="ui header" style="margin:0">Compute cluster</h3>
				<div class="ui buttons">
					<a href="cluster.php?clustertype=sge"   class="ui <?=$sgeactive?>   button">SGE</a>
					<a href="cluster.php?clustertype=slurm" class="ui <?=$slurmactive?> button">Slurm</a>
				</div>
			</div>

			<? if ($clustertype === 'slurm') { ?>
				<? DisplaySlurmTabs($action); ?>
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
				?>
				<tr>
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
