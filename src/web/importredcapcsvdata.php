<?
 // ------------------------------------------------------------------------------
 // NiDB importimaging.php
 // Copyright (C) 2004 - 2022
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
		<title>NiDB - Import Measures</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "nidbapi.php";
	require "menu.php";

	//PrintVariable($_POST);
	//PrintVariable($_GET);
	//PrintVariable($_FILES);
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$projectid = GetVariable("projectid");
	$dtype =  GetVariable("dtype");
	$subjectadd =  GetVariable("subjectadd");
	$csvdata= GetVariable("csvdata");
	$strata= GetVariable("strdata");
	
	/* determine action */
	switch ($action) {
		case 'readcsvfile':
			$dfile = $_FILES['datafile']['tmp_name'];
			$obfile = $_FILES['obsfile']['tmp_name'];
//			echo "Project ID:".$projectid;
//			echo " Data Type:".$dtype;
//			echo " NO/Yes:".$subjectadd;
			$str = ReadStrDef($obfile,$dtype);
//			print_r($observations);
			$csvData = ReaDataFiles($dfile, $projectid, $dtype, $subjectadd, $str);
//			print_r($csvData);
//			DisplayCsvFile($filepath);
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
		<form class="ui form" id="uploadcsv" method="post" enctype="multipart/form-data" action="importcsvdata.php">
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
			
			<div class="ui selection dropdown">
				<div class="ui label">
					NiDB Datatype
				</div>
				<input type="hidden" name="dtype">
				  <i class="dropdown icon"></i>
				<div class="default text">Select a Datatype</div>
				    <div class="menu">
				    <div class="item" data-value="m">Measures</div>
				    <div class="item" data-value="d">Drugs</div>
				  </div>
			</div>
			<br><br>

			<div class="field">
				<label> 
					Select Structure Information File (CSV Format) <i class="small blue question circle outline icon" title="A CSV File defining the structure (Assessment / Drug) of the data file"></i>
				</label>
                                <input type="file" name="obsfile" id="obsfile" accept=".csv" required>

				<label> 
					Select the Data File (CSV Format) <i class="small blue question circle outline icon" title="Data file setup according to the structure information file in CSV format  "></i>
				</label>
				<input type="file" name="datafile" id="datafile" accept=".csv" required>
			</div>
			<div class="inline fields">
                            <label>Do you want to create a subject if it is not found in NiDB? </label>
                            <div class="field">
                              <div class="ui radio checkbox">
                                <input type="radio" value="n" name="subjectadd" checked="checked">
                                <label>No</label>
                              </div>
                            </div>
                            <div class="field">
                              <div class="ui radio checkbox">
                                <input type="radio" value="y" name="subjectadd">
                                <label>Yes</label>
                              </div>
                            </div>
                         </div>
			<button class="ui primary button" type="submit"> Upload the Data </button>
			
                </form>
	</div>
<?
}


	/* -------------------------------------------- */
        /* ----------  ReadStrDef  ------------------- */
        /* -------------------------------------------- */
	function ReadStrDef($strfile,$dtype) {

		$strdata = array();
		if ($dtype === 'm'){


			if (($ghandle = fopen($strfile,"r")) !== FALSE) {

	
				$gheader = fgetcsv($ghandle, 3000, ',');


				while (($grow = fgetcsv($ghandle, 3000, ",")) !== FALSE) {

					$obsname = $grow[0];
					$nidbID = $grow[1];
					$altID = $grow[2];
					$dateCol = $grow[3];
					$raterCol = $grow[4];
					$notesCol = $grow[5];
					$dataCol = explode(' ', $grow[6]);

					$strdata[$obsname] = array(
						'nidbid' => $nidbID,
						'altid' => $altID,
						'date' => $dateCol,
						'rater' => $raterCol,
						'notes' => $notesCol,
						'data' => $dataCol,
					);
                	        }
                        	fclose ($ghandle);
			}

		}
//		print_r($gheader);
		//		print_r($obsdata);

		 if ($dtype === 'd'){

			if (($ghandle = fopen($strfile,"r")) !== FALSE) {


                                $gheader = fgetcsv($ghandle, 3000, ',');


                                while (($grow = fgetcsv($ghandle, 3000, ",")) !== FALSE) {

                                        $drugname = $grow[0];
                                        $nidbID = $grow[1];
                                        $altID = $grow[2];
                                        $StdateCol = $grow[3];
                                        $raterCol = $grow[4];
                                        $notesCol = $grow[5];
					$drugamount = $grow[6];
					$drugfreq = $grow[7];
					$drugroute = $grow[8];
					$drugkey = $grow[9];
					$drugunit = $grow[10];
					$drugtype = $grow[11];
					$EnddateCol = $grow[12];




                                        $strdata[$drugname] = array(
                                                'nidbid' => $nidbID,
                                                'altid' => $altID,
                                                'Stdate' => $StdateCol,
                                                'rater' => $raterCol,
						'notes' => $notesCol,
						'drugName' => $drugname,
						'drugamount' => $drugamount,
						'drugfreq' => $drugfreq,
						'drugroute' => $drugroute,
						'drugkey' => $drugkey,
						'drugunit' => $drugunit,
						'drugtype' => $drugtype,
						'Enddate' => $EnddateCol,



                                        );
                                }
                                fclose ($ghandle);
                        }

		 }
		return $strdata;

	}

	/* -------------------------------------------- */
        /* ----------  ReadCsvFiles  ------------------- */
        /* -------------------------------------------- */
        function   ReaDataFiles($dfile, $projectid, $dtype, $subjectadd, $str) {

		$csvdata = array();

//      Adding Measures information		
	if ($dtype === 'm'){

		if (($mhandle = fopen($dfile,"r")) !== FALSE) {
			while (($mrow = fgetcsv($mhandle, 3000, ",")) !== FALSE) {
                                if (!$mheader) {
					$mheader = $mrow;
//					print_r($mheader);
				} else {
					foreach ($str as $ObsName => $columns){
						$filteredRow = array();
						foreach (['altid', 'nidbid', 'date', 'rater', 'notes'] as $type) {
							$index = array_search($columns[$type], $mheader);
							if ($index !== false) {
								// Adding a subject in NiDB if it is not present in NiDB and selected Yes for adding subjects
								if ($type === 'nidbid') {
//									echo $filteredRow[$type];
									$AltID = $filteredRow['altid'];
//									echo "1 . Alternate ID:".$AltID.", Project ID:".$projectid."<br>";
									$sqlstringUid = "SELECT uid FROM subjects WHERE subject_id in (select subject_id from subject_altuid where altuid = '$AltID')";
//									PrintSQL($sqlstringUid);
							                $resultUid = MySQLiQuery($sqlstringUid, __FILE__, __LINE__);
							                $rowUid = mysqli_fetch_array($resultUid, MYSQLI_ASSOC);
									$UID = $rowUid['uid'];

									if (empty($UID) && $subjectadd === 'y') {
//										echo "here"."<br>";
										// Create NiDB subject if it does not exist in NiDB
										
										// Before Adding the subject check if the Project exists in NiDB
										$sqlstringPid = "SELECT project_id FROM projects WHERE project_id='$projectid'";
//		                                                                PrintSQL($sqlstringPid);
										$resultPid = MySQLiQuery($sqlstringPid, __FILE__, __LINE__);


										if (mysqli_num_rows($resultPid) > 0) {
//											echo "2. Alternate ID:".$AltID.", Project ID:".$projectid."<br>";
											$Uid = AddSubject( $AltID, $AltID,  $AltID ,$projectid);
											$filteredRow[$type] = $Uid;
										}
																		

									} else {
										$filteredRow[$type] = $UID;
									}
									
								} else {
									$filteredRow[$type] = $mrow[$index];
								}

                                                                }
						}

//						echo $filteredRow['altid'].",".$filteredRow['nidbid'].",".$filteredRow['siteid'].",".$projectinfo[$filteredRow['siteid']].",".$filteredRow['date'].",".$filteredRow['rater'].",".$filteredRow['notes'];
							foreach ($columns['data'] as $colName) {
								$index = array_search($colName, $mheader);
								if ($index !== false) {
									$filteredRow[$colName] = $mrow[$index];
//									echo ",".$colName." = ".$mrow[$index];
	
									// Defining the variables
									$subjectid = $filteredRow['nidbid'];
									$measurename = $colName;
									$measurevalue = $mrow[$index];
									$measurenotes = $filteredRow['notes'];
									$measurerater = $filteredRow['rater'];
									$measurestdate = $filteredRow['date'];
	
									echo $subjectid.",".$projectid.",".$measurename.",".$measurevalue.",".$measurenotes.",".$measurerater.",".$measurestdate."<br>";

									Addmeasures($subjectid,$projectid, $measurename, $measurevalue, $measurenotes, $measurerater, $measurestdate,$measureenddate); 
								}
							}
						
//						print_r($filteredRow);
//						echo $filteredRow['altid'].",".$filteredRow['nidbid'].",".$filteredRow['date'].",".$filteredRow['rater'].",".$filteredRow['notes'];
//						echo "<br>";
						$csvdata[$groupName][] = $filteredRow;

					}
                                }
                        }
                        fclose ($mhandle);
		}
	} // dtype-->m


// I NEED TO ADD DRUGS DATA ENTRY CODE HERE, KEEP MEASURES AS IT IS AND Write separate code for Drugs		


//	Adding Drugs information
	if ($dtype === 'd'){

			if (($mhandle = fopen($dfile,"r")) !== FALSE) {
			while (($mrow = fgetcsv($mhandle, 3000, ",")) !== FALSE) {
                                if (!$mheader) {
					$mheader = $mrow;
//					print_r($mheader);
				} else {
					foreach ($str as $DrugName => $columns){
						$filteredRow = array();
						foreach (['altid', 'nidbid', 'Stdate', 'rater', 'notes', 'drugName', 'drugamount','drugfreq', 'drugroute', 'drugkey', 'drugunit', 'drugtype', 'Enddate'] as $type) {
							$index = array_search($columns[$type], $mheader);
							if ($index !== false) {
								// Adding a subject in NiDB if it is not present in NiDB and selected Yes for adding subjects
								if ($type === 'nidbid') {
//									echo $filteredRow[$type];
									$AltID = $filteredRow['altid'];
//									echo "1 . Alternate ID:".$AltID.", Project ID:".$projectid."<br>";
									$sqlstringUid = "SELECT uid FROM subjects WHERE subject_id in (select subject_id from subject_altuid where altuid = '$AltID')";
//									PrintSQL($sqlstringUid);
							                $resultUid = MySQLiQuery($sqlstringUid, __FILE__, __LINE__);
							                $rowUid = mysqli_fetch_array($resultUid, MYSQLI_ASSOC);
									$UID = $rowUid['uid'];

									if (empty($UID) && $subjectadd === 'y') {
//										echo "here"."<br>";
										// Create NiDB subject if it does not exist in NiDB
										
										// Before Adding the subject check if the Project exists in NiDB
										$sqlstringPid = "SELECT project_id FROM projects WHERE project_id='$projectid'";
//		                                                                PrintSQL($sqlstringPid);
										$resultPid = MySQLiQuery($sqlstringPid, __FILE__, __LINE__);


										if (mysqli_num_rows($resultPid) > 0) {
//											echo "2. Alternate ID:".$AltID.", Project ID:".$projectid."<br>";
											$Uid = AddSubject( $AltID, $AltID,  $AltID ,$projectid);
											$filteredRow[$type] = $Uid;
										}
																		

									} else {
										$filteredRow[$type] = $UID;
									}
									
								} else {
									$filteredRow[$type] = $mrow[$index];
								}

                                                                }
						}

//						echo $filteredRow['altid'].",".$filteredRow['nidbid'].",".$filteredRow['siteid'].",".$projectinfo[$filteredRow['siteid']].",".$filteredRow['date'].",".$filteredRow['rater'].",".$filteredRow['notes'];
//									$filteredRow[$colName] = $mrow[$index];
//									echo ",".$colName." = ".$mrow[$index];
	
									// Defining the variables
									$subjectid = $filteredRow['nidbid'];
									$drugname =  $filteredRow['drugName'];
									$drugnotes = $filteredRow['notes'];
									$drugrater = $filteredRow['rater'];
									$drugStdate = $filteredRow['Stdate'];
									$drugamount = $filteredRow['drugamount'];
									$drugfreq = $filteredRow['drugfreq'];
									$drugroute = $filteredRow['drugroute'];
									$drugkey = $filteredRow['drugkey'];
									$drugunit = $filteredRow['drugunit'];
									$drugtype = $filteredRow['drugamount'];
									$drugenddate = $filteredRow['Enddate'];
	
									echo $subjectid.",".$projectid.",".$drugname.",".$drugStdate.",".$drugnotes.",".$drugrater.",".$drugamount.",".$drugfreq.",".$drugroute."<br>";

									Adddrugs($subjectid,$projectid, $drugname, $drugnotes, $drugrater, $drugStdate, $drugamount,  $drugfreq, $drugroute, $drugkey, $drugunit, $drugtype,$drugenddate); 
						
//						print_r($filteredRow);
//						echo $filteredRow['altid'].",".$filteredRow['nidbid'].",".$filteredRow['date'].",".$filteredRow['rater'].",".$filteredRow['notes'];
//						echo "<br>";
						$csvdata[$groupName][] = $filteredRow;

					}
                                }
                        }
                        fclose ($mhandle);
		} 

	 } // dtype-->d





//		print_r($csvdata);
		//		return array('header' => $mheader, 'data' => $csvdata);// data with all the header values
//		Returning array of data
		return array('data' => $csvdata); // data without header

	} 

	



	/* -------------------------------------------- */
        /* ------- AddSubject ------------------------- */
        /* -------------------------------------------- */
	function AddSubject($lastname, $firstname, $altuid, $projid) {

		$name = mysqli_real_escape_string($GLOBALS['linki'], "$lastname^$firstname");

                # create a new uid
                do {
                        $uid = NIDB\CreateUID('S',3);
                        $sqlstring = "SELECT * FROM `subjects` WHERE uid = '$uid'";
                        $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
                        $count = mysqli_num_rows($result);
                } while ($count > 0);

                # create a new family uid
                do {
                        $familyuid = NIDB\CreateUID('F');
                        $sqlstring = "SELECT * FROM `families` WHERE family_uid = '$familyuid'";
                        $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
                        $count = mysqli_num_rows($result);
                } while ($count > 0);

                /* insert the new subject */
                $sqlstring = "insert into subjects (name, uid) values ('$name', '$uid')";
                if ($GLOBALS['debug']) { PrintSQL($sqlstring); }
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
                $SubjectRowID = mysqli_insert_id($GLOBALS['linki']);

                # create familyRowID if it doesn't exist
                $sqlstring2 = "insert into families (family_uid, family_createdate, family_name) values ('$familyuid', now(), 'Proband-$uid')";
                if ($GLOBALS['debug']) { PrintSQL($sqlstring2); }
                $result2 = MySQLiQuery($sqlstring2,__FILE__,__LINE__);
                $familyRowID = mysqli_insert_id($GLOBALS['linki']);

                $sqlstring3 = "insert into family_members (family_id, subject_id, fm_createdate) values ($familyRowID, $SubjectRowID, now())";
                if ($GLOBALS['debug']) { PrintSQL($sqlstring3); }
		$result3 = MySQLiQuery($sqlstring3,__FILE__,__LINE__);

		// Inserting Alternate Ids	
		$sqlstringAlt = "insert ignore into subject_altuid (subject_id, altuid) values ($SubjectRowID, '$altuid')";
		$resultAlt = MySQLiQuery($sqlstringAlt, __FILE__, __LINE__);

		// Inserting Subject into Corresponding Study
//		echo "ENROLLING"."<br>";
		$sqlstringEn = "insert into enrollment (project_id, subject_id, enroll_startdate) values ($projid, $SubjectRowID, now())";
		 PrintSQL($sqlstringEn);
                $resultEn = MySQLiQuery($sqlstringEn , __FILE__, __LINE__);

		

                return $uid;
        }
	




        /*--------------------------------------------------------*/
        /* ---------------- TRANSFERING MEASURES DATA ------------*/
        /*--------------------------------------------------------*/

        function Addmeasures($subjectid,$projectid, $measurename, $measurevalue, $measurenotes, $measurerater, $measurestdate) {

                $sqlstringEn = "SELECT enrollment_id FROM `enrollment` WHERE subject_id in (select subject_id from subjects where subjects.uid = '$subjectid' ) and project_id = '$projectid' ";

//              PrintSQL($sqlstringEn);
                $resultEn = MySQLiQuery($sqlstringEn, __FILE__, __LINE__);
                $rowEn = mysqli_fetch_array($resultEn, MYSQLI_ASSOC);
                $enrollmentid = $rowEn['enrollment_id'];

                $sqlstringA = "select measurename_id from measurenames where measure_name = '$measurename'";


                $resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
                if (mysqli_num_rows($resultA) > 0) {
                        $rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
                        $measurenameid = $rowA['measurename_id'];
                }
                else {
                        $sqlstringA = "insert into measurenames (measure_name) values ('$measurename')";
                        //echo "$sqlstringA\n";
                        $resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
                        $measurenameid = mysqli_insert_id($GLOBALS['linki']);
		}


                 $measurenotes = str_replace("'","''",$measurenotes);
                 $measurenotes = str_replace('"',"''",$measurenotes);
		 
		 // Dealing with no date values
		
		 $msttime = strtotime($measurestdate);
		 $mstdate = ($msttime === false) ? '0000-00-00' : date('Y-m-d', $msttime);
//		 echo $mstdate;

                 if ($enrollmentid!=''){
                $sqlstring = "insert ignore into measures (enrollment_id, measure_dateentered, measurename_id, measure_notes, measure_rater,measure_value,measure_startdate,measure_entrydate,measure_createdate,measure_modifydate) values ($enrollmentid, now(),$measurenameid, '$measurenotes','$measurerater','$measurevalue',NULLIF('$mstdate',''),now(),now(),now()) on duplicate key update measure_value='$measurevalue', measure_modifydate=now()";
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
                 return 1;}
                 else{  return 0;}
        }



	 /* -------------------------------------------- */
        /* ------ Transferring Drugs data into NiDB------ */
        /* -------------------------------------------- */

   function  Adddrugs($subjectid,$projectid, $drugname, $drugnotes, $drugrater, $drugStdate, $drugamount,  $drugfreq, $drugroute, $drugkey, $drugunit, $drugtype,$drugenddate){

           // Decompacting variables
//                extract($drugdose, EXTR_OVERWRITE);


                $sqlstringEn = "SELECT enrollment_id FROM `enrollment` WHERE subject_id in (select subject_id from subjects where subjects.uid = '$subjectid' ) and project_id = '$projectid' ";
/*                PrintSQL($sqlstringEn);*/


                $resultEn = MySQLiQuery($sqlstringEn, __FILE__, __LINE__);
                $rowEn = mysqli_fetch_array($resultEn, MYSQLI_ASSOC);
                $enrollmentid = $rowEn['enrollment_id'];

                $sqlstringA = "select drugname_id from drugnames where drug_name = '$drugname'";
                //echo "$sqlstringA\n";
                $resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
                if (mysqli_num_rows($resultA) > 0) {
                        $rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
                        $drugname_id = $rowA['drugname_id'];
                }
                else {
                         $sqlstringA = "insert into drugnames (drug_name) values ('$drugname')";
                        //echo "$sqlstringA\n";
                        $resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
                        $drugname_id = mysqli_insert_id($GLOBALS['linki']);
                        echo 'A new drugname added!';?><br><?
                }


                $drug_notes = str_replace("'","''",$drug_notes);
		$drug_notes = str_replace('"',"''",$drug_notes);

		$dsttime = strtotime($drugStdate);
		$dstdate = ($dsttime === false) ? '0000-00-00' : date('Y-m-d', $dsttime);

		$dendtime = strtotime($drugenddate);
                $denddate = ($dendtime === false) ? '0000-00-00' : date('Y-m-d', $dendtime);

                        $sqlstring = "insert ignore into drugs (enrollment_id, drug_startdate, drug_enddate, drug_doseamount, drug_dosefrequency, drug_route, drugname_id, drug_type, drug_dosekey, drug_doseunit, drug_rater, drug_notes, drug_entrydate, drug_recordcreatedate, drug_recordmodifydate) values ($enrollmentid,'$dstdate',NULLIF('$denddate',''), NULLIF('$drugamount',''), NULLIF('$drugfreq',''),NULLIF('$drugroute',''), '$drugname_id', NULLIF('$drugtype',''), NULLIF('$drugkey',''), NULLIF('$drugunit',''), NULLIF('$drug_rater',''), NULLIF('$drug_notes',''),'$dstdate',now(),now()) on duplicate key update drug_doseunit = '$drugdoseunit', drug_recordmodifydate = now()";
                //      PrintSQL($sqlstring);
                        $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);


        }




?>
</body>	
<? include("footer.php") ?>
