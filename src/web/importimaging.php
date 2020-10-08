<?
 // ------------------------------------------------------------------------------
 // NiDB importimaging.php
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

	define("LEGIT_REQUEST", true);

	session_start();
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Import Imaging Data</title>
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
	$datalocation = GetVariable("datalocation");
	$nfspath = GetVariable("nfspath");
	$projectid = GetVariable("projectid");
	$modality = GetVariable("modality");
	$subjectcriteria = GetVariable("subjectcriteria");
	$studycriteria = GetVariable("studycriteria");
	$seriescriteria = GetVariable("seriescriteria");
	$uploadid = GetVariable("uploadid");
	
	/* determine action */
	switch ($action) {
		case 'newimport':
			NewImport($datalocation, $nfspath, $projectid, $modality, $subjectcriteria, $studycriteria, $seriescriteria);
			DisplayImportList();
			break;
		case 'displayimportlist':
			DisplayImportList();
			break;
		case 'displayimport':
			DisplayImport($uploadid);
			break;
		default:
			DisplayNewImportForm();
			DisplayImportList();
	}

	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- DisplayNewImportForm --------------- */
	/* -------------------------------------------- */
	function DisplayNewImportForm() {
		?>
		<table class="entrytable" style="border:0px" cellspacing="8">
			<form method="post" action="importimaging.php" enctype="multipart/form-data">
			<input type="hidden" name="action" value="newimport">
			<tr>
				<td class="label" valign="top">Data Location</td>
				<td valign="top">
					<input type="radio" name="datalocation" value="nfs">NFS path: <input type="text" name="nfspath"><br>
					<input type="radio" name="datalocation" value="web" checked>Web <input type="file" name="imagingfiles[]" multiple>
				</td>
			</tr>
			<tr>
				<td class="label" valign="top">Project</td>
				<td valign="top">
					<?=DisplayProjectSelectBox(true, "projectid", "projectid", "", false, "", 350, 50, true);?>
				</td>
			</tr>
			<tr>
				<td class="label" valign="top">Modality</td>
				<td valign="top">
					<select name="modality" required>
						<option value="">Select modality</option>
						<?
							$modalities = GetModalityList();
							foreach ($modalities as $modality) {
								?><option value="<?=$modality?>"><?=$modality?></option><?
							}
						?>
						<option value="unknown">UNKNOWN - Have NiDB try to guess the modality</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label" valign="top">Matching Criteria<br><span class="tiny">Criteria used to<br>organize incoming data</span></td>
				<td valign="top">
					<table cellspacing="0" cellpadding="10">
						<tr>
							<td style="background-color: #ddd; color: #333" class="label">Subject</td>
							<td style="border-top: #ddd 2px solid">
								<table>
									<tr>
										<td><input type="radio" name="subjectcriteria" value="patientid" checked></td>
										<td style="vertical-align: middle;">
											Patient<b>ID</b> <span class="tiny">DICOM (0010, 0020)</span>
										</td>
									</tr>
									<tr>
										<td colspan="2" align="center">- or -</td>
									</tr>
									<tr>
										<td style="vertical-align: middle;"><input type="radio" name="subjectcriteria" value="namesexdob"></td>
										<td>
											Patient<b>Name</b> <span class="tiny">DICOM (0010, 0010)</span><br>
											Patient<b>BirthDate</b> <span class="tiny">DICOM (0010, 0030)</span><br>
											Patient<b>Sex</b> <span class="tiny">DICOM (0010, 0040)</span>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td colspan="2"></td>
						</tr>
						<tr>
							<td style="background-color: #ddd; color: #333" class="label">Study</td>
							<td style="border-top: #ddd 2px solid">
								<table>
									<tr>
										<td style="vertical-align: middle;"><input type="radio" name="studycriteria" value="modalitystudydate" checked></td>
										<td>
											Modality <span class="tiny">DICOM (0008, 0020)</span><br>
											StudyDate <span class="tiny">DICOM (0008, 0020)</span><br>
											StudyTime <span class="tiny">DICOM (0008, 0030)</span>
										</td>
									</tr>
									<tr>
										<td colspan="2" align="center">- or -</td>
									</tr>
									<tr>
										<td style="vertical-align: middle;"><input type="radio" name="studycriteria" value="studyuid"></td>
										<td>
											StudyInstanceUID <span class="tiny">DICOM (0020,000D)</span>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td colspan="2"></td>
						</tr>
						<tr>
							<td style="background-color: #ddd; color: #333" class="label">Series</td>
							<td style="border-top: #ddd 2px solid">
								<table>
									<tr>
										<td style="vertical-align: middle;"><input type="radio" name="seriescriteria" value="seriesnum"></td>
										<td>
											SeriesNumber <span class="tiny">DICOM (0020,0011)</span>
										</td>
									</tr>
									<tr>
										<td colspan="2" align="center">- or -</td>
									</tr>
									<tr>
										<td style="vertical-align: middle;"><input type="radio" name="seriescriteria" value="seriesdate" checked></td>
										<td>
											SeriesDate <span class="tiny">DICOM (0008, 0021)</span><br>
											SeriesTime <span class="tiny">DICOM (0008, 0031)</span>
										</td>
									</tr>
									<tr>
										<td colspan="2" align="center">- or -</td>
									</tr>
									<tr>
										<td style="vertical-align: middle;"><input type="radio" name="seriescriteria" value="seriesuid"></td>
										<td>
											SeriesInstanceUID <span class="tiny">DICOM (0020,000E)</span>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<input type="submit" value="Import">
		<br><br>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- NewImport -------------------------- */
	/* -------------------------------------------- */
	function NewImport($datalocation, $nfspath, $projectid, $modality, $subjectcriteria, $studycriteria, $seriescriteria) {
		
		/* prepare fields for SQL */
		$datalocation = mysqli_real_escape_string($GLOBALS['linki'], $datalocation);
		$nfspath = mysqli_real_escape_string($GLOBALS['linki'], $nfspath);
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], $projectid);
		$modality = mysqli_real_escape_string($GLOBALS['linki'], $modality);
		$subjectcriteria = mysqli_real_escape_string($GLOBALS['linki'], $subjectcriteria);
		$studycriteria = mysqli_real_escape_string($GLOBALS['linki'], $studycriteria);
		$seriescriteria = mysqli_real_escape_string($GLOBALS['linki'], $seriescriteria);
		
		if ($modality == "unknown")
			$guessmodality = 1;
		else
			$guessmodality = 'null';
		
		/* uploads table:
		   upload_startdate, upload_enddate, upload_status, upload_log, upload_source, upload_nfsdir, upload_destprojectid, upload_modality, upload_guessmodality
		  */
		
		/* create the upload and get the upload_id */
		$sqlstring = "insert into uploads (upload_startdate, upload_status, upload_log, upload_source, upload_datapath, upload_destprojectid, upload_modality, upload_guessmodality, upload_subjectcriteria, upload_studycriteria, upload_seriescriteria) values (now(), 'uploading', 'Beginning upload', '$datalocation', '$nfspath', $projectid, '$modality', $guessmodality, '$subjectcriteria', '$studycriteria', '$seriescriteria')";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$uploadid = mysqli_insert_id($GLOBALS['linki']);
		
		/* create a temp directory in upload */
		if ($GLOBALS['cfg']['uploaddir'] == "") {
			/* update the upload_log */
			$msg = mysqli_real_escape_string($GLOBALS['linki'], $msg);
			$sqlstring = "update uploads set upload_status = 'error', upload_enddate = now(), upload_log = concat(upload_log, 'NiDB upload directory [uploaddir] is not set\n') where upload_id = $uploadid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			
			DisplayErrorMessage("NiDB Configuration Error", "Variable [uploaddir] is not set. Contact NiDB system administrator.");
			return;
		}
		$savepath = $GLOBALS['cfg']['uploaddir'] . "/" . date("YmdHisv") . "_$uploadid";
		mkdir($savepath, 0, true);
		chmod($savepath, 0777);
		
		if ($datalocation == "web") {
			$sqlstring = "update uploads set upload_datapath = '$savepath' where upload_id = $uploadid";
			//PrintSQL($sqlstring);
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		
		$status = "uploadcomplete";
		echo "<ul>";
		/* go through all the files and save them */
		foreach ($_FILES['imagingfiles']['name'] as $i => $name) {
			$files[] = $name;
			if (move_uploaded_file($_FILES['imagingfiles']['tmp_name'][$i], "$savepath/$name")) {
				
				$msg = "Received file [$name]. Size is [" . number_format($_FILES['imagingfiles']['size'][$i]) . "] bytes";
				echo "<li>$msg";
				chmod("$savepath/$name", 0777);
				
				/* update the upload_log */
				$msg = mysqli_real_escape_string($GLOBALS['linki'], $msg);
				$sqlstring = "update uploads set upload_log = concat(upload_log, '$msg\n') where upload_id = $uploadid";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
			else {
				$msg = "An error occured moving file [" . $_FILES['imagingfiles']['tmp_name'][$i] . "] to [$savepath/$name]. Error message [" . $_FILES['imagingfiles']['error'][$i] . "]";
				echo "<li>$msg";
				$status = "uploaderror";
				
				/* update the upload_log */
				$msg = mysqli_real_escape_string($GLOBALS['linki'], $msg);
				$sqlstring = "update uploads set upload_log = concat(upload_log, '$msg\n') where upload_id = $uploadid";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
		}
		echo "</ul>";
		/* update the upload_status, upload_enddate, and upload_originalfilelist */
		$msg = mysqli_real_escape_string($GLOBALS['linki'], $msg);
		$filelist = mysqli_real_escape_string($GLOBALS['linki'], implode2(",", $files));
		$sqlstring = "update uploads set upload_status = '$status', upload_enddate = now(), upload_originalfilelist = '$filelist' where upload_id = $uploadid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}


	/* -------------------------------------------- */
	/* ------- DisplayImportList ------------------ */
	/* -------------------------------------------- */
	function DisplayImportList() {
		
		$sqlstring = "select * from uploads order by upload_startdate desc limit 10";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0){
			?>
			<table class="tiered">
				<thead>
					<th>Date</th>
					<th>Status</th>
					<th>Datapath</th>
					<th>Project</th>
					<th>Modality</th>
				</thead>
			<?
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$uploadid = $row['upload_id'];
				$startdate = $row['upload_startdate'];
				$enddate = $row['upload_enddate'];
				$status = $row['upload_status'];
				$log = $row['upload_log'];
				$originalfilelist = $row['upload_originalfilelist'];
				$source = $row['upload_source'];
				$datapath = $row['upload_datapath'];
				$destprojectid = $row['upload_destprojectid'];
				$modality = $row['upload_modality'];
				$guessmodality = $row['upload_guessmodality'];
				$subjectcriteria = $row['upload_subjectcriteria'];
				$studycriteria = $row['upload_studycriteria'];
				$seriescriteria = $row['upload_seriescriteria'];
				?>
				<tr>
					<td><?=$startdate?> - <?=$enddate?></td>
					<td><a href="importimaging.php?action=displayimport&uploadid=<?=$uploadid?>"><?=$status?></a></td>
					<td><tt><?=$datapath?></tt></td>
					<td><?=$destprojectid?></td>
					<td><?=$modality?></td>
				</tr>
				<tr>
					<td class="details" colspan="5">
						<details>
						<summary>Details</summary>
						<table style="all: unset;">
							<tr>
								<td style="text-align: right; vertical-align: top; font-weight: bold;">Log</td>
								<td><tt><pre><?=$log?></pre></tt></td>
							</tr>
							<tr>
								<td style="text-align: right; vertical-align: top; font-weight: bold;">Uploaded files</td>
								<td><?=$originalfilelist?></td>
							</tr>
							<tr>
								<td style="text-align: right; vertical-align: top; font-weight: bold;">Source</td>
								<td><?=$source?></td>
							</tr>
							<tr>
								<td style="text-align: right; vertical-align: top; font-weight: bold;">Source Data path</td>
								<td><?=$datapath?></td>
							</tr>
							<tr>
								<td style="text-align: right; vertical-align: top; font-weight: bold;">Matching Criteria</td>
								<td>
									Subject: <?=$subjectcriteria?><br>
									Study: <?=$studycriteria?><br>
									Series: <?=$seriescriteria?>
								</td>
							</tr>
						</table>
						</details>
					</td>
				</tr>
				<?
			}
			?>
			</table>
			<?
		}
		else {
			?>No current or recent uploads<?
		}
	}


	/* -------------------------------------------- */
	/* ------- DisplayImport ---------------------- */
	/* -------------------------------------------- */
	function DisplayImport($uploadid) {
		
		$sqlstring = "select * from uploads where upload_id = $uploadid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0) {
			$uploadid = $row['upload_id'];
			$startdate = $row['upload_startdate'];
			$enddate = $row['upload_enddate'];
			$status = $row['upload_status'];
			$log = $row['upload_log'];
			$originalfilelist = $row['upload_originalfilelist'];
			$source = $row['upload_source'];
			$datapath = $row['upload_datapath'];
			$destprojectid = $row['upload_destprojectid'];
			$modality = $row['upload_modality'];
			$guessmodality = $row['upload_guessmodality'];
			$subjectcriteria = $row['upload_subjectcriteria'];
			$studycriteria = $row['upload_studycriteria'];
			$seriescriteria = $row['upload_seriescriteria'];
			
			$filelist = explode(",", $originalfilelist);
			
			?>
			<table style="all: unset;">
				<tr>
					<td style="text-align: right; vertical-align: top; font-weight: bold;">Log</td>
					<td><tt><pre><?=$log?></pre></tt></td>
				</tr>
				<tr>
					<td style="text-align: right; vertical-align: top; font-weight: bold;">Uploaded files</td>
					<td><?=implode2("<br>", $filelist)?></td>
				</tr>
				<tr>
					<td style="text-align: right; vertical-align: top; font-weight: bold;">Source</td>
					<td><?=$source?></td>
				</tr>
				<tr>
					<td style="text-align: right; vertical-align: top; font-weight: bold;">Source Data path</td>
					<td><?=$datapath?></td>
				</tr>
				<tr>
					<td style="text-align: right; vertical-align: top; font-weight: bold;">Matching Criteria</td>
					<td>
						Subject: <?=$subjectcriteria?><br>
						Study: <?=$studycriteria?><br>
						Series: <?=$seriescriteria?>
					</td>
				</tr>
			</table>
			
			<br><br>
			
			Subject/Study/Series
			<table>
				<thead>
					<th>PatientID</th>
					<th>Name</th>
					<th>DOB</th>
					<th>Sex</th>
				</thead>
			<?
			$sqlstringA = "select * from upload_subjects where upload_id = $uploadid";
			while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
				$uploadsubjectid = $rowA['uploadsubject_id'];
				$patientid = $rowA['uploadsubject_patientid'];
				$name = $rowA['uploadsubject_name'];
				$dob = $rowA['uploadsubject_dob'];
				?>
				<tr>
					<td><?=$patientid?></td>
					<td><?=$name?></td>
					<td><?=$dob?></td>
					<td><?=$sex?></td>
				</tr>
				<tr>
					<td colspan="4" style="padding-left: 15x;">
						<?
							$sqlstringB = "select * from upload_studies where uploadsubject_id = $uploadsubjectid";
							while ($rowB = mysqli_fetch_array($resultB, MYSQLI_ASSOC)) {
								$uploadstudyid = $rowB['uploadstudy_id'];
								$instanceuid = $rowB['uploadstudy_instanceuid'];
								$desc = $rowB['uploadstudy_desc'];
								$date = $rowB['uploadstudy_date'];
								$modality = $rowB['uploadstudy_modality'];
								$datatype = $rowB['uploadstudy_datatype'];
								$equipment = $rowB['uploadstudy_equipment'];
								$operator = $rowB['uploadstudy_operator'];
								?>
								<details>
								<summary>Study - <?=$desc?> <?=$date?> <?=$modality?> <?=$datatype?> <?=$equipment?></summary>
								<?
									$sqlstringC = "select * from upload_series where uploadstudy_id = $uploadstudyid";
									while ($rowC = mysqli_fetch_array($resultC, MYSQLI_ASSOC)) {
										$uploadseriesid = $rowC['uploadseries_id'];
										$instanceuid = $rowC['uploadseries_instanceuid'];
										$desc = $rowC['uploadseries_desc'];
										$date = $rowC['uploadseries_date'];
										$protocol = $rowC['uploadseries_protocol'];
										$num = $rowC['uploadseries_num'];
										$numfiles = $rowC['uploadseries_numfiles'];
										$tr = $rowC['uploadseries_tr'];
										$te = $rowC['uploadseries_te'];
										$slicespacing = $rowC['uploadseries_slicespacing'];
										$slicethickness = $rowC['uploadseries_slicethickness'];
										$rows = $rowC['uploadseries_rows'];
										$cols = $rowC['uploadseries_cols'];
										$filelist = $rowC['uploadseries_filelist'];
										?>
										<details>
										<summary>Series <?=$num?> - <?=$desc?> <?=$date?> <?=$cols?>x<?=$rows?></summary>
										<?
											$files = explode(",", $filelist);
											echo implode2("<br>", $files);
										?>
										</details>
										<?
									}
								
								?>
								</details>
								<?
							}
						?>
					</td>
				</tr>
				<?
			}
			?>
			</table>
			<?
		}
		else {
			?>Upload not found<?
		}
	}
	
?>
<? include("footer.php") ?>