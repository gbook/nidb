<?
 // ------------------------------------------------------------------------------
 // NiDB menu.php
 // Copyright (C) 2004 - 2026
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

<!-- menu -->

<!-- ****************** top menu ****************** -->
<div class="ui attached inverted menu" style="!important; overflow: auto">
	<div class="item" style="background-color: <?=htmlspecialchars($GLOBALS['cfg']['sitecolor'] ?? '')?>">
		<?=htmlspecialchars($GLOBALS['cfg']['sitename'] ?? '')?>
	</div>
	<?
		/* home */
		?><a href="index.php" class="<? if ($page=="index.php") { echo "active"; } ?> item"><i class="home icon"></i>Home</a><?
		/* search */
		?><a href="search.php" class="<? if ($page=="search.php" || $page=="requeststatus.php" || $page=="analysisbuilder.php" || $page=="batchupload.php") { echo "active"; } ?> item">Search</a><?
		/* subjects */
		?><a href="subjects.php" class="<? if ($page=="subjects.php" || $page=="groups.php" || $page == "series") { echo "active"; } ?> item">Subjects</a><?
		/* projects */
		?><a href="projects.php" class="<? if ($page=="projects.php" || $page=="projectchecklist.php" || $page=="projectusage.php" || $page=="nonimaging.php" || $page=="mrqcchecklist.php" || $page=="studies.php" || $page=="observations.php" || $page=="minipipeline.php" || $page=="templates.php" || $page == "experiment.php" || $page == "mriqc.php" || $page == "nda.php" || $page == "checklist.php") { echo "active"; } ?> item">Projects</a><?
		/* pipelines */
		if ($GLOBALS['cfg']['enablepipelines']) {
			?><a href="pipelines.php" class="<? if ($page=="pipelines.php" || $page=="analysis.php") { echo "active"; } ?> item">Pipelines</a><?
		}
		/* data */
		if ($GLOBALS['cfg']['enabledatamenu']) {
			?><a href="import.php" class="<? if ($page=="import.php" || $page=="importimaging.php" || $page=="importnonimaging.php" || $page=="importwizard.php" || $page=="importlog.php" || $page=="publicdownloads.php" || $page=="downloads.php" || $page=="datasetrequests.php") { echo "active"; } ?> item">Data</a><?
		}
		/* calendar */
		if ($GLOBALS['cfg']['enablecalendar']) {
			?><a href="calendar.php" class="<? if ($page=="calendar.php" || $page=="calendar_calendars.php") { echo "active"; } ?> item">Calendar</a><?
		}
	?>
	<div class="right menu">
		<?
			/* admin: admins and site admins (the same check admin.php makes) */
			if (IsAdminUser()) {
				?><a href="admin.php" class="<? if ((substr($page,0,5) == "admin") || ($page == "settings.php") || ($page == "status.php") || ($page == "reports.php") || ($page == "cleanup.php") || ($page == "stats.php") || ($page == "status.php") || ($page == "longqc.php") || ($page == "backup.php")) { echo "active"; } ?> item"><i class="cog <? if (file_exists("/nidb/setup/dbupgrade")) { echo "yellow loading"; } ?> icon"></i>Admin</a><?
			}
			/* user options */
			?><a href="users.php" class="<? if ($page=="users.php" || $page=="remoteconnections.php") { echo "active"; } ?> item">My Account <div class="ui mini label"><?=htmlspecialchars($GLOBALS['username'] ?? '')?></div></a><?
		?>
		<!-- logout is a POST with the CSRF token (login.php refuses a GET), so another site can't log the user out -->
		<a href="#" class="item" onclick="document.getElementById('logoutform').submit(); return false;">Logout <i class="sign out alternate icon inverted"></i></a>
		<form id="logoutform" method="post" action="login.php" style="display: none">
			<input type="hidden" name="action" value="logout">
			<?=CSRFTokenField()?>
		</form>
	</div>	
</div>

<!-- ****************** bottom menu ****************** -->
<div class="ui bottom attached grey inverted menu">
	<div class="ui dropdown item">
		<span title="Switch instance..."><?=htmlspecialchars($_SESSION['instancename'] ?? '')?></span>
		<i class="dropdown icon"></i>
		<div class="menu">
			<?
				/* instances the user is a member of (a pending join request is not membership). Bound: N51 */
				$menuusername = (string)($GLOBALS['username'] ?? '');
				$sqlstring = "select instance_id, instance_name from instance where instance_id in (select instance_id from user_instance where (instance_joinrequest is null or instance_joinrequest = 0) and user_id = (select user_id from users where username = ?)) order by instance_name";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 's', $menuusername);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$menuusername]);
				mysqli_stmt_close($stmt);
				while ($result && ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))) {
					$instance_id = (int)$row['instance_id'];
					$instance_name = $row['instance_name'];
					/* switching changes session state, so it is a POST with the CSRF token (index.php) */
					?>
					<a class="item" href="#" onclick="var f = document.getElementById('switchinstanceform'); f.instanceid.value = '<?=$instance_id?>'; f.submit(); return false;"><?=htmlspecialchars($instance_name ?? '')?></a>
					<?
				}
			?>
		</div>
		<form id="switchinstanceform" method="post" action="index.php" style="display: none">
			<input type="hidden" name="action" value="switchinstance">
			<input type="hidden" name="instanceid" value="">
			<?=CSRFTokenField()?>
		</form>
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
			?><a href="observations.php" class="<? if ($page=="observations.php"){ echo "active"; } ?> item">Observations</a><?
			?><a href="interventions.php" class="<? if ($page=="interventions.php"){ echo "active"; } ?> item">Interventions</a><?
			?><a href="diagnosis.php" class="<? if ($page=="diagnosis.php"){ echo "active"; } ?> item">Diagnosis</a><?
		}
		
		/* studies, which are displayed under the projects menu */
		elseif ($page == "studies.php" || $page=="observations.php" || $page=="interventions.php" || $page=="diagnosis.php" || $page == "managefiles.php" || $page == "series.php" || $page == "dicom.php" || $page == "enrollment.php" || $page == "timeline.php") {
			$studyid = (int)GetVariable("id");
			$seriesid = (int)GetVariable("seriesid");
			$modality = GetVariable("modality");
			$enrollmentid = (int)GetVariable("enrollmentid");
			$experimentid = (int)GetVariable("experimentid");
			
			if ($studyid == 0) {
				$studyid = (int)GetVariable("studyid");
			}
			if (($seriesid != 0) && ($modality != "")) {
				list($path1, $uid, $studynum, $seriesnum, $seriesdesc, $imagetype, $seriessize, $numfiles, $studyid, $subjectid, $modality1, $studytype1, $studydatetime1, $enrollmentid1, $projectname, $projectid) = GetSeriesInfo($seriesid, $modality);
				?>
				<a href="projects.php?action=displayprojectinfo&id=<?=(int)$projectid?>" class="item"><?=htmlspecialchars($projectname ?? '')?></a>
				<a href="subjects.php?id=<?=(int)$subjectid?>" class="item"><?=htmlspecialchars($uid ?? '')?></a>
				<a href="studies.php?id=<?=(int)$studyid?>" class="active item">Study <?=htmlspecialchars($studynum ?? '')?></a>
				<?
			}
			elseif ($studyid != 0) {
				list($path1, $uid, $studynum, $studyid, $subjectid, $modality1, $studytype1, $studydatetime1, $enrollmentid1, $projectname, $projectid) = GetStudyInfo($studyid);
				?>
				<a href="projects.php?action=displayprojectinfo&id=<?=(int)$projectid?>" class="item"><?=htmlspecialchars($projectname ?? '')?></a>
				<a href="subjects.php?id=<?=(int)$subjectid?>" class="item"><?=htmlspecialchars($uid ?? '')?></a>
				<a href="studies.php?id=<?=(int)$studyid?>" class="active item">Study <?=htmlspecialchars($studynum ?? '')?></a>
				<?
			}
			elseif (($enrollmentid != 0) && (($page == "observations.php") || ($page == "interventions.php") || ($page == "enrollment.php") || ($page == "diagnosis.php") || ($page == "timeline.php"))) {
				list($uid, $subjectid, $altuid, $projectname, $projectid) = GetEnrollmentInfo($enrollmentid);
				?>
				<a href="projects.php?action=displayprojectinfo&id=<?=(int)$projectid?>" class="item"><?=htmlspecialchars($projectname ?? '')?></a>
				<a href="subjects.php?id=<?=(int)$subjectid?>" class="item"><?=htmlspecialchars($uid ?? '')?></a>
				<?
			}
		}
		
		/* projects sub-menu */
		elseif ($page=="projects.php" || $page=="projectchecklist.php" || $page=="projectusage.php" || $page=="nonimaging.php" || $page=="mrqcchecklist.php" || $page=="studies.php" || $page=="minipipeline.php" || $page=="templates.php" || $page=="datadictionary.php" || $page == "experiment.php" || $page == "mriqc.php" || $page == "importremote.php" || $page == "instruments.php" || $page == "remoteimportmapping.php" || $page == "nda.php" || $page == "checklist.php") {
			$projectid = (int)GetVariable("projectid");
			if ($projectid == 0)
				$projectid = (int)GetVariable("id");
			
			if ($projectid == 0) {
				?><!--<a href="projects.php" style="background-color:#273f70" class="item">Project List</a>--><?
			} 
			else {
				$sqlstring = "select project_name from projects where project_id = ?";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'i', $projectid);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
				$row = ($result) ? mysqli_fetch_array($result, MYSQLI_ASSOC) : null;
				mysqli_stmt_close($stmt);
				$name = $row['project_name'] ?? '';

				/* the project Admin items are for admins/site admins and this project's project admins */
				$menuperms = GetCurrentUserProjectPermissions(array($projectid));
				$menuprojectadmin = (IsAdminUser() || GetPerm($menuperms, 'projectadmin', $projectid));
				?>
				<a href="projects.php?action=displayprojectinfo&id=<?=$projectid?>" class="active blue item"><?=htmlspecialchars($name)?></a>
				<div class="ui dropdown item">
					<div class="text">View Data</div>
					<i class="dropdown icon"></i>
					<div class="menu">
						<a class="item" href="projects.php?action=editsubjects&id=<?=$projectid?>" style="color: #222"><i class="user friends icon"></i> Subjects</a>
						<a class="item" href="projects.php?action=displaystudies&id=<?=$projectid?>" style="color: #222"><i class="project diagram icon"></i> Studies</a>
						<a class="item" href="projects.php?action=displaynonimaging&id=<?=$projectid?>" style="color: #222"><i class="clipboard icon"></i> Non-imaging</a>
						<a class="item" title="Checklist of expected data items" href="projectchecklist.php?projectid=<?=$projectid?>" style="color: #222"><i class="clipboard list icon"></i> Checklist</a>
						<a class="item" href="mrqcchecklist.php?action=viewqcparams&id=<?=$projectid?>" style="color: #222"><i class="clipboard check icon"></i> MR scan QC</a>
						<a class="item" href="mriqc.php?action=viewmriqc&projectid=<?=$projectid?>" style="color: #222"><i class="clipboard check icon"></i> Advanced mriqc</a>
					</div>
				</div>
				<div class="ui dropdown item">
					<div class="text">Manage</div>
					<i class="dropdown icon"></i>
					<div class="menu">
						<a class="item" title="Manage instruments and expected observations" href="instruments.php?projectid=<?=$projectid?>" style="color: #222"><i class="flask icon"></i> Manage instruments</a>
						<a class="item" href="datadictionary.php?projectid=<?=$projectid?>" style="color: #222"><i class="database icon"></i> Data dictionary</a>
						<a class="item" href="analysisbuilder.php?action=viewanalysissummary&projectid=<?=$projectid?>" style="color: #222"><i class="list alternate outline icon"></i> Analysis builder</a>
						<a class="item" href="templates.php?action=displaystudytemplatelist&projectid=<?=$projectid?>" style="color: #222"><i class="clone outline icon"></i> Study templates</a>
						<a class="item" href="minipipeline.php?projectid=<?=$projectid?>" style="color: #222"><i class="cogs icon"></i> Mini-pipelines</a>
						<a class="item" href="experiment.php?projectid=<?=$projectid?>" style="color: #222"><i class="clipboard icon"></i> Experiments</a>
						<a class="item" href="projects.php?action=editexperimentmapping&id=<?=$projectid?>" style="color: #222"><i class="tasks icon"></i> Experiment &harr; protocol mapping</a>
						<a class="item" href="nda.php?projectid=<?=$projectid?>" style="color: #222"><i class="upload icon"></i> NDA</a>
					</div>
				</div>
				<div class="ui dropdown item">
					<div class="text">Import Data</div>
					<i class="dropdown icon"></i>
					<div class="menu">
						<a class="item" title="Map remote data source fields to NiDB instruments" href="remoteimportmapping.php?projectid=<?=$projectid?>" style="color: #222"><i class="exchange icon"></i> Remote import mapping</a>
						<a class="item" href="importremote.php?action=viewbatchimportlist&projectid=<?=$projectid?>" style="color: #222"><i class="cloud download anternate icon"></i> Remote batch imports</a>
						<a class="item" href="importimaging.php?action=newimportform&projectid=<?=$projectid?>" style="color: #222"><i class="file import icon"></i> Import imaging</a>
						<a class="item" href="importnonimaging.php?action=newimportform&projectid=<?=$projectid?>" style="color: #222"><i class="file import icon"></i> Import Non-imaging</a>
					</div>
				</div>

				<div class="ui dropdown item">
					<div class="text">Admin</div>
					<i class="dropdown icon"></i>
					<div class="menu">
						<? if ($menuprojectadmin) { ?>
							<? /* these reset QC for the whole project, so they are POST forms with the CSRF token */ ?>
							<form method="post" action="projects.php" class="item" style="margin: 0" onsubmit="return confirm('Reset basic QA for every MR series in this project?')"><input type="hidden" name="action" value="resetqa"><input type="hidden" name="id" value="<?=(int)$projectid?>"><?=CSRFTokenField()?><button type="submit" style="background: none; border: none; padding: 0; cursor: pointer; color: inherit; font: inherit"><i class="red sync icon"></i> Reset basic QA</button></form>
							<form method="post" action="projects.php" class="item" style="margin: 0" onsubmit="return confirm('Reset advanced mriqc for every MR series in this project?')"><input type="hidden" name="action" value="resetmriqc"><input type="hidden" name="id" value="<?=(int)$projectid?>"><?=CSRFTokenField()?><button type="submit" style="background: none; border: none; padding: 0; cursor: pointer; color: inherit; font: inherit"><i class="red sync icon"></i> Reset advanced mriqc</button></form>
							<a class="item" class="item" href="importremote.php?action=viewimports&projectid=<?=$projectid?>"><i class="cloud download alternate icon"></i> Remote import sources</a>
						<? } ?>
						<div class="item"><b>Remote connection params</b><br>
							Project ID: <?=(int)$projectid?><br>
							Instance ID: <?=(int)($_SESSION['instanceid'] ?? 0)?><br>
						</div>
					</div>
				</div>
				<?
			}
		}
		
		/* pipelines sub-menu */
		elseif ($page=="pipelines.php" || $page=="analysis.php" || $page == "cluster.php" || $page == "pipelinecontainers.php") {
			if ($GLOBALS['cfg']['enablepipelines']) {
				$pipelineid = (int)GetVariable("id");
				if ($pipelineid == 0) {
					?><a href="pipelines.php" class="item">Pipeline List</a><?
					?><a href="pipelines.php?action=addform" class="red item">New Pipeline</a><?
					?><a href="cluster.php" class="<? if ($page == "cluster.php") { echo "active"; } ?> item">Cluster</a><?
				} 
				else {
					$sqlstring = "select pipeline_name from pipelines where pipeline_id = ?";
					$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
					mysqli_stmt_bind_param($stmt, 'i', $pipelineid);
					$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$pipelineid]);
					$row = ($result) ? mysqli_fetch_array($result, MYSQLI_ASSOC) : null;
					mysqli_stmt_close($stmt);
					$name = $row['pipeline_name'] ?? '';

					?><a href="pipelines.php" class="item">Pipelines</a><?
					
					?><a href="pipelines.php?action=editpipeline&id=<?=$pipelineid?>" class="<? if (($page=="pipelines.php") && ($action == "editpipeline")) { echo "active"; } ?> item"><?=htmlspecialchars($name)?></a><?

					?><a href="analysis.php?action=viewanalyses&id=<?=$pipelineid?>" class="<? if ($page=="analysis.php"){ echo "active"; } ?> item">Analyses</a><?
				}
			}
		}
		
		/* data sub-menu */
		elseif ($page=="import.php" || $page=="importimaging.php" || $page=="importnonimaging.php" || $page=="importwizard.php" || $page=="importlog.php" || $page=="publicdownloads.php" || $page=="publicdatasets.php" || $page=="downloads.php" || $page=="datasetrequests.php" || $page=="packages.php") {
			
			if ($GLOBALS['cfg']['enabledatamenu']) {
				?><a href="import.php" class="<? if (($page == "import.php") && ($action != "idmapper")) { echo "active"; } ?> blue item">Import</a><?
				?><a href="packages.php" class="<? if ($page == "packages.php") { echo "active"; } ?> blue item">Packages</a><?
				?><a href="importimaging.php" class="<? if ($page == "importimaging.php") { echo "active"; } ?> blue item">Import Imaging</a><?
				?><a href="importnonimaging.php" class="<? if ($page == "importnonimaging.php") { echo "active"; } ?> blue item">Import Non-imaging</a><?
				?><a href="importwizard.php" class="<? if ($page == "importwizard.php") { echo "active"; } ?> blue item">Import Wizard</a><?
				?><a href="import.php?action=idmapper" class="<? if (($page == "import.php") && ($action == "idmapper")) { echo "active"; } ?> blue item">ID mapper</a><?
				?><a href="importlog.php" class="<? if ($page == "importlog.php") { echo "active"; } ?> blue item">Import Log</a><?
				?><a href="publicdownloads.php" class="<? if ($page == "publicdownloads.php") { echo "active"; } ?> blue item">Public Downloads</a><?
				?><a href="publicdatasets.php" class="<? if ($page == "publicdatasets.php") { echo "active"; } ?> blue item">Public Datasets</a><?
				?><a href="datasetrequests.php" class="<? if ($page == "datasetrequests.php") { echo "active"; } ?> blue item">Request a Dataset</a><?
			}
			
		}
		
		/* calendar sub-menu */
		elseif ($page=="calendar.php" || $page=="calendar_calendars.php") {
			if ($GLOBALS['cfg']['enablecalendar']) {
				?><a href="calendar.php" class="<? if ($page=="calendar.php") { echo "active"; } ?> item">Calendar</a><?
				
				if (IsAdminUser()) {
					?><a href="calendar_calendars.php" class="<? if ($page=="calendar_calendars.php") { echo "active"; } ?> item">Manage</a><?
				}
			}
		}
		
		/* admin sub-menu. any pages starting with 'admin' */
		elseif ((substr($page,0,5) == "admin") || ($page == "settings.php") || ($page == "status.php") || ($page == "reports.php") || ($page == "cleanup.php") || ($page == "stats.php") || ($page == "status.php") || ($page == "longqc.php") || ($page == "backup.php") || ($page == "pipelinesettings.php")) {
			?><a href="admin.php" class="<? if ($page=="admin.php") { echo "active"; } ?> item"><i class="cog icon"></i> Admin</a><?
			/* Modules and Settings are site-admin-only pages */
			if ($GLOBALS['issiteadmin'] ?? false) {
				?><a href="adminmodules.php" class="<? if ($page=="adminmodules.php") { echo "active"; } ?> item">Modules</a><?
				?><a href="settings.php" class="<? if ($page=="settings.php") { echo "active"; } ?> item">Settings...</a><?
			}
		}
		
		/* user options sub-menu */
		elseif ($page=="users.php" || $page=="remoteconnections.php" || $page == "filesio.php") {
			?><a href="users.php" class="<? if ($page=="users.php") { echo "active"; } ?> item">My Account</a><?
			?><a href="remoteconnections.php" class="<? if ($page=="remoteconnections.php") { echo "active"; } ?> item">Remote Connections</a><?
			?><a href="filesio.php" class="<? if ($page=="filesio.php") { echo "active"; } ?> item">File IO</a><?
		}
	?>
	<script>
	$(document).ready(function() {
		$('.ui.search')
		  .search({
			apiSettings: {
			  url: 'ajaxapi.php?action=searchsubject&uid={query}'
			},
			fields: {
			  results : 'results',
			  title   : 'title',
			  url     : 'url'
			},
			minCharacters : 2
		  })
		;
	});
	</script>
	
	<div class="right menu">
		<div class="vertically fitted item">
			<div class="ui search">
				<div class="ui left icon input">
					<input class="prompt" type="text" placeholder="Search by UID">
					<i class="search icon"></i>
				</div>
			</div>
		</div>
		<!--
		<div class="vertically fitted item">
			<form action="subjects.php" method="post" style="margin: 0px;">
			<input type="hidden" name="action" value="search">
			<input type="hidden" name="searchactive" value="1">
			<div class="ui search">
				<div class="ui small action input">
					<input name="searchuid" type="text" placeholder="Search by UID...">
					<button class="ui icon button"><i class="search icon"></i></button>
				</div>
			</div>
			</form>
		</div>
		-->
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
		<? /* the message itself is HTML by design: site admins write it in settings.php */ ?>
		<li><?=htmlspecialchars($message_date ?? '')?> - <?=$message?>
		<? } ?>
		</ul>
	</div>
	<? }?>

<div class="ui grid" id="mainPageGrid">
	<div class="sixteen wide column" style="margin: 15px; overflow: visible">
			<!--  begin main page content -->
<?			
	if (count($_POST, COUNT_RECURSIVE) >= ini_get("max_input_vars")) {
		Error("You POSTed " . count($_POST, COUNT_RECURSIVE). " variables, but your server's PHP limit is " . ini_get("max_input_vars") . ". Truncation of the submitted form may have occured.<br>
		Contact your server administrator or increase the <code>max_input_vars</code> PHP variable.");
	}
	
	if (($_SESSION['username'] ?? '') == "") {
		Error("Username was blank. You do not appear to be logged in. Please login with your username to access NiDB");
	}
	
?>