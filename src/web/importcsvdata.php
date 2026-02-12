<?
 // ------------------------------------------------------------------------------
 // NiDB importimaging.php
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
// Contribution: Muhammad Asim Mubeen (02/19/2025)
 // ------------------------------------------------------------------------------

	define("LEGIT_REQUEST", true);

	session_start();
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Import Data from CSV files</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "nidbapi.php";
	require "menu.php";

	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$projectid = GetVariable("projectid");
	$dtype =  GetVariable("dtype");
	$csvdata= GetVariable("csvdata");
	$strata= GetVariable("strdata");
	
	/* determine action */
	switch ($action) {
		case 'readcsvfile':
			$dfile = $_FILES['datafile']['tmp_name'];
			$csvData = ReadDataFiles($dfile, $projectid, $dtype);
			eaisplayForm();
                        break;
		default:
			DisplayForm();
	}

/* ----------------------------------------- functions --------------------------------------- */

	/* -------------------------------------------- */
        /* ----------  DisplayForm  ------------------- */
        /* -------------------------------------------- */

	function DisplayForm(){
?>

        <div class="ui container">
                <h1 class="ui header"> Data Import to NiDB using CSV files </h1>
			<form class="ui form" method="post" enctype="multipart/form-data" action="importcsvdata.php">
			<input type='hidden' name='action' value='readcsvfile'>
			<div class="ui selection dropdown">
				<div class="ui label">
        	                        Project
				</div>
	                          <input type="hidden" name="projectid">
        	                  <i class="dropdown icon"></i>
                	          <div class="default text">Select a Project</div>
				  <div class="menu">
<?
					$sqlstring = "select * from projects a left join user_project b on a.project_id = b.project_id where b.user_id = (select user_id from users where username = '" . $_SESSION['username'] . "') and a.instance_id = '" . $_SESSION['instanceid'] . "' order by project_name";
	                                $result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
        	                        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                	                        $project_id = $row['project_id'];
                        	                $project_name = $row['project_name'];
                                	        $project_costcenter = $row['project_costcenter'];
?>
                        	                         <div class="item" data-value="<?=$project_id?>"> <?=$project_name?> (<?=$project_costcenter?>) </div>
<?
                                        }
?>					
                	          
	                      	</div>

			</div>
			<br><br>
			<div>
			<div class="ui paded segment">
				<div class="ui label">
                          	      Observations / Interventions
				</div>
				<br>
			  <div class="ui horizontal segments">
			    <div class="ui segment"> 
				<div class="ui selection dropdown">
                                	<input type="hidden" name="dtype">
	                                  <i class="dropdown icon"></i>
                                	<div class="default text">Select observation / Intervention type</div>
					<div class="menu">
	                                   <div class="item" data-value="m">Observations - Observation</div>
					   <div class="item" data-value="v">Vitals - Observation</div>
					   <div class="item" data-value="d">Drugs - Intervention</div>
                	                </div>
				</div>
			    </div>
			    <div class="ui segment">
				<div class="field">
	                                <label>
        	                                Select a csv data file (Formated as shown below) <i class="small blue question circle outline icon" title="A CSV data file should be formated according to the sample given below"></i>
                	                </label>
					<input type="file" name="datafile" id="datafile" accept=".csv" required>
				</div>
			    </div>
			  </div>	
				<br>
				<b>Sample observations.csv file format</b>
                                <div style="font-family:monospace; padding:8px; background-color: #eee; border: 1px dashed #aaa">
                                	nidbid,observationname,observation_startdate,observation_enddate,observation_value,observation_type,observation_rater,observation_notes <br>
                                        S1234ABC,audit_1,12/22/2022 16:47:53, ,2, ,AM,This is an example observation.
				</div>
				<br>
				<b>Sample vitals.csv file format</b>
                                <div style="font-family:monospace; padding:8px; background-color: #eee; border: 1px dashed #aaa">
                                        nidbid,vitalname,vital_startdate,vital_enddate,vital_value,vital_notes,vital_rater,vital_type,vital_duration <br>
                                        S1234ABC,SBP,08/01/2024 09:48:23, ,100,This is example of a vital,AM, , , 
				</div>
				<br>
                                <b>Sample drugs.csv file format</b>
                                <div style="font-family:monospace; padding:8px; background-color: #eee; border: 1px dashed #aaa">
                                        nidbid,interventionname,startdate,enddate,doseamount,dosefrequency,administration_route,intervention_type,dosekey,doseunit,drug_rater,<br>drug_notes <br>
                                        S1234ABC,11_Hydroxy-THC,06/23/2023 17:02:33, ,10,1 per day,vapor,Placebo,111-222-333,mg,AM,Dose administered 2 minutes late
                                </div>
				<div class="ui blue attached message">
                                  <div class="header">
                                    CSV Data File
                                  </div>
                                  <ul class="list">
                                    <li>should contain these three columns (nidbid, observationname / vitalname / interventionname, and observation_startdate / vital_startdate / startdate).</li>
                                    <li>should use the same column names.</li>
                                  </ul>
                                </div>

				<br><br>
				 <button class="ui primary button" type="submit"> Upload Data </button>
			</div>
                </form>
	</div>
<?
}


	/* ---------------------------------------------------- */
        /* ---------------- ReadDataFiles  -------------------- */
        /* ---------------------------------------------------- */
        function   ReadDataFiles($dfile, $projectid, $dtype) {

                $csvdata = array();
		
		//      Adding Observations information
		if ($dtype === 'm'){

			$requiredColumns = ['nidbid', 'observationname', 'observation_startdate']; // Mandatory columns
//			print_r($requiredColumns)."<br>";
			$optionalColumns = ['observation_enddate', 'observation_value', 'observation_type', 'observation_rater', 'observation_notes']; //Optional Columns

			if (($inshandle = fopen($dfile,"r")) !== FALSE) {
				$insheader =  fgetcsv($inshandle, 3000, ","); //Reading the first row (Header of the CSV file
//				print_r($insheader)."<br>";
				if (!$insheader){
					echo "Error: Empty or Invalid CSV file.";
					exit;
				}

				// Checking the required columns
				$missingColumns = array_diff($requiredColumns, $insheader);
//				print_r($missingColumns);
				if (!empty($missingColumns)) {
					echo "Error: Missing required Columns: ".implode(", ",$missingColumns);
					exit;
				}

				// Find the column indices
				$columnIndx = array_flip($insheader); // Mapping column names to indices 

				while (($insrow = fgetcsv($inshandle, 3000, ",")) !== FALSE) {
					$entry = [];

					// Extracting Required Columns
					foreach ($requiredColumns as $col){
						$entry[$col] = $insrow[$columnIndx[$col]];
					}

					$NiDBid = $entry['nidbid'];

					// Checking if the subject exists in NiDB in the chossen project and add a new one if choose to add subject
					$sqlstringEid = "SELECT enrollment_id FROM `enrollment` WHERE subject_id in (select subject_id from subjects where subjects.uid = '$NiDBid' ) and project_id = '$projectid' ";
//                                      PrintSQL($sqlstringEid);
                                        $resultEid = MySQLiQuery($sqlstringEid, __FILE__, __LINE__);
                                        $rowEid = mysqli_fetch_array($resultEid, MYSQLI_ASSOC);
					$EID = $rowEid['enrollment_id'];
					// Checking using Alternative ID
					if (empty($EID)) {
						echo "Error: Subject ".$NiDBid." does not exist in the selected project";
	                                        exit;

					}

					// Extracting Optional Columns
					foreach ($optionalColumns as $col){
						if (isset($columnIndx[$col])){
							$entry[$col] = $insrow[$columnIndx[$col]];
						}
					}


					// Preparing data for inserting to the NiDB
					// Defining the variables
                                        $subjectid = $entry['nidbid'];
                                        $observationname =  $entry['observationname'];
                                        $observationStdate = $entry['observation_startdate'];
                                        $observationenddate = $entry['observation_enddate'];
					$observationval = $entry['observation_value'];
					$observationtype = $entry['observation_type'];
                                        $observationrater = $entry['observation_rater'];
                                        $observationnotes = $entry['observation_notes'];

//					echo $subjectid.",".$projectid.",".$observationname.",".$observationval.",".$observationnotes.",".$observationrater.",".$observationStdate."<br>";

					Addobservations($subjectid, $projectid, $observationname, $observationval, $observationnotes, $observationrater, $observationStdate, $observationenddate);

					$csvdata[] = $entry;

				}

			}
			fclose($inshandle);	
			Notice("Observation's Data Added");
		}// Observation Information Section Ends

//      Adding Vitals information
		if ($dtype === 'v'){

			$requiredColumns = ['nidbid', 'vitalname', 'vital_startdate']; // Mandatory columns
//			print_r($requiredColumns)."<br>";
			$optionalColumns = ['vital_enddate', 'vital_value', 'vital_notes', 'vital_rater', 'vital_type', 'vital_duration']; //Optional Columns

			if (($inshandle = fopen($dfile,"r")) !== FALSE) {
				$insheader =  fgetcsv($inshandle, 3000, ","); //Reading the first row (Header of the CSV file
//				print_r($insheader)."<br>";
				if (!$insheader){
					echo "Error: Empty or Invalis CSV file.";
					exit;
				}

				// Checking the required columns
				$missingColumns = array_diff($requiredColumns, $insheader);
//				print_r($missingColumns);
				if (!empty($missingColumns)) {
					echo "Error: Missing required Columns: ".implode(", ",$missingColumns);
					exit;
				}

				// Find the column indices
				$columnIndx = array_flip($insheader); // Mapping column names to indices 

				while (($insrow = fgetcsv($inshandle, 3000, ",")) !== FALSE) {
					$entry = [];

					// Extracting Required Columns
					foreach ($requiredColumns as $col){
						$entry[$col] = $insrow[$columnIndx[$col]];
					}

					$NiDBid = $entry['nidbid'];

					// Checking if the subject exists in NiDB in the chossen project and add a new one if choose to add subject
					$sqlstringEid = "SELECT enrollment_id FROM `enrollment` WHERE subject_id in (select subject_id from subjects where subjects.uid = '$NiDBid' ) and project_id = '$projectid' ";
//                                      PrintSQL($sqlstringEid);
                                        $resultEid = MySQLiQuery($sqlstringEid, __FILE__, __LINE__);
                                        $rowEid = mysqli_fetch_array($resultEid, MYSQLI_ASSOC);
					$EID = $rowEid['enrollment_id'];
					// Checking using Alternative ID
					if (empty($EID)) {
						echo "Error: Subject ".$NiDBid." does not exist in the selected project";
	                                        exit;

					}

					// Extracting Optional Columns
					foreach ($optionalColumns as $col){
						if (isset($columnIndx[$col])){
							$entry[$col] = $insrow[$columnIndx[$col]];
						}
					}


					// Preparing data for inserting to the NiDB
					// Defining the variables
                                        $subjectid = $entry['nidbid'];
                                        $vitalName =  $entry['vitalname'];
                                        $vitalStdate = $entry['vital_startdate'];
                                        $vitalenddate = $entry['vital_enddate'];
                                        $vitalvalue = $entry['vital_value'];
					$vitaltype = $entry['vital_type'];
                                        $vitalduration = $entry['vital_duration'];
                                        $vitalrater = $entry['vital_rater'];
                                        $vitalnotes = $entry['vital_notes'];

//					echo $subjectid.",".$projectid.",".$vitalName.",".$vitalStdate.",".$vitalvalue.",".$vitalnotes.",".$vitalrater.",".$vitalenddate.",".$vitalduration."<br>";

					Addvitals($subjectid, $projectid, $vitalName, $vitalvalue, $vitalnotes, $vitalrater, $vitalStdate, $vitalenddate, $vitaltype, $vitalduration);

					$csvdata[] = $entry;

				}

			}
			fclose($inshandle);	
			Notice("Vital's Data Added");
		}// Vital Information Section Ends


//      Adding Drugs information
		if ($dtype === 'd'){

			$requiredColumns = ['nidbid', 'interventionname', 'startdate']; // Mandatory columns
//			print_r($requiredColumns)."<br>";
			$optionalColumns = ['enddate', 'doseamount', 'dosefrequency', 'administration_route', 'intervention_type', 'dosekey', 'doseunit', 'drug_rater', 'drug_notes']; //Optional Columns

			if (($inshandle = fopen($dfile,"r")) !== FALSE) {
				$insheader =  fgetcsv($inshandle, 3000, ","); //Reading the first row (Header of the CSV file
//				print_r($insheader)."<br>";
				if (!$insheader){
					echo "Error: Empty or Invalis CSV file.";
					exit;
				}

				// Checking the required columns
				$missingColumns = array_diff($requiredColumns, $insheader);
//				print_r($missingColumns);
				if (!empty($missingColumns)) {
					echo "Error: Missing required Columns: ".implode(", ",$missingColumns);
					exit;
				}

				// Find the column indices
				$columnIndx = array_flip($insheader); // Mapping column names to indices 

				while (($insrow = fgetcsv($inshandle, 3000, ",")) !== FALSE) {
					$entry = [];

					// Extracting Required Columns
					foreach ($requiredColumns as $col){
						$entry[$col] = $insrow[$columnIndx[$col]];
					}

					$NiDBid = $entry['nidbid'];

					// Checking if the subject exists in NiDB in the chossen project and add a new one if choose to add subject
					$sqlstringEid = "SELECT enrollment_id FROM `enrollment` WHERE subject_id in (select subject_id from subjects where subjects.uid = '$NiDBid' ) and project_id = '$projectid' ";
//                                      PrintSQL($sqlstringEid);
                                        $resultEid = MySQLiQuery($sqlstringEid, __FILE__, __LINE__);
                                        $rowEid = mysqli_fetch_array($resultEid, MYSQLI_ASSOC);
					$EID = $rowEid['enrollment_id'];
					// Checking using Alternative ID
					if (empty($EID)) {
						echo "Error: Subject ".$NiDBid." does not exist in the selected project";
	                                        exit;

					}

					// Extracting Optional Columns
					foreach ($optionalColumns as $col){
						if (isset($columnIndx[$col])){
							$entry[$col] = $insrow[$columnIndx[$col]];
						}
					}


					// Preparing data for inserting to the NiDB
					// Defining the variables
                                        $subjectid = $entry['nidbid'];
                                        $drugName =  $entry['interventionname'];
                                        $drugStdate = $entry['startdate'];
                                        $drugenddate = $entry['enddate'];
                                        $drugamount = $entry['doseamount'];
                                        $drugfreq = $entry['dosefrequency'];
					$drugroute = $entry['administration_route'];
					$drugtype = $entry['intervention_type'];
                                        $drugkey = $entry['dosekey'];
                                        $drugunit = $entry['doseunit'];
                                        $drugrater = $entry['drug_rater'];
                                        $drugnotes = $entry['drug_notes'];

//                                      echo $subjectid.",".$projectid.",".$drugName.",".$drugStdate.",".$drugnotes.",".$drugrater.",".$drugamount.",".$drugfreq.",".$drugroute."<br>";

                                     Adddrugs($subjectid,$projectid, $drugName, $drugnotes, $drugrater, $drugStdate, $drugamount,  $drugfreq, $drugroute, $drugkey, $drugunit, $drugtype,$drugenddate);

					$csvdata[] = $entry;

				}

			}
			fclose($inshandle);	
			Notice("Drug's Data Added");
		}// Drug Information Section Ends

		//  Returning array of data
                return array('data' => $csvdata); // data without header
		
	}


        /*--------------------------------------------------------*/
        /* ---------------- TRANSFERING MEASURES DATA ------------*/
        /*--------------------------------------------------------*/
	function  Addobservations($subjectid, $projectid, $observationname, $observationval, $observationnotes, $observationrater, $observationstdate, $observationenddate) {

                $sqlstringEn = "SELECT enrollment_id FROM `enrollment` WHERE subject_id in (select subject_id from subjects where subjects.uid = '$subjectid' ) and project_id = '$projectid' ";

//              PrintSQL($sqlstringEn);
                $resultEn = MySQLiQuery($sqlstringEn, __FILE__, __LINE__);
                $rowEn = mysqli_fetch_array($resultEn, MYSQLI_ASSOC);
                $enrollmentid = $rowEn['enrollment_id'];

                $sqlstringA = "select observationname_id from observationnames where observation_name = '$observationname'";


                $resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
                if (mysqli_num_rows($resultA) > 0) {
                        $rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
                        $observationnameid = $rowA['observationname_id'];
                }
                else {
                        $sqlstringA = "insert into observationnames (observation_name) values ('$observationname')";
                        //echo "$sqlstringA\n";
                        $resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
                        $observationnameid = mysqli_insert_id($GLOBALS['linki']);
		}


                 $observationnotes = str_replace("'","''",$observationnotes);
                 $observationnotes = str_replace('"',"''",$observationnotes);
		 
		 // Dealing with no date values
		
		 $msttime = strtotime($observationstdate);
		 $mstdate = ($msttime === false) ? '0000-00-00' :  date('Y-m-d H:i:s', $msttime);

		 $mendtime = strtotime($observationenddate);
                 $menddate = ($mendtime === false) ? '0000-00-00' :  date('Y-m-d H:i:s', $mendtime);
//		 echo $mstdate;

                 if ($enrollmentid!=''){
                $sqlstring = "insert ignore into observations (enrollment_id, observation_entrydate, observationname_id, observation_notes, observation_rater,observation_value,observation_startdate,observation_enddate,observation_entrydate,observation_createdate,observation_modifydate) values ($enrollmentid, now(),$observationnameid, '$observationnotes','$observationrater','$observationval',NULLIF('$mstdate',''),NULLIF('$menddate',''),now(),now(),now()) on duplicate key update observation_value='$observationval', observation_modifydate=now()";
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		return 1;
		 }
                 else{  return 0;}
        }

	

	/* -------------------------------------------- */
        /* ---- Transfering vitals into NiDB --------- */
        /* -------------------------------------------- */
	function Addvitals($subjectid, $projectid, $vitalName, $vitalvalue, $vitalnotes, $vitalrater, $vitalStdate, $vitalenddate, $vitaltype, $vitalduration){

                $sqlstringEn = "SELECT enrollment_id FROM `enrollment` WHERE subject_id in (select subject_id from subjects where subjects.uid = '$subjectid' ) and project_id = '$projectid' ";

        //      PrintSQL($sqlstringEn);
                $resultEn = MySQLiQuery($sqlstringEn, __FILE__, __LINE__);
                $rowEn = mysqli_fetch_array($resultEn, MYSQLI_ASSOC);
                $enrollmentid = $rowEn['enrollment_id'];

                $sqlstringA = "select vitalname_id from vitalnames where vital_name = '$vitalName'";
                //echo "$sqlstringA\n";
                $resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
                if (mysqli_num_rows($resultA) > 0) {
                        $rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
                        $vitalnameid = $rowA['vitalname_id'];
                }
                else {


                        $sqlstringA = "insert into vitalnames (vital_name) values ('$vitalName')";
                        //echo "$sqlstringA\n";
                        $resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
                        $vitalnameid = mysqli_insert_id($GLOBALS['linki']);
                }

                 $vitalnotes = str_replace("'","''",$vitalnotes);
                 $vitalnotes = str_replace('"',"''",$vitalnotes);
                 $vitalvalue = str_replace("'","''",$vitalvalue);
		 $vitalvalue = str_replace('"',"''",$vitalvalue);

		 // Dealing with no date values

                 $vsttime = strtotime($vitalStdate);
                 $vstdate = ($vsttime === false) ? '0000-00-00' :  date('Y-m-d H:i:s', $vsttime);

                 $vendtime = strtotime($vitalenddate);
                 $venddate = ($vendtime === false) ? '0000-00-00' :  date('Y-m-d H:i:s', $vendtime);


                 if ($enrollmentid!=''){
                $sqlstring = "insert ignore into vitals (enrollment_id, vital_date,vital_value,vital_notes,vital_duration,vital_type,vital_rater,vitalname_id,vital_startdate,vital_enddate,vital_entrydate,vital_recordcreatedate,vital_recordmodifydate) values ($enrollmentid,NULLIF('$vstdate',''),'$vitalvalue','$vitalnotes',NULLIF('$vitalduration',''),'$vitaltype','$vitalrater','$vitalnameid',NULLIF('$vstdate',''),NULLIF('$venddate',''),now(),now(),now()) on duplicate key update vital_value='$vitalvalue', vital_recordmodifydate=now()";
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		return 1;
		 }
                 else{  return 0;}
        }




	/* --------------------------------------------- */
        /* ----- Transferring Drugs data into NiDB------ */
        /* --------------------------------------------- */

   function  Adddrugs($subjectid,$projectid, $interventionname, $drugnotes, $drugrater, $drugStdate, $drugamount,  $drugfreq, $drugroute, $drugkey, $drugunit, $drugtype,$drugenddate){

           // Decompacting variables
//                extract($drugdose, EXTR_OVERWRITE);


                $sqlstringEn = "SELECT enrollment_id FROM `enrollment` WHERE subject_id in (select subject_id from subjects where subjects.uid = '$subjectid' ) and project_id = '$projectid' ";
/*                PrintSQL($sqlstringEn);*/


                $resultEn = MySQLiQuery($sqlstringEn, __FILE__, __LINE__);
                $rowEn = mysqli_fetch_array($resultEn, MYSQLI_ASSOC);
                $enrollmentid = $rowEn['enrollment_id'];

                $sqlstringA = "select interventionname_id from interventionnames where intervention_name = '$interventionname'";
                //echo "$sqlstringA\n";
                $resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
                if (mysqli_num_rows($resultA) > 0) {
                        $rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
                        $interventionname_id = $rowA['interventionname_id'];
                }
                else {
                         $sqlstringA = "insert into interventionnames (intervention_name) values ('$interventionname')";
                        //echo "$sqlstringA\n";
                        $resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
                        $interventionname_id = mysqli_insert_id($GLOBALS['linki']);
                        echo 'A new interventionname added!';?><br><?
                }


                $drug_notes = str_replace("'","''",$drug_notes);
		$drug_notes = str_replace('"',"''",$drug_notes);

		$dsttime = strtotime($drugStdate);
		$dstdate = ($dsttime === false) ? '0000-00-00' : date('Y-m-d H:i:s', $dsttime);

		$dendtime = strtotime($drugenddate);
		$denddate = ($dendtime === false) ? '0000-00-00' : date('Y-m-d H:i:s', $dendtime);

		if ($enrollmentid!=''){
                        $sqlstring = "insert ignore into drugs (enrollment_id, startdate, enddate, doseamount, dosefrequency, administration_route, interventionname_id, intervention_type, dosekey, doseunit, drug_rater, drug_notes, drug_entrydate, drug_recordcreatedate, drug_recordmodifydate) values ($enrollmentid,'$dstdate',NULLIF('$denddate',''), NULLIF('$drugamount',''), NULLIF('$drugfreq',''),NULLIF('$drugroute',''), '$interventionname_id', NULLIF('$drugtype',''), NULLIF('$drugkey',''), NULLIF('$drugunit',''), NULLIF('$drug_rater',''), NULLIF('$drug_notes',''),'$dstdate',now(),now()) on duplicate key update doseunit = '$drugunit', drug_recordmodifydate = now()";
                //      PrintSQL($sqlstring);
                        $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			return 1;
                 }
                 else{  return 0;}

        }




?>
</body>	
<? include("footer.php") ?>
