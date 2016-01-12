<?
 // ------------------------------------------------------------------------------
 // NiDB calendar.php
 // Copyright (C) 2004 - 2016
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
	ob_start(); // for any page redirects
	session_start();
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Administration</title>
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
	
	/* get variables */
	if ($_POST["action"] == "") { $action = $_GET["action"]; } else { $action = $_POST["action"]; }
	if ($_POST["id"] == "") { $id = $_GET["id"]; } else { $id = $_POST["id"]; }
	//if ($_POST["username"] == "") { $username = $_GET["username"]; } else { $username = $_POST["username"]; }
	if ($_POST["calendarid"] == "") { $calendarid = $_GET["calendarid"]; } else { $calendarid = $_POST["calendarid"]; }
	if ($_POST["projectid"] == "") { $projectid = $_GET["projectid"]; } else { $projectid = $_POST["projectid"]; }
	if ($_POST["details"] == "") { $details = $_GET["details"]; } else { $details = $_POST["details"]; }
	if ($_POST["title"] == "") { $title = $_GET["title"]; } else { $title = $_POST["title"]; }
	if ($_POST["startdate"] == "") { $startdate = $_GET["startdate"]; } else { $startdate = $_POST["startdate"]; }
	if ($_POST["starttime"] == "") { $starttime = $_GET["starttime"]; } else { $starttime = $_POST["starttime"]; }
	if ($_POST["enddate"] == "") { $enddate = $_GET["enddate"]; } else { $enddate = $_POST["enddate"]; }
	if ($_POST["endtime"] == "") { $endtime = $_GET["endtime"]; } else { $endtime = $_POST["endtime"]; }
	if ($_POST["isalldayevent"] == "") { $isalldayevent = $_GET["isalldayevent"]; } else { $isalldayevent = $_POST["isalldayevent"]; }
	if ($_POST["istimerequest"] == "") { $istimerequest = $_GET["istimerequest"]; } else { $istimerequest = $_POST["istimerequest"]; }
	if ($_POST["cancelreason"] == "") { $cancelreason = $_GET["cancelreason"]; } else { $cancelreason = $_POST["cancelreason"]; }
	if ($_POST["notifyusers"] == "") { $notifyusers = $_GET["notifyusers"]; } else { $notifyusers = $_POST["notifyusers"]; }
	if ($_POST["repeats"] == "") { $repeats = $_GET["repeats"]; } else { $repeats = $_POST["repeats"]; }
	if ($_POST["repeattype"] == "") { $repeattype = $_GET["repeattype"]; } else { $repeattype = $_POST["repeattype"]; }
	if ($_POST["repeatsun"] == "") { $repeatsun = $_GET["repeatsun"]; } else { $repeatsun = $_POST["repeatsun"]; }
	if ($_POST["repeatmon"] == "") { $repeatmon = $_GET["repeatmon"]; } else { $repeatmon = $_POST["repeatmon"]; }
	if ($_POST["repeattue"] == "") { $repeattue = $_GET["repeattue"]; } else { $repeattue = $_POST["repeattue"]; }
	if ($_POST["repeatwed"] == "") { $repeatwed = $_GET["repeatwed"]; } else { $repeatwed = $_POST["repeatwed"]; }
	if ($_POST["repeatthu"] == "") { $repeatthu = $_GET["repeatthu"]; } else { $repeatthu = $_POST["repeatthu"]; }
	if ($_POST["repeatfri"] == "") { $repeatfri = $_GET["repeatfri"]; } else { $repeatfri = $_POST["repeatfri"]; }
	if ($_POST["repeatsat"] == "") { $repeatsat = $_GET["repeatsat"]; } else { $repeatsat = $_POST["repeatsat"]; }
	if ($_POST["repeatenddate"] == "") { $repeatenddate = $_GET["repeatenddate"]; } else { $repeatenddate = $_POST["repeatenddate"]; }
	if ($_POST["groupid"] == "") { $groupid = $_GET["groupid"]; } else { $groupid = $_POST["groupid"]; }

	$startdatetime = "$startdate $starttime";
	$enddatetime = "$enddate $endtime";
	
	//echo "Action: $action<br><br>";
	//exit(0);
	
	if ($isalldayevent == "yes") { $isalldayevent = "1"; } else { $isalldayevent = "0"; }
	if ($istimerequest == "yes") { $istimerequest = "1"; } else { $istimerequest = "0"; }

	/* need this here because of the redirects */
	if (($action == "edit") || ($action == "delete") || ($action == "cancel")) {
	?><!--<META HTTP-EQUIV="refresh" CONTENT="3;URL=calendar.php">--><?
	}
	
	// default project ID, for now
	if ($projectid == "") { $projectid = 0; }
	
	/* check the action */
	if ($action == "addform") {
		DisplayForm("", "", "", $username, $calendarid, $projectid, $details, $title, $startdatetime, $enddatetime, $isalldayevent, $istimerequest, $currentcal, $repeats, $repeattype, $repeatsun, $repeatmon, $repeattue, $repeatwed, $repeatthu, $repeatfri, $repeatsat, $repeatenddate);
	}
	if ($action == "add") {
		Add("add", "", "", $username, $calendarid, $projectid, $details, $title, $startdatetime, $enddatetime, $isalldayevent, $istimerequest, $currentcal, $repeats, $repeattype, $repeatsun, $repeatmon, $repeattue, $repeatwed, $repeatthu, $repeatfri, $repeatsat, $repeatenddate, 0);
	}
	elseif ($action == "editform") {
		DisplayForm($id, "", "", "", "", "", "", "", "", "", "", "", $currentcal, $repeats, $repeattype, $repeatsun, $repeatmon, $repeattue, $repeatwed, $repeatthu, $repeatfri, $repeatsat, $repeatenddate);
	}
	elseif ($action == "edit") {
		Add("update", $id, $groupid, $username, $calendarid, $projectid, $details, $title, $startdatetime, $enddatetime, $isalldayevent, $istimerequest, $currentcal, $repeats, $repeattype, $repeatsun, $repeatmon, $repeattue, $repeatwed, $repeatthu, $repeatfri, $repeatsat, $repeatenddate, 0);
	}
	elseif ($action == "editall") {
		Add("update", $id, $groupid, $username, $calendarid, $projectid, $details, $title, $startdatetime, $enddatetime, $isalldayevent, $istimerequest, $currentcal, $repeats, $repeattype, $repeatsun, $repeatmon, $repeattue, $repeatwed, $repeatthu, $repeatfri, $repeatsat, $repeatenddate, 1);
	}
	elseif ($action == "delete") {
		Delete($id, $currentcal, 0);
	}
	elseif ($action == "deleteall") {
		Delete($groupid, $currentcal, 1);
	}
	elseif ($action == "cancel") {
		Cancel($id, $currentcal, $cancelreason, $notifyusers, 0);
	}
	elseif ($action == "cancelall") {
		Cancel($groupid, $currentcal, $cancelreason, $notifyusers, 1);
	}
	elseif (($action == "") || ($action == "list")) {
		//DisplayList();
		echo "Nothing to do";
	}
	
	
	/* ----------------------------------------------- */
	/* --------- Add --------------------------------- */
	/* ----------------------------------------------- */
	function Add($method, $id, $groupid, $username, $calendarid, $projectid, $details, $title, $startdatetime, $enddatetime, $isalldayevent, $istimerequest, $currentcal, $repeats, $repeattype, $repeatsun, $repeatmon, $repeattue, $repeatwed, $repeatthu, $repeatfri, $repeatsat, $repeatenddate, $editall) {
		
		/* check if any form elements are bad, if so redisplay the addform */
		if ($title == "") { DisplayForm("", "'Title' is blank", "", $username, $calendarid, $projectid, $details, $title, $startdatetime, $enddatetime, $isalldayevent, $istimerequest, $currentcal, $repeats, $repeattype, $repeatsun, $repeatmon, $repeattue, $repeatwed, $repeatthu, $repeatfri, $repeatsat, $repeatenddate); return; }
		if (!strtotime($startdatetime)) { DisplayForm("", "'Start date/time' is invalid", "", $username, $calendarid, $projectid, $details, $title, $startdatetime, $enddatetime, $isalldayevent, $istimerequest, $currentcal, $repeats, $repeattype, $repeatsun, $repeatmon, $repeattue, $repeatwed, $repeatthu, $repeatfri, $repeatsat, $repeatenddate); return; }
		if (!strtotime($enddatetime)) { DisplayForm("", "'End date/time' is invalid", "", $username, $calendarid, $projectid, $details, $title, $startdatetime, $enddatetime, $isalldayevent, $istimerequest, $currentcal, $repeats, $repeattype, $repeatsun, $repeatmon, $repeattue, $repeatwed, $repeatthu, $repeatfri, $repeatsat, $repeatenddate); return; }
		
		$details = mysql_real_escape_string($details);
		$title = mysql_real_escape_string($title);

		/* check if this appointment repeats */
		if (!$repeats) {
			$numappts = 1;
			$startdatetimes[0] = date('Y-m-d H:i:s',strtotime($startdatetime));
			$enddatetimes[0] = date('Y-m-d H:i:s',strtotime($enddatetime));
		}
		else {
			if ($method == "add") {
				/* get all dates on which the repeating appt falls */
				
				/* determine # of days between starting and ending date */
				$totaltimediff = strtotime($repeatenddate) - strtotime($startdatetime) + 86400;

				/* for daily and monthly, add the current date first. all dates are added inside the weekly case below */
				if (($repeattype == "daily") || ($repeattype == "monthly")) {
					$newappt = date('Y-m-d H:i:s',strtotime($startdatetime));
					$appts[] = $newappt;
				}
				/* add interval until date difference between starting and new appt is greater than ending date */
				$numappts = 1;
				$lastappt = $startdatetime;
				//print "Lastappt: $lastappt<br>";
				//$appts[] = $startdatetime;
				$done = false;
				while (!$done) {
					switch ($repeattype) {
						case "daily":
							$newappt = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s',strtotime($lastappt)) . " +1 days"));
							$appts[] = $newappt;
							break;
						case "weekly":
							//print "----- Lastappt: $lastappt<br>";
							if ($repeatsun) {
								$appt = date('Y-m-d H:i:s', strtotime("Sunday", strtotime($lastappt)));
								//print "Sun appt: $appt<br>";
								$appts[] = $appt;
							}
							if ($repeatmon) {
								$appt = date('Y-m-d H:i:s', strtotime("Monday", strtotime($lastappt)));
								//print "Mon appt: $appt<br>";
								$appts[] = $appt;
							}
							if ($repeattue) {
								$appt = date('Y-m-d H:i:s', strtotime("Tuesday", strtotime($lastappt)));
								//print "Tue appt: $appt<br>";
								$appts[] = $appt;
							}
							if ($repeatwed) {
								$appt = date('Y-m-d H:i:s', strtotime("Wednesday", strtotime($lastappt)));
								//print "Wed appt: $appt<br>";
								$appts[] = $appt;
							}
							if ($repeatthu) {
								$appt = date('Y-m-d H:i:s', strtotime("Thursday", strtotime($lastappt)));
								//print "Thu appt: $appt<br>";
								$appts[] = $appt;
							}
							if ($repeatfri) {
								$appt = date('Y-m-d H:i:s', strtotime("Friday", strtotime($lastappt)));
								//print "Fri appt: $appt<br>";
								$appts[] = $appt;
							}
							if ($repeatsat) {
								$appt = date('Y-m-d H:i:s', strtotime("Saturday", strtotime($lastappt)));
								//print "Sat appt: $appt<br>";
								$appts[] = $appt;
							}
							$newappt = date('Y-m-d H:i:s', strtotime($lastappt . " +1 weeks"));
							break;
						case "monthly";
							$newappt = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s',strtotime($lastappt)) . " +1 month"));
							$appts[] = $newappt;
							break;
					}
					
					$newtimediff = strtotime($newappt) - strtotime($startdatetime);
					
					if ($newtimediff > $totaltimediff) {
						$done = true;
						break;
					}
					$numappts++;
					if ($numappts > 100) {
						$done = true;
					}
					$lastappt = $newappt;
				}
				
				/* sort the appointment list and remove any appointments that fall outside the startdate/enddate range */
				sort($appts);
				/* make sure the remaining appointments retain the time */
				$k=0;
				for ($i=0;$i<count($appts);$i++) {
					//print date('Y-m-d', strtotime($appts[$i]));
					//print date('H:i:s', strtotime($startdatetime));
					$appts[$i] = date('Y-m-d', strtotime($appts[$i])) . " " . date('H:i:s', strtotime($startdatetime));
					$newtimediff = strtotime($appts[$i]) - strtotime($startdatetime);
					//print "NewTimeDiff: $newtimediff, Totaltimediff $totaltimediff<br>";
					//echo "<pre>";
					//print_r(get_defined_vars());
					//echo "</pre>";
					if ($newtimediff < $totaltimediff) {
						$startdatetimes[$k] = date('Y-m-d H:i:s', strtotime($appts[$i])); // . date('H:i:s', strtotime($startdatetime));
						//$appts[$i] =        date('Y-m-d', strtotime($appts[$i])) . " " . date('H:i:s', strtotime($startdatetime));
						$enddatetimes[$k] = date('Y-m-d ', strtotime($appts[$i])) . date('H:i:s', strtotime($enddatetime));
						$k++;
					}
				}
			}
		}
		//echo "<pre>";
		//print_r($appts);
		//print_r($startdatetimes);
		//print_r($enddatetimes);
		//echo "</pre>";
		
		//$groupid = "";
		/* add all the appointments, one by one */
		for ($i=0;$i<count($startdatetimes);$i++) {
		
			/* if this is an all day appointment, ignore any times that were entered */
			if ($isalldayevent) {
				$startdatetime = date('Y-m-d 00:00:00',strtotime($startdatetimes[$i]));
				$enddatetime = date('Y-m-d 00:00:00',strtotime($enddatetimes[$i]));
			}
			elseif ($istimerequest) {
				$startdatetime = date('Y-m-d H:i:s',strtotime($startdatetimes[$i]));
				$enddatetime = date('Y-m-d H:i:s',strtotime($enddatetimes[$i]));
			}
			else {
				$startdatetime = date('Y-m-d H:i:s',strtotime($startdatetimes[$i]));
				$enddatetime = date('Y-m-d H:i:s',strtotime($enddatetimes[$i]));

				// /* ignore allocations (for now) */
				// /* check if there are any allocations for this calendar/project */
				// $sqlstring = "select * from allocations where alloc_calendarid = $calendarid and alloc_projectid = $projectid";
				// $result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
				// if (mysql_num_rows($result) > 0) {
					// $row = mysql_fetch_array($result, MYSQL_ASSOC);
					// $alloc_days = $row['alloc_timeperiod'];
					// $alloc_hours = $row['alloc_amount'];
					
					// $allocdays = $alloc_days/2;
					
					// /* check the number of hours allocated vs the number of appointments on either side of this appointment */
					// if ($method == "add") {
						// $sqlstring = "SELECT unix_timestamp(appt_startdate)/3600 'appt_startdate', unix_timestamp(appt_enddate)/3600 'appt_enddate' FROM `calendar_appointments` WHERE appt_startdate between (date_add('$startdatetime', interval -$allocdays day)) and (date_add('$startdatetime', interval $allocdays day)) and appt_calendarid = $calendarid and appt_projectid = $projectid and appt_isalldayevent = 0 and appt_istimerequest = 0 and appt_deletedate > now() and appt_canceldate > now()";
					// }
					// else {
						// $sqlstring = "SELECT unix_timestamp(appt_startdate)/3600 'appt_startdate', unix_timestamp(appt_enddate)/3600 'appt_enddate' FROM `calendar_appointments` WHERE appt_startdate between (date_add('$startdatetime', interval -$allocdays day)) and (date_add('$startdatetime', interval $allocdays day)) and appt_calendarid = $calendarid and appt_projectid = $projectid and appt_isalldayevent = 0 and appt_istimerequest = 0 and appt_deletedate > now() and appt_canceldate > now() and appt_id != $id";
					// }
					// $result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
					// $total = 0;
					// while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
						// $start = $row['appt_startdate'];
						// $end = $row['appt_enddate'];
						// $apptlength = $end - $start;
						// $total += $apptlength;
					// }
					// if ($total > $alloc_hours) {
						// ?>
						<!-- <div align="center" style="border:orange 1px solid; background: lightyellow">
						// This appointment exceeds the allocation of <?=$alloc_hours?> hours per <?=$alloc_days?> for this project. Before adding this appointment, the project is using <?=$total?> hours within a <?=$alloc_days?> day span of time centered at <?=$startdatetime?>
						</div> -->
						<?
					// }
				// }
				
				/* check to see if this appointment overlaps an existing appt on the same calendar */
				if ($method == "add") {
					$sqlstring = "select * from calendar_appointments where ( (appt_startdate > '$startdatetime' and appt_startdate < '$enddatetime') or (appt_enddate > '$startdatetime' and appt_enddate < '$enddatetime') or (appt_startdate = '$startdatetime' and appt_enddate = '$enddatetime') or (appt_startdate < '$startdatetime' and appt_enddate > '$startdatetime') or (appt_startdate < '$enddatetime' and appt_enddate > '$enddatetime') ) and appt_calendarid = $calendarid and appt_deletedate > now() and appt_canceldate > now() and appt_istimerequest <> 1";
				}
				else {
					$sqlstring = "select * from calendar_appointments where ( (appt_startdate > '$startdatetime' and appt_startdate < '$enddatetime') or (appt_enddate > '$startdatetime' and appt_enddate < '$enddatetime') or (appt_startdate = '$startdatetime' and appt_enddate = '$enddatetime') or (appt_startdate < '$startdatetime' and appt_enddate > '$startdatetime') or (appt_startdate < '$enddatetime' and appt_enddate > '$enddatetime') ) and appt_calendarid = $calendarid and appt_deletedate > now() and appt_canceldate > now() and appt_id != $id and appt_istimerequest <> 1";
				}
				//echo "<br>$sqlstring<br>";
				$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
				if (mysql_num_rows($result) > 0) {
					while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
						$tstart = $row['appt_startdate'];
						$tend = $row['appt_enddate'];
						$ttitle = $row['appt_title'];
					}
				
					DisplayForm("","This appointment overlaps with an existing appointment(s): [$ttitle: $tstart - $tend]","",$username, $calendarid, $projectid, $details, $title, $startdatetime, $enddatetime, $isalldayevent, $istimerequest, $currentcal, $repeats, $repeattype, $repeatsun, $repeatmon, $repeattue, $repeatwed, $repeatthu, $repeatfri, $repeatsat, $repeatenddate, "");
					return;
				}
			}

			/* if we get to this point, its safe to add to the database */
			if ($method == "add") {
				$sqlstring = "insert into calendar_appointments (appt_groupid, appt_username, appt_calendarid, appt_projectid, appt_title, appt_details, appt_startdate, appt_enddate, appt_isalldayevent, appt_istimerequest) values ('$groupid', '$username', $calendarid, $projectid, '$title', '$details', '$startdatetime', '$enddatetime', $isalldayevent, $istimerequest)";
				//echo "$sqlstring<br>";
				$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
				/* CALULATE&POPULATE GROUP ID */
				if ($groupid == "") {
					$groupid = mysql_insert_id();
					$sqlstring = "update calendar_appointments set appt_groupid = $groupid where appt_id = $groupid";
					//echo "$sqlstring<br>";
					$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
				}
				
			}
			else {
				$sqlstring = "select * from calendar_appointments where appt_groupid = $groupid";
				$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
				if (mysql_num_rows($result) > 1) {
					$repeats = true;
				}
				
				if ($editall) {
					$sqlstring = "update calendar_appointments set appt_username = '$username', appt_calendarid = $calendarid, appt_projectid = $projectid, appt_title = '$title', appt_details = '$details', appt_isalldayevent = $isalldayevent, appt_istimerequest = $istimerequest where appt_groupid = $groupid";
				}
				else {
					if ($repeats) {
						$sqlstring = "update calendar_appointments set appt_username = '$username', appt_calendarid = $calendarid, appt_projectid = $projectid, appt_title = '$title', appt_details = '$details', appt_isalldayevent = $isalldayevent, appt_istimerequest = $istimerequest where appt_id = $id";
					}
					else {
						$sqlstring = "update calendar_appointments set appt_username = '$username', appt_calendarid = $calendarid, appt_projectid = $projectid, appt_title = '$title', appt_details = '$details', appt_startdate = '$startdatetime', appt_enddate = '$enddatetime', appt_isalldayevent = $isalldayevent, appt_istimerequest = $istimerequest where appt_id = $id";
					}
				}
				//echo "$sqlstring<br>";
				//exit(0);
				$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
			}
		}
		?>
		<div align="center">
		<?
			if ($method == "add") { echo "Appointment added"; } else { echo "Appointment updated"; }
		?>
		<br>
		<table><tr><td><img src="images/back16.png"></td><td><a href="calendar.php" class="link">Back</a> to calendar</td></tr></table><br>
		</div>
		<?
	}	

	
	/* ----------------------------------------------- */
	/* --------- Edit -------------------------------- */
	/* ----------------------------------------------- */
	function Edit($id, $username, $groupid, $calendarid, $projectid, $details, $title, $startdatetime, $enddatetime, $isalldayevent, $istimerequest, $currentcal, $repeats, $repeattype, $repeatsun, $repeatmon, $repeattue, $repeatwed, $repeatthu, $repeatfri, $repeatsat, $repeatenddate) {
		
		/* check if any form elements are bad, if so redisplay the addform */
		if ($name == "") { EditForm("'<b>Calendar Appointment</b>' was blank, original values now displayed",$id); return; }
		if ($description == "") { EditForm("'<b>Description</b>' was blank, original values now displayed",$id); return; }
		if ($location == "") { EditForm("'<b>Location</b>' was blank, original values now displayed",$id); return; }
		
		/* if we get to this point, its safe to add to the database */
		$sqlstring = "update calendars set calendar_name = '$name', calendar_description = '$description', calendar_location = '$location' where calendar_id = '$id'";
		//echo $sqlstring;
		$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		DisplayList();
	}	

	
	/* ----------------------------------------------- */
	/* --------- Delete ------------------------------ */
	/* ----------------------------------------------- */
	function Delete($id, $currentcal, $deleteall) {
		echo "Called Delete($id, $currentcal, $deleteall)<br>";
		if ($deleteall) {
			$sqlstring = "update calendar_appointments set appt_deletedate = now() where appt_groupid = '$id'";
		}
		else {
			$sqlstring = "update calendar_appointments set appt_deletedate = now() where appt_id = '$id'";
		}
		echo $sqlstring;
		$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		?>
		Appointment deleted<br><br>
		<a href="calendar.php" class="link">Back</a> to calendar
		<?
	}	


	/* ----------------------------------------------- */
	/* --------- Cancel ------------------------------ */
	/* ----------------------------------------------- */
	function Cancel($id, $currentcal, $cancelreason, $notifyusers, $cancelall) {
		/* get appointment info before changing it */
		$sqlstring = "select a.*, b.calendar_name, c.project_name from calendar_appointments a left join calendars b on a.appt_calendarid = b.calendar_id left join calendar_projects c on a.appt_projectid = c.project_id where a.appt_id = '$id'";
		$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$calid = $row['appt_calendarid'];
		$prjid = $row['appt_projectid'];
		$startdate = date('M j, Y', strtotime($row['appt_startdate']));
		$enddate   = date('M j, Y', strtotime($row['appt_enddate']));
		$appttitle = $row['appt_title'];
		$calendar = $row['calendar_name'];
		$starttime = date('g:i a', strtotime($row['appt_startdate']));
		$endtime   = date('g:i a', strtotime($row['appt_enddate']));

		if ($cancelall) {
			$sqlstring = "update calendar_appointments set appt_canceldate = now() where appt_groupid = '$id'";
		}
		else {
			$sqlstring = "update calendar_appointments set appt_canceldate = now() where appt_id = '$id'";
		}
		echo $sqlstring;
		$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		
		/* send an email if necessary */
		if ($notifyusers != "") {
			$body = "The appointment titled '$appttitle' scheduled from $starttime-$endtime on $startdate has been cancelled from the $calendar calendar because $cancelreason";
			$subject = "$calendar cancellation $startdate";
			
			/* get a list of users who care about this calendar and send an email */
			$sqlstring = "select b.user_email 'email' from calendar_notifications a left join users b on a.not_userid = b.user_id where not_calendarid = $calid";
			$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$recipients[] = $row['email'];
			}
			Sendmail($body, $recipients, $subject);
			
			/* get a list of users who care about this project and send an email */
		}
		?>
		<br>
		Appointment cancelled. Redirecting to calendar.
		<?
	}	
	
	
	/* ----------------------------------------------- */
	/* --------- Sendmail ---------------------------- */
	/* ----------------------------------------------- */
	function Sendmail($body, $recipients, $subject) {
		$mail             = new PHPMailer();
		//$body             = "This is a test message!";
		$body             = eregi_replace("[\]",'',$body);
		$mail->IsSMTP(); // telling the class to use SMTP
		$mail->SMTPAuth   = true;                  // enable SMTP authentication
		$mail->SMTPSecure = "tls";                 // sets the prefix to the servier
		$mail->Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
		$mail->Port       = 587;                   // set the SMTP port for the GMAIL server
		$mail->Username   = "email@gmail.com";  // GMAIL username
		$mail->Password   = "password";            // GMAIL password
		$mail->SetFrom('email@gmail.com', 'Calendar');
		$mail->Subject    = $subject;
		$mail->MsgHTML($body);
		foreach ($recipients as $email) {
			$mail->AddAddress($email, $email);
		}
		if(!$mail->Send()) {
		  echo "Mailer Error: " . $mail->ErrorInfo;
		} else {
		  echo "Emails sent!";
		}
	}

	
	/* ----------------------------------------------- */
	/* --------- DisplayForm ------------------------- */
	/* ----------------------------------------------- */
	function DisplayForm($id, $message, $groupid, $username, $calendarid, $projectid, $details, $title, $startdatetime, $enddatetime, $isalldayevent, $istimerequest, $currentcal, $repeats, $repeattype, $repeatsun, $repeatmon, $repeattue, $repeatwed, $repeatthu, $repeatfri, $repeatsat, $repeatenddate) {

		//echo "[$id]";
		
		$repeats = false;
		if ($id != "") {
			$type = "edit";
			$pagetitle = "Edit Appointment";
			$submitbutton = "Update";
			
			$sqlstring = "select * from calendar_appointments where appt_id = $id";
			//echo $sqlstring;
			$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
			$groupid = $row['appt_groupid'];
			$username = $row['appt_username'];
			$calendarid = $row['appt_calendarid'];
			$projectid = $row['appt_projectid'];
			$projectname = $row['project_name'];
			$title = $row['appt_title'];
			$startdate = date('Y-m-d', strtotime($row['appt_startdate']));
			$enddate = date('Y-m-d', strtotime($row['appt_enddate']));
			$starttime = date('g:ia', strtotime($row['appt_startdate']));
			$endtime = date('g:ia', strtotime($row['appt_enddate']));
			$isallday = $row['appt_isalldayevent'];
			$isrequest = $row['appt_istimerequest'];
			$details = $row['appt_details'];

			$sqlstring = "select * from calendar_appointments where appt_groupid = $groupid";
			$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
			if (mysql_num_rows($result) > 1) {
				$pagetitle = "Edit Recurring Appointment";
				$repeats = true;
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
			$calendarid = $currentcal;
			//echo "CurrentCal: $currentcal<br>";
		}
		
	?>
		<table><tr><td><img src="images/back16.png"></td><td><a href="calendar.php" class="link">Back</a> to calendar</td></tr></table><br>
		
		<form action="calendar_appointments.php" method="post" id="form1" name="form1">
		<input type="hidden" name="action" value="<?=$type?>">
		<input type="hidden" name="id" value="<?=$id?>">
		<input type="hidden" name="groupid" value="<?=$groupid?>">
		
		<table cellspacing="0" cellpadding="5" class="editor">
			<tr>
				<td colspan="3" style="color: darkblue; font-size: 14pt; text-align:center; font-weight: bold"><?=$pagetitle?></td>
			</tr>
			<tr>
				<td colspan="2" align="center" style="color:red"><?=$message?></td>
			</tr>
			<tr>
				<td class="label">Title</td>
				<td class="rightvalue">
					<input type="text" name="title" size="47" value="<?=$title?>"><br>
				</td>
			</tr>
			<tr>
				<td class="label">Start</td>
				<td class="rightvalue">
					<table>
						<tr>
							<td>
								<input type="text" name="startdate" size="12" value="<?=$startdate?>" <? if ($repeats) { echo "disabled"; } ?>>
							</td>
							<td>
								<!--<input type="text" name="starttime" size="10" value="<?=$starttime?>" <? if ($repeats) { echo "disabled"; } ?>>-->
								<select name="starttime" style="width: 100px" <? if ($repeats) { echo "disabled"; } ?>>
									<?
										$hours = array(12,1,2,3,4,5,6,7,8,9,10,11);
										foreach ($hours as $hr) {
											if ($starttime == "$hr:00am") { $selected = "selected"; } else { $selected = "";}
											?>
											<option value="<?="$hr:00am"?>" style="color: black; background-color: lightyellow" <?=$selected?>><?="$hr:00am"?></option>
											<? if ($starttime == "$hr:15am") { $selected = "selected"; } else { $selected = "";} ?>
											<option value="<?="$hr:15am"?>" style="color: gray" <?=$selected?>><?="$hr:15am"?></option>
											<? if ($starttime == "$hr:30am") { $selected = "selected"; } else { $selected = "";} ?>
											<option value="<?="$hr:30am"?>" style="color: gray" <?=$selected?>><?="$hr:30am"?></option>
											<? if ($starttime == "$hr:45am") { $selected = "selected"; } else { $selected = "";} ?>
											<option value="<?="$hr:45am"?>" style="color: gray" <?=$selected?>><?="$hr:45am"?></option>
											<?
										}
										foreach ($hours as $hr) {
											if ($starttime == "$hr:00pm") { $selected = "selected"; } else { $selected = "";}
											?>
											<option value="<?="$hr:00pm"?>" style="color: black; background-color: lightyellow" <?=$selected?>><?="$hr:00pm"?></option>
											<? if ($starttime == "$hr:15pm") { $selected = "selected"; } else { $selected = "";} ?>
											<option value="<?="$hr:15pm"?>" style="color: gray" <?=$selected?>><?="$hr:15pm"?></option>
											<? if ($starttime == "$hr:30pm") { $selected = "selected"; } else { $selected = "";} ?>
											<option value="<?="$hr:30pm"?>" style="color: gray" <?=$selected?>><?="$hr:30pm"?></option>
											<? if ($starttime == "$hr:45pm") { $selected = "selected"; } else { $selected = "";} ?>
											<option value="<?="$hr:45pm"?>" style="color: gray" <?=$selected?>><?="$hr:45pm"?></option>
											<?
										}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<td>
								<span class="tiny">YYYY-MM-DD</span>
							</td>
							<td>
								<span class="tiny">HH:MMpm</span>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class="label">End</td>
				<td class="rightvalue">
					<table>
						<tr>
							<td>
								<input type="text" name="enddate" size="12" value="<?=$enddate?>" <? if ($repeats) { echo "disabled"; } ?>>
							</td>
							<td>
								<!--<input type="text" name="endtime" size="10" value="<?=$endtime?>" <? if ($repeats) { echo "disabled"; } ?>>-->
								<select name="endtime" style="width: 100px" <? if ($repeats) { echo "disabled"; } ?>>
									<?
										$hours = array(12,1,2,3,4,5,6,7,8,9,10,11);
										foreach ($hours as $hr) {
											if ($endtime == "$hr:00am") { $selected = "selected"; } else { $selected = "";}
											?>
											<option value="<?="$hr:00am"?>" style="color: black; background-color: lightyellow" <?=$selected?>><?="$hr:00am"?></option>
											<? if ($endtime == "$hr:15am") { $selected = "selected"; } else { $selected = "";} ?>
											<option value="<?="$hr:15am"?>" style="color: gray" <?=$selected?>><?="$hr:15am"?></option>
											<? if ($endtime == "$hr:30am") { $selected = "selected"; } else { $selected = "";} ?>
											<option value="<?="$hr:30am"?>" style="color: gray" <?=$selected?>><?="$hr:30am"?></option>
											<? if ($endtime == "$hr:45am") { $selected = "selected"; } else { $selected = "";} ?>
											<option value="<?="$hr:45am"?>" style="color: gray" <?=$selected?>><?="$hr:45am"?></option>
											<?
										}
										foreach ($hours as $hr) {
											if ($endtime == "$hr:00pm") { $selected = "selected"; } else { $selected = "";}
											?>
											<option value="<?="$hr:00pm"?>" style="color: black; background-color: lightyellow" <?=$selected?>><?="$hr:00pm"?></option>
											<? if ($endtime == "$hr:15pm") { $selected = "selected"; } else { $selected = "";} ?>
											<option value="<?="$hr:15pm"?>" style="color: gray" <?=$selected?>><?="$hr:15pm"?></option>
											<? if ($endtime == "$hr:30pm") { $selected = "selected"; } else { $selected = "";} ?>
											<option value="<?="$hr:30pm"?>" style="color: gray" <?=$selected?>><?="$hr:30pm"?></option>
											<? if ($endtime == "$hr:45pm") { $selected = "selected"; } else { $selected = "";} ?>
											<option value="<?="$hr:45pm"?>" style="color: gray" <?=$selected?>><?="$hr:45pm"?></option>
											<?
										}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<td>
								<span class="tiny">YYYY-MM-DD</span>
							</td>
							<td>
								<span class="tiny">HH:MMpm</span>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class="label">Calendar</td>
				<td class="rightvalue">
					<select name="calendarid">
					<?
						$sqlstring = "select calendar_id, calendar_name from calendars where calendar_deletedate > now()";
						$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
						while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
							$calid = $row['calendar_id'];
							$name = $row['calendar_name'];
							if ($calid == $calendarid) { $selected = "selected"; } else { $selected = ""; }
					?>
						<option value="<?=$calid?>" <?=$selected?>><?=$name?>
					<?
						}
					?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">Project</td>
				<td class="rightvalue">
					<select name="projectid">
					<?
						$sqlstring = "select project_id, project_name from calendar_projects where project_enddate > now()";
						$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
						while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
							$prjid = $row['project_id'];
							$name = $row['project_name'];
							if ($prjid == $projectid) { $selected = "selected"; } else { $selected = ""; }
					?>
						<option value="<?=$prjid?>" <?=$selected?>><?=$name?>
					<?
						}
					?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">Options</td>
				<td class="rightvalue">
					<input type="checkbox" name="isalldayevent" value="yes" <?=$isalldayeventcheck?>> All day event<br>
					<input type="checkbox" name="istimerequest" value="yes" <?=$istimerequestcheck?>> Time Request ONLY
				</td>
			</tr>
			<tr>
				<td class="label">Details</td>
				<td class="rightvalue">
					<textarea name="details" cols="35" rows="4"><?=$details?></textarea>
				</td>
			</tr>
			<? if ($type == "add") { ?>
			<tr>
				<td class="label">Recurring appointment</td>
				<td class="rightvalue">
					<input type="checkbox" name="repeats" value="yes" <?=$apptrepeatscheck?>> Appointment repeats?<br>
					<table class="editor">
						<tr>
							<td colspan="2" style="color:darkblue">Frequency</td>
						</tr>
						<tr>
							<td class="rightvalue"><input type="radio" name="repeattype" value="daily">Daily</td>
							<td></td>
						</tr>
						<tr>
							<td class="rightvalue"><input type="radio" name="repeattype" value="weekly">Weekly on</td>
							<td> &nbsp; <input type="checkbox" name="repeatsun" value="1">S &nbsp;
							<input type="checkbox" name="repeatmon" value="1">M &nbsp;
							<input type="checkbox" name="repeattue" value="1">T &nbsp;
							<input type="checkbox" name="repeatwed" value="1">W &nbsp;
							<input type="checkbox" name="repeatthu" value="1">T &nbsp;
							<input type="checkbox" name="repeatfri" value="1">F &nbsp;
							<input type="checkbox" name="repeatsat" value="1">S 
							</td>
						</tr>
						<tr>
							<td class="rightvalue"><input type="radio" name="repeattype" value="monthly">Monthly on day </td>
							<td><select name="repeatdayofmonth">
							<?
								for ($i=1;$i<=31;$i++) {
									?><option value="<?=$i?>"><?=$i?><?
								}
							?>
							</select> of each month
							</td>
						</tr>
						<tr>
							<td align="right">Until &nbsp;</td>
							<td><input type="text" name="repeatenddate" size="12" value="<?=$enddate?>"></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td><span class="tiny">YYYY-MM-DD</span></td>
						</tr>
					</table>
				</td>
			</tr>
			<? } ?>
		</table>
		<p>
		<?	if ($repeats) { ?>
			<input type="submit" value="Update this only" name="submit" onClick="document.form1.action.value = 'edit';">
			<input type="submit" value="Update series" name="submit" onClick="document.form1.action.value = 'editall';">
		<?	} else { ?>
			<input type="submit" value="<?=$submitbutton?>" name="submit">
		<?	} ?>
		</p>
		</form>
		<br><br>
		<? if ($type == "edit") { ?>
			<table cellspacing="0" cellpadding="5" class="editor">
				<tr>
					<td colspan="2" style="color: darkblue; font-size: 12pt; text-align:center; font-weight: bold">Cancel or Delete<br>
					<span class="tiny">Cancel only because the subject couldn't/didn't show. Delete otherwise.</span></td>
				</tr>
				<tr>
					<td class="label">Cancellation reason</td>
					<td class="rightvalue"><input type="text" name="cancelreason"></td>
				</tr>
				<tr>
					<td class="label">Notify calendar users</td>
					<td class="rightvalue"><input type="checkbox" name="notifyusers"></td>
				</tr>
				<tr>
					<form action="calendar_appointments.php" method="post" id="form2">
					<input type="hidden" name="action" value="cancel">
					<input type="hidden" name="id" value="<?=$id?>">
					<td>
						<input type="submit" value="Cancel this only" onClick="return confirm('You\'re sure you want to cancel this appointment?');">
					</td>
					</form>
					
					<?	if ($repeats) { ?>
					<form action="calendar_appointments.php" method="post" id="form3">
					<input type="hidden" name="action" value="cancelall">
					<input type="hidden" name="id" value="<?=$id?>">
					<td>
						<input type="submit" value="Cancel series" onClick="return confirm('You\'re sure you want to cancel this recurring appointment?');">
					</td>
					</form>
					<? } ?>
				</tr>
			</table>
			</form>
			<br><Br>
			<table cellspacing="0" cellpadding="5" class="editor">
				<tr>
					<td class="label">Just delete it</td>
					
					<form action="calendar_appointments.php" method="post" id="form4">
					<input type="hidden" name="action" value="delete">
					<input type="hidden" name="id" value="<?=$id?>">
					<td class="rightvalue">
						<input type="submit" value="Delete this only" onClick="return confirm('You\'re for serious. You want to delete this appointment?');">
					</td>
					</form>
					
					<?	if ($repeats) { ?>
					<form action="calendar_appointments.php" method="post" id="form5">
					<input type="hidden" name="action" value="deleteall">
					<input type="hidden" name="groupid" value="<?=$id?>">
					<td class="rightvalue">
						<input type="submit" value="Delete series" onClick="return confirm('You\'re for serious. You want to delete this recurring appointment?');">
					</td>
					</form>
					<? } ?>
				</tr>
			</table>
		<?
		}
	}
	
?>
	
<? include("footer.php") ?>
