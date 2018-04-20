<?
 // ------------------------------------------------------------------------------
 // NiDB menu.php
 // Copyright (C) 2004 - 2018
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
?>


<script language="javascript" type="text/javascript">
    $(document).ready(function() {
        $('#menu1').dropmenu(
            { effect: 'none', nbsp: true }
        );
    });
</script>

<script type="text/javascript" src="scripts/dropdowntabs.js"></script>
<link rel="stylesheet" type="text/css" href="scripts/bluetabs.css" />


<? if ($isdevserver) { ?>
<div style="position:fixed; width:100%">
<table width="95%">
	<tr>
		<td style="background-color: darkred; color: white; height: 25px; vertical-align: middle; text-align: center; border-top: 2px solid #300000; border-bottom: 2px solid #300000" cellpadding="0" cellspacing="0">
			<b>Development server</b> - Use for testing only
		</td>
	</tr>
</table>
</div>
<br><br>
<? } ?>

<!-- menu -->

<table width="100%" cellspacing="0" cellpadding="0" style="background-color: 3b5998; padding:0px; border-bottom: 2px solid #35486D">
	<tr>
		<td style="width: 350px; color: white; background-color: <?=$GLOBALS['cfg']['sitecolor']?>; padding: 2px 10px; font-size: 16pt; font-weight: bold">
			<?=$GLOBALS['cfg']['sitename']?>
		</td>
		<td valign="top" style="background-color: 526faa; padding: 10px">
			<style>
				/* main menu */
				#menu-bar { width: 95%; margin: 0px 0px 0px 0px; padding: 0px; height: 32px; line-height: 100%; background: #526FAA; border: none; position:relative; z-index:999; }
				#menu-bar li { margin: 0px 0px 6px 0px; padding: 0px 5px 0px 5px; float: left; position: relative; list-style: none; }
				#menu-bar a { font-family: arial; font-style: normal; font-size: 14px; color: #fff; text-decoration: none; display: block; padding: 6px 10px 6px 10px; margin: 0; margin-bottom: 0px; }
				#menu-bar li ul li a { margin: 0; }
				#menu-bar .active a, #menu-bar li:hover > a { background: #405785; color: #fff; height: 19px; }
				#menu-bar ul li:hover a, #menu-bar li:hover li a { background: #405785; border: none; color: #fff; height: 19px; }
				#menu-bar ul a:hover { background: #fff !important; color: #000 !important; }
				#menu-bar li:hover > ul { display: block; }
				#menu-bar ul { background: #98bce5; display: none; margin: 0; padding: 0; width: 185px; position: absolute; top: 30px; left: 0; border: 1px solid #405785; }
				#menu-bar ul li { float: none; margin: 0; padding: 0; color: #fff; }
				#menu-bar ul a { padding:8px 0px 8px 13px; color:#fff !important; font-size:14px; font-style:normal; font-family:arial; font-weight: normal; }
				.menuheading { padding:8px 0px 8px 13px; margin:8px 0px 8px 13px; color:#fff !important; font-size:14px; font-style:normal; font-family:arial; font-weight: bold; background: #405785; }
				#menu-bar:after { content: "."; display: block; clear: both; visibility: hidden; line-height: 0; height: 0; }
				#menu-bar { display: inline-block; }
				  html[xmlns] #menu-bar { display: block; }
				* html #menu-bar { height: 1%; }			
			</style>
			<ul id="menu-bar">
				<li class="active"><a href="index.php">Home</a></li>
				<li><a href="subjects.php">Subjects</a>
					<ul>
						<li><a href="groups.php">Groups</a></li>
						<li class="menuheading">Most Recent Subjects</li>
						<?
						$sqlstring = "select a.mostrecent_date, a.subject_id, b.uid from mostrecent a left join subjects b on a.subject_id = b.subject_id where a.user_id in (select user_id from users where username = '$username') and a.subject_id is not null order by a.mostrecent_date desc";
						$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						if (mysqli_num_rows($result) > 0) {
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$subjectid = $row['subject_id'];
								$date = date('M j g:ia',strtotime($row['mostrecent_date']));
								$uid = $row['uid'];
								?>
								<li><a href="subjects.php?id=<?=$subjectid?>"><?=$uid?></a></li>
								<?
							}
						}
						?>
						<li class="menuheading">Most Recent Studies</li>
						<?
						$sqlstring = "select a.mostrecent_date, a.study_id, b.study_num, d.uid from mostrecent a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on d.subject_id = c.subject_id where a.user_id in (select user_id from users where username = '$username') and a.study_id is not null order by a.mostrecent_date desc";
						$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						if (mysqli_num_rows($result) > 0) {
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$studyid = $row['study_id'];
								$studynum = $row['study_num'];
								$date = date('M j g:ia',strtotime($row['mostrecent_date']));
								$uid = $row['uid'];
								?>
								<li><a href="studies.php?id=<?=$studyid?>"><?=$uid?><?=$studynum?></a></li>
								<?
							}
						}
						?>
					</ul>
				</li>
				<li><a href="search.php"><b>Search</b></a>
					<ul>
						<li><a href="requeststatus.php">Download status</a></li>
						<li><a href="publicdownloads.php">Public Downloads</a></li>
					</ul>
				</li>
				<li><a href="projects.php">Projects</a></li>
				<li><a href="pipelines.php">Analysis</a>
					<ul>
						<li><a href="pipelines.php">Pipelines</a></li>
						<li><a href="common.php">Common Objects</a></li>
						<li><a href="cluster.php">Cluster Stats</a></li>
					</ul>
				</li>
				<li><a href="import.php">Import</a></li>
				<li><a href="downloads.php">Downloads</a></li>
				<li><a href="calendar.php">Calendar</a>
					<? if ($GLOBALS['isadmin']) { ?>
					<ul>
						<li><a href="calendar_calendars.php">Manage</a></li>
					</ul>
					<? } ?>
				</li>
			 
				<? if ($GLOBALS['isadmin']) { ?>
				<li><a href="admin.php">Admin</a>
					<ul>
						<li><a href="adminusers.php">Users</a></li>
						<li><a href="adminprojects.php">Projects</a></li>
						<li><a href="adminassessmentforms.php">Assessment Forms</a></li>
						<li><a href="adminmodules.php">Modules</a></li>
						<li><a href="adminmodalities.php">Modalities</a></li>
						<li><a href="reports.php">Reports</a></li>
						<li><a href="adminqc.php">QC</a></li>
						<li><a href="importlog.php">Import Logs</a></li>
						<li><a href="stats.php">Usage stats</a></li>
						<li><a href="longqc.php">Longitudinal QC</a></li>
						<? if ($GLOBALS['issiteadmin']) { ?>
						<li><a href="cleanup.php">Clean-up</a></li>
						<li><a href="adminsites.php">Sites</a></li>
						<li><a href="admininstances.php">Instances</a></li>
						<li><a href="adminaudits.php">Audits</a></li>
						<li><a href="system.php">NiDB Settings...</a></li>
						<? } ?>
					</ul>
				</li>
				<? } ?>
				<li><a href="users.php">My Account</a>
					<ul>
						<li><a href="users.php">My Account</a></li>
						<li><a href="remoteconnections.php">Remote Connections</a></li>
						<li><a href="login.php?action=logout">Logout</a></li>
					</ul>
				</li>
			</ul>
			
		</td>
		<td align="left" style="color: white; padding: 8px 15px">
			<form method="post" action="index.php" id="instanceform" style="margin:0px">
			<input type="hidden" name="action" value="switchinstance">
			<span style="font-size:9pt"><i>Instance</i></span><br>
			<span style="font-size: 12pt; color: white; font-weight: normal; -webkit-font-smoothing: antialiased;"><?=$_SESSION['instancename']?></span>
			<select name="instanceid" style="background-color: #3B5998; border: 1px solid #526FAA; width: 20px; color: white" title="Switch instance" onChange="instanceform.submit()">
				<option value="">Select Instance...</option>
				<?
					$sqlstring = "select * from instance where instance_id in (select instance_id from user_instance where user_id = (select user_id from users where username = '" . $GLOBALS['username'] . "')) order by instance_name";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$instance_id = $row['instance_id'];
						$instance_name = $row['instance_name'];
						?>
						<option value="<?=$instance_id?>"><?=$instance_name?></option>
						<?
					}
				?>
			</select>
			</form>
		</td>
		<td align="center" valign="middle">
			<form action="subjects.php" method="post" style="margin: 0px">
			<input type="hidden" name="action" value="search">
			<input type="hidden" name="searchactive" value="1">
			<input placeholder="UID search" name="searchuid" type="text" size="9" style="background-color: #526FAA; color: white; border:1px solid #526FAA">
			<input type="submit" style="display:none; width: 0px height: 0px">
			</form>
			
		</td>
		
	</tr>
</table>
<script type="text/javascript">
	tabdropdown.init("bluemenu")
</script>

<!-- display system status -->
<?
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
<table width="100%" cellspacing="0" cellpadding="0">
	<tr>
		<td width="100%" style="font-size: 8pt; padding: 2px">
			<? if ($GLOBALS['issiteadmin']) { ?>
			<a href="status.php">System status</a>:
			<? } else { ?>
			System status:
			<? } ?>
			&nbsp; &nbsp; &nbsp; <b>CPU</b> <?=$percentLoad?>% (on <?=$cpuCoreNo?> cores) &nbsp; &nbsp; &nbsp; <b>Import queue</b> <?=$numimportpending?> requests, <?=$numimportdirs?> dirs &nbsp; &nbsp; &nbsp; <b>Archive queue</b> <?=$numdicomfiles?> files, <?=$numdicomdirs?> dirs &nbsp; &nbsp; &nbsp; <b>File IO queue</b> <?=$numiopending?> operations
			&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <b>Module status:</b> 
			<span style="background-color: <?=$moduleinfo['parsedicom']['color']?>" title="Status: <?=$moduleinfo['parsedicom']['status']?>">&nbsp;parsedicom&nbsp;</span> 
			<span style="background-color: <?=$moduleinfo['fileio']['color']?>" title="Status: <?=$moduleinfo['fileio']['status']?>">&nbsp;fileio&nbsp;</span> 
			<span style="background-color: <?=$moduleinfo['pipeline']['color']?>" title="Status: <?=$moduleinfo['pipeline']['status']?>">&nbsp;pipeline&nbsp;</span>
			<span style="background-color: <?=$moduleinfo['datarequests']['color']?>" title="Status: <?=$moduleinfo['datarequests']['status']?>">&nbsp;datarequests&nbsp;</span>
			<span style="background-color: <?=$moduleinfo['mriqa']['color']?>" title="Status: <?=$moduleinfo['mriqa']['status']?>">&nbsp;mriqa&nbsp;</span>
		</td>
	</tr>
</table>

<?
/* check for system status messages */
	$sqlstring = "select * from system_messages where message_status = 'active' order by message_date desc";
	$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	if (mysqli_num_rows($result) > 0) {
?>
<table width="100%" cellspacing="0" cellpadding="0">
	<tr>
		<td width="100%" style="border: 3px solid #FF5500;">
			<div style="color: white; background-color: #FF5500; padding: 5px">System messages</div>
			<ul>
			<?
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$message = $row['message'];
				$message_date = $row['message_date'];
			?>
			<li><?=$message_date?> - <?=$message?>
			<? } ?>
			</ul>
		</td>
	</tr>
</table>
<? } ?>
<br>
<table width="100%" cellpadding="5">
	<tr>
		<td width="100%">
			<!--  begin main page content -->
<?			
	if (count($_POST, COUNT_RECURSIVE) >= ini_get("max_input_vars")) {
		?>
		<div class="staticmessage">You POSTed <?=count($_POST, COUNT_RECURSIVE)?> variables, but your server's PHP limit is <?=ini_get("max_input_vars")?>. Truncation of the submitted form may have occured.<br>
		Contact your server administrator or increase the <code>max_input_vars</code> PHP variable.</div>
		<?
	}
?>