<?
 // ------------------------------------------------------------------------------
 // NiDB calendar.php
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
		<title>NiDB - Calendar</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	//PrintVariable($_POST);
	//PrintVariable($_GET);
	
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
			.time { font-size: 10pt; color: darkred; background-color: lightyellow; }
			.appttitle { font-size: 10pt; color: darkblue; }
			.apptowner { font-size: 8pt; color: #555555; }
			.timerequest { font-size: 8pt; background-color: darkred; color: #FFFFFF; font-variant: small-caps; }
		</style>

		<div class="ui raised segment">
			<div class="ui three column grid">
				<div class="column">
					<div class="ui blue image label">
						<i class="calendar check icon"></i>
						Today
						<div class="ui detail"><?=date('D M j, Y')?></div>
					</div>
					&nbsp; &nbsp; &nbsp; &nbsp; 
					<div class="ui image label">
						<i class="calendar alternate outline icon"></i>
						Calendar date
						<div class="ui detail"><?=date('D M j, Y',$caldate)?></div>
					</div>
				</div>
				<div class="center aligned column">
					<form name="pageform" action="calendar_select.php" method="post" class="ui form">
					<input type="hidden" name="action" value="set">
						<div class="ui labeled input">
							<div class="ui label">
								View
							</div>
							<select name="currentcal" onChange="document.pageform.submit()" class="ui dropdown">
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
							<option value="0" <? if ($currentcal == 0) { echo "selected"; } ?>>All Calendars
							</select>
						</div>
					</form>
				</div>
				<div class="right aligned column">
					<? if ($menuitem == "day") { $class="yellow"; } else { $class=""; } ?>
					<a class="ui big <?=$class?> label" href="calendar.php?action=day&year=<?=$year?>&month=<?=$month?>&day=<?=$day?>"><i class="calendar icon"></i>Day</a>
					<? if ($menuitem == "week") { $class="yellow"; } else { $class=""; } ?>
					<a class="ui big <?=$class?> label" href="calendar.php?action=week&year=<?=$year?>&month=<?=$month?>&day=<?=$day?>"><i class="calendar outline icon"></i>Week</a>
					<? if ($menuitem == "month") { $class="yellow"; } else { $class=""; } ?>
					<a class="ui big <?=$class?> label" href="calendar.php?action=month&year=<?=$year?>&month=<?=$month?>&day=<?=$day?>"><i class="calendar alternate outline icon"></i>Month</a>
				</div>
			</div>
		</div>
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
	/* --------- CalendarAppointmentOverlapWhere ------ */
	/* ----------------------------------------------- */
	function CalendarAppointmentOverlapWhere($startdatetime, $enddatetime) {
		return "a.appt_startdate <= '$enddatetime' and a.appt_enddate >= '$startdatetime'";
	}


	/* ----------------------------------------------- */
	/* --------- CalendarAppointmentTimeLabel --------- */
	/* ----------------------------------------------- */
	function CalendarAppointmentTimeLabel($displaydatetime, $apptstartdate, $apptenddate, $isallday) {
		$displaydate = date('Y-m-d', strtotime($displaydatetime));
		$startdate = date('Y-m-d', strtotime($apptstartdate));
		$enddate = date('Y-m-d', strtotime($apptenddate));

		if ($startdate == $enddate) {
			if ($isallday) {
				return "";
			}
			return date('g:ia', strtotime($apptstartdate)) . " - " . date('g:ia', strtotime($apptenddate));
		}

		if ($isallday) {
			return "Multi-day appt";
		}
		if ($displaydate == $startdate) {
			return date('g:ia', strtotime($apptstartdate)) . " ...";
		}
		if ($displaydate == $enddate) {
			return "... " . date('g:ia', strtotime($apptenddate));
		}
		return "Multi-day appt";
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
		<div class="ui text container">
			<div class="ui center aligned top attached grey inverted segment">
				<div class="ui three column grid">
					<div class="column">
						<a href="calendar.php?action=day&year=<?=$prevyear?>&month=<?=$prevmonth?>&day=<?=$prevday?>"><i class="ui inverted big arrow alternate circle left icon"></i></a>
					</div>
					<div class="column">
						<span style="color: white; font-size:16pt"><?=$today?></span>
					</div>
					<div class="column">
						<a href="calendar.php?action=day&year=<?=$nextyear?>&month=<?=$nextmonth?>&day=<?=$nextday?>"><i class="ui inverted big arrow alternate circle right icon"></i></a>
					</div>
				</div>
			</div>
			<div class="ui attached segment">
				<?
				$startdatetime = date('Y-m-d 00:00:00', mktime(0,0,0,$month, $day, $year));
				$enddatetime = date('Y-m-d 23:59:59', mktime(0,0,0,$month, $day, $year));
				?>
				<a href="calendar_appointments.php?action=addform&currentcal=<?=$currentcal?>&startdate=<?=date('YmdHi', strtotime($startdatetime))?>"><i class="orange calendar plus icon" title="Create appointment"></i> Create Appointment</a>
			</div>
			<div class="ui bottom attached segment">
				<?
				$apptDateWhere = CalendarAppointmentOverlapWhere($startdatetime, $enddatetime);
				if ($currentcal == 0) {
					$sqlstring = "select a.*, b.project_name, c.calendar_name from calendar_appointments a left join calendar_projects b on a.appt_projectid = b.project_id left join calendars c on a.appt_calendarid = c.calendar_id where appt_deletedate > now() and appt_canceldate > now() and $apptDateWhere order by appt_isalldayevent, appt_startdate";
				}
				else {
					$sqlstring = "select a.*, b.project_name, c.calendar_name from calendar_appointments a left join calendar_projects b on a.appt_projectid = b.project_id left join calendars c on a.appt_calendarid = c.calendar_id where a.appt_calendarid = $currentcal and appt_deletedate > now() and appt_canceldate > now() and $apptDateWhere order by appt_isalldayevent, appt_startdate";
				}
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$id = $row['appt_id'];
					$username = $row['appt_username'];
					$projectname = $row['project_name'];
					$calendarname = $row['calendar_name'];
					$title = $row['appt_title'];
					$details = $row['appt_details'];
					$isallday = $row['appt_isalldayevent'];
					$isrequest = $row['appt_istimerequest'];
					$timelabel = CalendarAppointmentTimeLabel($startdatetime, $row['appt_startdate'], $row['appt_enddate'], $isallday);
					?>
					<div class="ui blue segment">
						<?if ($timelabel != "") { ?>
							<span class="ui small yellow label">&nbsp;<?=$timelabel?>&nbsp;</span> &nbsp;
						<? } ?>
						<? if ($isrequest) { ?>
							<span class="ui small red label">&nbsp;Time request&nbsp;</span>
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
					</div>
					<?
				}
				?>
			</div>
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
		
		list($sun['y'], $sun['m'], $sun['d']) = explode('-', $sun_hol_date);
		list($mon['y'], $mon['m'], $mon['d']) = explode('-', $mon_hol_date);
		list($tue['y'], $tue['m'], $tue['d']) = explode('-', $tue_hol_date);
		list($wed['y'], $wed['m'], $wed['d']) = explode('-', $wed_hol_date);
		list($thu['y'], $thu['m'], $thu['d']) = explode('-', $thu_hol_date);
		list($fri['y'], $fri['m'], $fri['d']) = explode('-', $fri_hol_date);
		list($sat['y'], $sat['m'], $sat['d']) = explode('-', $sat_hol_date);
		
		$prevyear = date('Y', strtotime(date('Y-m-d',$first_day) . " -7 days"));
		$prevmonth = date('m', strtotime(date('Y-m-d',$first_day) . " -7 days"));
		$prevday = date('d', strtotime(date('Y-m-d',$first_day) . " -7 days"));
		
		$nextyear = date('Y', strtotime(date('Y-m-d',$first_day) . " +7 days"));
		$nextmonth = date('m', strtotime(date('Y-m-d',$first_day) . " +7 days"));
		$nextday = date('d', strtotime(date('Y-m-d',$first_day) . " +7 days"));
		
		$sun_holidays = $mon_holidays = $tue_holidays = $wed_holidays = $thu_holidays = $fri_holidays = $sat_holidays = "";
		if (array_key_exists($sun_hol_date, $holidays)) { $sun_holidays = implode("<br>", $holidays[$sun_hol_date]); }
		if (array_key_exists($mon_hol_date, $holidays)) { $mon_holidays = implode("<br>", $holidays[$mon_hol_date]); }
		if (array_key_exists($tue_hol_date, $holidays)) { $tue_holidays = implode("<br>", $holidays[$tue_hol_date]); }
		if (array_key_exists($wed_hol_date, $holidays)) { $wed_holidays = implode("<br>", $holidays[$wed_hol_date]); }
		if (array_key_exists($thu_hol_date, $holidays)) { $thu_holidays = implode("<br>", $holidays[$thu_hol_date]); }
		if (array_key_exists($fri_hol_date, $holidays)) { $fri_holidays = implode("<br>", $holidays[$fri_hol_date]); }
		if (array_key_exists($sat_hol_date, $holidays)) { $sat_holidays = implode("<br>", $holidays[$sat_hol_date]); }
	
		$weekdays = array(
			array('name' => 'Sunday', 'date' => $sun_hol_date, 'label' => $sun_date, 'parts' => $sun, 'holidays' => $sun_holidays),
			array('name' => 'Monday', 'date' => $mon_hol_date, 'label' => $mon_date, 'parts' => $mon, 'holidays' => $mon_holidays),
			array('name' => 'Tuesday', 'date' => $tue_hol_date, 'label' => $tue_date, 'parts' => $tue, 'holidays' => $tue_holidays),
			array('name' => 'Wednesday', 'date' => $wed_hol_date, 'label' => $wed_date, 'parts' => $wed, 'holidays' => $wed_holidays),
			array('name' => 'Thursday', 'date' => $thu_hol_date, 'label' => $thu_date, 'parts' => $thu, 'holidays' => $thu_holidays),
			array('name' => 'Friday', 'date' => $fri_hol_date, 'label' => $fri_date, 'parts' => $fri, 'holidays' => $fri_holidays),
			array('name' => 'Saturday', 'date' => $sat_hol_date, 'label' => $sat_date, 'parts' => $sat, 'holidays' => $sat_holidays)
		);
		$slotheight = 30;
		$dayheight = $slotheight * 48;
		$weekscrolltop = $slotheight * 16;
		$nowlinetop = ((date('G')*60) + date('i')) * ($slotheight/30);
	
		?>
			<style>
				.weekcal { --weekcal-scrollbar-width: 17px; display: grid; grid-template-columns: 56px repeat(7, minmax(110px, 1fr)) 56px; border: 1px solid #dadce0; border-radius: 6px; background: white; }
				.weekcalbody { grid-column: 1 / -1; display: grid; grid-template-columns: 56px repeat(7, minmax(110px, 1fr)) calc(56px - var(--weekcal-scrollbar-width)); max-height: 58vh; overflow-y: auto; overflow-x: hidden; position: relative; }
				.weekcalnav { display: flex; justify-content: center; align-items: center; min-height: 78px; border-right: 1px solid #dadce0; border-bottom: 1px solid #dadce0; background: #fff; }
				.weekcalhead { min-height: 78px; padding: 8px; border-right: 1px solid #dadce0; border-bottom: 1px solid #dadce0; background: #fff; position: relative; }
				.weekcalhead.today { background: #fcf8cc; }
				.weekcaldayname { color: #5f6368; font-size: 9pt; text-transform: uppercase; }
				.weekcaldate { font-size: 14pt; font-weight: bold; margin-top: 2px; }
				.weekcaladd { position: absolute; top: 8px; right: 8px; }
				.weekcalholiday { margin-top: 4px; font-size: 8pt; color: #9f3a38; line-height: 1.2; }
				.weekcaltimes { grid-column: 1; position: relative; height: <?=$dayheight?>px; border-right: 1px solid #dadce0; background: #fff; }
				.weekcaltime { position: absolute; right: 6px; transform: translateY(-50%); color: #70757a; font-size: 8pt; white-space: nowrap; }
				.weekcalcol { position: relative; height: <?=$dayheight?>px; border-right: 1px solid #dadce0; background: repeating-linear-gradient(to bottom, #e8eaed 0, #e8eaed 1px, transparent 1px, transparent <?=$slotheight?>px); }
				.weekcalcol.today { background: repeating-linear-gradient(to bottom, #e8eaed 0, #e8eaed 1px, #fcf8cc 1px, #fcf8cc <?=$slotheight?>px); }
				.weekcalappt { position: absolute; z-index: 2; overflow: hidden; box-sizing: border-box; padding: 3px 5px; border-radius: 4px; background: #386eaf; color: white; font-size: 8pt; line-height: 1.2; box-shadow: 0 2px 8px rgba(0,0,0,0.35); }
				.weekcalappt a, .weekcalappt a:visited { color: white; text-decoration: none; }
				.weekcalappt .meta { opacity: 0.9; font-size: 7pt; }
				.weekcalrequest { background: #bf4a4a; border: 1px dashed #fff; }
				.weekcalallday { background: #f2711c; }
				.weekcalbodyspacer { height: <?=$dayheight?>px; border-left: 1px solid #dadce0; background: #fff; }
				.weekcalnowline { position: absolute; left: 56px; right: calc(56px - var(--weekcal-scrollbar-width)); height: 2px; background: #87db8c; z-index: 2; pointer-events: none; }
			</style>
			<script type="text/javascript">
				$(document).ready(function() {
					var weekbody = $(".weekcalbody");
					var scrollbarwidth = weekbody[0].offsetWidth - weekbody[0].clientWidth;
					$(".weekcal").css("--weekcal-scrollbar-width", scrollbarwidth + "px");
					weekbody.scrollTop(<?=$weekscrolltop?>);
				});
			</script>
			<div class="weekcal">
				<div class="weekcalnav">
					<a href="calendar.php?action=week&year=<?=$prevyear?>&month=<?=$prevmonth?>&day=<?=$prevday?>"><i class="big black arrow alternate circle left icon" title="Previous week"></i></a>
				</div>
				<?
				foreach ($weekdays as $weekday) {
					$todayclass = ($weekday['date'] == date('Y-m-d')) ? "today" : "";
					?>
					<div class="weekcalhead <?=$todayclass?>">
						<a class="weekcaladd" href="calendar_appointments.php?action=addform&currentcal=<?=$currentcal?>&startdate=<?=date('YmdHi', strtotime($weekday['date']))?>"><i class="plus square icon" title="Create appointment"></i></a>
						<div class="weekcaldayname"><?=$weekday['name']?></div>
						<div class="weekcaldate"><a href="calendar.php?action=day&year=<?=$weekday['parts']['y']?>&month=<?=$weekday['parts']['m']?>&day=<?=$weekday['parts']['d']?>"><?=$weekday['label']?></a></div>
						<? if ($weekday['holidays'] != "") { ?><div class="weekcalholiday"><?=$weekday['holidays']?></div><? } ?>
					</div>
					<?
				}
				?>
				<div class="weekcalnav">
					<a href="calendar.php?action=week&year=<?=$nextyear?>&month=<?=$nextmonth?>&day=<?=$nextday?>"><i class="big black arrow alternate circle right icon" title="Next week"></i></a>
				</div>
				<div class="weekcalbody">
				<div class="weekcalnowline" style="top: <?=$nowlinetop?>px"></div>
				<div class="weekcaltimes">
					<?
					for ($i=0;$i<=48;$i++) {
						$minutes = $i * 30;
						if ($minutes == 1440) {
							$timelabel = "Midnight";
						}
						else {
							$timelabel = date('g:ia', strtotime("midnight +$minutes minutes"));
						}
						$top = $i * $slotheight;
						?><div class="weekcaltime" style="top: <?=$top?>px"><?=$timelabel?></div><?
					}
					?>
				</div>
				<?
				foreach ($weekdays as $weekday) {
					$startdatetime = date('Y-m-d 00:00:00', strtotime($weekday['date']));
					$enddatetime = date('Y-m-d 23:59:59', strtotime($weekday['date']));
					$todayclass = ($weekday['date'] == date('Y-m-d')) ? "today" : "";
					?>
					<div class="weekcalcol <?=$todayclass?>">
					<?
						$apptDateWhere = CalendarAppointmentOverlapWhere($startdatetime, $enddatetime);
						if ($currentcal == 0) {
							$sqlstring = "select a.*, b.project_name, c.calendar_name from calendar_appointments a left join calendar_projects b on a.appt_projectid = b.project_id left join calendars c on a.appt_calendarid = c.calendar_id where appt_deletedate > now() and appt_canceldate > now() and $apptDateWhere order by appt_isalldayevent, appt_startdate";
						}
						else {
							$sqlstring = "select a.*, b.project_name, c.calendar_name from calendar_appointments a left join calendar_projects b on a.appt_projectid = b.project_id left join calendars c on a.appt_calendarid = c.calendar_id where a.appt_calendarid = $currentcal and appt_deletedate > now() and appt_canceldate > now() and $apptDateWhere order by appt_isalldayevent, appt_startdate";
						}
						$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
						$appts = array();
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$id = $row['appt_id'];
							$username = $row['appt_username'];
							$projectname = $row['project_name'];
							$calendarname = $row['calendar_name'];
							$title = $row['appt_title'];
							$isallday = $row['appt_isalldayevent'];
							$isrequest = $row['appt_istimerequest'];
							$timelabel = CalendarAppointmentTimeLabel($startdatetime, $row['appt_startdate'], $row['appt_enddate'], $isallday);
							if ($isallday) {
								$visibleStart = strtotime($startdatetime);
								$visibleEnd = strtotime($enddatetime) + 1;
							}
							else {
								$visibleStart = max(strtotime($row['appt_startdate']), strtotime($startdatetime));
								$visibleEnd = min(strtotime($row['appt_enddate']), strtotime($enddatetime) + 1);
							}
							$top = floor(($visibleStart - strtotime($startdatetime))/60);
							$height = max(20, floor(($visibleEnd - $visibleStart)/60));
							$apptclass = "";
							if ($isrequest) { $apptclass .= " weekcalrequest"; }
							if ($isallday) { $apptclass .= " weekcalallday"; }
							$appts[] = array(
								'id' => $id,
								'username' => $username,
								'calendarname' => $calendarname,
								'title' => $title,
								'timelabel' => $timelabel,
								'top' => $top,
								'height' => $height,
								'start' => $top,
								'end' => $top + $height,
								'class' => $apptclass,
								'column' => 0,
								'columns' => 1
							);
						}

						$group = array();
						$groupend = -1;
						for ($apptnum=0;$apptnum<count($appts);$apptnum++) {
							if ((count($group) > 0) && ($appts[$apptnum]['start'] >= $groupend)) {
								$columnends = array();
								$groupcolumns = 0;
								foreach ($group as $groupapptnum) {
									$column = 0;
									while ((array_key_exists($column, $columnends)) && ($columnends[$column] > $appts[$groupapptnum]['start'])) {
										$column++;
									}
									$appts[$groupapptnum]['column'] = $column;
									$columnends[$column] = $appts[$groupapptnum]['end'];
									if ($column + 1 > $groupcolumns) { $groupcolumns = $column + 1; }
								}
								foreach ($group as $groupapptnum) {
									$appts[$groupapptnum]['columns'] = $groupcolumns;
								}
								$group = array();
								$groupend = -1;
							}
							$group[] = $apptnum;
							if ($appts[$apptnum]['end'] > $groupend) { $groupend = $appts[$apptnum]['end']; }
						}
						if (count($group) > 0) {
							$columnends = array();
							$groupcolumns = 0;
							foreach ($group as $groupapptnum) {
								$column = 0;
								while ((array_key_exists($column, $columnends)) && ($columnends[$column] > $appts[$groupapptnum]['start'])) {
									$column++;
								}
								$appts[$groupapptnum]['column'] = $column;
								$columnends[$column] = $appts[$groupapptnum]['end'];
								if ($column + 1 > $groupcolumns) { $groupcolumns = $column + 1; }
							}
							foreach ($group as $groupapptnum) {
								$appts[$groupapptnum]['columns'] = $groupcolumns;
							}
						}

						foreach ($appts as $appt) {
							$width = 100/$appt['columns'];
							$left = $appt['column']*$width;
							$width = $width - 1;
							?>
							<div class="weekcalappt<?=$appt['class']?>" style="top: <?=$appt['top']?>px; height: <?=$appt['height']?>px; left: <?=$left?>%; width: <?=$width?>%">
								<a href="calendar_appointments.php?action=editform&id=<?=$appt['id']?>"><b><?=$appt['title']?></b></a>
								<?if ($appt['timelabel'] != "") { ?><div><?=$appt['timelabel']?></div><? } ?>
								<div class="meta"><?=$appt['calendarname']?> - <?=$appt['username']?></div>
							</div>
							<?
						}
					?>
					</div>
					<?
				}
				?>
				<div class="weekcalbodyspacer"></div>
				</div>
			</div>
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
		<div class="ui center aligned secondary inverted segment">
			<div class="ui three column grid">
				<div class="left aligned column">
					<a href="calendar.php?action=month&year=<?=$prevyear?>&month=<?=$prevmonth?>&day=<?=$prevday?>"><i class="big inverted arrow alternate circle left icon"></i></a>
				</div>
				<div class="column">
					<h2 class="ui inverted header"><?=$title?> <?=$year?></h2>
				</div>
				<div class="right aligned column">
					<a href="calendar.php?action=month&year=<?=$nextyear?>&month=<?=$nextmonth?>&day=<?=$nextday?>"><i class="big inverted arrow alternate circle right icon"></i></a>
				</div>
			</div>
		</div>
		<div class="ui seven column very compact grid">
			<div class="column">
				<div class="ui center aligned segment">Sunday</div>
			</div>
			<div class="column">
				<div class="ui center aligned segment">Monday</div>
			</div>
			<div class="column">
				<div class="ui center aligned segment">Tuesday</div>
			</div>
			<div class="column">
				<div class="ui center aligned segment">Wednesday</div>
			</div>
			<div class="column">
				<div class="ui center aligned segment">Thursday</div>
			</div>
			<div class="column">
				<div class="ui center aligned segment">Friday</div>
			</div>
			<div class="column">
				<div class="ui center aligned segment">Saturday</div>
			</div>
		<!--</div>
		<br>-->
		
		<!--<table class="calendar" cellpadding="0" cellspacing="0" width="100%" style=" background-color: snow; border: 1px solid #555">
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
			</tr>-->
		<?
		//This counts the days in the week, up to 7
		$day_count = 1;

		//echo "<tr>";
		//first we take care of those blank days
		while ($blank > 0) {
			echo "<div class='column'>&nbsp;</div>";
			$blank = $blank-1;
			$day_count++;
		}
		
		//sets the first day of the month to 1
		$day_num = 1;

		//count up the days, untill we've done all of them in the month
		while ( $day_num <= $days_in_month ) {
			/* get day of year from PHP, then get the number of studies from the DB */
			$thedate = mktime(0,0,0,$month, $day_num, $year) ;
			
			$theholidays = array();

			$hol_date = date('Y-m-d', $thedate);
			if ($hol_date == date('Y-m-d')) {
				$bgcolor = "#FFFFAA";
			}
			else {
				$bgcolor = "white";
			}
			if (array_key_exists($hol_date, $holidays)) {
				$holidaystr = implode("<br>", $holidays[$hol_date]);
			}
			else
				$holidaystr = "";

			$startdatetime = "$year-$month-$day_num 00:00:00";
			$enddatetime = "$year-$month-$day_num 23:59:59";
			
			?>
			<div class="column">
			<!--<td class="day" style="background-color: <?=$bgcolor?>">-->
				<div class="ui styled blue segment" style="padding: 3px; height:100%; background-color: <?=$bgcolor?>">

					<h3 class="ui header">
						<a href="calendar_appointments.php?action=addform&currentcal=<?=$currentcal?>&startdate=<?=date('YmdHi', $thedate)?>"><i class="small grey calendar plus icon" title="Create appointment"></i></a>
						<div class="content">
							<a href="calendar.php?action=day&year=<?=$year?>&month=<?=$month?>&day=<?=$day_num?>" title="View day"><?=$day_num?></a>
							<? if ($holidaystr != "") { ?>
							<div class="sub header"><?=$holidaystr?></div>
							<? } ?>
						</div>
					</h3>
							<br>
							<?
							$apptDateWhere = CalendarAppointmentOverlapWhere($startdatetime, $enddatetime);
							if ($currentcal == 0) {
								$sqlstring = "select a.*, b.project_name, c.calendar_name from calendar_appointments a left join calendar_projects b on a.appt_projectid = b.project_id left join calendars c on a.appt_calendarid = c.calendar_id where appt_deletedate > now() and appt_canceldate > now() and $apptDateWhere order by appt_isalldayevent, appt_startdate";
							}
							else {
								$sqlstring = "select a.*, b.project_name, c.calendar_name from calendar_appointments a left join calendar_projects b on a.appt_projectid = b.project_id left join calendars c on a.appt_calendarid = c.calendar_id where a.appt_calendarid = $currentcal and appt_deletedate > now() and appt_canceldate > now() and $apptDateWhere order by appt_isalldayevent, appt_startdate";
							}
							$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$id = $row['appt_id'];
								$username = $row['appt_username'];
								$projectname = $row['project_name'];
								$calendarname = $row['calendar_name'];
								$title = $row['appt_title'];
								$isallday = $row['appt_isalldayevent'];
								$isrequest = $row['appt_istimerequest'];
								$timelabel = CalendarAppointmentTimeLabel($startdatetime, $row['appt_startdate'], $row['appt_enddate'], $isallday);
								?>
								<?if ($timelabel != "") { ?>
									<div class="ui orange label">&nbsp;<?=$timelabel?>&nbsp;</div><br>
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
				</div>
			</div>
			<?
			$day_num++;
			$day_count++;

			//Make sure we start a new row every week
			if ($day_count > 7) {
				//echo "</tr><tr>";
				$day_count = 1;
			}
		} 		
		//Finaly we finish out the table with some blank details if needed
		while ( $day_count > 1 && $day_count <= 7 ) {
			echo "<div class=\"column\">&nbsp;</div>";
			$day_count++;
		}

		echo "</div>";
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
		$holidays[get_holiday($year, 10, 1, 2)][] = "Indigenous People's Day";
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
