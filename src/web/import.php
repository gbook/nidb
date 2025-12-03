<?
 // ------------------------------------------------------------------------------
 // NiDB import.php
 // Copyright (C) 2004 - 2025
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

	/* to allow files created on a MAC to be read */
	ini_set("auto_detect_line_endings", true);
 
	require "functions.php";
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$apiaction = GetVariable("apiaction");
	$siteid = GetVariable("siteid");
	$projectid = GetVariable("projectid");
	$anonymize = GetVariable("anonymize");
	$permanent = GetVariable("permanent");
	$formid = GetVariable("formid");
	$fileformat = GetVariable("fileformat");
	$importdirs = GetVariable("importdirs");
	$idlist = GetVariable("idlist");
	$displayonlymatches = GetVariable("displayonlymatches");
	$searchthisinstance = GetVariable("searchthisinstance");
	$ignoredeleted = GetVariable("ignoredeleted");
	
	/* check for API actions that don't require the HTML page output as a response */
	if (trim($apiaction) != '') {
		switch ($apiaction) {
			case 'uploadnondicom':
				UploadNonDICOM($siteid, $projectid, $importdirs);
				break;
		}
		exit(0);
	}
	
	/* start the HTML output if no API calls */
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title><?=$_SERVER['HTTP_HOST']?> - NiDB Import Data</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";
	require "nidbapi.php";

	/* determine action */
	switch($action) {
		case 'viewassessmentformtemplate':
			DisplayAssessmentFormTemplate($formid);
			break;
		case 'viewassessmentform':
			DisplayAssessmentForm($formid);
			break;
		case 'viewassessmentforms':
			DisplayAssessmentFormList();
			break;
		case 'importassessmentform':
			ImportAssessmentForm();
			break;
		case 'importassessmentdata':
			ImportAssessmentData($siteid,$projectid);
			break;
		case 'importobservations':
			ImportObservations($siteid,$projectid,$fileformat);
			break;
		case 'updatedemographics':
			UpdateDemographics();
			break;
		case 'updateageatscan':
			UpdateAgeAtScan();
			break;
		case 'uploaddicom':
			UploadDICOM($siteid,$projectid,$anonymize,$permanent);
			break;
		case 'idmapper':
			DisplayIDMapperForm();
			break;
		case 'import':
			DisplayImportMenu();
			break;
		case 'mapids':
			MapIDs($idlist, $projectid, $displayonlymatches, $searchthisinstance, $ignoredeleted);
			break;
		default:
			DisplayMenu();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- DisplayMenu ------------------------ */
	/* -------------------------------------------- */
	function DisplayMenu() {
	
		?>
		<div class="ui container">
		
			<div class="ui spaced buttons">
				<a href="requeststatus.php" class="ui large green button"><i class="cloud download alternate icon"></i> Data Exports & Downloads</a>
				<a href="search.php" class="ui large green button"><i class="search icon"></i> Search for Data</a>
			</div>

			<h2 class="ui header">
				<i class="grey file import icon"></i>
				<div class="content">Imports
					<div class="sub header">Tools to bring data into NiDB</div>
				</div>
			</h2>
			
			<div class="ui four cards">
				<div class="ui card">
					<div class="content">
						<div class="header">ID Mapping</div>
					</div>
					<div class="content">
						Subjects and studies can having multiple IDs. This tool will match a list of IDs to existing objects.
					</div>
					<div class="center aligned extra content">
						<a href="import.php?action=idmapper" class="ui button">ID mapper</a>
					</div>
				</div>

				<div class="ui card">
					<div class="content">
						<div class="header">Import Imaging</div>
					</div>
					<div class="content">
						Upload imaging data and track existing uploads.<br><br>
					</div>
					<div class="center aligned extra content">
						<a href="importimaging.php" class="ui button">Import imaging</a>
					</div>
				</div>

				<div class="ui card">
					<div class="content">
						<div class="header">Import non-imaging</div>
					</div>
					<div class="content">
						Import observations and interventions.<br><br>
					</div>
					<div class="center aligned extra content">
						<a href="importnonimaging.php" class="ui button">Import non-imaging</a>
					</div>
				</div>

				<div class="ui card">
					<div class="content">
						<div class="header">Import Logs</div>
					</div>
					<div class="content">
						View <tt>dcmrcv</tt> import logs.<br><br><br>
					</div>
					<div class="center aligned extra content">
						<a href="importimaging.php?action=viewdcmrcvlogs" class="ui button">DICOM receiver logs</a>
					</div>
				</div>

				<div class="ui card">
					<div class="content">
						<div class="header">Uploader</div>
					</div>
					<div class="content">
						Download the <b>NiDB Uploader</b> from github.
					</div>
					<div class="center aligned extra content">
						<a href="https://github.com/gbook/nidbuploader/releases" class="ui button" target="_blank">Download the uploader</a>
					</div>
				</div>
			</div>
			
			<br><br>
			<h2 class="ui header">
				<i class="grey file export icon"></i>
				<div class="content">Exports
					<div class="sub header">Tools to get data out of NiDB</div>
				</div>
			</h2>
			
			<div class="ui four cards">
				<div class="ui card">
					<div class="content">
						<div class="header"><img src="images/squirrel-icon-64.png" height="50%"></img> Squirrel Packages</div>
					</div>
					<div class="content">
						Share data packages containing any data elements stored in NiDB. Exported in <a href="https://github.com/gbook/squirrel">squirrel</a> format.<br><br>
					</div>
					<div class="center aligned extra content">
						<a href="packages.php" class="ui button">Create & Manage Packages</a>
					</div>
				</div>

				<div class="ui card">
					<div class="content">
						<div class="header">Public Downloads</div>
					</div>
					<div class="content">
						Single public downloads. Available <b>temporarily</b> up to 90 days before being removed.<br><br>
					</div>
					<div class="center aligned extra content">
						<a href="publicdownloads.php" class="ui button">Manage Downloads</a>
					</div>
				</div>

				<div class="ui card">
					<div class="content">
						<div class="header">Public Datasets</div>
					</div>
					<div class="content">
						Groups of downloads, available <b>permamently</b>. Example: a multi-site project, where data from each site is available as a separate download
					</div>
					<div class="center aligned extra content">
						<a href="publicdatasets.php" class="ui button">Manage Datasets</a>
					</div>
				</div>

				<div class="ui card">
					<div class="content">
						<div class="header">Request a Dataset</div>
					</div>
					<div class="content">
						Your NiDB admin may allow you to submit a description of a requested dataset and destination. Check with your admin.<br><br>
					</div>
					<div class="center aligned extra content">
						<a href="datasetrequests.php" class="ui button">Manage Requests</a>
					</div>
				</div>
			</div>
			
			<!--
				<br><br>
				<i class="large disabled upload icon"></i> <span style="color: gray"><a href="import.php?action=import">Import</a> data via website (deprecated)</span>
				<br><br>
				<i class="large disabled file alternate outline icon"></i> <span style="color: gray">View <a href="importlog.php?action=viewtransactions">import logs</a> (deprecated)</span>
			-->
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayIDMapperForm ---------------- */
	/* -------------------------------------------- */
	function DisplayIDMapperForm() {
	
		?>
		<div class="ui container">
		<form action="import.php" method="post" class="ui form">
		<input type="hidden" name="action" value="mapids">
		<table width="90%" height="90%">
			<tr>
				<td>
					<div class="ui header">
						Enter list of IDs
						<div class="sub header">Acceptable delimeters: space tab comma period semicolon colon newline</div>
					</div>
				</td>
			</tr>
			<tr>
				<td>
					<textarea cols="100" rows="25" name="idlist"></textarea>
				</td>
			</tr>
			<tr>
				<td>
					<span style="font-weight: bold; color: #444">Search only this project</span><br>
					<select name="projectid" class="ui dropdown">
						<option value="all">All Projects</option>
						<?
							$sqlstring = "select * from projects where instance_id = '" . $_SESSION['instanceid'] . "' order by project_name";
							$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$project_id = $row['project_id'];
								$project_name = $row['project_name'];
								$project_costcenter = $row['project_costcenter'];
								if ($project_id == $searchvars['s_projectid']) { $selected = "selected"; } else { $selected = ""; }
								?>
								<option value="<?=$project_id?>" <?=$selected?>><?=$project_name?> (<?=$project_costcenter?>)</option>
								<?
							}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td>
					<div class="ui checkbox">
						<input type="checkbox" value="1" name="searchthisinstance">
						<label>Search only this instance (<?=$_SESSION['instancename']?>)</label>
					</div>
					<br>
					<div class="ui checkbox">
						<input type="checkbox" value="1" name="displayonlymatches">
						<label>Show only matches</label>
					</div>
					<br>
					<div class="ui checkbox">
						<input type="checkbox" value="1" name="ignoredeleted">
						<label>Do not show deleted subjects</label>
					</div>
					<br><br>
					<input type="submit" value="Map IDs" class="ui primary button">
				</td>
			</tr>
		</table>
		</div>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- MapIDs ----------------------------- */
	/* -------------------------------------------- */
	function MapIDs($idlist, $projectid, $displayonlymatches, $searchthisinstance, $ignoredeleted) {

		$idlist = preg_replace('~(*BSR_ANYCRLF)\R~', "\n", $idlist);
		$parts = preg_split('/[\^,;\'\s\t\n\f\r]+/', $idlist);
		foreach ($parts as $part) {
			$ids[] = mysqli_real_escape_string($GLOBALS['linki'], trim($part));
		}
		$ids = array_unique($ids);
		
		$instanceid = $_SESSION['instanceid'];
		?>
		A <span style="color: red"> red ID</span> means the foreign ID is contained in the alternate <b>Study</b> ID, not alternate <b>Subject</b> ID<br>
		A <span style="color: green"> green ID</span> means the foreign ID was found as a local UID<br>
		<br><br>
		<table class="ui very compact celled collapsing grey table">
			<thead>
				<th style="border-right: solid 1px #888"></th>
				<th colspan="5">Local</th>
			</thead>
			<thead>
				<th style="border-right: solid 1px #888">Foreign ID</th>
				<th>Deleted?</th>
				<th>Alt Subject ID</th>
				<th>Alt Study ID</th>
				<th>UID</th>
				<th>Enrollment</th>
			</thead>
		<?
		$numFound = 0;
		$numNotFound = 0;
		foreach ($ids as $altid) {
			$newid = 1;
			if ($altid != "") {
				$sqlstring = "select b.subject_id, b.uid, b.isactive, a.altuid,d.project_name from subject_altuid a left join subjects b on a.subject_id = b.subject_id left join enrollment c on b.subject_id = c.subject_id left join projects d on c.project_id = d.project_id where (a.altuid = '$altid' or a.altuid = sha1('$altid') or a.altuid = sha1('$altid ') or a.altuid = sha1(' $altid') or a.altuid = sha1(' $altid ') or a.altuid = sha1(upper('$altid')) or a.altuid = sha1(lower('$altid')))";
				if ($searchthisinstance)
					$sqlstring .= " and d.instance_id = $instanceid";
				if (($projectid != "all") && ($projectid != ""))
					$sqlstring .= " and c.project_id = $projectid";
				if ($ignoredeleted)
					$sqlstring .= " and b.isactive = 1";
				$sqlstring .= " group by c.enrollment_id";
				
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
				if (mysqli_num_rows($result) > 0) {
					$numFound++;
					
					if (mysqli_num_rows($result) > 1) { $bgcolor = "#FFFFD4"; }
					else { $bgcolor = ""; }
					
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$subjectid = $row['subject_id'];
						$projectname = $row['project_name'];
						$uid = $row['uid'];
						$altuid = $row['altuid'];
						$isactive = $row['isactive'];
						if (!$isactive) {
							$deleted = "&#10004;";
						}
						else {
							$deleted = "";
						}
						if ($newid) { $style = "border-top: solid 1pt #888;"; }
						else { $style = ""; }

						if ($subjectid == "") {
							?>
							<tr>
								<td style="<?=$style?>; background-color: <?=$bgcolor?>; border-right: solid 1px #888"><?=$altid?></td>
								<td style="<?=$style?>; background-color: <?=$bgcolor?>; border-right: solid 1px #888"><?=$deleted?></td>
								<td style="<?=$style?>; background-color: <?=$bgcolor?>; border-right: solid 1px #888"><?=$altuid?></td>
								<td style="<?=$style?>; background-color: <?=$bgcolor?>; border-right: solid 1px #888"></td>
								<td colspan="2" style="<?=$style?>; background-color: <?=$bgcolor?>; border-right: solid 1px #888">Subject ID was blank [<?=$subjectid?>]</td>
							</tr>
							<?
						}
						else {
							?>
							<tr>
								<td style="<?=$style?>; background-color: <?=$bgcolor?>; border-right: solid 1px #888"><?=$altid?></td>
								<td style="<?=$style?>; background-color: <?=$bgcolor?>; border-right: solid 1px #888"><?=$deleted?></td>
								<td style="<?=$style?>; background-color: <?=$bgcolor?>; border-right: solid 1px #888"><?=$altuid?></td>
								<td style="<?=$style?>; background-color: <?=$bgcolor?>; border-right: solid 1px #888"></td>
								<td style="<?=$style?>; background-color: <?=$bgcolor?>; border-right: solid 1px #888"><a href="subjects.php?id=<?=$subjectid?>"><?=$uid?></a></td>
								<td style="<?=$style?>; background-color: <?=$bgcolor?>; border-right: solid 1px #888"><?=$projectname?></td>
							</tr>
							<?
						}
						$newid = 0;
					}
				}
				else {
					$sqlstring = "select a.study_id, c.subject_id, c.isactive, c.uid, a.study_alternateid, d.project_name from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id left join projects d on d.project_id = b.project_id where a.study_alternateid = '$altid' or a.study_alternateid = sha1('$altid') or a.study_alternateid = sha1('$altid ') or a.study_alternateid = sha1(' $altid') or a.study_alternateid = sha1(' $altid ') or a.study_alternateid = sha1(upper('$altid')) or a.study_alternateid = sha1(lower('$altid'))";
					
					$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
					if (mysqli_num_rows($result) > 0) {
						$numFound++;
						
						if (mysqli_num_rows($result) > 1) { $bgcolor = "#FFFFD4"; }
						else { $bgcolor = ""; }
						
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$subjectid = $row['subject_id'];
							$studyid = $row['study_id'];
							$projectname = $row['project_name'];
							$uid = $row['uid'];
							$altuid = $row['study_alternateid'];
							$isactive = $row['isactive'];
							if (!$isactive) {
								$deleted = "&#10004;";
							}
							else {
								$deleted = "";
							}
							if ($newid) { $style = "border-top: solid 1pt #888;"; }
							else { $style = ""; }
							
							?>
							<tr>
								<td style="<?=$style?>; background-color: <?=$bgcolor?>; border-right: solid 1px #888"><?=$altid?></td>
								<td style="<?=$style?>; background-color: <?=$bgcolor?>; border-right: solid 1px #888"><?=$deleted?></td>
								<td style="<?=$style?>; background-color: <?=$bgcolor?>; border-right: solid 1px #888"></td>
								<td style="color:red; <?=$style?>; background-color: <?=$bgcolor?>; border-right: solid 1px #888"><?=$altuid?></td>
								<td style="<?=$style?>; background-color: <?=$bgcolor?>; border-right: solid 1px #888"><a href="subjects.php?id=<?=$subjectid?>"><?=$uid?></a></td>
								<td style="<?=$style?>; background-color: <?=$bgcolor?>; border-right: solid 1px #888"><?=$projectname?></td>
							</tr>
							<?
							$newid = 0;
						}
					}
					else {
						/* check if the ID is actually a local UID */
						$sqlstringA = "select * from subjects where uid = '$altid'";
						$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
						if (mysqli_num_rows($resultA) > 0) {
							$numFound++;
						
							$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
							$subjectid = $rowA['subject_id'];
							$uid = $rowA['uid'];
							$isactive = $rowA['isactive'];
							if (!$isactive) {
								$deleted = "&#10004;";
							}
							else {
								$deleted = "";
							}
							
							$sqlstringA = "select enrollment_id from enrollment where subject_id = $subjectid";
							$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
							$enrollcount = mysqli_num_rows($resultA);
							
							?>
							<tr>
								<td style="border-top: solid 1pt #888; border-right: solid 1px #888"><?=$altid?></td>
								<td style="border-top: solid 1pt #888; border-right: solid 1px #888"><?=$deleted?></td>
								<td style="color:green; border-top: solid 1pt #888; border-right: solid 1px #888"> </td>
								<td style="color:green; border-top: solid 1pt #888; border-right: solid 1px #888"> </td>
								<td style="border-top: solid 1pt #888; border-right: solid 1px #888"><a href="subjects.php?id=<?=$subjectid?>"><?=$uid?></a></td>
								<td style="<?=$style?>; background-color: <?=$bgcolor?>; border-right: solid 1px #888"><?=$enrollcount?></td>
							</tr>
							<?
						}
						else {
							$numNotFound++;
							if (!$displayonlymatches) {
								?>
								<tr>
									<td style="border-top: solid 1pt #888; border-right: solid 1px #888"><?=$altid?></td>
									<td style="border-top: solid 1pt #888; border-right: solid 1px #888" colspan="5" align="center"> </td>
								</tr>
								<?
							}
						}
					}
				}
			}
		}
		?>
		</table>
		<br>
		Found <?=$numFound?> of <?=$numNotFound+$numFound?>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayImportMenu ------------------ */
	/* -------------------------------------------- */
	function DisplayImportMenu() {
	
		?>
		
		<script>
			function AlphaNumeric(e) {
				var key;
				var keychar;

				if (window.event)
					key = window.event.keyCode;
				else if (e)
					key = e.which;
				else
					return true;
					
				keychar = String.fromCharCode(key);
				keychar = keychar.toLowerCase();

				// control keys
				if ((key==null) || (key==0) || (key==8) || (key==9) || (key==13) || (key==27) )
					return true;
				// alphas and numbers
				else if ((("abcdefghijklmnopqrstuvwxyz0123456789_").indexOf(keychar) > -1))
					return true;
				else
					return false;
			}
		</script>
		
		<span style="color:#444">Uploading data to the <b><?=$GLOBALS['instancename']?></b> instance</span>
		<br>
		<br>
		<br>
		<details>
			<summary>Upload DICOM Image Data</summary>
			<div style="margin-left: 20px; padding:8px; border: 1px solid #ccc; border-radius:5px">
			<table width="100%">
				<tr>
					<td>
						<span style="color: darkred">
						<b>Upload limits:</b> Max 1000 files per upload (unzipped). Max 1GB per file.
						</span>
						<br><br>
						<table class="entrytable">
							<form action="import.php" method="post" enctype="multipart/form-data">
							<input type="hidden" name="action" value="uploaddicom">
							<tr>
								<td class="label">Site</td>
								<td>
									<select name="siteid" required>
										<option value="">Select site...</option>
										<?
											$s = GetSiteList();
											foreach ($s as $site) { ?><option value="<?=$site['id']?>"><?=$site['name']?></option><? }
										?>
									</select>
								</td>
							</tr>
							<tr>
								<td class="label">Project</td>
								<td>
									<select name="projectid" required>
										<option value="">Select project...</option>
										<?
											$projects = GetProjectList();
											foreach ($projects as $p) { ?><option value="<?=$p['projectid']?>"><?=$p['name']?> (<?=$p['costcenter']?>)</option><? }
										?>
									</select>
								</td>
							</tr>
							<? if (!$GLOBALS['cfg']['ispublic']) { ?>
							<tr>
								<td class="label">Anonymize?</td>
								<td><input type="checkbox" name="anonymize" value="1" checked></td>
							</tr>
							<? } ?>
							<tr>
								<td class="label">Select files<br><span class="tiny">DICOM files only or a .zip<br>file containing DICOM files</span></td>
								<td><input type="file" name="files[]" multiple style="border:none"></td>
							</tr>
							<tr>
								<td colspan="2"><input type="submit" value="Import DICOM"></td>
							</tr>
							</form>
						</table>
					</td>
				</tr>
			</table>
			</div>
		</details>
		<br>
		<script>
			$(document).ready(function() {

				var MaxInputs       = 8; //maximum input boxes allowed
				var InputsWrapper   = $("#InputsWrapper"); //Input boxes wrapper ID
				var AddButton       = $("#AddMoreFileBox"); //Add button ID

				var x = InputsWrapper.length; //initlal text box count
				var FieldCount=1; //to keep track of text box added

				$(AddButton).click(function (e)  //on add input button click
				{
					if(x <= MaxInputs) //max input box allowed
					{
						FieldCount++; //text box added increment
						//add input box
						$(InputsWrapper).append('<span> / <select name="importdirs[]" id="field_'+ FieldCount +'"><option value="modality">Modality</option><option value="subjectid">Subject ID</option><option value="seriesdesc">Series description</option><option value="seriesnum">Series number</option><option value="studydesc">Study Description</option><option value="studydatetime">Study datetime</option><option value="thefiles">{The files}</option><option value="beh">beh</option></select><a href="#" class="removeclass">&times;</a></span>');
						x++; //text box increment
					}
					return false;
				});

				$("body").on("click",".removeclass", function(e){ //user click on remove text
					if( x > 1 ) {
						$(this).parent('span').remove(); //remove text box
						x--; //decrement textbox
					}
					return false;
				}); 

 				$('#nondicomform').on('submit', function(e) {
					//prevent the default submithandling
					e.preventDefault();
					//send the data of 'this' (the matched form) to yourURL
					var posting = $.post('import.php', $(this).serialize(), function(data) {
						$( "#result" ).empty().append( data );
					});
				});
			});	
		</script>
		<style>
			.removeclass { color: darkred; text-decoration:none; }
			.removeclass:hover { text-decoration:underline; }
			.direxample { font-family: monospace; white-space: pre; font-size:10pt }
			.btn { background-color: #ccc; border: 1px solid gray; color: black; text-decoration: none; padding: 1px 4px; }
			.freelabel {font-weight: bold; text-align: right; padding-right: 10px; vertical-align: top; color: #666666; font-size:12pt; white-space:nowrap; }
		</style>
		
		<? //if ($_SESSION['enablebeta']) { ?>
		<details>
			<summary>Import non-DICOM Image Data <?=PrintBeta();?></summary>
			<form action="import.php" id="nondicomform" method="post">
			<input type="hidden" name="apiaction" value="uploadnondicom">
			<div style="margin-left: 20px; padding:8px; border: 1px solid #ccc; border-radius:5px">
			<table width="100%">
				<tr>
					<td>
						<table class="entrytable">
							<tr>
								<td class="label">Site</td>
								<td>
									<select name="siteid" required>
										<option value="">Select site...</option>
										<?
											$s = GetSiteList();
											foreach ($s as $site) { ?><option value="<?=$site['id']?>"><?=$site['name']?></option><? }
										?>
									</select>
								</td>
							</tr>
							<tr>
								<td class="label">Project</td>
								<td>
									<select name="projectid" required>
										<option value="">Select project...</option>
										<?
											$projects = GetProjectList();
											foreach ($projects as $p) { ?><option value="<?=$p['projectid']?>"><?=$p['name']?> (<?=$p['costcenter']?>)</option><? }
										?>
									</select>
								</td>
							</tr>
							<tr>
								<td colspan="2">
									<span class="freelabel">Directory structure</span>
									<br>
									<details>
										<summary style="font-size:10pt">Example directory structures</summary>
										<table>
											<tr>
												<td style="padding:2px 20px;">
													<span class="direxample">/MR/23490802/t1/{files}</span>
												</td>
												<td>&rarr;</td>
												<td style="padding:2px 20px;">
													<span class="direxample">/Modality/SubjectID/SeriesDesc/{The files}</span>
												</td>
											</tr>
											<tr>
												<td style="padding:2px 20px;">
													<span class="direxample">/34890JKP/20140324_120934/5/{files}/beh</span>
												</td>
												<td>&rarr;</td>
												<td style="padding:2px 20px;">
													<span class="direxample">/SubjectID/StudyDatetime/SeriesNum/{The files}/beh</span>
												</td>
											</tr>
										</table>
									</details>
									<br>
									<a href="#" id="AddMoreFileBox" class="btn">Add Directory &rarr;</a>
									<div id="InputsWrapper">
									<span><select name="importdirs[]" id="field_0"><option value="modality">Modality</option><option value="subjectid">Subject ID</option><option value="seriesdesc">Series description</option><option value="seriesnum">Series number</option><option value="studydesc">Study Description</option><option value="studydatetime">Study datetime</option><option value="thefiles"><span style="color: blue">{The files}</span></option><option value="beh">beh</option></select><a href="#" class="removeclass">&times;</a></span>
									</div>
									<br>
								</td>
							</tr>
							<tr>
								<td colspan="2"><input type="submit" value="Import non-DICOM"> <div id="result" style="color: darkred"></div></td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
			</div>
			</form>
		</details>
		<? //} ?>
		<br>
		
		<details>
			<summary>Update Subject Demographics</summary>
			<div style="margin-left: 20px; padding:8px; border: 1px solid #ccc; border-radius:5px">
			<table width="100%">
				<tr>
					<td valign="top">
						<table class="entrytable" style="border:1px solid #ccc">
							<form action="import.php" method="post" enctype="multipart/form-data">
							<input type="hidden" name="action" value="updatedemographics">
							<tr>
								<td class="label">Select files<br><span class="tiny">.csv file in<br>NiDB demographics format</span></td>
								<td><input type="file" name="files[]" multiple style="border:none"></td>
							</tr>
							<tr>
								<td colspan="2"><input type="submit" value="Update Demographics"></td>
							</tr>
							</form>
						</table>
						<br><br>
						<b>Update age-at-scan</b><br>
						<table class="entrytable" style="border:1px solid #ccc">
							<form action="import.php" method="post" enctype="multipart/form-data">
							<input type="hidden" name="action" value="updateageatscan">
							<tr>
								<td class="label">Select files</td>
								<td><input type="file" name="files[]" multiple style="border:none"></td>
							</tr>
							<tr>
								<td colspan="2">
								.csv format (age in years: integer or decimal)
								<div style="border: 1px dashed #ccc;font-family: monospace; white-space: pre; font-size:8pt; padding: 8px">UID1,scandate,age-at-scan
UID2,scandate,age-at-scan
UID3,scandate,age-at-scan
...</div>
								</td>
							</tr>
							<tr>
								<td colspan="2"><input type="submit" value="Update age-at-scan"></td>
							</tr>
							</form>
						</table>
					</td>
					<td valign="top">
						<details>
							<summary>Demographics .csv file format</summary>
						.csv format
						<div style="border: 1px dashed #ccc;font-family: monospace; white-space: pre; font-size:8pt; padding: 8px">UID1, DOB, sex, ethnicity, race, handedness, education, maritalstatus, smokingstatus
UID2, DOB, sex, ethnicity, race, handedness, education, maritalstatus, smokingstatus
UID3, DOB, sex, ethnicity, race, handedness, education, maritalstatus, smokingstatus
...</div>
						<br>
						<b>Column values</b> <span class="tiny">(leave value blank to not update)</span>
						<ul style="font-size:10pt">
							<li>Sex
								<ul>
									<li>M - Male
									<li>F - Female
									<li>O - Other
									<li>U - Unknown
								</ul>
							<li><b>Ethnicity:</b> hispanic, nothispanic
							<li><b>Race:</b> unknown, asian, black, indian, islander, mixed, white, other
							<li>Handedness
								<ul>
									<li>L - Left
									<li>R - Right
									<li>A - Ambidextrous
									<li>U - Unknown
								</ul>
							<li>Education
								<ul>
									<li>0 - Unknown
									<li>1 - Grade school
									<li>2 - Middle school
									<li>3 - High school/GED
									<li>4 - Trade school
									<li>5 - Associates degree
									<li>6 - Bachelors degree
									<li>7 - Masters degree
									<li>8 - Doctoral degree
								</ul>
							<li><b>Marital status:</b> unknown, married, single, divorced, separated, civilunion, cohabitating, widowed
							<li><b>Smoking status:</b> unknown, never, current, past
						</ul>
						</details>
					</td>
				</tr>
			</table>
			</div>
		</details>
		<br>

		<details>
			<summary>Import Assessment Forms</summary>
			<div style="margin-left: 20px; padding:8px; border: 1px solid #ccc; border-radius:5px">
			<table width="100%">
				<tr>
					<td valign="top">
						<table class="entrytable">
							<form action="import.php" method="post" enctype="multipart/form-data">
							<input type="hidden" name="action" value="importassessmentform">
							<!--<tr>
								<td class="label">Form Name</td>
								<td><input type="text" name="newformname" maxlength="50" onKeyPress="return AlphaNumeric(event)"></td>
							</tr>-->
							<tr>
								<td class="label">Select files<br><span class="tiny">.csv file in NiDB<br>assessment format</span></td>
								<td><input type="file" name="files[]" multiple style="border:none"></td>
							</tr>
							<tr>
								<td colspan="2"><input type="submit" value="Import Assessment Forms"></td>
							</tr>
							</form>
						</table>
					</td>
					<td valign="top">
						.csv format
						<div style="border: 1px dashed #ccc;font-family: monospace; white-space: pre; font-size:8pt; padding: 5px">Title,,,,
description,,,,
question_num, question_text, datatype, values, comment</div>
						First line contains title, second line contains description. The remaining lines contain the questions
						<br><br>
						<b>Datatype</b>
						<ul>
							<li title="<big><b>multichoice Example</b></big><br><br><i>DSM Classification</i><br><br>100.1 - Silly<br>100.2 - Absurd<br>100.3 - Funny<br>100.4 - Humerous">multichoice <span class="tiny">List of pre-selected answers, multiple can be selected</span>
							<li title="<big><b>singlechoice Example</b></big><br><br><i>Handedness</i><br><br>L<br>R<br>B">singlechoice <span class="tiny">List of pre-selected answers, only one can be selected</span>
							<li>string <span class="tiny">Single line of text</span>
							<li>text <span class="tiny">Multiple lines of text</span>
							<li title="<big><b>singlechoice Example</b></big><br><br>3.141592 <i>or</i> 5">number <span class="tiny">Value that must be numeric</span>
							<li title="<big><b>singlechoice Example</b></big><br><br>2014/03/01">date <span class="tiny">A date</span>
							<li title="<big><b>singlechoice Example</b></big><br><br>The following section will assess your well-being">header <span class="tiny">Information, or section separator</span>
						</ul>
				</tr>
			</table>
			</div>
		</details>
		
		<br>

		<? if ($_SESSION['enablebeta']) { ?>
		<details>
			<summary>Import Assessment Data <?=PrintBeta();?></summary>
			<div style="margin-left: 20px; padding:8px; border: 1px solid #ccc; border-radius:5px">
			<table width="100%">
				<tr>
					<td>
						<table class="entrytable">
							<form action="import.php" method="post" enctype="multipart/form-data">
							<input type="hidden" name="action" value="importassessmentdata">
							<tr>
								<td class="label" colspan="2"><a href="import.php?action=viewassessmentforms">View Available Assessment Forms</a></td>
							</tr>
							<tr>
								<td class="label">Site</td>
								<td>
									<select name="siteid" required>
										<option value="">Select site...</option>
										<?
											$s = GetSiteList();
											foreach ($s as $site) { ?><option value="<?=$site['id']?>"><?=$site['name']?></option><? }
										?>
									</select>
								</td>
							</tr>
							<tr>
								<td class="label">Project</td>
								<td>
									<select name="projectid" required>
										<option value="">Select project...</option>
										<?
											$projects = GetProjectList();
											foreach ($projects as $p) { ?><option value="<?=$p['projectid']?>"><?=$p['name']?> (<?=$p['costcenter']?>)</option><? }
										?>
									</select>
								</td>
							</tr>
							<tr>
								<td class="label">Select files<br><span class="tiny">.csv file in NiDB<br>assessment data format</span></td>
								<td><input type="file" name="files[]" multiple style="border:none"></td>
							</tr>
							<tr>
								<td colspan="2"><input type="submit" value="Import Assessment Scores"></td>
							</tr>
							</form>
						</table>
					</td>
				</tr>
			</table>
			</div>
		</details>
		<br>
		<? } ?>
		
		<details>
			<summary>Import Observations (name/value pairs only) <?=PrintBeta();?></summary>
			<div style="margin-left: 20px; padding:8px; border: 1px solid #ccc; border-radius:5px">
			<table width="100%">
				<tr>
					<td>
						<table class="entrytable">
							<form action="import.php" method="post" enctype="multipart/form-data">
							<input type="hidden" name="action" value="importobservations">
							<tr>
								<td class="label">Site</td>
								<td>
									<select name="siteid" required>
										<option value="">Select site...</option>
										<?
											$s = GetSiteList();
											foreach ($s as $site) { ?><option value="<?=$site['id']?>"><?=$site['name']?></option><? }
										?>
									</select>
								</td>
							</tr>
							<tr>
								<td class="label">Project</td>
								<td>
									<select name="projectid" required>
										<option value="">Select project...</option>
										<?
											$projects = GetProjectList();
											foreach ($projects as $p) { ?><option value="<?=$p['projectid']?>"><?=$p['name']?> (<?=$p['costcenter']?>)</option><? }
										?>
									</select>
								</td>
							</tr>
							<tr>
								<td class="label">Select .csv files</td>
								<td><input type="file" name="files[]" multiple required style="border:none"></td>
							</tr>
							<tr>
								<td class="label">File format</td>
								<td>
									<table cellspacing="0" cellpadding="0">
										<tr>
											<td valign="top">
												<input type="radio" name="fileformat" value="short" required>Short rows <i class="small blue question circle outline icon" title="One row for each observation/value pair, no header"></i>
											</td>
											<td valign="top">
												<details><summary class="tiny">File format example</summary><pre style="font-size:10pt; border: 1px solid #aaa; border-radius:3px; padding: 5px">S1234ABC, instrument1, observation1, value<br>S1234ABC, instrument1, observation2, value<br>S1234ABC, instrument2, observation1, value<br>S1234ABC, instrument2, observation3, value</pre></details>
											</td>
										</tr>
										<tr>
											<td valign="top">
												<input type="radio" name="fileformat" value="long" required>Long rows <i class="small blue question circle outline icon" title="One row for each subject, with header"></i>
											</td>
											<td valign="top">
												<details><summary class="tiny">File format example</summary><pre style="font-size:10pt; border: 1px solid #aaa; border-radius:3px; padding: 5px">-,        instrument1, instrument1, instrument2, instrument2, etc<br>UID,      observation1,    observation2,    observation1,    observation3,    etc<br>S1234ABC, value1,      value2,      value3,      value4,      etc<br>S5678LMN, value1,      value2,      value3,      value4,      etc<br>S9292XYZ, value1,      value2,      value3,      value4,      etc</pre></details>
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td colspan="2"><input type="submit" value="Import Observations"></td>
							</tr>
							</form>
						</table>
					</td>
				</tr>
			</table>
			</div>
		</details>
		
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- UploadDICOM ------------------------ */
	/* -------------------------------------------- */
	function UploadDICOM($siteid,$projectid,$anonymize,$permanent) {
	
		/* get next import ID */
		$sqlstring = "insert into import_requests (import_datatype, import_datetime, import_status, import_siteid, import_projectid, import_anonymize, import_permanent) values ('dicom',now(),'uploading','$siteid','$projectid','$anonymize','$permanent')";
		//echo "[[$sqlstring]]";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$uploadID = mysqli_insert_id($GLOBALS['linki']);
		
		//echo "I'm still here\n";
		$savepath = $GLOBALS['cfg']['uploadeddir'] . "/$uploadID";
		$behsavepath = $GLOBALS['cfg']['uploadeddir'] . "/$uploadID/beh";
		
		/* create the directory in which the files are stored until the import module takes them */
		mkdir($savepath, 0, true);
		mkdir($behsavepath, 0, true);
		chmod($savepath, 0777);
		chmod($behsavepath, 0777);
		
		echo "<ul>";
		
		/* go through all the files and save them */
		foreach ($_FILES['files']['name'] as $i => $name) {
			if (move_uploaded_file($_FILES['files']['tmp_name'][$i], "$savepath/$name")) {
				echo "<li>Received $name - " . number_format($_FILES['files']['size'][$i]) . " bytes<br>";
				chmod("$savepath/$name", 0777);
			}
			else {
				echo "<br>An error occured moving [" . $_FILES['files']['tmp_name'][$i] . "] error: [" . $_FILES['files']['error'][$i] . "]<br>";
			}
		}
		/* go through all the beh files and save them */
		foreach ($_FILES['behs']['name'] as $i => $name) {
			if (move_uploaded_file($_FILES['behs']['tmp_name'][$i], "$behsavepath/$name")) {
				echo "<li>Received $name - " . number_format($_FILES['behs']['size'][$i]) . " bytes<br>";
				chmod("$behsavepath/$name", 0777);
			}
			else {
				echo "<br>An error occured moving [" . $_FILES['behs']['tmp_name'][$i] . "] error: [" . $_FILES['behs']['error'][$i] . "]<br>";
			}
		}
		
		echo "</ul>";
		
		$sqlstring = "update import_requests set import_status = 'pending' where importrequest_id = $uploadID";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}

	
	/* -------------------------------------------- */
	/* ------- UploadNonDICOM --------------------- */
	/* -------------------------------------------- */
	function UploadNonDICOM($siteid,$projectid,$importdirs) {
	
		/* get next import ID */
		$sqlstring = "insert into import_requests (import_datatype, import_datetime, import_status, import_siteid, import_projectid) values ('dicom',now(),'created','$siteid','$projectid')";
		//echo "[[$sqlstring]]";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$uploadID = mysqli_insert_id($GLOBALS['linki']);
		
		if (is_array($importdirs)) {
			$i = 0;
			foreach ($importdirs as $importdir) {
				$sqlstring = "insert into import_requestdirs (importrequest_id, dir_num, dir_type) values ($uploadID, $i, '$importdir')";
				//echo "[[$sqlstring]]";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				$i++;
			}
		}
		else {
			$sqlstring = "insert into import_requestdirs (importrequest_id, dir_num, dir_type) values ($uploadID, 0, '$importdirs')";
			//echo "[[$sqlstring]]";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		if ($uploadID != '') {
			echo "Upload created with ID <b>$uploadID</b>. When you've copied your data to /upload/$uploadID, come back and mark it as ready-to-import";
		}
		else {
			echo "ERROR: Upload ID not created";
		}
	}


	/* -------------------------------------------- */
	/* ------- DisplayAssessmentFormList ---------- */
	/* -------------------------------------------- */
	function DisplayAssessmentFormList() {
	
		?>
		<table class="ui very compact celled grey table">
			<thead>
				<th>Name</th>
				<th>Description</th>
				<th>Creator</th>
				<th>Created date</th>
				<th>.csv template</th>
			</thead>
		<?
		$sqlstring = "select * from assessment_forms where form_ispublished = 1";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$id = $row['form_id'];
			$title = $row['form_title'];
			$desc = $row['form_desc'];
			$creator = $row['form_creator'];
			$createdate = $row['form_createdate'];
			?>
			<tr>
				<td><a href="import.php?action=viewassessmentform&formid=<?=$id?>"><?=$title?></a></td>
				<td><?=$desc?></td>
				<td><?=$creator?></td>
				<td><?=$createdate?></td>
				<td><a href="import.php?action=viewassessmentformtemplate&formid=<?=$id?>">.csv</a></td>
			</tr>
			<?
		}
		?>
		</table>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- ImportObservations --------------------- */
	/* -------------------------------------------- */
	function ImportObservations($siteid, $projectid, $fileformat) {
	
		$instanceid = $_SESSION['instanceid'];
	
		$savepath = $GLOBALS['cfg']['tmpdir'] . '/' . GenerateRandomString(20);
		
		/* create the directory in which the files are stored until the import module takes them */
		mkdir($savepath, 0, true);
		chmod($savepath, 0777);
		
		echo "<ul>";
		
		/* go through all the files and save them */
		foreach ($_FILES['files']['name'] as $i => $name) {
			if (move_uploaded_file($_FILES['files']['tmp_name'][$i], "$savepath/$name")) {
				echo "<li>Received [$name] - " . number_format($_FILES['files']['size'][$i]) . " bytes<br>";
				chmod("$savepath/$name", 0777);
				if (ValidateObservations("$savepath/$name", $fileformat)) {
					echo "<li>Observations file [$name] is valid, inserting into database...";
					InsertObservations("$savepath/$name", $projectid, $fileformat);
				}
				else {
					echo "<li>Observations file [$name] is not valid. See errors above.";
				}
			}
			else {
				echo "<li>An error occured moving " . $_FILES['files']['tmp_name'][$i] . " to [" . $_FILES['files']['error'][$i] . "]<br>";
			}
		}
		
		echo "</ul>";
	}
	
	
	/* -------------------------------------------- */
	/* ------- ValidateObservations ------------------- */
	/* -------------------------------------------- */
	function ValidateObservations($f, $fileformat) {
	
		/* open the file and check some fields */
		$lines = file($f);

		echo "<pre style='border: solid 1px #ccc; padding:5px'>";

		$numErrors = 0;
		$numWarnings = 0;
		$L = 1;
		/* check if its the short format */
		if ($fileformat == "short") {
			for ($i=0;$i<count($lines);$i++) {
				$line = $lines[$i];
				$parts = str_getcsv($line);
				$validid = false;
				
				/* check for the correct number of columns */
				$c = count($parts);
				if ($c != 4) {
					echo "Incorrect number of columns. Should be 4, but is actually [$c]. Line $L<br>";
					$numErrors++;
					continue;
				}
				
				/* separate out the columns */
				$uid = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[0]));
				$instrument = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[1]));
				$observation = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[2]));
				$value = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[3]));
				
				/* ----- check each column ----- */
				/* check if the UID exists in any format anywhere */
				$uid = mysqli_real_escape_string($GLOBALS['linki'], trim($uid));
				$sqlstring = "select subject_id from subjects where uid = '$uid'";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					$validid = true;
				}
				else {
					$sqlstring = "select subject_id from subject_altuid where altuid = '$uid' or altuid = sha1('$uid')";
					//PrintSQL($sqlstring);
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					if (mysqli_num_rows($result) > 0) {
						$validid = true;
					}
				}
				
				if (!$validid) {
					echo "Row $L, Column 1 (UID) does not contain a valid UID [$uid]<br>";
					$numErrors++;
				}
				
				/* check for blank entries in other columns */
				if ($uid == "") {
					echo "Row $L, Column 1 (UID) is blank<br>";
					$numErrors++;
				}
				if ($observation == "") {
					echo "Row $L, Column 2 (Instrument name) is blank<br>";
					$numErrors++;
				}
				if ($observation == "") {
					echo "Row $L, Column 3 (Observation name) is blank<br>";
					$numErrors++;
				}
				/* blank values are OK, don't check for them */
				if ($value == "") {
					//echo "Warning (not an error) column 4 (Observation value) is blank. Line $L<br>";
					//$numErrors++;
				}
				
				$L++;
			}
		}
		/* otherwise its the long format */
		else {
			for ($i=0;$i<count($lines);$i++) {
				$line = $lines[$i];
				$parts = str_getcsv($line);
				$validid = false;
				
				/* check for the correct number of columns */
				$c = count($parts);
				if ($c < 2) {
					echo "Incorrect number of columns. Should be at least 2, but is actually [$c]. Line $L<br>";
					$numErrors++;
					continue;
				}
				
				if ($i == 0) {
					/* get the first line, the instruments */
					$instruments = mysqli_real_escape_string($GLOBALS['linki'], array_shift($parts));
				}
				elseif ($i == 1) {
					/* get the second line, the observations */
					$observations = mysqli_real_escape_string($GLOBALS['linki'], array_shift($parts));
				}
				else {
					/* otherwise, it should be a real line... with data */
					
					/* separate out the columns */
					$col=0;
					foreach ($parts as $part) {
						$value = mysqli_real_escape_string($GLOBALS['linki'], trim($part));
						
						if ($col == 0) {
							$uid = $value;
							$subjectRowID = GetSubjectRowID($uid);
							
							//echo "SubjectRowID for UID [$uid]: [$subjectRowID]<br>";
							
							if ($subjectRowID < 1) {
								echo "Row $L, Column 1 (UID) does not contain a valid UID [$uid]<br>";
								$numErrors++;
							}
						}
						else {
							$instrument = $instruments[$col];
							$observation = $observations[$col];
							/* check for blank entries in other columns */
							if ($value == "") {
								//echo "Warning (not an error) column $col (Observation value) is blank. Line $L<br>";
								//$numErrors++;
							}
						}
						
						$col++;
					}
				}
				
				$L++;
			}
		}
		
		echo "Checked $L lines";
		echo "</pre>";
		
		if ($numErrors == 0) {
			return 1;
		}
		else {
			return 0;
		}
	}

	
	/* -------------------------------------------- */
	/* ------- UpdateDemographics ----------------- */
	/* -------------------------------------------- */
	function UpdateDemographics() {
	
		$instanceid = $_SESSION['instanceid'];
	
		$savepath = $GLOBALS['cfg']['tmpdir'] . '/' . GenerateRandomString(20);
		
		/* create the directory in which the files are stored until the import module takes them */
		mkdir($savepath, 0, true);
		chmod($savepath, 0777);
		
		echo "<ul>";
		
		/* go through all the files and save them */
		foreach ($_FILES['files']['name'] as $i => $name) {
			if (move_uploaded_file($_FILES['files']['tmp_name'][$i], "$savepath/$name")) {
				echo "<li>Received [$name] - " . number_format($_FILES['files']['size'][$i]) . " bytes<br>";
				chmod("$savepath/$name", 0777);
				if (ValidateDemographics("$savepath/$name")) {
					echo "<li>Observations file [$name] is valid, inserting into database...";
					InsertDemographics("$savepath/$name");
				}
				else {
					echo "<li>Observations file [$name] is not valid. See errors above.";
				}
			}
			else {
				echo "<li>An error occured moving " . $_FILES['files']['tmp_name'][$i] . " to [" . $_FILES['files']['error'][$i] . "]<br>";
			}
		}
		
		echo "</ul>";
	}


	/* -------------------------------------------- */
	/* ------- ValidateDemographics --------------- */
	/* -------------------------------------------- */
	function ValidateDemographics($f) {
	
		/* open the file and check some fields */
		$lines = file($f);

		echo "<pre style='border: solid 1px #ccc; padding:5px'>";

		$numErrors = 0;
		$numWarnings = 0;
		$L = 1;
		/* check if its the short format */
		for ($i=0;$i<count($lines);$i++) {
			$line = $lines[$i];
			$parts = str_getcsv($line);
			$validid = false;
			
			/* check for the correct number of columns */
			$c = count($parts);
			if ($c != 9) {
				echo "Incorrect number of columns. Should be 9, but is actually [$c]. Line $L<br>";
				$numErrors++;
				continue;
			}
			
			/* separate out the columns */
			$uid = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[0]));
			$dob = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[1]));
			$sex = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[2]));
			$ethnicity = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[3]));
			$race = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[4]));
			$handedness = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[5]));
			$education = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[6]));
			$marital = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[7]));
			$smoking = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[8]));
			
			/* ----- check each column ----- */
			/* check if the UID exists in any format anywhere */
			$uid = mysqli_real_escape_string($GLOBALS['linki'], trim($uid));
			$sqlstring = "select subject_id from subjects where uid = '$uid'";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			if (mysqli_num_rows($result) > 0) {
				$validid = true;
			}
			else {
				$sqlstring = "select subject_id from subject_altuid where altuid = '$uid' or altuid = sha1('$uid') or altuid = sha1(upper('$uid')) or altuid = sha1(lower('$uid'))";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					$validid = true;
				}
			}
			
			if (!$validid) {
				echo "Column 1 (UID) does not contain a valid  subject ID [$uid]. Line $L<br>";
				$numErrors++;
			}
			
			/* check for blank entries in other columns */
			if ($uid == "") {
				echo "Column 1 (UID) is blank. Line $L<br>";
				$numErrors++;
			}
			
			$L++;
		}
		
		echo "Checked $L lines";
		echo "</pre>";
		
		if ($numErrors == 0) {
			return 1;
		}
		else {
			return 0;
		}
	}


	/* -------------------------------------------- */
	/* ------- InsertDemographics ----------------- */
	/* -------------------------------------------- */
	function InsertDemographics($f) {
	
		/* open the file and check some fields */
		$lines = file($f);

		$c=0;
		/* check if its the short format */
		for ($i=0;$i<count($lines);$i++) {
			$line = $lines[$i];
			$parts = str_getcsv($line);
			
			/* separate out the columns */
			$uid = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[0]));
			$dob = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[1]));
			$sex = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[2]));
			$ethnicity = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[3]));
			$race = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[4]));
			$handedness = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[5]));
			$education = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[6]));
			$marital = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[7]));
			$smoking = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[8]));
			
			/* ----- check each column ----- */
			/* get subjectID */
			$subjectRowID = GetSubjectRowID($uid);
			
			if ($subjectRowID != "") {
				$sqlstring = "update subjects set";
				if ($dob != "") {
					$thedate = date_parse($dob);
					$dob = $thedate['year'] . "-" . $thedate['month'] . "-" . $thedate['day'];
					$sqlstring .= " birthdate = '$dob'";
				}
				if ($sex != "") { $sqlstring .= " gender = '$gender'"; }
				if ($ethnicity != "") { $sqlstring .= " ethnicity1 = '$ethnicity'"; }
				if ($race != "") { $sqlstring .= " ethnicity2 = '$ethnicity2'"; }
				if ($handedness != "") { $sqlstring .= " handedness = '$handedness'"; }
				if ($education != "") { $sqlstring .= " education = '$education'"; }
				if ($marital != "") { $sqlstring .= " marital_status = '$marital'"; }
				if ($smoking != "") { $sqlstring .= " smoking_status = '$smoking'"; }
				$sqlstring .= " where subject_id = $subjectRowID";
			
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
				//echo "[" . mysqli_affected_rows() . "]<br>";
				$numupdated += mysqli_affected_rows($GLOBALS['linki']);
			}
			
			$c++;
		}
		?>
		<li><span style="color: darkblue">Updated <?=$numupdated?> of <?=$c?> demographic rows</span> <span class="tiny">Some rows may already have been up to date</span>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- UpdateAgeAtScan -------------------- */
	/* -------------------------------------------- */
	function UpdateAgeAtScan() {
	
		$instanceid = $_SESSION['instanceid'];
	
		$savepath = $GLOBALS['cfg']['tmpdir'] . '/' . GenerateRandomString(20);
		
		/* create the directory in which the files are stored until the import module takes them */
		mkdir($savepath, 0, true);
		chmod($savepath, 0777);
		
		echo "<ul>";
		
		/* go through all the files and save them */
		foreach ($_FILES['files']['name'] as $i => $name) {
			if (move_uploaded_file($_FILES['files']['tmp_name'][$i], "$savepath/$name")) {
				echo "<li>Received [$name] - " . number_format($_FILES['files']['size'][$i]) . " bytes<br>";
				chmod("$savepath/$name", 0777);
				if (ValidateAgeAtScan("$savepath/$name")) {
					echo "<li>age-at-scan file [$name] is valid, inserting into database...";
					InsertAgeAtScan("$savepath/$name");
				}
				else {
					echo "<li>age-at-scan file [$name] is not valid. See errors above.";
				}
			}
			else {
				echo "<li>An error occured moving " . $_FILES['files']['tmp_name'][$i] . " to [" . $_FILES['files']['error'][$i] . "]<br>";
			}
		}
		
		echo "</ul>";
	}


	/* -------------------------------------------- */
	/* ------- ValidateAgeAtScan ------------------ */
	/* -------------------------------------------- */
	function ValidateAgeAtScan($f) {
	
		/* open the file and check some fields */
		$lines = file($f);

		echo "<pre style='border: solid 1px #ccc; padding:5px'>";

		$numErrors = 0;
		$numWarnings = 0;
		$L = 1;
		/* check if its the short format */
		for ($i=0;$i<count($lines);$i++) {
			$line = $lines[$i];
			$parts = str_getcsv($line);
			$validid = false;
			
			/* check for the correct number of columns */
			$c = count($parts);
			if ($c != 3) {
				echo "Incorrect number of columns. Should be 3, but is actually [$c]. Line $L<br>";
				$numErrors++;
				continue;
			}
			
			/* separate out the columns */
			$uid = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[0]));
			$scandate = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[1]));
			$age = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[2]));
			
			/* ----- check each column ----- */
			/* check if the UID exists in any format anywhere */
			$uid = mysqli_real_escape_string($GLOBALS['linki'], trim($uid));
			$sqlstring = "select subject_id from subjects where uid = '$uid'";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			if (mysqli_num_rows($result) > 0) {
				$validid = true;
			}
			else {
				$sqlstring = "select subject_id from subject_altuid where altuid = '$uid' or altuid = sha1('$uid') or altuid = sha1(upper('$uid')) or altuid = sha1(lower('$uid'))";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					$validid = true;
				}
			}
			
			if (!$validid) {
				echo "Column 1 (UID) does not contain a valid  subject ID [$uid]. Line $L<br>";
				$numErrors++;
			}
			
			/* check for blank entries in other columns */
			if ($uid == "") {
				echo "Column 1 (UID) is blank. Line $L<br>";
				$numErrors++;
			}
			if ($scandate == "") {
				echo "Column 2 (scan date) is blank. Line $L<br>";
				$numErrors++;
			}
			if ($age == "") {
				echo "Column 3 (age-at-scan) is blank. Line $L<br>";
				$numErrors++;
			}
			
			$parseddate = date_parse($scandate);
			if ($parseddate['error_count'] > 0) {
				echo "Column 2 (scan date) is not valid [$scandate]. Line $L<br>";
				$numErrors++;
			}
			
			$L++;
		}
		
		echo "Checked $L lines";
		echo "</pre>";
		
		if ($numErrors == 0) {
			return 1;
		}
		else {
			return 0;
		}
	}


	/* -------------------------------------------- */
	/* ------- InsertAgeAtScan -------------------- */
	/* -------------------------------------------- */
	function InsertAgeAtScan($f) {
	
		/* open the file and check some fields */
		$lines = file($f);

		$c=0;
		/* check if its the short format */
		for ($i=0;$i<count($lines);$i++) {
			$line = $lines[$i];
			$parts = str_getcsv($line);
			
			/* separate out the columns */
			$uid = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[0]));
			$scandate = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[1]));
			$age = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[2]));
			
			/* ----- check each column ----- */
			/* get subjectID */
			$subjectRowID = GetSubjectRowID($uid);
			
			if ($subjectRowID != "") {
				$thedate = date_parse($scandate);
				$scandate = $thedate['year'] . "-" . $thedate['month'] . "-" . $thedate['day'];
				$sqlstring = "update studies set study_ageatscan = '$age' where date(study_datetime) = '$scandate' and enrollment_id in (select enrollment_id from enrollment where subject_id = $subjectRowID)";
			
				PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
				echo "[" . mysqli_affected_rows() . "]<br>";
				$numupdated += mysqli_affected_rows($GLOBALS['linki']);
			}
			
			$c++;
		}
		?>
		<li><span style="color: darkblue">Updated <?=$numupdated?> of <?=$c?> demographic rows</span> <span class="tiny">Some rows may already have been up to date</span>
		<?
	}
	

	/* -------------------------------------------- */
	/* ------- GetSubjectRowID -------------------- */
	/* -------------------------------------------- */
	function GetSubjectRowID($uid) {
		$subjectRowID = 0;
		
		/* check if the UID exists in any format anywhere */
		$uid = mysqli_real_escape_string($GLOBALS['linki'], trim($uid));
		$sqlstring = "select subject_id from subjects where uid = '$uid'";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0) {
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$subjectRowID = $row['subject_id'];
		}
		else {
			$sqlstring = "select subject_id from subject_altuid where altuid = '$uid' or altuid = sha1('$uid')";
			//PrintSQL($sqlstring);
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
				$subjectRowID = $row['subject_id'];
			}
		}
		
		return $subjectRowID;
	}

	
	/* -------------------------------------------- */
	/* ------- EnrollSubject ---------------------- */
	/* -------------------------------------------- */
	function EnrollSubject($subjectRowID, $projectRowID) {
		$sqlstring = "select enrollment_id from enrollment where subject_id = $subjectRowID and project_id = $projectRowID";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if (mysqli_num_rows($result) > 0) {
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$enrollmentRowID = $row['enrollment_id'];
		}
		else {
			$sqlstring = "insert into enrollment (project_id, subject_id, enroll_startdate) values ($projectRowID, $subjectRowID, now())";
			//PrintSQL($sqlstring);
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			$enrollmentRowID = mysqli_insert_id($GLOBALS['linki']);
		}
		
		return $enrollmentRowID;
	}

	
	/* -------------------------------------------- */
	/* ------- InsertInstrumentName --------------- */
	/* -------------------------------------------- */
	//function InsertInstrumentName($instrument) {
	//	$sqlstring = "select observationinstrument_id from observationinstruments where instrument_name = '$instrument'";
	//	//PrintSQL($sqlstring);
	//	$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
	//	if (mysqli_num_rows($result) > 0) {
	//		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
	//		$observationinstrumentnameid = $row['observationinstrument_id'];
	//	}
	//	else {
	//		$sqlstring = "insert into observationinstruments (instrument_name) values ('$instrument')";
	//		//PrintSQL($sqlstring);
	//		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
	//		$observationinstrumentnameid = mysqli_insert_id($GLOBALS['linki']);
	//	}
	//	return $observationinstrumentnameid;
	//}

	
	/* -------------------------------------------- */
	/* ------- Insertobservationname -------------- */
	/* -------------------------------------------- */
	//function Insertobservationname($observation) {
	//	$sqlstring = "select observationname_id from observationnames where observation_name = '$observation'";
	//	//PrintSQL($sqlstring);
	//	$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
	//	if (mysqli_num_rows($result) > 0) {
	//		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
	//		$observationnameid = $row['observationname_id'];
	//	}
	//	else {
	//		$sqlstring = "insert into observationnames (observation_name) values ('$observation')";
	//		//PrintSQL($sqlstring);
	//		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
	//		$observationnameid = mysqli_insert_id($GLOBALS['linki']);
	//	}
	//	return $observationnameid;
	//}


	/* -------------------------------------------- */
	/* ------- ImportAssessmentForm --------------- */
	/* -------------------------------------------- */
	function ImportAssessmentForm() {
	
		$savepath = $GLOBALS['cfg']['tmpdir'] . '/' . GenerateRandomString(20);
		
		/* create the directory in which the files are stored until the import module takes them */
		mkdir($savepath, 0, true);
		chmod($savepath, 0777);
		
		echo "<ul>";
		
		/* go through all the files and save them */
		foreach ($_FILES['files']['name'] as $i => $name) {
			if (move_uploaded_file($_FILES['files']['tmp_name'][$i], "$savepath/$name")) {
				echo "<li>Received $name - " . number_format($_FILES['files']['size'][$i]) . " bytes<br>";
				chmod("$savepath/$name", 0777);
				if (ValidateAssessmentForm("$savepath/$name")) {
					echo "<br>Assessment form is valid, inserting into database<br>";
					InsertAssessmentForm("$savepath/$name");
				}
				else {
					echo "<br>Assessment form is not valid. See errors above.";
				}
			}
			else {
				echo "<br>An error occured moving " . $_FILES['files']['tmp_name'][$i] . " to [" . $_FILES['files']['error'][$i] . "]<br>";
			}
		}
		
		echo "</ul>";
	}
	
	
	/* -------------------------------------------- */
	/* ------- ValidateAssessmentForm ------------- */
	/* -------------------------------------------- */
	function ValidateAssessmentForm($f) {
	
		/* open the file and check some fields */
		$lines = file($f);
		//echo "<pre>";
		//print_r($lines);
		//echo "</pre>";
		
		$parts = str_getcsv($lines[0]);
		$formtitle = $parts[0];
		if ($formtitle == '') {
			echo "Column 1 (assessment form name) is blank. Line 1<br>";
			$numErrors++;
		}
		$parts = str_getcsv($lines[1]);
		$formdesc = $parts[0];
		if ($formdesc == '') {
			echo "Column 1 (assessment form description) is blank. Line 2<br>";
			$numErrors++;
		}
		
		$numErrors = 0;
		$numWarnings = 0;
		$L = 3;
		for ($i=2;$i<count($lines);$i++) {
		//foreach ($lines as $line) {
			$line = $lines[$i];
			$parts = str_getcsv($line);
			
			/* check for the correct number of columns */
			$c = count($parts);
			if (($c != 3) && ($c != 4) && ($c != 5)) {
				echo "Incorrect number of columns ($c). Line $line<br>";
				$numErrors++;
				continue;
			}
			
			/* separate out the columns */
			$qnum = trim($parts[0]);
			$question = trim($parts[1]);
			$type = trim($parts[2]);
			if ($c > 3) {
				$values = trim($parts[3]);
				if ($c > 4) {
					$comment = trim($parts[4]);
				}
			}
			
			/* check each column */
			if (!is_numeric($qnum)) {
				echo "Column 1 (question number) is not a valid number. Line $L<br>";
				$numErrors++;
				continue;
			}
			
			if ($question == '') {
				echo "Column 2 (question) is blank. Line $L<br>";
				$numErrors++;
				continue;
			}
			
			if (!in_array($type,array('singlechoice','multichoice','string','text','number','date','header','binary'))) {
				echo "Column 3 (answer type) is not a valid. Line $L<br>";
				$numErrors++;
				continue;
			}
			
			if (($type != 'singlechoice') && ($type != 'multichoice') && ($values != '')) {
				echo "Column 5 (question values). Values will be ignored because column is not of type single or multichoice. Line $L<br>";
				$numWarnings++;
			}
			
			$L++;
		}
		
		/* print the summary */
		if ($numErrors > 0) {
			return false;
		}
		else {
			return true;
		}
	}


	/* -------------------------------------------- */
	/* ------- InsertAssessmentForm --------------- */
	/* -------------------------------------------- */
	function InsertAssessmentForm($f) {
		
		/* open the file and check some fields */
		$lines = file($f);

		$parts = str_getcsv($lines[0]);
		$formtitle = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[0]));
		$parts = str_getcsv($lines[1]);
		$formdesc = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[0]));

		$sqlstring = "insert into assessment_forms (form_title, form_desc, form_creator, form_createdate) values ('$formtitle','$formdesc','" . $GLOBALS['username'] . "',now())";
		PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$assessmentID = mysqli_insert_id($GLOBALS['linki']);
		
		for ($i=2;$i<=count($lines);$i++) {
			$line = $lines[$i];
			$parts = str_getcsv($line);
			$c = count($parts);
			
			/* separate out the columns */
			$qnum = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[0]));
			$question = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[1]));
			$type = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[2]));
			if ($c > 3) {
				$values = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[3]));
				if ($c > 4) {
					$comment = mysqli_real_escape_string($GLOBALS['linki'], trim($parts[4]));
				}
			}
			
			$sqlstring = "insert into assessment_formfields (form_id, formfield_desc, formfield_values, formfield_datatype, formfield_order) values ($assessmentID,'$question','$values','$type','$qnum')";
			PrintSQL($sqlstring);
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		
		$sqlstring = "update assessment_forms set form_ispublished = 1 where form_id = $assessmentID";
		PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}


	/* -------------------------------------------- */
	/* ------- DisplayAssessmentFormTemplate ------ */
	/* -------------------------------------------- */
	function DisplayAssessmentFormTemplate($id) {
		$sqlstring = "select * from assessment_forms where form_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$title = $row['form_title'];
		$desc = $row['form_desc'];
		
		$csv = "$id\n";
		/* display all other rows, sorted by order */
		$sqlstring = "select * from assessment_formfields where form_id = $id order by formfield_order + 0";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$formfield_order = $row['formfield_order'];
			$orders[] = $formfield_order;
		}
		$csv .= "subjectUID, " . implode(', ',$orders) . "\n";
		?>
		Below is the .csv template for <b><?=$title?></b><br>
		<span class="tiny"><?=$desc?></span><Br>
		
		<textarea rows="35" cols="90"><?=$csv?></textarea>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayAssessmentForm -------------- */
	/* -------------------------------------------- */
	function DisplayAssessmentForm($id) {
	
		$sqlstring = "select * from assessment_forms where form_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$title = $row['form_title'];
		$desc = $row['form_desc'];
		
	?>
		<div align="center">

		<br><br>
		<table class="formentrytable">
			<tr>
				<td class="title" colspan="3"><?=$title?></td>
			</tr>
			<tr>
				<td class="desc" colspan="3"><?=$desc?></td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
				<td style="font-size:8pt; color: darkblue">Question #</td>
				<!--<td style="font-size:8pt; color: darkblue">Question ID</td>-->
			</tr>
			<?
				/* display all other rows, sorted by order */
				$sqlstring = "select * from assessment_formfields where form_id = $id order by formfield_order + 0";
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$formfield_id = $row['formfield_id'];
					$formfield_desc = $row['formfield_desc'];
					$formfield_values = $row['formfield_values'];
					$formfield_datatype = $row['formfield_datatype'];
					$formfield_order = $row['formfield_order'];
					$formfield_scored = $row['formfield_scored'];
					$formfield_haslinebreak = $row['formfield_haslinebreak'];
					
					?>
					<tr>
						<? if ($formfield_datatype == "header") { ?>
							<td colspan="2" class="sectionheader"><?=$formfield_desc?></td>
						<? } else { ?>
							<td class="field"><?=$formfield_desc?></td>
							<td class="value">
							<?
								switch ($formfield_datatype) {
									case "binary": ?><input type="file" name="value[]"><? break;
									case "multichoice": ?>
										<select multiple name="<?=$formfield_id?>-multichoice" style="height: 150px">
											<?
												$values = explode(",", $formfield_values);
												natsort($values);
												foreach ($values as $value) {
													$value = trim($value);
												?>
													<option value="<?=$value?>"><?=$value?></option>
												<?
												}
											?>
										</select>
										<br>
										<span class="tiny">Hold <b>Ctrl</b>+click to select multiple items</span>
									<? break;
									case "singlechoice": ?>
											<?
												$values = explode(",", $formfield_values);
												//natsort($values);
												foreach ($values as $value) {
													$value = trim($value);
												?>
													<input type="radio"  name="<?=$formfield_id?>-singlechoice" value="<?=$value?>"><?=$value?>
												<?
													if ($formfield_haslinebreak) { echo "<br>"; } else { echo "&nbsp;"; }
												}
											?>
									<? break;
									case "date": ?><input type="date" name="<?=$formfield_id?>-date"><? break;
									case "number": ?><input type="number" name="<?=$formfield_id?>-number"><? break;
									case "string": ?><input type="text" name="<?=$formfield_id?>-string"><? break;
									case "text": ?><textarea name="<?=$formfield_id?>-text"></textarea><? break;
								}
							?>
						<? } ?>
						</td>
						<? if ($formfield_scored) {?>
						<td><input type="text" size="2"></td>
						<? } ?>
						<td class="order"><?=$formfield_order?></td>
						<!--<td class="order"><?=$formfield_id?></td>-->
					</tr>
					<?
				}
			?>
		</table>
		<br><br>
		
		</div>
	<?
	}

	
	/* -------------------------------------------- */
	/* ------- GetSiteList ------------------------ */
	/* -------------------------------------------- */
	function GetSiteList() {
		$sqlstring = "select * from nidb_sites";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$i=0;
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$r[$i]['id'] = $row['site_id'];
			$r[$i]['name'] = $row['site_name'];
			$i++;
		}
		return $r;
	}

	
	/* -------------------------------------------- */
	/* ------- GetProjectList --------------------- */
	/* -------------------------------------------- */
	function GetProjectList() {
		/* get list of projects the user has data-write access to */
		$sqlstring = "select * from projects a left join user_project b on a.project_id = b.project_id where b.write_data = 1 and b.user_id = (select user_id from users where username = '" . $GLOBALS['username'] . "') order by a.project_name";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$i=0;
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$r[$i]['projectid'] = $row['project_id'];
			$r[$i]['name'] = $row['project_name'];
			$r[$i]['costcenter'] = $row['project_costcenter'];
			$i++;
		}
		return $r;
	}
	
?>


<? include("footer.php") ?>
