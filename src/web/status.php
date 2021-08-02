<?
 // ------------------------------------------------------------------------------
 // NiDB status.php
 // Copyright (C) 2004 - 2021
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
		<title>NiDB - Status</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";
?>

<?
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	
	/* determine action */
	if ($action == "") {
		DisplayStatus();
	}
	else {
		DisplayStatus();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- DisplayStatus ---------------------- */
	/* -------------------------------------------- */
	function DisplayStatus() {

		# connect to DB and get status
		$dbconnect = true;
		$devdbconnect = true;
		$L = mysqli_connect($GLOBALS['cfg']['mysqlhost'],$GLOBALS['cfg']['mysqluser'],$GLOBALS['cfg']['mysqlpassword'],$GLOBALS['cfg']['mysqldatabase']) or $dbconnect = false;
		$dbStatus = explode("  ", mysqli_stat());
		
		# get number of fileio operations pending
		$sqlstring = "select count(*) 'numiopending' from fileio_requests where request_status in ('pending','')";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$numiopending = $row['numiopending'];
		
		# get number of directories in dicomincoming directory
		$dirs = glob($GLOBALS['cfg']['incomingdir'].'/*', GLOB_ONLYDIR);
		$numdicomdirs = count($dirs);
		
		# get number of files in dicomincoming directory
		$files = glob($GLOBALS['cfg']['incomingdir'].'/*');
		$numdicomfiles = count($files);
		
		# get number of import requests
		$sqlstring = "select count(*) 'numimportpending' from import_requests where import_status in ('pending','')";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$numimportpending = $row['numimportpending'];
		
		# get number of directories in dicomincoming directory
		$dirs = glob($GLOBALS['cfg']['uploadeddir'].'/*', GLOB_ONLYDIR);
		$numimportdirs = count($dirs);
		
		?>
		<div align="ui container">
			<table class="ui celled table">
				<tr>
					<td><h3 class="header">NiDB version</h3></td>
					<td><?=GetNiDBVersion();?></td>
				</tr>
				<tr>
					<td><h3 class="header">NiDB checks</h3></td>
					<td><?
						if (!RunSystemChecks())
							echo "No issues";
						?>
					</td>
				</tr>
				<tr>
					<td><h3 class="header">Uptime</h3></td>
					<td><pre><?=trim(`uptime`)?></pre></td>
				</tr>
				<tr>
					<td><h3 class="header">Memory</h3></td>
					<td><pre><?=trim(`free -h`)?></pre></td>
				</tr>
				<tr>
					<td><h3 class="header">CPU cores</h3></td>
					<td><pre><?=trim(`nproc`)?></pre></td>
				</tr>
				<tr>
					<td><h3 class="header">Disk usage</h3></td>
					<td><pre><?=trim(`df -Th`)?></pre></td>
				</tr>
				<tr>
					<td><h3 class="header">Crontab for <tt><?=system('whoami')?></tt></h3></td>
					<td>
						<?
							$crontab = trim(`crontab -l`);
							if (!contains($crontab, "nidb backup")) Error("<tt>backup</tt> module is not listed in crontab", false);
							if (!contains($crontab, "nidb export")) Error("export module is not listed in crontab", false);
							if (!contains($crontab, "nidb fileio")) Error("fileio module is not listed in crontab", false);
							if (!contains($crontab, "nidb import")) Error("import module is not listed in crontab", false);
							if (!contains($crontab, "nidb importuploaded")) Error("importuploaded module is not listed in crontab", false);
							if (!contains($crontab, "nidb minipipeline")) Error("minipipeline module is not listed in crontab", false);
							if (!contains($crontab, "nidb modulemanager")) Error("modulemanager module is not listed in crontab", false);
							if (!contains($crontab, "nidb mriqa")) Error("mriqa module is not listed in crontab", false);
							if (!contains($crontab, "nidb pipeline")) Error("pipeline module is not listed in crontab", false);
							if (!contains($crontab, "nidb qc")) Error("qc module is not listed in crontab", false);
							if (!contains($crontab, "nidb upload")) Error("upload module is not listed in crontab", false);
						?>
						<pre><?=$crontab?></pre>
					</td>
				</tr>
				<tr>
					<td><h3 class="header">DICOM receiver</h3></td>
					<td>
						<?
							$dcmrcv = trim(`ps -ef | grep '/nidb/bin/dcm4che'`);
							$lines = explode("\n", $dcmrcv);
							foreach ($lines as $line) {
								if (contains($line, "java -cp")) {
									$dcmrcvline = $line;
									break;
								}
							}
							$parts = preg_split('/\s+/', $dcmrcvline);

							$aeport = $parts[count($parts)-3];
							$dest = $parts[count($parts)-1];
						?>
						<table class="ui basic table">
							<tr>
								<td><b>Application Entity (AE):port</b></td>
								<td><?=$aeport?></td>
							</tr>
							<tr>
								<td><b>Destination Directory</b></td>
								<td><?=$dest?>
								<?
									if (!is_dir($dest))
										Error("dcmrcv points to direcory [$dest] which does not exist", false);
									if ($dest != $GLOBALS['cfg']['incomingdir'])
										Error("dcmrcv is NOT writing images to the incoming directory. Images will be not be archived. dcmrcv destination path [$dest] must match the config variable 'incomingdir' which is currently [" . $GLOBALS['cfg']['incomingdir'] . "]", false);
								?>
								</td>
							</tr>
							<tr>
								<td><tt>ps -ef</tt> output</td>
								<td>
									<?=$dcmrcvline?>
									<?
										if ($dcmrcvline == "")
											Error("dcmrcv is not running. Images will be archived");
									?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td><h3 class="header">SQL table sizes</h3></td>
					<td><pre><?
					/* get information about the modality table */
					$largetables = array("analysis_results", "analysis_history", "analysis", "qc_results", "importlogs");
					foreach ($largetables as $table) {
						$sqlstringA = "show table status like '$table'";
						$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
						$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
						$numrows = $rowA['Rows'];
						$tablesize = $rowA['Data_length'];
						$indexsize = $rowA['Index_length'];
						?><b><?=$table?></b>&#9;<?=number_format(($tablesize + $indexsize),0)?> bytes   <span class="tiny">(<?=number_format($numrows,0)?> rows)</span><br><?
					}
					?></pre>
					</td>
				</tr>
				<tr>
					<td><h3 class="header">Software versions</h3></td>
					<td>
						<b>OS</b> <tt><?=php_uname()?></tt><br>
						<b>PHP</b> <tt><?=phpversion()?></tt><br>
						<b>MySQL</b> <tt><?=trim(`mysql -V`)?></tt><br>
						<b>ImageMagick</b> <tt><?=trim(`convert --version`)?></tt><br>
					</td>
				</tr>
				<tr>
					<td><h3 class="header">Database</h3></td>
					<td><pre><?
					foreach ($dbStatus as $value){
	echo $value . "\n";
					}
					?></pre></td>
				</tr>
				<tr>
					<td><h3 class="header">All NiDB modules</h3></td>
					<td>
					
						<table class="ui very small very compact celled selectable grey table">
							<thead>
								<tr>
									<th>Name</th>
									<th>&nbsp;</th>
									<th>Status</th>
									<th>Instances</th>
									<th>Last finish</th>
									<th>Run time</th>
									<th>Enabled</th>
								</tr>
							</thead>
							<tbody>
								<?
									$sqlstring = "select * from modules order by module_name";
									$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
									while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
										$id = $row['module_id'];
										$module_name = $row['module_name'];
										$module_status = $row['module_status'];
										$module_numrunning = $row['module_numrunning'];
										$module_laststart = $row['module_laststart'];
										$module_laststop = $row['module_laststop'];
										$module_isactive = $row['module_isactive'];
										
										/* calculate the status color */
										if (!$module_isactive) { $color = "gray"; }
										else {
											if ($module_status == "running") { $color = "green"; }
											if ($module_status == "stopped") { $color = "darkblue"; }
										}
										
										/* calculate and format the run time */
										if ($module_status == "stopped") {
											$runtime = (strtotime($module_laststop) - strtotime($module_laststart));
											
											if ($runtime > 3600) {
												$runtime = number_format($runtime/3600,2) . " hr";
											}
											elseif ($runtime > 60) {
												$runtime = number_format($runtime/60,2) . " min";
											}
											else {
												$runtime = $runtime . " sec";
											}
										}
										else {
											$runtime = "-";
										}
										
										$module_laststop = date("D M j, Y H:i:s",strtotime($module_laststop));
								?>
								<tr>
									<td><b><?=$module_name?></b></td>
									<td><a href="adminmodules.php?action=viewlogs&modulename=<?=$module_name?>">view logs</a></td>
									<td style="color: <?=$color?>"><?=$module_status?></td>
									<td><?=$module_numrunning?></td>
									<td><?=$module_laststop?></td>
									<td><?=$runtime?></td>
									<td>
										<?
											if ($module_isactive) {
												?><img src="images/checkedbox16.png"><?
											}
											else {
												?><img src="images/uncheckedbox16.png"><?
											}
										?>
									</td>
								</tr>
								<? 
									}
								?>
							</tbody>
						</table>
					
					
					</td>
				</tr>
				<tr>
					<td><h3 class="header"><a href="adminmodules.php?action=viewlogs&modulename=import" title="View import logs">Import module</a><br><span class="tiny"><?=$GLOBALS['cfg']['incomingdir']?></span></h3></td>
					<td>
						<?=$numdicomfiles?> queued files<br>
						<?=$numdicomdirs?> queued directories<br>
					</td>
				</tr>
				<tr>
					<td><h3 class="header"><a href="adminmodules.php?action=viewlogs&modulename=importuploaded" title="View importuploaded.pl logs">Import Uploads module</a><br><span class="tiny"><?=$GLOBALS['cfg']['uploadeddir']?></span></h3></td>
					<td>
						<?=$numimportpending?> requests pending<br>
						<?=$numimportdirs?> queued directories<br>
					</td>
				</tr>
				<tr>
					<td><h3 class="header"><a href="adminmodules.php?action=viewlogs&modulename=fileio" title="View fileio.pl logs">File IO module</a></h3></td>
					<td><?=$numiopending?> operations pending</td>
				</tr>
				<tr>
					<td><h3 class="header"><a href="adminmodules.php?action=viewlogs&modulename=pipeline" title="View pipeline.pl logs">Pipeline module</a></h3></td>
					<td>
						<table class="ui very small very compact celled selectable grey table">
						<thead>
							<tr>
								<th>Process ID</th>
								<th>Status</th>
								<th>Startdate</th>
								<th>Last checkin</th>
								<th>Current pipeline</th>
								<th>Current study</th>
							</tr>
						</thead>
						<tbody>
						<?
							$sqlstring = "select a.*, b.pipeline_name from pipeline_procs a left join pipelines b on a.pp_currentpipeline = b.pipeline_id order by a.pp_lastcheckin";
							$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$pp_processid = $row['pp_processid'];
								$pp_status = $row['pp_status'];
								$pp_startdate = $row['pp_startdate'];
								$pp_lastcheckin = $row['pp_lastcheckin'];
								$pp_currentpipeline = $row['pp_currentpipeline'];
								$pipelinename = $row['pipeline_name'];
								$pp_currentsubject = $row['pp_currentsubject'];
								$pp_currentstudy = $row['pp_currentstudy'];
								?>
								<tr>
									<td><?=$pp_processid?></td>
									<td><?=$pp_status?></td>
									<td><?=$pp_startdate?></td>
									<td><?=$pp_lastcheckin?></td>
									<td><?=$pipelinename?></td>
									<td><?=$pp_currentstudy?></td>
								</tr>
								<?
							}
						?>
						</tbody>
						</table>
					</td>
				</tr>
				<tr>
					<td><h3 class="header">phpinfo()</h3></td>
					<td><? //phpinfo(); ?></td>
				</tr>
			</table>
		</div>
		<?
	}
?>


<? include("footer.php") ?>
