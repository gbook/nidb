<?
 // ------------------------------------------------------------------------------
 // NiDB menu.php
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

<table width="100%" cellspacing="0" cellpadding="0" style="background-color: 3b5998; padding:10px; border-bottom: 2px solid #35486D">
	<tr>
		<td width="250px">
			<form method="post" action="index.php" id="instanceform">
			<input type="hidden" name="action" value="switchinstance">
			<span style="font-size: 16pt; color: white; font-weight: normal; -webkit-font-smoothing: antialiased;"><?=$_SESSION['instancename']?></span>
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
		<td width="50px">
			&nbsp;
		</td>
		<td valign="top" style="background-color: 526faa; border-radius:5px; padding:5px; height: 30px">
			<div id="bluemenu" class="bluetabs">
				<ul>
					<li><span><a href="index.php">Home</a></span></li>
					<li><a href="subjects.php" rel="subjects_menu">Subjects</a></li>
					<li><a href="search.php" rel="search_menu"><b>Search</b></a></li>
					<li><a href="projects.php">Projects</a></li>
					<li><a href="pipelines.php" rel="analysis_menu">Analysis</a></li>
					<li><a href="import.php">Import</a></li>
					<li><a href="downloads.php">Downloads</a></li>
					<li><a href="calendar.php" rel="calendar_menu">Calendar</a></li>
					<? if ($GLOBALS['isadmin']) { ?>
					<li><a href="admin.php" rel="admin_menu">Admin</a></li>
					<? } ?>
					<li><a href="users.php" style="padding-left:50px" rel="user_menu"><?=$username?></a>
				</ul>
			</div>

			<div id="analysis_menu" class="dropmenudiv_b">
				<a href="pipelines.php">Pipelines</a>
				<!--<a href="csprefs.php">CenterScripts</a>-->
				<a href="common.php">Common objects</a>
				<a href="cluster.php">Cluster Stats</a>
			</div>
			
			<div id="calendar_menu" class="dropmenudiv_b">
				<? if ($GLOBALS['isadmin']) { ?>
				<a href="calendar_calendars.php">Manage</a>
				<? } ?>
			</div>
			
			<div id="subjects_menu" class="dropmenudiv_b">
				<a href="groups.php">Groups</a>
				<br><span style="color: #CCC">Most recent subjects</span>
				<?
				$sqlstring = "select a.mostrecent_date, a.subject_id, b.uid from mostrecent a left join subjects b on a.subject_id = b.subject_id where a.user_id in (select user_id from users where username = '$username') and a.subject_id is not null order by a.mostrecent_date desc";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$subjectid = $row['subject_id'];
						$date = date('M j g:ia',strtotime($row['mostrecent_date']));
						$uid = $row['uid'];
						?>
						<a href="subjects.php?id=<?=$subjectid?>" style="font-size:10pt"><?=$uid?></a>
						<?
					}
				}
				?>
				
				<span style="color: #CCC">Most recent studies</span>
				<?
				$sqlstring = "select a.mostrecent_date, a.study_id, b.study_num, d.uid from mostrecent a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on d.subject_id = c.subject_id where a.user_id in (select user_id from users where username = '$username') and a.study_id is not null order by a.mostrecent_date desc";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$studyid = $row['study_id'];
						$studynum = $row['study_num'];
						$date = date('M j g:ia',strtotime($row['mostrecent_date']));
						$uid = $row['uid'];
						?>
						<a href="studies.php?id=<?=$studyid?>" style="font-size:10pt"><?=$uid?><?=$studynum?></a>
						<?
					}
				}
				?>
			</div>
			
			<div id="search_menu" class="dropmenudiv_b">
				<a href="requeststatus.php">Data request status</a>
				<a href="publicdownloads.php">Public Downloads</a>
			</div>
			
			<? if ($GLOBALS['isadmin']) { ?>
			<div id="admin_menu" class="dropmenudiv_b">
				<a href="adminusers.php">Users</a>
				<a href="adminprojects.php">Projects</a>
				<a href="adminassessmentforms.php">Assessment Forms</a>
				<a href="adminmodules.php">Modules</a>
				<a href="adminmodalities.php">Modalities</a>
				<a href="adminsites.php">Sites</a>
				<a href="reports.php">Reports</a>
				<a href="adminqc.php">QC</a>
				<a href="importlog.php">Import Logs</a>
				<? if ($GLOBALS['issiteadmin']) { ?>
				<a href="admininstances.php">Instances</a>
				<? } ?>
				<a href="adminaudits.php">Audits</a>
				<a href="cleanup.php">Clean-up</a>
				<a href="system.php">Configuration</a>
				<a href="stats.php">Usage stats</a>
				<a href="longqc.php">Longitudinal QC</a>
			</div>
			<? } ?>
			
			<div id="user_menu" class="dropmenudiv_b">
				<a href="users.php">My Account</a>
				<a href="remoteconnections.php">Remote Connections</a>
				<a href="login.php?action=logout">Logout</a>
			</div>
		</td>
		<td align="right" valign="bottom">
			<form action="subjects.php" method="post">
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
	$dirs = glob($GLOBALS['cfg']['incomingdir'].'/*', GLOB_ONLYDIR);
	$numdicomdirs = count($dirs);
	
	# get number of files in dicomincoming directory
	$files = glob($GLOBALS['cfg']['incomingdir'].'/*');
	$numdicomfiles = count($files) - $numdicomdirs;
	
	# get number of import requests
	$sqlstring = "select count(*) 'numimportpending' from import_requests where import_status in ('pending','')";
	$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
	$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
	$numimportpending = $row['numimportpending'];
	
	# get number of directories in dicomincoming directory
	$dirs = glob($GLOBALS['cfg']['uploadedpath'].'/*', GLOB_ONLYDIR);
	$numimportdirs = count($dirs);
	
	/* get system load & number of cores */
	$load = sys_getloadavg();
	$cmd = "cat /proc/cpuinfo | grep processor | wc -l";
	$cpuCoreNo = intval(trim(shell_exec($cmd)));
	$percentLoad = number_format(($load[0]/$cpuCoreNo)*100.0,2);
	
?>
<table width="100%" cellspacing="0" cellpadding="0">
	<tr>
		<td width="100%" style="font-size: 8pt; padding: 2px"><a href="status.php">System status</a>: &nbsp; &nbsp; &nbsp; <b>CPU</b> <?=$percentLoad?>% &nbsp; &nbsp; &nbsp; <b>Import queue</b> <?=$numimportpending?> requests, <?=$numimportdirs?> dirs &nbsp; &nbsp; &nbsp; <b>Archive queue</b> <?=$numdicomfiles?> files, <?=$numdicomdirs?> dirs &nbsp; &nbsp; &nbsp; <b>File IO queue</b> <?=$numiopending?> operations</td>
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