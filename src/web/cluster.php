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
	$validtabs = array('qstatjobs', 'qstatusage', 'nodes', 'queues');
	if (!in_array($action, $validtabs)) {
		$action = 'qstatjobs';
	}

	DisplayClusterTabs($action);


	/* -------------------------------------------- */
	/* ------- DisplayClusterTabs ----------------- */
	/* -------------------------------------------- */
	function DisplayClusterTabs($activetab) {
		$qstatjobsactive = ($activetab == 'qstatjobs') ? 'active' : '';
		$qstatusageactive = ($activetab == 'qstatusage') ? 'active' : '';
		$nodesactive = ($activetab == 'nodes') ? 'active' : '';
		$queuesactive = ($activetab == 'queues') ? 'active' : '';
		?>
		<div class="ui container">
			<h3 class="ui header">View cluster information</h3>

			<div class="ui top attached tabular menu large">
				<a class="<?=$qstatjobsactive?> item" data-tab="qstatjobs"><tt>qstat</tt> &nbsp;job output</a>
				<a class="<?=$qstatusageactive?> item" data-tab="qstatusage"><tt>qstat</tt> &nbsp;usage output</a>
				<a class="<?=$nodesactive?> item" data-tab="nodes">Nodes</a>
				<a class="<?=$queuesactive?> item" data-tab="queues">Queues</a>
			</div>

			<div class="ui bottom attached <?=$qstatjobsactive?> tab segment" data-tab="qstatjobs">
				<? DisplayQstatJobs(); ?>
			</div>
			<div class="ui bottom attached <?=$qstatusageactive?> tab segment" data-tab="qstatusage">
				<? DisplayQstatUsage(); ?>
			</div>
			<div class="ui bottom attached <?=$nodesactive?> tab segment" data-tab="nodes">
				<? DisplayNodes(); ?>
			</div>
			<div class="ui bottom attached <?=$queuesactive?> tab segment" data-tab="queues">
				<? DisplayQueues(); ?>
			</div>
		</div>

		<script>
			$(document).ready(function() {
				$('.tabular.menu .item').tab();
			});
		</script>
		<?
	}


	/* -------------------------------------------- */
	/* ------- GetClusterStats -------------------- */
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
	/* ------- DisplayNodes ----------------------- */
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
						<td><img src="horizontalchart.php?b=yes&w=200&h=10&v=<?=$slotsused?>,<?=($slotsavailable-$slotsused)?>&c=<?=$slotsusedcolor?>,<?=$slotsunusedcolor?>"> &nbsp; <span class="tiny"><?=$slotsused?> of <?=$slotsavailable?></span></td>
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
					<td><img src="horizontalchart.php?b=yes&w=200&h=10&v=<?=$totalClusterSlotsUsed?>,<?=($totalClusterSlotsAvailable-$totalClusterSlotsUsed)?>&c=darkred,<?=$slotsunusedcolor?>"> &nbsp; <?=$totalClusterSlotsUsed?> of <?=$totalClusterSlotsAvailable?></td>
				</tr>
			</tfoot>
		</table>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayQueues ---------------------- */
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
										<img src="horizontalchart.php?b=yes&w=600&h=25&v=<?=$slotsused?>,<?=($slotsunused-$slotsused)?>&c=<?=$slotsusedcolor?>,<?=$slotsunusedcolor?>">
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
?>

<br><br><br><br>

<? include("footer.php") ?>
