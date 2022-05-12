<?
 // ------------------------------------------------------------------------------
 // NiDB index.php
 // Copyright (C) 2004 - 2022
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

	define("LEGIT_REQUEST", true);
	
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
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	/* ----- setup variables ----- */
	$vars['action'] = GetVariable("action");
	$vars['instanceid'] = GetVariable("instanceid");

	switch ($vars['action']) {
		case 'switchinstance':
			SwitchInstance($vars['instanceid']);
			break;
	}
	
	/* put this here so that the instance ID can be changed before displaying things */
	//require "menu.php";
	
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
	}
	
	$q = mysqli_stmt_init($GLOBALS['linki']);
	mysqli_stmt_prepare($q, "select user_email, user_logincount from users where username = ?");
	mysqli_stmt_bind_param($q, 's', $GLOBALS['username']);
	$result = MySQLiBoundQuery($q, __FILE__, __LINE__);
	$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
	$email = $row['user_email'];
	$logincount = $row['user_logincount'];

	if ($email == "") {
		Notice("Your email address is currently blank. Please <a href='users.php'>update</a>.");
	}
	
	
	$sqlstring = "select count(*) count from subjects where isactive = 1";
	$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
	$numsubjects = $row['count'];

	$sqlstring = "select count(*) count from studies";
	$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
	$numstudies = $row['count'];

	$totalseries = 0;
	$totalsize = 0;
	$sqlstring = "show tables from " . $GLOBALS['cfg']['mysqldatabase'] . " like '%\_series'";
	$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
		$tablename = $row['Tables_in_' . $GLOBALS['cfg']['mysqldatabase'] . ' (%\_series)'];
		$parts = explode("_", $tablename);
		$modality = $parts[0];
		
		if (($modality != 'audit') && ($modality != 'upload')) {
			$sqlstring2 = "select count(*) 'count', sum(series_size) 'size' from $modality" . "_series";
			$result2 = MySQLiQuery($sqlstring2, __FILE__, __LINE__);
			$row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC);
			$totalseries += $row2['count'];
			$totalsize += $row2['size'];
		}
	}
	
?>
	<div class="ui two column grid">
		<div class="column">
			<div class="ui two column grid">
				<div class="column">
					<img class="ui image" src="images/NIDB_logo.png" width="300px">
					<div class="ui tiny statistics">
						<div class="statistic">
							<div class="value"><?=number_format($numsubjects)?></div>
							<div class="label">Subjects</div>
						</div>
						<div class="statistic">
							<div class="value"><?=number_format($numstudies)?></div>
							<div class="label">Studies</div>
						</div>
						<div class="statistic">
							<div class="value"><?=number_format($totalseries)?></div>
							<div class="label">Series</div>
						</div>
					</div>
				</div>
				<div class="column">
					<a class="ui primary big button" href="import.php"><i class="cloud upload icon"></i> Import</a> &nbsp; 
					<a class="ui primary big button" href="search.php"><i class="search icon"></i> Search / Export</a>
				</div>
			</div>
			<br><br>
			
			<?
				if ($GLOBALS['cfg']['displayrecentstudies']) {
					$numrecentdays = $GLOBALS['cfg']['displayrecentstudydays'];
			?>
			<div class="ui header">
				<div class="content">
					<i class="clock outline icon"></i> New Studies
					<div class="sub header">Imaging studies collected in past <?=$numrecentdays?> days</div>
				</div>
			</div>
			<table class="ui small celled selectable grey very compact table">
				<thead>
					<th class="subheader">Subject UID</th>
					<th class="subheader">Study #</th>
					<th class="subheader">Date</th>
					<th class="subheader">Modality</th>
					<th class="subheader">Site</th>
					<th class="subheader"># of Series</th>
					<th class="subheader">Project</th>
				</thead>
			<?
				$sqlstring = "select a.*, c.*, d.uid, d.subject_id, f.family_uid from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join projects c on b.project_id = c.project_id left join subjects d on d.subject_id = b.subject_id left join family_members e on d.subject_id = e.subject_id left join families f on e.family_id = f.family_id where d.isactive = 1 and (a.study_datetime > now() - interval $numrecentdays day) and a.study_datetime <= now() and b.project_id in (select project_id from projects where instance_id = '" . $_SESSION['instanceid'] . "') and a.study_modality <> '' order by a.study_datetime desc";
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
					$projectid = $row['project_id'];
					$project_costcenter = $row['project_costcenter'];
					$familyuid = $row['family_uid'];
					
					$perms = GetCurrentUserProjectPermissions(array($projectid));
					//PrintVariable($projectid);
					//PrintVariable($perms);
					if (GetPerm($perms, 'viewdata', $projectid)) {
						$sqlstringA = "select count(*) 'seriescount' from " . strtolower($modality) . "_series where study_id = $study_id";
						$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
						$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
						$seriescount = $rowA['seriescount'];
						?>
						<tr>
							<td class="tt"><a href="subjects.php?id=<?=$subject_id?>"><?=$uid;?></a></td>
							<td class="tt"><a href="studies.php?id=<?=$study_id?>"><?=$study_num;?></a></td>
							<td style="font-size:8pt; white-space: nowrap"><?=$study_datetime?></td>
							<td style="font-size:8pt"><?=$modality?></td>
							<td style="font-size:8pt"><?=$study_site?></td>
							<td style="font-size:8pt"><?=$seriescount?></td>
							<td style="font-size:8pt"><?=$project_name?> (<?=$project_costcenter?>)</td>
						</tr>
						<?
					}
					else {
						?>
						<tr>
							<td class="tt"><a href="subjects.php?id=<?=$subject_id?>"><?=$uid;?></a></td>
							<td class="tt"><a href="studies.php?id=<?=$study_id?>"><?=$study_num;?></a></td>
							<td style="font-size:8pt; white-space: nowrap"><?=$study_datetime?></td>
							<td style="font-size:8pt"><?=$modality?></td>
							<td style="font-size:8pt"><?=$study_site?></td>
							<td style="font-size:8pt" colspan="2">No permissions for <?=$project_name?> (<?=$project_costcenter?>)</td>
						</tr>
						<?
					}
				}
			?>
			</table>
			<?
				}
			?>
		</div>
		<div class="column">
			<h2 class="ui header">
				<div class="header">
					<i class="yellow star icon"></i> Favorite Projects
				</div>
			</h2>
			<div class="ui relaxed divided list">
			<?
				/* get user_project info */
				$sqlstring = "select * from user_project a left join projects b on a.project_id = b.project_id where a.user_id in (select user_id from users where username = '" . $GLOBALS['username'] . "') and a.favorite = 1";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$projectid = $row['project_id'];
					$projectname = $row['project_name'];
					
					$sqlstringA = "select count(*) 'numsubjects' from subjects a left join enrollment b on a.subject_id = b.subject_id where b.project_id = $projectid and a.isactive = 1";
					$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
					$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
					$numsubjects = $rowA['numsubjects'];
					?>
					<div class="item">
						<div class="content">
							<a href="projects.php?id=<?=$projectid?>" style="font-size: 150%;"><?=$projectname?></a>
							<div class="description">
							<?=$numsubjects?> subjects
							</div>
						</div>
					</div>
					<?
				}
			?>
			</div>

			<br><br>
			
			<div class="ui header">
				<div class="content">
					Recent projects
				</div>
			</div>
			<table class="ui grey selectable very compact table">
				<thead>
					<th>Project</th>
					<th>Date Accessed</th>
				</thead>
				<?
				$sqlstring = "select a.mostrecent_date, a.project_id, b.* from mostrecent a left join projects b on a.project_id = b.project_id where a.user_id in (select user_id from users where username = '$username') and a.project_id is not null and b.project_id in (select project_id from projects where instance_id = '" . $_SESSION['instanceid'] . "') group by b.project_name order by a.mostrecent_date desc";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$projectid = $row['project_id'];
						$date = date('M j g:ia',strtotime($row['mostrecent_date']));
						$projectname = $row['project_name'];
						?>
						<tr>
							<td><a href="projects.php?id=<?=$projectid?>"><b><?=$projectname?></b></a></td>
							<td><?=$date?></td>
						</tr>
						<?
					}
				}
				?>
			</table>

			<div class="ui header">
				<div class="content">
					Recently subjects
				</div>
			</div>

			<table class="ui small celled selectable grey very compact table">
				<thead>
					<th>UID</th>
					<th>Sex</th>
					<th>DOB</th>
					<th>Date Accessed</th>
				</thead>
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
							<td class="tt"><a href="subjects.php?id=<?=$subjectid?>"><?=$uid?></a></td>
							<td><?=$sex?></td>
							<td><?=$dob?></td>
							<td><?=$date?></td>
						</tr>
						<?
					}
				}
				?>
			</table>
			
			<div class="ui header">
				<div class="content">
					Recent studies
				</div>
			</div>

			<table class="ui small celled selectable grey very compact table">
				<thead>
					<tr>
						<th>StudyID</th>
						<th>Date</th>
						<th>Modality</th>
						<th>Site</th>
						<th>Date Accessed</th>
					</tr>
				</thead>
				<?
				$q = mysqli_stmt_init($GLOBALS['linki']);
				mysqli_stmt_prepare($q, "select a.mostrecent_date, a.study_id, b.*, d.uid from mostrecent a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on d.subject_id = c.subject_id where a.user_id in (select user_id from users where username = ?) and a.study_id is not null and c.project_id in (select project_id from projects where instance_id = ?) order by a.mostrecent_date desc");
				mysqli_stmt_bind_param($q, 'ss', $username, $_SESSION['instanceid']);
				$result = MySQLiBoundQuery($q, __FILE__, __LINE__);
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
							<td class="tt"><a href="studies.php?id=<?=$studyid?>"><?=$uid?><?=$studynum?></a></td>
							<td><?=$studydate?></td>
							<td><?=$modality?></td>
							<td><?=$site?></td>
							<td><?=$date?></td>
						</tr>
						<?
					}
				}
				?>
			</table>
		</div>
	</div>

<? include("footer.php") ?>