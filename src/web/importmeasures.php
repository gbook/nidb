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

	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$projectid = GetVariable("projectid");
	$csvdata= GetVariable("csvdata");
	$groupdata= GetVariable("groupata");
	
	/* determine action */
	switch ($action) {
		case 'readcsvfile':
			$mfile = $_FILES['measuresfile']['tmp_name'];
			$gfile = $_FILES['groupsfile']['tmp_name'];
			$pfile = $_FILES['projectsfile']['tmp_name'];
			$groups = ReadGroupsDef($gfile);
//			print_r($groups);
			$csvData = ReadCsvFiles($mfile, $pfile, $groups);
//			print_r($csvData);
//			DisplayCsvFile($filepath);
			break;
		default:
			DisplayForm();
	}

	/* ------------------------------------ functions ------------------------------------ */

	/* -------------------------------------------- */
        /* ----------  DisplayForm  ------------------- */
        /* -------------------------------------------- */

	function DisplayForm(){
?>

        <div class="ui container">
                <h1 class="ui header"> Select the CSV file to upload the data </h1>
		<form class="ui form" id="uploadcsv" method="post" enctype="multipart/form-data" action="importmeasures.php">
		<input type='hidden' name='action' value='readcsvfile'>
			<div class="field">
				<label> Select a CSV File for Project / Site Info</label>
                                <input type="file" name="projectsfile" id="projectsfile" accept=".csv" rerquired>
				<label> Select a CSV File to Define Groups</label>
                                <input type="file" name="groupsfile" id="groupsfile" accept=".csv" rerquired>
				<label> Select a CSV File to Upload Measures</label>
				<input type="file" name="measuresfile" id="measuresfile" accept=".csv" rerquired>
			</div>
			<button class="ui primary button" type="submit"> Upload the Data </button>
			
                </form>
	</div>
<?
}


	/* -------------------------------------------- */
        /* ----------  ReadGroupsDef  ------------------- */
        /* -------------------------------------------- */
	function ReadGroupsDef($gfile) {

		$groupdata = array();

		if (($ghandle = fopen($gfile,"r")) !== FALSE) {

			$gheader = fgetcsv($ghandle, 3000, ',');

			while (($grow = fgetcsv($ghandle, 3000, ",")) !== FALSE) {

				$groupname = $grow[0];
				$nidbID = $grow[1];
				$altID = $grow[2];
				$siteID = $grow[3];
				$dateCol = $grow[4];
				$raterCol = $grow[5];
				$notesCol = $grow[6];
				$dataCol = explode(' ', $grow[7]);

				$groupdata[$groupname] = array(
					'nidbid' => $nidbID,
					'altid' => $altID,
					'siteid' => $siteID,
					'date' => $dateCol,
					'rater' => $raterCol,
					'notes' => $notesCol,
					'data' => $dataCol,
				);
                        }
                        fclose ($ghandle);
		}

		return $groupdata;

//		print_r($gheader);
//		print_r($groupdata);
	}



	/* -------------------------------------------- */
        /* ----------  ReadCsvFiles  ------------------- */
        /* -------------------------------------------- */
        function ReadCsvFiles($mfile, $pfile, $groups) {

		$projectinfo =array();
		if (($phandle = fopen($pfile,"r")) !== FALSE) {
                        while (($prow = fgetcsv($phandle, 3000, ",")) !== FALSE) {
                                if (!$pheader) {
                                        $pheader = $prow;
				} else {
					$site = $prow[0];
					$pid = $prow[1];
					$projectinfo[$site]= $pid;
				}
			}
		}

//		print_r(array('header' => $pheader, 'data' => $projectinfo));

//		echo "<br>";

//		print_r($projectinfo);

//		echo "<br>";


		$csvdata = array();

                if (($mhandle = fopen($mfile,"r")) !== FALSE) {
                        while (($mrow = fgetcsv($mhandle, 3000, ",")) !== FALSE) {
                                if (!$mheader) {
					$mheader = $mrow;
				} else {
					foreach ($groups as $groupName => $columns){
						$filteredRow = array();
						foreach (['altid', 'siteid', 'nidbid', 'date', 'rater', 'notes'] as $type) {
							$index = array_search($columns[$type], $mheader);
							if ($index !== false) {
								if ($type === 'nidbid' && empty($mrow[$index])) {
//									echo $filteredRow[$type];
									$AltID = $filteredRow['altid'];
									$SiteID = $filteredRow['siteid'];
									$PID = $projectinfo[$SiteID];
//									echo "1 . Alternate ID:".$AltID.", Site ID:".$SiteID.", Project ID:".$PID."<br>";
									$sqlstringUid = "SELECT uid FROM subjects WHERE subject_id in (select subject_id from subject_altuid where altuid = '$AltID')";
//									PrintSQL($sqlstringUid);
							                $resultUid = MySQLiQuery($sqlstringUid, __FILE__, __LINE__);
							                $rowUid = mysqli_fetch_array($resultUid, MYSQLI_ASSOC);
									$UID = $rowUid['uid'];

									if (empty($UID)) {
										// Create NiDB subject if it does not exist in NiDB
										
										// Before Adding the subject check if the Project exists in NiDB
										$sqlstringPid = "SELECT project_id FROM projects WHERE project_id='$PID'";
//		                                                                PrintSQL($sqlstringPid);
										$resultPid = MySQLiQuery($sqlstringPid, __FILE__, __LINE__);


										if (mysqli_num_rows($resultPid) > 0) {
//											echo "2. Alternate ID:".$AltID.", Site ID:".$SiteID.", Project ID:".$PID."<br>";
											$Uid = AddSubject($filteredRow['altid'], $filteredRow['altid'], $filteredRow['altid'],$projectinfo[$SiteID]);
											$filteredRow[$type] = $Uid;
										}
																		

									} else {
										$filteredRow[$type] = $UID;
									}
									
								} else {
									// Some subjects has different uid in olinnidb.org

									$test_uid = $mrow[$index];

									$sqlstringuid = "SELECT uid FROM subjects WHER uid = $test_uid')";
                                                                        $resultuid = MySQLiQuery($sqlstringuid, __FILE__, __LINE__);

									if (mysqli_num_rows($resultuid) > 0) {
										$filteredRow[$type] = $test_uid;
									} else {
										$AltID = $filteredRow['altid'];
										$sqlstringUid = "SELECT uid FROM subjects WHERE subject_id in (select subject_id from subject_altuid where altuid = '$AltID')";
	//                                                                      PrintSQL($sqlstringUid);
        	                                                                $resultUid = MySQLiQuery($sqlstringUid, __FILE__, __LINE__);
                	                                                        $rowUid = mysqli_fetch_array($resultUid, MYSQLI_ASSOC);
                        	                                                $filteredRow[$type] = $rowUid['uid'];

									}
								}

                                                                }
						}


//						echo $filteredRow['altid'].",".$filteredRow['nidbid'].",".$filteredRow['siteid'].",".$projectinfo[$filteredRow['siteid']].",".$filteredRow['date'].",".$filteredRow['rater'].",".$filteredRow['notes'];

						foreach ($columns['data'] as $colName) {
							$index = array_search($colName, $mheader);
							if ($index !== false) {
								$filteredRow[$colName] = $mrow[$index];
//								echo ",".$colName." = ".$mrow[$index];

								// Defining the variables
								$subjectid = $filteredRow['nidbid'];
								$projectid =$projectinfo[$SiteID];
								$measurename = $colName;
								$measurevalue = $mrow[$index];
								$measurenotes = $filteredRow['notes'];
								$measurerater = $filteredRow['rater'];
								$measurestdate = $filteredRow['date'];

//								echo $subjectid.",".$projectid.",".$measurename.",".$measurevalue.",".$measurenotes.",".$measurerater.",".$measurestdate."<br>";

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
//		print_r($csvdata);
		//		return array('header' => $mheader, 'data' => $csvdata);// data with all the header values
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
//		 PrintSQL($sqlstringEn);
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
		
		 $msttime = strtotime($measurestdate);
		 $mstdate = ($msttime === false) ? '0000-00-00 00:00:00' : date('Y-m-d', $msttime);
//		 echo $mstdate;

                 if ($enrollmentid!=''){
                $sqlstring = "insert ignore into measures (enrollment_id, measure_dateentered, measurename_id, measure_notes, measure_rater,measure_value,measure_startdate,measure_entrydate,measure_createdate,measure_modifydate) values ($enrollmentid, now(),$measurenameid, NULLIF('$measurenotes',''),NULLIF('$measurerater',''),NULLIF('$measurevalue',''),NULLIF('$mstdate',''),now(),now(),now()) on duplicate key update measure_value='$measurevalue', measure_modifydate=now()";
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		return 1;
		 echo "Done Loading Data";
		 }
                 else{  return 0;}
        }



?>
</body>	
<? include("footer.php") ?>
