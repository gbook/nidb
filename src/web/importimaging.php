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
 // ------------------------------------------------------------------------------

	define("LEGIT_REQUEST", true);

	session_start();
	ob_start(); /* buffer output so POST/Redirect/GET (a header('Location') redirect) works despite the HTML rendered below */
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
	require "tus_functions.php";
	require "menu.php";

	//PrintVariable($_POST);
	//PrintVariable($_GET);
	//PrintVariable($_FILES);
	$username = $_SESSION['username'];
	$instanceid = $_SESSION['instanceid'];

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$datalocation = GetVariable("datalocation");
	$nfspath = GetVariable("nfspath");
	$projectid = (int)GetVariable("projectid");
	$modality = GetVariable("modality");
	$filetype = GetVariable("filetype");
	$subjectcriteria = GetVariable("subjectcriteria");
	$studycriteria = GetVariable("studycriteria");
	$seriescriteria = GetVariable("seriescriteria");
	$bidsflags = GetVariable("bidsflags"); /* array of checked BIDS flag values (name="bidsflags[]"), or NULL */
	$uploadid = (int)GetVariable("uploadid");
	$uploadseriesid = GetVariable("uploadseriesid");
	$displayall = GetVariable("displayall");
	$datestart = GetVariable("datestart");
	$dateend = GetVariable("dateend");
	$keyword = GetVariable("keyword");
	$userspecifiedpatientid = GetVariable("userspecifiedpatientid");
	
	ShowFlashMessage();

	/* determine action */
	switch ($action) {
		case 'newimportform':
			DisplayNewImportForm($username, $instanceid);
			break;
		case 'newimport':
			ob_start();
			$newuploadid = 0;
			if (VerifyCSRFToken())
				$newuploadid = NewImport($datalocation, $nfspath, $projectid, $modality, $filetype, $subjectcriteria, $studycriteria, $seriescriteria, $userspecifiedpatientid, $bidsflags);
			$_SESSION['flash'] = ob_get_clean();
			/* files from the local computer are sent by the browser from the upload page */
			if (($newuploadid > 0) && ($datalocation == "web"))
				RedirectTo("importimaging.php?action=uploadfiles&uploadid=$newuploadid");
			RedirectTo("importimaging.php");
			break;
		case 'uploadfiles':
			DisplayUploadFiles($uploadid, $userid);
			break;
		case 'finalizeupload':
			ob_start();
			$finalized = false;
			if (VerifyCSRFToken())
				$finalized = FinalizeUpload($uploadid, $userid);
			$_SESSION['flash'] = ob_get_clean();
			if ($finalized)
				RedirectTo("importimaging.php?action=displayimport&uploadid=$uploadid");
			RedirectTo("importimaging.php?action=uploadfiles&uploadid=$uploadid");
			break;
		case 'queueforarchive':
			ob_start();
			if (VerifyCSRFToken())
				QueueUploadForArchive($uploadid, $uploadseriesid);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("importimaging.php?action=displayimport&uploadid=$uploadid");
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
		case 'cancel':
			ob_start();
			CancelUpload($uploadid);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("importimaging.php");
			break;
		case 'reparse':
			ob_start();
			if (VerifyCSRFToken())
				ReparseUpload($uploadid, $subjectcriteria, $studycriteria, $seriescriteria);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("importimaging.php?action=displayimport&uploadid=$uploadid");
			break;
		default:
			DisplayImportList($displayall);
	}

	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- DisplayNewImportForm --------------- */
	/* -------------------------------------------- */
	function DisplayNewImportForm($username, $instanceid) {
		?>
		<div class="ui container">
			<div class="ui top attached grey segment">
				<h2 class="ui header">New Import</h2>
			</div>
			<form method="post" action="importimaging.php" name="importform" class="ui form attached fluid segment">
				<input type="hidden" name="action" value="newimport">
				<?=CSRFTokenField()?>
				<div class="ui grid">
					
					<div class="three wide column"><h3 class="ui grey right aligned header">Data Location</h3></div>
					<div class="thirteen wide column">
						<div class="field">
							<div style="display:flex; align-items:center; gap:1em">
								<label style="white-space:nowrap"><input type="radio" name="datalocation" value="web" checked onchange="updateDataLocation()"> Local Computer</label>
								<span class="ui grey text" id="datalocation_web">Files are selected on the next page. Large uploads can be resumed if interrupted</span>
							</div>
						</div>
						<div class="field">
							<div style="display:flex; align-items:center; gap:1em">
								<label style="white-space:nowrap"><input type="radio" name="datalocation" value="nfs" onchange="updateDataLocation()"> NFS path</label>
								<input class="ui fluid input" type="text" name="nfspath" id="datalocation_nfs" placeholder="/path/accessible/to/nidb/server" style="flex:1">
							</div>
						</div>
					</div>
					<script>
						/* show only the input (file picker or NFS path) for the selected data location radio */
						function updateDataLocation() {
							var loc = document.querySelector('input[name="datalocation"]:checked');
							loc = loc ? loc.value : 'web';
							document.getElementById('datalocation_web').style.display = (loc == 'web') ? '' : 'none';
							document.getElementById('datalocation_nfs').style.display = (loc == 'nfs') ? '' : 'none';
						}
						updateDataLocation();
					</script>

					<div class="three wide column"><h3 class="ui grey right aligned header">Destination Project</h3></div>
					<div class="thirteen wide column">
						<select name="projectid" required>
							<option value="">Select project...</option>
							<?
								$sqlstring = "select * from projects a left join user_project b on a.project_id = b.project_id where b.user_id = (select user_id from users where username = '$username') and a.instance_id = '$instanceid' order by project_name";
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

					<div class="three wide column"><h3 class="ui grey right aligned header">File Format</h3></div>
					<div class="thirteen wide column">
						<div class="ui fluid selection dropdown">
							<input type="hidden" name="filetype" id="filetype" required>
							<i class="dropdown icon"></i>
							<div class="default text">Select file format</div>
							<div class="menu">
								<div class="item" data-value="auto"><b>Imaging file(s)</b></div>
								<div class="item" data-value="bids"><b>BIDS</b></div>
								<div class="item" data-value="squirrel"><b>squirrel</b> - only one squirrel package at a time</div>
							</div>
						</div>
					</div>

					<div class="three wide column fmt-modality"><h3 class="ui grey right aligned header">Data Modality</h3></div>
					<div class="thirteen wide column fmt-modality">
						<div class="ui fluid selection dropdown">
							<input type="hidden" name="modality" id="modalityfield" required>
							<i class="dropdown icon"></i>
							<div class="default text">Select modality</div>
							<div class="menu">
								<div class="item" data-value="auto"><b>Automatically detect</b> - DICOM only</div>
								<?
									$modalities = GetModalityList();
									foreach ($modalities as $modality) {
										?><div class="item" data-value="<?=$modality?>"><?=$modality?></div><?
									}
								?>
								<div class="item" data-value="unknown"><b>Unknown</b> - Let NiDB guess the modality</div>
							</div>
						</div>
					</div>

					<div class="three wide column fmt-dicom"><h3 class="ui grey right aligned header">DICOM Matching Criteria</h3></div>
					<div class="thirteen wide column fmt-dicom">

						<div class="ui styled accordion" id="matchingAccordion">
							<div class="title">
								<i class="dropdown icon"></i>
								Subject <span class="ui small grey text" id="sel_subject" style="font-weight:normal"></span>
							</div>
							<div class="content">
							<div class="ui grid">
								<div class="four wide right aligned column">
									<h3 class="ui blue header">Subject</h3>
									<span class="tiny">If DICOM Patient field(s) are blank then PatientID will be read from DICOM file's parent directory</span>
								</div>
								<div class="eight wide column">
									<div class="ui grid">
										<div class="two wide right aligned column">
											<input type="radio" name="subjectcriteria" value="patientid" data-label="PatientID" onchange="updateCriteriaLabels()" checked>
										</div>
										<div class="fourteen wide column">
											Patient<b>ID</b> <span class="tiny">DICOM (<tt>0010</tt>, <tt>0020</tt>)</span>
										</div>
									</div>
									<div class="ui horizontal divider">Or</div>
									<div class="ui grid">
										<div class="two wide right aligned column">
											<input type="radio" name="subjectcriteria" value="specificpatientid" data-label="Specific PatientID" onchange="updateCriteriaLabels()">
										</div>
										<div class="fourteen wide column">
											Specific PatientID <input type="text" name="userspecifiedpatientid" placeholder="Enter PatientID"><br><span class="tiny">This PatientID will be applied to all imported data</span>
										</div>
									</div>
									<div class="ui horizontal divider">Or</div>
									<div class="ui grid">
										<div class="two wide right aligned column">
											<input type="radio" name="subjectcriteria" value="patientidfromdir" data-label="PatientID from directory name" onchange="updateCriteriaLabels()">
										</div>
										<div class="fourteen wide column">
											PatientID from directory name
										</div>
									</div>
									<div class="ui horizontal divider">Or</div>
									<div class="ui grid">
										<div class="two wide right aligned column">
											<input type="radio" name="subjectcriteria" value="namesexdob" data-label="PatientName / BirthDate / Sex" onchange="updateCriteriaLabels()">
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
							<div class="title">
								<i class="dropdown icon"></i>
								Study <span class="ui small grey text" id="sel_study" style="font-weight:normal"></span>
							</div>
							<div class="content">
							<div class="ui grid">
								<div class="four wide right aligned column">
									<h3 class="ui blue header">Study</h3>
									<span class="tiny">If DICOM Study Date/Time field(s) are blank then StudyInstanceUID will be used to uniquely identify studies</span>
								</div>
								<div class="eight wide column">
									<div class="ui grid">
										<div class="two wide right aligned column">
											<input type="radio" name="studycriteria" value="modalitystudydate" data-label="Modality / StudyDate / StudyTime" onchange="updateCriteriaLabels()" checked>
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
											<input type="radio" name="studycriteria" value="studyuid" data-label="StudyInstanceUID" onchange="updateCriteriaLabels()">
										</div>
										<div class="fourteen wide column">
											StudyInstanceUID <span class="tiny">DICOM (<tt>0020</tt>, <tt>000D</tt>)</span>
										</div>
									</div>
									<div class="ui horizontal divider">Or</div>
									<div class="ui grid">
										<div class="two wide right aligned column">
											<input type="radio" name="studycriteria" value="studyid" data-label="StudyID" onchange="updateCriteriaLabels()">
										</div>
										<div class="fourteen wide column">
											StudyID <span class="tiny">DICOM (<tt>0020</tt>, <tt>0010</tt>)</span>
										</div>
									</div>
								</div>
							</div>
							</div>
							<div class="title">
								<i class="dropdown icon"></i>
								Series <span class="ui small grey text" id="sel_series" style="font-weight:normal"></span>
							</div>
							<div class="content">
							<div class="ui grid">
								<div class="four wide right aligned column">
									<h3 class="ui blue header">Series</h3>
									<span class="tiny">If DICOM SeriesNumber or Date/Time field(s) are blank then SeriesInstanceUID will be used to uniquely identify series</span>
								</div>
								<div class="eight wide column">
									<div class="ui grid">
										<div class="two wide right aligned column">
											<input type="radio" name="seriescriteria" value="seriesnum" data-label="SeriesNumber" onchange="updateCriteriaLabels()" checked>
										</div>
										<div class="fourteen wide column">
											SeriesNumber <span class="tiny">DICOM (<tt>0020</tt>, <tt>0011</tt>)</span>
										</div>
									</div>
									<div class="ui horizontal divider">Or</div>
									<div class="ui grid">
										<div class="two wide right aligned column">
											<input type="radio" name="seriescriteria" value="seriesdate" data-label="SeriesDate / SeriesTime" onchange="updateCriteriaLabels()">
										</div>
										<div class="fourteen wide column">
											SeriesDate <span class="tiny">DICOM (<tt>0008</tt>, <tt>0021</tt>)</span><br>
											SeriesTime <span class="tiny">DICOM (<tt>0008</tt>, <tt>0031</tt>)</span>
										</div>
									</div>
									<div class="ui horizontal divider">Or</div>
									<div class="ui grid">
										<div class="two wide right aligned column">
											<input type="radio" name="seriescriteria" value="seriesuid" data-label="SeriesInstanceUID" onchange="updateCriteriaLabels()">
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

					<div class="three wide column fmt-bids"><h3 class="ui grey right aligned header">BIDS Options</h3></div>
					<div class="thirteen wide column fmt-bids">
						<div class="ui styled fluid accordion" id="bidsAccordion">
							<div class="title active">
								<i class="dropdown icon"></i>
								BIDS Flags
							</div>
							<div class="content active">
								<span class="tiny">Optional flags applied when importing a BIDS (or BIDS-like) dataset. Ignored for non-BIDS file formats.</span>
								<div class="ui grid" style="margin-top:0.25em">
									<div class="sixteen wide column">
										<div class="ui checkbox">
											<input type="checkbox" name="bidsflags[]" value="BIDS_KEEP_SUBID">
											<label><b>Keep BIDS subject IDs</b><br><span class="tiny">Use the <tt>sub-&lt;label&gt;</tt> value as the NiDB subject ID instead of matching/creating a new one</span></label>
										</div>
									</div>
									<div class="sixteen wide column">
										<div class="ui checkbox">
											<input type="checkbox" name="bidsflags[]" value="BIDS_KEEP_SES">
											<label><b>Keep BIDS session labels</b><br><span class="tiny">Use the <tt>ses-&lt;label&gt;</tt> value as the study visit label</span></label>
										</div>
									</div>
									<div class="sixteen wide column">
										<div class="ui checkbox">
											<input type="checkbox" name="bidsflags[]" value="BIDS_SEPARATE_RUNS">
											<label><b>Separate runs</b><br><span class="tiny">Import each <tt>run-&lt;index&gt;</tt> as its own series</span></label>
										</div>
									</div>
									<div class="sixteen wide column">
										<div class="ui checkbox">
											<input type="checkbox" name="bidsflags[]" value="BIDS_ADD_REVERSE_MAPPING">
											<label><b>Add reverse mapping</b><br><span class="tiny">Record a mapping from each imported series back to its original BIDS entities/paths</span></label>
										</div>
									</div>
									<div class="sixteen wide column">
										<div class="ui checkbox">
											<input type="checkbox" name="bidsflags[]" value="BIDS_ACCEPT_NONCOMPLIANT_DATASET">
											<label><b>Accept non-compliant dataset</b><br><span class="tiny">Import BIDS-like datasets that are missing required root metadata (e.g. <tt>dataset_description.json</tt>) instead of rejecting them</span></label>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<script>
					/* the global init (includes_html.php) sets every accordion to exclusive:false;
					   re-flip just this one so only one of Subject/Study/Series is open at a time.
					   Registered after the global $(document).ready, so it runs after it. */
					$(document).ready(function() {
						$('#matchingAccordion').accordion('setting', 'exclusive', true);
						$('.ui.checkbox').checkbox();
						updateCriteriaLabels();
						/* Fomantic updates the hidden filetype input and fires 'change' on it
						   when a format is picked; show only the sections relevant to that format */
						var ff = document.getElementById('filetype');
						if (ff) ff.addEventListener('change', updateFileFormatSections);
						updateFileFormatSections();
					});

					/* show/hide Data Modality, DICOM Matching Criteria, and BIDS Options based on
					   the selected file format:
					     Imaging file(s) (auto) -> Data Modality + DICOM Matching Criteria
					     BIDS                    -> BIDS Options only
					     squirrel                -> none
					   nothing selected yet      -> none (a format must be chosen first) */
					function updateFileFormatSections() {
						var ff = document.getElementById('filetype');
						var v = ff ? ff.value : '';
						setSectionDisplay('fmt-modality', v == 'auto');
						setSectionDisplay('fmt-dicom', v == 'auto');
						setSectionDisplay('fmt-bids', v == 'bids');
						/* the modality field is 'required'; drop that when it is hidden so the
						   form can still submit for BIDS/squirrel imports */
						var mod = document.getElementById('modalityfield');
						if (mod) { if (v == 'auto') mod.setAttribute('required', ''); else mod.removeAttribute('required'); }
					}

					function setSectionDisplay(cls, show) {
						var els = document.getElementsByClassName(cls);
						for (var i = 0; i < els.length; i++)
							els[i].style.display = show ? '' : 'none';
					}

					/* show the selected radio's label in each accordion title, so the choice
					   is visible while the section is collapsed */
					function updateCriteriaLabels() {
						var groups = {
							subjectcriteria: 'sel_subject',
							studycriteria:   'sel_study',
							seriescriteria:  'sel_series'
						};
						for (var name in groups) {
							var sel = document.querySelector('input[name="' + name + '"]:checked');
							var span = document.getElementById(groups[name]);
							if (span) span.textContent = sel ? ('— ' + (sel.getAttribute('data-label') || '')) : '';
						}
					}
				</script>

				<br>
				<div style="text-align: right">
					<button class="ui button" onClick="window.location.href='importimaging.php'; return false;">Cancel</button>
					<input type="submit" class="ui primary button" value="Next">
					<script>
						/* the NFS path is only required when importing from NFS */
						document.importform.addEventListener('submit', function(e) {
							var loc = document.querySelector('input[name="datalocation"]:checked');
							if (loc && (loc.value == 'nfs') && (document.getElementById('datalocation_nfs').value.trim() == '')) {
								alert('Enter the NFS path to import from');
								e.preventDefault();
							}
						});
					</script>
				</div>
			
			</form>
		</div>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- NewImport -------------------------- */
	/* -------------------------------------------- */
	/* create the upload. NFS imports go straight to the upload module. Imports from the local
	   computer are created with status 'uploading' and an empty directory, which the browser then
	   fills through tusupload.php (see DisplayUploadFiles). Returns the new upload_id, or 0 on error */
	function NewImport($datalocation, $nfspath, $projectid, $modality, $filetype, $subjectcriteria, $studycriteria, $seriescriteria, $userspecifiedpatientid, $bidsflags = null) {

		if (($datalocation != "web") && ($datalocation != "nfs")) {
			Error("Invalid data location [" . htmlspecialchars($datalocation) . "]");
			return 0;
		}
		if (($datalocation == "web") && ($GLOBALS['cfg']['uploaddir'] == "")) {
			Error("NiDB Configuration Error - Variable [uploaddir] is not set. Contact NiDB system administrator.");
			return 0;
		}

		/* BIDS flags: whitelist the posted checkbox values against the allowed SET members,
		   then join with commas for the upload_bidsflags SET column (empty string = no flags) */
		$allowedbidsflags = array('BIDS_KEEP_SUBID', 'BIDS_KEEP_SES', 'BIDS_SEPARATE_RUNS', 'BIDS_ADD_REVERSE_MAPPING', 'BIDS_ACCEPT_NONCOMPLIANT_DATASET');
		$bidsflagslist = array();
		if (is_array($bidsflags)) {
			foreach ($bidsflags as $flag) {
				if (in_array($flag, $allowedbidsflags))
					$bidsflagslist[] = $flag;
			}
		}
		$bidsflagsstr = implode(",", $bidsflagslist);

		$projectid = (int)$projectid;
		$guessmodality = ($modality == "unknown") ? 1 : null;

		/* NFS data is already in place, so it is ready for the upload module right away */
		if ($datalocation == "nfs") {
			$status = "uploadcomplete";
			$datapath = $nfspath;
		}
		else {
			$status = "uploading";
			$datapath = "";
		}

		/* create the upload and get the upload_id */
		$sqlstring = "insert into uploads (upload_startdate, upload_enddate, upload_status, upload_source, upload_type, upload_datapath, upload_destprojectid, upload_modality, upload_guessmodality, upload_subjectcriteria, upload_studycriteria, upload_seriescriteria, upload_patientid, upload_bidsflags) values (now(), if(? = 'uploadcomplete', now(), null), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$params = array($status, $status, $datalocation, $filetype, $datapath, $projectid, $modality, $guessmodality, $subjectcriteria, $studycriteria, $seriescriteria, $userspecifiedpatientid, $bidsflagsstr);
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'sssssisisssss', $status, $status, $datalocation, $filetype, $datapath, $projectid, $modality, $guessmodality, $subjectcriteria, $studycriteria, $seriescriteria, $userspecifiedpatientid, $bidsflagsstr);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		$uploadid = mysqli_insert_id($GLOBALS['linki']);
		mysqli_stmt_close($stmt);

		if ($uploadid < 1) {
			Error("Unable to create the import");
			return 0;
		}

		AppendUploadLog($uploadid, "Import created from IP address [" . $_SERVER['REMOTE_ADDR'] . "]");

		if ($datalocation == "nfs") {
			AppendUploadLog($uploadid, "Importing from NFS path [$nfspath]");
			Notice("Import from <code>" . htmlspecialchars($nfspath) . "</code> has been queued");
			return $uploadid;
		}

		/* create the directory the browser uploads into. The .tus subdirectory holds the resumable
		   upload state (see tus_functions.php) and records who may add files to this upload */
		$savepath = $GLOBALS['cfg']['uploaddir'] . "/" . date("YmdHisv") . "_$uploadid";
		if (!TusInitDir($savepath, $GLOBALS['userid'])) {
			AppendUploadLog($uploadid, "Unable to create upload directory [$savepath]");
			SetUploadStatus($uploadid, "uploaderror");
			Error("Unable to create upload directory <code>$savepath</code>. Contact NiDB system administrator.");
			return 0;
		}

		$sqlstring = "update uploads set upload_datapath = ? where upload_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'si', $savepath, $uploadid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($savepath, $uploadid));
		mysqli_stmt_close($stmt);

		AppendUploadLog($uploadid, "Waiting for files from the browser. Upload directory is [$savepath]");

		return $uploadid;
	}


	/* -------------------------------------------- */
	/* ------- GetResumableUploadPath ------------- */
	/* -------------------------------------------- */
	/* returns the upload directory if this is a web upload that is still receiving files, and was
	   created by this user. Otherwise displays an error and returns "" */
	function GetResumableUploadPath($uploadid, $userid) {
		$sqlstring = "select upload_status, upload_source, upload_datapath from uploads where upload_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $uploadid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($uploadid));
		$row = ($result) ? mysqli_fetch_array($result, MYSQLI_ASSOC) : null;
		mysqli_stmt_close($stmt);

		if (!$row) {
			Error("Import [$uploadid] not found");
			return "";
		}
		if (($row['upload_status'] != "uploading") || ($row['upload_source'] != "web")) {
			Error("Import [$uploadid] is not accepting files. Its status is [" . htmlspecialchars($row['upload_status']) . "]");
			return "";
		}

		$savepath = $row['upload_datapath'];
		$owner = TusReadOwner($savepath);
		if ($owner === null) {
			Error("Import [$uploadid] can not be resumed. It was started before resumable uploads were available, or its upload directory is missing");
			return "";
		}
		if ($owner != (int)$userid) {
			Error("Import [$uploadid] was started by another user. Only that user can add files to it");
			return "";
		}

		return $savepath;
	}


	/* -------------------------------------------- */
	/* ------- DisplayUploadFiles ----------------- */
	/* -------------------------------------------- */
	/* page where the user selects files from their computer and the browser uploads them in resumable
	   chunks to tusupload.php. Also used to resume an interrupted upload */
	function DisplayUploadFiles($uploadid, $userid) {
		$savepath = GetResumableUploadPath($uploadid, $userid);
		if ($savepath == "") return;

		$sqlstring = "select a.upload_type, a.upload_startdate, b.project_name, b.project_costcenter from uploads a left join projects b on a.upload_destprojectid = b.project_id where a.upload_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $uploadid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($uploadid));
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		list($complete, $incomplete) = TusGetFileStates($savepath, "tusupload.php", $uploadid);
		/* natural, case-insensitive order, so scan2 comes before scan10 */
		usort($complete, function($a, $b) { return strnatcasecmp($a['filename'], $b['filename']); });
		$completebytes = 0;
		foreach ($complete as $file)
			$completebytes += $file['length'];
		?>
		<script src="scripts/tus.min.js"></script>
		<div class="ui container">
			<div class="ui top attached grey segment">
				<h2 class="ui header">
					Upload Files
					<div class="sub header">Import <b>#<?=$uploadid?></b> into <b><?=htmlspecialchars($row['project_name'] ?? '')?> (<?=htmlspecialchars($row['project_costcenter'] ?? '')?>)</b> &nbsp; File format <b><?=htmlspecialchars($row['upload_type'] ?? '')?></b> &nbsp; Started <?=htmlspecialchars($row['upload_startdate'] ?? '')?></div>
				</h2>
			</div>
			<div class="ui attached segment">
				<? if (count($incomplete) > 0) { ?>
				<div class="ui warning message">
					<div class="header"><?=count($incomplete)?> file(s) did not finish uploading</div>
					Select the same files again below to resume them from where they stopped, or discard them.
					<table class="ui very basic compact table">
						<? foreach ($incomplete as $file) { ?>
						<tr>
							<td><?=htmlspecialchars($file['filename'])?></td>
							<td><?=HumanReadableFilesize($file['offset'])?> of <?=HumanReadableFilesize($file['length'])?> (<?=($file['length'] > 0) ? number_format(100 * $file['offset'] / $file['length'], 1) : 0?>%)</td>
							<td class="right aligned"><button class="ui mini basic red button" onClick="DiscardFile('<?=$file['url']?>'); return false;">Discard</button></td>
						</tr>
						<? } ?>
					</table>
				</div>
				<? } ?>

				<? if (count($complete) > 0) { ?>
				<div class="ui accordion">
					<div class="title">
						<i class="dropdown icon"></i>
						<b><?=count($complete)?></b> file(s) already received (<?=HumanReadableFilesize($completebytes)?>)
					</div>
					<div class="content">
						<div style="max-height: 300px; overflow-y: auto">
							<table class="ui small very compact basic table" style="width: 100%; margin: 0">
								<thead>
									<tr>
										<th style="padding: 0.3em 1.5em 0.3em 0.5em">File</th>
										<th class="right aligned" style="padding: 0.3em 1.5em 0.3em 0.5em; width: 1%; white-space: nowrap">Size</th>
										<th style="padding: 0.3em 0.5em; width: 1%; white-space: nowrap" title="When the server finished receiving the file">Received</th>
									</tr>
								</thead>
								<? foreach ($complete as $file) { ?>
								<tr>
									<td style="padding: 0.2em 1.5em 0.2em 0.5em"><tt><?=htmlspecialchars($file['filename'])?></tt></td>
									<td class="right aligned" style="padding: 0.2em 1.5em 0.2em 0.5em; white-space: nowrap"><?=HumanReadableFilesize($file['length'])?></td>
									<td style="padding: 0.2em 0.5em; white-space: nowrap"><?=($file['received'] !== null) ? date("Y-m-d H:i:s", $file['received']) : "-"?></td>
								</tr>
								<? } ?>
							</table>
						</div>
					</div>
				</div>
				<br>
				<? } ?>

				<style>
					#dropzone { border: 2px dashed #c0c0c0; border-radius: 0.5em; padding: 1.5em; text-align: center; color: #767676; transition: background-color 0.1s, border-color 0.1s; }
					#dropzone.dragover { border-color: #2185D0; background-color: #EEF6FC; color: #2185D0; }
				</style>
				<div id="dropzone">
					<i class="big cloud upload icon"></i>
					<div style="font-size: 1.15em; margin: 0.5em 0">Drag files or folders here</div>
					<div style="margin-bottom: 0.5em">or</div>
					<button class="ui button" onClick="document.getElementById('tusfiles').click(); return false;">Choose Files</button>
					<input type="file" id="tusfiles" multiple style="display: none">
				</div>
				<div class="ui checkbox" style="margin: 1em 0">
					<input type="checkbox" id="autofinish">
					<label>Start the import automatically when all files are uploaded</label>
				</div>

				<style>
					/* compact file list: one line per file, thin progress bars, scrolls when there are many files */
					#filetablewrap { max-height: 400px; overflow-y: auto; margin-bottom: 1em; }
					#filetable { table-layout: fixed; margin: 0; }
					#filetable th { position: sticky; top: 0; z-index: 1; padding: 0.4em 0.6em; }
					#filetable td { padding: 0.3em 0.6em; line-height: 1.4; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
					#filetable .ui.progress { margin: 0; }
					#filetable .ui.progress .bar { height: 0.75em; min-width: 0; }
					/* step buttons are plain grey until they are enabled, then show their color */
					#startbutton:disabled, #finishbutton:disabled { background-color: #e0e1e2 !important; color: rgba(0,0,0,0.6) !important; }
				</style>
				<div id="filetablewrap" style="display: none">
					<table class="ui small very compact celled striped table" id="filetable">
						<thead>
							<tr><th>File</th><th style="width: 7em">Size</th><th style="width: 25%">Progress</th><th style="width: 30%">Status</th></tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>

				<div class="ui progress" id="totalprogress" style="display: none">
					<div class="bar"><div class="progress"></div></div>
					<div class="label" id="totallabel"></div>
				</div>

				<div style="display: flex; justify-content: space-between; align-items: center">
					<a class="ui basic red button" href="importimaging.php?action=cancel&uploadid=<?=$uploadid?>" onClick="return confirm('Cancel this import? Files uploaded so far will not be imported');">Cancel Import</a>
					<div>
						<button class="ui primary button" id="startbutton" disabled onClick="StartUploads(); return false;">Step 1 - Upload</button>
						<button class="ui green button" id="finishbutton" <?=(count($complete) > 0) ? "" : "disabled"?> onClick="FinishUpload(); return false;">Step 2 - Import</button>
					</div>
				</div>
			</div>
		</div>

		<form method="post" action="importimaging.php" name="finalizeform">
			<input type="hidden" name="action" value="finalizeupload">
			<input type="hidden" name="uploadid" value="<?=$uploadid?>">
			<?=CSRFTokenField()?>
		</form>

		<script>
			var uploadid = <?=(int)$uploadid?>;
			var numcomplete = <?=count($complete)?>;
			/* files the server already has, and files it has partially. Selected files are matched against
			   these by name and size, so completed files are skipped and partial ones resume */
			var serverComplete = <?=json_encode($complete)?>;
			var serverIncomplete = <?=json_encode($incomplete)?>;

			var MAXPARALLEL = 3;
			var CHUNKSIZE = 50 * 1024 * 1024; /* each PATCH request stays well under Apache's 1 GiB LimitRequestBody default */
			var items = [];
			var active = 0;

			$('.ui.accordion').accordion();
			$('.ui.checkbox').checkbox();

			document.getElementById('tusfiles').addEventListener('change', function() {
				AddFiles(this.files);
				this.value = '';
			});

			/* drag and drop. dragenter/dragleave also fire for child elements, so count the depth to know
			   when the pointer has really left the drop zone */
			var dropzone = document.getElementById('dropzone');
			var dragdepth = 0;
			dropzone.addEventListener('dragenter', function(e) {
				e.preventDefault();
				dragdepth++;
				dropzone.classList.add('dragover');
			});
			dropzone.addEventListener('dragleave', function(e) {
				dragdepth--;
				if (dragdepth <= 0) {
					dragdepth = 0;
					dropzone.classList.remove('dragover');
				}
			});
			dropzone.addEventListener('dragover', function(e) {
				e.preventDefault();
				e.dataTransfer.dropEffect = 'copy';
			});
			dropzone.addEventListener('drop', function(e) {
				e.preventDefault();
				dragdepth = 0;
				dropzone.classList.remove('dragover');
				ReadDroppedItems(e.dataTransfer).then(AddFiles);
			});
			/* a file dropped outside the drop zone would otherwise make the browser open it and leave the page */
			window.addEventListener('dragover', function(e) { e.preventDefault(); });
			window.addEventListener('drop', function(e) { e.preventDefault(); });

			/* get the files from a drop, expanding any folders (recursively) into the files they contain.
			   The entries must be taken from dataTransfer during the drop event, before anything async */
			function ReadDroppedItems(dt) {
				var entries = [];
				if (dt.items && (dt.items.length > 0) && dt.items[0].webkitGetAsEntry) {
					for (var i = 0; i < dt.items.length; i++) {
						var entry = dt.items[i].webkitGetAsEntry();
						if (entry) entries.push(entry);
					}
					return ReadEntries(entries);
				}
				return Promise.resolve(Array.prototype.slice.call(dt.files));
			}

			function ReadEntries(entries) {
				return Promise.all(entries.map(ReadEntry)).then(function(lists) {
					return [].concat.apply([], lists);
				});
			}

			function ReadEntry(entry) {
				/* skip hidden files such as .DS_Store inside dropped folders */
				if (entry.name.charAt(0) == '.')
					return Promise.resolve([]);

				if (entry.isFile) {
					return new Promise(function(resolve) {
						entry.file(function(file) { resolve([file]); }, function() { resolve([]); });
					});
				}
				if (entry.isDirectory) {
					/* readEntries returns the folder's contents in batches, until it returns an empty batch */
					var reader = entry.createReader();
					var children = [];
					return new Promise(function(resolve) {
						function ReadBatch() {
							reader.readEntries(function(batch) {
								if (batch.length == 0) {
									ReadEntries(children).then(resolve);
								}
								else {
									children = children.concat(batch);
									ReadBatch();
								}
							}, function() { resolve([]); });
						}
						ReadBatch();
					});
				}
				return Promise.resolve([]);
			}

			/* warn before leaving the page while files are uploading */
			window.addEventListener('beforeunload', function(e) {
				if (active > 0) {
					e.preventDefault();
					e.returnValue = '';
				}
			});

			function HumanSize(bytes) {
				var units = ['B', 'KB', 'MB', 'GB', 'TB'];
				var i = 0;
				while ((bytes >= 1024) && (i < units.length - 1)) { bytes /= 1024; i++; }
				return bytes.toFixed(1) + ' ' + units[i];
			}

			function FindServerFile(list, file) {
				for (var i = 0; i < list.length; i++) {
					if ((list[i].filename == file.name) && (list[i].length == file.size) && (!list[i].claimed))
						return list[i];
				}
				return null;
			}

			function AddFiles(files) {
				var tbody = document.querySelector('#filetable tbody');
				for (var i = 0; i < files.length; i++) {
					var file = files[i];

					/* ignore a file that is already in the list */
					var dup = false;
					for (var j = 0; j < items.length; j++) {
						if ((items[j].file.name == file.name) && (items[j].file.size == file.size) && (items[j].file.lastModified == file.lastModified)) dup = true;
					}
					if (dup) continue;

					var row = tbody.insertRow();
					var namecell = row.insertCell();
					namecell.textContent = file.name;
					namecell.title = file.name;
					row.insertCell().textContent = HumanSize(file.size);
					row.insertCell().innerHTML = '<div class="ui tiny progress"><div class="bar" style="width: 0%"></div></div>';
					row.insertCell();

					var item = { file: file, row: row, state: 'queued', sent: 0, upload: null, resumeurl: null };

					var done = FindServerFile(serverComplete, file);
					var partial = FindServerFile(serverIncomplete, file);
					if (done) {
						done.claimed = true;
						item.state = 'skipped';
						item.sent = file.size;
						SetRow(item, 'Already uploaded', 100, 'success');
					}
					else if (partial) {
						partial.claimed = true;
						item.resumeurl = partial.url;
						item.sent = partial.offset;
						SetRow(item, 'Will resume at ' + HumanSize(partial.offset), (file.size > 0) ? 100 * partial.offset / file.size : 0, '');
					}
					else {
						SetRow(item, 'Waiting', 0, '');
					}
					items.push(item);
				}
				document.getElementById('filetablewrap').style.display = (items.length > 0) ? '' : 'none';
				document.getElementById('startbutton').disabled = !HasState('queued') || (active > 0);
				UpdateTotal();
			}

			function HasState(state) {
				for (var i = 0; i < items.length; i++)
					if (items[i].state == state) return true;
				return false;
			}

			/* status colors: green for complete, red for error, blue for everything else */
			var STATUSCOLORS = { green: '#21BA45', red: '#DB2828', blue: '#2185D0' };
			function StatusColor(cls) {
				if (cls == 'success') return 'green';
				if (cls == 'error') return 'red';
				return 'blue';
			}

			function SetRow(item, status, percent, cls) {
				var color = StatusColor(cls);
				var progress = item.row.cells[2].firstChild;
				progress.className = 'ui tiny ' + color + ' progress' + ((cls == 'active') ? ' active' : '');
				progress.firstChild.style.width = percent.toFixed(1) + '%';
				/* Fomantic UI makes the bar transparent unless data-percent is set, which would hide the color */
				progress.setAttribute('data-percent', percent.toFixed(1));
				item.row.cells[3].textContent = status;
				item.row.cells[3].title = status; /* long error messages are cut off; the full text shows on hover */
				item.row.cells[3].style.color = STATUSCOLORS[color];
				item.row.className = (cls == 'error') ? 'negative' : '';
			}

			function UpdateTotal() {
				var total = 0, sent = 0;
				for (var i = 0; i < items.length; i++) {
					total += items[i].file.size;
					sent += items[i].sent;
				}
				var bar = document.getElementById('totalprogress');
				if (items.length == 0) { bar.style.display = 'none'; return; }
				bar.style.display = '';
				var percent = (total > 0) ? 100 * sent / total : 100;
				/* red if any file failed, green when everything is uploaded, blue while in progress */
				var color = HasState('error') ? 'red' : ((HasState('queued') || HasState('uploading')) ? 'blue' : 'green');
				bar.className = 'ui ' + color + ' progress';
				bar.setAttribute('data-percent', percent.toFixed(1));
				bar.querySelector('.bar').style.width = percent.toFixed(1) + '%';
				bar.querySelector('.progress').textContent = percent.toFixed(1) + '%';
				document.getElementById('totallabel').textContent = HumanSize(sent) + ' of ' + HumanSize(total);
			}

			function StartUploads() {
				/* retry anything that failed before */
				for (var i = 0; i < items.length; i++) {
					if (items[i].state == 'error') {
						items[i].state = 'queued';
						SetRow(items[i], 'Waiting', (items[i].file.size > 0) ? 100 * items[i].sent / items[i].file.size : 0, '');
					}
				}
				document.getElementById('startbutton').disabled = true;
				document.getElementById('finishbutton').disabled = true;
				UpdateTotal();
				Pump();
			}

			/* keep up to MAXPARALLEL uploads running until the queue is empty */
			function Pump() {
				for (var i = 0; (i < items.length) && (active < MAXPARALLEL); i++) {
					if (items[i].state == 'queued')
						StartOne(items[i]);
				}
				if (active == 0)
					AllDone();
			}

			function StartOne(item) {
				active++;
				item.state = 'uploading';
				SetRow(item, 'Uploading', (item.file.size > 0) ? 100 * item.sent / item.file.size : 0, 'active');

				var options = {
					endpoint: 'tusupload.php',
					chunkSize: CHUNKSIZE,
					retryDelays: [0, 1000, 3000, 5000, 10000, 20000, 30000, 60000],
					metadata: { filename: item.file.name, context: String(uploadid) },
					/* resume points come from the server (serverIncomplete), not the browser's localStorage,
					   so an upload can be resumed from a different browser or computer */
					storeFingerprintForResuming: false,
					onProgress: function(sent, total) {
						item.sent = sent;
						SetRow(item, 'Uploading ' + HumanSize(sent) + ' of ' + HumanSize(total), (total > 0) ? 100 * sent / total : 100, 'active');
						UpdateTotal();
					},
					onError: function(err) {
						var msg = (err.originalResponse && err.originalResponse.getBody()) ? err.originalResponse.getBody() : String(err.message || err);
						item.state = 'error';
						SetRow(item, 'Error: ' + msg, (item.file.size > 0) ? 100 * item.sent / item.file.size : 0, 'error');
						UpdateTotal();
						active--;
						Pump();
					},
					onSuccess: function() {
						item.state = 'done';
						item.sent = item.file.size;
						numcomplete++;
						SetRow(item, 'Complete', 100, 'success');
						UpdateTotal();
						active--;
						Pump();
					}
				};
				if (item.resumeurl)
					options.uploadUrl = item.resumeurl;

				item.upload = new tus.Upload(item.file, options);
				item.upload.start();
			}

			function AllDone() {
				if (HasState('error')) {
					var start = document.getElementById('startbutton');
					start.textContent = 'Step 1 - Retry Failed Files';
					start.disabled = false;
					document.getElementById('finishbutton').disabled = (numcomplete == 0);
					return;
				}
				document.getElementById('finishbutton').disabled = (numcomplete == 0);
				if ((numcomplete > 0) && (document.getElementById('autofinish').checked) && (items.length > 0))
					FinishUpload();
			}

			function FinishUpload() {
				if (active > 0) {
					alert('Wait for the current uploads to finish');
					return;
				}
				document.finalizeform.submit();
			}

			/* tus termination: delete a partial file from the server */
			function DiscardFile(url) {
				if (!confirm('Discard this partially uploaded file?')) return;
				fetch(url, { method: 'DELETE', headers: { 'Tus-Resumable': '1.0.0' } }).then(function(response) {
					if (!response.ok) {
						return response.text().then(function(text) { alert('Unable to discard file: ' + text); });
					}
					window.location.reload();
				});
			}
		</script>
		<?
	}


	/* -------------------------------------------- */
	/* ------- FinalizeUpload --------------------- */
	/* -------------------------------------------- */
	/* all files are uploaded: remove the tus state and hand the upload to the upload module.
	   Returns true on success */
	function FinalizeUpload($uploadid, $userid) {
		$savepath = GetResumableUploadPath($uploadid, $userid);
		if ($savepath == "") return false;

		list($complete, $incomplete) = TusGetFileStates($savepath, "tusupload.php", $uploadid);
		if (count($incomplete) > 0) {
			$names = array();
			foreach ($incomplete as $file)
				$names[] = htmlspecialchars($file['filename']);
			Error(count($incomplete) . " file(s) have not finished uploading: " . implode(", ", $names) . "<br>Select the same files again to resume them, or discard them.");
			return false;
		}

		/* the file list is what is actually in the directory, which are the final (possibly renamed) filenames */
		$files = TusListFiles($savepath);
		$totalbytes = 0;
		foreach ($files as $file)
			$totalbytes += filesize("$savepath/$file");
		if (count($files) < 1) {
			Error("No files have been uploaded yet");
			return false;
		}

		/* remove the tus state, so the upload module only sees the uploaded data */
		TusRemoveState($savepath);

		$filelist = implode(",", $files);
		$sqlstring = "update uploads set upload_status = 'uploadcomplete', upload_enddate = now(), upload_originalfilelist = ? where upload_id = ? and upload_status = 'uploading'";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'si', $filelist, $uploadid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($filelist, $uploadid));
		mysqli_stmt_close($stmt);

		$msg = "Upload complete. Received [" . count($files) . "] files totaling [" . number_format($totalbytes) . "] bytes";
		AppendUploadLog($uploadid, $msg);
		Notice($msg . ". The files will now be parsed");

		return true;
	}


	/* -------------------------------------------- */
	/* ------- SetUploadStatus -------------------- */
	/* -------------------------------------------- */
	function SetUploadStatus($uploadid, $status) {
		$sqlstring = "update uploads set upload_status = ? where upload_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'si', $status, $uploadid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($status, $uploadid));
		mysqli_stmt_close($stmt);
	}


	/* -------------------------------------------- */
	/* ------- DisplayDcmRcvLogs ------------------ */
	/* -------------------------------------------- */
	function DisplayDcmRcvLogs($datestart, $dateend, $keyword) {
		$datestart = mysqli_real_escape_string($GLOBALS['linki'], $datestart);
		$dateend = mysqli_real_escape_string($GLOBALS['linki'], $dateend);
		$keyword = mysqli_real_escape_string($GLOBALS['linki'], $keyword);
		
		$sqlstring = "delete from upload_logs where log_date < date_sub(now(), interval 180 day)";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
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

		?>
		<div class="ui container">
			<div class="ui two column grid">
				<div class="column">
					<h1 class="ui header">Imaging Import</h1>
					Displaying 10 most recent imports. <a href="importimaging.php?displayall=1">View all</a>
				</div>
				<div class="right aligned column">
					<button class="ui primary big button" onClick="window.location.href='importimaging.php?action=newimportform'; return false;"><i class="cloud upload icon"></i> New Import</button>
				</div>
			</div>
		<?
		
		if ($displayall == "1") {
			$sqlstring = "select * from uploads a left join projects b on a.upload_destprojectid = b.project_id order by upload_startdate desc";
		}
		else {
			$sqlstring = "select * from uploads a left join projects b on a.upload_destprojectid = b.project_id order by upload_startdate desc limit 10";
		}
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0){
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$uploadid = $row['upload_id'];
				$startdate = $row['upload_startdate'];
				$enddate = $row['upload_enddate'];
				$status = $row['upload_status'];
				//$log = $row['upload_log'];
				$originalfilelist = $row['upload_originalfilelist'];
				$source = $row['upload_source'];
				$filetype = $row['upload_type'];
				$datapath = $row['upload_datapath'];
				$destprojectid = $row['upload_destprojectid'];
				$projectname = $row['project_name'];
				$projectnumber = $row['project_costcenter'];
				$modality = $row['upload_modality'];
				$guessmodality = $row['upload_guessmodality'];
				$subjectcriteria = $row['upload_subjectcriteria'];
				$studycriteria = $row['upload_studycriteria'];
				$seriescriteria = $row['upload_seriescriteria'];
				
				$filelist = explode(",", $originalfilelist);
				$filecount = count($filelist);
				
				switch ($status) {
					case 'parsingerror':
					case 'uploaderror':
					case 'archiveerror':
						$statuscolor = "red";
						$buttoncolor = "red";
						$buttonlabel = "View Details";
						$label = "<a class='ui red ribbon label'>Error</a>";
						break;
						
					case 'parsingcomplete':
						$statuscolor = "yellow";
						$buttoncolor = "yellow";
						$buttonlabel = "Choose Data to Import";
						$label = "<a class='ui yellow ribbon label'>Needs Attention</a>";
						break;

					case 'archivecomplete':
						$statuscolor = "grey";
						$buttoncolor = "";
						$buttonlabel = "View Import";
						$label = "";
						break;

					case 'cancelled':
						$statuscolor = "grey";
						$buttoncolor = "";
						$buttonlabel = "View Cancelled Import";
						$label = "";
						break;

					case 'uploading':
						$statuscolor = "secondary blue";
						$buttoncolor = "blue";
						$buttonlabel = "View Import";
						$label = ($source == "web") ? "<a class='ui blue ribbon label' href='importimaging.php?action=uploadfiles&uploadid=$uploadid'>Upload / Resume Files</a>" : "";
						break;

					default:
						$statuscolor = "secondary blue";
						$buttoncolor = "";
						$buttonlabel = "View Import";
						$label = "";
				}
				
				?>
				<p>
				<div class="ui top attached <?=$statuscolor?> segment">
					<?=$label?>
					<a class="ui <?=$buttoncolor?> button" href="importimaging.php?action=displayimport&uploadid=<?=$uploadid?>"><?=$buttonlabel?></a>
					<span style="font-size: larger;">Importing <b><?=$filecount?> files</b> into <b><?=$projectname?></b> Started <?=date("r", strtotime($startdate))?></span>
				</div>
				
				<!--
				<div class="ui attached segment">
					<details>
					<summary>Details</summary>
					<table style="all: unset;">
						<tr>
							<td>Log</td>
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
							<td>Uploaded files</td>
							<td>
								<details>
								<summary>File list (<?=count($filelist);?> files)</summary>
									<tt style="font-size:8pt"><?=implode2("<br>", $filelist)?></tt>
								</details>
							</td>
						</tr>
						<tr>
							<td>Source</td>
							<td><?=$source?></td>
						</tr>
						<tr>
							<td>Source Data path</td>
							<td><tt><?=$datapath?></tt></td>
						</tr>
						<tr>
							<td>Matching Criteria</td>
							<td>
								Subject: <?=$subjectcriteria?><br>
								Study: <?=$studycriteria?><br>
								Series: <?=$seriescriteria?>
							</td>
						</tr>
					</table>
					</details>
				</div>
				-->
				<?DisplayStatusSteps($status, "", "bottom");?>
				</p>
				<br><br>
				<?
			}
		}
		else {
			?>No current or recent uploads<?
		}
		?>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- ReparseUpload ---------------------- */
	/* -------------------------------------------- */
	function ReparseUpload($uploadid, $subjectcriteria, $studycriteria, $seriescriteria) {
		if (!ValidID($uploadid,'UploadID')) { return; }

		$sqlstring = "update uploads set upload_status = 'reparse', upload_subjectcriteria = ?, upload_studycriteria = ?, upload_seriescriteria = ? where upload_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'sssi', $subjectcriteria, $studycriteria, $seriescriteria, $uploadid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($subjectcriteria, $studycriteria, $seriescriteria, $uploadid));
		mysqli_stmt_close($stmt);
		
		Notice("Upload will be re-parsed");
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
			
			Notice("Upload queued for archiving");
		}
		else {
			$sqlstring = "update uploads set upload_status = 'archiveerror' where upload_id = $uploadid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			
			Error("No series found for this upload");
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
			$percent = $row['upload_statuspercent'];
			$originalfilelist = $row['upload_originalfilelist'];
			$source = $row['upload_source'];
			$filetype = $row['upload_type'];
			$datapath = $row['upload_datapath'];
			$destprojectid = $row['upload_destprojectid'];
			$modality = $row['upload_modality'];
			$guessmodality = $row['upload_guessmodality'];
			$subjectcriteria = $row['upload_subjectcriteria'];
			$studycriteria = $row['upload_studycriteria'];
			$seriescriteria = $row['upload_seriescriteria'];
			
			$filelist = explode(",", $originalfilelist);

			switch ($status) {
					
				case 'uploading':
					$statuscolor = "";
					$statusmsg = "Uploading";
					break;
				case 'uploadcomplete':
					$statuscolor = "";
					$statusmsg = "Upload Complete";
					break;
				case 'uploaderror':
					$statuscolor = "red";
					$statusmsg = "Upload Error";
					break;
				case 'parsing':
					$statuscolor = "";
					$statusmsg = "Parsing";
					break;
				case 'parsingcomplete':
					$statuscolor = "yellow";
					$statusmsg = "Parsing Complete";
					break;
				case 'parsingerror':
					$statuscolor = "red";
					$statusmsg = "Parsing Error";
					break;
				case 'archiving':
					$statuscolor = "";
					$statusmsg = "Archiving";
					break;
				case 'archivecomplete':
					$statuscolor = "green";
					$statusmsg = "Archiving Complete";
					break;
				case 'archiveerror':
					$statuscolor = "red";
					$statusmsg = "Archiving Error";
					break;
				case 'queueforarchive':
					$statuscolor = "";
					$statusmsg = "Queued for archiving";
					break;
				case 'reparse':
					$statuscolor = "";
					$statusmsg = "Queued for Reparsing";
					break;
					
				default:
					$statuscolor = "";
					$statusmsg = $status;
			}
			
			?>
			<div class="ui container">
				<div class="ui two column grid">
					<div class="column">
						<a class="ui primary button" href="importimaging.php"><i class="arrow alternate circle left icon"></i> Back</a>
					</div>
					<div class="right aligned column">
						<a class="ui primary button" href="importimaging.php?action=displayimport&uploadid=<?=$uploadid?>"><i class="refresh icon"></i> Refresh</a>
					</div>
				</div>
				<br><br>
				<h3 class="ui top attached inverted header">Upload Details</h3>
				<table class="ui attached celled table">
					<tr>
						<td class="right aligned"><h4 class="header">Status</h4></td>
						<td>
							<div class="ui top attached <?=$statuscolor?> message">
								<?=$statusmsg?>
								<? if ($percent != "") { ?>
									<div class="ui basic label"><?=number_format($percent, 1)?>%</div>
								<? } ?>								
							</div>
							<?DisplayStatusSteps($status, "mini", "bottom");?>
						</td>
					</tr>
					<tr>
						<td class="right aligned"><h4 class="header">Log</h4></td>
						<td>
							<div class="ui scrolling segment">						
								<?
									$sqlstring = "select * from upload_logs where upload_id = $uploadid";
									$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
								?>
								<div class="ui accordion">
									<div class="title">
										<i class="dropdown icon"></i>
										View Log <span class="tiny"><?=mysqli_num_rows($result)?> entries</span>
									</div>
									<div class="content">
										<tt><pre><?
											while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
												$date = $row['log_date'];
												$msg = $row['log_msg'];
												echo "[$date] $msg\n";
											}
										?></pre></tt>
									</div>
								</div>
							</div>
						</td>
					</tr>
					<tr>
						<td class="right aligned"><h4 class="header">Uploaded files</h4></td>
						<td>
							<div class="ui accordion">
								<div class="title">
									<i class="dropdown icon"></i>
									Original file list (<?=count($filelist);?> files)
								</div>
								<div class="content">
									<tt><?=implode2("<br>", $filelist)?></tt>
								</div>
								<?
									$sqlstringA = "SELECT * FROM upload_subjects a LEFT JOIN upload_studies b on a.uploadsubject_id = b.uploadsubject_id LEFT JOIN upload_series c on b.uploadstudy_id = c.uploadstudy_id WHERE a.upload_id = $uploadid and (uploadsubject_patientid = 'unreadable' or uploadsubject_patientid = 'NiDBunreadable')";
									$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
									$errorfiles = array();
									while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
										$errorfiles = array_merge($errorfiles, explode(",", $rowA['uploadseries_filelist']));
									}
									if (count($errorfiles) > 0) {
										?>
										<div class="ui accordion">
											<div class="title">
												<i class="dropdown icon"></i>
												Unreadable (<?=count($filelist);?> files)
											</div>
											<div class="content">
												<tt><?=implode2("<br>", $errorfiles)?></tt>
											</div>
										</div>
										<?
									}
								?>
							</div>
						</td>
					</tr>
					<tr>
						<td class="right aligned"><h4 class="header">Source</h4></td>
						<td><?=$source?></td>
					</tr>
					<tr>
						<td class="right aligned"><h4 class="header">File format</h4></td>
						<td><?=$filetype?></td>
					</tr>
					<tr>
						<td class="right aligned"><h4 class="header">Source Data Path</h4></td>
						<td><tt><?=$datapath?></tt></td>
					</tr>
					<tr>
						<td class="right aligned"><h4 class="header">Matching Criteria</h4></td>
						<td>
							Subject: <b><?=$subjectcriteria?></b><br>
							Study: <b><?=$studycriteria?></b><br>
							Series: <b><?=$seriescriteria?></b>
						</td>
					</tr>
				</table>
				<h3 class="ui attached inverted header">Operations</h3>
				<div class="ui bottom attached segment">
					<? if (($status == "uploading") && ($source == "web")) { ?>
					<a class="ui primary button" title="Select files to upload, or resume an interrupted upload" href="importimaging.php?action=uploadfiles&uploadid=<?=$uploadid?>"><i class="cloud upload icon"></i> Upload / Resume Files</a>
					<? } ?>
					<a class="ui red button" title="Cancel the upload" href="importimaging.php?action=cancel&uploadid=<?=$uploadid?>">Cancel Import</a>
				</div>
				<!--
				<h3 class="ui top attached inverted header">Reparse</h3>
				<div class="ui bottom attached segment">
					<form class="ui form" method="post" action="importimaging.php" name="reparseform">
					<input type="hidden" name="action" value="reparse">
					<input type="hidden" name="uploadid" value="<?=$uploadid?>">
					<?=CSRFTokenField()?>
					<div class="field">
						<label>Subject Matching Criteria</label>
						<select class="ui dropdown" name="subjectcriteria">
							<option value="patientid" <? if ($subjectcriteria == "patientid") echo "selected"; ?>>PatientID (DICOM 0010,0020)</option>
							<option value="specificpatientid" <? if ($subjectcriteria == "specificpatientid") echo "selected"; ?>>Specific PatientID</option>
							<option value="patientidfromdir" <? if ($subjectcriteria == "patientidfromdir") echo "selected"; ?>>PatientID from parent directory</option>
							<option value="namesexdob" <? if ($subjectcriteria == "namesexdob") echo "selected"; ?>>PatientName/PatientBirthDate/PatientSex (DICOM fields)</option>
						</select>
						<label>Study Matching Criteria</label>
						<select class="ui dropdown" name="studycriteria">
							<option value="modalitystudydate" <? if ($studycriteria == "modalitystudydate") echo "selected"; ?>>Modality/StudyDate/StudyTime (DICOM)</option>
							<option value="studyuid" <? if ($studycriteria == "studyuid") echo "selected"; ?>>StudyInstanceUID (DICOM 0020,000D)</option>
							<option value="patientidfromdir" <? if ($studycriteria == "patientidfromdir") echo "selected"; ?>>StudyID (DICOM 0020,0010)</option>
						</select>
						<label>Series Matching Criteria</label>
						<select class="ui dropdown" name="seriescriteria">
							<option value="seriesnum" <? if ($seriescriteria == "seriesnum") echo "selected"; ?>>SeriesNumber (DICOM 0020,0011)</option>
							<option value="seriesdate" <? if ($seriescriteria == "seriesdate") echo "selected"; ?>>SeriesDate/SeriesTime (DICOM)</option>
							<option value="seriesuid" <? if ($seriescriteria == "seriesuid") echo "selected"; ?>>SeriesInstanceUID (DICOM 0020,000E)</option>
						</select>
					</div>
					<button class="ui red button" title="Completely reset the upload (remove logs, and any found subjects/studies/series) and reparse based on the new settings." onClick="document.reparseform.submit();">Reparse</button>
					</form>
				</div>
				-->
			</div>
			
			
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
			
			<br><br>
			
			<? if ($status == "parsingcomplete") { ?>
			<form method="post" action="importimaging.php">
			<input type="hidden" name="action" value="queueforarchive">
			<input type="hidden" name="uploadid" value="<?=$uploadid?>">
			<?=CSRFTokenField()?>
			Select series for archiving. (All series are selected by default) &nbsp; &nbsp; <input type="submit" class="ui primary button" value="Archive">
			<? } ?>
			
			<?
			$sqlstringA = "select * from upload_subjects where upload_id = $uploadid and uploadsubject_patientid <> 'unreadable' and uploadsubject_patientid <> 'NiDBunreadable' order by uploadsubject_patientid desc";
			$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
			$numsubjects = mysqli_num_rows($resultA);
			if ($numsubjects < 1) { $smsg = "No subjects"; }
			elseif ($numsubjects == 1) { $smsg = "$numsubjects subject"; }
			elseif ($numsubjects > 1) { $smsg = "$numsubjects subjects"; }
			?>
			<h3 class="inverted header"><?=$smsg?></h3>
			<?
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
				<div class="ui styled attached fluid accordion" style="background-color: #ddd">
					<div class="title">
						<div class="ui two column grid">
							<div class="column">
								<i class="dropdown icon"></i>
								<div class="ui large blue label">
									<?=$patientid?>
								</div>
								<div class="ui large image label">
									Name
									<div class="detail"><?=$name?></div>
								</div>
							</div>
							<div class="right aligned column">
								<? if ($matchsubjectid != "") { ?>
									<div class="ui labeled button">
										<div class="ui small yellow button">
											<i class="clipboard check icon"></i> Matched existing subject
										</div>
										<a href="subjects.php?subjectid=<?=$matchsubjectid?>" class="ui yellow label" target="_blank"><?=$matchsubjectuid?></a>
									</div>
								<? } ?>
							</div>
						</div>
					</div>
					<div class="content">

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
						<div class="accordion" style="background-color: #eee">
							<div class="title">
								<div class="ui two column grid">
									<div class="column">
										<i class="dropdown icon"></i>
										<div class="ui large blue label">
											<?=$desc?>
										</div>
										<div class="ui large image label">
											Date
											<div class="detail"><?=$studydate?></div>
										</div>
										<div class="ui large image label">
											Modality
											<div class="detail"><?=$modality?></div>
										</div>
										<div class="ui large image label">
											Datatype
											<div class="detail"><?=$datatype?></div>
										</div>
									</div>
									<div class="right aligned column">
										<? if ($matchstudyid != "") { ?>
											<div class="ui labeled button">
												<div class="ui small yellow button">
													<i class="clipboard check icon"></i> Matched existing study
												</div>
												<a href="studies.php?studyid=<?=$matchstudyid?>" target="_blank" class="ui yellow label"><?=$matchsubjectuid?><?=$matchstudynum?></a>
											</div>
										<? } ?>
									</div>
								</div>
							</div>
							<div class="content">
						<?
							$sqlstringC = "select * from upload_series where uploadstudy_id = $uploadstudyid order by uploadseries_num asc";
							$resultC = MySQLiQuery($sqlstringC, __FILE__, __LINE__);
							while ($rowC = mysqli_fetch_array($resultC, MYSQLI_ASSOC)) {
								//PrintVariable($rowC);
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
								if ($protocol == "") $protocol = $desc;
								if ($seriesnum == "") $seriesnum = "(blankSeriesNum)";
								
								/* check for existing series using this specified criteria */
								$seriesmatches = GetMatchingSeries($seriescriteria, $matchstudyid, $modality, $seriesdate, $seriesnum, $seriesinstanceuid);
								//PrintVariable($seriesmatches);
								
								?>
								<div class="accordion" style="background-color: #fff">
									<div class="title">
										<div class="ui two column grid">
											<div class="column">
												<i class="dropdown icon"></i>
												<? if ($status == "parsingcomplete") { ?><input type="checkbox" name="uploadseriesid[]" value="<?=$uploadseriesid?>" checked><?}?>
												<div class="ui large blue label">
													<?=$seriesnum?>
												</div>
												<div class="ui large label">
													<?=$desc?>
												</div>
												<div class="ui large image label">
													Protocol
													<div class="detail"><?=$protocol?></div>
												</div>
												<div class="ui large image label">
													Date
													<div class="detail"><?=$seriesdate?></div>
												</div>
												<div class="ui large image label">
													Img
													<div class="detail"><?=$cols?>x<?=$rows?></div>
												</div>
											</div>
											<div class="right aligned column">
												<i>Matched <?=count($seriesmatches);?> series</i>
											</div>
										</div>
									</div>
									<div class="content">
									<?
										$files = explode(",", $filelist);
										?>
										<b><?=count($files)?> files</b><br>
										<tt style="font-size: 8pt">
										<?
										foreach ($files as $f) {
											echo "$f<br>";
										}
									?>
										</tt>
									</div>
								</div>
								<?
							}
						?>
						</div>
						</div>
						<?
					}
				?>
				</div>
				</div>
				<?
			}
			?>

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
		?>
		<?
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

		$matches = array();

		if ($studyid == "") {
			return $matches;
		}
		if (!IsNiDBModality($modality)) {
			return $matches;
		}

		$i = 0;
		$modality = strtolower($modality);
		$studyid = (int)$studyid;

		if ($seriescriteria == "seriesnum") {
			/* find existing series by seriesnum (series_num is an integer; blank/unknown is stored as 0) */
			$seriesnum = is_numeric(trim($seriesnum)) ? (int)$seriesnum : 0;
			$sqlstring = "select * from " . $modality . "_series where study_id = ? and series_num = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'ii', $studyid, $seriesnum);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($studyid, $seriesnum));
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				//PrintVariable($row);
				$matches[$i]['modality'] = $row['modality'];
				$matches[$i]['seriesid'] = $row[$modality . "series_id"];

				$i++;
			}
			mysqli_stmt_close($stmt);
		}
		elseif ($seriescriteria == "seriesdate") {
			/* find existing series by seriesdate */
			if (trim($seriesdate) != "") {
				$sqlstring = "select * from " . $modality . "_series where study_id = ? and series_datetime = ?";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'is', $studyid, $seriesdate);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($studyid, $seriesdate));
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					//PrintVariable($row);
					$matches[$i]['modality'] = $modality;
					$matches[$i]['seriesid'] = $row[$modality . "series_id"];

					$i++;
				}
				mysqli_stmt_close($stmt);
			}
		}
		elseif ($seriescriteria == "seriesuid") {
			/* find existing series by seriesuid (rare that someone would do this) */
			if (trim($seriesuid) != "") {
				$sqlstring = "select * from " . $modality . "_series where study_id = ? and series_uid = ?";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'is', $studyid, $seriesuid);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($studyid, $seriesuid));
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					//PrintVariable($row);
					$matches[$i]['modality'] = $modality;
					$matches[$i]['seriesid'] = $row[$modality . "series_id"];

					$i++;
				}
				mysqli_stmt_close($stmt);
			}
		}

		return $matches;
	}


	/* ---------------------------------------------------------- */
	/* --------- CancelUpload ----------------------------------- */
	/* ---------------------------------------------------------- */
	function CancelUpload($uploadid) {
		if (!ValidID($uploadid,'UploadID')) { return; }
		$sqlstring = "update uploads set upload_status = 'cancelled' where upload_id = $uploadid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		Notice("Upload cancelled. The upload module may take a few minutes to stop");
	}
	
	
	/* ---------------------------------------------------------- */
	/* --------- AppendUploadLog -------------------------------- */
	/* ---------------------------------------------------------- */
	function AppendUploadLog($uploadid, $m) {
		if (($uploadid >= 0) && (trim($m) != "")) {
			$str = "ImportImaging.php  " . mysqli_real_escape_string($GLOBALS['linki'], $m);

			$sqlstring = "insert ignore into upload_logs (upload_id, log_date, log_msg) values ($uploadid, now(), '$str')";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
	}


	/* ---------------------------------------------------------- */
	/* --------- DisplayStatusSteps ----------------------------- */
	/* ---------------------------------------------------------- */
	function DisplayStatusSteps($status, $size, $attachment) {
		
		// Possible statuses: 'uploading','uploadcomplete','uploaderror','parsing','parsingcomplete','parsingerror','archiving','archivecomplete','archiveerror','queueforarchive','reparse'
		
		switch ($status) {
			case 'uploading':
				$step1_title = "Started"; $step1_desc = "Upload has been submitted"; $step1_state = "active";
				$step2_title = "Uploading"; $step2_desc = "Data is uploading"; $step2_state = "disabled";
				$step3_title = "Parse"; $step3_desc = ""; $step3_state = "disabled"; $step3_icon = "tasks";
				$step4_title = "Archive"; $step4_desc = ""; $step4_state = "disabled";
				$step5_title = "Complete"; $step5_desc = ""; $step5_state = "disabled";
				break;
			case 'uploadcomplete':
				$step1_title = "Started"; $step1_desc = "Upload has been submitted"; $step1_state = "completed";
				$step2_title = "Uploaded"; $step2_desc = "Data has been uploaded"; $step2_state = "active";
				$step3_title = "Parse"; $step3_desc = ""; $step3_state = "disabled"; $step3_icon = "tasks";
				$step4_title = "Archive"; $step4_desc = ""; $step4_state = "disabled";
				$step5_title = "Complete"; $step5_desc = ""; $step5_state = "disabled";
				break;
			case 'uploaderror':
				$step1_title = "Started"; $step1_desc = "Upload has been submitted"; $step1_state = "completed";
				$step2_title = "Upload Error"; $step2_desc = "Error uploading"; $step2_state = "active";
				$step3_title = "Parse"; $step3_desc = ""; $step3_state = "disabled"; $step3_icon = "tasks";
				$step4_title = "Archive"; $step4_desc = ""; $step4_state = "disabled";
				$step5_title = "Complete"; $step5_desc = ""; $step5_state = "disabled";
				break;
			case 'parsing':
				$step1_title = "Started"; $step1_desc = "Upload has been submitted"; $step1_state = "completed";
				$step2_title = "Uploaded"; $step2_desc = "Data has been uploaded"; $step2_state = "completed";
				$step3_title = "Parsing"; $step3_desc = "Data is being parsed"; $step3_state = "active"; $step3_icon = "tasks";
				$step4_title = "Archive"; $step4_desc = ""; $step4_state = "disabled";
				$step5_title = "Complete"; $step5_desc = ""; $step5_state = "disabled";
				break;
			case 'parsingcomplete':
				$step1_title = "Start"; $step1_desc = "Upload has been submitted"; $step1_state = "completed";
				$step2_title = "Upload"; $step2_desc = "Data has been uploaded"; $step2_state = "completed";
				$step3_title = "Parsed"; $step3_desc = "Parsed data must be checked by user before archiving"; $step3_state = "active"; $step3_icon = "exclamation circle";
				$step4_title = "Archive"; $step4_desc = ""; $step4_state = "disabled";
				$step5_title = "Complete"; $step5_desc = ""; $step5_state = "disabled";
				break;
			case 'parsingerror':
				$step1_title = "Started"; $step1_desc = "Upload has been submitted"; $step1_state = "completed";
				$step2_title = "Uploaded"; $step2_desc = "Data has been uploaded"; $step2_state = "completed";
				$step3_title = "Parse Error"; $step3_desc = "Error parsing"; $step3_state = "active"; $step3_icon = "tasks";
				$step4_title = "Archive"; $step4_desc = ""; $step4_state = "disabled";
				$step5_title = "Complete"; $step5_desc = ""; $step5_state = "disabled";
				break;
			case 'archiving':
				$step1_title = "Started"; $step1_desc = "Upload has been submitted"; $step1_state = "completed";
				$step2_title = "Uploaded"; $step2_desc = "Data has been uploaded"; $step2_state = "completed";
				$step3_title = "Parsed"; $step3_desc = "Data has been parsed"; $step3_state = "completed"; $step3_icon = "tasks";
				$step4_title = "Archiving"; $step4_desc = "Data is being archived"; $step4_state = "active";
				$step5_title = "Complete"; $step5_desc = ""; $step5_state = "disabled";
				break;
			case 'archivecomplete':
				$step1_title = "Started"; $step1_desc = "Upload has been submitted"; $step1_state = "completed";
				$step2_title = "Uploaded"; $step2_desc = "Data has been uploaded"; $step2_state = "completed";
				$step3_title = "Parsed"; $step3_desc = "Data has been parsed"; $step3_state = "completed"; $step3_icon = "tasks";
				$step4_title = "Archived"; $step4_desc = "Data has been archived"; $step4_state = "completed";
				$step5_title = "Complete"; $step5_desc = "Upload complete"; $step5_state = "active";
				break;
			case 'archiveerror':
				$step1_title = "Started"; $step1_desc = "Upload has been submitted"; $step1_state = "completed";
				$step2_title = "Uploaded"; $step2_desc = "Data has been uploaded"; $step2_state = "completed";
				$step3_title = "Parsed"; $step3_desc = "Data has been parsed"; $step3_state = "completed"; $step3_icon = "tasks";
				$step4_title = "Archive Error"; $step4_desc = "Error archiving"; $step4_state = "active";
				$step5_title = "Complete"; $step5_desc = ""; $step5_state = "disabled";
				break;
			case 'queueforarchive':
				$step1_title = "Start"; $step1_desc = "Upload has been submitted"; $step1_state = "completed";
				$step2_title = "Upload"; $step2_desc = "Data has been uploaded"; $step2_state = "completed";
				$step3_title = "Parse"; $step3_desc = "Data has been parsed"; $step3_state = "completed"; $step3_icon = "tasks";
				$step4_title = "Archive"; $step4_desc = "Data queued for archiving"; $step4_state = "archive";
				$step5_title = "Complete"; $step5_desc = ""; $step5_state = "disabled";
				break;
			case 'reparse':
				$step1_title = "Start"; $step1_desc = "Upload has been submitted"; $step1_state = "completed";
				$step2_title = "Upload"; $step2_desc = "Data has been uploaded"; $step2_state = "completed";
				$step3_title = "Parse"; $step3_desc = "Data queued to be re-parsed"; $step3_state = "active"; $step3_icon = "tasks";
				$step4_title = "Archive"; $step4_desc = ""; $step4_state = "disabled";
				$step5_title = "Complete"; $step5_desc = ""; $step5_state = "disabled";
				break;
			case 'cancelled':
				$step1_title = "Started"; $step1_desc = "Upload has been submitted"; $step1_state = "active";
				$step2_title = "Uploading"; $step2_desc = "Import has been cancelled"; $step2_state = "disabled";
				$step3_title = "Parse"; $step3_desc = "Import has been cancelled"; $step3_state = "disabled";
				$step4_title = "Archive"; $step4_desc = "Import has been cancelled"; $step4_state = "disabled";
				$step5_title = "Complete"; $step5_desc = "Import has been cancelled"; $step5_state = "disabled";
				break;
			default:
		}
		
		?>
		<script>
			$(document).ready(function() {
				$('.ui .progress').progress();
			});
		</script>		
			<div class="ui <?=$attachment?> attached five <?=$size?> steps">
				<div class="<?=$step1_state?> step">
					<div class="content">
						<div class="title"><?=$step1_title?></div>
						<div class="description"><?=$step1_desc?></div>
					</div>
				</div>
				<div class="<?=$step2_state?> step">
					<i class="cloud upload icon"></i>
					<div class="content">
						<div class="green title"><?=$step2_title?></div>
						<div class="description"><?=$step2_desc?></div>
					</div>
				</div>
				<div class="<?=$step3_state?> step">
					<i class="<?=$step3_icon?> icon"></i>
					<div class="content">
						<div class="title"><?=$step3_title?></div>
						<div class="description"><?=$step3_desc?></div>
					</div>
				</div>
				<div class="<?=$step4_state?> step">
					<i class="archive icon"></i>
					<div class="content">
						<div class="title"><?=$step4_title?></div>
						<div class="description"><?=$step4_desc?></div>
					</div>
				</div>
				<div class="<?=$step5_state?> step">
					<i class="check icon"></i>
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