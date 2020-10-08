<?
 // ------------------------------------------------------------------------------
 // NiDB menu.php
 // Copyright (C) 2004 - 2020
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

	$page=basename($_SERVER['PHP_SELF']);
	$action = GetVariable("action");
?>

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
	<style>
		#Bar1 a:link { background-color:#526FAA; color: white; padding: 10px 12px; text-align: center; text-decoration: none; display: inline-block; white-space: nowrap; font-size:11pt; min-width: 70px; }
		#Bar1 a:visited { background-color:#526FAA; color: white; padding: 10px 12px; text-align: center; text-decoration: none; font-size:11pt; }
		#Bar1 a:hover { background-color: #3B5998; }
		#Bar1 a:active { background-color: #3B5998; }
		
		#Bar2 a:link{ background-color:#3B5998; color: white; padding: 10px 15px; text-align: center; text-decoration: none; display: inline-block; white-space: nowrap;font-size:11pt;}
		#Bar2 a:visited { background-color:#3B5998; color: white; padding:10px 15px; text-align: center; text-decoration: none; font-size:11pt;}
		#Bar2 a:hover { background-color: #526FAA; }
		#Bar2 a:active { background-color: #526FAA; }
	</style>

	<table width="100%" cellspacing="0" cellpadding="0" style="background-color:#526FAA; border-spacing: 0px; padding:0px; border-bottom: 2px solid #444">
		<tr>
			<td rowspan="2" align="center" style="width: 300px; color: white; background-color: <?=$GLOBALS['cfg']['sitecolor']?>; padding: 5px 15px; font-size: 16pt; overflow: hidden; text-overflow: ellipsis; max-width: 300px; white-space:nowrap;">
			<b><?=$GLOBALS['cfg']['sitename']?></b><br>
			
			<form method="post" action="index.php" id="instanceform" style="margin:0px">
			<input type="hidden" name="action" value="switchinstance">
			<!--<span style="font-size:9pt;"><i>Project group</i></span><br>-->
			
			<span style="overflow: hidden; text-overflow: ellipsis; width: 250px; display: inline-block; font-size: 10pt; color: white; white-space: nowrap" title="<?=$_SESSION['instancename']?>"><?=$_SESSION['instancename']?></span>
			<select name="instanceid" style="background-color: <?=$GLOBALS['cfg']['sitecolor']?>; padding:0;border: 0px solid #526FAA; width:20px; color: white" title="Switch instance (project group)" onChange="instanceform.submit()">
				<option value="">Select Project Group...</option>
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
			<td id="Bar1" style="background-color:#526FAA; padding: 0px" width="60%">
			<?
				/* home */
				if ($page=="index.php"){ $style = "background-color:#3B5998"; }
				else { $style = ""; }
				?><a href="index.php" style="<?=$style?>"><b>Home</b></a><?
				
				/* search */
				if ($page=="search.php" || $page=="requeststatus.php" || $page=="analysisbuilder.php" || $page=="batchupload.php") { $style = "background-color:#3B5998"; }
				else { $style = ""; }
				?><a href="search.php" style="<?=$style?>"><b>Search</b></a><?
				
				/* subjects */
				if ($page=="subjects.php" || $page=="groups.php") { $style = "background-color:#3B5998"; }
				else { $style = ""; }
				?><a href="subjects.php" style="<?=$style?>"><b>Subjects</b></a><?
				
				/* projects */
				if ($page=="projects.php" || $page=="projectchecklist.php" || $page=="mrqcchecklist.php" || $page=="studies.php" || $page=="measures.php" || $page=="minipipeline.php" || $page=="templates.php") { $style = "background-color:#3B5998"; }
				else { $style = ""; }
				?><a href="projects.php" style="<?=$style?>"><b>Projects</b></a><?
				
				/* pipelines */
				if ($GLOBALS['cfg']['enablepipelines']) {
					if ($page=="pipelines.php" || $page=="analysis.php") { $style = "background-color:#3B5998"; }
					else { $style = ""; }
					?><a href="pipelines.php" style="<?=$style?>"><b>Pipelines</b></a><?
				}
				
				/* data */
				if ($GLOBALS['cfg']['enabledatamenu']) {
					if ($page=="import.php" || $page=="importimaging.php" || $page=="importlog.php" || $page=="publicdownloads.php" || $page=="downloads.php") { $style = "background-color:#3B5998"; }
					else { $style = ""; }
					?><a href="import.php" style="<?=$style?>"><b>Data</b></a><?
				}
				
				/* calendar */
				if ($GLOBALS['cfg']['enablecalendar']) {
					if ($page=="calendar.php" || $page=="calendar_calendars.php") { $style = "background-color:#3B5998"; }
					else { $style = ""; }
					?><a href="calendar.php" style="<?=$style?>"><b>Calendar</b></a><?
				}
				
			?>
			</td>
			<td id="bar1" align="right" style="background-color: 526faa; padding: 0px">
			<?
				/* admin */
				if ($GLOBALS['isadmin']) {
					if ((substr($page,0,5) == "admin") || ($page == "system.php") || ($page == "status.php") || ($page == "reports.php") || ($page == "cleanup.php") || ($page == "stats.php") || ($page == "status.php") || ($page == "longqc.php")) { $style = "background-color:#3B5998"; }
					else { $style = ""; }
					?><a href="admin.php" style="<?=$style?>"><b>Admin</b></a><?
				}
				
				/* user options */
				if ($page=="users.php" || $page=="remoteconnections.php") { $style = "background-color:#3B5998"; }
				else { $style = ""; }
				?><a href="users.php" style="<?=$style?>"><b>My Account</b></a><?
			?>
				<a href="login.php?action=logout"></b>Logout<b></a>
			</td>
		</tr>
		<tr>
			<td id="Bar2" style="background-color:#3B5998; padding:0px" width="60%">
			<?
				
				/* home sub-menu */
				if ($page=="index.php") {
					$style = "background-color:#273f70";
					?><a href="index.php" style="<?=$style?>">Home</a><?
				}
				
				/* search sub-menu */
				elseif ($page=="search.php" || $page=="requeststatus.php" || $page=="analysisbuilder.php" || $page=="batchupload.php") {
					
					if ($page=="search.php" || $page=="batchupload.php"){ $style = "background-color:#273f70"; }
					else { $style = ""; }
					?><a href="search.php" style="<?=$style?>">Search</a><?
					
					if ($page=="requeststatus.php"){ $style = "background-color:#273f70"; }
					else { $style = ""; }
					?><a href="requeststatus.php" style="<?=$style?>">Export Status</a><?
					
					if ($page=="analysisbuilder.php"){ $style = "background-color:#273f70"; }
					else { $style = ""; }
					?><a href="analysisbuilder.php" style="<?=$style?>">Analysis Builder</a><?
				}
				
				/* subjects sub-menu */
				elseif ($page=="subjects.php" || $page=="groups.php") {
					if ($page=="subjects.php"){ $style = "background-color:#273f70"; }
					else { $style = ""; }
					?><a href="subjects.php" style="<?=$style?>">Subjects</a><?

					if ($page=="groups.php"){ $style = "background-color:#273f70"; }
					else { $style = ""; }
					?><a href="groups.php" style="<?=$style?>">Groups</a><?
					
					if ($page=="measures.php"){ $style = "background-color:#273f70"; }
					else { $style = ""; }
					?><a href="measures.php" style="<?=$style?>">Measures</a><?
				}
				
				/* studies, which are displayed under the projects menu */
				elseif ($page == "studies.php" || $page=="measures.php") {
					$studyid = GetVariable("id");
					$enrollmentid = GetVariable("enrollmentid");
					
					if ($studyid == "") {
						$studyid = GetVariable("studyid");
					}
					if ($studyid != "") {
						list($path1, $uid, $studynum, $studyid, $subjectid, $modality1, $studytype1, $studydatetime1, $enrollmentid1, $projectname, $projectid) = GetStudyInfo($studyid);
						?>
						<a href="projects.php">Project List</a>
						<b><a href="projects.php?action=displayprojectinfo&id=<?=$projectid?>"><?=$projectname?></a></b>
						<a href="subjects.php?id=<?=$subjectid?>"><?=$uid?></a>
						<a href="studies.php?id=<?=$studyid?>" style="background-color:#273f70">Study <?=$studynum?></a>
						<?
					}
					elseif (($enrollmentid != "") && ($page == "measures.php")) {
						list($uid, $subjectid, $projectname, $projectid) = GetEnrollmentInfo($enrollmentid);
						?>
						<a href="projects.php">Project List</a>
						<b><a href="projects.php?action=displayprojectinfo&id=<?=$projectid?>"><?=$projectname?></a></b>
						<a href="subjects.php?id=<?=$subjectid?>"><?=$uid?></a>
						<?
					}
				}
				
				/* projects sub-menu */
				elseif ($page=="projects.php" || $page=="projectchecklist.php" || $page=="mrqcchecklist.php" || $page=="projectassessments.php" || $page=="studies.php" || $page=="minipipeline.php" || $page=="templates.php" || $page=="datadictionary.php") {
					
					if ($page=="projectchecklist.php" || $page=="projectassessments.php" || $page=="minipipeline.php" || $page=="templates.php" || $page=="datadictionary.php") {
						$projectid = GetVariable("projectid");
					}
					else {
						$projectid = GetVariable("id");
					}
					
					if ($projectid == "") {
						?><a href="projects.php" style="background-color:#273f70">Project List</a><?
					} 
					else {
						$projectid = mysqli_real_escape_string($GLOBALS['linki'], $projectid);
						$sqlstring = "select * from projects where project_id = $projectid";
						$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
						$name = $row['project_name'];
						
						?><a href="projects.php" style="<?=$style?>">Project List</a> <?
						
						if (($page == "projects.php") && ($action == "" || $action == "displayprojectinfo")) { $style = "background-color:#273f70"; }
						else { $style = ""; }
						?><b><a href="projects.php?action=displayprojectinfo&id=<?=$projectid?>" style="<?=$style?>"><?=$name?></a></b><?
						
						if ($page=="datadictionary.php"){ $style = "background-color:#273f70"; }
						else { $style = ""; }
						?><a href="datadictionary.php?projectid=<?=$projectid?>" style="<?=$style?>">Data Dictionary</a><?
						
						if ($page=="projectassessments.php"){ $style = "background-color:#273f70"; }
						else { $style = ""; }
						?><a href="projectassessments.php?projectid=<?=$projectid?>" style="<?=$style?>">Assessments</a><?
						
						if (($page=="projects.php") && ($action == "editsubjects")) { $style = "background-color:#273f70"; }
						else { $style = ""; }
						?><a href="projects.php?action=editsubjects&id=<?=$projectid?>" style="<?=$style?>">Subjects</a><?
						
						if (($page=="projects.php") && ($action == "displaystudies")) { $style = "background-color:#273f70"; }
						else { $style = ""; }
						?><a href="projects.php?action=displaystudies&id=<?=$projectid?>" style="<?=$style?>">Studies</a><?
						
						if ($page=="projectchecklist.php"){ $style = "background-color:#273f70"; }
						else { $style = ""; }
						?><a href="projectchecklist.php?projectid=<?=$projectid?>" style="<?=$style?>">Checklist</a><?
						
						if ($page=="mrqcchecklist.php"){ $style = "background-color:#273f70"; }
						else { $style = ""; }
						?><a href="mrqcchecklist.php?action=viewqcparams&id=<?=$projectid?>" style="<?=$style?>">MR Scan QC</a><?

						if ($page=="minipipeline.php"){ $style = "background-color:#273f70"; }
						else { $style = ""; }
						?><a href="minipipeline.php?projectid=<?=$projectid?>" style="<?=$style?>">Behavioral pipelines</a><?
						
						if ($page=="templates.php"){ $style = "background-color:#273f70"; }
						else { $style = ""; }
						?><a href="templates.php?projectid=<?=$projectid?>" style="<?=$style?>">Templates</a><?
					}
				}
				
				/* pipelines sub-menu */
				elseif ($page=="pipelines.php" || $page=="analysis.php" || $page == "cluster.php") {
					if ($GLOBALS['cfg']['enablepipelines']) {
						$pipelineid = GetVariable("id");
						if ($pipelineid == "") {
							?><a href="pipelines.php" style="background-color: #273f70">Pipeline List</a><?
							?><a href="pipelines.php?action=addform">New Pipeline</a><?
							
							if ($page == "cluster.php") { $style = "background-color:#273f70"; }
							?><a href="cluster.php" style="<?=$style?>">Cluster</a><?
						} 
						else {
							$sqlstring = "select a.*, b.username from pipelines a left join users b on a.pipeline_admin = b.user_id where a.pipeline_id = $pipelineid";
							$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
							$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
							$name = $row['pipeline_name'];

							?><a href="pipelines.php" style="">Pipeline List</a> <span style="color:#fff">&gt;</span> <?
							
							if (($page=="pipelines.php") && ($action == "editpipeline")) { $style = "background-color:#273f70"; }
							else { $style = ""; }
							?><a href="pipelines.php?action=editpipeline&id=<?=$pipelineid?>" style="<?=$style?>"><?=$name?></a> <span style="color:#fff">&gt;</span><?

							if ($page=="analysis.php"){ $style = "background-color:#273f70"; }
							else { $style = ""; }
							?><a href="analysis.php?action=viewanalyses&id=<?=$pipelineid?>" style="<?=$style?>">Analyses</a><?
						}
					}
				}
				
				/* data sub-menu */
				elseif ($page=="import.php" || $page=="importimaging.php" || $page=="importlog.php" || $page=="publicdownloads.php" || $page=="downloads.php") {
					
					if ($GLOBALS['cfg']['enabledatamenu']) {
						if (($page == "import.php") && ($action != "idmapper")) { $style = "background-color:#273f70"; }
						else { $style = ""; }
						?><a href="import.php" style="<?=$style?>">Import</a><?

						if ($page == "importimaging.php") { $style = "background-color:#273f70"; }
						else { $style = ""; }
						?><a href="importimaging.php" style="<?=$style?>">Import Imaging</a><?
						
						if (($page == "import.php") && ($action == "idmapper")) { $style = "background-color:#273f70"; }
						else { $style = ""; }
						?><a href="import.php?action=idmapper" style="<?=$style?>">ID mapper</a><?

						if ($page == "importlog.php") { $style = "background-color:#273f70"; }
						else { $style = ""; }
						?><a href="importlog.php" style="<?=$style?>">Import Log</a><?

						if ($page == "publicdownloads.php") { $style = "background-color:#273f70"; }
						else { $style = ""; }
						?><a href="publicdownloads.php" style="<?=$style?>">Public Downloads</a><?

						if ($page == "downloads.php") { $style = "background-color:#273f70"; }
						else { $style = ""; }
						?><a href="downloads.php" style="<?=$style?>">Downloads</a><?
					}
					
				}
				
				/* calendar sub-menu */
				elseif ($page=="calendar.php" || $page=="calendar_calendars.php") {
					if ($GLOBALS['cfg']['enablecalendar']) {
						if ($page=="calendar.php") { $style = "background-color:#273f70"; }
						else { $style = ""; }
						?><a href="calendar.php" style="<?=$style?>">Calendar</a><?
						
						if ($GLOBALS['isadmin']) {
							if ($page=="calendar_calendars.php") { $style = "background-color:#273f70"; }
							else { $style = ""; }
							?><a href="calendar_calendars.php" style="<?=$style?>">Manage</a><?
						}
					}
				}
				
				/* admin sub-menu. any pages starting with 'admin' */
				elseif ((substr($page,0,5) == "admin") || ($page == "system.php") || ($page == "status.php") || ($page == "reports.php") || ($page == "cleanup.php") || ($page == "stats.php") || ($page == "status.php") || ($page == "longqc.php")) {
					if ($page=="admin.php") { $style = "background-color:#273f70"; }
					else { $style = ""; }
					?><a href="admin.php" style="<?=$style?>">Admin</a><?
					
					if ($page=="adminmodules.php") { $style = "background-color:#273f70"; }
					else { $style = ""; }
					?><a href="adminmodules.php" style="<?=$style?>">Modules</a><?
					
					if ($page=="system.php") { $style = "background-color:#273f70"; }
					else { $style = ""; }
					?><a href="system.php" style="<?=$style?>">Settings...</a><?
				}
				
				/* user options sub-menu */
				elseif ($page=="users.php" || $page=="remoteconnections.php" || $page == "filesio.php") {
					
					if ($page=="users.php") { $style = "background-color:#273f70"; }
					else { $style = ""; }
					?><a href="users.php" style="<?=$style?>">My Account</a><?
					
					if ($page=="remoteconnections.php") { $style = "background-color:#273f70"; }
					else { $style = ""; }
					?><a href="remoteconnections.php" style="<?=$style?>">Remote Connections</a><?
					
					if ($page=="filesio.php") { $style = "background-color:#273f70"; }
					else { $style = ""; }
					?><a href="filesio.php" style="<?=$style?>">File IO</a><?
				}
			?>
			</td>
			<td style="background-color: #3B5998">
			</td>
		</tr>
	</table>

<table width="100%" cellspacing="0" cellpadding="0">
	<tr>
		<td><?=RunSystemChecks()?></td>
		<td align="right" valign="top">
			<form action="subjects.php" method="post" style="margin: 0px">
			<input type="hidden" name="action" value="search">
			<input type="hidden" name="searchactive" value="1">
			<input placeholder="Search by UID" name="searchuid" type="search" size="17" autocomplete="on" style="background-color: white; color: black; border: 1px solid #526FAA">
			<input type="submit" style="display:none; width: 0px height: 0px">
			</form>
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
		DisplayErrorMessage("Error", "You POSTed " . count($_POST, COUNT_RECURSIVE). " variables, but your server's PHP limit is " . ini_get("max_input_vars") . ". Truncation of the submitted form may have occured.<br>
		Contact your server administrator or increase the <code>max_input_vars</code> PHP variable.");
	}
	
	if ($_SESSION['username'] == "") {
		DisplayErrorMessage("Error", "Username was blank. You do not appear to be logged in. Please login with your username to access NiDB");
	}
	
?>