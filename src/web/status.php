<?
 // ------------------------------------------------------------------------------
 // NiDB status.php
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
		<title>NiDB - Status</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	/* check if they have permissions to this view page */
	if (!isSiteAdmin()) {
		Warning("You do not have permissions to view this page");
		exit(0);
	}

	/* ----- setup variables ----- */
	$action = GetVariable("action");

	/* optionally clean up stale pipeline process rows before rendering */
	$pipelineMsg = "";
	if ($action == "deleteoldpipelineprocs") {
		$n = DeleteOldPipelineProcs();
		$pipelineMsg = "Deleted $n stale pipeline process row(s) (last check-in over 30 days ago).";
	}

	/* this page has a single status view */
	DisplayStatus($pipelineMsg);
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- DisplayStatus ---------------------- */
	/* -------------------------------------------- */
	function DisplayStatus($pipelineMsg = "") {

		# get DB status from the existing connection
		$dbStatus = explode("  ", mysqli_stat($GLOBALS['linki']));
		
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
					<td>
						<div style="display:flex;align-items:flex-start;gap:24px">
						<?php
							$freeLines = explode("\n", trim(`free -b`));
							if (isset($freeLines[1])) {
								$parts    = preg_split('/\s+/', trim($freeLines[1]));
								$memTotal = (float)($parts[1] ?? 0);
								$memUsed  = (float)($parts[2] ?? 0);
								$memAvail = (float)(isset($parts[6]) ? $parts[6] : ($parts[3] ?? 0));
								$pctFree  = $memTotal > 0 ? round(($memAvail / $memTotal) * 100) : 0;
								$pctUsed  = 100 - $pctFree;
								$barColor = $pctFree < 10 ? '#db2828' : ($pctFree < 25 ? '#f2711c' : ($pctFree < 50 ? '#fbbd08' : '#21ba45'));
								function fmtBytes($b) {
									if ($b >= 1073741824) return round($b/1073741824, 1) . ' GB';
									if ($b >= 1048576)    return round($b/1048576, 1)    . ' MB';
									return round($b/1024, 1) . ' KB';
								}
						?>
						<div style="width:220px;flex-shrink:0;padding-top:4px">
							<div style="background:#e0e0e0;border-radius:4px;height:22px;overflow:hidden;margin-bottom:5px">
								<div style="width:<?= $pctFree ?>%;height:100%;background:<?= $barColor ?>;display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.8em;font-weight:bold;white-space:nowrap">
									<?= $pctFree ?>% free
								</div>
							</div>
							<div style="font-size:0.85em;color:#555"><?= fmtBytes($memAvail) ?> free of <?= fmtBytes($memTotal) ?> total</div>
						</div>
						<?php } ?>
						<pre style="margin:0"><?= trim(`free -h`) ?></pre>
						</div>
					</td>
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
					<td><h3 class="header">Directories</h3><span class="tiny">All <tt>cfg['*dir']</tt> paths</span></td>
					<td>
						<table class="ui very small very compact celled table">
							<thead>
								<tr>
									<th>Config</th>
									<th>Path</th>
									<th>Exists</th>
									<th>Owner:Group</th>
									<th>Perms</th>
									<th>Writable by <tt>nidb</tt></th>
								</tr>
							</thead>
							<tbody>
							<?
								$nidbuser = "nidb";

								/* collect every configured directory (cfg keys ending in 'dir') */
								$dirkeys = array();
								foreach ($GLOBALS['cfg'] as $ckey => $cval) {
									if (preg_match('/dir$/i', $ckey) && is_string($cval) && (trim($cval) != ""))
										$dirkeys[$ckey] = $cval;
								}
								ksort($dirkeys);

								foreach ($dirkeys as $ckey => $path) {
									$exists = is_dir($path);
									$owner = $group = $perms = "";
									if ($exists) {
										$ownerInfo = function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($path)) : false;
										$groupInfo = function_exists('posix_getgrgid') ? posix_getgrgid(filegroup($path)) : false;
										$owner = $ownerInfo ? $ownerInfo['name'] : fileowner($path);
										$group = $groupInfo ? $groupInfo['name'] : filegroup($path);
										$perms = substr(sprintf('%o', fileperms($path)), -4);
									}
									$writable = $exists ? DirWritableByUser($path, $nidbuser) : false;
									?>
									<tr>
										<td><tt><?=htmlspecialchars($ckey)?></tt></td>
										<td><tt><?=htmlspecialchars($path)?></tt></td>
										<td class="center aligned"><?= $exists ? '<i class="green check icon"></i>' : '<i class="red times circle icon"></i> <span style="color:#900">missing</span>' ?></td>
										<td><?= $exists ? htmlspecialchars("$owner:$group") : '' ?></td>
										<td><tt><?= $exists ? $perms : '' ?></tt></td>
										<td class="center aligned">
										<? if ($exists): ?>
											<?= $writable ? '<i class="green check icon"></i>' : '<i class="red exclamation triangle icon"></i> <span style="color:#900">not writable</span>' ?>
										<? endif; ?>
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
					<td><h3 class="header">Crontab for <tt><?=trim(`whoami`)?></tt></h3></td>
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
							$dcmrcvline = "";
							foreach ($lines as $line) {
								if (contains($line, "java -cp")) {
									$dcmrcvline = $line;
									break;
								}
							}
							$aeport = $dest = "";
							if ($dcmrcvline != "") {
								/* parse by markers, not by offset from the end - the ps line can include trailing
								   shell tokens (e.g. "> /dev/null 2>&1 &") that shift the final fields */
								$parts = preg_split('/\s+/', $dcmrcvline);
								for ($i = 0; $i < count($parts); $i++) {
									/* AE:port is the argument right after the DcmRcv class */
									if (contains($parts[$i], "DcmRcv") && isset($parts[$i+1]))
										$aeport = $parts[$i+1];
									/* destination directory is the argument after the -dest flag */
									if (($parts[$i] === "-dest") && isset($parts[$i+1]))
										$dest = $parts[$i+1];
								}
							}
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
									/* only validate the destination when dcmrcv is actually running */
									if ($dcmrcvline != "") {
										if (!is_dir($dest))
											Error("dcmrcv points to direcory [$dest] which does not exist", false);
										if ($dest != $GLOBALS['cfg']['incomingdir'])
											Error("dcmrcv is NOT writing images to the incoming directory. Images will be not be archived. dcmrcv destination path [$dest] must match the config variable 'incomingdir' which is currently [" . $GLOBALS['cfg']['incomingdir'] . "]", false);
									}
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
					<td>
						<table class="ui very small very compact celled table">
							<thead>
								<tr>
									<th>Table</th>
									<th class="right aligned">Rows</th>
									<th class="right aligned">Data (bytes)</th>
									<th class="right aligned">Index (bytes)</th>
									<th class="right aligned">Total (bytes)</th>
								</tr>
							</thead>
							<tbody>
							<?
								$largetables = array("analysis_results", "analysis_history", "analysis", "qc_results", "importlogs");
								/* SHOW TABLE STATUS can't run through the prepared-statement protocol on all MySQL/MariaDB
								   versions, so read the same figures from information_schema (which is preparable) */
								$stmtA = mysqli_prepare($GLOBALS['linki'], "select table_rows as numrows, data_length as datalen, index_length as idxlen from information_schema.tables where table_schema = database() and table_name = ?");
								foreach ($largetables as $table) {
									mysqli_stmt_bind_param($stmtA, 's', $table);
									$resultA = MySQLiBoundQuery($stmtA, __FILE__, __LINE__);
									$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
									/* no row if the table is absent - skip it rather than warn */
									if (!$rowA)
										continue;
									$numrows = $rowA['numrows'];
									$tablesize = $rowA['datalen'];
									$indexsize = $rowA['idxlen'];
									?>
									<tr>
										<td><b><?=$table?></b></td>
										<td class="right aligned"><?=number_format($numrows)?></td>
										<td class="right aligned"><?=number_format($tablesize)?></td>
										<td class="right aligned"><?=number_format($indexsize)?></td>
										<td class="right aligned"><?=number_format($tablesize + $indexsize)?></td>
									</tr>
									<?
								}
								mysqli_stmt_close($stmtA);
							?>
							</tbody>
						</table>
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
										$color = "";
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
										
										$module_laststop = $module_laststop ? date("D M j, Y H:i:s", strtotime($module_laststop)) : "";
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
						<? if ($pipelineMsg != "") { ?>
							<div class="ui small positive message"><?=htmlspecialchars($pipelineMsg)?></div>
						<? } ?>
						<form method="post" action="status.php" onsubmit="return confirm('Delete all pipeline process rows whose last check-in was more than 30 days ago?');" style="margin-bottom: 10px">
							<input type="hidden" name="action" value="deleteoldpipelineprocs">
							<button class="ui small red button" type="submit"><i class="trash icon"></i> Delete processes older than 30 days</button>
						</form>
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
			</table>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DeleteOldPipelineProcs ------------- */
	/* -------------------------------------------- */
	/* Delete stale pipeline_procs rows - those whose last check-in was more than 30 days ago.
	   Returns the number of rows removed. */
	function DeleteOldPipelineProcs() {
		$stmt = mysqli_prepare($GLOBALS['linki'], "delete from pipeline_procs where pp_lastcheckin < (now() - interval 30 day)");
		MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$deleted = mysqli_stmt_affected_rows($stmt);
		mysqli_stmt_close($stmt);
		return $deleted;
	}


	/* -------------------------------------------- */
	/* ------- DirWritableByUser ------------------ */
	/* -------------------------------------------- */
	/* Determine whether a specific (named) user can write to a directory, from ownership and permission
	   bits - independent of which user the web server itself runs as. */
	function DirWritableByUser($path, $username) {
		if (!file_exists($path))
			return false;

		$perms = fileperms($path);

		/* world-writable */
		if ($perms & 0002)
			return true;

		/* without posix we can only best-effort check as the current process user */
		if (!function_exists('posix_getpwnam') || !function_exists('posix_getgrgid'))
			return is_writable($path);

		$user = posix_getpwnam($username);
		if (!$user)
			return false;

		/* owner-writable and owned by the user */
		if (($perms & 0200) && (fileowner($path) == $user['uid']))
			return true;

		/* group-writable and the user belongs to the directory's group (primary or supplementary) */
		if ($perms & 0020) {
			$gid = filegroup($path);
			if ($gid == $user['gid'])
				return true;
			$grp = posix_getgrgid($gid);
			if ($grp && in_array($username, $grp['members']))
				return true;
		}

		return false;
	}
?>


<? include("footer.php") ?>
