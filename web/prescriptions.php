<?
 // ------------------------------------------------------------------------------
 // NiDB experiments.php
 // Copyright (C) 2004 - 2015
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
		<title>NiDB - Prescriptions</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes.php";
	require "menu.php";
	
	PrintVariable($_POST,'POST');
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$rxid = GetVariable("rxid");
	$enrollmentid = GetVariable("enrollmentid");
	$rxname = GetVariable('rx_name');
	$rxdose = GetVariable('rx_dose');
	$rxfreq = GetVariable('rx_freq');
	$rxroute = GetVariable('rx_route');
	$rxdatestart = GetVariable('rx_datestart');
	$rxdateend = GetVariable('rx_dateend');
	$rxrater = GetVariable('rx_rater');
						
	/* determine action */
	switch ($action) {
		case 'addrx':
			AddPrescription($enrollmentid, $rxname, $rxdose, $rxfreq, $rxroute, $rxdatestart, $rxdateend, $rxrater);
			DisplayPrescriptionList($enrollmentid);
			break;
		case 'deleterx':
			DeletePrescription($rxid);
			DisplayPrescriptionList($enrollmentid);
			break;
		default:
			DisplayPrescriptionList($enrollmentid);
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- AddPrescription ------------------------- */
	/* -------------------------------------------- */
	function AddPrescription($enrollmentid, $rxname, $rxdose, $rxfreq, $rxroute, $rxdatestart, $rxdateend, $rxrater) {
		$rxname = mysql_real_escape_string($rxname);
		$rxdose = mysql_real_escape_string($rxdose);
		$rxfreq = mysql_real_escape_string($rxfreq);
		$rxroute = mysql_real_escape_string($rxroute);
		$rxdatestart = mysql_real_escape_string($rxdatestart);
		$rxdateend = mysql_real_escape_string($rxdateend);
		$rxrater = mysql_real_escape_string($rxrater);

		$sqlstringA = "select rxname_id from prescriptionnames where rx_name = '$rxname'";
		//echo "$sqlstringA\n";
		$resultA = mysql_query($sqlstringA) or die("Query failed: " . mysql_error() . "<br><b>$sqlstringA</b><br>");
		if (mysql_num_rows($resultA) > 0) {
			$rowA = mysql_fetch_array($resultA, MYSQL_ASSOC);
			$rxnameid = $rowA['rxname_id'];
		}
		else {
			$sqlstringA = "insert into prescriptionnames (rx_name) values ('$rxname')";
			//echo "$sqlstringA\n";
			$resultA = mysql_query($sqlstringA) or die("Query failed: " . mysql_error() . "<br><b>$sqlstringA</b><br>");
			$rxnameid = mysql_insert_id();
		}
		
		$sqlstring = "insert into prescriptions (enrollment_id, rx_startdate, rx_enddate, rx_doseamount, rx_dosefrequency, rx_route, rxname_id) values ($enrollmentid, '$rxstartdate', '$rxenddate', '$rxdose', '$rxfreq', '$rxroute', '$rxnameid')";
		//PrintSQL($sqlstring);
		$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");	
	}


	/* -------------------------------------------- */
	/* ------- DeletePrescription ----------------- */
	/* -------------------------------------------- */
	function DeletePrescription($id) {
		$sqlstring = "delete from prescriptions where rx_id = $id";
		//echo "[$sqlstring]";
		$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><b>$sqlstring</b><br>");
		
		?><div align="center"><span class="message">Prescription deleted</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- DisplayPrescriptionList ------------ */
	/* -------------------------------------------- */
	function DisplayPrescriptionList($enrollmentid) {
		/* get subject's info for the breadcrumb list */
		$sqlstring = "select * from enrollment a left join subjects b on a.subject_id = b.subject_id left join projects c on a.project_id = c.project_id where a.enrollment_id = $enrollmentid";
		$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><b>$sqlstring</b><br>");
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$uid = $row['uid'];
		$subjectid = $row['subject_id'];
		$projectname = $row['project_name'];
		
		$urllist['Subject List'] = "subjects.php";
		$urllist[$uid] = "subjects.php?action=display&id=$subjectid";
		$urllist["$projectname prescriptions"] = "prescriptions.php?enrollmentid=$enrollmentid";
		NavigationBar("Prescriptions", $urllist);
		
		?>
		<SCRIPT LANGUAGE="Javascript">
			function decision(message, url){
				if(confirm(message)) location.href = url;
			}
		</SCRIPT>
		
		<table class="smalldisplaytable">
			<thead>
				<tr>
					<th>Prescription</th>
					<th>Dose</th>
					<th>Route</th>
					<th>Dates</th>
				</tr>
			</thead>
			<tbody>
				<form action="prescriptions.php" method="post">
				<input type="hidden" name="action" value="addrx">
				<input type="hidden" name="enrollmentid" value="<?=$enrollmentid?>">
				<tr>
					<td><input type="text" name="rx_name" size="15" placeholder="Prescription"></td>
					<td><input type="text" name="rx_dose" placeholder="Dose"> <input type="text" name="rx_freq" placeholder="Dose frequency"></td>
					<td><input type="text" name="rx_route" placeholder="Route"></td>
					<td><input type="date" name="rx_datestart"> to <input type="date" name="rx_dateend"></td>
					<td><input type="text" name="rx_rater" value="<?=$GLOBALS['username']?>"></td>
					<td><input type="submit" value="Add"></td>
				</tr>
				</form>
				<?
					$sqlstring = "select * from prescriptions a left join prescriptionnames b on a.rxname_id = b.rxname_id where enrollment_id = $enrollmentid order by rx_name";
					$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><b>$sqlstring</b><br>");
					while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
						$rxid = $row['rx_id'];
						$rx_name = $row['rx_name'];
						$rx_route = $row['rx_route'];
						$rx_startdate = $row['rx_startdate'];
						$rx_enddate = $row['rx_enddate'];
						$rx_dose = $row['rx_doseamount'];
						$rx_dosefreq = $row['rx_dosefrequency'];
						
						?>
						<tr>
							<td><?=$rx_name?></td>
							<td><?=$rx_route?><br><?=$rx_dateentered2?></td>
							<td><?=$rx_rater?><br><?=$rx_rater2?></td>
							<td align="right" class="delete">
								<a href="javascript:decision('Are you sure you want to delete this rx?', 'prescriptions.php?action=deleterx&rxid=<?=$rxid?>&enrollmentid=<?=$enrollmentid?>')" class="delete">X</a>
							</td>
						</tr>
					<?
					}
					?>
			</tbody>
		</table>
		<?
	}
?>


<? include("footer.php") ?>
