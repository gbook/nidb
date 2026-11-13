<?
 // ------------------------------------------------------------------------------
 // NiDB index.php
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

	define("LEGIT_REQUEST", true);
	
	session_start();
	ob_start(); /* buffer output so POST/Redirect/GET works despite the menu rendered before the action runs */
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title><?=htmlspecialchars($_SERVER['HTTP_HOST'] ?? '')?> - NiDB</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	/* ----- setup variables ----- */
	/* switching instance changes session state: POST + CSRF token (the menu's instance switcher) */
	$vars['action'] = GetSafeAction(array("switchinstance"));
	$vars['instanceid'] = GetVariable("instanceid");

	switch ($vars['action']) {
		case 'switchinstance':
			/* Post/Redirect/GET, so the menu is re-rendered with the new instance */
			ob_start();
			SwitchInstance($vars['instanceid']);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("index.php");
			break;
		default:
			ShowFlashMessage();
	}

	/* -------------------------------------------- */
	/* ------- SwitchInstance --------------------- */
	/* -------------------------------------------- */
	/* switch to an instance the user is a member of (a pending join request is not membership).
	   Admins and site admins may switch to any instance */
	function SwitchInstance($id) {
		$id = (int)$id;

		$sqlstring = "select instance_name from instance where instance_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
		$row = ($result) ? mysqli_fetch_array($result, MYSQLI_ASSOC) : null;
		mysqli_stmt_close($stmt);
		if (!$row) { Error("Instance not found"); return; }
		$instancename = $row['instance_name'];

		if (!IsAdminUser()) {
			$userid = (int)($GLOBALS['userid'] ?? 0);
			$sqlstring = "select userinstance_id from user_instance where user_id = ? and instance_id = ? and (instance_joinrequest is null or instance_joinrequest = 0)";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'ii', $userid, $id);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$userid, $id]);
			$ismember = (($result) && (mysqli_num_rows($result) > 0));
			mysqli_stmt_close($stmt);
			if (!$ismember) { Error("You are not a member of that instance"); return; }
		}

		$_SESSION['instanceid'] = $id;
		$_SESSION['instancename'] = $instancename;
	}

	$sqlstring = "select count(*) count from subjects where isactive = 1";
	$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
	$numsubjects = $row['count'];

	$sqlstring = "select count(*) count from studies";
	$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
	$numstudies = $row['count'];

	/* total series across all <modality>_series tables. information_schema instead of SHOW TABLES (can't be prepared on all MariaDB versions, and its result column name is fragile) */
	$totalseries = 0;
	$sqlstring = "select table_name 'tablename' from information_schema.tables where table_schema = database() and table_name like '%\_series'";
	$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
		$tablename = strtolower($row['tablename'] ?? '');
		/* only plain <modality>_series tables (validated before use as an identifier) */
		if (!preg_match('/^([a-z0-9]+)_series$/', $tablename, $m))
			continue;
		if (in_array($m[1], array('audit', 'upload', 'package', 'deprecated'), true))
			continue;

		$result2 = MySQLiQuery("select count(*) 'count' from `$tablename`", __FILE__, __LINE__);
		$row2 = ($result2) ? mysqli_fetch_array($result2, MYSQLI_ASSOC) : null;
		$totalseries += (int)($row2['count'] ?? 0);
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
					<a class="ui primary button" href="import.php"><i class="cloud upload icon"></i> Import</a> &nbsp; 
					<a class="ui primary button" href="search.php"><i class="search icon"></i> Search / Export</a>
				</div>
			</div>
			<br><br>
			
			<?
				if ($GLOBALS['cfg']['displayrecentstudies'] ?? false) {
					$numrecentdays = (int)($GLOBALS['cfg']['displayrecentstudydays'] ?? 0);
					if ($numrecentdays <= 0) {
						$numrecentdays = 5;
					}
					$instanceid = (int)($_SESSION['instanceid'] ?? 0);
			?>
			<div class="ui header">
				<div class="content">
					<i class="clock outline icon"></i> New Studies
					<div class="sub header">Imaging studies collected in past <?=$numrecentdays?> days</div>
				</div>
				<a class="ui right floated button" href="dicomimport.php">Dicom import monitor</a>
			</div>
			<table class="ui small celled selectable grey very compact table">
				<thead>
					<tr>
						<th class="subheader">UID</th>
						<th class="subheader">Study #</th>
						<th class="subheader">Date</th>
						<th class="subheader">Modality</th>
						<th class="subheader">Site</th>
						<th class="subheader">Study Desc</th>
						<th class="subheader">Series</th>
						<th class="subheader">Project</th>
					</tr>
				</thead>
			<?
				$stmt = mysqli_prepare($GLOBALS['linki'], "select a.*, c.*, d.uid, d.subject_id from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join projects c on b.project_id = c.project_id left join subjects d on d.subject_id = b.subject_id where d.isactive = 1 and a.study_datetime > DATE_SUB(now(), interval ? day) and a.study_datetime <= now() and b.project_id in (select project_id from projects where instance_id = ?) and a.study_modality <> '' order by a.study_datetime desc");
				mysqli_stmt_bind_param($stmt, 'ii', $numrecentdays, $instanceid);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
				/* collect the rows first, so permissions are looked up once for all projects */
				$recentstudies = array();
				$projectids = array();
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$recentstudies[] = $row;
					$projectids[] = $row['project_id'];
				}
				mysqli_stmt_close($stmt);
				$perms = GetCurrentUserProjectPermissions($projectids);

				foreach ($recentstudies as $row) {
					$study_id = (int)$row['study_id'];
					$subject_id = (int)$row['subject_id'];
					$modality = $row['study_modality'] ?? '';
					$ts = strtotime($row['study_datetime'] ?? '');
					$study_datetime = $ts !== false ? date('M j g:ia', $ts) : '';
					$study_site = $row['study_site'] ?? '';
					$study_desc = $row['study_desc'] ?? '';
					$study_num = $row['study_num'] ?? '';
					$uid = $row['uid'] ?? '';
					$project_name = $row['project_name'] ?? '';
					$projectid = $row['project_id'];
					$project_costcenter = $row['project_costcenter'] ?? '';

					if (GetPerm($perms, 'viewdata', $projectid)) {
						/* series count, and check that series numbers are consecutive starting at 1. An unrecognized modality (no valid series table) still lists the study, with '-' */
						$seriescount = '-';
						$missingseries = array();
						$seriestable = GetSeriesTableName($modality);
						if ($seriestable != '') {
							$sqlstringA = "select series_num from `$seriestable` where study_id = ?";
							$stmtA = mysqli_prepare($GLOBALS['linki'], $sqlstringA);
							if ($stmtA) {
								mysqli_stmt_bind_param($stmtA, 'i', $study_id);
								$resultA = MySQLiBoundQuery($stmtA, __FILE__, __LINE__, $sqlstringA, [$study_id]);
								if ($resultA) {
									$seriescount = 0;
									$seriesnums = array();
									while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
										$seriescount++;
										if (is_numeric($rowA['series_num'] ?? null))
											$seriesnums[(int)$rowA['series_num']] = true;
									}
									/* any number between 1 and the highest series number that isn't present */
									if (count($seriesnums) > 0) {
										$maxseries = max(array_keys($seriesnums));
										for ($i = 1; $i <= $maxseries; $i++) {
											if (!isset($seriesnums[$i]))
												$missingseries[] = $i;
										}
									}
								}
								mysqli_stmt_close($stmtA);
							}
						}

						/* summarize missing series as ranges (3,5,9-12) */
						$seriestooltip = '';
						if (count($missingseries) > 0) {
							$ranges = array();
							$start = $prev = $missingseries[0];
							foreach (array_slice($missingseries, 1) as $n) {
								if ($n == $prev + 1) {
									$prev = $n;
									continue;
								}
								$ranges[] = ($start == $prev) ? "$start" : "$start-$prev";
								$start = $prev = $n;
							}
							$ranges[] = ($start == $prev) ? "$start" : "$start-$prev";
							$seriestooltip = "Total of $seriescount series - Missing series " . implode(',', $ranges);
						}
						?>
						<tr<?=($seriestooltip != '') ? ' style="background-color: #FFE0B2"' : ''?>>
							<td class="tt"><a href="subjects.php?id=<?=$subject_id?>"><?=htmlspecialchars($uid)?></a></td>
							<td class="tt"><a href="studies.php?id=<?=$study_id?>"><?=htmlspecialchars($study_num)?></a></td>
							<td style="font-size:8pt; white-space: nowrap"><?=$study_datetime?></td>
							<td style="font-size:8pt"><?=htmlspecialchars($modality)?></td>
							<td style="font-size:8pt"><?=htmlspecialchars($study_site)?></td>
							<td style="font-size:8pt"><?=htmlspecialchars($study_desc)?></td>
							<td style="font-size:8pt"><? if ($seriestooltip != '') { ?><span data-tooltip="<?=htmlspecialchars($seriestooltip)?>" data-position="left center" style="cursor: help; text-decoration: underline dotted"><?=$seriescount?></span><? } else { ?><?=$seriescount?><? } ?></td>
							<td style="font-size:8pt"><?=htmlspecialchars($project_name)?> (<?=htmlspecialchars($project_costcenter)?>)</td>
						</tr>
						<?
					}
					else {
						?>
						<tr>
							<td class="tt"><a href="subjects.php?id=<?=$subject_id?>"><?=htmlspecialchars($uid)?></a></td>
							<td class="tt"><a href="studies.php?id=<?=$study_id?>"><?=htmlspecialchars($study_num)?></a></td>
							<td style="font-size:8pt; white-space: nowrap"><?=$study_datetime?></td>
							<td style="font-size:8pt"><?=htmlspecialchars($modality)?></td>
							<td style="font-size:8pt"><?=htmlspecialchars($study_site)?></td>
							<td style="font-size:8pt" colspan="3">No permissions for <?=htmlspecialchars($project_name)?> (<?=htmlspecialchars($project_costcenter)?>)</td>
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
				/* favorite projects in the current instance */
				$instanceid = (int)($_SESSION['instanceid'] ?? 0);
				$stmt = mysqli_prepare($GLOBALS['linki'], "select b.project_id, b.project_name from user_project a join projects b on a.project_id = b.project_id where a.user_id in (select user_id from users where username = ?) and a.favorite = 1 and b.instance_id = ? order by b.project_name");
				mysqli_stmt_bind_param($stmt, 'si', $GLOBALS['username'], $instanceid);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$projectid = (int)$row['project_id'];
					$projectname = $row['project_name'] ?? '';

					$sqlstringA = "select count(distinct a.subject_id) 'numsubjects' from subjects a join enrollment b on a.subject_id = b.subject_id where b.project_id = ? and a.isactive = 1";
					$stmtA = mysqli_prepare($GLOBALS['linki'], $sqlstringA);
					mysqli_stmt_bind_param($stmtA, 'i', $projectid);
					$resultA = MySQLiBoundQuery($stmtA, __FILE__, __LINE__, $sqlstringA, [$projectid]);
					$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
					$numsubjects = (int)($rowA['numsubjects'] ?? 0);
					mysqli_stmt_close($stmtA);
					?>
					<div class="item">
						<div class="content">
							<a href="projects.php?id=<?=$projectid?>" style="font-size: 150%;"><?=htmlspecialchars($projectname)?></a>
							<div class="description">
							<?=number_format($numsubjects)?> subjects
							</div>
						</div>
					</div>
					<?
				}
				mysqli_stmt_close($stmt);
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
					<tr>
						<th>Project</th>
						<th>Date Accessed</th>
					</tr>
				</thead>
				<?
				/* one row per project, with its latest access date */
				$stmt = mysqli_prepare($GLOBALS['linki'], "select max(a.mostrecent_date) 'mostrecent_date', b.project_id, b.project_name from mostrecent a join projects b on a.project_id = b.project_id where a.user_id in (select user_id from users where username = ?) and b.instance_id = ? group by b.project_id, b.project_name order by mostrecent_date desc");
				mysqli_stmt_bind_param($stmt, 'si', $GLOBALS['username'], $instanceid);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$projectid = (int)$row['project_id'];
					$ts = strtotime($row['mostrecent_date'] ?? '');
					$date = $ts !== false ? date('M j g:ia', $ts) : '';
					$projectname = $row['project_name'] ?? '';
					?>
					<tr>
						<td><a href="projects.php?id=<?=$projectid?>"><b><?=htmlspecialchars($projectname)?></b></a></td>
						<td><?=$date?></td>
					</tr>
					<?
				}
				mysqli_stmt_close($stmt);
				?>
			</table>

			<div class="ui header">
				<div class="content">
					Recent subjects
				</div>
			</div>

			<table class="ui small celled selectable grey very compact table">
				<thead>
					<tr>
						<th>UID</th>
						<th>Sex</th>
						<th>Date Accessed</th>
					</tr>
				</thead>
				<?
				/* one row per subject (enrolled in a project in this instance), with its latest access date. No DOB (PHI) here, by decision */
				$stmt = mysqli_prepare($GLOBALS['linki'], "select max(a.mostrecent_date) 'mostrecent_date', b.subject_id, b.uid, b.gender from mostrecent a join subjects b on a.subject_id = b.subject_id join enrollment c on b.subject_id = c.subject_id where a.user_id in (select user_id from users where username = ?) and c.project_id in (select project_id from projects where instance_id = ?) group by b.subject_id, b.uid, b.gender order by mostrecent_date desc");
				mysqli_stmt_bind_param($stmt, 'si', $GLOBALS['username'], $instanceid);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$subjectid = (int)$row['subject_id'];
					$ts = strtotime($row['mostrecent_date'] ?? '');
					$date = $ts !== false ? date('M j g:ia', $ts) : '';
					$uid = $row['uid'] ?? '';
					$sex = $row['gender'] ?? '';
					?>
					<tr>
						<td class="tt"><a href="subjects.php?id=<?=$subjectid?>"><?=htmlspecialchars($uid)?></a></td>
						<td><?=htmlspecialchars($sex)?></td>
						<td><?=$date?></td>
					</tr>
					<?
				}
				mysqli_stmt_close($stmt);
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
				$stmt = mysqli_prepare($GLOBALS['linki'], "select a.mostrecent_date, a.study_id, b.*, d.uid from mostrecent a join studies b on a.study_id = b.study_id join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on d.subject_id = c.subject_id where a.user_id in (select user_id from users where username = ?) and c.project_id in (select project_id from projects where instance_id = ?) order by a.mostrecent_date desc");
				mysqli_stmt_bind_param($stmt, 'si', $GLOBALS['username'], $instanceid);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$studyid = (int)$row['study_id'];
					$studynum = $row['study_num'] ?? '';
					$ts = strtotime($row['mostrecent_date'] ?? '');
					$date = $ts !== false ? date('M j g:ia', $ts) : '';
					$uid = $row['uid'] ?? '';
					$studydate = $row['study_datetime'] ?? '';
					$modality = $row['study_modality'] ?? '';
					$site = $row['study_site'] ?? '';
					?>
					<tr>
						<td class="tt"><a href="studies.php?id=<?=$studyid?>"><?=htmlspecialchars($uid . $studynum)?></a></td>
						<td><?=htmlspecialchars($studydate)?></td>
						<td><?=htmlspecialchars($modality)?></td>
						<td><?=htmlspecialchars($site)?></td>
						<td><?=$date?></td>
					</tr>
					<?
				}
				mysqli_stmt_close($stmt);
				?>
			</table>
		</div>
	</div>

<? include("footer.php") ?>