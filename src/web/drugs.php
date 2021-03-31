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
		<title>NiDB - Drugs</title>
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
	$drugid = GetVariable("drugid");
	$enrollmentid = GetVariable("enrollmentid");
	$drugname = GetVariable('drug_name');
	$drugdose = GetVariable('drug_dose');
	$drugfreq = GetVariable('drug_freq');
	$drugroute = GetVariable('drug_route');
	$drugstartdate = GetVariable('drug_startdate');
	$drugenddate = GetVariable('drug_enddate');
 	$drugtype = GetVariable('drug_type');						

	/* determine action */
	switch ($action) {
		case 'adddrug':
			AddDrug($enrollmentid, $drugname, $drugdose, $drugfreq, $drugroute, $drugstartdate, $drugenddate, $drugtype);
			DisplayDrugList($enrollmentid);
			break;
		case 'deletedrug':
			DeleteDrug($drugid);
			DisplayDrugList($enrollmentid);
			break;
		default:
			DisplayDrugList($enrollmentid);
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- AddDrug ------------------------- */
	/* -------------------------------------------- */
	function AddDrug($enrollmentid, $drugname, $drugdose, $drugfreq, $drugroute, $drugstartdate, $drugenddate, $drugtype) {
		$drugname = mysqli_real_escape_string($GLOBALS['linki'], $drugname);
		$drugdose = mysqli_real_escape_string($GLOBALS['linki'], $drugdose);
		$drugfreq = mysqli_real_escape_string($GLOBALS['linki'], $drugfreq);
		$drugroute = mysqli_real_escape_string($GLOBALS['linki'], $drugroute);
		$drugstartdate = mysqli_real_escape_string($GLOBALS['linki'], $drugstartdate);
		$drugenddate = mysqli_real_escape_string($GLOBALS['linki'], $drugenddate);
		$drugtype = mysqli_real_escape_string($GLOBALS['linki'], $drugtype);

		$sqlstringA = "select drugname_id from drugnames where drug_name = '$drugname'";
		//echo "$sqlstringA\n";
		$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
		if (mysqli_num_rows($resultA) > 0) {
			$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
			$drugnameid = $rowA['drugname_id'];
		}
		else {
			$sqlstringA = "insert into drugnames (drug_name) values ('$drugname')";
			//echo "$sqlstringA\n";
			$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
			$drugnameid = mysqli_insert_id($GLOBALS['linki']);
		}
		

		$sqlstring = "insert into drugs (enrollment_id, drug_startdate, drug_enddate, drug_doseamount, drug_dosefrequency, drug_route, drugname_id, drug_type) values ($enrollmentid, '$drugstartdate', '$drugenddate', '$drugdose', '$drugfreq', '$drugroute', '$drugnameid', '$drugtype')";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);	
	}


	/* -------------------------------------------- */
	/* ------- DeleteDrug ----------------- */
	/* -------------------------------------------- */
	function DeleteDrug($id) {
		$sqlstring = "delete from drugs where drug_id = $id";
		//echo "[$sqlstring]";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		?><div align="center"><span class="message">Drug deleted</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- DisplayDrugList ------------ */
	/* -------------------------------------------- */
	function DisplayDrugList($enrollmentid) {
		/* get subject's info for the breadcrumb list */
		$sqlstring = "select * from enrollment a left join subjects b on a.subject_id = b.subject_id left join projects c on a.project_id = c.project_id where a.enrollment_id = $enrollmentid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$uid = $row['uid'];
		$subjectid = $row['subject_id'];
		$projectname = $row['project_name'];
		
		$urllist['Subject List'] = "subjects.php";
		$urllist[$uid] = "subjects.php?action=display&id=$subjectid";
		$urllist["$projectname drugs"] = "drugs.php?enrollmentid=$enrollmentid";
		NavigationBar("Drugs", $urllist);
		
		?>
		<SCRIPT LANGUAGE="Javascript">
			function decision(message, url){
				if(confirm(message)) location.href = url;
			}
		</SCRIPT>
		
		<table class="smalldisplaytable">
			<thead align="left">
				<tr>
					<th>Drug</th>
					<th>Type</th>
					<th>Dose</th>
					<th>Route</th>
					<th>Start Date (mm/dd/yyyy hh:mm AM/PM)</th>
					<th>End Date (mm/dd/yyyy hh:mm AM/PM)</th>
				</tr>
			</thead>
			<tbody>
				<form action="drugs.php" method="post">
				<input type="hidden" name="action" value="adddrug">
				<input type="hidden" name="enrollmentid" value="<?=$enrollmentid?>">
				<tr>
					<td><input type="text" name="drug_name" size="15" placeholder="Drug" required></td>
					<td><input type="text" name="drug_type" list="ls_type" size="15" placeholder="Type">
						<datalist id="ls_type">
                                                        <option> Prescription</option>
                                                        <option>Trial</option>
                                                        <option>OTC</option>
                                                        <option>Recreational</option>
                                                </datalist>
					</td>
					<td><input type="text" name="drug_dose" size="15" placeholder="Dose"> <input type="text" name="drug_freq"  size="10" placeholder="Dose frequency"></td>
					<td><input type="text" name="drug_route" list="ls_route" size="15" placeholder="Route" />
						<datalist id="ls_route">
							<option> Oral</option>
							<option>IV</option>
							<option>IM</option>
							<option>Subcu</option>
						</datalist>
					</td>
					<td><input type="datetime-local" name="drug_startdate" required> to </td>
					<td><input type="datetime-local" name="drug_enddate"></td>
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
                        	                <th>Drug</th>
                                	        <th>Type</th>
                                        	<th>Dose / Frequency</th>
                                        	<th>Route</th>
                                        	<th>Dates (mm/dd/yyyy; hh:mm:ss AM/PM)</th>
						<th></th>
                	                </tr>
	                        </thead>

				<?
					$sqlstring = "select a.*, b.drug_name,date_format(a.drug_startdate,'%m-%d-%Y; %r') 'startdate', date_format(a.drug_enddate,'%m-%d-%Y; %r') 'enddate' from drugs a left join drugnames b on a.drugname_id = b.drugname_id where enrollment_id = $enrollmentid order by drug_name";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$drugid = $row['drug_id'];
						$drug_name = $row['drug_name'];
						$drug_route = $row['drug_route'];
						$drug_startdate = $row['startdate'];
						$drug_enddate = $row['enddate'];
						$drug_dose = $row['drug_doseamount'];
						$drug_dosefreq = $row['drug_dosefrequency'];
						$drug_type =  $row['drug_type'];
					 if ($drug_enddate=='')  {
						$drug_enddate = 'TO-DATE';
						}
						
						?>

				<tbody>
						<tr><br></tr>	
						<tr>
							<td><?=$drug_name?></td>
							<td><?=$drug_type?></td>
							<td><?=$drug_dose?> / <?=$drug_dosefreq?></td>
							<td><?=$drug_route?></td>
							<td><?=$drug_startdate?> to <?=$drug_enddate?></td>
							<td>
								<a class="ui red button" href="drugs.php?action=deletedrug&drugid=<?=$drugid?>&enrollmentid=<?=$enrollmentid?>" onclick="return confirm('Are you sure?')"><i class="trash icon"></i></a>
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
