<?
 // ------------------------------------------------------------------------------
 // NiDB experiments.php
 // Copyright (C) 2004 - 2021
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
		<title>NiDB - Vitals</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$vitalid = GetVariable("vitalid");
	$enrollmentid = GetVariable("enrollmentid");
	$vitalname = GetVariable('vital_name');
	$vitalvalue = GetVariable('vital_value');
	$vitalnotes = GetVariable('vital_notes');
	$vitaldate = GetVariable('vital_date');
 	$vitaltype = GetVariable('vital_type');						

	/* determine action */
	switch ($action) {
		case 'addvital':
			AddVital($enrollmentid, $vitalname, $vitalvalue, $vitalnotes, $vitaldate, $vitaltype);
			DisplayVitalList($enrollmentid);
			break;
		case 'deletevital':
			DeleteVital($vitalid);
			DisplayVitalList($enrollmentid);
			break;
		default:
			DisplayVitalList($enrollmentid);
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- Add Vital ------------------------- */
	/* -------------------------------------------- */
	function AddVital($enrollmentid, $vitalname, $vitalvalue, $vitalnotes, $vitaldate, $vitaltype) {
		$vitalname = mysqli_real_escape_string($GLOBALS['linki'], $vitalname);
		$vitalvalue = mysqli_real_escape_string($GLOBALS['linki'], $vitalvalue);
		$vitalnotes = mysqli_real_escape_string($GLOBALS['linki'], $vitalnotes);
		$vitaldate = mysqli_real_escape_string($GLOBALS['linki'], $vitaldate);
		$vitaltype = mysqli_real_escape_string($GLOBALS['linki'], $vitaltype);

		$sqlstringA = "select vitalname_id from vitalnames where vital_name = '$vitalname'";
		//echo "$sqlstringA\n";
		$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
		if (mysqli_num_rows($resultA) > 0) {
			$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
			$vitalnameid = $rowA['vitalname_id'];
		}
		else {
			$sqlstringA = "insert into vitalnames (vital_name) values ('$vitalname')";
			//echo "$sqlstringA\n";
			$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
			$vitalnameid = mysqli_insert_id($GLOBALS['linki']);
		}
		

		$sqlstring = "insert into vitals (enrollment_id, vital_date, vital_value, vital_notes, vitalname_id, vital_type) values ($enrollmentid, '$vitaldate', '$vitalvalue', '$vitalnotes', '$vitalnameid', '$vitaltype')";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);	
	}


	/* -------------------------------------------- */
	/* ------- Delete Vital ----------------- */
	/* -------------------------------------------- */
	function DeleteVital($id) {
		$sqlstring = "delete from vitals where vital_id = $id";
		//echo "[$sqlstring]";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		?><div align="center"><span class="message">Vital deleted</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- DisplayVitalList ------------ */
	/* -------------------------------------------- */
	function DisplayVitalList($enrollmentid) {
		/* get subject's info for the breadcrumb list */
		$sqlstring = "select * from enrollment a left join subjects b on a.subject_id = b.subject_id left join projects c on a.project_id = c.project_id where a.enrollment_id = $enrollmentid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$uid = $row['uid'];
		$subjectid = $row['subject_id'];
		$projectname = $row['project_name'];
		
		$urllist['Subject List'] = "subjects.php";
		$urllist[$uid] = "subjects.php?action=display&id=$subjectid";
		$urllist["$projectname vitals"] = "vitals.php?enrollmentid=$enrollmentid";
		NavigationBar("Vitals", $urllist);
		
		?>
		<SCRIPT LANGUAGE="Javascript">
			function decision(message, url){
				if(confirm(message)) location.href = url;
			}
		</SCRIPT>
		
		<table class="smalldisplaytable">
			<thead align="left">
				<tr>
					<th>Vitals</th>
					<th>Type</th>
					<th>Value</th>
					<th>Notes</th>
					<th>Date (mm/dd/yyyy hh:mm AM/PM)</th>
				</tr>
			</thead>
			<tbody>
				<form action="vitals.php" method="post">
				<input type="hidden" name="action" value="addvital">
				<input type="hidden" name="enrollmentid" value="<?=$enrollmentid?>">
				<tr>
					<td><input type="text" name="vital_name" size="15" placeholder="Vital" required></td>
					<td><input type="text" name="vital_type" list="ls_type" size="15" placeholder="Type">
						<datalist id="ls_type">
                                                        <option> Blood Test</option>
							<option>BP</option>
							<option>Pulse</option>
							<option>Respiration</option>
                                                        <option>Temperature</option>
                                                        <option>SPO2</option>
                                                </datalist>
					</td>
					<td><input type="text" name="vital_value" size="15" placeholder="Value">
					<td><input type="text" name="vital_notes" size="75" placeholder="Notes">
					<td><input type="datetime-local" name="vital_date" required></td>
					<td><input type="submit" value="Add"></td>
				</tr>
				</form>
				</tbody>
                	</table>

			 <table class="smalldisplaytable">
			
				<thead align="left">
        	                        <tr>
                        	                <th>Vitals</th>
                                	        <th>Type</th>
                                        	<th>Value</th>
                                        	<th>Notes</th>
                                        	<th>Date (mm/dd/yyyy; hh:mm:ss AM/PM)</th>
                	                </tr>
	                        </thead>

				<?
					$sqlstring = "select a.*, b.vital_name,date_format(a.vital_date,'%m-%d-%Y; %r') 'vdate' from vitals a left join vitalnames b on a.vitalname_id = b.vitalname_id where enrollment_id = $enrollmentid order by vital_name";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$vitalid = $row['vital_id'];
						$vital_name = $row['vital_name'];
						$vital_date = $row['vdate'];
						$vital_value = $row['vital_value'];
						$vital_notes = $row['vital_notes'];
						$vital_type =  $row['vital_type'];
						
						?>

				<tbody>
						<tr><br></tr>	
						<tr>
							<td><?=$vital_name?></td>
							<td><?=$vital_type?></td>
							<td><?=$vital_value?></td>
							<td><?=$vital_notes?></td>
							<td><?=$vital_date?></td>
							<td align="right">
								<a class="ui red button" href="javascript:decision('Are you sure you want to delete this vital?', 'vitals.php?action=deletevital&vitalid=<?=$vitalid?>&enrollmentid=<?=$enrollmentid?>')"><i class="trash icon"></i></a>
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
