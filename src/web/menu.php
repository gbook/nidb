<?
 // ------------------------------------------------------------------------------
 // NiDB menu.php
 // Copyright (C) 2004 - 2021
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
<div class="ui inverted red attached segment">
	<b>Development server</b> - Use for testing only
</div>
<? } ?>

<!-- menu -->

<!-- ****************** top menu ****************** -->
<div class="ui attached inverted menu" style="!important; overflow: auto">
	<div class="item" style="background-color: <?=$GLOBALS['cfg']['sitecolor']?>">
		<?=$GLOBALS['cfg']['sitename']?>
	</div>
	<?
		/* home */
		?><a href="index.php" class="<? if ($page=="index.php") { echo "active"; } ?> item"><i class="home icon"></i>Home</a><?
		/* search */
		?><a href="search.php" class="<? if ($page=="search.php" || $page=="requeststatus.php" || $page=="analysisbuilder.php" || $page=="batchupload.php") { echo "active"; } ?> item">Search</a><?
		/* subjects */
		?><a href="subjects.php" class="<? if ($page=="subjects.php" || $page=="groups.php" || $page == "series") { echo "active"; } ?> item">Subjects</a><?
		/* projects */
		?><a href="projects.php" class="<? if ($page=="projects.php" || $page=="projectchecklist.php" || $page=="mrqcchecklist.php" || $page=="studies.php" || $page=="measures.php" || $page=="minipipeline.php" || $page=="templates.php") { echo "active"; } ?> item">Projects</a><?
		/* pipelines */
		if ($GLOBALS['cfg']['enablepipelines']) {
			?><a href="pipelines.php" class="<? if ($page=="pipelines.php" || $page=="analysis.php") { echo "active"; } ?> item">Pipelines</a><?
		}
		/* data */
		if ($GLOBALS['cfg']['enabledatamenu']) {
			?><a href="import.php" class="<? if ($page=="import.php" || $page=="importimaging.php" || $page=="importlog.php" || $page=="publicdownloads.php" || $page=="downloads.php" || $page=="datasetrequests.php") { echo "active"; } ?> item">Data</a><?
		}
		/* calendar */
		if ($GLOBALS['cfg']['enablecalendar']) {
			?><a href="calendar.php" class="<? if ($page=="calendar.php" || $page=="calendar_calendars.php") { echo "active"; } ?> item">Calendar</a><?
		}
	?>
	<div class="right menu">
		<?
			/* admin */
			if ($GLOBALS['isadmin']) {
				?><a href="admin.php" class="<? if ((substr($page,0,5) == "admin") || ($page == "system.php") || ($page == "status.php") || ($page == "reports.php") || ($page == "cleanup.php") || ($page == "stats.php") || ($page == "status.php") || ($page == "longqc.php") || ($page == "backup.php")) { echo "active"; } ?> item"><i class="cog icon"></i>Admin</a><?
			}
			/* user options */
			?><a href="users.php" class="<? if ($page=="users.php" || $page=="remoteconnections.php") { echo "active"; } ?> item">My Account</a><?
		?>
		<a href="login.php?action=logout" class="item">Logout <i class="sign out alternate icon inverted"></i></a>
	</div>	
</div>

<!-- ****************** bottom menu ****************** -->
<div class="ui bottom attached grey inverted menu">
	<div class="ui dropdown item">
		<span title="Switch instance..."><?=$_SESSION['instancename']?></span>
		<i class="dropdown icon"></i>
		<div class="menu">
			<?
				$sqlstring = "select * from instance where instance_id in (select instance_id from user_instance where user_id = (select user_id from users where username = '" . $GLOBALS['username'] . "')) order by instance_name";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$instance_id = $row['instance_id'];
					$instance_name = $row['instance_name'];
					?>
					<a class="item" href="index.php?action=switchinstance&instanceid=<?=$instance_id?>"><?=$instance_name?></a>
					<?
				}
			?>
		</div>
	</div>
	<?
		/* home sub-menu */
		if ($page=="index.php") {
			?>
			<!--<div class="item">Home</div>-->
			<?
		}
		
		/* search sub-menu */
		elseif ($page=="search.php" || $page=="requeststatus.php" || $page=="analysisbuilder.php" || $page=="batchupload.php") {
			?><a href="search.php" class="<? if ($page=="search.php" || $page=="batchupload.php"){ echo "active"; } ?> item">Search</a><?
			?><a href="requeststatus.php" class="<? if ($page=="requeststatus.php"){ echo "active"; } ?> item">Export Status</a><?
			?><a href="analysisbuilder.php" class="<? if ($page=="analysisbuilder.php"){ echo "active"; } ?> item">Analysis Builder</a><?
		}
		
		/* subjects sub-menu */
		elseif ($page=="subjects.php" || $page=="groups.php") {
			?><a href="subjects.php" class="<? if ($page=="subjects.php"){ echo "active"; } ?> item">Subjects</a><?
			?><a href="groups.php" class="<? if ($page=="groups.php"){ echo "active"; } ?> item">Groups</a><?
			?><a href="measures.php" class="<? if ($page=="measures.php"){ echo "active"; } ?> item">Measures</a><?
		}
		
		/* studies, which are displayed under the projects menu */
		elseif ($page == "studies.php" || $page=="measures.php" || $page == "managefiles.php" || $page == "series.php") {
			$studyid = GetVariable("id");
			$seriesid = GetVariable("seriesid");
			$modality = GetVariable("modality");
			$enrollmentid = GetVariable("enrollmentid");
			
			if ($studyid == "") {
				$studyid = GetVariable("studyid");
			}
			if (($seriesid != "") && ($modality != "")) {
				list($path1, $uid, $studynum, $studyid, $subjectid, $modality1, $studytype1, $studydatetime1, $enrollmentid1, $projectname, $projectid) = GetSeriesInfo($seriesid, $modality);
				?>
				<a href="projects.php?action=displayprojectinfo&id=<?=$projectid?>" class="item"><?=$projectname?></a>
				<a href="subjects.php?id=<?=$subjectid?>" class="item"><?=$uid?></a>
				<a href="studies.php?id=<?=$studyid?>" class="active item">Study <?=$studynum?></a>
				<?
			}
			elseif ($studyid != "") {
				list($path1, $uid, $studynum, $studyid, $subjectid, $modality1, $studytype1, $studydatetime1, $enrollmentid1, $projectname, $projectid) = GetStudyInfo($studyid);
				?>
				<a href="projects.php?action=displayprojectinfo&id=<?=$projectid?>" class="item"><?=$projectname?></a>
				<a href="subjects.php?id=<?=$subjectid?>" class="item"><?=$uid?></a>
				<a href="studies.php?id=<?=$studyid?>" class="active item">Study <?=$studynum?></a>
				<?
			}
			elseif (($enrollmentid != "") && ($page == "measures.php")) {
				list($uid, $subjectid, $projectname, $projectid) = GetEnrollmentInfo($enrollmentid);
				?>
				<a href="projects.php?action=displayprojectinfo&id=<?=$projectid?>" class="item"><?=$projectname?></a>
				<a href="subjects.php?id=<?=$subjectid?>" class="item"><?=$uid?></a>
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
				?><!--<a href="projects.php" style="background-color:#273f70" class="item">Project List</a>--><?
			} 
			else {
				$projectid = mysqli_real_escape_string($GLOBALS['linki'], $projectid);
				$sqlstring = "select * from projects where project_id = $projectid";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
				$name = $row['project_name'];
				
				?><a href="projects.php" class="<?  ?> item">Project List</a> <?
				?><a href="projects.php?action=displayprojectinfo&id=<?=$projectid?>" class="<? if (($page == "projects.php") && ($action == "" || $action == "displayprojectinfo")) { echo "active"; } ?> item"><?=$name?></b><?
				?><a href="datadictionary.php?projectid=<?=$projectid?>" class="<? if ($page=="datadictionary.php"){ echo "active"; } ?> item">Data Dictionary</a><?
				?><a href="projectassessments.php?projectid=<?=$projectid?>" class="<? if ($page=="projectassessments.php"){ echo "active"; } ?> item">Assessments</a><?
				?><a href="projects.php?action=editsubjects&id=<?=$projectid?>" class="<? if (($page=="projects.php") && ($action == "editsubjects")) { echo "active"; } ?> item">Subjects</a><?
				?><a href="projects.php?action=displaystudies&id=<?=$projectid?>" class="<? if (($page=="projects.php") && ($action == "displaystudies")) { echo "active"; } ?> item">Studies</a><?
				?><a href="projectchecklist.php?projectid=<?=$projectid?>" class="<? if ($page=="projectchecklist.php"){ echo "active"; } ?> item">Checklist</a><?
				?><a href="mrqcchecklist.php?action=viewqcparams&id=<?=$projectid?>" class="<? if ($page=="mrqcchecklist.php"){ echo "active"; } ?> item">MR Scan QC</a><?
				?><a href="minipipeline.php?projectid=<?=$projectid?>" class="<? if ($page=="minipipeline.php"){ echo "active"; } ?> item">Behavioral pipelines</a><?
				?><a href="templates.php?projectid=<?=$projectid?>" class="<? if ($page=="templates.php"){ echo "active"; } ?> item">Templates</a><?
			}
		}
		
		/* pipelines sub-menu */
		elseif ($page=="pipelines.php" || $page=="analysis.php" || $page == "cluster.php") {
			if ($GLOBALS['cfg']['enablepipelines']) {
				$pipelineid = GetVariable("id");
				if ($pipelineid == "") {
					?><a href="pipelines.php" class="item">Pipeline List</a><?
					?><a href="pipelines.php?action=addform" class="red item">New Pipeline</a><?
					?><a href="cluster.php" class="<? if ($page == "cluster.php") { echo "active"; } ?> item">Cluster</a><?
				} 
				else {
					$sqlstring = "select a.*, b.username from pipelines a left join users b on a.pipeline_admin = b.user_id where a.pipeline_id = $pipelineid";
					$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
					$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
					$name = $row['pipeline_name'];

					?><a href="pipelines.php" class="item">Pipelines</a><?
					
					?><a href="pipelines.php?action=editpipeline&id=<?=$pipelineid?>" class="<? if (($page=="pipelines.php") && ($action == "editpipeline")) { echo "active"; } ?> item"><?=$name?></a><?

					?><a href="analysis.php?action=viewanalyses&id=<?=$pipelineid?>" class="<? if ($page=="analysis.php"){ echo "active"; } ?> item">Analyses</a><?
				}
			}
		}
		
		/* data sub-menu */
		elseif ($page=="import.php" || $page=="importimaging.php" || $page=="importlog.php" || $page=="publicdownloads.php" || $page=="downloads.php" || $page=="datasetrequests.php") {
			
			if ($GLOBALS['cfg']['enabledatamenu']) {
				?><a href="import.php" class="<? if (($page == "import.php") && ($action != "idmapper")) { echo "active"; } ?> item">Import</a><?
				?><a href="importimaging.php" class="<? if ($page == "importimaging.php") { echo "active"; } ?> item">Import Imaging</a><?
				?><a href="import.php?action=idmapper" class="<? if (($page == "import.php") && ($action == "idmapper")) { echo "active"; } ?> item">ID mapper</a><?
				?><a href="importlog.php" class="<? if ($page == "importlog.php") { echo "active"; } ?> item">Import Log</a><?
				?><a href="publicdownloads.php" class="<? if ($page == "publicdownloads.php") { echo "active"; } ?> item">Public Downloads</a><?
				?><a href="downloads.php" class="<? if ($page == "downloads.php") { echo "active"; } ?> item">Downloads</a><?
				?><a href="datasetrequests.php" class="<? if ($page == "datasetrequests.php") { echo "active"; } ?> item">Request a Dataset</a><?
			}
			
		}
		
		/* calendar sub-menu */
		elseif ($page=="calendar.php" || $page=="calendar_calendars.php") {
			if ($GLOBALS['cfg']['enablecalendar']) {
				?><a href="calendar.php" class="<? if ($page=="calendar.php") { echo "active"; } ?> item">Calendar</a><?
				
				if ($GLOBALS['isadmin']) {
					?><a href="calendar_calendars.php" class="<? if ($page=="calendar_calendars.php") { echo "active"; } ?> item">Manage</a><?
				}
			}
		}
		
		/* admin sub-menu. any pages starting with 'admin' */
		elseif ((substr($page,0,5) == "admin") || ($page == "system.php") || ($page == "status.php") || ($page == "reports.php") || ($page == "cleanup.php") || ($page == "stats.php") || ($page == "status.php") || ($page == "longqc.php") || ($page == "backup.php")) {
			?><a href="admin.php" class="<? if ($page=="admin.php") { echo "active"; } ?> item"><i class="cog icon"></i> Admin</a><?
			?><a href="adminmodules.php" class="<? if ($page=="adminmodules.php") { echo "active"; } ?> item">Modules</a><?
			?><a href="system.php" class="<? if ($page=="system.php") { echo "active"; } ?> item">Settings...</a><?
		}
		
		/* user options sub-menu */
		elseif ($page=="users.php" || $page=="remoteconnections.php" || $page == "filesio.php") {
			?><a href="users.php" class="<? if ($page=="users.php") { echo "active"; } ?> item">My Account</a><?
			?><a href="remoteconnections.php" class="<? if ($page=="remoteconnections.php") { echo "active"; } ?> item">Remote Connections</a><?
			?><a href="filesio.php" class="<? if ($page=="filesio.php") { echo "active"; } ?> item">File IO</a><?
		}
	?>
	<div class="right menu">
		<div class="vertically fitted item">
			<form action="subjects.php" method="post" style="margin: 0px;">
			<input type="hidden" name="action" value="search">
			<input type="hidden" name="searchactive" value="1">
			<div class="ui small action input">
				<input name="searchuid" type="text" placeholder="Search by UID...">
				<button class="ui icon button"><i class="search icon"></i></button>
			</div>
			</form>
		</div>
	</div>
</div>
<?=RunSystemChecks()?>

<?
/* check for system status messages */
	$sqlstring = "select * from system_messages where message_status = 'active' order by message_date desc";
	$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	if (mysqli_num_rows($result) > 0) {
	?>
	<div class="ui negative message">
		<i class="close icon"></i>
		<div class="header">System Messages</div>
		<ul>
		<?
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$message = $row['message'];
			$message_date = $row['message_date'];
		?>
		<li><?=$message_date?> - <?=$message?>
		<? } ?>
		</ul>
	</div>
	<? }?>

<div class="ui grid" id="mainPageGrid">
	<div class="sixteen wide column" style="margin: 15px; overflow-x: auto">
			<!--  begin main page content -->
<?			
	if (count($_POST, COUNT_RECURSIVE) >= ini_get("max_input_vars")) {
		Error("Error", "You POSTed " . count($_POST, COUNT_RECURSIVE). " variables, but your server's PHP limit is " . ini_get("max_input_vars") . ". Truncation of the submitted form may have occured.<br>
		Contact your server administrator or increase the <code>max_input_vars</code> PHP variable.");
	}
	
	if ($_SESSION['username'] == "") {
		Error("Error", "Username was blank. You do not appear to be logged in. Please login with your username to access NiDB");
	}
	
?>