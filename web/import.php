<?
 // ------------------------------------------------------------------------------
 // NiDB import.php
 // Copyright (C) 2004 - 2016
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
	require "includes.php";
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
		case 'importmeasures':
			ImportMeasures($siteid,$projectid,$fileformat);
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
		case 'downloads':
			DisplayDownloads();
			break;
		case 'import':
			DisplayImportMenu();
			break;
		case 'mapids':
			MapIDs($idlist, $displayonlymatches);
			break;
		default:
			DisplayMenu();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- DisplayMenu ------------------------ */
	/* -------------------------------------------- */
	function DisplayMenu() {
	
		$urllist['Home'] = "index.php";
		$urllist['Import'] = "import.php";
		NavigationBar("Import", $urllist);
		
		?>
		<ul>
		<li><a href="import.php?action=idmapper" title="Matches a list of alternate IDs to the NiDB ID">ID mapper</a>
		<li><a href="https://github.com/gbook/nidbuploader/releases" target="_blank">Download</a> the NiDB uploader <span class="tiny">via github.com</a>
		<li><a href="import.php?action=import">Import</a> data via website
		<li>View <a href="importlog.php?action=viewtransactions">import logs</a>
		</ul>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayDownloads ------------------- */
	/* -------------------------------------------- */
	function DisplayDownloads() {
	
		$urllist['Home'] = "index.php";
		$urllist['Import'] = "import.php";
		$urllist['Downloads'] = "import.php?action=downloads";
		NavigationBar("Import", $urllist);
		?>
		<b>GUI based DICOM anonymizer/uploader</b>

		<br><br>
		<span style="color:darkred; font-weight:bold">March 25, 2015 (latest)</span>
		<ul>
		<li>Windows 7 (32-bit) <a href="downloads/NiDBUploader-Win7-v20150325.zip">Download</a>
		</ul>
		<br><br>
		v56 - November 14, 2014</span>
		<ul>
		<li>Windows 7 (32-bit) <a href="downloads/NiDBUploader-Win7-v56.zip">Download</a>
		<li>CentOS 6 (64-bit) <a href="downloads/NiDBUploader-CentOS6-v56.tar.gz">Download</a>
		<li>CentOS 7 (64-bit) <a href="downloads/NiDBUploader-CentOS7-v56.tar.gz">Download</a>
		<li>Fedora 16 (64-bit) <a href="downloads/NiDBUploader-Fedora16-v56.tar.gz">Download</a>
		</ul>
		<br><br>
		v50 - November 5, 2014
		<ul>
		<li>Windows 7 (32-bit) <a href="downloads/NiDBUploader-Win7-v50.zip">Download</a>
		<li>CentOS 6 (64-bit) <a href="downloads/NiDBUploader-CentOS6-v50.tar.gz">Download</a>
		<li>CentOS 7 (64-bit) <a href="downloads/NiDBUploader-CentOS7-v50.tar.gz">Download</a>
		<li>Fedora 16 (64-bit) <a href="downloads/NiDBUploader-Fedora16-v50.tar.gz">Download</a>
		</ul>
		<br><br>
		v44 - October 7, 2014
		<ul>
		<li>Windows 7 (32-bit) <a href="downloads/NiDBUploader-Win7-v44.zip">Download</a>
		<li>CentOS 6 (64-bit) <a href="downloads/NiDBUploader-CentOS6-v44.tar.gz">Download</a>
		<li>CentOS 7 (64-bit) <a href="downloads/NiDBUploader-CentOS7-v44.tar.gz">Download</a>
		<li>Fedora 16 (64-bit) <a href="downloads/NiDBUploader-Fedora16-v44.tar.gz">Download</a>
		</ul>
		<br><br>
		v38 - October 2, 2014
		<ul>
		<li>Windows 7 (32-bit) <a href="downloads/NiDBUploader-Win7-v38.zip">Download</a>
		<li>CentOS 6 (64-bit) <a href="downloads/NiDBUploader-CentOS6-v38.tar.gz">Download</a>
		<li>CentOS 7 (64-bit) <a href="downloads/NiDBUploader-CentOS7-v38.tar.gz">Download</a>
		<li>Fedora 16 (64-bit) <a href="downloads/NiDBUploader-Fedora16-v38.tar.gz">Download</a>
		</ul>
		<br><br>
		v35 - September 22, 2014
		<ul>
		<li>Windows 7 (32-bit) <a href="downloads/NiDBUploader-Win7-v35.zip">Download</a>
		<li>CentOS 6 (64-bit) <a href="downloads/NiDBUploader-CentOS6-v35.tar.gz">Download</a>
		<li>CentOS 7 (64-bit) <a href="downloads/NiDBUploader-CentOS7-v35.tar.gz">Download</a>
		<li>Fedora 16 (64-bit) <a href="downloads/NiDBUploader-Fedora16-v35.tar.gz">Download</a>
		</ul>
		Older versions:
		<ul>
		<li>Windows 7 (32-bit) <a href="downloads/NiDBUploader-Win7.zip">Download</a>
		<li>CentOS 6 (64-bit) <a href="downloads/NiDBUploader-CentOS6-v17.tar.gz">Download</a> <span class="tiny">v17</span>
		<li>CentOS 7 (64-bit) <a href="downloads/NiDBUploader-CentOS7-v17.tar.gz">Download</a> <span class="tiny">v17</span>
		<li>Fedora 16 (64-bit) <a href="downloads/NiDBUploader-Fedora16-v17.tar.gz">Download</a> <span class="tiny">v17</span>
		</ul>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayIDMapperForm ---------------- */
	/* -------------------------------------------- */
	function DisplayIDMapperForm() {
	
		$urllist['Home'] = "index.php";
		$urllist['Import'] = "import.php";
		$urllist['ID mapper'] = "import.php?action=idmapper";
		NavigationBar("Import", $urllist);
		?>
		<div align="center">
		<form action="import.php" method="post">
		<input type="hidden" name="action" value="mapids">
		<table width="90%" height="90%">
			<tr>
				<td><span style="font-weight: bold; color: #444">Enter list of IDs</span><br><span class="tiny">Acceptable delimeters: space tab comma period semicolon colon</span></td>
			</tr>
			<tr>
				<td>
					<textarea cols="100" rows="25" name="idlist"></textarea>
				</td>
			</tr>
			<tr>
				<td>
				<input type="checkbox" value="1" name="displayonlymatches">Display only matches<br>
				<input type="submit" value="Map IDs"></td>
			</tr>
		</table>
		</div>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- MapIDs ----------------------------- */
	/* -------------------------------------------- */
	function MapIDs($idlist, $displayonlymatches) {
	
		$urllist['Home'] = "index.php";
		$urllist['Import'] = "import.php";
		$urllist['ID mapper'] = "import.php?action=idmapper";
		NavigationBar("Import", $urllist);

		//$idlist = mysql_real_escape_string($idlist);
		$idlist = preg_replace('~(*BSR_ANYCRLF)\R~', "\n", $idlist);
		$parts = preg_split('/[\^,;\-\'\s\t\n\f\r]+/', $idlist);
		foreach ($parts as $part) {
			$ids[] = mysql_real_escape_string(trim($part));
		}
		$ids = array_unique($ids);
		#$idlist = implode2(",", $newparts);
		
		?>
		A <span style="color: red"> red Alternate UID</span> means the foreign ID is contained in the alternate <b>Study</b> ID, not alternate <b>Subject</b> ID
		<br><br>
		<table class="graydisplaytable">
			<thead>
				<th>Foreign ID</th>
				<th>Local alternate UID</th>
				<th>Local UID</th>
			</thead>
		<?
		$numFound = 0;
		$numNotFound = 0;
		foreach ($ids as $altid) {
			if ($altid != "") {
				$sqlstring = "select * from subject_altuid a left join subjects b on a.subject_id = b.subject_id where a.altuid = '$altid' or a.altuid = sha1('$altid') or a.altuid = sha1('$altid ') or a.altuid = sha1(' $altid') or a.altuid = sha1(' $altid ') or a.altuid = sha1(upper('$altid')) or a.altuid = sha1(lower('$altid')) group by a.altuid, b.isactive";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
				if (mysqli_num_rows($result) > 0) {
					$numFound++;
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$subjectid = $row['subject_id'];
						$uid = $row['uid'];
						$altuid = $row['altuid'];
						$isactive = $row['isactive'];
						if (!$isactive) {
							$deleted = " (deleted)";
						}
						else {
							$deleted = "";
						}
						?>
						<tr>
							<td><?=$altid?> <?=$deleted?></td>
							<td><?=$altuid?></td>
							<td><a href="subjects.php?id=<?=$subjectid?>"><?=$uid?></a></td>
						</tr>
						<?
					}
				}
				else {
					$sqlstring = "select c.subject_id, c.isactive, c.uid, a.study_alternateid from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.study_alternateid = '$altid' or a.study_alternateid = sha1('$altid') or a.study_alternateid = sha1('$altid ') or a.study_alternateid = sha1(' $altid') or a.study_alternateid = sha1(' $altid ') or a.study_alternateid = sha1(upper('$altid')) or a.study_alternateid = sha1(lower('$altid')) group by a.study_alternateid, c.isactive";
					$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
					if (mysqli_num_rows($result) > 0) {
						$numFound++;
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$subjectid = $row['subject_id'];
							$uid = $row['uid'];
							$altuid = $row['study_alternateid'];
							$isactive = $row['isactive'];
							if (!$isactive) {
								$deleted = " (deleted)";
							}
							else {
								$deleted = "";
							}
							?>
							<tr>
								<td><?=$altid?> <?=$deleted?></td>
								<td style="color:red"><?=$altuid?></td>
								<td><a href="subjects.php?id=<?=$subjectid?>"><?=$uid?></a></td>
							</tr>
							<?
						}
					}
					else {
						$numNotFound++;
						if (!$displayonlymatches) {
							?>
							<tr>
								<td><?=$altid?></td>
								<td colspan="2">Not found</td>
							</tr>
							<?
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
	
		$urllist['Home'] = "index.php";
		$urllist['Import'] = "import.php";
		$urllist['Import'] = "import.php?action=import";
		NavigationBar("Import", $urllist);
		?>
		<style>
			.gradientsummary {
background: -moz-linear-gradient(left, rgba(151,187,229,1) 0%, rgba(151,187,229,0.99) 1%, rgba(125,185,232,0) 100%); /* FF3.6+ */
background: -webkit-gradient(linear, left top, right top, color-stop(0%,rgba(151,187,229,1)), color-stop(1%,rgba(151,187,229,0.99)), color-stop(100%,rgba(125,185,232,0))); /* Chrome,Safari4+ */
background: -webkit-linear-gradient(left, rgba(151,187,229,1) 0%,rgba(151,187,229,0.99) 1%,rgba(125,185,232,0) 100%); /* Chrome10+,Safari5.1+ */
background: -o-linear-gradient(left, rgba(151,187,229,1) 0%,rgba(151,187,229,0.99) 1%,rgba(125,185,232,0) 100%); /* Opera 11.10+ */
background: -ms-linear-gradient(left, rgba(151,187,229,1) 0%,rgba(151,187,229,0.99) 1%,rgba(125,185,232,0) 100%); /* IE10+ */
background: linear-gradient(to right, rgba(151,187,229,1) 0%,rgba(151,187,229,0.99) 1%,rgba(125,185,232,0) 100%); /* W3C */
filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#97bbe5', endColorstr='#007db9e8',GradientType=1 ); /* IE6-9 */				color: black;
				/*font-weight:bold*/
			}
		</style>
		
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
		
		<? if ($_SESSION['enablebeta']) { ?>
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
		<? } ?>
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
			<summary>Import Measures (name/value pairs only) <?=PrintBeta();?></summary>
			<div style="margin-left: 20px; padding:8px; border: 1px solid #ccc; border-radius:5px">
			<table width="100%">
				<tr>
					<td>
						<table class="entrytable">
							<form action="import.php" method="post" enctype="multipart/form-data">
							<input type="hidden" name="action" value="importmeasures">
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
												<input type="radio" name="fileformat" value="short" required>Short rows <img src="images/help.gif" title="One row for each measure/value pair, no header">
											</td>
											<td valign="top">
												<details><summary class="tiny">File format example</summary><pre style="font-size:10pt; border: 1px solid #aaa; border-radius:3px; padding: 5px">S1234ABC, instrument1, measure1, value<br>S1234ABC, instrument1, measure2, value<br>S1234ABC, instrument2, measure1, value<br>S1234ABC, instrument2, measure3, value</pre></details>
											</td>
										</tr>
										<tr>
											<td valign="top">
												<input type="radio" name="fileformat" value="long" required>Long rows <img src="images/help.gif" title="One row for each subject, with header">
											</td>
											<td valign="top">
												<details><summary class="tiny">File format example</summary><pre style="font-size:10pt; border: 1px solid #aaa; border-radius:3px; padding: 5px">-,        instrument1, instrument1, instrument2, instrument2, etc<br>UID,      measure1,    measure2,    measure1,    measure3,    etc<br>S1234ABC, value1,      value2,      value3,      value4,      etc<br>S5678LMN, value1,      value2,      value3,      value4,      etc<br>S9292XYZ, value1,      value2,      value3,      value4,      etc</pre></details>
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td colspan="2"><input type="submit" value="Import Measures"></td>
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
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		$uploadID = mysql_insert_id();
		
		//echo "I'm still here\n";
		$savepath = $GLOBALS['cfg']['uploadedpath'] . "/$uploadID";
		$behsavepath = $GLOBALS['cfg']['uploadedpath'] . "/$uploadID/beh";
		
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
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
	}

	
	/* -------------------------------------------- */
	/* ------- UploadNonDICOM --------------------- */
	/* -------------------------------------------- */
	function UploadNonDICOM($siteid,$projectid,$importdirs) {
	
		/* get next import ID */
		$sqlstring = "insert into import_requests (import_datatype, import_datetime, import_status, import_siteid, import_projectid) values ('dicom',now(),'created','$siteid','$projectid')";
		//echo "[[$sqlstring]]";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		$uploadID = mysql_insert_id();
		
		if (is_array($importdirs)) {
			$i = 0;
			foreach ($importdirs as $importdir) {
				$sqlstring = "insert into import_requestdirs (importrequest_id, dir_num, dir_type) values ($uploadID, $i, '$importdir')";
				//echo "[[$sqlstring]]";
				$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
				$i++;
			}
		}
		else {
			$sqlstring = "insert into import_requestdirs (importrequest_id, dir_num, dir_type) values ($uploadID, 0, '$importdirs')";
			//echo "[[$sqlstring]]";
			$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
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
		$urllist['Home'] = "index.php";
		$urllist['Import'] = "import.php";
		$urllist['Assessment Form List'] = "import.php?action=viewassessmentforms";
		NavigationBar("Import", $urllist);
	
		?>
		<table class="graydisplaytable">
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
	/* ------- ImportMeasures --------------------- */
	/* -------------------------------------------- */
	function ImportMeasures($siteid, $projectid, $fileformat) {
	
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
				if (ValidateMeasures("$savepath/$name", $fileformat)) {
					echo "<li>Measures file [$name] is valid, inserting into database...";
					InsertMeasures("$savepath/$name", $projectid, $fileformat);
				}
				else {
					echo "<li>Measures file [$name] is not valid. See errors above.";
				}
			}
			else {
				echo "<li>An error occured moving " . $_FILES['files']['tmp_name'][$i] . " to [" . $_FILES['files']['error'][$i] . "]<br>";
			}
		}
		
		echo "</ul>";
	}
	
	
	/* -------------------------------------------- */
	/* ------- ValidateMeasures ------------------- */
	/* -------------------------------------------- */
	function ValidateMeasures($f, $fileformat) {
	
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
				$uid = mysql_real_escape_string(trim($parts[0]));
				$instrument = mysql_real_escape_string(trim($parts[1]));
				$measure = mysql_real_escape_string(trim($parts[2]));
				$value = mysql_real_escape_string(trim($parts[3]));
				
				/* ----- check each column ----- */
				/* check if the UID exists in any format anywhere */
				$uid = mysql_real_escape_string(trim($uid));
				$sqlstring = "select subject_id from subjects where uid = '$uid'";
				$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
				if (mysql_num_rows($result) > 0) {
					$validid = true;
				}
				else {
					$sqlstring = "select subject_id from subject_altuid where altuid = '$uid' or altuid = sha1('$uid')";
					//PrintSQL($sqlstring);
					$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
					if (mysql_num_rows($result) > 0) {
						$validid = true;
					}
				}
				
				if (!$validid) {
					echo "Column 1 (UID) does not contain a valid UID [$uid]. Line $L<br>";
					$numErrors++;
				}
				
				/* check for blank entries in other columns */
				if ($uid == "") {
					echo "Column 1 (UID) is blank. Line $L<br>";
					$numErrors++;
				}
				if ($measure == "") {
					echo "Column 2 (Instrument name) is blank. Line $L<br>";
					$numErrors++;
				}
				if ($measure == "") {
					echo "Column 3 (Measure name) is blank. Line $L<br>";
					$numErrors++;
				}
				if ($value == "") {
					echo "Column 4 (Measure value) is blank. Line $L<br>";
					$numErrors++;
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
					$instruments = mysql_real_escape_string(array_shift($parts));
				}
				elseif ($i == 1) {
					/* get the second line, the measures */
					$measures = mysql_real_escape_string(array_shift($parts));
				}
				else {
					/* otherwise, it should be a real line... with data */
					
					/* separate out the columns */
					$col=0;
					foreach ($parts as $part) {
						$value = mysql_real_escape_string(trim($part));
						
						if ($col == 0) {
							$uid = $value;
							$subjectRowID = GetSubjectRowID($uid);
							
							//echo "SubjectRowID for UID [$uid]: [$subjectRowID]<br>";
							
							if ($subjectRowID < 1) {
								echo "Column 1 (UID) does not contain a valid UID [$uid]. Line $L<br>";
								$numErrors++;
							}
						}
						else {
							$instrument = $instruments[$col];
							$measure = $measures[$col];
							/* check for blank entries in other columns */
							if ($value == "") {
								echo "Column 4 (Measure value) is blank. Line $L<br>";
								$numErrors++;
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
					echo "<li>Measures file [$name] is valid, inserting into database...";
					InsertDemographics("$savepath/$name");
				}
				else {
					echo "<li>Measures file [$name] is not valid. See errors above.";
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
			$uid = mysql_real_escape_string(trim($parts[0]));
			$dob = mysql_real_escape_string(trim($parts[1]));
			$sex = mysql_real_escape_string(trim($parts[2]));
			$ethnicity = mysql_real_escape_string(trim($parts[3]));
			$race = mysql_real_escape_string(trim($parts[4]));
			$handedness = mysql_real_escape_string(trim($parts[5]));
			$education = mysql_real_escape_string(trim($parts[6]));
			$marital = mysql_real_escape_string(trim($parts[7]));
			$smoking = mysql_real_escape_string(trim($parts[8]));
			
			/* ----- check each column ----- */
			/* check if the UID exists in any format anywhere */
			$uid = mysql_real_escape_string(trim($uid));
			$sqlstring = "select subject_id from subjects where uid = '$uid'";
			$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
			if (mysql_num_rows($result) > 0) {
				$validid = true;
			}
			else {
				$sqlstring = "select subject_id from subject_altuid where altuid = '$uid' or altuid = sha1('$uid') or altuid = sha1(upper('$uid')) or altuid = sha1(lower('$uid'))";
				//PrintSQL($sqlstring);
				$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
				if (mysql_num_rows($result) > 0) {
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
			$uid = mysql_real_escape_string(trim($parts[0]));
			$dob = mysql_real_escape_string(trim($parts[1]));
			$sex = mysql_real_escape_string(trim($parts[2]));
			$ethnicity = mysql_real_escape_string(trim($parts[3]));
			$race = mysql_real_escape_string(trim($parts[4]));
			$handedness = mysql_real_escape_string(trim($parts[5]));
			$education = mysql_real_escape_string(trim($parts[6]));
			$marital = mysql_real_escape_string(trim($parts[7]));
			$smoking = mysql_real_escape_string(trim($parts[8]));
			
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
				$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
				//echo "[" . mysql_affected_rows() . "]<br>";
				$numupdated += mysql_affected_rows();
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
			$uid = mysql_real_escape_string(trim($parts[0]));
			$scandate = mysql_real_escape_string(trim($parts[1]));
			$age = mysql_real_escape_string(trim($parts[2]));
			
			/* ----- check each column ----- */
			/* check if the UID exists in any format anywhere */
			$uid = mysql_real_escape_string(trim($uid));
			$sqlstring = "select subject_id from subjects where uid = '$uid'";
			$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
			if (mysql_num_rows($result) > 0) {
				$validid = true;
			}
			else {
				$sqlstring = "select subject_id from subject_altuid where altuid = '$uid' or altuid = sha1('$uid') or altuid = sha1(upper('$uid')) or altuid = sha1(lower('$uid'))";
				//PrintSQL($sqlstring);
				$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
				if (mysql_num_rows($result) > 0) {
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
			$uid = mysql_real_escape_string(trim($parts[0]));
			$scandate = mysql_real_escape_string(trim($parts[1]));
			$age = mysql_real_escape_string(trim($parts[2]));
			
			/* ----- check each column ----- */
			/* get subjectID */
			$subjectRowID = GetSubjectRowID($uid);
			
			if ($subjectRowID != "") {
				$thedate = date_parse($scandate);
				$scandate = $thedate['year'] . "-" . $thedate['month'] . "-" . $thedate['day'];
				$sqlstring = "update studies set study_ageatscan = '$age' where date(study_datetime) = '$scandate' and enrollment_id in (select enrollment_id from enrollment where subject_id = $subjectRowID)";
			
				PrintSQL($sqlstring);
				$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
				echo "[" . mysql_affected_rows() . "]<br>";
				$numupdated += mysql_affected_rows();
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
		$uid = mysql_real_escape_string(trim($uid));
		$sqlstring = "select subject_id from subjects where uid = '$uid'";
		//PrintSQL($sqlstring);
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		if (mysql_num_rows($result) > 0) {
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
			$subjectRowID = $row['subject_id'];
		}
		else {
			$sqlstring = "select subject_id from subject_altuid where altuid = '$uid' or altuid = sha1('$uid')";
			//PrintSQL($sqlstring);
			$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
			if (mysql_num_rows($result) > 0) {
				$row = mysql_fetch_array($result, MYSQL_ASSOC);
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
		$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
		if (mysql_num_rows($result) > 0) {
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
			$enrollmentRowID = $row['enrollment_id'];
		}
		else {
			$sqlstring = "insert into enrollment (project_id, subject_id, enroll_startdate) values ($projectRowID, $subjectRowID, now())";
			//PrintSQL($sqlstring);
			$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
			$enrollmentRowID = mysql_insert_id();
		}
		
		return $enrollmentRowID;
	}

	
	/* -------------------------------------------- */
	/* ------- InsertInstrumentName --------------- */
	/* -------------------------------------------- */
	function InsertInstrumentName($instrument) {
		$sqlstring = "select measureinstrument_id from measureinstruments where instrument_name = '$instrument'";
		//PrintSQL($sqlstring);
		$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
		if (mysql_num_rows($result) > 0) {
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
			$measureinstrumentnameid = $row['measureinstrument_id'];
		}
		else {
			$sqlstring = "insert into measureinstruments (instrument_name) values ('$instrument')";
			//PrintSQL($sqlstring);
			$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
			$measureinstrumentnameid = mysql_insert_id();
		}
		return $measureinstrumentnameid;
	}

	
	/* -------------------------------------------- */
	/* ------- InsertMeasureName ------------------ */
	/* -------------------------------------------- */
	function InsertMeasureName($measure) {
		$sqlstring = "select measurename_id from measurenames where measure_name = '$measure'";
		//PrintSQL($sqlstring);
		$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
		if (mysql_num_rows($result) > 0) {
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
			$measurenameid = $row['measurename_id'];
		}
		else {
			$sqlstring = "insert into measurenames (measure_name) values ('$measure')";
			//PrintSQL($sqlstring);
			$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
			$measurenameid = mysql_insert_id();
		}
		return $measurenameid;
	}


	/* -------------------------------------------- */
	/* ------- InsertMeasures --------------------- */
	/* -------------------------------------------- */
	function InsertMeasures($f, $projectRowID, $fileformat) {
	
		/* open the file and check some fields */
		$lines = file($f);

		$c=0;
		/* check if its the short format */
		if ($fileformat == "short") {
			for ($i=0;$i<count($lines);$i++) {
				$line = $lines[$i];
				$parts = str_getcsv($line);
				
				/* separate out the columns */
				$uid = mysql_real_escape_string(trim($parts[0]));
				$instrument = mysql_real_escape_string(trim($parts[1]));
				$measure = mysql_real_escape_string(trim($parts[2]));
				$value = mysql_real_escape_string(trim($parts[3]));
				
				/* ----- check each column ----- */
				/* get subjectID */
				$subjectRowID = GetSubjectRowID($uid);
				
				/* check if this enrollment exists, and if not, create it */
				$enrollmentRowID = EnrollSubject($subjectRowID, $projectRowID);
				
				$instrumentnameid = InsertInstrumentName($instrument);
				$measurenameid = InsertMeasureName($measure);
				
				$sqlstring = "insert ignore into measures (enrollment_id, measure_dateentered, measure_dateentered2, instrumentname_id, measurename_id, measure_type, measure_valuestring, measure_valuenum, measure_rater, measure_rater2, measure_isdoubleentered, measure_datecomplete) values ($enrollmentRowID, now(), now(), '$instrumentnameid', '$measurenameid', '$type', '$valuestring','$valuenum', 'Imported', 'Imported', 1, now())";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
				
				$c++;
			}
		}
		/* otherwise its the long format */
		else {
			for ($i=0;$i<count($lines);$i++) {
				$line = $lines[$i];
				$parts = str_getcsv($line);
				
				//echo "<pre> PARTS:";
				//print_r($parts);
				//echo "</pre>";
				if ($i == 0) {
					/* get the first line, the instruments */
					$instruments = $parts;
					//array_shift($instruments);
					//echo "<pre> instruments:";
					//print_r($instruments);
					//echo "</pre>";
				}
				elseif ($i == 1) {
					/* get the second line, the measures */
					$measures = $parts;
					//array_shift($measures);
					//echo "<pre> measures:";
					//print_r($measures);
					//echo "</pre>";
				}
				else {
					/* otherwise, it should be a real line... with data */
					//echo "This is real data!";
					
					/* separate out the columns */
					$col=0;
					foreach ($parts as $part) {
						$value = mysql_real_escape_string(trim($part));
						
						//echo "Working on column $col<br>";
						if ($col == 0) {
							$uid = $value;
							/* get subjectID */
							$subjectRowID = GetSubjectRowID($uid);
							
							/* check if this enrollment exists, and if not, create it */
							$enrollmentRowID = EnrollSubject($subjectRowID, $projectRowID);
						}
						else {
							$instrument = $instruments[$col];
							$measure = $measures[$col];
							
							/* create the measures SQL string */
							if (is_numeric($value)) {
								$type = 'n';
								$valuestring = '';
								$valuenum = $value;
							}
							else {
								$type = 's';
								$valuestring = $value;
								$valuenum = '';
							}
							
							$instrumentnameid = InsertInstrumentName($instrument);
							$measurenameid = InsertMeasureName($measure);
							
							$sqlstring = "insert ignore into measures (enrollment_id, measure_dateentered, measure_dateentered2, instrumentname_id, measurename_id, measure_type, measure_valuestring, measure_valuenum, measure_rater, measure_rater2, measure_isdoubleentered, measure_datecomplete) values ($enrollmentRowID, now(), now(), '$instrumentnameid', '$measurenameid', '$type', '$valuestring','$valuenum', 'Imported', 'Imported', 1, now())";
							//PrintSQL($sqlstring);
							$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
							$c++;
						}
						$col++;
					}
				}
			}
		}
		?>
		<li><span style="color: darkblue">Inserted <?=$c?> measure values</span>
		<?
	}
	
	
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
		$formtitle = mysql_real_escape_string(trim($parts[0]));
		$parts = str_getcsv($lines[1]);
		$formdesc = mysql_real_escape_string(trim($parts[0]));

		$sqlstring = "insert into assessment_forms (form_title, form_desc, form_creator, form_createdate) values ('$formtitle','$formdesc','" . $GLOBALS['username'] . "',now())";
		PrintSQL($sqlstring);
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		$assessmentID = mysql_insert_id();
		
		for ($i=2;$i<=count($lines);$i++) {
			$line = $lines[$i];
			$parts = str_getcsv($line);
			$c = count($parts);
			
			/* separate out the columns */
			$qnum = mysql_real_escape_string(trim($parts[0]));
			$question = mysql_real_escape_string(trim($parts[1]));
			$type = mysql_real_escape_string(trim($parts[2]));
			if ($c > 3) {
				$values = mysql_real_escape_string(trim($parts[3]));
				if ($c > 4) {
					$comment = mysql_real_escape_string(trim($parts[4]));
				}
			}
			
			$sqlstring = "insert into assessment_formfields (form_id, formfield_desc, formfield_values, formfield_datatype, formfield_order) values ($assessmentID,'$question','$values','$type','$qnum')";
			PrintSQL($sqlstring);
			$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		}
		
		$sqlstring = "update assessment_forms set form_ispublished = 1 where form_id = $assessmentID";
		PrintSQL($sqlstring);
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
	}


	/* -------------------------------------------- */
	/* ------- DisplayAssessmentFormTemplate ------ */
	/* -------------------------------------------- */
	function DisplayAssessmentFormTemplate($id) {
		$sqlstring = "select * from assessment_forms where form_id = $id";
		$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$title = $row['form_title'];
		$desc = $row['form_desc'];
		
		$csv = "$id\n";
		/* display all other rows, sorted by order */
		$sqlstring = "select * from assessment_formfields where form_id = $id order by formfield_order + 0";
		$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
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
		$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$title = $row['form_title'];
		$desc = $row['form_desc'];
		
		$urllist['Administration'] = "admin.php";
		$urllist['Assessment Forms'] = "adminassessmentforms.php";
		$urllist[$title] = "adminassessmentforms.php?action=editform&id=$id";
		NavigationBar("Admin", $urllist);
		
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
				$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
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
