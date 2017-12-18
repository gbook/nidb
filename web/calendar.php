<?
 // ------------------------------------------------------------------------------
 // NiDB calendar.php
 // Copyright (C) 2004 - 2017
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
		<title>NiDB - Calendar</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes.php";
	require "menu.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");

	$currentcal = $_COOKIE['currentcal'];
	$currentcalname = $_COOKIE['currentcalname'];
	$currentview = $_COOKIE['currentview'];

	if ($currentcal == "") {
		header("Location: calendar_select.php");
	}
	
	if ($_POST["action"] == "") { $action = $_GET["action"]; } else { $action = $_POST["action"]; }
	if ($_POST["year"] == "") { $year = $_GET["year"]; } else { $year = $_POST["year"]; }
	if ($_POST["month"] == "") { $month = $_GET["month"]; } else { $month = $_POST["month"]; }
	if ($_POST["day"] == "") { $day = $_GET["day"]; } else { $day = $_POST["day"]; }
	if ($_POST["datestart"] == "") { $datestart = $_GET["datestart"]; } else { $datestart = $_POST["datestart"]; }
	if ($_POST["dateend"] == "") { $dateend = $_GET["dateend"]; } else { $dateend = $_POST["dateend"]; }

	if ($action == "") {
		$action = $currentview;
	}
	
	/* check for blank dates */
	if ($year == "") { $year = date("Y"); }
	if ($month == "") { $month = date("m"); }
	if ($day == "") { $day = date("d"); }

	$holidays = CalculateHolidays($year);
	
	/* check the action */
	if ($action == "year") {
		setcookie("currentview", "year");
		DisplayMenu($year, $month, $day, "year");
		DisplayYear($year, $currentcal);
	}
	elseif ($action == "month") {
		setcookie("currentview", "month");
		DisplayMenu($year, $month, $day, "month");
		DisplayMonth($year, $month, $holidays, $currentcal);
	}
	elseif ($action == "day") {
		setcookie("currentview", "day");
		DisplayMenu($year, $month, $day, "day");
		DisplayDay($year, $month, $day, $holidays, $currentcal);
	}
	elseif ($action == "manage") {
		DisplayManagementMenu();
	}
	elseif (($action == "") || ($action == "week")) {
		setcookie("currentview", "week");
		DisplayMenu($year, $month, $day, "week");
		DisplayWeek($year, $month, $day, $holidays, $currentcal);
	}
	
	
	/* ----------------------------------------------- */
	/* --------- DisplayMenu ------------------------- */
	/* ----------------------------------------------- */
	function DisplayMenu($year, $month, $day, $menuitem) {
		global $currentcal;
		global $currentcalname;

		$caldate = mktime(0,0,0,$month,$day,$year);
		?>
		<style>
			.menuitem { color: blue; text-align: center; padding-left: 5px; padding-right: 5px }
			.menuitemhighlight { text-align: center; padding: 5px; background-color: lightyellow; border: 1pt solid orange; border-radius: 3px }
			.title { color: darkblue; font-size: 16pt; font-weight: bold }

			.week_heading { font-size: 12pt; color: white; font-weight:bold; text-align: center; background-color: #555; padding: 5px }
			.week_heading_date { font-size: 10pt; color: white; font-weight: normal; }
			.week_heading_holiday { font-size: 8pt; background-color: darkred; color: white; font-weight: bold; padding: 1px 4px; }
			.time { font-size: 10pt; color: darkred; background-color: lightyellow; }
			.timeallday { font-size: 10pt; color: darkred; background-color: #DFDFDF; }
			.appttitle { font-size: 10pt; color: darkblue; }
			.apptowner { font-size: 8pt; color: #555555; }
			.timerequest { font-size: 8pt; background-color: darkred; color: #FFFFFF; font-variant: small-caps; }
		</style>
		
		<table width="100%" cellspacing="0">
			<tr>
				<form name="pageform" action="calendar_select.php" method="get">
				<td align="center"><span class="title"><?=$currentcalname?></span>
				<br><br>
				<span class="apptowner">Switch:</span>
				<input type="hidden" name="action" value="set">
				<select name="currentcal" style="border: 1px solid #AAAAAA; font-size:8pt" onChange="pageform.submit()">
				<?
				$sqlstring = "select * from calendars where calendar_deletedate > now() order by calendar_name";
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$id = $row['calendar_id'];
					$name = $row['calendar_name'];
					$description = $row['calendar_description'];
					$location = $row['calendar_location'];
					?>
					<option value="<?=$id?>" <? if ($currentcal == $id) { echo "selected"; } ?>><?=$name?>
					<?
				}
				?>
				<option value="0" <? if ($currentcal == 0) { echo "selected"; } ?>>View All Calendars...
				</select>
				</form>
				</td>
			</tr>
			<tr>
				<td width="90%"><span style="font-size:10pt">
					<b>Today</b> <?=date('D M j, Y')?><br><br>
					<b>Calendar Date</b> <?=date('D M j, Y',$caldate)?>
					</span>
				</td>
				<? if ($menuitem == "day") { $class="menuitemhighlight"; } else { $class="menuitem"; } ?>
				<td class="<?=$class?>">
				<a href="calendar.php?action=day&year=<?=$year?>&month=<?=$month?>&day=<?=$day?>"><img src="images/day.png"><br>Day</a>
				</td>
				<? if ($menuitem == "week") { $class="menuitemhighlight"; } else { $class="menuitem"; } ?>
				<td class="<?=$class?>">
				<a href="calendar.php?action=week&year=<?=$year?>&month=<?=$month?>&day=<?=$day?>"><img src="images/week.png"><br>Week</a></td>
				<? if ($menuitem == "month") { $class="menuitemhighlight"; } else { $class="menuitem"; } ?>
				<td class="<?=$class?>">
				<a href="calendar.php?action=month&year=<?=$year?>&month=<?=$month?>&day=<?=$day?>"><img src="images/month.png"><br>Month</a></td>
				<!--<? if ($menuitem == "year") { $class="menuitemhighlight"; } else { $class="menuitem"; } ?>
				<td class="<?=$class?>"><a href="calendar.php?action=year&year=<?=$year?>&month=<?=$month?>&day=<?=$day?>" style="color: blue; text-decoration: underline">Year</a></td>-->
			</tr>
		</table>
		<br><br>
		<?
	}


	/* ----------------------------------------------- */
	/* --------- DisplayManagementMenu --------------- */
	/* ----------------------------------------------- */
	function DisplayManagementMenu() {
		?>
		<b>Manage...</b><br><br>
		<a href="calendar_calendars.php">Calendars</a><br>
		<a href="calendar_projects.php">Projects</a><br>
		Project Resource <a href="calendar_allocations.php">Allocations</a><br>
		<br><br>
		<?
	}
	

	/* ----------------------------------------------- */
	/* --------- DisplayYear ------------------------- */
	/* ----------------------------------------------- */
	function DisplayYear($year, $currentcal) {
		?>
		<table width="100%" cellpadding="10">
			<tr>
				<td width="25%" valign="top"><? DisplayMonth(1, $year); ?></td>
				<td width="25%" valign="top"><? DisplayMonth(2, $year); ?></td>
				<td width="25%" valign="top"><? DisplayMonth(3, $year); ?></td>
				<td width="25%" valign="top"><? DisplayMonth(4, $year); ?></td>
			</tr>
			<tr>
				<td width="25%" valign="top"><? DisplayMonth(5, $year); ?></td>
				<td width="25%" valign="top"><? DisplayMonth(6, $year); ?></td>
				<td width="25%" valign="top"><? DisplayMonth(7, $year); ?></td>
				<td width="25%" valign="top"><? DisplayMonth(8, $year); ?></td>
			</tr>
			<tr>
				<td width="25%" valign="top"><? DisplayMonth(9, $year); ?></td>
				<td width="25%" valign="top"><? DisplayMonth(10, $year); ?></td>
				<td width="25%" valign="top"><? DisplayMonth(11, $year); ?></td>
				<td width="25%" valign="top"><? DisplayMonth(12, $year); ?></td>
			</tr>
		</table>
		<?
	}

	/* ----------------------------------------------- */
	/* --------- DisplayDay -------------------------- */
	/* ----------------------------------------------- */
	function DisplayDay($year, $month, $day, $holidays, $currentcal) {

		$first_day = mktime(0,0,0,$month, $day, $year) ;
		
		$prevyear = date('Y', strtotime(date('Y-m-d',$first_day) . " -1 days"));
		$prevmonth = date('m', strtotime(date('Y-m-d',$first_day) . " -1 days"));
		$prevday = date('d', strtotime(date('Y-m-d',$first_day) . " -1 days"));;
		
		$nextyear = date('Y', strtotime(date('Y-m-d',$first_day) . " +1 days"));
		$nextmonth = date('m', strtotime(date('Y-m-d',$first_day) . " +1 days"));
		$nextday = date('d', strtotime(date('Y-m-d',$first_day) . " +1 days"));
		
		$today = date('D M j, Y',mktime(0,0,0,$month, $day, $year));
		?>
		<div align="center">
		<table class="calendar" cellpadding="0" cellspacing="0" width="50%" style="border: 1px solid #555; background-color: snow">
			<tr>
				<td colspan=7 class="heading" style="padding: 8px; background-color: #555;">
					<br>
					<a href="calendar.php?action=day&year=<?=$prevyear?>&month=<?=$prevmonth?>&day=<?=$prevday?>" style="text-decoration: none; color: white; font-size:16pt">&#9664;</a>
					&nbsp;
					<span style="color: white; font-size:16pt"><?=$today?></span>
					&nbsp;
					<a href="calendar.php?action=day&year=<?=$nextyear?>&month=<?=$nextmonth?>&day=<?=$nextday?>" style="text-decoration: none; color: white; font-size:16pt">&#9654;</a>
					<br>
					<br>
				</td>
			</tr>
			<tr>
				<td style="border-top: 1px solid gray; padding: 5px">
					<?
					$startdatetime = date('Y-m-d 00:00:00', mktime(0,0,0,$month, $day, $year));
					$enddatetime = date('Y-m-d 23:59:59', mktime(0,0,0,$month, $day, $year));
					?>
					<a href="calendar_appointments.php?action=addform&currentcal=<?=$currentcal?>&startdate=<?=date('YmdHi', strtotime($startdatetime))?>"><img src="images/add12.png" border="0" title="Add appointment"></a><br><br>
					<?
					if ($currentcal == 0) {
						$sqlstring = "select a.*, b.project_name, c.calendar_name from calendar_appointments a left join calendar_projects b on a.appt_projectid = b.project_id left join calendars c on a.appt_calendarid = c.calendar_id where appt_deletedate > now() and appt_canceldate > now() and a.appt_startdate >= '$startdatetime' and a.appt_enddate <= '$enddatetime' order by appt_isalldayevent, appt_startdate";
					}
					else {
						$sqlstring = "select a.*, b.project_name, c.calendar_name from calendar_appointments a left join calendar_projects b on a.appt_projectid = b.project_id left join calendars c on a.appt_calendarid = c.calendar_id where a.appt_calendarid = $currentcal and appt_deletedate > now() and appt_canceldate > now() and a.appt_startdate >= '$startdatetime' and a.appt_enddate <= '$enddatetime' order by appt_isalldayevent, appt_startdate";
					}
					$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$id = $row['appt_id'];
						$username = $row['appt_username'];
						$projectname = $row['project_name'];
						$calendarname = $row['calendar_name'];
						$title = $row['appt_title'];
						$details = $row['appt_details'];
						$starttime = date('g:i a', strtotime($row['appt_startdate']));
						$endtime = date('g:i a', strtotime($row['appt_enddate']));
						$isallday = $row['appt_isalldayevent'];
						$isrequest = $row['appt_istimerequest'];
						?>
						<?if (!$isallday) { ?>
							<span class="time">&nbsp;<?=$starttime?> - <?=$endtime?>&nbsp;</span> &nbsp;
						<? } ?>
						<? if ($isrequest) { ?>
							<span class="timerequest">&nbsp;Time request&nbsp;</span>
						<? } ?>
						
						<? /*if ($_COOKIE['username'] == $username) { */?>
						<a href="calendar_appointments.php?action=editform&id=<?=$id?>">
						<span class="appttitle"><u><?=$title?></u></span></a>
						<? /* } else { */ ?>
						<!--<span class="appttitle"><?=$title?></span>-->
						<? /*}*/ ?>
						&nbsp;
						<span class="apptowner"><?=$calendarname?> - <b><?=$username?></b></span>
						<br>
						<?=$details;?>
						<br>
						<div style="border-bottom: 1px dashed gray">&nbsp;</div>
						<br>
						<?
					}
					?>
				</td>
			</tr>
		</table>
		</div>
		<?
	}
	
	
	/* ----------------------------------------------- */
	/* --------- DisplayWeek ------------------------- */
	/* ----------------------------------------------- */
	function DisplayWeek($year, $month, $day, $holidays, $currentcal) {

		//Here we generate the first day of the month
		$first_day = mktime(0,0,0,$month, $day, $year) ;

		//This gets us the month name
		$title = date('F', $first_day);
		
		//Here we find out what day of the week the first day of the month falls on
		$day_of_week = date('D', $first_day) ;

		$dayofweek = date('w', mktime(0,0,0,$month, $day, $year));
		$dayofyear = date('z',mktime(0,0,0,$month, $day, $year));
		
		//echo "$dayofweek | $dayofyear : $year-$month-$day";
		//$dayofweek = 4;
		//$tmpdate = date('Y-m-d',$first_day);
		$dayofweek0 = -$dayofweek+0;
		$dayofweek1 = -$dayofweek+1;
		$dayofweek2 = -$dayofweek+2;
		$dayofweek3 = -$dayofweek+3;
		$dayofweek4 = -$dayofweek+4;
		$dayofweek5 = -$dayofweek+5;
		$dayofweek6 = -$dayofweek+6;
		//echo "[$tmpdate -" . $dayofweek1 . " days]<br>";
		
		$sun_date = date('M j', strtotime(date('Y-m-d',$first_day) . " +$dayofweek0 days"));
		$mon_date = date('M j', strtotime(date('Y-m-d',$first_day) . " +$dayofweek1 days"));
		$tue_date = date('M j', strtotime(date('Y-m-d',$first_day) . " +$dayofweek2 days"));
		$wed_date = date('M j', strtotime(date('Y-m-d',$first_day) . " +$dayofweek3 days"));
		$thu_date = date('M j', strtotime(date('Y-m-d',$first_day) . " +$dayofweek4 days"));
		$fri_date = date('M j', strtotime(date('Y-m-d',$first_day) . " +$dayofweek5 days"));
		$sat_date = date('M j', strtotime(date('Y-m-d',$first_day) . " +$dayofweek6 days"));
		//echo date('Y-m-d');
		$sun_hol_date = date('Y-m-d', strtotime(date('Y-m-d',$first_day) . " +$dayofweek0 days"));
		$mon_hol_date = date('Y-m-d', strtotime(date('Y-m-d',$first_day) . " +$dayofweek1 days"));
		$tue_hol_date = date('Y-m-d', strtotime(date('Y-m-d',$first_day) . " +$dayofweek2 days"));
		$wed_hol_date = date('Y-m-d', strtotime(date('Y-m-d',$first_day) . " +$dayofweek3 days"));
		$thu_hol_date = date('Y-m-d', strtotime(date('Y-m-d',$first_day) . " +$dayofweek4 days"));
		$fri_hol_date = date('Y-m-d', strtotime(date('Y-m-d',$first_day) . " +$dayofweek5 days"));
		$sat_hol_date = date('Y-m-d', strtotime(date('Y-m-d',$first_day) . " +$dayofweek6 days"));
		
		list($sun{'y'}, $sun{'m'}, $sun{'d'}) = explode('-', $sun_hol_date);
		list($mon{'y'}, $mon{'m'}, $mon{'d'}) = explode('-', $mon_hol_date);
		list($tue{'y'}, $tue{'m'}, $tue{'d'}) = explode('-', $tue_hol_date);
		list($wed{'y'}, $wed{'m'}, $wed{'d'}) = explode('-', $wed_hol_date);
		list($thu{'y'}, $thu{'m'}, $thu{'d'}) = explode('-', $thu_hol_date);
		list($fri{'y'}, $fri{'m'}, $fri{'d'}) = explode('-', $fri_hol_date);
		list($sat{'y'}, $sat{'m'}, $sat{'d'}) = explode('-', $sat_hol_date);
		
		$prevyear = date('Y', strtotime(date('Y-m-d',$first_day) . " -7 days"));
		$prevmonth = date('m', strtotime(date('Y-m-d',$first_day) . " -7 days"));
		$prevday = date('d', strtotime(date('Y-m-d',$first_day) . " -7 days"));
		
		$nextyear = date('Y', strtotime(date('Y-m-d',$first_day) . " +7 days"));
		$nextmonth = date('m', strtotime(date('Y-m-d',$first_day) . " +7 days"));
		$nextday = date('d', strtotime(date('Y-m-d',$first_day) . " +7 days"));
		
		if (array_key_exists($sun_hol_date, $holidays)) { $sun_holidays = implode("<br>", $holidays[$sun_hol_date]); }
		if (array_key_exists($mon_hol_date, $holidays)) { $mon_holidays = implode("<br>", $holidays[$mon_hol_date]); }
		if (array_key_exists($tue_hol_date, $holidays)) { $tue_holidays = implode("<br>", $holidays[$tue_hol_date]); }
		if (array_key_exists($wed_hol_date, $holidays)) { $wed_holidays = implode("<br>", $holidays[$wed_hol_date]); }
		if (array_key_exists($thu_hol_date, $holidays)) { $thu_holidays = implode("<br>", $holidays[$thu_hol_date]); }
		if (array_key_exists($fri_hol_date, $holidays)) { $fri_holidays = implode("<br>", $holidays[$fri_hol_date]); }
		if (array_key_exists($sat_hol_date, $holidays)) { $sat_holidays = implode("<br>", $holidays[$sat_hol_date]); }
	
		?>
		<table class="calendar" cellpadding="3" cellspacing="0" width="100%" style="background-color: snow;">
			<tr>
				<td class="week_heading"><a href="calendar.php?action=week&year=<?=$prevyear?>&month=<?=$prevmonth?>&day=<?=$prevday?>" style="text-decoration: none; color: white; font-size: 18pt">&#9664;</a></td>
				<td width="14%" class="week_heading" valign="top">Sunday<br><a href="calendar.php?action=day&year=<?=$sun{'y'}?>&month=<?=$sun{'m'}?>&day=<?=$sun{'d'}?>"><span class="week_heading_date"><u><?=$sun_date?></u></span></a></td>
				<td width="14%" class="week_heading" valign="top">Monday<br><a href="calendar.php?action=day&year=<?=$mon{'y'}?>&month=<?=$mon{'m'}?>&day=<?=$mon{'d'}?>"><span class="week_heading_date"><u><?=$mon_date?></u></span></a></td>
				<td width="14%" class="week_heading" valign="top">Tuesday<br><a href="calendar.php?action=day&year=<?=$tue{'y'}?>&month=<?=$tue{'m'}?>&day=<?=$tue{'d'}?>"><span class="week_heading_date"><u><?=$tue_date?></u></span></a></td>
				<td width="14%" class="week_heading" valign="top">Wednesday<br><a href="calendar.php?action=day&year=<?=$wed{'y'}?>&month=<?=$wed{'m'}?>&day=<?=$wed{'d'}?>"><span class="week_heading_date"><u><?=$wed_date?></u></span></a></td>
				<td width="14%" class="week_heading" valign="top">Thursday<br><a href="calendar.php?action=day&year=<?=$thu{'y'}?>&month=<?=$thu{'m'}?>&day=<?=$thu{'d'}?>"><span class="week_heading_date"><u><?=$thu_date?></u></span></a></td>
				<td width="14%" class="week_heading" valign="top">Friday<br><a href="calendar.php?action=day&year=<?=$fri{'y'}?>&month=<?=$fri{'m'}?>&day=<?=$fri{'d'}?>"><span class="week_heading_date"><u><?=$fri_date?></u></span></a></td>
				<td width="14%" class="week_heading" valign="top">Saturday<br><a href="calendar.php?action=day&year=<?=$sat{'y'}?>&month=<?=$sat{'m'}?>&day=<?=$sat{'d'}?>"><span class="week_heading_date"><u><?=$sat_date?></u></span></a></td>
				<td class="week_heading"><a href="calendar.php?action=week&year=<?=$nextyear?>&month=<?=$nextmonth?>&day=<?=$nextday?>" style="text-decoration: none; color: white; font-size: 18pt">&#9654;</a></td>
			</tr>
			<tr>
				<td></td>
				<td height="100%" valign="bottom" style="border-left: 1px solid gray; border-right: 1px solid gray; border-bottom: 1px dashed gray">
					<? if ($sun_holidays != "") { ?>
					<div align="center" class="week_heading_holiday"><?=$sun_holidays?></div>
					<? } ?>
					&nbsp;<a href="calendar_appointments.php?action=addform&currentcal=<?=$currentcal?>&startdate=<?=date('YmdHi', strtotime($sun_hol_date))?>"><img src="images/add12.png" title="Add appointment" border="0"></a>
				</td>
				<td height="100%" valign="bottom" style="border-right: 1px solid gray; border-bottom: 1px dashed gray">
					<? if ($mon_holidays != "") { ?>
					<div align="center" class="week_heading_holiday"><?=$mon_holidays?></div>
					<? } ?>
					&nbsp;<a href="calendar_appointments.php?action=addform&currentcal=<?=$currentcal?>&startdate=<?=date('YmdHi', strtotime($mon_hol_date))?>"><img src="images/add12.png" title="Add appointment" border="0"></a>
				</td>
				<td height="100%" valign="bottom" style="border-right: 1px solid gray; border-bottom: 1px dashed gray">
					<? if ($tue_holidays != "") { ?>
					<div align="center" class="week_heading_holiday"><?=$tue_holidays?></div>
					<? } ?>
					&nbsp;<a href="calendar_appointments.php?action=addform&currentcal=<?=$currentcal?>&startdate=<?=date('YmdHi', strtotime($tue_hol_date))?>"><img src="images/add12.png" title="Add appointment" border="0"></a>
				</td>
				<td height="100%" valign="bottom" style="border-right: 1px solid gray; border-bottom: 1px dashed gray">
					<? if ($wed_holidays != "") { ?>
					<div align="center" class="week_heading_holiday"><?=$wed_holidays?></div>
					<? } ?>
					&nbsp;<a href="calendar_appointments.php?action=addform&currentcal=<?=$currentcal?>&startdate=<?=date('YmdHi', strtotime($wed_hol_date))?>"><img src="images/add12.png" title="Add appointment" border="0"></a>
				</td>
				<td height="100%" valign="bottom" style="border-right: 1px solid gray; border-bottom: 1px dashed gray">
					<? if ($thu_holidays != "") { ?>
					<div align="center" class="week_heading_holiday"><?=$thu_holidays?></div>
					<? } ?>
					&nbsp;<a href="calendar_appointments.php?action=addform&currentcal=<?=$currentcal?>&startdate=<?=date('YmdHi', strtotime($thu_hol_date))?>"><img src="images/add12.png" title="Add appointment" border="0"></a>
				</td>
				<td height="100%" valign="bottom" style="border-right: 1px solid gray; border-bottom: 1px dashed gray">
					<? if ($fri_holidays != "") { ?>
					<div align="center" class="week_heading_holiday"><?=$fri_holidays?></div>
					<? } ?>
					&nbsp;<a href="calendar_appointments.php?action=addform&currentcal=<?=$currentcal?>&startdate=<?=date('YmdHi', strtotime($fri_hol_date))?>"><img src="images/add12.png" title="Add appointment" border="0"></a>
				</td>
				<td height="100%" valign="bottom" style="border-right: 1px solid gray; border-bottom: 1px dashed gray">
					<? if ($sat_holidays != "") { ?>
					<div align="center" class="week_heading_holiday"><?=$sat_holidays?></div>
					<? } ?>
					&nbsp;<a href="calendar_appointments.php?action=addform&currentcal=<?=$currentcal?>&startdate=<?=date('YmdHi', strtotime($sat_hol_date))?>"><img src="images/add12.png" title="Add appointment" border="0"></a>
				</td>
				<td></td>
			</tr>
			<tr>
				<td></td>
				
				<?
					/* loop through each day of the week. The first <td...> is setup inside the switch statement */
					for ($i=0;$i<7;$i++) {
						switch ($i) {
							case 0:
								$startdatetime = date('Y-m-d 00:00:00', strtotime($sun_hol_date));
								$enddatetime = date('Y-m-d 23:59:59', strtotime($sun_hol_date));
								if ($sun_hol_date == date('Y-m-d')) { $bgcolor = "#FFFFAA"; } else { $bgcolor = "white"; }
								?><td valign="top" height="100%" style="border-right: 1px solid gray; border-left: 1px solid gray; background-color: <?=$bgcolor?>"><?
								break;
							case 1:
								$startdatetime = date('Y-m-d 00:00:00', strtotime($mon_hol_date));
								$enddatetime = date('Y-m-d 23:59:59', strtotime($mon_hol_date));
								if ($mon_hol_date == date('Y-m-d')) { $bgcolor = "#FFFFAA"; } else { $bgcolor = "white"; }
								?><td valign="top" height="100%" style="border-right: 1px solid gray; background-color: <?=$bgcolor?>"><?
								break;
							case 2:
								$startdatetime = date('Y-m-d 00:00:00', strtotime($tue_hol_date));
								$enddatetime = date('Y-m-d 23:59:59', strtotime($tue_hol_date));
								if ($tue_hol_date == date('Y-m-d')) { $bgcolor = "#FFFFAA"; } else { $bgcolor = "white"; }
								?><td valign="top" height="100%" style="border-right: 1px solid gray; background-color: <?=$bgcolor?>"><?
								break;
							case 3:
								$startdatetime = date('Y-m-d 00:00:00', strtotime($wed_hol_date));
								$enddatetime = date('Y-m-d 23:59:59', strtotime($wed_hol_date));
								if ($wed_hol_date == date('Y-m-d')) { $bgcolor = "#FFFFAA"; } else { $bgcolor = "white"; }
								?><td valign="top" height="100%" style="border-right: 1px solid gray; background-color: <?=$bgcolor?>"><?
								break;
							case 4:
								$startdatetime = date('Y-m-d 00:00:00', strtotime($thu_hol_date));
								$enddatetime = date('Y-m-d 23:59:59', strtotime($thu_hol_date));
								if ($thu_hol_date == date('Y-m-d')) { $bgcolor = "#FFFFAA"; } else { $bgcolor = "white"; }
								?><td valign="top" height="100%" style="border-right: 1px solid gray; background-color: <?=$bgcolor?>"><?
								break;
							case 5:
								$startdatetime = date('Y-m-d 00:00:00', strtotime($fri_hol_date));
								$enddatetime = date('Y-m-d 23:59:59', strtotime($fri_hol_date));
								if ($fri_hol_date == date('Y-m-d')) { $bgcolor = "#FFFFAA"; } else { $bgcolor = "white"; }
								?><td valign="top" height="100%" style="border-right: 1px solid gray; background-color: <?=$bgcolor?>"><?
								break;
							case 6:
								$startdatetime = date('Y-m-d 00:00:00', strtotime($sat_hol_date));
								$enddatetime = date('Y-m-d 23:59:59', strtotime($sat_hol_date));
								if ($sat_hol_date == date('Y-m-d')) { $bgcolor = "#FFFFAA"; } else { $bgcolor = "white"; }
								?><td valign="top" height="100%" style="border-right: 1px solid gray; background-color: <?=$bgcolor?>"><?
								break;
						}
						
						if ($currentcal == 0) {
							$sqlstring = "select a.*, b.project_name, c.calendar_name from calendar_appointments a left join calendar_projects b on a.appt_projectid = b.project_id left join calendars c on a.appt_calendarid = c.calendar_id where appt_deletedate > now() and appt_canceldate > now() and a.appt_startdate >= '$startdatetime' and a.appt_enddate <= '$enddatetime' order by appt_isalldayevent, appt_startdate";
						}
						else {
							$sqlstring = "select a.*, b.project_name, c.calendar_name from calendar_appointments a left join calendar_projects b on a.appt_projectid = b.project_id left join calendars c on a.appt_calendarid = c.calendar_id where a.appt_calendarid = $currentcal and appt_deletedate > now() and appt_canceldate > now() and a.appt_startdate >= '$startdatetime' and a.appt_enddate <= '$enddatetime' order by appt_isalldayevent, appt_startdate";
						}
						$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$id = $row['appt_id'];
							$username = $row['appt_username'];
							$projectname = $row['project_name'];
							$calendarname = $row['calendar_name'];
							$title = $row['appt_title'];
							$starttime = date('g:i a', strtotime($row['appt_startdate']));
							$endtime = date('g:i a', strtotime($row['appt_enddate']));
							$isallday = $row['appt_isalldayevent'];
							$isrequest = $row['appt_istimerequest'];
							?>
							<?if (!$isallday) { ?>
								<span class="time">&nbsp;<?=$starttime?> - <?=$endtime?>&nbsp;</span><br>
							<? } ?>
							<? if ($isrequest) { ?>
								<span class="timerequest">&nbsp;Time request&nbsp;</span>
							<? } ?>
							<a href="calendar_appointments.php?action=editform&id=<?=$id?>"><span class="appttitle"><u><?=$title?></u></span></a><br>
							<span class="apptowner"><?=$calendarname?> - <b><?=$username?></b></span>
							<br><br>
							<?
						}
					?>
					&nbsp;
				</td>
						<?
					}
				?>
				<td></td>
			</tr>
		</table>
		<?
	}
	
	/* ----------------------------------------------- */
	/* --------- DisplayMonth ------------------------ */
	/* ----------------------------------------------- */
	function DisplayMonth($year, $month, $holidays, $currentcal) {

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

		$prevyear = date('Y', strtotime(date('Y-m-d',$first_day) . " -1 months"));
		$prevmonth = date('m', strtotime(date('Y-m-d',$first_day) . " -1 months"));
		$prevday = 1;
		
		$nextyear = date('Y', strtotime(date('Y-m-d',$first_day) . " +1 months"));
		$nextmonth = date('m', strtotime(date('Y-m-d',$first_day) . " +1 months"));
		$nextday = 1;
		
		//We then determine how many days are in the current month
		//echo "month: $month year: $year<br>";
		$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
		
		$datestart = "$year-$month-1 00:00:00";
		$dateend = "$year-$month-$days_in_month 23:59:59";
		?>
		<table class="calendar" cellpadding="0" cellspacing="0" width="100%" style=" background-color: snow; border: 1px solid #555">
			<tr>
				<td colspan=7 class="heading" style="background-color: #555; padding-top: 10px; padding-bottom: 10px">
					<a href="calendar.php?action=month&year=<?=$prevyear?>&month=<?=$prevmonth?>&day=<?=$prevday?>" style="text-decoration: none; color: white; font-size:16pt">&#9664;</a>
					&nbsp;
					<span style="color: white; font-size:16pt"><?=$title?> <?=$year?></span>
					&nbsp;
					<a href="calendar.php?action=month&year=<?=$nextyear?>&month=<?=$nextmonth?>&day=<?=$nextday?>" style="text-decoration: none; color: white; font-size:16pt">&#9654;</a>
				</td>
			</tr>
			<tr>
				<td width="14.28%" class="days">Sun</td>
				<td width="14.28%" class="days">Mon</td>
				<td width="14.28%" class="days">Tue</td>
				<td width="14.28%" class="days">Wed</td>
				<td width="14.28%" class="days">Thu</td>
				<td width="14.28%" class="days">Fri</td>
				<td width="14.28%" class="days">Sat</td>
			</tr>
		<?
		//This counts the days in the week, up to 7
		$day_count = 1;

		echo "<tr>";
		//first we take care of those blank days
		while ($blank > 0) {
			echo "<td class='day'>&nbsp;</td>";
			$blank = $blank-1;
			$day_count++;
		}
		
		//sets the first day of the month to 1
		$day_num = 1;

		//count up the days, untill we've done all of them in the month
		while ( $day_num <= $days_in_month ) {
			/* get day of year from PHP, then get the number of studies from the DB */
			$thedate = mktime(0,0,0,$month, $day_num, $year) ;
			
			$theholidays = "";

			$hol_date = date('Y-m-d', $thedate);
			if ($hol_date == date('Y-m-d')) {
				$bgcolor = "#FFFFAA";
			}
			else {
				$bgcolor = "white";
			}
			if (array_key_exists($hol_date, $holidays)) { $theholidays = implode("<br>", $holidays[$hol_date]); }

			$startdatetime = "$year-$month-$day_num 00:00:00";
			$enddatetime = "$year-$month-$day_num 23:59:59";
			
			?>
			<td class="day" style="background-color: <?=$bgcolor?>">
				<table width="100%" cellpadding="0" cellspacing="0">
					<tr>
						<td style="background-image: -webkit-gradient(linear, 0% 100%, 100% 100%, from(#454545), to(#CCCCCC)); background-image: -moz-linear-gradient(0% 0% 0deg,#454545, #FFFFFF)">
						<span style="color: white; background-color: #555555; font-weight: bold">&nbsp;<a href="calendar.php?action=day&year=<?=$year?>&month=<?=$month?>&day=<?=$day_num?>" style="color: white; font-size:10pt; text-decoration: none"><?=$day_num?></a>&nbsp;</span>
						<? if ($theholidays != "") { ?>
						<span class="week_heading_holiday"><?=$theholidays?></span>
						<? } ?>
						</td>
					</tr>
				</table>
				<table width="100%" height="100%">
					<tr>
						<td align="left" height="100%">&nbsp;<a href="calendar_appointments.php?action=addform&currentcal=<?=$currentcal?>&startdate=<?=date('YmdHi', $thedate)?>"><img src="images/add12.png" title="Add appointment" border="0"></a>
							<br>
							<?
							if ($currentcal == 0) {
								$sqlstring = "select a.*, b.project_name, c.calendar_name from calendar_appointments a left join calendar_projects b on a.appt_projectid = b.project_id left join calendars c on a.appt_calendarid = c.calendar_id where appt_deletedate > now() and appt_canceldate > now() and a.appt_startdate >= '$startdatetime' and a.appt_enddate <= '$enddatetime' order by appt_isalldayevent, appt_startdate";
							}
							else {
								$sqlstring = "select a.*, b.project_name, c.calendar_name from calendar_appointments a left join calendar_projects b on a.appt_projectid = b.project_id left join calendars c on a.appt_calendarid = c.calendar_id where a.appt_calendarid = $currentcal and appt_deletedate > now() and appt_canceldate > now() and a.appt_startdate >= '$startdatetime' and a.appt_enddate <= '$enddatetime' order by appt_isalldayevent, appt_startdate";
							}
							$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$id = $row['appt_id'];
								$username = $row['appt_username'];
								$projectname = $row['project_name'];
								$calendarname = $row['calendar_name'];
								$title = $row['appt_title'];
								$starttime = date('g:i a', strtotime($row['appt_startdate']));
								$endtime = date('g:i a', strtotime($row['appt_enddate']));
								$isallday = $row['appt_isalldayevent'];
								$isrequest = $row['appt_istimerequest'];
								?>
								<?if (!$isallday) { ?>
									<span class="time">&nbsp;<?=$starttime?> - <?=$endtime?>&nbsp;</span><br>
								<? } ?>
								<? if ($isrequest) { ?>
									<span class="timerequest">&nbsp;Time request&nbsp;</span>
								<? } ?>
								<a href="calendar_appointments.php?action=editform&id=<?=$id?>"><span class="appttitle"><u><?=$title?></u></span></a><br>
								<span class="apptowner"><?=$calendarname?> - <b><?=$username?></b></span>
								<br><br>
								<?
							}
							?>
						</td>
					</tr>
				</table>
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
		//Finaly we finish out the table with some blank details if needed
		while ( $day_count > 1 && $day_count <= 7 ) {
			echo "<td class='day'>&nbsp;</td>";
			$day_count++;
		}

		echo "</tr></table>"; 		
	}

	/* US Holiday Calculations in PHP
	 * Version 1.02
	 * by Dan Kaplan <design@abledesign.com>
	 * Last Modified: April 15, 2001
	 * ------------------------------------------------------------------------
	 * The holiday calculations on this page were assembled for
	 * use in MyCalendar:  http://abledesign.com/programs/MyCalendar/
	 * 
	 * USE THIS LIBRARY AT YOUR OWN RISK; no warranties are expressed or
	 * implied. You may modify the file however you see fit, so long as
	 * you retain this header information and any credits to other sources
	 * throughout the file.  If you make any modifications or improvements,
	 * please send them via email to Dan Kaplan <design@abledesign.com>.
	 * ------------------------------------------------------------------------
	*/

	/* ----------------------------------------------- */
	/* --------- format_date ------------------------- */
	/* ----------------------------------------------- */
	function format_date($year, $month, $day) {
		// pad single digit months/days with a leading zero for consistency (aesthetics)
		// and format the date as desired: YYYY-MM-DD by default

		if (strlen($month) == 1) {
			$month = "0". $month;
		}
		if (strlen($day) == 1) {
			$day = "0". $day;
		}
		$date = $year ."-". $month ."-". $day;
		return $date;
	}

	// the following function get_holiday() is based on the work done by
	// Marcos J. Montes: http://www.smart.net/~mmontes/ushols.html
	//
	// if $week is not passed in, then we are checking for the last week of the month
	function get_holiday($year, $month, $day_of_week, $week="") {
		if ( (($week != "") && (($week > 5) || ($week < 1))) || ($day_of_week > 6) || ($day_of_week < 0) ) {
			// $day_of_week must be between 0 and 6 (Sun=0, ... Sat=6); $week must be between 1 and 5
			return FALSE;
		} else {
			if (!$week || ($week == "")) {
				$lastday = date("t", mktime(0,0,0,$month,1,$year));
				$temp = (date("w",mktime(0,0,0,$month,$lastday,$year)) - $day_of_week) % 7;
			} else {
				$temp = ($day_of_week - date("w",mktime(0,0,0,$month,1,$year))) % 7;
			}
			
			if ($temp < 0) {
				$temp += 7;
			}

			if (!$week || ($week == "")) {
				$day = $lastday - $temp;
			} else {
				$day = (7 * $week) - 6 + $temp;
			}

			return format_date($year, $month, $day);
		}
	}

	function observed_day($year, $month, $day) {
		// sat -> fri & sun -> mon, any exceptions?
		//
		// should check $lastday for bumping forward and $firstday for bumping back,
		// although New Year's & Easter look to be the only holidays that potentially
		// move to a different month, and both are accounted for.

		$dow = date("w", mktime(0, 0, 0, $month, $day, $year));
		
		if ($dow == 0) {
			$dow = $day + 1;
		} elseif ($dow == 6) {
			if (($month == 1) && ($day == 1)) {    // New Year's on a Saturday
				$year--;
				$month = 12;
				$dow = 31;
			} else {
				$dow = $day - 1;
			}
		} else {
			$dow = $day;
		}

		return format_date($year, $month, $dow);
	}

	function calculate_easter($y) {
		// In the text below, 'intval($var1/$var2)' represents an integer division neglecting
		// the remainder, while % is division keeping only the remainder. So 30/7=4, and 30%7=2
		//
		// This algorithm is from Practical Astronomy With Your Calculator, 2nd Edition by Peter
		// Duffett-Smith. It was originally from Butcher's Ecclesiastical Calendar, published in
		// 1876. This algorithm has also been published in the 1922 book General Astronomy by
		// Spencer Jones; in The Journal of the British Astronomical Association (Vol.88, page
		// 91, December 1977); and in Astronomical Algorithms (1991) by Jean Meeus. 

		$a = $y%19;
		$b = intval($y/100);
		$c = $y%100;
		$d = intval($b/4);
		$e = $b%4;
		$f = intval(($b+8)/25);
		$g = intval(($b-$f+1)/3);
		$h = (19*$a+$b-$d-$g+15)%30;
		$i = intval($c/4);
		$k = $c%4;
		$l = (32+2*$e+2*$i-$h-$k)%7;
		$m = intval(($a+11*$h+22*$l)/451);
		$p = ($h+$l-7*$m+114)%31;
		$EasterMonth = intval(($h+$l-7*$m+114)/31);    // [3 = March, 4 = April]
		$EasterDay = $p+1;    // (day in Easter Month)
		
		return format_date($y, $EasterMonth, $EasterDay);
	}
	
	function CalculateHolidays($year) {
		$holidays[($year+1) . "-01-01"][] = "New Year's Day";
		$holidays["$year-01-01"][] = "New Year's Day";
		$holidays[observed_day($year, 1, 1)][] = "New Year's Day (observed)";
		$holidays[get_holiday($year, 1, 1, 3)][] = "Martin Luther King Day";
		$holidays["$year-02-14"][] = "Valentine's Day";
		$holidays[get_holiday($year, 2, 1, 3)][] = "President's Day";
		$holidays["$year-03-17"][] = "St Patrick's Day";
		$holidays[calculate_easter($year)][] = "Easter";
		$holidays[get_holiday($year, 5, 1)][] = "Memorial Day (observed)";
		$holidays["$year-07-04"][] = "Independence Day";
		$holidays[observed_day($year, 7, 4)][] = "Independence Day (observed)";
		$holidays["$year-08-04"][] = "Greg's Birthday";
		$holidays[get_holiday($year, 9, 1, 1)][] = "Labor Day";
		$holidays[get_holiday($year, 10, 1, 2)][] = "Columbus Day";
		$holidays["$year-10-31"][] = "Halloween";
		$holidays[get_holiday($year, 11, 4, 4)][] = "Thanksgiving";
		$holidays["$year-12-24"][] = "Christmas Eve";
		$holidays["$year-12-25"][] = "Christmas";
		$holidays[observed_day($year, 12, 25)][] = "Christmas (observed)";
		$holidays["$year-12-31"][] = "New Year's Eve";
		
		//echo "<li>New Year's Day = ". format_date($year, 1, 1);
		//echo "<br>New Year's Day Observed = ". observed_day($year, 1, 1);
		//echo "<li>Martin Luther King Day Observed (Third Monday in January) = ". get_holiday($year, 1, 1, 3);
		//echo "<li>Valentine's Day = ". format_date($year, 2, 14);
		//echo "<li>President's Day Observed (Third Monday in February) = ". get_holiday($year, 2, 1, 3);
		//echo "<li>St. Patrick's Day = ". format_date($year, 3, 17);
		//echo "<li>Easter = ". calculate_easter($year);
		//echo "<li>Cinco De Mayo = ". format_date($year, 5, 5);
		//echo "<li>Memorial Day Observed (Last Monday in May) = ". get_holiday($year, 5, 1);
		//echo "<li>Independence Day = ". format_date($year, 7, 4);
		//echo "<br>Independence Day Observed = ". observed_day($year, 7, 4);
		//echo "<li>Labor Day Observed (First Monday in September) = ". get_holiday($year, 9, 1, 1);
		//echo "<li>Columbus Day Observed (Second Monday in October) = ". get_holiday($year, 10, 1, 2);
		//echo "<li>Halloween = ". format_date($year, 10, 31);
		// Veteran's Day Observed - November 11th ?
		//echo "<li>Thanksgiving (Fourth Thursday in November) = ". get_holiday($year, 11, 4, 4);
		//echo "<li>Christmas Day = ". format_date($year, 12, 25);
		return $holidays;
	}

?>
	
<? include("footer.php") ?>
