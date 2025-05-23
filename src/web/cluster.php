<?
 // ------------------------------------------------------------------------------
 // NiDB cluster.php
 // Copyright (C) 2004 - 2022
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

	/* ----- determine which action to take ----- */
	switch ($action) {
		case 'qstatjobs': DisplayQstatJobs(); break;
		case 'qstatusage': DisplayQstatUsage(); break;
		case 'nodes': DisplayNodes(); break;
		case 'queues': DisplayQueues(); break;
		default:
			DisplayQstatJobs();
	}


	/* -------------------------------------------- */
	/* ------- DisplayStatsMenu ------------------- */
	/* -------------------------------------------- */
	function DisplayStatsMenu() {
		?>
		<div class="ui center aligned container">
			<h3 class="ui header">View cluster information</h3>
			<a class="ui basic button" href="cluster.php?action=qstatjobs"><tt>qstat</tt> job output</a>
			<a class="ui basic button" href="cluster.php?action=qstatusage"><tt>qstat</tt> usage output</a>
			<a class="ui basic button" href="cluster.php?action=nodes"><i class="server icon"></i> Nodes</a>
			<a class="ui basic button" href="cluster.php?action=queues"><i class="tasks icon"></i> Queues</a>
		</div>
		<br><br>
		<?
	}	

	
	/* -------------------------------------------- */
	/* ------- GetClusterStats -------------------- */
	/* -------------------------------------------- */
	function GetClusterStats() {
		//$statsoutput = explode("\n",shell_exec("SGE_ROOT=/sge/sge-root; export SGE_ROOT; SGE_CELL=nrccell; export SGE_CELL; /sge/sge-root/bin/lx24-amd64/./qstat -f -u '*'"));
		$command = "ssh ".$GLOBALS['cfg']['clustersubmithost']." qstat -f -u '*' 2>&1";
		$statsoutput = explode("\n",shell_exec($command));
		
		//PrintVariable($command);
		//PrintVariable($statsoutput);

		$hostname = $queue = "";
		$hostnames = $queues = array();

		foreach ($statsoutput as $line) {
			$line = trim($line);
			//echo $line;
			if (!strstr($line,'------')) {
				if (trim($line == "")) {
					break;
				}
				if (strstr($line, 'queuename')) {
					continue;
				}

				//echo "$line\n";
				if (strstr($line, '@')) {
					list($queuehost, $unk, $usage, $cpu, $arch, $states) = preg_split('/\s+/', $line);
					list($queue, $hostname) = explode('@',$queuehost);
					//echo "[$usage]\n";
					list($slotsres,$slotsused,$slotsavailable) = explode('/',$usage);
					//echo "Queue: [$queue], Host: [$hostname], [$slotsused] of [$slotsavailable], CPU: [$cpu]\n";
					$report[$hostname]['queues'][$queue] = null;
					$report[$hostname]['cpu'] = $cpu;
					$report[$hostname]['arch'] = $arch;
					$report[$hostname]['queues'][$queue]['slotsused'] = $slotsused;
					$report[$hostname]['queues'][$queue]['slotsavailable'] = $slotsavailable;
					$report[$hostname]['states'] = $states;
					
					if ( (!isset($hostnames)) || (!in_array($hostname, $hostnames)) ) {
						$hostnames[] = $hostname;
					}
					if ( (!isset($queues)) || (!in_array($queue, $queues)) ) {
						$queues[] = $queue;
					}
				}
				else {
					//echo "$line\n";
					$report[$hostname]['queues'][$queue]['jobs'][] = $line;
					$report[$hostname]['queues'][$queue]['slotsused'] = $slotsused;
					$report[$hostname]['queues'][$queue]['slotsavailable'] = $slotsavailable;
				}
			}
		}
		sort($hostnames);
		sort($queues);
		
		//PrintVariable($hostnames);
		//PrintVariable($queues);
		//PrintVariable($report);
		
		return array($statsoutput,$report,$queues,$hostnames);
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayQstatJobs ------------------- */
	/* -------------------------------------------- */
	function DisplayQstatJobs() {
		DisplayStatsMenu();

		$command = "ssh ".$GLOBALS['cfg']['clustersubmithost']." qstat 2>&1";
		$statsoutput = explode("\n",shell_exec($command));
		
		?>
		<div class="ui container">
			<div class="ui styled segment" style="font-family: monospace; white-space: pre;"><?
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
		DisplayStatsMenu();

		$command = "ssh ".$GLOBALS['cfg']['clustersubmithost']." qstat -f -u '*' 2>&1";
		$statsoutput = explode("\n",shell_exec($command));
		
		?>
		<div class="ui container">
			<div class="ui styled segment" style="font-family: monospace; white-space: pre;"><?
			foreach ($statsoutput as $line) {
				if (!strstr($line,'------')) {
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
	
		list($statsoutput,$report,$queues,$hostnames) = GetClusterStats();
		
		DisplayStatsMenu();

		$slotsusedcolor = "e89b9f";
		$slotsunusedcolor = "EEEEEE";
		
		?>
		<div class="ui container">
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
		</div>
	<?
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayQueues ---------------------- */
	/* -------------------------------------------- */
	function DisplayQueues() {
	
		//$urllist['Cluster Stats'] = "cluster.php";
		//NavigationBar("Cluster Stats", $urllist);

		list($statsoutput,$report,$queues,$hostnames) = GetClusterStats();
		
		DisplayStatsMenu();

		$slotsusedcolor = "FF4500";
		$slotsunusedcolor = "EEEEEE";

		?>

		<div class="ui container">
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
											//print_r($report[$hostname]['queues'][$queue]['jobs']);
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
					<td valign="top">
					</td>
				</tr>
			</table>
		</div>
	<?
	}
	
?>

<br><br><br><br>

<? include("footer.php") ?>
