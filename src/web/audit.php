<?
 // ------------------------------------------------------------------------------
 // NiDB audit.php
 // Copyright (C) 2004 - 2020
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

	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require 'nanodicom.php';

	//header("Content-Type: text/plain");
	ob_end_flush();

	date_default_timezone_set("America/New_York");
	
	/* database connection (nidb) */
	$link2 = mysqli_connect($GLOBALS['cfg']['mysqlhost'], $GLOBALS['cfg']['mysqluser'], $GLOBALS['cfg']['mysqlpassword'], $GLOBALS['cfg']['mysqldatabase']) or die ("Could not connect. Error [" . mysqli_error() . "]  File [" . __FILE__ . "] Line [ " . __LINE__ . "]");
	
	$archivedir = $GLOBALS['cfg']['archivedir'];

	echo "UID\tStudy\tSeries\tInconsistency\tFile\tDB\tDB Subject ID\tDB Study ID\tDB Series ID\n";
	
		/* scan all directories in /archive */
		$subjects = scandir($archivedir);
	
		foreach ($subjects as $subject) {
			
			if ((is_dir("$archivedir/$subject")) && ( $subject != "." ) && ( $subject != ".." ) ) {
			
				/* check if this subject is in the database */
				$sqlstring = "select * from subjects where uid = '$subject'";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
				if (mysqli_num_rows($result) < 1) {
					echo "$subject\t-\t-not in DB\n";
					//echo "$subject\t$study\t$series\tstudy|scanid\t$filescanid\t$studyaltid\t$subjectid\t$studyid\t$seriesid\n";
				}
				else {
					$subjectid = $row['subject_id'];
					$subjectname = trim($row['name']);
					$subjectbirthdate = trim($row['birthdate']);
					$subjectbirthdate = str_replace("-","",$subjectbirthdate);
					$subjectgender = trim($row['gender']);
					
					$studies = scandir("$archivedir/$subject");
					foreach ($studies as $study) {
						if ((is_dir("$archivedir/$subject/$study")) && ( $study != "." ) && ( $study != ".." ) ) {
							/* check if this study is in the database */
							$sqlstring = "select * from subjects a left join enrollment b on a.subject_id = b.subject_id left join studies c on b.enrollment_id = c.enrollment_id where a.uid = '$subject' and c.study_num = $study";
							$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
							$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
							if (mysqli_num_rows($result) < 1) {
								echo "$subject\t$study\t-\tnot in DB\n";
							}
							else {
								
								/* check all the series */
								$enrollmentid = $row['enrollment_id'];
								$studyid = $row['study_id'];
								$studyaltid = $row['study_alternateid'];
								
								$seriess = scandir("$archivedir/$subject/$study");
								foreach ($seriess as $series) {
									if ((is_dir("$archivedir/$subject/$study/$series")) && ( $series != "." ) && ( $series != ".." ) ) {

										$found = 0;
										/* check if this series is in the database */
										$sqlstring = "select * from mr_series where study_id = $studyid and series_num = '$series'";
										$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
										$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
										if (mysqli_num_rows($result) > 0) {
											$found = 1;
											$seriesid = $row['mrseries_id'];
											$numfiles = $row['numfiles'];
											$dbprotocol = $row['series_desc'];
											//echo "DB num files: $numfiles\n";
										}
										$sqlstring = "select * from eeg_series where study_id = $studyid and series_num = '$series'";
										$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
										$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
										if (mysqli_num_rows($result) > 0) {
											$found = 1;
											$seriesid = $row['eegseries_id'];
										}
										$sqlstring = "select * from et_series where study_id = $studyid and series_num = '$series'";
										$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
										$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
										if (mysqli_num_rows($result) > 0) {
											$found = 1;
											$seriesid = $row['etseries_id'];
										}
										$sqlstring = "select * from ppi_series where study_id = $studyid and series_num = '$series'";
										$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
										$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
										if (mysqli_num_rows($result) > 0) {
											$found = 1;
											$seriesid = $row['ppiseries_id'];
										}

										if ($found) {
											/* check the dicom files */
											$dicoms = glob("$archivedir/$subject/$study/$series/dicom/*.dcm");
											//print_r($dicoms);
											$dcmcount = count($dicoms);
											if ($dcmcount > 0) {
												$filename = $dicoms[0];
												
												//echo "$filename\n";
												/* open the dicom file, check the 3 important tags */
												$dicom = Nanodicom::factory($filename, 'simple');
												$dicom->parse(array(array(0x0010, 0x0010), array(0x0010, 0x0030), array(0x0010, 0x0040))); /* patient  name,dob,sex */
												// Only a small subset of the dictionary entries were loaded
												$dicom->profiler_diff('parse');
												//echo 'Patient name if exists: '.$dicom->value(0x0010, 0x0010)."\n"; // Patient Name if exists
												// This will return nothing because dictionaries were not loaded
												//echo 'Patient name should be empty here: '.$dicom->PatientName."\n";
												
												$filesubjectname = trim($dicom->value(0x0010, 0x0010));
												$filesubjectdob = trim($dicom->value(0x0010, 0x0030));
												$filesubjectsex = trim($dicom->value(0x0010, 0x0040));
												$fileprotocol = trim($dicom->value(0x0018, 0x1030));
												$fileseriesdesc = trim($dicom->value(0x0008, 0x103E));
												$filescanid = trim($dicom->value(0x0010, 0x0020));
												
												//print_r($dicom);
												
												if (strcasecmp($subjectname,$filesubjectname) != 0) {
													if (($subjectname == "") && ($filesubjectname) != "") {
														//echo "$subject,$study,$series, patientname not consistent (File: $filesubjectname , DB: $subjectname)\n";
														echo "$subject\t$study\t$series\tpatientname\t$filesubjectname\t$subjectname\t$subjectid\t$studyid\t$seriesid\n";
													}
													elseif (($filesubjectname == "") && ($subjectname) != "") {
														//echo "$subject,$study,$series, patientname not consistent (File: $filesubjectname , DB: $subjectname)\n";
														echo "$subject\t$study\t$series\tpatientname\t$filesubjectname\t$subjectname\t$subjectid\t$studyid\t$seriesid\n";
													}
													else {
														if ((stristr($subjectname, $filesubjectname) === false) && (stristr($filesubjectname, $subjectname) === false)) {
															//echo "$subject,$study,$series, patientname not consistent (File: $filesubjectname , DB: $subjectname)\n";
															echo "$subject\t$study\t$series\tpatientname\t$filesubjectname\t$subjectname\t$subjectid\t$studyid\t$seriesid\n";
														}
													}
												}
												if ($subjectbirthdate != $filesubjectdob) {
													if (($filesubjectdob != "") && ($subjectbirthdate != "10000101")) {
														//echo "$subject,$study,$series, patientdob not consistent (File: $filesubjectdob , DB: $subjectbirthdate)\n";
														echo "$subject\t$study\t$series\tpatientdob\t$filesubjectdob\t$subjectbirthdate\t$subjectid\t$studyid\t$seriesid\n";
													}
												}
												if ($subjectgender != $filesubjectsex) {
													//echo "$subject,$study,$series, patientsex not consistent (File: $filesubjectsex , DB: $subjectgender)\n";
													echo "$subject\t$study\t$series\tpatientsex\t$filesubjectsex\t$subjectgender\t$subjectid\t$studyid\t$seriesid\n";
												}
												if ($numfiles != $dcmcount) {
													//echo "$subject,$study,$series, numfiles not consistent (File: $dcmcount , DB: $numfiles)\n";
													echo "$subject\t$study\t$series\tnumfiles\t$dcmcount\t$numfiles\t$subjectid\t$studyid\t$seriesid\n";
												}
												
												/*
												if (($dbprotocol != $fileprotocol) && ($dbprotocol != $fileseriesdesc)) {
													if ((strtolower($dbprotocol) != "circle_localizer") && (strtolower($fileprotocol) != "circle scout")) {
														if ((strtolower($dbprotocol) != "circle localizer") && (strtolower($fileprotocol) != "circle scout")) {
														
														
															if ((stristr($dbprotocol, $fileprotocol) === false) && (stristr($fileprotocol, $dbprotocol) === false)) {
																echo "$subject,$study,$series, protocol not consistent (File: $fileprotocol , DB: $dbprotocol)\n";
															}
															
															
														}
													}
												}
												*/
												
												if ($studyaltid != $filescanid) {
													echo "$subject\t$study\t$series\tstudy|scanid\t$filescanid\t$studyaltid\t$subjectid\t$studyid\t$seriesid\n";
												}
												
												unset($dicom);
											}
										}
										else {
											echo "$subject,$study,$series not in DB\n";
										}
									}
								}
								
							}
						}
					}
				}
				ob_flush();
			}
		}
?>