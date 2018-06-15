<? session_start(); ?>
<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Cluster</title>
	</head>

<body>
	<div id="wrapper">
<?
	//require "config.php";
	require "functions.php";
	require "includes.php";
	require "menu.php";
	require 'kashi.php';
	
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
		<div align="center">
			<a href="cluster.php?action=qstatjobs"><tt>qstat</tt> job output</a> &nbsp; &nbsp; 
			<a href="cluster.php?action=qstatusage"><tt>qstat</tt> usage output</a> &nbsp; &nbsp; 
			<a href="cluster.php?action=nodes">Nodes</a> &nbsp; &nbsp; 
			<a href="cluster.php?action=queues">Queues</a> &nbsp; &nbsp; 
		</div>
		<br><br>
		<?
	}	

	
	/* -------------------------------------------- */
	/* ------- GetClusterStats -------------------- */
	/* -------------------------------------------- */
	function GetClusterStats() {
		//$statsoutput = explode("\n",shell_exec("SGE_ROOT=/sge/sge-root; export SGE_ROOT; SGE_CELL=nrccell; export SGE_CELL; /sge/sge-root/bin/lx24-amd64/./qstat -f -u '*'"));
		$command = "ssh ".$GLOBALS['cfg']['clustersubmithost']." qstat -f -u '*'";
		$statsoutput = explode("\n",shell_exec($command));
		
		//PrintVariable($statsoutput);

		$hostname = $queue = "";
		$hostnames = $queues = null;

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
	
		$urllist['Cluster Stats'] = "cluster.php";
		NavigationBar("Cluster Stats", $urllist);
		
		DisplayStatsMenu();

		$command = "ssh ".$GLOBALS['cfg']['clustersubmithost']." qstat";
		$statsoutput = explode("\n",shell_exec($command));
		
		?>
		<div class="dropshadow" style="padding:8px; border: 1px solid #777; width: 50%; font-family: monospace; white-space: pre;"><?
			foreach ($statsoutput as $line) {
				$line = trim($line);
echo "$line\n";
			}
		?>
		</div>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayQstatUsage ------------------ */
	/* -------------------------------------------- */
	function DisplayQstatUsage() {
	
		$urllist['Cluster Stats'] = "cluster.php";
		NavigationBar("Cluster Stats", $urllist);
		
		DisplayStatsMenu();

		$command = "ssh ".$GLOBALS['cfg']['clustersubmithost']." qstat -f -u '*'";
		$statsoutput = explode("\n",shell_exec($command));
		
		?>
		<div class="dropshadow" style="padding:8px; border: 1px solid #777; width: 50%; font-family: monospace; white-space: pre;"><?
			foreach ($statsoutput as $line) {
				if (!strstr($line,'------')) {
					$line = trim($line);
echo "$line\n";
				}
			}
		?>
		</div>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayNodes ----------------------- */
	/* -------------------------------------------- */
	function DisplayNodes() {
	
		$urllist['Cluster Stats'] = "cluster.php";
		NavigationBar("Cluster Stats", $urllist);

		list($statsoutput,$report,$queues,$hostnames) = GetClusterStats();
		
		DisplayStatsMenu();

		$slotsusedcolor = "e89b9f";
		$slotsunusedcolor = "EEEEEE";
		
		?>

		<table class="graydisplaytable dropshadow">
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
					//PrintVariable($report[$hostname]);

					$slotsavailable = 0;
					$slotsused = 0;
					foreach ($report[$hostname]['queues'] as $queue => $info) {
						//PrintVariable($info);
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
			<tr>
				<td style="font-weight: bold; font-size:14pt">Totals</td>
				<td></td>
				<td></td>
				<td></td>
				<td style="font-weight: bold; font-size:14pt"><?=$totalClusterSlotsAvailable?></td>
				<td><img src="horizontalchart.php?b=yes&w=200&h=10&v=<?=$totalClusterSlotsUsed?>,<?=($totalClusterSlotsAvailable-$totalClusterSlotsUsed)?>&c=darkred,<?=$slotsunusedcolor?>"> &nbsp; <?=$totalClusterSlotsUsed?> of <?=$totalClusterSlotsAvailable?></td>
			</tr>
		</table>
		<br><br><br><br>
	<?
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayQueues ---------------------- */
	/* -------------------------------------------- */
	function DisplayQueues() {
	
		$urllist['Cluster Stats'] = "cluster.php";
		NavigationBar("Cluster Stats", $urllist);

		list($statsoutput,$report,$queues,$hostnames) = GetClusterStats();
		
		DisplayStatsMenu();

		$slotsusedcolor = "FF4500";
		$slotsunusedcolor = "EEEEEE";

		?>

		<table>
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
	<?
	}
	
?>

<br><br><br><br>

<? include("footer.php") ?>
