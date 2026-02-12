<?
 // ------------------------------------------------------------------------------
 // NiDB interventions.php
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
		<title>NiDB - Interventions</title>
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
	$interventionid = GetVariable("interventionid");
	$enrollmentid = GetVariable("enrollmentid");
	$intreventionname = GetVariable('intervention_name');
	$dose = GetVariable('dose');
	$freq = GetVariable('freq');
	$route = GetVariable('administration_route');
	$startdate = GetVariable('startdate');
	$enddate = GetVariable('enddate');
 	$interventiontype = GetVariable('intervention_type');						

	/* determine action */
	switch ($action) {
		case 'addintervention':
			AddIntervention($enrollmentid, $interventionname, $dose, $freq, $route, $startdate, $enddate, $interventiontype);
			DisplayInterventionList($enrollmentid);
			break;
		case 'deleteintervention':
			DeleteIntervention($interventionid);
			DisplayInterventionList($enrollmentid);
			break;
		default:
			DisplayInterventionList($enrollmentid);
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- AddIntervention -------------------- */
	/* -------------------------------------------- */
	function AddIntervention($enrollmentid, $interventionname, $dose, $freq, $route, $startdate, $enddate, $interventiontype) {
		$interventionname = mysqli_real_escape_string($GLOBALS['linki'], $interventionname);
		$dose = mysqli_real_escape_string($GLOBALS['linki'], $dose);
		$freq = mysqli_real_escape_string($GLOBALS['linki'], $freq);
		$route = mysqli_real_escape_string($GLOBALS['linki'], $route);
		$startdate = mysqli_real_escape_string($GLOBALS['linki'], $startdate);
		$enddate = mysqli_real_escape_string($GLOBALS['linki'], $enddate);
		$interventiontype = mysqli_real_escape_string($GLOBALS['linki'], $interventiontype);

		$sqlstringA = "select interventionname_id from interventionnames where intervention_name = '$interventionname'";
		//echo "$sqlstringA\n";
		$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
		if (mysqli_num_rows($resultA) > 0) {
			$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
			$interventionnameid = $rowA['interventionname_id'];
		}
		else {
			$sqlstringA = "insert into interventionnames (intervention_name) values ('$interventionname')";
			//echo "$sqlstringA\n";
			$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
			$interventionnameid = mysqli_insert_id($GLOBALS['linki']);
		}
		

		$sqlstring = "insert into interventions (enrollment_id, startdate, enddate, doseamount, dosefrequency, administration_route, interventionname_id, intervention_type) values ($enrollmentid, '$startdate', '$enddate', '$dose', '$freq', '$route', '$interventionnameid', '$interventiontype')";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);	
	}


	/* -------------------------------------------- */
	/* ------- DeleteIntervention ----------------- */
	/* -------------------------------------------- */
	function DeleteIntervention($id) {
		$sqlstring = "delete from interventions where intervention_id = $id";
		//echo "[$sqlstring]";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		?><div align="center"><span class="message">Intervention deleted</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- DisplayInterventionList ------------ */
	/* -------------------------------------------- */
	function DisplayInterventionList($enrollmentid) {
		/* get subject's info for the breadcrumb list */
		$sqlstring = "select * from enrollment a left join subjects b on a.subject_id = b.subject_id left join projects c on a.project_id = c.project_id where a.enrollment_id = $enrollmentid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$uid = $row['uid'];
		$subjectid = $row['subject_id'];
		$projectname = $row['project_name'];
		
		?>
		<SCRIPT LANGUAGE="Javascript">
			function decision(message, url){
				if(confirm(message)) location.href = url;
			}
		</SCRIPT>
		
		<table class="smalldisplaytable">
			<thead align="left">
				<tr>
					<th>Intervention</th>
					<th>Type</th>
					<th>Dose</th>
					<th>Route</th>
					<th>Start Date (mm/dd/yyyy hh:mm AM/PM)</th>
					<th>End Date (mm/dd/yyyy hh:mm AM/PM)</th>
				</tr>
			</thead>
			<tbody>
				<form action="interventions.php" method="post">
				<input type="hidden" name="action" value="addintervention">
				<input type="hidden" name="enrollmentid" value="<?=$enrollmentid?>">
				<tr>
					<td><input type="text" name="intervention_name" size="15" placeholder="Intervention" required></td>
					<td><input type="text" name="intervention_type" list="ls_type" size="15" placeholder="Type">
						<datalist id="ls_type">
                                                        <option> Prescription</option>
                                                        <option>Trial</option>
                                                        <option>OTC</option>
                                                        <option>Recreational</option>
                                                </datalist>
					</td>
					<td><input type="text" name="dose" size="15" placeholder="Dose"> <input type="text" name="freq"  size="10" placeholder="Dose frequency"></td>
					<td><input type="text" name="administration_route" list="ls_route" size="15" placeholder="Route" />
						<datalist id="ls_route">
							<option> Oral</option>
							<option>IV</option>
							<option>IM</option>
							<option>Subcu</option>
						</datalist>
					</td>
					<td><input type="datetime-local" name="startdate" required> to </td>
					<td><input type="datetime-local" name="enddate"></td>
					<td><input type="submit" value="Add"></td>
				</tr>
				</form>
				</tbody>
				<tfoot>
					<tr>
						<td align="right" colspan="6"> <font color="blue">End date can be left blank</font></td>
					</tr>
				</tfoot>
                	</table>


			 <table class="smalldisplaytable">
			
				<thead align="left">
        	                        <tr>
                        	                <th>Intervention</th>
                                	        <th>Type</th>
                                        	<th>Dose / Frequency</th>
                                        	<th>Route</th>
                                        	<th>Dates (mm/dd/yyyy; hh:mm:ss AM/PM)</th>
						<th></th>
                	                </tr>
	                        </thead>

				<?
					$sqlstring = "select *, date_format(startdate,'%m-%d-%Y; %r') 'startdate', date_format(enddate,'%m-%d-%Y; %r') 'enddate' from interventions where enrollment_id = $enrollmentid order by intervention_name";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$interventionid = $row['intervention_id'];
						$intervention_name = $row['intervention_name'];
						$administration_route = $row['administration_route'];
						$startdate = $row['startdate'];
						$enddate = $row['enddate'];
						$dose = $row['doseamount'];
						$dosefreq = $row['dosefrequency'];
						$intervention_type =  $row['intervention_type'];
					 if ($enddate=='')  {
						$enddate = 'TO-DATE';
						}
						
						?>

				<tbody>
						<tr><br></tr>	
						<tr>
							<td><?=$intervention_name?></td>
							<td><?=$intervention_type?></td>
							<td><?=$dose?> / <?=$dosefreq?></td>
							<td><?=$administration_route?></td>
							<td><?=$startdate?> to <?=$enddate?></td>
							<td>
								<a class="ui red button" href="interventions.php?action=deleteintervention&interventionid=<?=$interventionid?>&enrollmentid=<?=$enrollmentid?>" onclick="return confirm('Are you sure?')"><i class="trash icon"></i></a>
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
