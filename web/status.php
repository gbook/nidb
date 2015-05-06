<?
 // ------------------------------------------------------------------------------
 // NiDB status.php
 // Copyright (C) 2004 - 2015
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
	require_once "Mail.php";
	require_once "Mail/mime.php";

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
	require "includes.php";
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
	
		$urllist['System Status'] = "status.php";
		NavigationBar("System", $urllist);

		# connect to DB and get status
		$dbconnect = true;
		$devdbconnect = true;
		$L = mysqli_connect($GLOBALS['cfg']['mysqlhost'],$GLOBALS['cfg']['mysqluser'],$GLOBALS['cfg']['mysqlpassword'],$GLOBALS['cfg']['mysqldatabase']) or $dbconnect = false;
		$dbStatus = explode("  ", mysql_stat());
		
		# get number of fileio operations pending
		$sqlstring = "select count(*) 'numiopending' from fileio_requests where request_status in ('pending','')";
		$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$numiopending = $row['numiopending'];
		
		# get number of directories in dicomincoming directory
		$dirs = glob($GLOBALS['cfg']['incomingdir'].'/*', GLOB_ONLYDIR);
		$numdicomdirs = count($dirs);
		
		# get number of files in dicomincoming directory
		$files = glob($GLOBALS['cfg']['incomingdir'].'/*');
		$numdicomfiles = count($files);
		
		# get number of import requests
		$sqlstring = "select count(*) 'numimportpending' from import_requests where import_status in ('pending','')";
		$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$numimportpending = $row['numimportpending'];
		
		# get number of directories in dicomincoming directory
		$dirs = glob($GLOBALS['cfg']['uploadedpath'].'/*', GLOB_ONLYDIR);
		$numimportdirs = count($dirs);
		
		?>
		<table class="entrytable">
			<tr>
				<td class="label">Uptime</td>
				<td><pre><?=trim(`uptime`)?></pre></td>
			</tr>
			<tr>
				<td class="label">Memory (GB)</td>
				<td><pre><?=trim(`free -g`)?></pre></td>
			</tr>
			<tr>
				<td class="label">Disk usage</td>
				<td><pre><?=system('df -lh')?></pre></td>
			</tr>
			<tr>
				<td class="label">Database</td>
				<td><pre><?
				foreach ($dbStatus as $value){
echo $value . "\n";
				}
				?></pre></td>
			</tr>
			<tr>
				<td class="label">Parse DICOM module</td>
				<td>
					<?=$numdicomfiles?> queued files<br>
					<?=$numdicomdirs?> queued directories<br>
				</td>
			</tr>
			<tr>
				<td class="label">Import module</td>
				<td>
					<?=$numimportpending?> requests pending<br>
					<?=$numimportdirs?> queued directories<br>
				</td>
			</tr>
			<tr>
				<td class="label">File IO module</td>
				<td><?=$numiopending?> operations pending</td>
			</tr>
			<tr>
				<td class="label">Pipeline module</td>
				<td>
					<table class="smallgraydisplaytable">
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
						$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
						while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
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
		<?
	}
?>


<? include("footer.php") ?>
