<?
 // ------------------------------------------------------------------------------
 // NiDB footer.php
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

	$time = microtime();
	$time = explode(' ', $time);
	$time = $time[1] + $time[0];
	$pagefinish = $time;
	$pagetotaltime = round(($pagefinish - $GLOBALS['pagestart']), 3);
	
	/* get system load & number of cores */
	$load = sys_getloadavg();
	$cmd = "cat /proc/cpuinfo | grep processor | wc -l";
	$cpuCoreNo = intval(trim(shell_exec($cmd)));
	$percentLoad = number_format(($load[0]/$cpuCoreNo)*100.0,2);
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
		<td width="33%" align="left" style="font-size:8pt;">
			<a href="about.php" style="color: white; text-decoration:none"><img src="images/nidb_plain.png" style="height:15px; border: 4px white solid; border-radius:2px"> v<?=$GLOBALS['cfg']['version']?></a>
		</td>
		<td width="33%" align="center" style="font-size:8pt; color: white">
			Generated: <? echo date("D M j, Y g:i a T"); ?><br>
			Load time: <?=$pagetotaltime?> sec &nbsp; &nbsp; System load <?=$percentLoad?>%
			&nbsp; &nbsp; <a href="status.php" style="color:white; text-decoration:underline">Status</a>
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