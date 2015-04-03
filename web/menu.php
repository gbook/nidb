<?
 // ------------------------------------------------------------------------------
 // NiDB menu.php
 // Copyright (C) 2004 - 2014
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
					$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
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
		<td valign="top" style="background-color: 526faa; border-radius:5px; padding:5px">
			<div id="bluemenu" class="bluetabs">
				<ul>
					<li><span><a href="index.php">Home</a></span></li>
					<li><a href="subjects.php" rel="subjects_menu">Subjects</a></li>
					<li><a href="search.php" rel="search_menu"><b>Search</b></a></li>
					<li><a href="projects.php">Projects</a></li>
					<li><a href="pipelines.php" rel="analysis_menu">Analysis</a></li>
					<li><a href="import.php">Import</a></li>
					<? if ($GLOBALS['isadmin']) { ?>
					<li><a href="admin.php" rel="admin_menu">Admin</a></li>
					<? } ?>
					<li><a href="users.php" style="padding-left:50px" rel="user_menu"><?=$username?></a>
				</ul>
			</div>

			<div id="analysis_menu" class="dropmenudiv_b">
				<a href="pipelines.php">Pipelines</a>
				<a href="csprefs.php">CenterScripts</a>
				<a href="common.php">Common objects</a>
				<a href="cluster.php">Cluster Stats</a>
			</div>
			
			<div id="subjects_menu" class="dropmenudiv_b">
				<a href="groups.php">Groups</a>
				<br><span style="color: #CCC">Most recent subjects</span>
				<?
				$sqlstring = "select a.mostrecent_date, a.subject_id, b.uid from mostrecent a left join subjects b on a.subject_id = b.subject_id where a.user_id in (select user_id from users where username = '$username') and a.subject_id is not null order by a.mostrecent_date desc";
				$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
				if (mysql_num_rows($result) > 0) {
					while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
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
				$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
				if (mysql_num_rows($result) > 0) {
					while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
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
			</div>
			<? } ?>
			
			<div id="user_menu" class="dropmenudiv_b">
				<a href="users.php">My Account</a>
				<a href="login.php?action=logout">Logout</a>
			</div>
		</td>
		<script type="text/javascript">
		tabdropdown.init("bluemenu")
		</script>

		<form action="subjects.php" method="post">
		<input type="hidden" name="action" value="search">
		<input type="hidden" name="searchactive" value="1">
		<td align="right" valign="bottom">
			<input placeholder="UID search" name="searchuid" type="text" size="9" style="background-color: #526FAA; color: white; border:1px solid #526FAA">
		</td>
		<input type="submit" style="display:none">
		</form>
	</tr>
</table>
<br>
<table width="100%" cellpadding="5">
	<tr>
		<td width="100%">
			<!--  begin main page content -->