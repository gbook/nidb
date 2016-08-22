<?
 // ------------------------------------------------------------------------------
 // NiDB adminmodules.php
 // Copyright (C) 2004 - 2016
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
	require "includes.php";
	require "menu.php";

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
		
		?><div align="center"><span class="message"><?=$modulename?> updated</span></div><br><br><?
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
		
		?><div align="center"><span class="message"><?=$modulename?> added</span></div><br><br><?
	}

	
	/* -------------------------------------------- */
	/* ------- ViewLogs --------------------------- */
	/* -------------------------------------------- */
	function ViewLogs($modulename) {
	
		$urllist['Administration'] = "admin.php";
		$urllist['Modules'] = "adminmodules.php";
		NavigationBar("Admin", $urllist);
		
		chdir($GLOBALS['cfg']['logdir']);
		$files = glob("$modulename*.log");
		usort($files, create_function('$b,$a', 'return filemtime($a) - filemtime($b);'));
		foreach ($files as $filename) {
			$filesize = filesize($filename);
			$filedate = date ("F d Y H:i:s", filemtime($filename));
			?>
			<details>
			<summary><?=$filename?> <span class="tiny"><?=$filedate?> - <?=number_format($filesize,0)?> bytes</span></summary>
			<? if ($filesize < 50000000) {?>
			<pre style="border: 1px solid #aaa; background-color: #eee; padding:5px"><?=htmlspecialchars(file_get_contents($filename))?></pre>
			<? } else { ?>
			File too large to display
			<? } ?>
			</details>
			<?
		}
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
		/* get module name */
		$sqlstring = "select module_name from modules where module_id = $id";
		$result = MySQLiQuery($sqlstring) or die(SQLError(__FILE__, __LINE__, mysql_error(), $sqlstring));
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$scriptname = $row['module_name'];
		
		/* delete all lock files */
		$path = $GLOBALS['cfg']['scriptdir'] . "/lock/$scriptname*";
		//echo "$path<br>";
		$files = glob($path);
		//print_r($files);
		foreach ($files as $file) {
			if (stripos($file, $scriptname) !== false) {
				?><div class="message">Deleting lock file [<?=$file?>]</div><?
				unlink($file);
			}
		}
		
		/* update DB to have 0 instances, status=stopped and lastfinish=now() */
		$sqlstring = "update modules set module_status = 'stopped', module_numrunning = 0, module_laststop = now() where module_id = $id";
		//echo "$sqlstring<br>";
		$result = MySQLiQuery($sqlstring) or die(SQLError(__FILE__, __LINE__, mysql_error(), $sqlstring));
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayModuleList ------------------ */
	/* -------------------------------------------- */
	function DisplayModuleList() {
	
		$urllist['Administration'] = "admin.php";
		$urllist['Modules'] = "adminmodules.php";
		NavigationBar("Admin", $urllist);
		
	?>

	<table class="graydisplaytable">
		<thead>
			<tr>
				<th>Name</th>
				<th>&nbsp;</th>
				<th>Status</th>
				<th>Instances</th>
				<th>Last finish</th>
				<th>Run time</th>
				<th>Enable/Disable</th>
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
				<td style="color: <?=$color?>"><?=$module_status?> <? if (($module_status == "running") || ($module_numrunning != 0)) { ?><small>(<a href="adminmodules.php?action=reset&id=<?=$id?>">reset</a>)</small> <? } ?></td>
				<td><?=$module_numrunning?></td>
				<td><?=$module_laststop?></td>
				<td><?=$runtime?></td>
				<td>
					<?
						if ($module_isactive) {
							?><a href="adminmodules.php?action=disable&id=<?=$id?>"><img src="images/checkedbox16.png"></a><?
						}
						else {
							?><a href="adminmodules.php?action=enable&id=<?=$id?>"><img src="images/uncheckedbox16.png"></a><?
						}
					?>
				</td>
			</tr>
			<? 
				}
			?>
		</tbody>
	</table>
	<?
	}
?>


<? include("footer.php") ?>
