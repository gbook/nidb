<?
 // ------------------------------------------------------------------------------
 // NiDB calendar.php
 // Copyright (C) 2004 - 2023
 // Gregory A Book <gregory.book@hhchealth.org> <greg@squirrelresearch.com>
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
	
	ob_start(); /* buffer output for POST/Redirect/GET (see functions.php RedirectTo/ShowFlashMessage) */
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

	$currentcal = (int)($_COOKIE['currentcal'] ?? 0);

	//PrintVariable($_POST);
	//exit(0);
	
	/* ----- setup variables ----- */
	$action = GetSafeAction(array("add", "edit", "editall", "resize", "delete", "deleteall", "cancel", "cancelall")); /* mutating actions require POST + CSRF token */
	$id = (int)GetVariable("id");
	$calendarid = (int)GetVariable("calendarid");
	$projectid = (int)GetVariable("projectid");
	$details = GetVariable("details");
	$title = GetVariable("title");
	$startdate = GetVariable("startdate");
	$starttime = GetVariable("starttime");
	$enddate = GetVariable("enddate");
	$endtime = GetVariable("endtime");
	$isalldayevent = GetVariable("isalldayevent");
	$istimerequest = GetVariable("istimerequest");
	$cancelreason = GetVariable("cancelreason");
	$notifyusers = GetVariable("notifyusers");
	$repeats = GetVariable("repeats");
	$repeattype = GetVariable("repeattype");
	$repeatsun = GetVariable("repeatsun");
	$repeatmon = GetVariable("repeatmon");
	$repeattue = GetVariable("repeattue");
	$repeatwed = GetVariable("repeatwed");
	$repeatthu = GetVariable("repeatthu");
	$repeatfri = GetVariable("repeatfri");
	$repeatsat = GetVariable("repeatsat");
	$repeatenddate = GetVariable("repeatenddate");
	$repeatdayofmonth = (int)GetVariable("repeatdayofmonth");
	$groupid = (int)GetVariable("groupid");
	
	$startdatetime = "$startdate $starttime";
	$enddatetime = "$enddate $endtime";
	
	//echo "Action: $action<br><br>";
	//exit(0);
	
	if ($isalldayevent == "yes") { $isalldayevent = "1"; } else { $isalldayevent = "0"; }
	if ($istimerequest == "yes") { $istimerequest = "1"; } else { $istimerequest = "0"; }

	// default project ID, for now
	if ($projectid == "") { $projectid = 0; }
	
	/* check the action */
	if ($action == "addform") {
		DisplayForm("", "", "", $username, $calendarid, $projectid, $details, $title, $startdatetime, $enddatetime, $isalldayevent, $istimerequest, $currentcal, $repeats, $repeattype, $repeatsun, $repeatmon, $repeattue, $repeatwed, $repeatthu, $repeatfri, $repeatsat, $repeatenddate, $repeatdayofmonth);
	}
	/* mutating actions use POST/Redirect/GET (to calendar.php) so a refresh/Back doesn't re-submit */
	elseif ($action == "add") {
		Add("add", "", "", $username, $calendarid, $projectid, $details, $title, $startdatetime, $enddatetime, $isalldayevent, $istimerequest, $currentcal, $repeats, $repeattype, $repeatsun, $repeatmon, $repeattue, $repeatwed, $repeatthu, $repeatfri, $repeatsat, $repeatenddate, $repeatdayofmonth, 0);
	}
	elseif ($action == "editform") {
		DisplayForm($id, "", "", "", "", "", "", "", "", "", "", "", $currentcal, $repeats, $repeattype, $repeatsun, $repeatmon, $repeattue, $repeatwed, $repeatthu, $repeatfri, $repeatsat, $repeatenddate, $repeatdayofmonth);
	}
	elseif ($action == "edit") {
		Add("update", $id, $groupid, $username, $calendarid, $projectid, $details, $title, $startdatetime, $enddatetime, $isalldayevent, $istimerequest, $currentcal, $repeats, $repeattype, $repeatsun, $repeatmon, $repeattue, $repeatwed, $repeatthu, $repeatfri, $repeatsat, $repeatenddate, $repeatdayofmonth, 0);
	}
	elseif ($action == "editall") {
		Add("update", $id, $groupid, $username, $calendarid, $projectid, $details, $title, $startdatetime, $enddatetime, $isalldayevent, $istimerequest, $currentcal, $repeats, $repeattype, $repeatsun, $repeatmon, $repeattue, $repeatwed, $repeatthu, $repeatfri, $repeatsat, $repeatenddate, $repeatdayofmonth, 1);
	}
	elseif ($action == "resize") {
		Resize($id, GetVariable("edge"), GetVariable("date"), GetVariable("minutes"));
	}
	elseif ($action == "delete") {
		ob_start();
		Delete($id, $currentcal, 0);
		$_SESSION['flash'] = ob_get_clean();
		RedirectTo("calendar.php");
	}
	elseif ($action == "deleteall") {
		ob_start();
		Delete($id, $currentcal, 1);
		$_SESSION['flash'] = ob_get_clean();
		RedirectTo("calendar.php");
	}
	elseif ($action == "cancel") {
		ob_start();
		Cancel($id, 0, $currentcal, $cancelreason, $notifyusers, 0);
		$_SESSION['flash'] = ob_get_clean();
		RedirectTo("calendar.php");
	}
	elseif ($action == "cancelall") {
		ob_start();
		Cancel($id, $groupid, $currentcal, $cancelreason, $notifyusers, 1);
		$_SESSION['flash'] = ob_get_clean();
		RedirectTo("calendar.php");
	}
	elseif (($action == "") || ($action == "list")) {
		//DisplayList();
		echo "Nothing to do";
	}
	
	
	/* ----------------------------------------------- */
	/* --------- Add --------------------------------- */
	/* ----------------------------------------------- */
	function Add($method, $id, $groupid, $username, $calendarid, $projectid, $details, $title, $startdatetime, $enddatetime, $isalldayevent, $istimerequest, $currentcal, $repeats, $repeattype, $repeatsun, $repeatmon, $repeattue, $repeatwed, $repeatthu, $repeatfri, $repeatsat, $repeatenddate, $repeatdayofmonth, $editall) {
		
		/* an update changes one existing appointment (or, for editall, the shared fields of its series).
		   Members of a series are edited with their date fields disabled, so nothing is posted for them:
		   keep the stored dates, and take the group id from the database rather than the request */
		$isseriesmember = false;
		if ($method != "add") {
			$id = (int)$id;
			$sqlstring = "select appt_startdate, appt_enddate, appt_groupid, (select count(*) from calendar_appointments g where g.appt_groupid = a.appt_groupid and a.appt_groupid > 0 and g.appt_deletedate > now() and g.appt_canceldate > now()) 'groupsize' from calendar_appointments a where appt_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $id);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($id));
			$existing = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			if (!$existing) { Error("Appointment not found"); return; }
			$groupid = (int)$existing['appt_groupid'];
			if ((int)$existing['groupsize'] > 1) {
				$isseriesmember = true;
				$startdatetime = $existing['appt_startdate'];
				$enddatetime = $existing['appt_enddate'];
			}
			$repeats = false; /* "Repeats" only creates a series when adding */
		}
		
		/* a validation failure redisplays the form with what was entered: still as an edit form when editing, so a
		   resubmit updates the appointment instead of adding a copy. Nothing is written until every check passes */
		$redisplay = function($message) use ($method, $id, $username, $calendarid, $projectid, $details, $title, &$startdatetime, &$enddatetime, $isalldayevent, $istimerequest, $currentcal, $repeats, $repeattype, $repeatsun, $repeatmon, $repeattue, $repeatwed, $repeatthu, $repeatfri, $repeatsat, $repeatenddate, $repeatdayofmonth) {
			DisplayForm(($method == "add") ? "" : $id, $message, "", $username, $calendarid, $projectid, $details, $title, $startdatetime, $enddatetime, $isalldayevent, $istimerequest, $currentcal, $repeats, $repeattype, $repeatsun, $repeatmon, $repeattue, $repeatwed, $repeatthu, $repeatfri, $repeatsat, $repeatenddate, $repeatdayofmonth);
		};

		if ($title == "") { $redisplay("'Title' is blank"); return; }
		if ($calendarid == 0) { $redisplay("Calendar ID is blank"); return; }
		if (!strtotime($startdatetime)) { $redisplay("'Start date/time' is invalid"); return; }
		if (!strtotime($enddatetime)) { $redisplay("'End date/time' is invalid"); return; }
		if ($repeats) {
			if (!in_array($repeattype, array("daily", "weekly", "monthly"), true)) { $redisplay("Choose how often the appointment repeats"); return; }
			if (($repeattype == "monthly") && (($repeatdayofmonth < 1) || ($repeatdayofmonth > 31))) { $redisplay("Choose the day of the month for a monthly appointment"); return; }
			if (($repeattype == "weekly") && !($repeatsun || $repeatmon || $repeattue || $repeatwed || $repeatthu || $repeatfri || $repeatsat)) { $redisplay("Choose at least one day of the week for a weekly appointment"); return; }
			if (!strtotime($repeatenddate ?? '') || (date('Y-m-d', strtotime($repeatenddate)) < date('Y-m-d', strtotime($startdatetime)))) { $redisplay("'Until' must be a date on or after the start date"); return; }
		}
		
		$calendarid = (int)$calendarid;
		$projectid = (int)$projectid;
		$id = (int)$id;
		$groupid = (int)$groupid;
		$isalldayevent = (int)$isalldayevent;
		$istimerequest = (int)$istimerequest;
		$startdatetimes = array();
		$enddatetimes = array();
		$appts = array();

		/* check if this appointment repeats (adding only - see above) */
		if (!$repeats) {
			$startdatetimes[0] = date('Y-m-d H:i:s',strtotime($startdatetime));
			$enddatetimes[0] = date('Y-m-d H:i:s',strtotime($enddatetime));
		}
		else {
			/* get all dates on which the repeating appt falls */
			
			/* determine # of days between starting and ending date */
			$totaltimediff = strtotime($repeatenddate) - strtotime($startdatetime) + 86400;

			if ($repeattype == "monthly") {
				/* the chosen day of each month from the start month through the until date. Months without that day
				   (the 31st in a 30-day month, the 29th-31st in February) are skipped rather than moved to another date */
				$firstofmonth = date('Y-m-01', strtotime($startdatetime));
				$until = date('Y-m-d', strtotime($repeatenddate));
				for ($n = 0; count($appts) < 100; $n++) {
					$monthts = strtotime("$firstofmonth +$n months");
					if (date('Y-m-01', $monthts) > $until) { break; }
					if ($repeatdayofmonth <= (int)date('t', $monthts)) {
						$appts[] = date('Y-m-', $monthts) . sprintf('%02d', $repeatdayofmonth);
					}
				}
			}
			else {
				/* for daily, add the current date first. all dates are added inside the weekly case below */
				if ($repeattype == "daily") {
					$appts[] = date('Y-m-d H:i:s',strtotime($startdatetime));
				}
				/* add interval until date difference between starting and new appt is greater than ending date */
				$numappts = 1;
				$lastappt = $startdatetime;
				$done = false;
				while (!$done) {
					switch ($repeattype) {
						case "daily":
							$newappt = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s',strtotime($lastappt)) . " +1 days"));
							$appts[] = $newappt;
							break;
						case "weekly":
							if ($repeatsun) { $appts[] = date('Y-m-d H:i:s', strtotime("Sunday", strtotime($lastappt))); }
							if ($repeatmon) { $appts[] = date('Y-m-d H:i:s', strtotime("Monday", strtotime($lastappt))); }
							if ($repeattue) { $appts[] = date('Y-m-d H:i:s', strtotime("Tuesday", strtotime($lastappt))); }
							if ($repeatwed) { $appts[] = date('Y-m-d H:i:s', strtotime("Wednesday", strtotime($lastappt))); }
							if ($repeatthu) { $appts[] = date('Y-m-d H:i:s', strtotime("Thursday", strtotime($lastappt))); }
							if ($repeatfri) { $appts[] = date('Y-m-d H:i:s', strtotime("Friday", strtotime($lastappt))); }
							if ($repeatsat) { $appts[] = date('Y-m-d H:i:s', strtotime("Saturday", strtotime($lastappt))); }
							$newappt = date('Y-m-d H:i:s', strtotime($lastappt . " +1 weeks"));
							break;
					}
				
					$newtimediff = strtotime($newappt) - strtotime($startdatetime);
				
					if ($newtimediff > $totaltimediff) {
						break;
					}
					$numappts++;
					if ($numappts > 100) {
						$done = true;
					}
					$lastappt = $newappt;
				}
			}
			
			/* sort the appointment list, give each the original times, and drop any outside the start..until range */
			sort($appts);
			$k=0;
			for ($i=0;$i<count($appts);$i++) {
				$appts[$i] = date('Y-m-d', strtotime($appts[$i])) . " " . date('H:i:s', strtotime($startdatetime));
				$newtimediff = strtotime($appts[$i]) - strtotime($startdatetime);
				if (($newtimediff >= 0) && ($newtimediff < $totaltimediff)) {
					$startdatetimes[$k] = date('Y-m-d H:i:s', strtotime($appts[$i]));
					$enddatetimes[$k] = date('Y-m-d ', strtotime($appts[$i])) . date('H:i:s', strtotime($enddatetime));
					$k++;
				}
			}
			if (count($startdatetimes) == 0) { $redisplay("No occurrences fall between the start date and the 'Until' date"); return; }
		}
		
		/* check every occurrence before writing any of them, so a failure part way through a series doesn't leave half of it added */
		for ($i=0;$i<count($startdatetimes);$i++) {
		
			/* if this is an all day appointment, ignore any times that were entered */
			if ($isalldayevent) {
				$startdatetimes[$i] = date('Y-m-d 00:00:00',strtotime($startdatetimes[$i]));
				$enddatetimes[$i] = date('Y-m-d 00:00:00',strtotime($enddatetimes[$i]));
				if ($enddatetimes[$i] < $startdatetimes[$i]) { $redisplay("The end date must be on or after the start date"); return; }
				continue;
			}
			$startdatetimes[$i] = date('Y-m-d H:i:s',strtotime($startdatetimes[$i]));
			$enddatetimes[$i] = date('Y-m-d H:i:s',strtotime($enddatetimes[$i]));
			if (strtotime($enddatetimes[$i]) <= strtotime($startdatetimes[$i])) { $redisplay("The end time must be after the start time"); return; }
			if ($istimerequest) { continue; }

			/* check to see if this appointment overlaps an existing appt on the same calendar */
			$sqlstring = "select * from calendar_appointments where ( (appt_startdate > ? and appt_startdate < ?) or (appt_enddate > ? and appt_enddate < ?) or (appt_startdate = ? and appt_enddate = ?) or (appt_startdate < ? and appt_enddate > ?) or (appt_startdate < ? and appt_enddate > ?) ) and appt_calendarid = ? and appt_deletedate > now() and appt_canceldate > now() and appt_id != ? and appt_istimerequest <> 1";
			$excludeid = ($method == "add") ? 0 : $id; /* when updating, don't count the appointment itself */
			$s = $startdatetimes[$i];
			$e = $enddatetimes[$i];
			$params = array($s, $e, $s, $e, $s, $e, $s, $s, $e, $e, $calendarid, $excludeid);
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'ssssssssssii', ...$params);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
			$overlap = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			if ($overlap) {
				$which = (count($startdatetimes) > 1) ? "The occurrence on " . date('D M j, Y', strtotime($s)) : "This appointment";
				$redisplay("<b>$which overlaps with an existing appointment</b><br>[" . htmlspecialchars($overlap['appt_title'] . ": " . $overlap['appt_startdate'] . " - " . $overlap['appt_enddate']) . "]");
				return;
			}
		}

		/* everything checked out: write to the database */
		if (($method != "add") && $editall && ($groupid < 1)) {
			/* group 0 means "not in a series", so never act on all of those at once */
			$redisplay("This appointment is not part of a series"); return;
		}
		for ($i=0;$i<count($startdatetimes);$i++) {
			$startdatetime = $startdatetimes[$i];
			$enddatetime = $enddatetimes[$i];
			if ($method == "add") {
				$sqlstring = "insert into calendar_appointments (appt_username, appt_calendarid, appt_projectid, appt_title, appt_details, appt_startdate, appt_enddate, appt_isalldayevent, appt_istimerequest) values (?, ?, ?, ?, ?, ?, ?, ?, ?)";
				$params = array($username, $calendarid, $projectid, $title, $details, $startdatetime, $enddatetime, $isalldayevent, $istimerequest);
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'siissssii', ...$params);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
				$newid = mysqli_insert_id($GLOBALS['linki']);
				mysqli_stmt_close($stmt);
				/* a repeating appointment's occurrences form a series: they all get the first occurrence's id
				   as their group id. A single appointment keeps group 0 ("not in a series") */
				if (count($startdatetimes) > 1) {
					if ($i == 0) $groupid = $newid;
					$sqlstring = "update calendar_appointments set appt_groupid = ? where appt_id = ?";
					$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
					mysqli_stmt_bind_param($stmt, 'ii', $groupid, $newid);
					MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($groupid, $newid));
					mysqli_stmt_close($stmt);
				}
				
			}
			else {
				/* appt_username (who created the appointment) is never changed by an edit */
				if ($editall) {
					$sqlstring = "update calendar_appointments set appt_calendarid = ?, appt_projectid = ?, appt_title = ?, appt_details = ?, appt_isalldayevent = ?, appt_istimerequest = ? where appt_groupid = ?";
					$types = 'iissiii';
					$params = array($calendarid, $projectid, $title, $details, $isalldayevent, $istimerequest, $groupid);
				}
				elseif ($isseriesmember) {
					/* one occurrence of a series: its dates aren't editable here */
					$sqlstring = "update calendar_appointments set appt_calendarid = ?, appt_projectid = ?, appt_title = ?, appt_details = ?, appt_isalldayevent = ?, appt_istimerequest = ? where appt_id = ?";
					$types = 'iissiii';
					$params = array($calendarid, $projectid, $title, $details, $isalldayevent, $istimerequest, $id);
				}
				else {
					$sqlstring = "update calendar_appointments set appt_calendarid = ?, appt_projectid = ?, appt_title = ?, appt_details = ?, appt_startdate = ?, appt_enddate = ?, appt_isalldayevent = ?, appt_istimerequest = ? where appt_id = ?";
					$types = 'iissssiii';
					$params = array($calendarid, $projectid, $title, $details, $startdatetime, $enddatetime, $isalldayevent, $istimerequest, $id);
				}
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, $types, ...$params);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
				mysqli_stmt_close($stmt);
			}
		}
		/* success: redirect to the calendar (POST/Redirect/GET). Validation failures above re-render the form instead */
		ob_start();
		if ($method == "add") { Notice("Appointment added"); } else { Notice("Appointment updated"); }
		$_SESSION['flash'] = ob_get_clean();
		RedirectTo("calendar.php");
	}


	/* ----------------------------------------------- */
	/* --------- Resize ------------------------------ */
	/* ----------------------------------------------- */
	/* change the start or end time of one appointment (dragging its top or bottom edge in calendar.php's day/week grid), or move it
	   keeping its length (dragging its body). $edge is "start", "end" or "move", $date the grid day the drag ended on, $minutes the
	   minutes past its midnight (15-min steps, 0..1425 for a start or move, up to 1440 = midnight for an end). For a move, $minutes is
	   the new start. Same rules as the edit form: no all-day appointments or series members, no overlaps */
	function Resize($id, $edge, $date, $minutes) {
		$id = (int)$id;
		$minutes = (int)$minutes;

		ob_start();
		/* back to the day that was being viewed, whether or not the change works */
		$redirect = "calendar.php";
		if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date ?? '') && strtotime($date)) {
			$redirect = "calendar.php?year=" . date('Y', strtotime($date)) . "&month=" . date('m', strtotime($date)) . "&day=" . date('d', strtotime($date));
		}
		$sqlstring = "select appt_calendarid, appt_title, appt_startdate, appt_enddate, appt_isalldayevent, appt_istimerequest, (select count(*) from calendar_appointments g where g.appt_groupid = a.appt_groupid and a.appt_groupid > 0 and g.appt_deletedate > now() and g.appt_canceldate > now()) 'groupsize' from calendar_appointments a where appt_id = ? and appt_deletedate > now() and appt_canceldate > now()";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($id));
		$appt = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		if (!$appt) {
			Error("Appointment not found");
		}
		elseif (!in_array($edge, array("start", "end", "move"), true) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date ?? '') || !strtotime($date) || ($minutes < 0) || ($minutes > (($edge == "end") ? 1440 : 1425)) || ($minutes % 15 != 0)) {
			Error("Invalid " . (($edge == "end") ? "end" : "start") . " time");
		}
		elseif ($appt['appt_isalldayevent']) {
			Error("All-day appointments can't be " . (($edge == "move") ? "moved" : "resized"));
		}
		elseif ((int)$appt['groupsize'] > 1) {
			Error("Appointments in a series can't be " . (($edge == "move") ? "moved" : "resized") . ". Edit the series instead");
		}
		else {
			/* build the wall-clock time from the minutes (not midnight + seconds, which is an hour off on daylight saving days) */
			if ($minutes == 1440) { $newdatetime = date('Y-m-d 00:00:00', strtotime("$date +1 day")); }
			else { $newdatetime = sprintf("%s %02d:%02d:00", $date, intdiv($minutes, 60), $minutes % 60); }
			if ($edge == "move") {
				$startdatetime = $newdatetime;
				$enddatetime = date('Y-m-d H:i:s', strtotime($newdatetime) + (strtotime($appt['appt_enddate']) - strtotime($appt['appt_startdate'])));
			}
			else {
				$startdatetime = ($edge == "start") ? $newdatetime : $appt['appt_startdate'];
				$enddatetime = ($edge == "end") ? $newdatetime : $appt['appt_enddate'];
			}
			$calendarid = (int)$appt['appt_calendarid'];

			$overlap = null;
			if (!$appt['appt_istimerequest']) {
				/* same overlap check as Add() */
				$sqlstring = "select * from calendar_appointments where ( (appt_startdate > ? and appt_startdate < ?) or (appt_enddate > ? and appt_enddate < ?) or (appt_startdate = ? and appt_enddate = ?) or (appt_startdate < ? and appt_enddate > ?) or (appt_startdate < ? and appt_enddate > ?) ) and appt_calendarid = ? and appt_deletedate > now() and appt_canceldate > now() and appt_id != ? and appt_istimerequest <> 1";
				$params = array($startdatetime, $enddatetime, $startdatetime, $enddatetime, $startdatetime, $enddatetime, $startdatetime, $startdatetime, $enddatetime, $enddatetime, $calendarid, $id);
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'ssssssssssii', ...$params);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
				$overlap = mysqli_fetch_array($result, MYSQLI_ASSOC);
				mysqli_stmt_close($stmt);
			}

			if (strtotime($enddatetime) <= strtotime($startdatetime)) {
				Error("The end time must be after the start time");
			}
			elseif ($overlap) {
				Error("<b>The new " . (($edge == "move") ? "time" : "$edge time") . " overlaps with an existing appointment</b><br>[" . htmlspecialchars($overlap['appt_title'] . ": " . $overlap['appt_startdate'] . " - " . $overlap['appt_enddate']) . "]");
			}
			else {
				/* appt_username (who created the appointment) is never changed by an edit */
				$sqlstring = "update calendar_appointments set appt_startdate = ?, appt_enddate = ? where appt_id = ?";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'ssi', $startdatetime, $enddatetime, $id);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($startdatetime, $enddatetime, $id));
				mysqli_stmt_close($stmt);
				if ($edge == "move") {
					Notice("'" . htmlspecialchars($appt['appt_title'] ?? '') . "' moved to " . date('D M j, g:ia', strtotime($startdatetime)) . " - " . date('g:ia', strtotime($enddatetime)));
				}
				else {
					Notice("'" . htmlspecialchars($appt['appt_title'] ?? '') . "' now " . (($edge == "start") ? "starts" : "ends") . " at " . date('g:ia', strtotime($newdatetime)));
				}
			}
		}
		$_SESSION['flash'] = ob_get_clean();
		RedirectTo($redirect);
	}


	/* ----------------------------------------------- */
	/* --------- Delete ------------------------------ */
	/* ----------------------------------------------- */
	function Delete($id, $currentcal, $deleteall) {
		$id = (int)$id;
		$sqlstring = "select appt_groupid from calendar_appointments where appt_id = ? and appt_deletedate > now()";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($id));
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$row) { Error("Appointment not found, or already deleted"); return; }
		if ($deleteall) {
			/* delete the series the appointment belongs to (its stored group id, not the request's, like Cancel()).
			   Group 0 means "not in a series", so never act on all of those at once. Members deleted earlier keep their date */
			$target = (int)$row['appt_groupid'];
			if ($target < 1) { Error("This appointment is not part of a series"); return; }
			$sqlstring = "update calendar_appointments set appt_deletedate = now() where appt_groupid = ? and appt_deletedate > now()";
		}
		else {
			$target = $id;
			$sqlstring = "update calendar_appointments set appt_deletedate = now() where appt_id = ?";
		}
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $target);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($target));
		mysqli_stmt_close($stmt);
		Notice($deleteall ? "Series deleted" : "Appointment deleted");
	}	


	/* ----------------------------------------------- */
	/* --------- Cancel ------------------------------ */
	/* ----------------------------------------------- */
	function Cancel($id, $groupid, $currentcal, $cancelreason, $notifyusers, $cancelall) {
		/* get appointment info before changing it */
		$id = (int)$id;
		$sqlstring = "select a.*, b.calendar_name, c.project_name from calendar_appointments a left join calendars b on a.appt_calendarid = b.calendar_id left join calendar_projects c on a.appt_projectid = c.project_id where a.appt_id = ? and a.appt_deletedate > now()";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($id));
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$row) { Error("Appointment not found"); return; }
		if (!$cancelall && (strtotime($row['appt_canceldate']) <= time())) { Error("This appointment was already cancelled"); return; }
		$calid = $row['appt_calendarid'];
		$prjid = $row['appt_projectid'];
		$startdate = date('M j, Y', strtotime($row['appt_startdate']));
		$enddate   = date('M j, Y', strtotime($row['appt_enddate']));
		$appttitle = $row['appt_title'];
		$calendar = $row['calendar_name'];
		$starttime = date('g:i a', strtotime($row['appt_startdate']));
		$endtime   = date('g:i a', strtotime($row['appt_enddate']));

		if ($cancelall) {
			/* cancel the series the appointment belongs to (its stored group id, not the request's).
			   Group 0 means "not in a series", so never act on all of those at once */
			$target = (int)$row['appt_groupid'];
			if ($target < 1) { Error("This appointment is not part of a series"); return; }
			$sqlstring = "update calendar_appointments set appt_canceldate = now() where appt_groupid = ? and appt_canceldate > now() and appt_deletedate > now()"; /* earlier cancellations keep their date */
		}
		else {
			$target = $id;
			$sqlstring = "update calendar_appointments set appt_canceldate = now() where appt_id = ?";
		}
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $target);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($target));
		mysqli_stmt_close($stmt);
		
		/* send an email if necessary */
		if ($notifyusers != "") {
			/* SendEmail() sends HTML, so escape the user-entered parts */
			$body = "The appointment titled '" . htmlspecialchars($appttitle ?? '') . "' scheduled from $starttime-$endtime on $startdate has been cancelled from the " . htmlspecialchars($calendar ?? '') . " calendar because " . htmlspecialchars($cancelreason ?? '');
			$subject = "$calendar cancellation $startdate";
			
			/* get a list of users who care about this calendar and send an email */
			$recipients = array();
			$calid = (int)$calid;
			$sqlstring = "select b.user_email 'email' from calendar_notifications a left join users b on a.not_userid = b.user_id where not_calendarid = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $calid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($calid));
			mysqli_stmt_close($stmt);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$recipients[] = $row['email'];
			}
			Sendmail($body, $recipients, $subject);
			
			/* get a list of users who care about this project and send an email */
		}
		Notice($cancelall ? "Series cancelled" : "Appointment cancelled");
	}	
	
	
	/* ----------------------------------------------- */
	/* --------- Sendmail ---------------------------- */
	/* ----------------------------------------------- */
	/* send the notification to each recipient through the site's SMTP server (SendEmail() in
	   functions.php). This used to use PHPMailer with hard-coded Gmail settings */
	function Sendmail($body, $recipients, $subject) {
		$body = preg_replace('/[\\\\]/','',$body);
		$sent = 0;
		$failed = array();
		foreach ($recipients as $email) {
			if (trim($email ?? '') == '') continue;
			if (SendEmail($email, $subject, $body, 0))
				$sent++;
			else
				$failed[] = $email;
		}
		if (count($failed) > 0) {
			echo "Unable to send email to: " . htmlspecialchars(implode(", ", $failed));
		} else {
			echo "Emails sent!";
		}
	}

	
	/* ----------------------------------------------- */
	/* --------- DisplayTimeOptions ------------------ */
	/* ----------------------------------------------- */
	function DisplayTimeOptions($selectedtime) {
		$hours = array(12,1,2,3,4,5,6,7,8,9,10,11);
		foreach (array("am","pm") as $ampm) {
			foreach ($hours as $hr) {
				foreach (array("00","15","30","45") as $min) {
					$time = "$hr:$min$ampm";
					if ($selectedtime == $time) { $selected = "selected"; } else { $selected = ""; }
					if ($min == "00") { $style = "color: black; background-color: lightyellow"; } else { $style = "color: gray"; }
					?>
					<option value="<?=$time?>" style="<?=$style?>" <?=$selected?>><?=$time?></option>
					<?
				}
			}
		}
	}


	/* ----------------------------------------------- */
	/* --------- DisplayForm ------------------------- */
	/* ----------------------------------------------- */
	function DisplayForm($id, $message, $groupid, $username, $calendarid, $projectid, $details, $title, $startdatetime, $enddatetime, $isalldayevent, $istimerequest, $currentcal, $repeats, $repeattype, $repeatsun, $repeatmon, $repeattue, $repeatwed, $repeatthu, $repeatfri, $repeatsat, $repeatenddate, $repeatdayofmonth = 0) {

		//echo "[$id]";
		
		/* repeat choices from a redisplayed add form */
		$apptrepeatscheck = ($repeats == "yes") ? "checked" : "";
		$repeatdays = array("sun" => $repeatsun, "mon" => $repeatmon, "tue" => $repeattue, "wed" => $repeatwed, "thu" => $repeatthu, "fri" => $repeatfri, "sat" => $repeatsat);
		$repeats = false; /* from here on: is the appointment being edited part of a series */
		$passed = array('calendarid' => $calendarid, 'projectid' => $projectid, 'title' => $title, 'details' => $details, 'isalldayevent' => $isalldayevent, 'istimerequest' => $istimerequest);
		/* compare as int: the add form passes $id = "", and on PHP 8 "" != 0 is true, which sent new appointments down the edit path */
		if ((int)$id > 0) {
			$type = "edit";
			$pagetitle = "Edit Appointment";
			$submitbutton = "Update";
			
			$id = (int)$id;
			$sqlstring = "select * from calendar_appointments where appt_id = $id and appt_deletedate > now()"; /* $id is cast to int above */
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			if (!$row) { Error("Appointment not found"); return; }
			$groupid = $row['appt_groupid'];
			$username = $row['appt_username'];
			$calendarid = $row['appt_calendarid'];
			$projectid = $row['appt_projectid'];
			$title = $row['appt_title'];
			$startdate = date('Y-m-d', strtotime($row['appt_startdate']));
			$enddate = date('Y-m-d', strtotime($row['appt_enddate']));
			$starttime = date('g:ia', strtotime($row['appt_startdate']));
			$endtime = date('g:ia', strtotime($row['appt_enddate']));
			$isallday = $row['appt_isalldayevent'];
			$isrequest = $row['appt_istimerequest'];
			$details = $row['appt_details'];

			if ($groupid != 0) {
				$groupid = (int)$groupid;
				$sqlstring = "select appt_id from calendar_appointments where appt_groupid = $groupid and appt_deletedate > now() and appt_canceldate > now()"; /* database integer; live members only */
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 1) {
					$pagetitle = "Edit Recurring Appointment";
					$repeats = true;
				}
			}

			/* redisplayed after a failed update: show what was entered rather than what's stored (a series member's dates aren't editable) */
			if ($message != "") {
				$calendarid = $passed['calendarid'];
				$projectid = $passed['projectid'];
				$title = $passed['title'];
				$details = $passed['details'];
				$isallday = $passed['isalldayevent'];
				$isrequest = $passed['istimerequest'];
				if (!$repeats && strtotime($startdatetime ?? '') && strtotime($enddatetime ?? '')) {
					$startdate = date('Y-m-d', strtotime($startdatetime));
					$starttime = date('g:ia', strtotime($startdatetime));
					$enddate = date('Y-m-d', strtotime($enddatetime));
					$endtime = date('g:ia', strtotime($enddatetime));
				}
			}
			
			if ($isallday == "1") { $isalldayeventcheck = "checked"; } else { $isalldayeventcheck = ""; }
			if ($isrequest == "1") { $istimerequestcheck = "checked"; } else { $istimerequestcheck = ""; }
		}
		else {
			$type = "add";
			$pagetitle = "Add Appointment";
			$submitbutton = "Add";
			$startdate = date('Y-m-d',strtotime($startdatetime));
			$enddate = date('Y-m-d',strtotime($startdatetime));
			$starttime = "12:00pm";
			$endtime = "1:00pm";
			/* a start time was passed in (clicked time slot in calendar.php as "g:ia", or a form redisplay as either "g:ia" or "H:i:s"), snapped to the 15-min options; end defaults to +1 hour if none was passed */
			if (preg_match('/\d{1,2}:\d{2}/', $startdatetime ?? '') && strtotime($startdatetime)) {
				$startts = strtotime($startdatetime);
				$startts = $startts - ($startts % 900);
				$startdate = date('Y-m-d', $startts);
				$starttime = date('g:ia', $startts);
				if (preg_match('/\d{1,2}:\d{2}/', $enddatetime ?? '') && strtotime($enddatetime)) { /* kept as entered, even if before the start, so a "must be after" error shows the values that caused it */
					$endts = strtotime($enddatetime);
				}
				else {
					$endts = $startts + 3600;
				}
				$endts = $endts - ($endts % 900);
				$enddate = date('Y-m-d', $endts);
				$endtime = date('g:ia', $endts);
			}
			$calendarid = $currentcal;
			if ($isalldayevent == "1") { $isalldayeventcheck = "checked"; } else { $isalldayeventcheck = ""; }
			if ($istimerequest == "1") { $istimerequestcheck = "checked"; } else { $istimerequestcheck = ""; }
			//echo "CurrentCal: $currentcal<br>";
		}
		
	?>
		<div class="ui text container">
			<div class="ui attached visible message">
				<a href="calendar.php" class="ui right floated small button"><i class="arrow alternate circle left icon"></i> Back to calendar</a>
				<div class="header"><?=$pagetitle?></div>
			</div>
			<? if ($message != "") { ?>
			<div class="ui attached negative message">
				<?=$message?>
			</div>
			<? } ?>

			<form action="calendar_appointments.php" method="post" id="form1" name="form1" class="ui form attached fluid segment">
			<input type="hidden" name="action" value="<?=$type?>">
			<input type="hidden" name="id" value="<?=$id?>">
			<input type="hidden" name="groupid" value="<?=$groupid?>">
			<?=CSRFTokenField()?>
			
			<h3 class="ui dividing header">Appointment</h3>
			<div class="required field">
				<label>Title</label>
				<input type="text" name="title" value="<?=htmlspecialchars($title ?? '')?>" required>
			</div>

			<div class="two fields">
				<div class="field">
					<label>Start</label>
					<div class="two fields">
						<div class="field">
							<input type="date" name="startdate" value="<?=$startdate?>" placeholder="YYYY-MM-DD" <? if ($repeats) { echo "disabled"; } ?>>
						</div>
						<div class="field">
							<select class="ui dropdown" name="starttime" <? if ($repeats) { echo "disabled"; } ?>>
								<? DisplayTimeOptions($starttime); ?>
							</select>
						</div>
					</div>
				</div>
				<div class="field">
					<label>End</label>
					<div class="two fields">
						<div class="field">
							<input type="date" name="enddate" value="<?=$enddate?>" placeholder="YYYY-MM-DD" <? if ($repeats) { echo "disabled"; } ?>>
						</div>
						<div class="field">
							<select class="ui dropdown" name="endtime" <? if ($repeats) { echo "disabled"; } ?>>
								<? DisplayTimeOptions($endtime); ?>
							</select>
						</div>
					</div>
				</div>
			</div>

			<div class="two fields">
				<div class="field">
					<label>Calendar</label>
					<select name="calendarid" class="ui dropdown">
					<?
						$sqlstring = "select calendar_id, calendar_name from calendars where calendar_deletedate > now()";
						$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$calid = $row['calendar_id'];
							$name = $row['calendar_name'];
							if ($calid == $calendarid) { $selected = "selected"; } else { $selected = ""; }
					?>
						<option value="<?=(int)$calid?>" <?=$selected?>><?=htmlspecialchars($name ?? '')?></option>
					<?
						}
					?>
					</select>
				</div>
				<div class="field">
					<label>Project</label>
					<select name="projectid" class="ui dropdown">
					<?
						$sqlstring = "select project_id, project_name from calendar_projects where project_enddate > now()";
						$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$prjid = $row['project_id'];
							$name = $row['project_name'];
							if ($prjid == $projectid) { $selected = "selected"; } else { $selected = ""; }
					?>
						<option value="<?=(int)$prjid?>" <?=$selected?>><?=htmlspecialchars($name ?? '')?></option>
					<?
						}
					?>
					</select>
				</div>
			</div>

			<div class="grouped fields">
				<label>Options</label>
				<div class="field">
					<div class="ui checkbox">
						<input type="checkbox" name="isalldayevent" value="yes" <?=$isalldayeventcheck?>>
						<label>All day event</label>
					</div>
				</div>
				<div class="field">
					<div class="ui checkbox">
						<input type="checkbox" name="istimerequest" value="yes" <?=$istimerequestcheck?>>
						<label>Time Request ONLY</label>
					</div>
				</div>
			</div>

			<div class="field">
				<label>Details</label>
				<textarea name="details" rows="4"><?=htmlspecialchars($details ?? '')?></textarea>
			</div>

			<? if ($type == "add") { ?>
			<h3 class="ui dividing header">Recurring Appointment</h3>
			<div class="field">
				<div class="ui checkbox">
					<input type="checkbox" name="repeats" value="yes" <?=$apptrepeatscheck?>>
					<label>Appointment repeats?</label>
				</div>
			</div>
			<div class="grouped fields">
				<label>Frequency</label>
				<div class="field">
					<div class="ui radio checkbox">
						<input type="radio" name="repeattype" value="daily" <?=($repeattype == "daily") ? "checked" : ""?>>
						<label>Daily</label>
					</div>
				</div>
				<div class="field">
					<div class="ui radio checkbox">
						<input type="radio" name="repeattype" value="weekly" <?=($repeattype == "weekly") ? "checked" : ""?>>
						<label>Weekly on</label>
					</div>
				</div>
				<div class="inline fields">
					<div class="field">
						<div class="ui checkbox"><input type="checkbox" name="repeatsun" value="1" <?=$repeatdays['sun'] ? "checked" : ""?>><label>S</label></div>
					</div>
					<div class="field">
						<div class="ui checkbox"><input type="checkbox" name="repeatmon" value="1" <?=$repeatdays['mon'] ? "checked" : ""?>><label>M</label></div>
					</div>
					<div class="field">
						<div class="ui checkbox"><input type="checkbox" name="repeattue" value="1" <?=$repeatdays['tue'] ? "checked" : ""?>><label>T</label></div>
					</div>
					<div class="field">
						<div class="ui checkbox"><input type="checkbox" name="repeatwed" value="1" <?=$repeatdays['wed'] ? "checked" : ""?>><label>W</label></div>
					</div>
					<div class="field">
						<div class="ui checkbox"><input type="checkbox" name="repeatthu" value="1" <?=$repeatdays['thu'] ? "checked" : ""?>><label>T</label></div>
					</div>
					<div class="field">
						<div class="ui checkbox"><input type="checkbox" name="repeatfri" value="1" <?=$repeatdays['fri'] ? "checked" : ""?>><label>F</label></div>
					</div>
					<div class="field">
						<div class="ui checkbox"><input type="checkbox" name="repeatsat" value="1" <?=$repeatdays['sat'] ? "checked" : ""?>><label>S</label></div>
					</div>
				</div>
				<div class="inline fields">
					<div class="field">
						<div class="ui radio checkbox">
							<input type="radio" name="repeattype" value="monthly" <?=($repeattype == "monthly") ? "checked" : ""?>>
							<label>Monthly on day</label>
						</div>
					</div>
					<div class="field">
						<select name="repeatdayofmonth" class="ui dropdown">
						<?
							$selecteddom = ((int)$repeatdayofmonth > 0) ? (int)$repeatdayofmonth : (int)date('j', strtotime($startdate));
							for ($i=1;$i<=31;$i++) {
								?><option value="<?=$i?>" <?=($i == $selecteddom) ? "selected" : ""?>><?=$i?></option><?
							}
						?>
						</select>
					</div>
					<div class="field">of each month</div>
				</div>
			</div>
			<div class="field">
				<label>Until</label>
				<input type="text" name="repeatenddate" value="<?=htmlspecialchars(($repeatenddate ?? '') != '' ? $repeatenddate : $enddate)?>" placeholder="YYYY-MM-DD">
			</div>
			<? } ?>

			<div class="field" align="right">
			<?	if ($repeats) { ?>
				<input type="submit" value="Update this only" name="submit" onClick="document.form1.elements['action'].value = 'edit';" class="ui primary button">
				<input type="submit" value="Update series" name="submit" onClick="document.form1.elements['action'].value = 'editall';" class="ui primary button">
			<?	} else { ?>
				<input type="submit" value="<?=$submitbutton?>" name="submit" class="ui primary button">
			<?	} ?>
			</div>
			</form>
		</div>
		<br><br>
		<? if ($type == "edit") { ?>
			<div class="ui text container">
				<div class="ui attached visible message">
					<div class="header">Cancel or Delete</div>
					<p>Cancel only because the subject couldn't/didn't show. Delete otherwise.</p>
				</div>
				<form action="calendar_appointments.php" method="post" id="form2" name="form2" class="ui form attached fluid segment">
				<input type="hidden" name="action" value="cancel">
				<input type="hidden" name="id" value="<?=$id?>">
				<input type="hidden" name="groupid" value="<?=$groupid?>">
			<?=CSRFTokenField()?>
				<div class="field">
					<label>Cancellation reason</label>
					<input type="text" name="cancelreason">
				</div>
				<div class="field">
					<div class="ui checkbox">
						<input type="checkbox" name="notifyusers">
						<label>Notify calendar users</label>
					</div>
				</div>
				<div class="field" align="right">
					<input type="submit" value="Cancel this only" onClick="document.form2.elements['action'].value = 'cancel'; return confirm('You\'re sure you want to cancel this appointment?');" class="ui red button">
					<?	if ($repeats) { ?>
					<input type="submit" value="Cancel series" onClick="document.form2.elements['action'].value = 'cancelall'; return confirm('You\'re sure you want to cancel this recurring appointment?');" class="ui red button">
					<? } ?>
				</div>
				</form>
				<form action="calendar_appointments.php" method="post" id="form4" name="form4" class="ui form attached fluid segment">
				<input type="hidden" name="action" value="delete">
				<input type="hidden" name="id" value="<?=$id?>">
				<input type="hidden" name="groupid" value="<?=$groupid?>">
			<?=CSRFTokenField()?>
				<div class="field" align="right">
					<input type="submit" value="Delete this only" onClick="document.form4.elements['action'].value = 'delete'; return confirm('You\'re for serious. You want to delete this appointment?');" class="ui red button">
					<?	if ($repeats) { ?>
					<input type="submit" value="Delete series" onClick="document.form4.elements['action'].value = 'deleteall'; return confirm('You\'re for serious. You want to delete this recurring appointment?');" class="ui red button">
					<? } ?>
				</div>
				</form>
			</div>
		<?
		}
	}
	
?>
	
<? include("footer.php") ?>
