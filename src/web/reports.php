<?
 // ------------------------------------------------------------------------------
 // NiDB reports.php
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
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Study Reports</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	/* reports cover every study in the system: admins, site admins and project admins only */
	if (!IsAdminUser() && !IsAnyProjectAdmin()) {
		Warning("You do not have permissions to view this page");
		exit(0);
	}

	/* setup variables */
	$action = GetVariable("action");
	$year = (int)GetVariable("year");
	$datestart = GetVariable("datestart");
	$dateend = GetVariable("dateend");
	$modality = GetVariable("modality");
	$studysite = GetVariable("studysite");
	
	switch ($action) {
		case 'yearstudy':
			DisplayMenu();
			DisplayYear($year, "studies", $modality, $studysite);
			break;
		case 'viewreport':
			ViewReport($datestart, $dateend, $modality, $studysite);
			break;
		case 'menu':
		default:
			DisplayMenu();
			break;
	}
	
	
	/* ----------------------------------------------- */
	/* --------- DisplayMenu ------------------------- */
	/* ----------------------------------------------- */
	function DisplayMenu() {
	
		?>
		<div class="ui two column grid">
			<div class="column">
				<h2 class="ui header">
					Reports by Year
					<div class="sub header">
						View modality & year
					</div>
				</h2>
				<table class="ui very compact celled collapsing striped selectable small table">
				<?
				/* get a list of modalities */
				$sqlstring = "select distinct(study_modality) 'modality' from studies";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$modality = $row['modality'];
					
					?>
					<tr>
						<td align="right" class="left"><?=htmlspecialchars($modality ?? '')?></td>
					<?
					$years = array();
					/* get the range of years that studies have occured */
					$modality = (string)($modality ?? '');
					$sqlstring2 = "select distinct year(study_datetime) theyear from studies where study_datetime > '0000-00-01 00:00:00' and study_modality = ? order by year(study_datetime) desc";
					$stmt2 = mysqli_prepare($GLOBALS['linki'], $sqlstring2);
					mysqli_stmt_bind_param($stmt2, 's', $modality);
					$result2 = MySQLiBoundQuery($stmt2, __FILE__, __LINE__, $sqlstring2, [$modality]);
					mysqli_stmt_close($stmt2);
					while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
						array_push($years, (int)$row2['theyear']);
					}
					foreach ($years as $year) {
					?>
						<td class="right"><a href="reports.php?action=yearstudy&year=<?=$year?>&modality=<?=urlencode($modality)?>"><?=$year?></a></td>
					<? } ?>
					</tr>
					<?
				}
				?>
				</table>
			</div>
			<div class="column">
				<h2 class="ui header">
					Reports by Site/Equipment
					<div class="sub header">
						View site & year
					</div>
				</h2>
				<table class="ui very compact celled collapsing striped selectable small table">
				<?
				/* get a list of modalities */
				$sqlstring = "select distinct(study_site) 'study_site' from studies";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$studysite = (string)($row['study_site'] ?? '');
					
					?>
					<tr>
						<td align="right" class="left"><?=htmlspecialchars($studysite)?></td>
					<?
					$years = array();
					/* get the range of years that studies have occured */
					$sqlstring2 = "select distinct year(study_datetime) theyear from studies where study_datetime > '0000-00-01 00:00:00' and study_site = ? order by year(study_datetime) desc";
					$stmt2 = mysqli_prepare($GLOBALS['linki'], $sqlstring2);
					mysqli_stmt_bind_param($stmt2, 's', $studysite);
					$result2 = MySQLiBoundQuery($stmt2, __FILE__, __LINE__, $sqlstring2, [$studysite]);
					mysqli_stmt_close($stmt2);
					while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
						array_push($years, (int)$row2['theyear']);
					}
					foreach ($years as $year) {
					?>
						<td class="right"><a href="reports.php?action=yearstudy&year=<?=$year?>&studysite=<?=urlencode($studysite)?>"><?=$year?></a></td>
					<? } ?>
					</tr>
					<?
				}
				?>
				</table>
			</div>
		</div>
		<?
	}
	

	/* ----------------------------------------------- */
	/* --------- DisplayYear ------------------------- */
	/* ----------------------------------------------- */
	function DisplayYear($year, $type, $modality, $studysite) {
		/* generate a color gradient in an array (green to yellow) */
		$startR = 0xFF; $startG = 0xFF; $startB = 0x00;
		$endR = 0x00; $endG = 0xFF; $endB = 0x00;
		$total = 50;

		for ($i=0; $i<=$total; $i++) {
			$percentSR = ($i/$total)*$startR;
			$percentER = (1-($i/$total))*$endR;
			$colorR = $percentSR + $percentER;

			$percentSG = ($i/$total)*$startG;
			$percentEG = (1-($i/$total))*$endG;
			$colorG = $percentSG + $percentEG;

			$percentSB = ($i/$total)*$startB;
			$percentEB = (1-($i/$total))*$endB;
			$colorB = $percentSB + $percentEB;

			$color = sprintf("%02X%02X%02X", $colorR, $colorG, $colorB);
			$colors[] = $color;
		}

		/* generate gradient from yellow to red */
		$startR = 0xFF; $startG = 0x33; $startB = 0x33;
		$endR = 0xFF; $endG = 0xFF; $endB = 0x00;

		for ($i=0; $i<=$total; $i++) {
			$percentSR = ($i/$total)*$startR;
			$percentER = (1-($i/$total))*$endR;
			$colorR = $percentSR + $percentER;

			$percentSG = ($i/$total)*$startG;
			$percentEG = (1-($i/$total))*$endG;
			$colorG = $percentSG + $percentEG;

			$percentSB = ($i/$total)*$startB;
			$percentEB = (1-($i/$total))*$endB;
			$colorB = $percentSB + $percentEB;

			$color = sprintf("%02X%02X%02X", $colorR, $colorG, $colorB);
			$colors[$i+50] = $color;
		}
		?>
		<br><br>
		<h2 class="ui header">
			Select Report for <?=(int)$year?>
			<div class="sub header">
				View by month or day
			</div>
		</h2>

		<div class="ui fitted top attached segment">
			<div class="ui basic horizontal segments">
				<div class="ui basic compact segment" style="padding: 2px"><b style="font-size: larger">Key</b></div>
				<div class="ui basic compact segment" style="padding:2px 10px 2px 30px"><div class="ui green label">Few</div></div>
				<div class="segment" style="background: linear-gradient(90deg, lime 0%, yellow 50%, red 100%); padding: 2px; border-left: none"></div>
				<div class="ui compact segment" style="padding:2px 15px"><div class="ui red label">Many</div></div>
			</div>
		</div>
		<div class="ui bottom attached segment">
			<table width="100%" cellpadding="10">
				<tr>
					<td width="25%" valign="top"><? DisplayMonth(1, $year, $colors, $type, $modality, $studysite); ?></td>
					<td width="25%" valign="top"><? DisplayMonth(2, $year, $colors, $type, $modality, $studysite); ?></td>
					<td width="25%" valign="top"><? DisplayMonth(3, $year, $colors, $type, $modality, $studysite); ?></td>
					<td width="25%" valign="top"><? DisplayMonth(4, $year, $colors, $type, $modality, $studysite); ?></td>
				</tr>
				<tr>
					<td width="25%" valign="top"><? DisplayMonth(5, $year, $colors, $type, $modality, $studysite); ?></td>
					<td width="25%" valign="top"><? DisplayMonth(6, $year, $colors, $type, $modality, $studysite); ?></td>
					<td width="25%" valign="top"><? DisplayMonth(7, $year, $colors, $type, $modality, $studysite); ?></td>
					<td width="25%" valign="top"><? DisplayMonth(8, $year, $colors, $type, $modality, $studysite); ?></td>
				</tr>
				<tr>
					<td width="25%" valign="top"><? DisplayMonth(9, $year, $colors, $type, $modality, $studysite); ?></td>
					<td width="25%" valign="top"><? DisplayMonth(10, $year, $colors, $type, $modality, $studysite); ?></td>
					<td width="25%" valign="top"><? DisplayMonth(11, $year, $colors, $type, $modality, $studysite); ?></td>
					<td width="25%" valign="top"><? DisplayMonth(12, $year, $colors, $type, $modality, $studysite); ?></td>
				</tr>
			</table>
		</div>
		<?
	}
	
	
	/* ----------------------------------------------- */
	/* --------- DisplayMonth ------------------------ */
	/* ----------------------------------------------- */
	function DisplayMonth($month, $year, $colors, $type, $modality, $studysite) {
		$month = (int)$month;
		$year = (int)$year;
		$modality = (string)$modality;
		$studysite = (string)$studysite;
		$filterurl = "&modality=" . urlencode($modality) . "&studysite=" . urlencode($studysite);

		//Here we generate the first day of the month
		$first_day = mktime(0,0,0,$month, 1, $year) ;

		//This gets us the month name
		$title = date('F', $first_day);
		
		//Here we find out what day of the week the first day of the month falls on
		$day_of_week = date('D', $first_day) ;

		//Once we know what day of the week it falls on, we know how many blank days occure before it. If the first day of the week is a Sunday then it would be zero
		switch($day_of_week){
			case "Sun": $blank = 0; break;
			case "Mon": $blank = 1; break;
			case "Tue": $blank = 2; break;
			case "Wed": $blank = 3; break;
			case "Thu": $blank = 4; break;
			case "Fri": $blank = 5; break;
			case "Sat": $blank = 6; break;
		}

		//We then determine how many days are in the current month
		$days_in_month = cal_days_in_month(0, $month, $year) ; 
		
		//Here we start building the table heads
		$datestart = "$year-$month-1 00:00:00";
		$dateend = "$year-$month-$days_in_month 23:59:59";
		?>
		<table class="calendar" cellpadding="0" cellspacing="0" width="100%">
			<tr><td colspan=7 class="heading">
				<a href="reports.php?action=viewreport&datestart=<?=urlencode($datestart)?>&dateend=<?=urlencode($dateend)?><?=$filterurl?>" style="color: darkblue;"><?=$title?> <?=$year?></a>
			</td></tr>
			<tr>
				<td width="14.28%" class="days">S</td>
				<td width="14.28%" class="days">M</td>
				<td width="14.28%" class="days">T</td>
				<td width="14.28%" class="days">W</td>
				<td width="14.28%" class="days">T</td>
				<td width="14.28%" class="days">F</td>
				<td width="14.28%" class="days">S</td>
			</tr>
		<?
		//This counts the days in the week, up to 7
		$day_count = 1;

		?><tr><?
		//first we take care of those blank days
		while ($blank > 0) {
			echo "<td class='day'>&nbsp;</td>";
			$blank = $blank-1;
			$day_count++;
		}
		
		//sets the first day of the month to 1
		$day_num = 1;

		/* one statement per month, re-executed for each day (bind_param binds by reference) */
		$daystart = "";
		$dayend = "";
		$filtervalue = ($modality == "") ? $studysite : $modality;
		if ($modality == "") {
			$sqlstring = "SELECT count(*) 'count' FROM studies WHERE study_datetime between ? and ? and study_site = ?";
		}
		else {
			$sqlstring = "SELECT count(*) 'count' FROM studies WHERE study_datetime between ? and ? and study_modality = ?";
		}
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'sss', $daystart, $dayend, $filtervalue);

		//count up the days, untill we've done all of them in the month
		while ( $day_num <= $days_in_month ) {
			$datestart = sprintf("%04d-%02d-%02d 00:00:00", $year, $month, $day_num);
			$dateend = sprintf("%04d-%02d-%02d 23:59:59", $year, $month, $day_num);
			$daystart = $datestart;
			$dayend = $dateend;
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$daystart, $dayend, $filtervalue]);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$numstudies = (int)($row['count'] ?? 0);
			$percent = 0;
			if ($type == "studies") {
				$percent = min(100, (int)round(($numstudies/12)*100)); /* the gradient has 101 colors (0-100) */
			}
			if ($numstudies < 1) {
				$color = "#FFFFFF";
				$numstudies = "&nbsp;";
			}
			else {
				$color = $colors[$percent];
			}
			?>
			<td class="day" style="background-color: <?=$color?>">
				<span style="color: #555555;"> <?=$day_num?>&nbsp;</span><br>
				<div align="right" style="color: black; font-size:10pt;">
				<? if ($numstudies < 1) { ?>
					&nbsp;
				<? } else { ?>
				<a href="reports.php?action=viewreport&datestart=<?=urlencode($datestart)?>&dateend=<?=urlencode($dateend)?><?=$filterurl?>" style="color: blue;"><?=$numstudies?></a>
				&nbsp;</div>
				<? } ?>
			</td>
			<?
			$day_num++;
			$day_count++;

			//Make sure we start a new row every week
			if ($day_count > 7) {
				echo "</tr><tr>";
				$day_count = 1;
			}
		} 		
		mysqli_stmt_close($stmt);

		//Finaly we finish out the table with some blank details if needed
		while ( $day_count > 1 && $day_count <= 7 ) {
			echo "<td class='day'>&nbsp;</td>";
			$day_count++;
		}

		?>
			</tr>
		</table>
		<? 		
	}


	/* ----------------------------------------------- */
	/* --------- ViewReport -------------------------- */
	/* ----------------------------------------------- */
	function ViewReport($datestart, $dateend, $modality, $studysite) {
		$modality = (string)$modality;
		$studysite = (string)$studysite;

		/* normalize the dates; anything that isn't a date is rejected */
		$tsstart = strtotime((string)$datestart);
		$tsend = strtotime((string)$dateend);
		if (($tsstart === false) || ($tsend === false)) {
			Error("Invalid date range");
			return;
		}
		$datestart = date('Y-m-d H:i:s', $tsstart);
		$dateend = date('Y-m-d H:i:s', $tsend);

		if ($modality == "") {
			$sqlstring = "select c.uid, c.subject_id, c.gender, c.birthdate, d.*, a.* from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id left join projects d on b.project_id = d.project_id where a.study_datetime > ? and a.study_datetime < ? and a.study_site = ? order by study_datetime";
			$params = [$datestart, $dateend, $studysite];
		}
		else {
			$sqlstring = "select c.uid, c.subject_id, c.gender, c.birthdate, d.*, a.* from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id left join projects d on b.project_id = d.project_id where a.study_modality = ? and a.study_datetime > ? and a.study_datetime < ? order by study_datetime";
			$params = [$modality, $datestart, $dateend];
		}
		?>
		<div class="ui container">
			<h3 class="ui header">
				<?=htmlspecialchars($modality)?> studies collected on <?=htmlspecialchars($studysite)?>
				<div class="sub header">
					Collected between <?=$datestart?> to <?=$dateend?>
				</div>
			</h3>
			<table class="ui very compact celled grey table">
				<thead>
					<th>UID</th>
					<th>Study</th>
					<th>Sex</th>
					<th>BirthDate</th>
					<th>Study Description</th>
					<th>Project</th>
					<th>Study date</th>
					<th>Radiological Read?</th>
					<th>Read date</th>
					<th>Read findings</th>
				</thead>
			<?
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'sss', ...$params);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
			mysqli_stmt_close($stmt);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$uid = $row['uid'];
				$gender = $row['gender'];
				$subjectid = $row['subject_id'];
				$subjectdob = $row['birthdate'];
				$studyid = $row['study_id'];
				$studynum = $row['study_num'];
				$studydesc = $row['study_desc'];
				$studydatetime = $row['study_datetime'];
				$studyradreaddone = $row['study_doradread'];
				$studyradreaddate = $row['study_radreaddate'];
				$studyradreadfindings = $row['study_radreadfindings'];
				$project = $row['project_name'] . " (" . $row['project_costcenter'] . ")";
				?>
				<tr>
					<td><a href="subjects.php?id=<?=(int)$subjectid?>"><?=htmlspecialchars($uid ?? '')?></a></td>
					<td><a href="studies.php?id=<?=(int)$studyid?>"><?=htmlspecialchars(($uid ?? '') . ($studynum ?? ''))?></a></td>
					<td><?=htmlspecialchars($gender ?? '')?></td>
					<td><?=htmlspecialchars($subjectdob ?? '')?></td>
					<td><?=htmlspecialchars($studydesc ?? '')?></td>
					<td><?=htmlspecialchars($project)?></td>
					<td><?=htmlspecialchars($studydatetime ?? '')?></td>
					<td><?=htmlspecialchars($studyradreaddone ?? '')?></td>
					<td><?=htmlspecialchars($studyradreaddate ?? '')?></td>
					<td><?=htmlspecialchars($studyradreadfindings ?? '')?></td>
				</tr>
				<?
			}
			?>
			</table>
		</div>
		<?
	}

	
?>
	
<? include("footer.php") ?>
