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
		case 'list': DisplayList(); break;
		case 'summary': DisplaySummary(); break;
		case 'graph': DisplayGraph(); break;
		default:
			DisplayList();
	}


	/* -------------------------------------------- */
	/* ------- DisplayStatsMenu ------------------- */
	/* -------------------------------------------- */
	function DisplayStatsMenu() {
		?>
		<div align="center">
			<a href="cluster.php?action=list">List</a> &nbsp; 
			<a href="cluster.php?action=summary">Summary</a> &nbsp; 
			<a href="cluster.php?action=graph">Graph</a> &nbsp; 
		</div>
		<br><Br>
		<script>
			$(function() {
				$( document ).tooltip({show:{effect:'appear'}, hide:{duration:0}});
			});
		</script>
		<style>
			.ui-tooltip {
				padding: 7px 7px;
				border-radius: 5px;
				font-size: 10px;
				font-family: monospace;
				/*white-space: pre;*/
				border: 1px solid black;
			}
		</style>

		<?
	}	

	
	/* -------------------------------------------- */
	/* ------- GetClusterStats -------------------- */
	/* -------------------------------------------- */
	function GetClusterStats() {
		//$statsoutput = explode("\n",shell_exec("SGE_ROOT=/sge/sge-root; export SGE_ROOT; SGE_CELL=nrccell; export SGE_CELL; /sge/sge-root/bin/lx24-amd64/./qstat -f -u '*'"));
		$statsoutput = explode("\n",shell_exec("ssh ".$GLOBALS['cfg']['clustersubmithost']." qstat -f -u '*'"));
		
		//print_r($statsoutput);

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
					list($queuehost, $unk, $usage, $cpu, $arch) = preg_split('/\s+/', $line);
					list($queue, $hostname) = explode('@',$queuehost);
					//echo "[$usage]\n";
					list($slotsres,$slotsused,$slotsavailable) = explode('/',$usage);
					//echo "Queue: [$queue], Host: [$hostname], [$slotsused] of [$slotsavailable], CPU: [$cpu]\n";
					$report[$hostname]['queues'][$queue] = null;
					$report[$hostname]['cpu'] = $cpu;
					$report[$hostname]['arch'] = $arch;
					$report[$hostname]['queues'][$queue]['slotsused'] = $slotsused;
					$report[$hostname]['queues'][$queue]['slotsavailable'] = $slotsavailable;
					
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
		//print_r($hostnames);
		//print_r($queues);
		//print_r($report);
		
		sort($hostnames);
		sort($queues);
		
		return array($statsoutput,$report,$queues,$hostnames);
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayList ------------------------ */
	/* -------------------------------------------- */
	function DisplayList() {
	
		$urllist['Cluster Stats'] = "cluster.php";
		NavigationBar("Cluster Stats", $urllist);

		list($statsoutput,$report,$queues,$hostnames) = GetClusterStats();
		
		DisplayStatsMenu();
		
		?>
		<pre>
		<?
			foreach ($statsoutput as $line) {
				$line = trim($line);
				echo "$line\n";
			}
		?>
		</pre>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- DisplaySummary --------------------- */
	/* -------------------------------------------- */
	function DisplaySummary() {
	
		$urllist['Cluster Stats'] = "cluster.php";
		NavigationBar("Cluster Stats", $urllist);

		list($statsoutput,$report,$queues,$hostnames) = GetClusterStats();
		
		DisplayStatsMenu();

		$slotsusedcolor = "FF4500";
		$slotsunusedcolor = "EEEEEE";
		
		?>

		<table cellpadding="2" style="border: 1px solid #ccc; border-collapse: collapse;">
			<tr>
				<td style="border: 1px solid #ccc"><b>Hostname</b> (load)</td>
			<?
				foreach ($queues as $queue) {
				?>
					<td style="border: 1px solid #ccc"><b><?=$queue?></b></td>
				<?
				}
			?>
				<td align="center"><b>% slots used</b></td>
			</tr>
			<?
				foreach ($hostnames as $hostname) {
					?>
					<tr>
						<td style="border: 1px solid #ccc"><b><?=$hostname?></b> (<?=$report[$hostname]['cpu']?>)</td>
						<?
							foreach ($queues as $queue) {
								$hosts[$hostname]['slotsused'] += $report[$hostname]['queues'][$queue]['slotsused'];
								$hosts[$hostname]['slotsavailable'] += $report[$hostname]['queues'][$queue]['slotsavailable'];
								if ((isset($report[$hostname]['queues'][$queue])) && ($report[$hostname]['queues'][$queue]['slotsused'] > 0)) {
									$joblist = implode('<br>',$report[$hostname]['queues'][$queue]['jobs']);
									//$joblist = str_replace(' ','&nbsp;',$joblist);
								?>
									<td style="border: 1px solid #ccc" title="<?=$joblist?>"><?=$report[$hostname]['queues'][$queue]['slotsused']?>/<?=$report[$hostname]['queues'][$queue]['slotsavailable']?></td>
								<?
								}
								else {
									?><td style="border: 1px solid #ccc">&nbsp;</td> <?
								}
							}
							$slotsused = $hosts[$hostname]['slotsused'];
							$slotsavailable = $hosts[$hostname]['slotsavailable'];
						?>
						<td style="border: 1px solid #ccc"><img src="horizontalchart.php?b=yes&w=300&h=15&v=<?=$slotsused?>,<?=($slotsavailable-$slotsused)?>&c=<?=$slotsusedcolor?>,<?=$slotsunusedcolor?>"> &nbsp; <span class="tiny"><?=$hosts[$hostname]['slotsused']?> of <?=$slotsavailable?></span></td>
					</tr>
					<?
				}
			?>
		</table>
	<?
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayGraph ----------------------- */
	/* -------------------------------------------- */
	function DisplayGraph() {
	
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