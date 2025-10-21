<?
 // ------------------------------------------------------------------------------
 // NiDB footer.php
 // Copyright (C) 2004 - 2025
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

	if (!defined("LEGIT_REQUEST")) die ("This page cannot be called directly.");

	$time = microtime();
	$time = explode(' ', $time);
	$time = $time[1] + $time[0];
	$pagefinish = $time;
	$pagetotaltime = round(($pagefinish - $GLOBALS['pagestart']), 3);
	
	# get number of fileio operations pending
	//$sqlstring = "select count(*) 'numiopending' from fileio_requests where request_status in ('pending','')";
	//$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
	//$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
	//$numiopending = $row['numiopending'];
	
	# get number of directories in dicomincoming directory
	//$dirs = glob($GLOBALS['cfg']['incomingdir'].'/*', GLOB_ONLYDIR);
	//$dirs = 0;
	//$numdicomdirs = count($dirs);
	
	# get number of files in dicomincoming directory
	//$files = glob($GLOBALS['cfg']['incomingdir'].'/*');
	//$files = 0;
	//$numdicomfiles = count($files) - $numdicomdirs;
	
	# get number of import requests
	//$sqlstring = "select count(*) 'numimportpending' from import_requests where import_status in ('pending','')";
	//$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
	//$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
	//$numimportpending = $row['numimportpending'];
	
	# get number of directories in dicomincoming directory
	//$dirs = glob($GLOBALS['cfg']['uploadeddir'].'/*', GLOB_ONLYDIR);
	//$dirs = 0;
	//$numimportdirs = count($dirs);
	
	/* get system load & number of cores */
	$load = sys_getloadavg();
	$cmd = "cat /proc/cpuinfo | grep processor | wc -l";
	$cpuCoreNo = intval(trim(shell_exec($cmd)));
	$percentLoad = number_format(($load[0]/$cpuCoreNo)*100.0,2);
	
	$sqlstring = "select * from modules order by module_name";
	$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
		$name = $row['module_name'];
		$moduleinfo[$name]['status'] = $row['module_status'];
		$moduleinfo[$name]['numrunning'] = $row['module_numrunning'];
		$moduleinfo[$name]['isactive'] = $row['module_isactive'];
		
		/* calculate the status color */
		if (!$moduleinfo[$name]['isactive']) {
			$moduleinfo[$name]['color'] = "red";
			$moduleinfo[$name]['status'] = 'Disabled';
		}
		else {
			if ($moduleinfo[$name]['status'] == "running") {
				$moduleinfo[$name]['color'] = "green";
				$moduleinfo[$name]['status'] = 'Running';
			}
			if ($moduleinfo[$name]['status'] == "stopped") {
				$moduleinfo[$name]['color'] = "blue";
				$moduleinfo[$name]['status'] = 'Enabled';
			}
		}
	}
	
?>
<!-- end main page content -->

	</div> <!-- end column div -->
</div> <!-- end page grid div -->

<br><br><br><br>
<!-- footer -->
<div class="ui mini inverted menu" style="!important; overflow: auto; position:absolute; bottom:0; left:0;" id="footer">
	<div class="item" style="font-size: larger">
		<em data-emoji=":chipmunk:"></em>&nbsp; <a href="neuroinfodb.org"><b>NiDB</b> v<?=$GLOBALS['cfg']['version']?></a>
	</div>
	<div class="item">
		Page generated: <? echo date("D M j, Y g:i a T"); ?> in <?=$pagetotaltime?> sec
	</div>
	<div class="item">
		System status: <b>CPU</b> <?=$percentLoad?>% (on <?=$cpuCoreNo?> cores) &nbsp; &nbsp; &nbsp; <b>Module status:</b> 
		<div class="ui mini <?=$moduleinfo['import']['color']?> label" title="Status: <?=$moduleinfo['import']['status']?>">import</div> 
		<div class="ui mini <?=$moduleinfo['fileio']['color']?> label" title="Status: <?=$moduleinfo['fileio']['status']?>">fileio</div> 
		<div class="ui mini <?=$moduleinfo['pipeline']['color']?> label" title="Status: <?=$moduleinfo['pipeline']['status']?>">pipeline</div>
		<div class="ui mini <?=$moduleinfo['export']['color']?> label" title="Status: <?=$moduleinfo['export']['status']?>">export</div>
		<div class="ui mini <?=$moduleinfo['mriqa']['color']?> label" title="Status: <?=$moduleinfo['mriqa']['status']?>">mriqa</div>
	</div>
	<div class="right menu">
		<div class="item">
			Problem, bug, or comment? &nbsp;<a href="https://github.com/gbook/nidb/issues"><i class="github icon"></i> Report it</a>
		</div>
	</div>
</div>

</body>
</html>