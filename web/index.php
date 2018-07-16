<?
 // ------------------------------------------------------------------------------
 // NiDB index.php
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

	session_start();
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title><?=$_SERVER['HTTP_HOST']?> - NiDB</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes.php";
	
	/* ----- setup variables ----- */
	$vars['action'] = GetVariable("action");
	$vars['instanceid'] = GetVariable("instanceid");

	switch ($vars['action']) {
		case 'switchinstance':
			SwitchInstance($vars['instanceid']);
			break;
	}
	
	/* put this here so that the instance ID can be changed before displaying things */
	require "menu.php";
	
	/* -------------------------------------------- */
	/* ------- SwitchInstance --------------------- */
	/* -------------------------------------------- */
	function SwitchInstance($id) {
		$sqlstring = "select instance_name from instance where instance_id = '$id'";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$instancename = $row['instance_name'];
		
		$_SESSION['instanceid'] = $id;
		$_SESSION['instancename'] = $instancename;
		//echo "Switched instance to $id";
	}
?>

<script>
	$(function() {
		$( document ).tooltip({show:{effect:'appear'}, hide:{duration:0}});
	});
</script>

<?
$sqlstring = "select user_email, user_logincount from users where username = '" . $GLOBALS['username'] . "'";
//PrintSQL($sqlstring);
$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
$email = $row['user_email'];
$logincount = $row['user_logincount'];
if ($email == "") {
?>
<div style="background-color: #e45a48; color: white; padding:10px">Your email address is currently blank. Please <a href="users.php">update</a> your email address. Thank you!
</div><br>
<? } ?>
<details <? if (($logincount < 10) && ($logincount > 0) && ($logincount != '')) { echo "open"; }?>>
<summary style="font-size:9pt" title="Click here to view things to do on NiDB">Welcome!</summary>
<div style="background-color: #ffe7ce; color: black; padding:0px; border: 1px solid #E45A48; border-radius: 3px">
<div align="center" style="background-color: #E45A48; color: white; font-size:14pt; padding:5px">Thank you for joining <b><?=$GLOBALS['cfg']['sitename']?></b></div>
<div style="padding:15px; column-width: 200px; column-count: 3;">
<a href="users.php">Join</a> an instance<br>
<a href="import.php?action=import">Import</a> data via the website<br>
<a href="import.php?action=downloads">Download</a> the uploader<br>
<a href="import.php?action=idmapper">Map</a> foreign IDs to NiDB UIDs<br>
<a href="search.php">Search</a> for data<br>
<a href="projects.php">Browse</a> data by project<br>
</div>
</div>
</details>
<br>

<table width="100%">
	<tr>
		<td valign="top" width="50%">
			<img src="images/nidb_short_notext_small.png">
			<br><br>
			<table class="graydisplaytable" width="90%">
				<tr>
					<th colspan="7">
						<b>New Imaging Studies</b><br>
						<span class="sublabel">Collected in past 72 hours</span>
					</th>
				</tr>
				<tr>
					<td class="subheader">Subject UID</td>
					<td class="subheader">Study #</td>
					<td class="subheader">Date</td>
					<td class="subheader">Modality</td>
					<td class="subheader"># of Series</td>
					<td class="subheader">Site</td>
					<td class="subheader">Project</td>
				</tr>
			<?
				$sqlstring = "select a.*, c.*, d.uid, d.subject_id, f.family_uid from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join projects c on b.project_id = c.project_id left join subjects d on d.subject_id = b.subject_id left join family_members e on d.subject_id = e.subject_id left join families f on e.family_id = f.family_id where d.isactive = 1 and (a.study_datetime > now() - interval 72 hour) and a.study_datetime <= now() and b.project_id in (select project_id from projects where instance_id = '" . $_SESSION['instanceid'] . "') and a.study_modality <> '' order by a.study_datetime desc";
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$study_id = $row['study_id'];
					$subject_id = $row['subject_id'];
					$modality = $row['study_modality'];
					$study_datetime = date('M j g:ia',strtotime($row['study_datetime']));
					$study_site = $row['study_site'];
					$study_num = $row['study_num'];
					$uid = $row['uid'];
					$project_name = $row['project_name'];
					$project_costcenter = $row['project_costcenter'];
					$familyuid = $row['family_uid'];
					
					$sqlstringA = "select count(*) 'seriescount' from " . strtolower($modality) . "_series where study_id = $study_id";
					$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
					$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
					$seriescount = $rowA['seriescount'];
					?>
					<tr>
						<td title="Family UID: <b><?=$familyuid?></b>"><a href="subjects.php?id=<?=$subject_id?>"><?=$uid;?></a></td>
						<td><a href="studies.php?id=<?=$study_id?>"><?=$study_num;?></a></td>
						<td style="font-size:8pt; white-space: nowrap"><?=$study_datetime?></td>
						<td style="font-size:8pt"><?=$modality?></td>
						<td style="font-size:8pt"><?=$seriescount?></td>
						<td style="font-size:8pt"><?=$study_site?></td>
						<td style="font-size:8pt"><?=$project_name?> (<?=$project_costcenter?>)</td>
					</tr>
					<?
				}
			?>
			</table>
		</td>
		<td valign="top">
			<table class="graydisplaytable" width="90%">
				<tr>
					<th colspan="4">Recently Viewed Subjects</th>
				</tr>
				<tr>
					<td class="subheader">UID</td>
					<td class="subheader">Sex</td>
					<td class="subheader">DOB</td>
					<td class="subheader"><span class="tiny">Date Accessed</span></td>
				</tr>
				<?
				$sqlstring = "select a.mostrecent_date, a.subject_id, b.* from mostrecent a left join subjects b on a.subject_id = b.subject_id left join enrollment c on b.subject_id = c.subject_id where a.user_id in (select user_id from users where username = '$username') and a.subject_id is not null and c.project_id in (select project_id from projects where instance_id = '" . $_SESSION['instanceid'] . "') group by b.uid order by a.mostrecent_date desc";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$subjectid = $row['subject_id'];
						$date = date('M j g:ia',strtotime($row['mostrecent_date']));
						$dob = date('Y-m-d',strtotime($row['birthdate']));
						$uid = $row['uid'];
						$sex = $row['gender'];
						?>
						<tr>
							<td><a href="subjects.php?id=<?=$subjectid?>"><?=$uid?></a></td>
							<td><?=$sex?></td>
							<td><?=$dob?></td>
							<td><span class="tiny"><?=$date?></span></td>
						</tr>
						<?
					}
				}
				?>
			</table>
			<br>
			<table class="graydisplaytable" width="90%">
				<tr>
					<th colspan="6">Recently Viewed Imaging studies</th>
				</tr>
				<tr>
					<td class="subheader">UID-Number</td>
					<td class="subheader">Date</td>
					<td class="subheader">Modality</td>
					<td class="subheader">Site</td>
					<td class="subheader"><span class="tiny">Date Accessed</span></td>
				</tr>
				<?
				$sqlstring = "select a.mostrecent_date, a.study_id, b.*, d.uid from mostrecent a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on d.subject_id = c.subject_id where a.user_id in (select user_id from users where username = '$username') and a.study_id is not null and c.project_id in (select project_id from projects where instance_id = '" . $_SESSION['instanceid'] . "') order by a.mostrecent_date desc";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$studyid = $row['study_id'];
						$studynum = $row['study_num'];
						$date = date('M j g:ia',strtotime($row['mostrecent_date']));
						$uid = $row['uid'];
						$studydate = $row['study_datetime'];
						$modality = $row['study_modality'];
						$site = $row['study_site'];
						?>
						<tr>
							<td><a href="studies.php?id=<?=$studyid?>"><?=$uid?><?=$studynum?></a></td>
							<td><?=$studydate?></td>
							<td><?=$modality?></td>
							<td><?=$site?></td>
							<td><span class="tiny"><?=$date?></span></td>
						</tr>
						<?
					}
				}
				?>
			</table>
		</td>
	</tr>
</table>

<? include("footer.php") ?>
<? ob_end_flush(); ?>
