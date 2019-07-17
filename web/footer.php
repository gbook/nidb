<?
 // ------------------------------------------------------------------------------
 // NiDB footer.php
 // Copyright (C) 2004 - 2019
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

	$time = microtime();
	$time = explode(' ', $time);
	$time = $time[1] + $time[0];
	$pagefinish = $time;
	$pagetotaltime = round(($pagefinish - $GLOBALS['pagestart']), 3);
	
	# get number of fileio operations pending
	$sqlstring = "select count(*) 'numiopending' from fileio_requests where request_status in ('pending','')";
	$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
	$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
	$numiopending = $row['numiopending'];
	
	# get number of directories in dicomincoming directory
	//$dirs = glob($GLOBALS['cfg']['incomingdir'].'/*', GLOB_ONLYDIR);
	$dirs = 0;
	$numdicomdirs = count($dirs);
	
	# get number of files in dicomincoming directory
	//$files = glob($GLOBALS['cfg']['incomingdir'].'/*');
	$files = 0;
	$numdicomfiles = count($files) - $numdicomdirs;
	
	# get number of import requests
	$sqlstring = "select count(*) 'numimportpending' from import_requests where import_status in ('pending','')";
	$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
	$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
	$numimportpending = $row['numimportpending'];
	
	# get number of directories in dicomincoming directory
	$dirs = glob($GLOBALS['cfg']['uploadeddir'].'/*', GLOB_ONLYDIR);
	$dirs = 0;
	$numimportdirs = count($dirs);
	
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
			$moduleinfo[$name]['color'] = "#f00";
			$moduleinfo[$name]['status'] = 'Disabled';
		}
		else {
			if ($moduleinfo[$name]['status'] == "running") {
				$moduleinfo[$name]['color'] = "#bcffc5";
				$moduleinfo[$name]['status'] = 'Running';
			}
			if ($moduleinfo[$name]['status'] == "stopped") {
				$moduleinfo[$name]['color'] = "#adc7ff";
				$moduleinfo[$name]['status'] = 'Enabled';
			}
		}
	}
	
?>
		<!-- end main page content -->
		</td>
	</tr>
</table>

<br><br><br>

<!-- footer -->
<div id="footer">
<table width="100%" cellspacing="0" cellpadding="6" style="background-color: 3b5998; border-top: 2px solid #35486D">
	<tr>
		<td width="20%" align="left" style="font-size:8pt;">
			<a href="about.php" style="color: white; text-decoration:none"><img src="images/nidb_plain.png" style="height:15px; border: 4px white solid; border-radius:2px"> v<?=$GLOBALS['cfg']['version']?></a>
		</td>
		<td width="60%" align="center" style="font-size:8pt; color: white">
			Page generated: <? echo date("D M j, Y g:i a T"); ?> &nbsp; &nbsp; Page creation time: <?=$pagetotaltime?> sec<br>
			<? if ($GLOBALS['issiteadmin']) { ?>
			<a href="status.php" style="color: #fff">System status</a>:
			<? } else { ?>
			System status:
			<? } ?>
			&nbsp; &nbsp; &nbsp; <b>CPU</b> <?=$percentLoad?>% (on <?=$cpuCoreNo?> cores) &nbsp; &nbsp; &nbsp; <b>Import queue</b> <?=$numimportpending?> requests, <?=$numimportdirs?> dirs &nbsp; &nbsp; &nbsp; <b>Archive queue</b> <?=$numdicomfiles?> files, <?=$numdicomdirs?> dirs &nbsp; &nbsp; &nbsp; <b>File IO queue</b> <?=$numiopending?> operations
			&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <b>Module status:</b> 
			<span style="background-color: <?=$moduleinfo['import']['color']?>" title="Status: <?=$moduleinfo['import']['status']?>">&nbsp;import&nbsp;</span> 
			<span style="background-color: <?=$moduleinfo['fileio']['color']?>" title="Status: <?=$moduleinfo['fileio']['status']?>">&nbsp;fileio&nbsp;</span> 
			<span style="background-color: <?=$moduleinfo['pipeline']['color']?>" title="Status: <?=$moduleinfo['pipeline']['status']?>">&nbsp;pipeline&nbsp;</span>
			<span style="background-color: <?=$moduleinfo['export']['color']?>" title="Status: <?=$moduleinfo['export']['status']?>">&nbsp;export&nbsp;</span>
			<span style="background-color: <?=$moduleinfo['mriqa']['color']?>" title="Status: <?=$moduleinfo['mriqa']['status']?>">&nbsp;mriqa&nbsp;</span>
		</td>
		<td align="right" style="font-size:8pt; color: white">
			<span style="color: white; font-size:8pt; padding-right: 5px; padding-left: 5px">Problem, bug, or comment? <a href="https://github.com/gbook/nidb/issues" style="color:white; text-decoration:underline">Report it</a></span>
		</td>
	</tr>
</table>
</div>
</div>
</body>
</html>