<?
 // ------------------------------------------------------------------------------
 // NiDB adminmodules.php
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
		<title>NiDB - Manage Modules</title>
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
	$id = GetVariable("id");
	$modulename = GetVariable("modulename");
	
	/* determine action */
	switch ($action) {
		case 'disable':
			DisableModule($id);
			DisplayModuleList();
			break;
		case 'enable':
			EnableModule($id);
			DisplayModuleList();
			break;
		case 'debug':
			DebugModule($id);
			DisplayModuleList();
			break;
		case 'nodebug':
			NoDebugModule($id);
			DisplayModuleList();
			break;
		case 'reset':
			ResetModule($id);
			DisplayModuleList();
			break;
		case 'viewlogs':
			ViewLogs($modulename);
			break;
		default:
			DisplayModuleList();
	}
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- Updatemodule ---------------------- */
	/* -------------------------------------------- */
	function Updatemodule($id, $modulename, $moduledesc, $admin) {
		/* perform data checks */
		$modulename = mysqli_real_escape_string($GLOBALS['linki'], $modulename);
		$moduledesc = mysqli_real_escape_string($GLOBALS['linki'], $moduledesc);
		
		/* update the module */
		$sqlstring = "update modules set module_name = '$modulename', module_desc = '$moduledesc', module_admin = '$admin' where module_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		Notice("$modulename updated");
	}


	/* -------------------------------------------- */
	/* ------- Addmodule ------------------------- */
	/* -------------------------------------------- */
	function Addmodule($modulename, $moduledesc, $admin) {
		/* perform data checks */
		$modulename = mysqli_real_escape_string($GLOBALS['linki'], $modulename);
		$moduledesc = mysqli_real_escape_string($GLOBALS['linki'], $moduledesc);
		
		/* insert the new module */
		$sqlstring = "insert into modules (module_name, module_desc, module_admin, module_createdate, module_status) values ('$modulename', '$moduledesc', '$admin', now(), 'active')";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		Notice("$modulename added");
	}

	
	/* -------------------------------------------- */
	/* ------- ViewLogs --------------------------- */
	/* -------------------------------------------- */
	function ViewLogs($modulename) {

		$sqlstring = "select * from modules where module_name = '$modulename'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$id = $row['module_id'];
		$module_name = $row['module_name'];
		$module_status = $row['module_status'];
		$module_numrunning = $row['module_numrunning'];
		$module_laststart = $row['module_laststart'];
		$module_laststop = $row['module_laststop'];
		$module_isactive = $row['module_isactive'];
		$module_debug = $row['module_debug'];
		
		?>
		<div class="ui text container">
			<h2 class="ui header">
				<i class="puzzle piece icon"></i>
				<div class="content">
					<?=$modulename?>
					<div class="sub header">
					<? if ($module_isactive) {?>
					<a href="adminmodules.php?action=disable&id=<?=$id?>" title="<b>Module is currently enabled.</b> Click to disable">Disable</a>
					<? } else { ?>
					<a href="adminmodules.php?action=enable&id=<?=$id?>" title="<b>Module is currently disabled.</b> Click to enable">Enable</a>
					<? } ?>
					</div>
				</div>
			</h2>
		</div>
		<br><br>
		<div class="ui grid">
			<div class="sixteen wide column">
				<div class="ui styled segment">
					<div class="ui accordion">
						<?
						$systemstring = "ls -t " . $GLOBALS['cfg']['logdir'] . "/$modulename" . "*.log";
						//PrintVariable($systemstring);
						$filelisting = shell_exec($systemstring);
						$files = explode("\n", $filelisting);
						
						//chdir($GLOBALS['cfg']['logdir']);
						//$files = glob("$modulename"."2*.log");
						//usort($files, create_function('$b,$a', 'return filemtime($a) - filemtime($b);'));
						
						if (count($files) > 0) {
							foreach ($files as $filename) {
								if (trim($filename) == "")
									continue;
								
								$filesize = filesize($filename);
								$filedate = date ("F d Y H:i:s", filemtime($filename));
								?>
								<div class="ui title">
									<div class="ui header">
										<div class="content">
											<i class="dropdown icon"></i>
											<?=$filename?>
											<div class="sub header"><?=$filedate?> - <?=number_format($filesize,0)?> bytes</div>
										</div>
									</div>
								</div>
								<div class="content">
								<?
									if ($filesize < 1000000) {
										$contents = file_get_contents($filename);
										if ($contents === false) {
											$error = error_get_last();
											echo "Error getting file contents: $error<br>";
										}
										else {
											$contents = htmlspecialchars($contents, ENT_SUBSTITUTE);
											?>
											<pre style="border: 1px solid #aaa; background-color: #eee; padding:5px; white-space: pre-wrap;"><?=$contents?></pre>
											<?
										}
									} else { ?>
									File larger than 1MB, showing the first 500,000 bytes and the last 500,000 bytes<br><pre style="border: 1px solid #aaa; background-color: #eee; padding:5px">
	<?=preg_replace('/\x1B/', '', htmlspecialchars(file_get_contents($filename, null,null,0,500000), ENT_SUBSTITUTE))?>
									
									
			... ... ...
									
									
	<?=preg_replace('/\x1B/', '', htmlspecialchars(file_get_contents($filename, null,null,$filesize-500000), ENT_SUBSTITUTE))?>
								</pre>
								<?
								}
							?>
							</div>
							<br>
							<?
							}
						}
						else {
							echo "No $modulename files found<br>";
						}
						?>
					</div>
				</div>
			</div>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DebugModule ------------------------ */
	/* -------------------------------------------- */
	function DebugModule($id) {
		$sqlstring = "update modules set module_debug = 1 where module_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}


	/* -------------------------------------------- */
	/* ------- NoDebugModule ---------------------- */
	/* -------------------------------------------- */
	function NoDebugModule($id) {
		$sqlstring = "update modules set module_debug = 0 where module_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}

	
	/* -------------------------------------------- */
	/* ------- EnableModule ----------------------- */
	/* -------------------------------------------- */
	function EnableModule($id) {
		$sqlstring = "update modules set module_isactive = 1 where module_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}


	/* -------------------------------------------- */
	/* ------- DisableModule ---------------------- */
	/* -------------------------------------------- */
	function DisableModule($id) {
		$sqlstring = "update modules set module_isactive = 0 where module_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}

	
	/* -------------------------------------------- */
	/* ------- ResetModule ----------------------- */
	/* -------------------------------------------- */
	function ResetModule($id) {
		
		if (($id <= 0) || ($id == "")) {
			Error("ID was not valid [$id]");
		}
		
		/* get module name */
		$sqlstring = "select module_name from modules where module_id = '$id'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$scriptname = $row['module_name'];
		
		/* delete all lock files */
		$path = $GLOBALS['cfg']['lockdir'] . "/$scriptname*";
		//echo "$path<br>";
		$files = glob($path);
		//print_r($files);
		foreach ($files as $file) {
			if (stripos($file, $scriptname) !== false) {
				Notice("Deleting lock file [$file]");
				unlink($file);
			}
		}
		
		/* update DB to have 0 instances, status=stopped and lastfinish=now() */
		$sqlstring = "update modules set module_status = 'stopped', module_numrunning = 0, module_laststop = now() where module_id = '$id'";
		//echo "$sqlstring<br>";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayModuleList ------------------ */
	/* -------------------------------------------- */
	function DisplayModuleList() {
	
		/* create the color lookup table */
		$colors = GenerateColorGradient();
		
	?>

	<!--NiDB version <b><? //echo GetNiDBVersion();?></b>-->
	<br><br>
	
	<div class="ui container">
		<h2 class="ui header">Modules</h2>
		<table class="ui celled selectable compact table">
			<thead>
				<tr>
					<th>Name</th>
					<th>View Logs</th>
					<th>Status</th>
					<th>Instances</th>
					<th>Last finish</th>
					<th>Run time</th>
					<th>Enable</th>
					<th title="Enable debugging will always save the log file, and will output all SQL statements to the log file" style="text-decoration: underline; text-decoration-style: dotted">Debug</th>
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
						$module_debug = $row['module_debug'];
						
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
						
						if (!$module_isactive) { $rowclass = ""; } else { $rowclass = "positive"; }
				?>
				<tr>
					<td class="<?=$rowclass?>"><h3 class="header"><?=$module_name?></h3></td>
					<td><a href="adminmodules.php?action=viewlogs&modulename=<?=$module_name?>" class="ui button"><i class="file alternate outline icon"></i> View logs</a></td>
					<td style="color: <?=$color?>">
						<?=$module_status?> <? if (($module_status == "running") || ($module_numrunning != 0)) { ?> <a href="adminmodules.php?action=reset&id=<?=$id?>" class="ui small yellow button">reset</a> <? } ?>
					</td>
					<td><?=$module_numrunning?></td>
					<td><?=$module_laststop?></td>
					<td><?=$runtime?></td>
					<td>
						<?
							if ($module_isactive) {
								?><a href="adminmodules.php?action=disable&id=<?=$id?>" title="<b>Enabled.</b> Click to disable"><i class="big green toggle on icon"></i></a><?
							}
							else {
								?><a href="adminmodules.php?action=enable&id=<?=$id?>" title="<b>Disabled.</b> Click to enable"><i class="big grey horizontally flipped toggle on icon"></i></a><?
							}
						?>
					</td>
					<td>
						<?
							if ($module_debug) {
								?><a href="adminmodules.php?action=nodebug&id=<?=$id?>" title="<b>Enabled.</b> Click to disable"><i class="big green toggle on icon"></i></a><?
							}
							else {
								?><a href="adminmodules.php?action=debug&id=<?=$id?>" title="<b>Disabled.</b> Click to enable"><i class="big grey horizontally flipped toggle on icon"></i></a><?
							}
						?>
					</td>
				</tr>
				<? 
						/* get the list of threads/processes that are running */
						$sqlstringA = "select *, abs(time_to_sec(timediff(last_checkin, now()))) 'timediff', timediff(now(), last_checkin) 'timediff2' from module_procs where module_name = '$module_name' order by last_checkin";
						$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
						while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
							$lastcheckin = $rowA['last_checkin'];
							$timediff = $rowA['timediff'];
							$timediff2 = $rowA['timediff2'];
							$pid = $rowA['process_id'];
							
							/* get color index for the size */
							$maxtime = 2*60*60; /* 2 hours */
							$timeindex = round(($timediff/$maxtime)*100.0);
							if ($timeindex > 100) { $timeindex = 100; }
							$timecolor = $colors[$timeindex];
							
							?>
							<tr style="font-size: 9pt">
								<td colspan="4"> &nbsp; &nbsp; &nbsp;<?=$module_name?>:<?=$pid?></td>
								<td colspan="3" style="background-color: <?=$timecolor?>">Checked in <?=$lastcheckin?> &nbsp; (<?=$timediff2?> ago)</td>
							</tr>
							<?
						}
					}
				?>
			</tbody>
		</table>
	</div>
	<?
	}
?>


<? include("footer.php") ?>
