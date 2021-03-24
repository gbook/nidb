<?
 // ------------------------------------------------------------------------------
 // NiDB importimaging.php
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
	$uploadseriesid = GetVariable("uploadseriesid");
	$displayall = GetVariable("displayall");
	$datestart = GetVariable("datestart");
	$dateend = GetVariable("dateend");
	$keyword = GetVariable("keyword");
	
	/* determine action */
	switch ($action) {
		case 'newimportform':
			DisplayNewImportForm();
			break;
		case 'newimport':
			NewImport($datalocation, $nfspath, $projectid, $modality, $subjectcriteria, $studycriteria, $seriescriteria);
			DisplayImportList($displayall);
			break;
		case 'queueforarchive':
			QueueUploadForArchive($uploadid, $uploadseriesid);
			DisplayImport($uploadid);
			break;
		case 'reparse':
			ReParseUpload($uploadid);
			DisplayImport($uploadid);
			break;
		case 'displayimportlist':
			DisplayImportList($displayall);
			break;
		case 'viewdcmrcvlogs':
			DisplayDcmRcvLogs($datestart, $dateend, $keyword);
			break;
		case 'displayimport':
			DisplayImport($uploadid);
			break;
		default:
			DisplayImportPage();
	}

	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- DisplayImportPage ------------------ */
	/* -------------------------------------------- */
	function DisplayImportPage() {
		?>
		<div class="ui container">
			<div class="ui two column grid">
				<div class="column">
					<h1 class="ui header">Imaging Import</h1>
				</div>
				<div class="right aligned column">
					<button class="ui primary large button" onClick="window.location.href='importimaging.php?action=newimportform'; return false;"><i class="plus square outline icon"></i> New Import</button>
				</div>
			</div>
			<?DisplayImportList(0);?>
		</div>

		<!--
		<table width="100%" cellpadding="5">
			<tr>
				<td style="font-weight: bold; font-size:16pt; color: #333;">New Import</td>
				<td style="font-weight: bold; font-size:16pt; color: #333;">Most recent 10 imports<br><span style="font-size: 12pt; font-weight: normal"><a href="importimaging.php?action=displayimportlist&displayall=1">View all</a></span></td>
			</tr>
			<tr>
				<td valign="top"><?DisplayNewImportForm();?></td>
				<td valign="top"><?DisplayImportList(0);?></td>
			</tr>
		</table>-->
		<?
	}
	
	/* -------------------------------------------- */
	/* ------- DisplayNewImportForm --------------- */
	/* -------------------------------------------- */
	function DisplayNewImportForm() {
		?>
		<div class="ui container">
			<div class="ui top attached grey segment">
				<h2 class="ui header">New Import</h2>
			</div>
			<form method="post" action="importimaging.php" enctype="multipart/form-data" class="ui form attached fluid segment">
				<input type="hidden" name="action" value="newimport">
				<div class="ui grid">
					
					<div class="three wide column"><h3 class="ui grey right aligned header">Data Location</h3></div>
					<div class="thirteen wide column">
						<div class="field">
							<label><input type="radio" name="datalocation" value="web" checked> Local Computer</label>
							<input type="file" name="imagingfiles[]" multiple>
						</div>
						<div class="field">
							<label><input type="radio" name="datalocation" value="nfs"> NFS path</label>
							<input class="ui fluid input" type="text" name="nfspath" placeholder="/path/accessible/to/nidb/server">
						</div>
					</div>
					
					<div class="three wide column"><h3 class="ui grey right aligned header">Data Modality</h3></div>
					<div class="thirteen wide column">
						<select class="ui dropdown" name="modality" required>
							<option value="auto" selected>Automatically detect (DICOM only)</option>
							<?
								$modalities = GetModalityList();
								foreach ($modalities as $modality) {
									?><option value="<?=$modality?>"><?=$modality?></option><?
								}
							?>
							<option value="unknown">UNKNOWN - Have NiDB try to guess the modality</option>
						</select>
					</div>

					<div class="three wide column"><h3 class="ui grey right aligned header">Destination Project</h3></div>
					<div class="thirteen wide column">
						<select name="projectid" class="ui dropdown" required>
							<option value="">Select project...</option>
							<?
								$sqlstring = "select * from projects a left join user_project b on a.project_id = b.project_id where b.user_id = (select user_id from users where username = '" . $_SESSION['username'] . "') and a.instance_id = '" . $_SESSION['instanceid'] . "' order by project_name";
								$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
								while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
									$project_id = $row['project_id'];
									$project_name = $row['project_name'];
									$project_costcenter = $row['project_costcenter'];
									?>
									<option value="<?=$project_id?>"><?=$project_name?> (<?=$project_costcenter?>)</option>
									<?
								}
							?>
						</select>
					</div>
					
					<div class="three wide column"><h3 class="ui grey right aligned header">Matching Criteria</h3></div>
					<div class="ten wide column">
					
						<div class="ui grey segment">
							<div class="ui grid">
								<div class="four wide right aligned column">
									<h3 class="ui blue header">Subject</h3>
									<span class="tiny">If DICOM Patient field(s) are blank then PatientID will be read from DICOM file's parent directory</span>
								</div>
								<div class="eight wide column">
									<div class="ui grid">
										<div class="two wide right aligned column">
											<input type="radio" name="subjectcriteria" value="patientid" checked>
										</div>
										<div class="fourteen wide column">
											Patient<b>ID</b> <span class="tiny">DICOM (<tt>0010</tt>, <tt>0020</tt>)</span>
										</div>
									</div>
									<div class="ui horizontal divider">Or</div>
									<div class="ui grid">
										<div class="two wide right aligned column">
											<input type="radio" name="subjectcriteria" value="specificpatientid" >
										</div>
										<div class="fourteen wide column">
											Specific PatientID <input type="text" name="userspecifiedpatientid" placeholder="Enter PatientID"><br><span class="tiny">This PatientID will be applied to all imported data</span>
										</div>
									</div>
									<div class="ui horizontal divider">Or</div>
									<div class="ui grid">
										<div class="two wide right aligned column">
											<input type="radio" name="subjectcriteria" value="patientidfromdir" >
										</div>
										<div class="fourteen wide column">
											PatientID from directory name
										</div>
									</div>
									<div class="ui horizontal divider">Or</div>
									<div class="ui grid">
										<div class="two wide right aligned column">
											<input type="radio" name="subjectcriteria" value="namesexdob" >
										</div>
										<div class="fourteen wide column">
											Patient<b>Name</b> <span class="tiny">DICOM (<tt>0010</tt>, <tt>0010</tt>)</span><br>
											Patient<b>BirthDate</b> <span class="tiny">DICOM (<tt>0010</tt>, <tt>0030</tt>)</span><br>
											Patient<b>Sex</b> <span class="tiny">DICOM (<tt>0010</tt>, <tt>0040</tt>)</span>
										</div>
									</div>
								</div>
							</div>
						</div>
						
						<div class="ui grey segment">
							<div class="ui grid">
								<div class="four wide right aligned column">
									<h3 class="ui blue header">Study</h3>
									<span class="tiny">If DICOM Study Date/Time field(s) are blank then StudyInstanceUID will be used to uniquely identify studies</span>
								</div>
								<div class="eight wide column">
									<div class="ui grid">
										<div class="two wide right aligned column">
											<input type="radio" name="studycriteria" value="modalitystudydate" checked>
										</div>
										<div class="fourteen wide column">
											Modality <span class="tiny">DICOM (<tt>0008</tt>, <tt>0020</tt>)</span><br>
											StudyDate <span class="tiny">DICOM (<tt>0008</tt>, <tt>0020</tt>)</span><br>
											StudyTime <span class="tiny">DICOM (<tt>0008</tt>, <tt>0030</tt>)</span>
										</div>
									</div>
									<div class="ui horizontal divider">Or</div>
									<div class="ui grid">
										<div class="two wide right aligned column">
											<input type="radio" name="studycriteria" value="studyuid" >
										</div>
										<div class="fourteen wide column">
											StudyInstanceUID <span class="tiny">DICOM (<tt>0020</tt>, <tt>000D</tt>)</span>
										</div>
									</div>
									<div class="ui horizontal divider">Or</div>
									<div class="ui grid">
										<div class="two wide right aligned column">
											<input type="radio" name="studycriteria" value="studyid" >
										</div>
										<div class="fourteen wide column">
											StudyID <span class="tiny">DICOM (<tt>0020</tt>, <tt>0010</tt>)</span>
										</div>
									</div>
								</div>
							</div>
						</div>

						<div class="ui grey segment">
							<div class="ui grid">
								<div class="four wide right aligned column">
									<h3 class="ui blue header">Series</h3>
									<span class="tiny">If DICOM SeriesNumber or Date/Time field(s) are blank then SeriesInstanceUID will be used to uniquely identify series</span>
								</div>
								<div class="eight wide column">
									<div class="ui grid">
										<div class="two wide right aligned column">
											<input type="radio" name="seriescriteria" value="seriesnum" checked>
										</div>
										<div class="fourteen wide column">
											SeriesNumber <span class="tiny">DICOM (<tt>0020</tt>, <tt>0011</tt>)</span>
										</div>
									</div>
									<div class="ui horizontal divider">Or</div>
									<div class="ui grid">
										<div class="two wide right aligned column">
											<input type="radio" name="seriescriteria" value="seriesdate" >
										</div>
										<div class="fourteen wide column">
											SeriesDate <span class="tiny">DICOM (<tt>0008</tt>, <tt>0021</tt>)</span><br>
											SeriesTime <span class="tiny">DICOM (<tt>0008</tt>, <tt>0031</tt>)</span>
										</div>
									</div>
									<div class="ui horizontal divider">Or</div>
									<div class="ui grid">
										<div class="two wide right aligned column">
											<input type="radio" name="seriescriteria" value="seriesuid" >
										</div>
										<div class="fourteen wide column">
											SeriesInstanceUID <span class="tiny">DICOM (<tt>0020</tt>, <tt>000E</tt>)</span>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</form>
			<div class="ui bottom attached right aligned segment">
				<button class="ui button" onClick="window.location.href='importimaging.php'; return false;">Cancel</button> <button class="ui primary button">Upload</button>
			</div>
			
			</form>
		</div>
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
		   upload_startdate, upload_enddate, upload_status, upload_source, upload_nfsdir, upload_destprojectid, upload_modality, upload_guessmodality
		  */
		
		/* create the upload and get the upload_id */
		$sqlstring = "insert into uploads (upload_startdate, upload_status, upload_source, upload_datapath, upload_destprojectid, upload_modality, upload_guessmodality, upload_subjectcriteria, upload_studycriteria, upload_seriescriteria) values (now(), 'uploading', '$datalocation', '$nfspath', $projectid, '$modality', $guessmodality, '$subjectcriteria', '$studycriteria', '$seriescriteria')";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$uploadid = mysqli_insert_id($GLOBALS['linki']);
		
		AppendUploadLog($uploadid, "Beginning upload from IP address [" . $_SERVER['REMOTE_ADDR'] . "]");
		
		/* create a temp directory in upload */
		if ($GLOBALS['cfg']['uploaddir'] == "") {
			AppendUploadLog($uploadid, "NiDB upload directory [uploaddir] is not set");
			
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
		else {
			$sqlstring = "update uploads set upload_datapath = '$nfspath' where upload_id = $uploadid";
			//PrintSQL($sqlstring);
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		
		$status = "uploadcomplete";
		if ($datalocation == "web") {		
			echo "<ul>";
			/* go through all the files and save them */
			foreach ($_FILES['imagingfiles']['name'] as $i => $name) {
				$files[] = $name;
				if (move_uploaded_file($_FILES['imagingfiles']['tmp_name'][$i], "$savepath/$name")) {
					
					$msg = "Received file [$name]. Size is [" . number_format($_FILES['imagingfiles']['size'][$i]) . "] bytes";
					echo "<li>$msg";
					chmod("$savepath/$name", 0777);
					
					AppendUploadLog($uploadid, $msg);
				}
				else {
					$msg = "An error occured moving file [" . $_FILES['imagingfiles']['tmp_name'][$i] . "] to [$savepath/$name]. Error message [" . $_FILES['imagingfiles']['error'][$i] . "]";
					echo "<li>$msg";
					$status = "uploaderror";
					
					AppendUploadLog($uploadid, $msg);
				}
			}
			echo "</ul>";
		}
		
		/* update the upload_status, upload_enddate, and upload_originalfilelist */
		$msg = mysqli_real_escape_string($GLOBALS['linki'], $msg);
		$filelist = mysqli_real_escape_string($GLOBALS['linki'], implode2(",", $files));
		$sqlstring = "update uploads set upload_status = '$status', upload_enddate = now(), upload_originalfilelist = '$filelist' where upload_id = $uploadid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}


	/* -------------------------------------------- */
	/* ------- DisplayDcmRcvLogs ------------------ */
	/* -------------------------------------------- */
	function DisplayDcmRcvLogs($datestart, $dateend, $keyword) {
		$datestart = mysqli_real_escape_string($GLOBALS['linki'], $datestart);
		$dateend = mysqli_real_escape_string($GLOBALS['linki'], $dateend);
		$keyword = mysqli_real_escape_string($GLOBALS['linki'], $keyword);
		
		$sqlstring = "select * from upload_logs where upload_id = 0";
		if ($datestart != "")
			$sqlstring .= " and log_date > '$datestart'";
		
		if ($dateend != "")
			$sqlstring .= " and log_date < '$dateend'";

		if ($keyword != "")
			$sqlstring .= " and log_msg like '%$keyword%'";
		
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		?>
		<form method="post" action="importimaging.php">
		<input type="hidden" name="action" value="viewdcmrcvlogs">
		<table class="entrytable">
			<tr>
				<td>Date</td>
				<td><input type="date" name="datestart" value="<?=$datestart?>"> - <input type="date" name="dateend" value="<?=$dateend?>"></td>
			</tr>
			<tr>
				<td>Keyword</td>
				<td><input type="text" name="keyword" value="<?=$keyword?>"></td>
			</tr>
		</table>
		<input type="submit" value="Update">
		</form>
		
		<?=mysqli_num_rows($result)?> entries</span>
		<tt><pre><?
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$date = $row['log_date'];
				$msg = $row['log_msg'];
				echo "[$date] $msg\n";
			}
		?>
		</pre></tt>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayImportList ------------------ */
	/* -------------------------------------------- */
	function DisplayImportList($displayall) {
		
		if ($displayall == "1") {
			$sqlstring = "select * from uploads order by upload_startdate desc";
		}
		else {
			$sqlstring = "select * from uploads order by upload_startdate desc limit 10";
		}
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0){
			?>
			<table class="ui selectable celled very compact table">
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
				//$log = $row['upload_log'];
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
				<tr>
					<td><?=$startdate?> - <?=$enddate?></td>
					<td><a href="importimaging.php?action=displayimport&uploadid=<?=$uploadid?>"><?=$status?></a></td>
					<td><tt><?=$datapath?></tt></td>
					<td><?=$destprojectid?></td>
					<td><?=$modality?></td>
				</tr>
				<tr>
					<td colspan="5">
						<?DisplayStatusSteps($status);?>
						
						<details>
						<summary>Details</summary>
						<table style="all: unset;">
							<tr>
								<td style="text-align: right; vertical-align: top; font-weight: bold;">Log</td>
								<td>
									<details>
									<?
										$sqlstringA = "select * from upload_logs where upload_id = $uploadid";
										$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
									?>
									<summary>View Log <span class="tiny"><?=mysqli_num_rows($resultA)?> entries</span></summary>
									<tt><pre><?
										while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
											$date = $rowA['log_date'];
											$msg = $rowA['log_msg'];
											echo "[$date] $msg\n";
										}
									?></pre></tt>
									</details>
								</td>
							</tr>
							<tr>
								<td style="text-align: right; vertical-align: top; font-weight: bold;">Uploaded files</td>
								<td>
									<details>
									<summary>File list (<?=count($filelist);?> files)</summary>
										<tt style="font-size:8pt"><?=implode2("<br>", $filelist)?></tt>
									</details>
								</td>
							</tr>
							<tr>
								<td style="text-align: right; vertical-align: top; font-weight: bold;">Source</td>
								<td><?=$source?></td>
							</tr>
							<tr>
								<td style="text-align: right; vertical-align: top; font-weight: bold;">Source Data path</td>
								<td><tt><?=$datapath?></tt></td>
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
	/* ------- ReParseUpload ---------------------- */
	/* -------------------------------------------- */
	function ReParseUpload($uploadid) {
		if (!ValidID($uploadid,'UploadID')) { return; }
		
		$sqlstring = "update uploads set upload_status = 'reparse' where upload_id = $uploadid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		DisplayNotice("Upload will be re-parsed");
		
	}


	/* -------------------------------------------- */
	/* ------- QueueUploadForArchive -------------- */
	/* -------------------------------------------- */
	function QueueUploadForArchive($uploadid, $uploadseriesids) {
		if (!ValidID($uploadid,'UploadID')) { return; }
		
		/* get all series for this uploadid */
		$sqlstring = "select * from upload_series a left join upload_studies b on a.uploadstudy_id = b.uploadstudy_id left join upload_subjects c on b.uploadsubject_id = c.uploadsubject_id where c.upload_id = $uploadid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0) {
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$seriesid = $row['uploadseries_id'];
				
				if (in_array($seriesid, $uploadseriesids)) {
					$sqlstringA = "update upload_series set uploadseries_status = 'import' where uploadseries_id = $seriesid";
				}
				else {
					$sqlstringA = "update upload_series set uploadseries_status = 'ignore' where uploadseries_id = $seriesid";
				}
				$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
			}

			$sqlstringA = "update uploads set upload_status = 'queueforarchive' where upload_id = $uploadid";
			$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
			
			DisplayNotice("Upload queued for archiving");
		}
		else {
			$sqlstring = "update uploads set upload_status = 'archiveerror' where upload_id = $uploadid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			
			DisplayErrorMessage("Error", "No series found for this upload");
		}		
	}
	

	/* -------------------------------------------- */
	/* ------- DisplayImport ---------------------- */
	/* -------------------------------------------- */
	function DisplayImport($uploadid) {
		
		$sqlstring = "select * from uploads where upload_id = $uploadid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0) {
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$uploadid = $row['upload_id'];
			$startdate = $row['upload_startdate'];
			$enddate = $row['upload_enddate'];
			$status = $row['upload_status'];
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
			<span style="font-weight: bold; font-size: 14pt;">Upload Details</span><br>
			<table style="border: 1px solid #aaa;" cellpadding="5">
				<tr>
					<td style="text-align: right; vertical-align: top; font-weight: bold;">Status</td>
					<td><?=$status?></td>
				</tr>
				<tr>
					<td style="text-align: right; vertical-align: top; font-weight: bold;">Log</td>
					<td>
						<details>
						<?
							$sqlstring = "select * from upload_logs where upload_id = $uploadid";
							$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						?>
						<summary>View Log <span class="tiny"><?=mysqli_num_rows($result)?> entries</span></summary>
						<tt><pre><?
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$date = $row['log_date'];
								$msg = $row['log_msg'];
								echo "[$date] $msg\n";
							}
						?></pre></tt>
						</details>
					</td>
				</tr>
				<tr>
					<td style="text-align: right; vertical-align: top; font-weight: bold;">Uploaded files</td>
					<td>
						<details>
						<summary>Original file list (<?=count($filelist);?> files)</summary>
							<tt><?=implode2("<br>", $filelist)?></tt>
						</details>
						
						<?
							$sqlstringA = "SELECT * FROM upload_subjects a LEFT JOIN upload_studies b on a.uploadsubject_id = b.uploadsubject_id LEFT JOIN upload_series c on b.uploadstudy_id = c.uploadstudy_id WHERE a.upload_id = 33 and (uploadsubject_patientid = 'unreadable' or uploadsubject_patientid = 'NiDBunreadable')";
							$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
							$errorfiles = array();
							while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
								$errorfiles = array_merge($errorfiles, explode(",", $rowA['uploadseries_filelist']));
							}
							if (count($errorfiles) > 0) {
								?>
								<details style="color: red">
								<summary>Unreadable files (<?=count($errorfiles);?> files)</summary>
									<tt><?=implode2("<br>", $errorfiles)?></tt>
								</details>
								<?
							}
							?>
					</td>
				</tr>
				<tr>
					<td style="text-align: right; vertical-align: top; font-weight: bold;">Source</td>
					<td><?=$source?></td>
				</tr>
				<tr>
					<td style="text-align: right; vertical-align: top; font-weight: bold;">Source Data path</td>
					<td><tt><?=$datapath?></tt></td>
				</tr>
				<tr>
					<td style="text-align: right; vertical-align: top; font-weight: bold;">Matching Criteria</td>
					<td>
						Subject: <i><?=$subjectcriteria?></i><br>
						Study: <i><?=$studycriteria?></i><br>
						Series: <i><?=$seriescriteria?></i>
					</td>
				</tr>
			</table>
			
			<br><br>
			
			<style>
				ul, #myUL { list-style-type: none; }
				#myUL { margin: 0; padding: 0; }
				#myUL .caret { width: 100%; padding:5px; cursor: pointer; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; user-select: none; }
				#myUL .caret::before { content: "\25B6"; color: #666; display: inline-block; margin-right: 6px; }
				#myUL .caret-down::before { -ms-transform: rotate(90deg); -webkit-transform: rotate(90deg); transform: rotate(90deg); }
				#myUL .nested { display: none; }
				#myUL .active { display: block; }
				li.level1 { background-color: #c0cfff; margin: 5px; padding: 10px; border-radius: 8px; }
				li.level2 { background-color: #d0dfff; margin: 5px; padding: 10px; border-radius: 8px; }
				li.level3 { background-color: #e0efff; margin: 5px; padding: 10px; border-radius: 8px; }
			</style>
			
			<? if ($status == "parsingcomplete") { ?>
			<form method="post" action="importimaging.php">
			<input type="hidden" name="action" value="queueforarchive">
			<input type="hidden" name="uploadid" value="<?=$uploadid?>">
			Select series for archiving. (All series are selected by default) &nbsp; &nbsp; <input type="submit" value="Archive">
			<? } ?>
			
			<ul id="myUL">
			<?
			$sqlstringA = "select * from upload_subjects where upload_id = $uploadid and uploadsubject_patientid <> 'unreadable' and uploadsubject_patientid <> 'NiDBunreadable' order by uploadsubject_patientid desc";
			$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
			while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
				$uploadsubjectid = $rowA['uploadsubject_id'];
				$patientid = $rowA['uploadsubject_patientid'];
				$name = $rowA['uploadsubject_name'];
				$dob = $rowA['uploadsubject_dob'];
				$sex = $rowA['uploadsubject_sex'];
				
				if ($patientid == "") $patientid = "(blank PatientID)";
				if ($name == "") $name = "(blank PatientName)";
				if ($dob == "") $dob = "(blank PatientBirthDate)";
				if ($sex == "") $sex = "(blank PatientSex)";
				
				/* check for existing subjects using this specified criteria */
				$subjectmatches = GetMatchingSubject($subjectcriteria, $patientid, $name, $dob, $sex);
				//PrintVariable($subjectmatches);
				$matchsubjectid = $subjectmatches[0]['subjectid'];
				$matchsubjectuid = $subjectmatches[0]['uid'];
				
				?>
				<li class="level1">
					<span class="caret active"><span class="tiny">PatientID:</span> <b><?=$patientid?></b> <span class="tiny">Name:</span> <?=$name?></span>
					<? if ($matchsubjectid != "") { ?>Matched existing subject <a href="subjects.php?subjectid=<?=$matchsubjectid?>" target="_blank"><?=$matchsubjectuid?></a><? } ?>
				<ul class="nested">
				<?
					$sqlstringB = "select * from upload_studies where uploadsubject_id = $uploadsubjectid order by uploadstudy_date desc";
					$resultB = MySQLiQuery($sqlstringB, __FILE__, __LINE__);
					while ($rowB = mysqli_fetch_array($resultB, MYSQLI_ASSOC)) {
						$uploadstudyid = $rowB['uploadstudy_id'];
						$studyinstanceuid = $rowB['uploadstudy_instanceuid'];
						$desc = $rowB['uploadstudy_desc'];
						$studydate = $rowB['uploadstudy_date'];
						$modality = $rowB['uploadstudy_modality'];
						$datatype = $rowB['uploadstudy_datatype'];
						$equipment = $rowB['uploadstudy_equipment'];
						$operator = $rowB['uploadstudy_operator'];

						if ($desc == "") $desc = "(blankStudyDescription)";
						if ($studydate == "") $studydate = "(blankStudyDateTime)";
						if ($datatype == "") $datatype = "(blankDatatype)";
						if ($equipment == "") $equipment = "(blankEquipment)";
						if ($operator == "") $operator = "(blankOperator)";
						
						/* check for existing subjects using this specified criteria */
						$studymatches = GetMatchingStudies($studycriteria, $matchsubjectid, $modality, $studydate, $studyinstanceuid, $destprojectid);
						//PrintVariable($studymatches);
						$matchstudyid = $studymatches[0]['studyid'];
						$matchstudynum = $studymatches[0]['studynum'];
						
						?>
						<li class="level2"><span class="caret"><span class="tiny">Desc:</span> <b><?=$desc?></b> <span class="tiny">Date:</span> <?=$studydate?> <span class="tiny">Modality:</span> <?=$modality?> <span class="tiny">Datatype:</span> <?=$datatype?> <span class="tiny">Equipment:</span> <?=$equipment?></span>
						<? if ($matchstudyid != "") { ?>Matched existing studies <a href="studies.php?studyid=<?=$matchstudyid?>" target="_blank"><?=$matchsubjectuid?><?=$matchstudynum?></a><? } ?>

						<ul class="nested">
						<?
							$sqlstringC = "select * from upload_series where uploadstudy_id = $uploadstudyid order by uploadseries_num asc";
							$resultC = MySQLiQuery($sqlstringC, __FILE__, __LINE__);
							while ($rowC = mysqli_fetch_array($resultC, MYSQLI_ASSOC)) {
								$uploadseriesid = $rowC['uploadseries_id'];
								$seriesinstanceuid = $rowC['uploadseries_instanceuid'];
								$desc = $rowC['uploadseries_desc'];
								$seriesdate = $rowC['uploadseries_date'];
								$protocol = $rowC['uploadseries_protocol'];
								$seriesnum = $rowC['uploadseries_num'];
								$numfiles = $rowC['uploadseries_numfiles'];
								$tr = $rowC['uploadseries_tr'];
								$te = $rowC['uploadseries_te'];
								$slicespacing = $rowC['uploadseries_slicespacing'];
								$slicethickness = $rowC['uploadseries_slicethickness'];
								$rows = $rowC['uploadseries_rows'];
								$cols = $rowC['uploadseries_cols'];
								$filelist = $rowC['uploadseries_filelist'];

								if ($desc == "") $desc = "(blankSeriesDescription)";
								if ($protocol == "") $desc = "(blankProtocol)";
								if ($seriesnum == "") $desc = "(blankSeriesNum)";
								
								/* check for existing series using this specified criteria */
								$seriesmatches = GetMatchingSeries($seriescriteria, $matchstudyid, $modality, $seriesdate, $seriesnum, $seriesinstanceuid);
								//PrintVariable($seriesmatches);
								
								?>
								<li class="level3"><? if ($status == "parsingcomplete") { ?><input type="checkbox" name="uploadseriesid[]" value="<?=$uploadseriesid?>" checked><?}?><span class="caret"><b><?=$seriesnum?></b> &nbsp; <?=$desc?> &nbsp; <?=$protocol?> &nbsp; <?=$seriesdate?> <span class="tiny">Img:</span> <?=$cols?>x<?=$rows?></span> <i>Matched <?=count($seriesmatches);?> series</i>
									<ul class="nested" style="margin: 5px;">
									<?
										$files = explode(",", $filelist);
										?>
										<li style='background-color: #fff; padding: 2px 5px;'><b><?=count($files)?> files</b>
										<tt>
										<?
										foreach ($files as $f) {
											echo "<li style='font-size: 8pt; background-color: #fff; padding: 1px 5px;'><tt>$f</tt>";
										}
									?>
										</tt>
									</ul>
								</tt>
								<?
							}
						?>
						</ul>
						<?
					}
				?>
				</ul>
				<?
			}
			?>
			</ul>
			<? if ($status == "parsingcomplete") { ?>
			</form>
			<? } ?>
			
			<script>
				var toggler = document.getElementsByClassName("caret");
				var i;

				for (i = 0; i < toggler.length; i++) {
					toggler[i].addEventListener("click", function() {
						this.parentElement.querySelector(".nested").classList.toggle("active");
						this.classList.toggle("caret-down");
					});
				}
			</script>
			
			<?
		}
		else {
			?>Upload not found<?
		}
	}
	
	
	/* -------------------------------------------- */
	/* ------- GetMatchingSubject ----------------- */
	/* -------------------------------------------- */
	function GetMatchingSubject($subjectcriteria, $patientid, $name, $dob, $sex) {
		
		$i = 0;
		if ($subjectcriteria == "patientid") {
			/* find existing subjects by patientid */
			if (trim($patientid) != "") {
				$sqlstring = "select b.subject_id, b.uid from subject_altuid a left join subjects b on a.subject_id = b.subject_id left join enrollment c on b.subject_id = c.subject_id where (a.altuid = '$patientid' or a.altuid = sha1('$patientid') or a.altuid = sha1('$patientid ') or a.altuid = sha1(' $patientid') or a.altuid = sha1(' $patientid ') or a.altuid = sha1(upper('$patientid')) or a.altuid = sha1(lower('$patientid')))";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$matches[$i]['subjectid'] = $row['subject_id'];
					$matches[$i]['uid'] = $row['uid'];
					
					$i++;
				}
			}
		}
		elseif ($subjectcriteria == "namesexdob") {
			/* find existing subjects by name/sex/dob */
			if ( (trim($name) != "") && (trim($dob) != "") && (trim($sex) != "") ) {
				$sqlstring = "select * from subjects where name = '$name' and birthdate = '$dob' and gender = '$sex'";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$matches[$i]['subjectid'] = $row['subject_id'];
					$matches[$i]['uid'] = $row['uid'];
					
					$i++;
				}
			}
		}
		
		return $matches;
	}


	/* -------------------------------------------- */
	/* ------- GetMatchingStudies ----------------- */
	/* -------------------------------------------- */
	function GetMatchingStudies($studycriteria, $subjectid, $modality, $studydate, $studyuid, $projectid) {
		//echo "GetMatchingStudies($studycriteria, $subjectid, $modality, $studydate, $studyuid, $projectid)<br>";

		if ($subjectid == "") {
			return;
		}
		if (!IsNiDBModality($modality)) {
			return;
		}
		
		$i = 0;
		if ($studycriteria == "modalitystudydate") {
			/* find existing studies by modality/studydate */
			if ( (trim($studydate) != "") && (trim($modality) != "") ) {
				$sqlstring = "select * from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where c.subject_id = $subjectid and a.study_modality = '$modality' and (study_datetime between date_sub('$studydate', interval 30 second) and date_add('$studydate', interval 30 second))";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$matches[$i]['subjectid'] = $row['subject_id'];
					$matches[$i]['studyid'] = $row['study_id'];
					$matches[$i]['studynum'] = $row['study_num'];
					$matches[$i]['uid'] = $row['uid'];
					
					$i++;
				}
			}
		}
		elseif ($studycriteria == "studyuid") {
			/* find existing studies by studyuid (rare that someone would ever do this) */
			if (trim($studyuid) != "") {
				$sqlstring = "select * from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where c.subject_id = $subjectid and a.study_uid = '$studyuid'";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$matches[$i]['subjectid'] = $row['subject_id'];
					$matches[$i]['studyid'] = $row['study_id'];
					$matches[$i]['studynum'] = $row['study_num'];
					$matches[$i]['uid'] = $row['uid'];
					
					$i++;
				}
			}
		}
		
		return $matches;
	}
	

	/* -------------------------------------------- */
	/* ------- GetMatchingSeries ------------------ */
	/* -------------------------------------------- */
	function GetMatchingSeries($seriescriteria, $studyid, $modality, $seriesdate, $seriesnum, $seriesuid) {
		//echo "GetMatchingSeries($seriescriteria, $studyid, $modality, $seriesdate, $seriesnum, $seriesuid)<br>";
		
		if ($studyid == "") {
			return;
		}
		if (!IsNiDBModality($modality)) {
			return;
		}
			
		$i = 0;
		$modality = strtolower($modality);
		
		if ($seriescriteria == "seriesnum") {
			/* find existing series by seriesnum */
			if (trim($seriesnum) != "") {
				$sqlstring = "select * from $modality" . "_series where study_id = $studyid and series_num = $seriesnum";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					//PrintVariable($row);
					$matches[$i]['modality'] = $row['modality'];
					$matches[$i]['seriesid'] = $row[$modality . "series_id"];
					
					$i++;
				}
			}
		}
		elseif ($seriescriteria == "seriesdate") {
			/* find existing series by seriesdate */
			if (trim($seriesdate) != "") {
				$sqlstring = "select * from $modality" . "_series where study_id = $studyid and series_datetime = '$seriesdate'";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					//PrintVariable($row);
					$matches[$i]['modality'] = $modality;
					$matches[$i]['seriesid'] = $row[$modality . "series_id"];
					
					$i++;
				}
			}
		}
		elseif ($seriescriteria == "seriesuid") {
			/* find existing series by seriesuid (rare that someone would do this) */
			if (trim($seriesuid) != "") {
				$sqlstring = "select * from $modality" . "_series where study_id = $studyid and series_uid = '$seriesuid'";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					//PrintVariable($row);
					$matches[$i]['modality'] = $modality;
					$matches[$i]['seriesid'] = $row[$modality . "series_id"];
					
					$i++;
				}
			}
		}
		
		return $matches;
	}
	
	
	/* ---------------------------------------------------------- */
	/* --------- AppendUploadLog -------------------------------- */
	/* ---------------------------------------------------------- */
	function AppendUploadLog($uploadid, $m) {
		if (($uploadid >= 0) && (trim($m) != "")) {
			$str = "ImportImaging.php  " + mysqli_real_escape_string($GLOBALS['linki'], $m);

			$sqlstring = "insert ignore into upload_logs (upload_id, log_date, log_msg) values ($uploadid, now(), '$str')";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
	}


	/* ---------------------------------------------------------- */
	/* --------- DisplayStatusSteps ----------------------------- */
	/* ---------------------------------------------------------- */
	function DisplayStatusSteps($status) {
		
		// Possible statuses: 'uploading','uploadcomplete','uploaderror','parsing','parsingcomplete','parsingerror','archiving','archivecomplete','archiveerror','queueforarchive','reparse'
		
		switch ($status) {
			case 'uploading':
				$step1_title = "Started"; $step1_desc = "Upload has been submitted"; $step1_state = "active";
				$step2_title = "Uploading"; $step2_desc = "Data is uploading"; $step2_state = "disabled";
				$step3_title = "Parse"; $step3_desc = ""; $step3_state = "disabled";
				$step4_title = "Archive"; $step4_desc = ""; $step4_state = "disabled";
				$step5_title = "Complete"; $step5_desc = ""; $step5_state = "disabled";
				break;
			case 'uploadcomplete':
				$step1_title = "Started"; $step1_desc = "Upload has been submitted"; $step1_state = "completed";
				$step2_title = "Uploaded"; $step2_desc = "Data has been uploaded"; $step2_state = "completed";
				$step3_title = "Parse"; $step3_desc = ""; $step3_state = "disabled";
				$step4_title = "Archive"; $step4_desc = ""; $step4_state = "disabled";
				$step5_title = "Complete"; $step5_desc = ""; $step5_state = "disabled";
				break;
			case 'uploaderror':
				$step1_title = "Started"; $step1_desc = "Upload has been submitted"; $step1_state = "completed";
				$step2_title = "Upload Error"; $step2_desc = "Error uploading"; $step2_state = "active";
				$step3_title = "Parse"; $step3_desc = ""; $step3_state = "disabled";
				$step4_title = "Archive"; $step4_desc = ""; $step4_state = "disabled";
				$step5_title = "Complete"; $step5_desc = ""; $step5_state = "disabled";
				break;
			case 'parsing':
				$step1_title = "Started"; $step1_desc = "Upload has been submitted"; $step1_state = "completed";
				$step2_title = "Uploaded"; $step2_desc = "Data has been uploaded"; $step2_state = "completed";
				$step3_title = "Parsing"; $step3_desc = "Data is being parsed"; $step3_state = "active";
				$step4_title = "Archive"; $step4_desc = ""; $step4_state = "disabled";
				$step5_title = "Complete"; $step5_desc = ""; $step5_state = "disabled";
				break;
			case 'parsingcomplete':
				$step1_title = "Start"; $step1_desc = "Upload has been submitted"; $step1_state = "completed";
				$step2_title = "Upload"; $step2_desc = "Data has been uploaded"; $step2_state = "completed";
				$step3_title = "Parse"; $step3_desc = "Data has been parsed"; $step3_state = "active";
				$step4_title = "Archive"; $step4_desc = ""; $step4_state = "disabled";
				$step5_title = "Complete"; $step5_desc = ""; $step5_state = "disabled";
				break;
			case 'parsingerror':
				$step1_title = "Started"; $step1_desc = "Upload has been submitted"; $step1_state = "completed";
				$step2_title = "Uploaded"; $step2_desc = "Data has been uploaded"; $step2_state = "completed";
				$step3_title = "Parse Error"; $step3_desc = "Error parsing"; $step3_state = "active";
				$step4_title = "Archive"; $step4_desc = ""; $step4_state = "disabled";
				$step5_title = "Complete"; $step5_desc = ""; $step5_state = "disabled";
				break;
			case 'archiving':
				$step1_title = "Started"; $step1_desc = "Upload has been submitted"; $step1_state = "completed";
				$step2_title = "Uploaded"; $step2_desc = "Data has been uploaded"; $step2_state = "completed";
				$step3_title = "Parsed"; $step3_desc = "Data has been parsed"; $step3_state = "completed";
				$step4_title = "Archiving"; $step4_desc = "Data is being archived"; $step4_state = "active";
				$step5_title = "Complete"; $step5_desc = ""; $step5_state = "disabled";
				break;
			case 'archivecomplete':
				$step1_title = "Started"; $step1_desc = "Upload has been submitted"; $step1_state = "completed";
				$step2_title = "Uploaded"; $step2_desc = "Data has been uploaded"; $step2_state = "completed";
				$step3_title = "Parsed"; $step3_desc = "Data has been parsed"; $step3_state = "completed";
				$step4_title = "Archived"; $step4_desc = "Data has been archived"; $step4_state = "completed";
				$step5_title = "Complete"; $step5_desc = "Upload complete"; $step5_state = "active";
				break;
			case 'archiveerror':
				$step1_title = "Started"; $step1_desc = "Upload has been submitted"; $step1_state = "completed";
				$step2_title = "Uploaded"; $step2_desc = "Data has been uploaded"; $step2_state = "completed";
				$step3_title = "Parsed"; $step3_desc = "Data has been parsed"; $step3_state = "completed";
				$step4_title = "Archive Error"; $step4_desc = "Error archiving"; $step4_state = "active";
				$step5_title = "Complete"; $step5_desc = ""; $step5_state = "disabled";
				break;
			case 'queueforarchive':
				$step1_title = "Start"; $step1_desc = "Upload has been submitted"; $step1_state = "completed";
				$step2_title = "Upload"; $step2_desc = "Data has been uploaded"; $step2_state = "completed";
				$step3_title = "Parse"; $step3_desc = "Data has been parsed"; $step3_state = "completed";
				$step4_title = "Archive"; $step4_desc = "Data queued for archiving"; $step4_state = "archive";
				$step5_title = "Complete"; $step5_desc = ""; $step5_state = "disabled";
				break;
			case 'reparse':
				$step1_title = "Start"; $step1_desc = "Upload has been submitted"; $step1_state = "completed";
				$step2_title = "Upload"; $step2_desc = "Data has been uploaded"; $step2_state = "completed";
				$step3_title = "Parse"; $step3_desc = "Data queued to be re-parsed"; $step3_state = "active";
				$step4_title = "Archive"; $step4_desc = ""; $step4_state = "disabled";
				$step5_title = "Complete"; $step5_desc = ""; $step5_state = "disabled";
				break;
			default:
		}
		
		?>
			<div class="ui five steps">
				<div class="<?=$step1_state?> step">
					<div class="content">
						<div class="title"><?=$step1_title?></div>
						<div class="description"><?=$step1_desc?></div>
					</div>
				</div>
				<div class="<?=$step2_state?> step">
					<div class="content">
						<div class="green title"><?=$step2_title?></div>
						<div class="description"><?=$step2_desc?></div>
					</div>
				</div>
				<div class="<?=$step3_state?> step">
					<div class="content">
						<div class="title"><?=$step3_title?></div>
						<div class="description"><?=$step3_desc?></div>
					</div>
				</div>
				<div class="<?=$step4_state?> step">
					<div class="content">
						<div class="title"><?=$step4_title?></div>
						<div class="description"><?=$step4_desc?></div>
					</div>
				</div>
				<div class="<?=$step5_state?> step">
					<div class="content">
						<div class="title"><?=$step5_title?></div>
						<div class="description"><?=$step5_desc?></div>
					</div>
				</div>
			</div>
		<?
	}

?>
<? include("footer.php") ?>