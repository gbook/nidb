<?
 // ------------------------------------------------------------------------------
 // NiDB packages.php
 // Copyright (C) 2004 - 2023
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
		<title>NiDB - Packages</title>
	</head>
<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	PrintVariable($_POST);
	PrintVariable($_GET);

	/* check if this page is being called from itself */
	$referringpage = $_SERVER['HTTP_REFERER'];
	$phpfilename = pathinfo(__FILE__)['basename'];
	if (contains($referringpage, $phpfilename))
		$selfcall = true;
	else
		$selfcall = false;
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$packageid = GetVariable("packageid");
	$packagename = GetVariable("packagename");
	$packagedesc = GetVariable("packagedesc");
	$packageformat = GetVariable("packageformat");
	$subjectdirformat = GetVariable("subjectdirformat");
	$studydirformat = GetVariable("studydirformat");
	$seriesdirformat = GetVariable("seriesdirformat");
	$readme = GetVariable("readme");
	$notes = GetVariable("notes");
	$license = GetVariable("license");
	$changes = GetVariable("changes");

	$objecttype = GetVariable("objecttype");
	$objectids = GetVariable("objectids");
	$modality = GetVariable("modality");
	$enrollmentids = GetVariable("enrollmentids");
	$subjectids = GetVariable("subjectids");
	$studyids = GetVariable("studyids");
	$seriesids = GetVariable("seriesids");
	$experimentids = GetVariable("experimentids");
	$analysisids = GetVariable("analysisids");
	$pipelineids = GetVariable("pipelineids");
	$datadictionaryids = GetVariable("datadictionaryids");
	$drugids = GetVariable("drugids");
	$measureids = GetVariable("measureids");
	$includedrugs = GetVariable("includedrugs");
	$includemeasures = GetVariable("includemeasures");
	$includeexperiments = GetVariable("includeexperiments");
	$includeanalysis = GetVariable("includeanalysis");
	$includepipelines = GetVariable("includepipelines");

	if (count($seriesids) > 0)
		$objectids = $seriesids;
	
	/* determine action */
	if ($selfcall) {
		if ($action == "editform")  {
			DisplayPackageForm($packageid, "edit");
		}
		elseif ($action == "addform") {
			DisplayPackageForm($packageid, "add");
		}
		elseif ($action == "updatepackage") {
			UpdatePackage($packageid,$packagename,$packagedesc,$packageformat,$subjectdirformat,$studydirformat,$seriesdirformat,$readme,$notes,$license,$changes);
			DisplayPackageList();
		}
		elseif ($action == "addpackage") {
			AddPackage($packagename,$packagedesc,$packageformat,$subjectdirformat,$studydirformat,$seriesdirformat,$readme,$notes,$license,$changes);
			DisplayPackageList();
		}
		elseif ($action == "displaypackage") {
			DisplayPackage($packageid);
		}
		elseif ($action == "addobjectstopackage") {
			AddObjectsToPackage($packageid, $enrollmentids, $subjectids, $studyids, $seriesids, $modality, $experimentids, $analysisids, $pipelineids, $datadictionaryids, $drugids, $measureids, $includedrugs, $includemeasures, $includeexperiments, $includeanalysis, $includepipelines);
			DisplayPackage($packageid);
		}
		elseif ($action == "removeobject") {
			echo "Calling RemoveObject()";
			RemoveObject($packageid, $objecttype, $objectids);
			DisplayPackage($packageid);
		}
		else {
			DisplayPackageList();
		}
	}
	else {
		if ($action == "addobject") {
			AddObjectForm($objecttype, $objectids, $modality);
		}
		else {
			DisplayPackageList();
		}
	}
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- AddObjectForm ---------------------- */
	/* -------------------------------------------- */
	function AddObjectForm($objecttype, $objectids, $modality) {

		/* perform data checks */
		$objecttype = mysqli_real_escape_string($GLOBALS['linki'], $objecttype);
		$objectids = mysqli_real_escape_array($GLOBALS['linki'], $objectids);
		$modality = mysqli_real_escape_string($GLOBALS['linki'], $modality);

		switch ($objecttype) {
			case "enrollment":
				DisplayAddEnrollmentForm($objectids);
				break;
			case "subject":
				DisplayAddSubjectForm($objectids);
				break;
			case "study":
				DisplayAddStudyForm($objectids);
				break;
			case "series":
				DisplayAddSeriesForm($objectids, $modality);
				break;
			case "experiment":
				DisplayAddExperimentForm($objectids);
				break;
			case "pipeline":
				DisplayAddPipelineForm($objectids);
				break;
			case "analysis":
				DisplayAddAnalysisForm($objectids);
				break;
			case "datadictionary":
				DisplayAddDataDictionaryForm($objectids);
				break;
		}
	}

	/* -------------------------------------------- */
	/* ------- DisplayAddEnrollmentForm ----------- */
	/* -------------------------------------------- */
	function DisplayAddEnrollmentForm($enrollmentids) {
		
		if (count($enrollmentids) < 1) {
			Error("0 subjectids passed into function");
			return;
		}
		$uids = array();
		$enrollmentidstr = implode2(",", $enrollmentids);
		
		/* get all series from this enrollment */
		$sqlstring = "select * from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.enrollment_id in (" . $enrollmentidstr . ")";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$numstudies = mysqli_num_rows($result);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$enrollmentids[] = $row['enrollment_id'];
			$subjectids[] = $row['subject_id'];
			$studyids[] = $row['study_id'];
			$studyid = $row['study_id'];
			$projectids[] = $row['project_id'];
			$projectid = $row['project_id'];
			
			$modality = strtolower($row['study_modality']);
			
			$sqlstringA = "select * from $modality" . "_series where study_id = $studyid";
			$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
			while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
				$seriesids[$modality][] = $rowA[$modality . 'series_id'];

				if (trim($rowA['series_desc']) == "")
					$seriesdesc = $rowA['series_protocol'];
				else
					$seriesdesc = $rowA['series_desc'];
				
				$experimentmapping[$modality][$seriesdesc]['projectid'] = $projectid; /* don't make this array unique because multiple mappings could exist for each protocol */
			}
		}
		//PrintVariable($seriesids);
		$enrollmentids = array_unique($enrollmentids);
		$subjectids = array_unique($subjectids);
		$studyids = array_unique($studyids);
		//$seriesids = array_unique($seriesids);
		$projectids = array_unique($projectids);
		$seriesdescs = array_unique($seriesdesc);
		
		$numenrollments = count($enrollmentids);
		$numsubjects = count($subjectids);
		$numstudies = count($studyids);
		$numseries = count($seriesids, COUNT_RECURSIVE);
		
		/* get list of analysisids */
		$studyidstr = implode2(",", $studyids);
		$sqlstring = "select * from analysis where study_id in (" . $studyidstr . ") and analysis_status in ('complete', 'error','rerunresults')";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$numseries = mysqli_num_rows($result);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$analysisids[] = $row['analysis_id'];
			$pipelineids[] = $row['pipeline_id'];
		}
		$analysisids = array_unique($analysisids);
		$pipelineids = array_unique($pipelineids);
		
		/* get list of experiments - need to map the experiment to the protocol/modality and project */
		foreach ($experimentmapping as $modalitykey => $modalityvalue) {
			foreach ($modalityvalue as $seriesdesc => $value) {
				$projectid = $value['projectid'];
				
				$sqlstring = "select experiment_id from experiment_mapping where project_id = $projectid and protocolname = '$seriesdesc' and modality = '$modalitykey'";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				$numseries = mysqli_num_rows($result);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$experimentids[] = $row['experiment_id'];
				}
			}
		}
		$experimentids = array_unique($experimentids);
		
		?>
		
		<div class="ui container">
			<div class="ui raised segment">
				
				<form method="post" action="packages.php">
					<input type="hidden" name="action" value="addobjectstopackage">
					
					<h2>The following objects will be added to the package</h2>
					<? DisplayFormSubjects($enrollmentids, true); ?>
					
					<h2>Optional related objects</h3>
					<br>
					<? DisplayFormSeries($seriesids, false, "Associated studies will be automatically added"); ?>
					<br>
					<? DisplayFormExperiments($experimentids, false); ?>
					<br>
					<? DisplayFormAnalyses($analysisids, false); ?>
					<br>
					<? DisplayFormPipelines($pipelineids, false); ?>
					<br>
					<? DisplayFormDrugs($enrollmentids, false); ?>
					<br>
					<? DisplayFormMeasures($enrollmentids, false); ?>
				
					<br><br>
					<h4 class="ui horizontal divider header">Select Package</h4>
					<div style="text-align: center">
						<? DisplayFormSelectPackage(); ?>
						<br><br>
						<input type="submit" value="Add to package" class="ui primary button">
					</div>
				</form>
			</div>
		</div>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayAddStudyForm ---------------- */
	/* -------------------------------------------- */
	function DisplayAddStudyForm($studyids) {
		
		if (count($studyids) < 1) {
			Error("No studyids passed into function");
			return;
		}
		$uids = array();
		$studyidstr = implode2(",", $studyids);
		
		/* get all series from this study */
		$sqlstring = "select * from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.study_id in (" . $studyidstr . ")";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$numstudies = mysqli_num_rows($result);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$enrollmentids[] = $row['enrollment_id'];
			$subjectids[] = $row['subject_id'];
			$studyids[] = $row['study_id'];
			$studyid = $row['study_id'];
			$projectids[] = $row['project_id'];
			$projectid = $row['project_id'];
			
			$modality = strtolower($row['study_modality']);
			
			$sqlstringA = "select * from $modality" . "_series where study_id = $studyid";
			$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
			while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
				$seriesids[$modality][] = $rowA[$modality . 'series_id'];

				if (trim($rowA['series_desc']) == "")
					$seriesdesc = $rowA['series_protocol'];
				else
					$seriesdesc = $rowA['series_desc'];
				
				$experimentmapping[$modality][$seriesdesc]['projectid'] = $projectid; /* don't make this array unique because multiple mappings could exist for each protocol */
			}
		}
		//PrintVariable($seriesids);
		$enrollmentids = array_unique($enrollmentids);
		$subjectids = array_unique($subjectids);
		$studyids = array_unique($studyids);
		//$seriesids = array_unique($seriesids);
		$projectids = array_unique($projectids);
		$seriesdescs = array_unique($seriesdesc);
		
		$numenrollments = count($enrollmentids);
		$numsubjects = count($subjectids);
		$numstudies = count($studyids);
		$numseries = count($seriesids, COUNT_RECURSIVE);
		
		/* get list of analysisids */
		$studyidstr = implode2(",", $studyids);
		$sqlstring = "select * from analysis where study_id in (" . $studyidstr . ") and analysis_status in ('complete', 'error','rerunresults')";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$numseries = mysqli_num_rows($result);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$analysisids[] = $row['analysis_id'];
			$pipelineids[] = $row['pipeline_id'];
		}
		$analysisids = array_unique($analysisids);
		$pipelineids = array_unique($pipelineids);
		
		/* get list of experiments - need to map the experiment to the protocol/modality and project */
		foreach ($experimentmapping as $modalitykey => $modalityvalue) {
			foreach ($modalityvalue as $seriesdesc => $value) {
				$projectid = $value['projectid'];
				
				$sqlstring = "select experiment_id from experiment_mapping where project_id = $projectid and protocolname = '$seriesdesc' and modality = '$modalitykey'";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				$numseries = mysqli_num_rows($result);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$experimentids[] = $row['experiment_id'];
				}
			}
		}
		$experimentids = array_unique($experimentids);
		
		?>
		
		<div class="ui container">
			<div class="ui raised segment">
				
				<form method="post" action="packages.php">
					<input type="hidden" name="action" value="addobjectstopackage">
					
					<h2>The following objects will be added to the package</h2>
					<? DisplayFormSubjects($enrollmentids, true); ?>
					<br>
					<? DisplayFormStudies($studyids, true); ?>
					<br>
					<? DisplayFormSeries($seriesids, true); ?>
					
					<h2>Optional related objects</h3>
					<? DisplayFormExperiments($experimentids, false); ?>
					<br>
					<? DisplayFormAnalyses($analysisids, false); ?>
					<br>
					<? DisplayFormPipelines($pipelineids, false); ?>
					<br>
					<? DisplayFormDrugs($enrollmentids, false); ?>
					<br>
					<? DisplayFormMeasures($enrollmentids, false); ?>
				
					<br><br>
					<h4 class="ui horizontal divider header">Select Package</h4>
					<div style="text-align: center">
						<? DisplayFormSelectPackage(); ?>
						<br><br>
						<input type="submit" value="Add to package" class="ui primary button">
					</div>
				</form>
			</div>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayAddSeriesForm --------------- */
	/* -------------------------------------------- */
	function DisplayAddSeriesForm($seriesids, $modality) {
		
		if (count($seriesids < 1)) {
			Error("0 seriesids passed into function");
			return;
		}
		$uids = array();
		$seriesidstr = implode2(",", $seriesids);
		
		/* get subject info. there may be series from multiple subjects in this list */
		$sqlstring = "select * from $modality" . "_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.$modality" . "series_id in (" . $seriesidstr . ")";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$numseries = mysqli_num_rows($result);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$enrollmentids[] = $row['enrollment_id'];
			$subjectids[] = $row['subject_id'];
			$studyids[] = $row['study_id'];
			$projectids[] = $row['project_id'];
			if ($row[$modality . '_seriesid'] != "")
				$seriesids2[$modality][] = $row[$modality . '_seriesid'];

			if (trim($row['series_desc']) == "")
				$seriesdesc = $row['series_protocol'];
			else
				$seriesdesc = $row['series_desc'];
			
			$experimentmapping[$modality][$seriesdesc]['projectid'] = $row['project_id']; /* don't make this array unique because multiple mappings could exist for each protocol */
		}
		$enrollmentids = array_unique($enrollmentids);
		$subjectids = array_unique($subjectids);
		$studyids = array_unique($studyids);
		//$seriesids = array_unique($seriesids);
		$projectids = array_unique($projectids);
		$seriesdescs = array_unique($seriesdesc);
		
		$numenrollments = count($enrollmentids);
		$numsubjects = count($subjectids);
		$numstudies = count($studyids);
		$numseries = count($seriesids2, COUNT_RECURSIVE);
		
		/* get list of analysisids */
		$studyidstr = implode2(",", $studyids);
		$sqlstring = "select * from analysis where study_id in (" . $studyidstr . ") and analysis_status in ('complete', 'error','rerunresults')";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$numseries = mysqli_num_rows($result);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$analysisids[] = $row['analysis_id'];
			$pipelineids[] = $row['pipeline_id'];
		}
		$analysisids = array_unique($analysisids);
		$pipelineids = array_unique($pipelineids);
		
		/* get list of experiments - need to map the experiment to the protocol/modality and project */
		foreach ($experimentmapping as $modalitykey => $modalityvalue) {
			foreach ($modalityvalue as $seriesdesc => $value) {
				$projectid = $value['projectid'];
				
				$sqlstring = "select experiment_id from experiment_mapping where project_id = $projectid and protocolname = '$seriesdesc' and modality = '$modalitykey'";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				$numseries = mysqli_num_rows($result);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$experimentids[] = $row['experiment_id'];
				}
			}
		}
		$experimentids = array_unique($experimentids);
		
		?>
		
		<div class="ui container">
			<div class="ui raised segment">
				
				<form method="post" action="packages.php">
					<input type="hidden" name="action" value="addobjectstopackage">
					
					<h2>The following objects will be added to the package</h2>
					<? DisplayFormSubjects($enrollmentids, true); ?>
					<br>
					<? DisplayFormStudies($studyids, true); ?>
					<br>
					<? DisplayFormSeries($seriesids, true); ?>
					
					<h2>Optional related objects</h3>
					<? DisplayFormExperiments($experimentids, false); ?>
					<br>
					<? DisplayFormAnalyses($analysisids, false); ?>
					<br>
					<? DisplayFormPipelines($pipelineids, false); ?>
					<br>
					<? DisplayFormDrugs($enrollmentids, false); ?>
					<br>
					<? DisplayFormMeasures($enrollmentids, false); ?>
				
					<br><br>
					<h4 class="ui horizontal divider header">Select Package</h4>
					<div style="text-align: center">
						<? DisplayFormSelectPackage(); ?>
						<br><br>
						<input type="submit" value="Add to package" class="ui primary button">
					</div>
				</form>
			</div>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayAddSubjectForm -------------- */
	/* -------------------------------------------- */
	function DisplayAddSubjectForm($subjectids) {
		?>
		The following information related to the subject(s) will be added to the package
		
		<pre>
			[x] subject info
			
			[ ] study info
				[ ] series list
					[ ] experiments
					[ ] analyses
					[ ] pipelines
		</pre>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayAddExperimentForm ----------- */
	/* -------------------------------------------- */
	function DisplayAddExperimentForm($experimentids) {
		?>
		<div class="ui container">
			<div class="ui raised segment">
				
				<form method="post" action="packages.php">
					<input type="hidden" name="action" value="addobjectstopackage">
					
					<h2>The following objects will be added to the package</h2>
					<? DisplayFormExperiments($experimentids, true); ?>
				
					<br><br>
					<h4 class="ui horizontal divider header">Select Package</h4>
					<div style="text-align: center">
						<? DisplayFormSelectPackage(); ?>
						<br><br>
						<input type="submit" value="Add to package" class="ui primary button">
					</div>
				</form>
			</div>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayAddPipelineForm ------------- */
	/* -------------------------------------------- */
	function DisplayAddPipelineForm($pipelineids) {
		?>
		<div class="ui container">
			<div class="ui raised segment">
				
				<form method="post" action="packages.php">
					<input type="hidden" name="action" value="addobjectstopackage">
					
					<h2>The following objects will be added to the package</h2>
					<? DisplayFormPipelines($pipelineids, true); ?>
				
					<br><br>
					<h4 class="ui horizontal divider header">Select Package</h4>
					<div style="text-align: center">
						<? DisplayFormSelectPackage(); ?>
						<br><br>
						<input type="submit" value="Add to package" class="ui primary button">
					</div>
				</form>
			</div>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayAddAnalysisForm ------------- */
	/* -------------------------------------------- */
	function DisplayAddAnalysisForm($analysisids) {
		?>
		<div class="ui container">
			<div class="ui raised segment">
				
				<form method="post" action="packages.php">
					<input type="hidden" name="action" value="addobjectstopackage">
					
					<h2>The following objects will be added to the package</h2>
					<? DisplayFormAnalyses($analysisids, true); ?>
				
					<br><br>
					<h4 class="ui horizontal divider header">Select Package</h4>
					<div style="text-align: center">
						<? DisplayFormSelectPackage(); ?>
						<br><br>
						<input type="submit" value="Add to package" class="ui primary button">
					</div>
				</form>
			</div>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayAddDataDictionaryForm ------- */
	/* -------------------------------------------- */
	function DisplayAddDataDictionaryForm($datadictids) {
		?>
		The following information related to the data dictionary(s) will be added to the package
		
		<pre>
			[*] data dictionary
		</pre>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayFormSelectPackage ----------- */
	/* -------------------------------------------- */
	function DisplayFormSelectPackage() {
		?>
		<select class="ui selection dropdown" name="packageid" required>
			<option value="">Select package...</option>
		<?				
			$sqlstring = "select * from packages where user_id = " . $_SESSION['userid'];
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$packageid = $row['package_id'];
				$name = $row['package_name'];
				$desc = $row['package_desc'];
				$createdate = date('M j, Y h:ia',strtotime($row['package_date']));
				?>
				<option value="<?=$packageid?>"><?=$name?></option>
				<?
			}
		?>
		</select>
		<?
	}


	/* -------------------------------------------------------------------------------------
	    The following functions display a list of objects from the list of input IDs
		Functions display HTML that contains <input> elements to store variables, but
		do not contain any <form> or </form> elements
	   ------------------------------------------------------------------------------------- */

	/* -------------------------------------------- */
	/* ------- DisplayFormSubjects ---------------- */
	/* -------------------------------------------- */
	/* this function expects a list of enrollment IDs */
	function DisplayFormSubjects($enrollmentids, $required) {
		
		$numsubjects = count($enrollmentids);
		
		if ($required) {
			$checkboxstr = " checked onClick='return false' onKeyDown='return false' ";
			$checkboxreadonly = "read-only";
			$checkboxstate = "checked";
			$labelstr = "will be added";
			$numselected = $numsubjects;
		}
		else {
			$labelstr = "selected";
			$numselected = 0;
		}
		
		if (count($enrollmentids) > 0) {
			?>
			<script type="text/javascript">
				$(function() {
					$("#selectallsubjects").click(function() {
						var checked_status = this.checked;
						$(".allsubjects").find("input[type='checkbox']").each(function() {
							this.checked = checked_status;
						});
						if (this.checked)
							document.getElementById('includesubjects').checked = true;
					});
				});
				
				function SelectAllSubjects() {
					var checked_status = document.getElementById('includesubjects').checked;
					$(".allsubjects").find("input[type='checkbox']").each(function() {
						this.checked = checked_status;
					});
					CheckSelectedSubjectCount();
				}

				function CheckSelectedSubjectCount(e) {
					var n = document.querySelectorAll('input[type="checkbox"].subjectcheck:checked').length;
					document.getElementById('numsubjectsselected').innerHTML = n;

					if (e.checked)
						document.getElementById('includesubjects').checked = true;
				}
			</script>

			<div class="ui grid">
				<div class="ui four wide column">
					<div class="ui toggle <?=$checkboxreadonly?> checkbox" onChange="SelectAllSubjects()">
						<input type="checkbox" name="includesubjects" id="includesubjects" value="1" <?=$checkboxstate?>>
						<label style="font-size:larger; font-weight: bold">Subjects</label>
					</div>
				</div>
				<div class="ui ten wide column">
					<div class="ui left pointing red label"><span id="numsubjectsselected"><?=$numselected?></span> of <?=$numsubjects?> subjects <?=$labelstr?></div>
				</div>
			</div>

			<div class="ui accordion">
				<div class="title">
					<i class="dropdown icon"></i>
					View subjects
				</div>
				<div class="content">
					<table class="ui very compact table">
						<thead>
							<th><input type="checkbox" id="selectallsubjects"></th>
							<th>UID</th>
							<th>Sex</th>
							<th>Enrolled project</th>
						</thead>
						<tbody>
						<?
							$enrollmentidstr = implode2(",", $enrollmentids);
							
							/* get subject info - there may be series from multiple subjects in this list */
							$sqlstring = "select * from enrollment a left join subjects b on a.subject_id = b.subject_id left join projects c on a.project_id = c.project_id where a.enrollment_id in (" . $enrollmentidstr . ")";
							$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$enrollmentid = $row['enrollment_id'];
								$uid = $row['uid'];
								$subjectid = $row['subject_id'];
								$sex = $row['sex'];
								$projectname = $row['project_name'];
								
								?>
									<tr>
										<td class="allsubjects"><input type="checkbox" name="enrollmentids[]" value="<?=$enrollmentid?>" <?=$checkboxstr?> class="subjectcheck" onClick="CheckSelectedSubjectCount(this);"></td>
										<td><a href="subjects.php?id=<?=$subjectid?>"><?=$uid?></a></td>
										<td><?=$sex?></td>
										<td><?=$projectname?></td>
									</tr>
								<?
							}
						?>
						</tbody>
					</table>
				</div>
			</div>
			<?
		}
		else {
			?>
			<div class="ui toggle read-only checkbox">
				<input type="checkbox" name="includesubjects" value="0">
				<label style="font-size:larger; font-weight: bold">No subjects found</label>
			</div>
			<br>
			<?
		}
	}


	/* -------------------------------------------- */
	/* ------- DisplayFormStudies ----------------- */
	/* -------------------------------------------- */
	function DisplayFormStudies($studyids, $required) {
		
		$numstudies = count($studyids);
		
		if ($required) {
			$checkboxstr = " checked onClick='return false' onKeyDown='return false' ";
			$checkboxreadonly = "read-only";
			$checkboxstate = "checked";
			$labelstr = "will be added";
			$numselected = $numstudies;
		}
		else {
			$labelstr = "selected";
			$numselected = 0;
		}

		if (count($studyids) > 0) {
			?>
			<script type="text/javascript">
				$(function() {
					$("#selectallstudies").click(function() {
						var checked_status = this.checked;
						$(".allstudies").find("input[type='checkbox']").each(function() {
							this.checked = checked_status;
						});
						if (this.checked)
							document.getElementById('includestudies').checked = true;
					});
				});
				
				function SelectAllStudies() {
					var checked_status = document.getElementById('includestudies').checked;
					$(".allstudies").find("input[type='checkbox']").each(function() {
						this.checked = checked_status;
					});
					CheckSelectedStudyCount();
				}

				function CheckSelectedStudyCount(e) {
					var n = document.querySelectorAll('input[type="checkbox"].studycheck:checked').length;
					document.getElementById('numstudiesselected').innerHTML = n;

					if (e.checked)
						document.getElementById('includestudies').checked = true;
				}
			</script>

			<div class="ui grid">
				<div class="ui four wide column">
					<div class="ui toggle <?=$checkboxreadonly?> checkbox" onChange="SelectAllStudies()">
						<input type="checkbox" name="includestudies" id="includestudies" value="1" <?=$checkboxstate?>>
						<label style="font-size:larger; font-weight: bold">Studies</label>
					</div>
				</div>
				<div class="ui ten wide column">
					<div class="ui left pointing red label"><span id="numstudiesselected"><?=$numselected?></span> of <?=$numstudies?> studies <?=$labelstr?></div>
				</div>
			</div>
			
			<div class="ui accordion">
				<div class="title">
					<i class="dropdown icon"></i>
					View studies
				</div>
				<div class="content">
					<table class="ui very compact table">
						<thead>
							<th><input type="checkbox" id="selectallstudies"></th>
							<th>Study</th>
							<th>Date</th>
							<th>Visit</th>
						</thead>
						<tbody>
						<?
							$studyidstr = implode2(",", $studyids);
							
							/* get subject info. there may be series from multiple subjects in this list */
							$sqlstring = "select * from studies b left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where b.study_id in (" . $studyidstr . ")";
							$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$uid = $row['uid'];
								$studynum = $row['studynum'];
								$studyid = $row['study_id'];
								$studydate = $row['study_datetime'];
								$visit = $row['study_visit'];
								
								?>
									<tr>
										<td class="allstudies"><input type="checkbox" name="studyids[]" value="<?=$studyid?>" <?=$checkboxstr?> class="studycheck" onClick="CheckSelectedStudyCount(this)"></td>
										<td><a href="studies.php?id=<?=$studyid?>"><?=$uid?><?=$studynum?></a></td>
										<td><?=$studydate?></td>
										<td><?=$visit?></td>
									</tr>
								<?
							}
						?>
						</tbody>
					</table>
				</div>
			</div>
			<?
		}
		else {
			?>
			<div class="ui toggle read-only checkbox">
				<input type="checkbox" name="includestudies" value="0">
				<label style="font-size:larger; font-weight: bold">No studies found</label>
			</div>
			<br>
			<?
		}
	}


	/* -------------------------------------------- */
	/* ------- DisplayFormSeries ------------------ */
	/* -------------------------------------------- */
	/* seriesids format:
			Array
			(
				[mr] => Array
					(
						[0] => 77
						[1] => 79
					)

			)	
	*/
	function DisplayFormSeries($seriesids, $required, $msg="") {

		$numseries = 0;
		foreach ($seriesids as $modality => $serieslist) {
			$numseries += count($serieslist);
		}

		if ($required) {
			$checkboxstr = " checked onClick='return false' onKeyDown='return false' ";
			$checkboxreadonly = "read-only";
			$checkboxstate = "checked";
			$labelstr = "will be added";
			$numselected = $numseries;
		}
		else {
			$labelstr = "selected";
			$numselected = 0;
		}

		if (count($seriesids) > 0) {
			?>
			<script type="text/javascript">
				$(function() {
					$("#selectallseries").click(function() {
						var checked_status = this.checked;
						$(".allseries").find("input[type='checkbox']").each(function() {
							this.checked = checked_status;
						});
						if (this.checked)
							document.getElementById('includeseries').checked = true;
					});
				});
					
				function SelectAllSeries() {
					var checked_status = document.getElementById('includeseries').checked;
					$(".allseries").find("input[type='checkbox']").each(function() {
						this.checked = checked_status;
					});
					CheckSelectedSeriesCount();
				}

				function CheckSelectedSeriesCount(e) {
					var n = document.querySelectorAll('input[type="checkbox"].seriescheck:checked').length;
					document.getElementById('numseriesselected').innerHTML = n;

					if (e.checked)
						document.getElementById('includeseries').checked = true;
				}
			</script>

			<div class="ui grid">
				<div class="ui four wide column">
					<div class="ui toggle <?=$checkboxreadonly?> checkbox" onChange="SelectAllSeries()">
						<input type="checkbox" name="includeseries" id="includeseries" value="1" <?=$checkboxstate?>>
						<label style="font-size:larger; font-weight: bold">Series</label>
					</div>
				</div>
				<div class="ui ten wide column">
					<div class="ui left pointing red label"><span id="numseriesselected"><?=$numselected?></span> of <?=$numseries?> series <?=$labelstr?></div> &nbsp; <? if ($msg != "") { echo $msg; } ?>
				</div>
			</div>

			<div class="ui accordion">
				<div class="title">
					<i class="dropdown icon"></i>
					View series
				</div>
				<div class="content">
					<table class="ui very compact table">
						<thead>
							<th><input type="checkbox" id="selectallseries"></th>
							<th>UID</th>
							<th>Study</th>
							<th>Series</th>
							<th>Study desc</th>
							<th>Series desc</th>
							<th>Size</th>
							<th>Num Files</th>
						</thead>
						<tbody>
						<?
							foreach ($seriesids as $modality => $serieslist) {
								$seriesidstr = implode2(",", $serieslist);
							
								$sqlstring = "select * from $modality" . "_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.$modality" . "series_id in (" . $seriesidstr . ")";
								$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
								while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
									$seriesid = $row[$modality . 'series_id'];
									$uid = $row['uid'];
									$studynum = $row['study_num'];
									$studydesc = $row['study_desc'];
									$seriesnum = $row['series_num'];
									$seriesdesc = $row['series_desc'];
									$seriessize = $row['series_size'];
									$seriesnumfiles = $row['numfiles'];
									
									?>
										<tr>
											<td class="allseries"><input type="checkbox" name="seriesids[]" value="<?=$modality?>-<?=$seriesid?>" <?=$checkboxstr?> class="seriescheck" onClick="CheckSelectedSeriesCount(this);"></td>
											<td><?=$uid?></td>
											<td><?=$studynum?></td>
											<td><?=$seriesnum?></td>
											<td><?=$studydesc?></td>
											<td><?=$seriesdesc?></td>
											<td><?=$seriessize?></td>
											<td><?=$seriesnumfiles?></td>
										</tr>
									<?
								}
							}
						?>
						</tbody>
					</table>
				</div>
			</div>
			<input type="hidden" name="modality" value="<?=$modality?>">
			<?
		}
		else {
			?>
			<div class="ui toggle read-only checkbox">
				<input type="checkbox" name="includeseries" value="0">
				<label style="font-size:larger; font-weight: bold">No series found</label>
			</div>
			<br>
			<?
		}
	}


	/* -------------------------------------------- */
	/* ------- DisplayFormExperiments ------------- */
	/* -------------------------------------------- */
	function DisplayFormExperiments($experimentids, $required) {
		
		$experimentidstr = implode2(",", $experimentids);
		$numexperiments = count($experimentids);
		
		if ($required) {
			$checkboxstr = " checked onClick='return false' onKeyDown='return false' ";
			$checkboxreadonly = "read-only";
			$checkboxstate = "checked";
			$labelstr = "will be added";
			$numselected = $numexperiments;
		}
		else {
			$labelstr = "selected";
			$numselected = 0;
		}

		if (count($experimentids) > 0) {
			?>
			<script type="text/javascript">
				$(function() {
					$("#selectallexperiments").click(function() {
						var checked_status = this.checked;
						$(".allexperiments").find("input[type='checkbox']").each(function() {
							this.checked = checked_status;
						});
						if (this.checked)
							document.getElementById('includeexperiments').checked = true;
					});
				});
					
				function SelectAllExperiments() {
					var checked_status = document.getElementById('includeexperiments').checked;
					$(".allexperiments").find("input[type='checkbox']").each(function() {
						this.checked = checked_status;
					});
					CheckSelectedExperimentCount();
				}

				function CheckSelectedExperimentCount(e) {
					var n = document.querySelectorAll('input[type="checkbox"].experimentcheck:checked').length;
					document.getElementById('numexperimentsselected').innerHTML = n;

					if (e.checked)
						document.getElementById('includeexperiments').checked = true;
				}
			</script>

			<div class="ui grid">
				<div class="ui four wide column">
					<div class="ui toggle <?=$checkboxreadonly?> checkbox" onChange="SelectAllExperiments()">
						<input type="checkbox" name="includeexperiments" id="includeexperiments" value="1" <?=$checkboxstate?>>
						<label style="font-size:larger; font-weight: bold">Experiments</label>
					</div>
				</div>
				<div class="ui ten wide column">
					<div class="ui left pointing red label"><span id="numexperimentsselected"><?=$numselected?></span> of <?=$numexperiments?> experiments <?=$labelstr?></div>
				</div>
			</div>

			<div class="ui accordion">
				<div class="title">
					<i class="dropdown icon"></i>
					View experiments
				</div>
				<div class="content">
					<table class="ui very compact collapsing table">
						<thead>
							<th><input type="checkbox" id="selectallexperiments"></th>
							<th>Experiment</th>
							<th>Date</th>
						</thead>
						<tbody>
						<?
							/* get subject info. there may be series from multiple subjects in this list */
							$sqlstring = "select * from experiments where experiment_id in (" . $experimentidstr . ")";
							$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$experimentid = $row['experiment_id'];
								$expname = $row['exp_name'];
								$expdate = $row['exp_date'];

								?>
									<tr>
										<td class="allexperiments"><input type="checkbox" name="experimentids[]" value="<?=$experimentid?>" <?=$checkboxstr?> class="experimentcheck" onClick="CheckSelectedExperimentCount(this);"></td>
										<td><a href="experiments.php?id=<?=$experimentid?>"><?=$expname?></a></td>
										<td><?=$expdate?></td>
									</tr>
								<?
							}
						?>
						</tbody>
					</table>
				</div>
			</div>
			<?
		}
		else {
			?>
			<div class="ui toggle read-only checkbox">
				<input type="checkbox" name="includeexperiments" value="0">
				<label style="font-size:larger; font-weight: bold">No experiments found</label>
			</div>
			<br>
			<?
		}
	}


	/* -------------------------------------------- */
	/* ------- DisplayFormAnalyses ---------------- */
	/* -------------------------------------------- */
	function DisplayFormAnalyses($analysisids, $required) {

		$analysisidstr = implode2(",", $analysisids);
		$numanalysis = count($analysisids);

		if ($required) {
			$checkboxstr = " checked onClick='return false' onKeyDown='return false' ";
			$checkboxreadonly = "read-only";
			$checkboxstate = "checked";
			$labelstr = "will be added";
			$numselected = $numanalysis;
		}
		else {
			$labelstr = "selected";
			$numselected = 0;
		}

		if (count($analysisids) > 0) {
			?>
			<script type="text/javascript">
				$(function() {
					$("#selectallanalysis").click(function() {
						var checked_status = this.checked;
						$(".allanalysis").find("input[type='checkbox']").each(function() {
							this.checked = checked_status;
						});
						if (this.checked)
							document.getElementById('includeanalysis').checked = true;
					});
				});

				function SelectAllAnalysis() {
					var checked_status = document.getElementById('includeanalysis').checked;
					$(".allanalysis").find("input[type='checkbox']").each(function() {
						this.checked = checked_status;
					});
					CheckSelectedAnalysisCount();
				}

				function CheckSelectedAnalysisCount(e) {
					var n = document.querySelectorAll('input[type="checkbox"].analysischeck:checked').length;
					document.getElementById('numanalysisselected').innerHTML = n;

					if (e.checked)
						document.getElementById('includeanalysis').checked = true;
				}
			</script>

			<div class="ui grid">
				<div class="ui four wide column">
					<div class="ui toggle <?=$checkboxreadonly?> checkbox" onChange="SelectAllAnalysis()">
						<input type="checkbox" name="includeanalysis" id="includeanalysis" value="1" <?=$checkboxstate?>>
						<label style="font-size:larger; font-weight: bold">Analyses</label>
					</div>
				</div>
				<div class="ui ten wide column">
					<div class="ui left pointing red label"><span id="numanalysisselected"><?=$numselected?></span> of <?=$numanalysis?> analyses <?=$labelstr?></div>
				</div>
			</div>

			<div class="ui accordion">
				<div class="title">
					<i class="dropdown icon"></i>
					Select analyses
				</div>
				<div class="content">
					<table class="ui very compact table">
						<thead>
							<th><input type="checkbox" name="selectallanalysis" id="selectallanalysis"></th>
							<th>Analysis</th>
							<th>Date</th>
							<th>Status</th>
						</thead>
						<tbody>
						<?
						//'complete','pending','processing','error','submitted','','notcompleted','NoMatchingStudies','rerunresults','NoMatchingStudyDependency','IncompleteDependency','BadDependency','NoMatchingSeries','OddDependencyStatus','started'
							/* get subject info. there may be series from multiple subjects in this list */
							$sqlstring = "select * from analysis where analysis_id in (" . $analysisidstr . ") and analysis_status in ('complete', 'error','rerunresults')";
							$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$analysisid = $row['analysis_id'];
								$analysisdate = $row['analysis_date'];
								$analysisstatus = $row['analysis_status'];
								?>
									<tr>
										<td class="allanalysis"><input type="checkbox" name="analysisids[]" value="<?=$analysisid?>" class="analysischeck" <?=$checkboxstr?> onClick="CheckSelectedAnalysisCount(this);"></td>
										<td><a href="analysis.php?analysisid=<?=$analysisid?>"><?=$analysisid?></a></td>
										<td><?=$analysisdate?></td>
										<td><?=$analysisstatus?></td>
									</tr>
								<?
							}
						?>
						</tbody>
					</table>
				</div>
			</div>
			<?
		}
		else {
			?>
			<div class="ui toggle read-only checkbox">
				<input type="checkbox" name="includeanalysis" value="0">
				<label style="font-size:larger; font-weight: bold">No analyses found</label>
			</div>
			<br>
			<?
		}
	}


	/* -------------------------------------------- */
	/* ------- DisplayFormPipelines --------------- */
	/* -------------------------------------------- */
	function DisplayFormPipelines($pipelineids, $required) {

		$pipelineidstr = implode2(",", $pipelineids);
		$numpipelines = count($pipelineids);

		if ($required) {
			$checkboxstr = " checked onClick='return false' onKeyDown='return false' ";
			$checkboxreadonly = "read-only";
			$checkboxstate = "checked";
			$labelstr = "will be added";
			$numselected = $numpipelines;
		}
		else {
			$labelstr = "selected";
			$numselected = 0;
		}

		if (count($pipelineids) > 0) {
			?>
			<script type="text/javascript">
				$(function() {
					$("#selectallpipelines").click(function() {
						var checked_status = this.checked;
						$(".allpipelines").find("input[type='checkbox']").each(function() {
							this.checked = checked_status;
						});
						if (this.checked)
							document.getElementById('includepipelines').checked = true;
					});
				});
				
				function SelectAllPipelines() {
					var checked_status = document.getElementById('includepipelines').checked;
					$(".allpipelines").find("input[type='checkbox']").each(function() {
						this.checked = checked_status;
					});
					CheckSelectedPipelineCount();
				}

				function CheckSelectedPipelineCount(e) {
					var n = document.querySelectorAll('input[type="checkbox"].pipelinecheck:checked').length;
					document.getElementById('numpipelinesselected').innerHTML = n;

					if (e.checked)
						document.getElementById('includepipelines').checked = true;
				}
			</script>

			<div class="ui grid">
				<div class="ui four wide column">
					<div class="ui toggle <?=$checkboxreadonly?> checkbox" onChange="SelectAllPipelines()">
						<input type="checkbox" name="includepipelines" id="includepipelines" value="1" <?=$checkboxstate?>>
						<label style="font-size:larger; font-weight: bold">Pipelines</label>
					</div>
				</div>
				<div class="ui ten wide column">
					<div class="ui left pointing red label"><span id="numpipelinesselected"><?=$numselected?></span> of <?=$numpipelines?> pipelines <?=$labelstr?></div>
				</div>
			</div>

			<div class="ui accordion">
				<div class="title">
					<i class="dropdown icon"></i>
					View pipelines
				</div>
				<div class="content">
					<table class="ui very compact table">
						<thead>
							<th><input type="checkbox" id="selectallpipelines"></th>
							<th>Pipeline</th>
						</thead>
						<tbody>
						<?
							/* get subject info. there may be series from multiple subjects in this list */
							$sqlstring = "select * from pipelines where pipeline_id in (" . $pipelineidstr . ")";
							$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$pipelineid = $row['pipeline_id'];
								$pipelinename = $row['pipeline_name'];

								?>
									<tr>
										<td class="allpipelines"><input type="checkbox" name="pipelineids[]" value="<?=$pipelineid?>" class="pipelinecheck" <?=$checkboxstr?> onClick="CheckSelectedPipelineCount(this);"></td>
										<td><a href="pipelines.php?pipelineid=<?=$pipelineid?>"><?=$pipelinename?></a></td>
									</tr>
								<?
							}
						?>
						</tbody>
					</table>
				</div>
			</div>
			<?
		}
		else {
			?>
			<div class="ui toggle read-only checkbox">
				<input type="checkbox" name="includepipelines" value="0">
				<label style="font-size:larger; font-weight: bold">No pipelines found</label>
			</div>
			<br>
			<?
		}
	}


	/* -------------------------------------------- */
	/* ------- DisplayFormMeasures ---------------- */
	/* -------------------------------------------- */
	function DisplayFormMeasures($enrollmentids, $required) {

		$enrollmentidstr = implode2(",", $enrollmentids);

		$sqlstring = "select * from measures a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id left join measurenames d on a.measurename_id = d.measurename_id where a.enrollment_id in (" . implode2(",", $enrollmentids) . ")";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$nummeasures = mysqli_num_rows($result);
		
		if ($required) {
			$checkboxstr = " checked onClick='return false' onKeyDown='return false' ";
			$checkboxreadonly = "read-only";
			$checkboxstate = "checked";
			$labelstr = "will be added";
			$numselected = $nummeasures;
		}
		else {
			$labelstr = "selected";
			$numselected = 0;
		}
		
		if ((count($enrollmentids) > 0) && ($nummeasures > 0)) {
			?>
			<script type="text/javascript">
				$(function() {
					$("#selectallmeasures").click(function() {
						var checked_status = this.checked;
						$(".allmeasurees").find("input[type='checkbox']").each(function() {
							this.checked = checked_status;
						});
						if (this.checked)
							document.getElementById('includemeasures').checked = true;
					});
				});
				
				function SelectAllMeasures() {
					var checked_status = document.getElementById('includemeasures').checked;
					$(".allmeasures").find("input[type='checkbox']").each(function() {
						this.checked = checked_status;
					});
					CheckSelectedMeasureCount();
				}

				function CheckSelectedMeasureCount(e) {
					var n = document.querySelectorAll('input[type="checkbox"].measurecheck:checked').length;
					document.getElementById('nummeasuresselected').innerHTML = n;

					if (e.checked)
						document.getElementById('includemeasures').checked = true;
				}
			</script>

			<div class="ui grid">
				<div class="ui four wide column">
					<div class="ui toggle <?=$checkboxreadonly?> checkbox" onChange="SelectAllMeasures()">
						<input type="checkbox" name="includemeasures" id="includemeasures" value="1" <?=$checkboxstate?>>
						<label style="font-size:larger; font-weight: bold">Measures</label>
					</div>
				</div>
				<div class="ui ten wide column">
					<div class="ui left pointing red label"><span id="nummeasuresselected"><?=$numselected?></span> of <?=$nummeasures?> measures <?=$labelstr?></div>
				</div>
			</div>

			<div class="ui accordion">
				<div class="title">
					<i class="dropdown icon"></i>
					View measures
				</div>
				<div class="content">
					<table class="ui very compact collapsing table">
						<thead>
							<th><input type="checkbox" id="selectallmeasures"></th>
							<th>UID</th>
							<th>Measure</th>
							<th>Date</th>
						</thead>
						<tbody>
						<?
							/* get subject info. there may be series from multiple subjects in this list */
							//$sqlstring = "select * from measures a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id left join measurenames d on a.measurename_id = d.measurename_id where a.enrollment_id in (" . implode2(",", $enrollmentids) . ")";
							//$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$uid = $row['uid'];
								$subjectid = $row['subject_id'];
								$mesauredate = $row['measure_startdate'];
								$measureid = $row['measure_id'];
								$measurename = $row['measure_name'];
								
								$measureids[] = $measureid;
								?>
									<tr>
										<td class="allmeasures"><input type="checkbox" name="measureids[]" value="<?=$measureid?>" <?=$checkboxstr?> class="measurecheck" onClick="CheckSelectedMeasureCount(this);"></td>
										<td><a href="subjects.php?subjectid=<?=$subjectid?>"><?=$uid?></a></td>
										<td><?=$measurename?></td>
										<td><?=$measuredate?></td>
									</tr>
								<?
							}
						?>
						</tbody>
					</table>
				</div>
			</div>
			<?
		}
		else {
			?>
			<div class="ui toggle read-only checkbox">
				<input type="checkbox" name="includeneasures" value="0">
				<label style="font-size:larger; font-weight: bold">No measures found</label>
			</div>
			<br>
			<?
		}
	}


	/* -------------------------------------------- */
	/* ------- DisplayFormDrugs ------------------- */
	/* -------------------------------------------- */
	function DisplayFormDrugs($enrollmentids, $required) {
		
		$enrollmentidstr = implode2(",", $enrollmentids);

		$sqlstring = "select * from drugs a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id left join drugnames d on a.drugname_id = d.drugname_id where a.enrollment_id in (" . implode2(",", $enrollmentids) . ")";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$numdrugs = mysqli_num_rows($result);
		
		if ($required) {
			$checkboxstr = " checked onClick='return false' onKeyDown='return false' ";
			$checkboxreadonly = "read-only";
			$checkboxstate = "checked";
			$labelstr = "will be added";
			$numselected = $numdrugs;
		}
		else {
			$labelstr = "selected";
			$numselected = 0;
		}

		if ((count($enrollmentids) > 0) && ($numdrugs > 0)) {
			?>
			<script type="text/javascript">
				$(function() {
					$("#selectalldrugs").click(function() {
						var checked_status = this.checked;
						$(".alldrugs").find("input[type='checkbox']").each(function() {
							this.checked = checked_status;
						});
						if (this.checked)
							document.getElementById('includedrugs').checked = true;
					});
				});

				function SelectAllDrugs() {
					var checked_status = document.getElementById('includedrugs').checked;
					$(".alldrugs").find("input[type='checkbox']").each(function() {
						this.checked = checked_status;
					});
					CheckSelectedDrugCount();
				}

				function CheckSelectedDrugCount(e) {
					var n = document.querySelectorAll('input[type="checkbox"].drugcheck:checked').length;
					document.getElementById('numdrugsselected').innerHTML = n;

					if (e.checked)
						document.getElementById('includedrugs').checked = true;
				}
			</script>

			<div class="ui grid">
				<div class="ui four wide column">
					<div class="ui toggle <?=$checkboxreadonly?> checkbox" onChange="SelectAllDrugs()">
						<input type="checkbox" name="includedrugs" id="includesubjects" value="1" <?=$checkboxstate?>>
						<label style="font-size:larger; font-weight: bold">Drugs</label>
					</div>
				</div>
				<div class="ui ten wide column">
					<div class="ui left pointing red label"><span id="numdrugsselected"><?=$numselected?></span> of <?=$numdrugs?> drugs <?=$labelstr?></div>
				</div>
			</div>

			<div class="ui accordion">
				<div class="title">
					<i class="dropdown icon"></i>
					View drugs
				</div>
				<div class="content">
					<table class="ui very compact table">
						<thead>
							<th><input type="checkbox" id="selectalldrugs"></th>
							<th>UID</th>
							<th>Drug</th>
							<th>Dose desc</th>
							<th>Date</th>
						</thead>
						<tbody>
						<?
							/* get subject info. there may be series from multiple subjects in this list */
							//$sqlstring = "select * from drugs a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id left join drugnames d on a.drugname_id = d.drugname_id where a.enrollment_id in (" . implode2(",", $enrollmentids) . ")";
							//$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$uid = $row['uid'];
								$subjectid = $row['subject_id'];
								$drugdate = $row['drug_startdate'];
								$drugid = $row['drug_id'];
								$dosedesc = $row['drug_dosedesc'];
								$drugname = $row['drug_name'];
								
								$drugids[] = $drugid;
								?>
									<tr>
										<td class="alldrugs"><input type="checkbox" name="drugids[]" value="<?=$drugid?>" <?=$checkboxstr?> class="drugcheck" onClick="CheckSelectedDrugCount(this);"></td>
										<td><a href="subjects.php?subjectid=<?=$subjectid?>"><?=$uid?></a></td>
										<td><?=$drug?></td>
										<td><?=$drugdesc?></td>
										<td><?=$drugdate?></td>
									</tr>
								<?
							}
						?>
						</tbody>
					</table>
				</div>
			</div>
			<?
		}
		else {
			?>
			<div class="ui toggle read-only checkbox">
				<input type="checkbox" name="includedrugs" value="0">
				<label style="font-size:larger; font-weight: bold">No drugs found</label>
			</div>
			<br>
			<?
		}
	}


	/* -------------------------------------------- */
	/* ------- RemoveObject ----------------------- */
	/* -------------------------------------------- */
	function RemoveObject($packageid, $objecttype, $objectids) {

		/* perform data checks */
		$packageid = mysqli_real_escape_string($GLOBALS['linki'], $packageid);
		$objecttype = mysqli_real_escape_string($GLOBALS['linki'], $objecttype);
		$objectids = mysqli_real_escape_array($GLOBALS['linki'], $objectids);

		$numobjects = count($objectids);
		$objectidstr = implode2(",", $objectids);
		switch ($objecttype) {
			case "enrollment":
				DisplayAddEnrollmentForm($objectids);
				break;
			case "subject":
				DisplayAddSubjectForm($objectids);
				break;
			case "study":
				DisplayAddStudyForm($objectids);
				break;
			case "series":
				$sqlstring = "delete from package_series where packageseries_id in ($objectidstr)";
				PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				Notice("Removed $numobjects series");
				break;
			case "measure":
				$sqlstring = "delete from package_measures where packagemeasure_id in ($objectidstr)";
				PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				Notice("Removed $numobjects measures");
				break;
			case "drug":
				$sqlstring = "delete from package_drugs where packagedrug_id in ($objectidstr)";
				PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				Notice("Removed $numobjects drugs");
				break;
			case "experiment":
				$sqlstring = "delete from package_experiments where packageexperiment_id in ($objectidstr)";
				PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				Notice("Removed $numobjects experiments");
				break;
			case "pipeline":
				$sqlstring = "delete from package_pipelines where packagepipeline_id in ($objectidstr)";
				PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				Notice("Removed $numobjects pipelines");
				break;
			case "analysis":
				$sqlstring = "delete from package_analyses where packageanalysis_id in ($objectidstr)";
				PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				Notice("Removed $numobjects analyses");
				break;
			case "datadictionary":
				$sqlstring = "delete from package_dictionaries where packagedatadictionary_id in ($objectidstr)";
				PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				Notice("Removed $numobjects datadictionaries");
				break;
		}
	}


	/* -------------------------------------------- */
	/* ------- AddObjectsToPackage ---------------- */
	/* -------------------------------------------- */
	function AddObjectsToPackage($packageid, $enrollmentids, $subjectids, $studyids, $seriesids, $modality, $experimentids, $analysisids, $pipelineids, $datadictionaryids, $drugids, $measureids, $includedrugs, $includemeasures, $includeexperiments, $includeanalysis, $includepipelines) {

		/* perform data checks */
		$packageid = mysqli_real_escape_string($GLOBALS['linki'], $packageid);
		$enrollmentids = mysqli_real_escape_array($GLOBALS['linki'], $enrollmentids);
		$subjectids = mysqli_real_escape_array($GLOBALS['linki'], $subjectids);
		$studyids = mysqli_real_escape_array($GLOBALS['linki'], $studyids);
		$seriesids = mysqli_real_escape_array($GLOBALS['linki'], $seriesids);
		$modality = mysqli_real_escape_string($GLOBALS['linki'], $modality);
		$experimentids = mysqli_real_escape_array($GLOBALS['linki'], $experimentids);
		$analysisids = mysqli_real_escape_array($GLOBALS['linki'], $analysisids);
		$pipelineids = mysqli_real_escape_array($GLOBALS['linki'], $pipelineids);
		$datadictionaryids = mysqli_real_escape_array($GLOBALS['linki'], $datadictionaryids);
		$drugids = mysqli_real_escape_array($GLOBALS['linki'], $drugids);
		$measureids = mysqli_real_escape_array($GLOBALS['linki'], $measureids);
		$includedrugs = mysqli_real_escape_string($GLOBALS['linki'], $includedrugs);
		$includemeasures = mysqli_real_escape_string($GLOBALS['linki'], $includemeasures);
		$includeexperiments = mysqli_real_escape_string($GLOBALS['linki'], $includeexperiments);
		$includeanalysis = mysqli_real_escape_string($GLOBALS['linki'], $includeanalysis);
		$includepipelines = mysqli_real_escape_string($GLOBALS['linki'], $includepipelines);
		
		/* add any enrollments */
		if ((count($enrollmentids) > 0) && (is_array($enrollmentids))) {
			//PrintVariable($enrollments);
			foreach ($enrollmentids as $enrollmentid) {
				$sqlstring = "insert ignore into package_enrollments (package_id, enrollment_id) values ($packageid, $enrollmentid)";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
			$numobjects += count($enrollmentids);
			$msg .= "Added " . count($enrollmentids) . " enrollments<br>";
		}

		/* add any series */
		if ((count($seriesids) > 0) && (is_array($seriesids))) {
			foreach ($seriesids as $seriesid) {
				list($mod, $sid) = explode("-",$seriesid);
				$sqlstring = "insert ignore into package_series (package_id, modality, series_id) values ($packageid, '$mod', $sid)";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
			$numobjects += count($seriesids);
			$msg .= "Added " . count($seriesids) . " series<br>";
		}

		/* add any experiments */
		if ((count($experimentids) > 0) && ($includeexperiments) && (is_array($experimentids))) {
			foreach ($experimentids as $experimentid) {
				$sqlstring = "insert ignore into package_experiments (package_id, experiment_id) values ($packageid, $experimentid)";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
			$numobjects += count($experimentids);
			$msg .= "Added " . count($experimentids) . " experiments<br>";
		}

		/* add any analyses */
		if ((count($analysisids) > 0) && ($includeanalysis) && (is_array($analysisids))) {
			foreach ($analysisids as $analysisid) {
				$sqlstring = "insert ignore into package_analyses (package_id, analysis_id) values ($packageid, $analysisid)";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
			$numobjects += count($analysisids);
			$msg .= "Added " . count($analysisids) . " analyses<br>";
		}
		
		/* add any pipelines */
		if ((count($pipelineids) > 0) && ($includepipelines) && (is_array($pipelineids))) {
			foreach ($pipelineids as $pipelineid) {
				$sqlstring = "insert ignore into package_pipelines (package_id, pipeline_id) values ($packageid, $pipelineid)";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
			$numobjects += count($pipelineids);
			$msg .= "Added " . count($pipelineids) . " pipelines<br>";
		}

		/* add any drugs */
		if ((count($drugids) > 0) && ($includedrugs) && (is_array($drugids))) {
			foreach ($drugids as $drugid) {
				$sqlstring = "insert ignore into package_drugs (package_id, drug_id) values ($packageid, $drugid)";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
			$numobjects += count($drugids);
			$msg .= "Added " . count($drugids) . " drugs<br>";
		}
		
		/* add any measures */
		if ((count($measureids) > 0) && ($includemeasures) && (is_array($measureids))) {
			foreach ($measureids as $measureid) {
				$sqlstring = "insert ignore into package_measures (package_id, measure_id) values ($packageid, $measureid)";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
			$numobjects += count($measureids);
			$msg .= "Added " . count($measureids) . " measures<br>";
		}
		
		$title = "Added $numobjects objects to package";
		Notice($msg, $title);
	}
	

	/* -------------------------------------------- */
	/* ------- UpdatePackage ---------------------- */
	/* -------------------------------------------- */
	function UpdatePackage($packageid,$packagename,$packagedesc,$packageformat,$subjectdirformat,$studydirformat,$seriesdirformat,$readme,$notes,$license,$changes) {
		/* perform data checks */
		$packageid = mysqli_real_escape_string($GLOBALS['linki'], $packageid);
		$packagename = mysqli_real_escape_string($GLOBALS['linki'], $packagename);
		$packagedesc = mysqli_real_escape_string($GLOBALS['linki'], $packagedesc);
		$packageformat = mysqli_real_escape_string($GLOBALS['linki'], $packageformat);
		$subjectdirformat = mysqli_real_escape_string($GLOBALS['linki'], $subjectdirformat);
		$studydirformat = mysqli_real_escape_string($GLOBALS['linki'], $studydirformat);
		$seriesdirformat = mysqli_real_escape_string($GLOBALS['linki'], $seriesdirformat);
		$readme = mysqli_real_escape_string($GLOBALS['linki'], $readme);
		$notes = mysqli_real_escape_string($GLOBALS['linki'], $notes);
		$license = mysqli_real_escape_string($GLOBALS['linki'], $license);
		$changes = mysqli_real_escape_string($GLOBALS['linki'], $changes);
		
		/* update the package */
		$sqlstring = "update packages set package_name = '$packagename', package_desc = '$packagedesc', package_subjectdirformat = '$subjectdirformat', package_studydirformat = '$studydirformat', package_seriesdirformat = '$seriesdirformat', package_dataformat = '$packageformat', package_license = '$license', package_readme = '$readme', package_changes = '$changes', package_notes = '$notes' where package_id = $packageid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		Notice("$packagename updated");
		return true;
	}


	/* -------------------------------------------- */
	/* ------- AddPackage ------------------------- */
	/* -------------------------------------------- */
	function AddPackage($packagename,$packagedesc,$packageformat,$subjectdirformat,$studydirformat,$seriesdirformat,$readme,$notes,$license,$changes) {
		/* perform data checks */
		$packagename = mysqli_real_escape_string($GLOBALS['linki'], $packagename);
		$packagedesc = mysqli_real_escape_string($GLOBALS['linki'], $packagedesc);
		$packageformat = mysqli_real_escape_string($GLOBALS['linki'], $packageformat);
		$subjectdirformat = mysqli_real_escape_string($GLOBALS['linki'], $subjectdirformat);
		$studydirformat = mysqli_real_escape_string($GLOBALS['linki'], $studydirformat);
		$seriesdirformat = mysqli_real_escape_string($GLOBALS['linki'], $seriesdirformat);
		$readme = mysqli_real_escape_string($GLOBALS['linki'], $readme);
		$notes = mysqli_real_escape_string($GLOBALS['linki'], $notes);
		$license = mysqli_real_escape_string($GLOBALS['linki'], $license);
		$changes = mysqli_real_escape_string($GLOBALS['linki'], $changes);

		/* insert the new package */
		$sqlstring = "insert into packages (user_id, package_date, package_name, package_desc, package_subjectdirformat, package_studydirformat, package_seriesdirformat, package_dataformat, package_license, package_readme, package_changes, package_notes) values (" . $_SESSION['userid'] . ", now(), '$packagename', '$packagedesc', '$subjectdirformat', '$studydirformat', '$seriesdirformat', '$packageformat', '$readme', '$notes', '$license', '$changes')";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$packageid = mysqli_insert_id($GLOBALS['linki']);

		Notice("$packagename created");
	}


	/* -------------------------------------------- */
	/* ------- DeletePackage ---------------------- */
	/* -------------------------------------------- */
	function DeletePackage($packageid) {
		if (!ValidID($packageid,'Package ID')) { return; }
		
		$sqlstring = "delete from packages where package_id = $packageid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		Notice("Package deleted");
	}	


	/* -------------------------------------------- */
	/* ------- DisplayPackage --------------------- */
	/* -------------------------------------------- */
	function DisplayPackage($packageid) {
		if (!ValidID($packageid,'Package ID')) { return; }

		/* declare variables */
		$subjects = array();
		$measures = array();
		$drugs = array();
		$analyses = array();
		$experiments = array();
		$pipelines = array();
		$datadictionaries = array();
		
		/* get package details */
		$sqlstring = "select * from packages where package_id = $packageid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$pkg['createdate'] = date('M j, Y h:ia',strtotime($row['package_date']));
		$pkg['name'] = $row['package_name'];
		$pkg['desc'] = $row['package_desc'];
		$pkg['subjectDirFormat'] = $row['package_subjectdirformat'];
		$pkg['studyDirFormat'] = $row['package_studydirformat'];
		$pkg['seriesDirFormat'] = $row['package_seriesdirformat'];
		$pkg['dataFormat'] = $row['package_dataformat'];
		$pkg['license'] = $row['package_license'];
		$pkg['readme'] = $row['package_readme'];
		$pkg['changes'] = $row['package_changes'];
		$pkg['notes'] = $row['package_notes'];
		
		/* get enrollment data */
		$sqlstring = "select * from package_enrollments where package_id = $packageid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$packagesenrollmentid = $row['packageenrollment_id'];
			$enrollmentid = $row['enrollment_id'];
			$optionflags = $row['option_flags'];
			$pkgsubjectid = $row['pkg_subjectid'];
			
			list($uid, $subjectid, $projectname, $projectid) = GetEnrollmentInfo($enrollmentid);
			
			//if (contains($optionflags, 'DRUGS')) {
			//	$drugs[$uid]['drugs'] = GetDrugsByEnrollment($enrollmentid);
			//}
			//if (contains($optionflags, 'MEASURES')) {
			//	$measures[$uid]['measures'] = GetVitalsByEnrollment($enrollmentid);
			//	$measures[$uid]['measures'] .= GetMeasuresByEnrollment($enrollmentid);
			//}
		}
		
		/* get series data */
		$totalbytes = 0;
		$totalfiles = 0;
		$sqlstring = "select * from package_series where package_id = $packageid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$packageseriesid = $row['packageseries_id'];
			$modality = $row['modality'];
			$seriesid = $row['series_id'];
			list($path, $uid, $studynum, $seriesnum, $seriessize, $numfiles, $studyid, $subjectid, $modality, $type, $studydatetime, $enrollmentid, $projectname, $projectid) = GetSeriesInfo($seriesid, $modality);
			
			if ($uid != "") {
				$subjects[$uid][$studynum][$modality][$seriesnum] = "$seriesid";
				$totalbytes += $seriessize;
				$totalfiles += $numfiles;
			}
		}

		/* get measures */
		$sqlstring = "select * from package_measures a left join measures b on a.measure_id = b.measure_id left join measurenames c on b.measurename_id = c.measurename_id where a.package_id = $packageid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$nummeasures = mysqli_num_rows($result);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$enrollmentid = $row['enrollment_id'];
			list($uid, $subjectid, $projectname, $projectid) = GetEnrollmentInfo($enrollmentid);
			$objectid = $row['packagemeasure_id'];
			$measures[$uid][$objectid]['measureid'] = $row['measure_id'];
			$measures[$uid][$objectid]['name'] = $row['measure_name'];
			$measures[$uid][$objectid]['value'] = $row['measure_value'];
			$measures[$uid][$objectid]['startdate'] = $row['measure_startdate'];
		}

		/* get drugs */
		$sqlstring = "select * from package_drugs a left join drugs b on a.drug_id = b.drug_id left join drugnames c on b.drugname_id = c.drugname_id where a.package_id = $packageid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$numdrugs = mysqli_num_rows($result);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$enrollmentid = $row['enrollment_id'];
			list($uid, $subjectid, $projectname, $projectid) = GetEnrollmentInfo($enrollmentid);
			$objectid = $row['packagemeasure_id'];
			$drugs[$uid][$objectid]['drugid'] = $row['drug_id'];
			$drugs[$uid][$objectid]['name'] = $row['drug_name'];
			$drugs[$uid][$objectid]['startdate'] = $row['drug_startdate'];
		}
		
		/* get experiments */
		$sqlstring = "select * from package_experiments a left join experiments b on a.experiment_id = b.experiment_id where a.package_id = $packageid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$packageexperimentid = $row['packageexperiment_id'];
			$experimentid = $row['experiment_id'];
			$experiments[$experimentid]['objectid'] = $packageexperimentid;
			$experiments[$experimentid]['name'] = $row['exp_name'];
			$experiments[$experimentid]['version'] = $row['exp_version'];
			$experiments[$experimentid]['desc'] = $row['exp_desc'];
			$experiments[$experimentid]['createdate'] = $row['exp_createdate'];
			$experiments[$experimentid]['creator'] = $row['exp_creator'];
		}
		$numexperiments = count($experiments);
		
		/* get pipelines */
		$sqlstring = "select * from package_pipelines a left join pipelines b on a.pipeline_id = b.pipeline_id where a.package_id = $packageid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$packagepipelineid = $row['packagepipeline_id'];
			$pipelineid = $row['pipeline_id'];
			$pipelines[$pipelineid]['objectid'] = $packagepipelineid;
			$pipelines[$pipelineid]['name'] = $row['pipeline_name'];
			$pipelines[$pipelineid]['version'] = $row['pipeline_version'];
			$pipelines[$pipelineid]['desc'] = $row['pipeline_desc'];
			$pipelines[$pipelineid]['createdate'] = $row['pipeline_createdate'];
		}
		$numpipelines = count($pipelines);

		/* get analysis */
		$totalanalysisfiles = 0;
		$totalanalysisbytes = 0;
		$sqlstring = "select * from package_analyses a left join analysis b on a.analysis_id = b.analysis_id left join studies c on b.study_id = c.study_id where a.package_id = $packageid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$numanalysis = mysqli_num_rows($result);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$enrollmentid = $row['enrollment_id'];
			list($uid, $subjectid, $projectname, $projectid) = GetEnrollmentInfo($enrollmentid);

			$objectid = $row['packageanalysis_id'];
			$analyses[$uid][$objectid]['analysisid'] = $row['analysis_id'];
			$analyses[$uid][$objectid]['studynum'] = $row['study_num'];
			$analyses[$uid][$objectid]['date'] = $row['analysis_date'];
			$analyses[$uid][$objectid]['status'] = $row['analysis_status'];
			$analyses[$uid][$objectid]['disksize'] = $row['analysis_disksize'];
			$analyses[$uid][$objectid]['numfiles'] = $row['analysis_numfiles'];
			
			$totalanalysisfiles += $row['analysis_numfiles'];
			$totalanalysisbytes += $row['analysis_disksize'];
		}

		/* get counts of all of the data objects */
		$numsubjects = count($subjects);
		$numstudies = 0;
		//$nummeasures = 0;
		//$numdrugs = 0;
		$numdatadict = count($datadictionaries);
		$numgroupanalyses = count($groupanalyses);

		foreach ($subjects as $uid =>$studies) {
			if ($uid != "") {
				$numstudies += count($studies);
				foreach ($studies as $studynum => $modalities) {
					foreach ($modalities as $modality => $series) {
						$numseries += count($series);
					}
				}
			}
		}
		
		?>
		<div class="ui container">
			<div class="ui top attached raised segment">
				<div class="ui header">
					<img src="images/squirrel-icon-64.png"></img>
					<h2 class="content"><?=$pkg['name']?></h2>
					<div class="sub header"><?=$pkg['desc']?></div>
				</div>
			</div>
			
			<script>
				$(document).ready(function() {
					$('.menu .item').tab();
					$('.tabular.menu .item').tab();
				});
			</script>
			<style>
				.item2.active { background-color: #333 !important; color: #fff !important; }
				td.a { font-weight: bold; }
				td.b {
					max-width: 100px;
					overflow: hidden;
					text-overflow: ellipsis;
					white-space: nowrap;
				}
			</style>
			
			<!-- tab menu -->
			<div class="ui attached large tabular menu">
				<a class="active item item2" data-tab="overview"><i class="grey box open icon"></i>Package overview</a>
				<a class="item item2" data-tab="subjects"><i class="grey user icon"></i> Subjects & Data</a>
				<a class="item item2" data-tab="measures"><i class="grey clipboard icon"></i> Measures</a>
				<a class="item item2" data-tab="drugs"><i class="grey pills icon"></i> Drugs</a>
				<a class="item item2" data-tab="analysis">Analysis</a>
				<a class="item item2" data-tab="experiments">Experiments</a>
				<a class="item item2" data-tab="pipelines">Pipelines</a>
				<a class="item item2" data-tab="datadict"><i class="grey book icon"></i>Data dictionary</a>
			</div>

			<!-- package overview tab -->
			<div class="ui bottom attached active tab raised center aligned segment" data-tab="overview">
				<div class="ui grid">
					<div class="ui five wide column">
						<div class="ui top attached segment" style="background-color: #eee">
							<b>Package details</b>
						</div>
						<table class="ui bottom attached table">
							<tr>
								<td class="a">Name</td>
								<td><?=$pkg['name']?></td>
							</tr>
							<tr>
								<td class="a">Description</td>
								<td><?=$pkg['desc']?></td>
							</tr>
							<tr>
								<td class="a">Date</td>
								<td><?=$pkg['createdate']?></td>
							</tr>
							<tr>
								<td class="a">Subject dir format</td>
								<td><?=$pkg['subjectDirFormat']?></td>
							</tr>
							<tr>
								<td class="a">Study dir format</td>
								<td><?=$pkg['studyDirFormat']?></td>
							</tr>
							<tr>
								<td class="a">Series dir format</td>
								<td><?=$pkg['seriesDirFormat']?></td>
							</tr>
							<tr>
								<td class="a">Data format</td>
								<td><?=$pkg['dataFormat']?></td>
							</tr>
							<tr>
								<td class="a">License</td>
								<td class="b"><?=$pkg['license']?></td>
							</tr>
							<tr>
								<td class="a">Readme</td>
								<td class="b"><?=$pkg['readme']?></td>
							</tr>
							<tr>
								<td class="a">Changes</td>
								<td class="b"><?=$pkg['changes']?></td>
							</tr>
							<tr>
								<td class="a">Notes</td>
								<td class="b"><?=$pkg['notes']?></td>
							</tr>
						</table>
					</div>
					<div class="ui eleven wide column">
						<script type="module">
							import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';
						</script>
						
						<?
							if ($numsubjects > 0) { $subjcolor = "fill:#ffe500,stroke:#444,stroke-width:4px"; $subjtext = "subjects ($numsubjects)"; } else { $subjcolor = "fill:#fff,stroke:#aaa,color:#999,stroke-width:4px"; $subjtext = "subjects"; }
							if ($numstudies > 0) { $studcolor = "fill:#ffe500,stroke:#444,stroke-width:4px"; $studtext = "studies ($numstudies)"; } else { $studcolor = "fill:#fff,stroke:#aaa,color:#999,stroke-width:4px"; $studtext = "studies"; }
							if ($numseries > 0) { $sercolor = "fill:#ffe500,stroke:#444,stroke-width:4px"; $sertext = "series ($numseries)"; } else { $sercolor = "fill:#fff,stroke:#aaa,color:#999,stroke-width:4px"; $sertext = "series"; }
							if ($numexperiments > 0) { $expcolor = "fill:#FFFFCC,stroke:#444,stroke-width:1px"; $exptext = "experiments ($numexperiments)"; } else { $expcolor = "fill:#fff,stroke:#aaa,color:#999,stroke-width:1px"; $exptext = "experiments"; }
							if ($numpipelines > 0) { $pipecolor = "fill:#FFFFCC,stroke:#444,stroke-width:1px"; $pipetext = "pipelines ($numpipelines)"; } else { $pipecolor = "fill:#fff,stroke:#aaa,color:#999,stroke-width:1px"; $pipetext = "pipelines"; }
							//if ($numdatadict > 0) { $dictcolor = "fill:#FFFFCC,stroke:#444,stroke-width:1px"; $dicttext = "data-dictionary ($numdatadict)"; } else { $dictcolor = "fill:#fff,stroke:#aaa,color:#999,stroke-width:1px"; $dicttext = "data-dictionary"; }
							if ($numanalysis > 0) { $analysiscolor = "fill:#FFFFCC,stroke:#444,stroke-width:1px"; $analysistext = "analysis ($numanalysis)"; } else { $analysiscolor = "fill:#fff,stroke:#aaa,color:#999,stroke-width:1px"; $analysistext = "analysis"; }
							if ($numgroupanalyses > 0) { $groupanalysiscolor = "fill:#FFFFCC,stroke:#444,stroke-width:1px"; $groupanalysistext = "group-analysis ($numgroupanalyses)"; } else { $groupanalysiscolor = "fill:#fff,stroke:#aaa,color:#999,stroke-width:1px"; $groupanalysistext = "group-analysis"; }
							if ($nummeasures > 0) { $meascolor = "fill:#FFFFCC,stroke:#444,stroke-width:1px"; $meastext = "measures ($nummeasures)"; } else { $meascolor = "fill:#fff,stroke:#aaa,color:#999,stroke-width:1px"; $meastext = "measures"; }
							if ($numdrugs > 0) { $drugcolor = "fill:#FFFFCC,stroke:#444,stroke-width:1px"; $drugtext = "drugs ($numdrugs)"; } else { $drugcolor = "fill:#fff,stroke:#aaa,color:#999,stroke-width:1px"; $drugtext = "drugs"; }
							
						?>
						
						<pre class="mermaid">
							graph LR
								%%root-->package(details);
								data-->subjects("<?=$subjtext?>");
								root-->pipelines("<?=$pipetext?>");
								root-->experiments("<?=$exptext?>");
								%%root-->datadict("<?=$dicttext?>");
								root(package)-->data(data);
								data-->groupanalysis("<?=$groupanalysistext?>");
								subjects-->studies("<?=$studtext?>");
								subjects-->measures("<?=$meastext?>");
								subjects-->drugs("<?=$drugtext?>");
								studies-->series("<?=$sertext?>");
								studies-->analysis("<?=$analysistext?>");
								
								click root href "packages.php?action=editform&packageid=<?=$packageid?>"
								
								style pipelines <?=$pipecolor?>;
								style experiments <?=$expcolor?>;
								%%style datadict <?=$dictcolor?>;
								style groupanalysis <?=$groupanalysiscolor?>;
								style measures <?=$meascolor?>;
								style drugs <?=$drugcolor?>;
								style analysis <?=$analysiscolor?>;
								style subjects <?=$subjcolor?>;
								style studies <?=$studcolor?>;
								style series <?=$sercolor?>;
								style root fill:#fff, stroke:#666;
								%%style package fill:#fff, stroke:#666;
								style data fill:#fff, stroke:#666;
						</pre>
					</div>
				</div>
			</div>
			
			<!-- subjects tab -->
			<div class="ui bottom attached tab raised segment" data-tab="subjects">
			
				<div class="ui message">
					<div class="content">
						<div class="header">
							Object summary
						</div>
						<?=$numsubjects?> Subjects, <?=$numstudies?> Studies, <?=$numseries?> Series<br>
						<?=$totalfiles?> files, <?=HumanReadableFileSize($totalbytes)?>
					</div>				
				</div>
				
				<!-- This tree view is neat, but not very practical for quickly accessing the data -->
				<!--
				<ul class="tree">
				<?
				/*
				ksort($subjects, SORT_NATURAL);
				foreach ($subjects as $uid =>$studies) {
					if ($uid != "") {
						?><li>
							<details>
								<summary><?=$uid?></summary>
								<ul><?
						
						ksort($studies, SORT_NATURAL);
						foreach ($studies as $studynum => $modalities) {
							?><li>
								<details>
									<summary><?=$studynum?></summary>
									<ul><?

							ksort($modalities, SORT_NATURAL);
							foreach ($modalities as $modality => $series) {
								?><li>
									<details>
										<summary><?=$modality?></summary>
										<ul><?
								
								ksort($series, SORT_NATURAL);
								?>
									<table class="ui basic very compact table">
										<thead>
											<th>UID</th>
											<th>StudyNum</th>
											<th>Modality</th>
											<th>SeriesNum</th>
										</thead>
										<?
										foreach ($series as $seriesnum => $seriesid) {
											?>
											<tr>
												<td><?=$uid?></td>
												<td><?=$studynum?></td>
												<td><?=$modality?></td>
												<td><?=$seriesnum?></td>
											</tr>
											<?
										}
										?>
									</table>
								</ul></details></li><?
							}
							?></ul></details></li><?
						}
						?></ul></details></li><?
					}
				} */
				?>
				</ul> -->
				
				<table class="ui very compact table">
					<thead>
						<th>UID</th>
						<th>StudyNum</th>
						<th>Modality</th>
						<th>SeriesNum</th>
					</thead>
				<?
				ksort($subjects, SORT_NATURAL);
				foreach ($subjects as $uid =>$studies) {
					if ($uid != "") {
						
						ksort($studies, SORT_NATURAL);
						foreach ($studies as $studynum => $modalities) {

							ksort($modalities, SORT_NATURAL);
							foreach ($modalities as $modality => $series) {
								
								ksort($series, SORT_NATURAL);
								foreach ($series as $seriesnum => $seriesid) {
									?>
									<tr>
										<td><?=$uid?></td>
										<td><?=$studynum?></td>
										<td><?=$modality?></td>
										<td><?=$seriesnum?></td>
									</tr>
									<?
								}
							}
						}
					}
				}
				?>
				</table>
				
			</div>


			<!-- measures tab -->
			<div class="ui bottom attached tab raised segment" data-tab="measures">

				<div class="ui message">
					<div class="content">
						<div class="header">
							Object summary
						</div>
						<?=$nummeasures?> Measures
					</div>				
				</div>
			
				<? if (count($measures) > 0) { ?>
				<script type="text/javascript">
					$(function() {
						$("#selectallmeasure").click(function() {
							var checked_status = this.checked;
							$(".allmeasure").find("input[type='checkbox']").each(function() {
								this.checked = checked_status;
							});
						});
					});
				</script>
				
				<form method="post" action="packages.php">
				<input type="hidden" name="action" value="removeobject">
				<input type="hidden" name="objecttype" value="measure">
				<input type="hidden" name="packageid" value="<?=$packageid?>">
				<table class="ui basic very compact table">
					<thead>
						<th><input type="checkbox" id="selectallmeasure"></th>
						<th>UID</th>
						<th>Measure</th>
						<th>Value</th>
						<th>Date</th>
					</thead>
				<?
				ksort($measures, SORT_NATURAL);
				foreach ($measures as $uid => $objects) {
					foreach ($objects as $objectid => $measure) {
						$measureid = $measure['measureid'];
						$measurename = $measure['name'];
						$measurevalue = $measure['value'];
						$measuredate = $measure['startdate'];
						?>
						<tr>
							<td class="allmeasure"><input type="checkbox" name="objectids[]" value="<?=$objectid?>"></td>
							<td><?=$uid?></td>
							<td><?=$measurename?></td>
							<td><?=$measurevalue?></td>
							<td><?=$measuredate?></td>
						</tr>
						<?
					}
				}
				?>
				</table>
				<button type="submit" class="ui orange button"><i class="trash icon"></i>Remove selected measures</button>
				</form>
				<? } else { ?>
				No measure objects in this package
				<? } ?>
			</div>

			<!-- drug tab -->
			<div class="ui bottom attached tab raised segment" data-tab="drugs">

				<div class="ui message">
					<div class="content">
						<div class="header">
							Object summary
						</div>
						<?=$numdrugs?> drug records
					</div>				
				</div>
				
				<? if (count($drugs) > 0) { ?>
				<script type="text/javascript">
					$(function() {
						$("#selectalldrug").click(function() {
							var checked_status = this.checked;
							$(".alldrug").find("input[type='checkbox']").each(function() {
								this.checked = checked_status;
							});
						});
					});
				</script>
				
				<form method="post" action="packages.php">
				<input type="hidden" name="action" value="removeobject">
				<input type="hidden" name="objecttype" value="drug">
				<input type="hidden" name="packageid" value="<?=$packageid?>">
				<table class="ui basic very compact table">
					<thead>
						<th><input type="checkbox" id="selectalldrug"></th>
						<th>UID</th>
						<th>Drug</th>
						<th>Date</th>
					</thead>
				<?
				ksort($drugs, SORT_NATURAL);
				foreach ($drugs as $uid => $objects) {
					foreach ($objects as $objectid => $drug) {
						$drugid = $drug['drugid'];
						$drugname = $drug['name'];
						$drugdate = $drug['startdate'];
						?>
						<tr>
							<td class="alldrug"><input type="checkbox" name="objectids[]" value="<?=$objectid?>"></td>
							<td><?=$uid?></td>
							<td><?=$drugname?></td>
							<td><?=$drugdate?></td>
						</tr>
						<?
					}
				}
				?>
				</table>
				<button type="submit" class="ui orange button"><i class="trash icon"></i>Remove selected drugs</button>
				</form>
				<? } ?>
			</div>

			<!-- analysis tab -->
			<div class="ui bottom attached tab raised segment" data-tab="analysis">

				<div class="ui message">
					<div class="content">
						<div class="header">
							Object summary
						</div>
						<?=$numanalysis?> analyses<br>
						<?=$totalanalysisfiles?> files, <?=HumanReadableFileSize($totalanalysisbytes)?>
					</div>				
				</div>
			
				<? if (count($analyses) > 0) { ?>
				<script type="text/javascript">
					$(function() {
						$("#selectallanalysis").click(function() {
							var checked_status = this.checked;
							$(".allanalysis").find("input[type='checkbox']").each(function() {
								this.checked = checked_status;
							});
						});
					});
				</script>
				
				<form method="post" action="packages.php">
				<input type="hidden" name="action" value="removeobject">
				<input type="hidden" name="objecttype" value="analysis">
				<input type="hidden" name="packageid" value="<?=$packageid?>">
				<table class="ui basic very compact table">
					<thead>
						<th><input type="checkbox" id="selectallanalysis"></th>
						<th>UID</th>
						<th>StudyNum</th>
						<th>Pipeline</th>
						<th>Date</th>
						<th>Status</th>
					</thead>
				<?
				ksort($analyses, SORT_NATURAL);
				foreach ($analyses as $uid => $objects) {
					foreach ($objects as $objectid => $analysis) {
						$analysisid = $analysis['analysisid'];
						?>
						<tr>
							<td class="allanalysis"><input type="checkbox" name="objectids[]" value="<?=$objectid?>"></td>
							<td><?=$uid?></td>
							<td><?=$analysis['studynum']?></td>
							<td><?=$analysis['name']?></td>
							<td><?=$analysis['date']?></td>
							<td><?=$analysis['status']?></td>
						</tr>
						<?
					}
				}
				?>
				</table>
				<button type="submit" class="ui orange button"><i class="trash icon"></i>Remove selected analyses</button>
				</form>
				<? } else { ?>
				No analysis objects in this package
				<? } ?>
			</div>
			
			<!-- experiments tab -->
			<div class="ui bottom attached tab raised segment" data-tab="experiments">
			
				<div class="ui message">
					<div class="content">
						<div class="header">
							Object summary
						</div>
						<?=$numexperiments?> experiments
					</div>				
				</div>
			
				<? if (count($experiments) > 0) { ?>
				<script type="text/javascript">
					$(function() {
						$("#selectallexperiment").click(function() {
							var checked_status = this.checked;
							$(".allexperiment").find("input[type='checkbox']").each(function() {
								this.checked = checked_status;
							});
						});
					});
				</script>
				
				<form method="post" action="packages.php">
				<input type="hidden" name="action" value="removeobject">
				<input type="hidden" name="objecttype" value="experiment">
				<input type="hidden" name="packageid" value="<?=$packageid?>">
				<table class="ui basic very compact table">
					<thead>
						<th><input type="checkbox" id="selectallexperiment"></th>
						<th>Name</th>
						<th>Version</th>
						<th>Description</th>
						<th>Create date</th>
						<th>Creator</th>
					</thead>
				<?
				foreach ($experiments as $experimentid => $experiment) {
					?>
					<tr>
						<td class="allexperiment"><input type="checkbox" name="objectids[]" value="<?=$experiment['objectid']?>"></td>
						<td><?=$experiment['name']?></td>
						<td><?=$experiment['version']?></td>
						<td><?=$experiment['desc']?></td>
						<td><?=$experiment['createdate']?></td>
						<td><?=$experiment['creator']?></td>
					</tr>
					<?
				}
				?>
				</table>
				<button type="submit" class="ui orange button"><i class="trash icon"></i>Remove selected experiments</button>
				</form>
				<? } else { ?>
				<div class="ui message">
					No experiment objects in this package
				</div>
				<? } ?>
			</div>
			
			<!-- ********** pipelines tab ********* -->
			<div class="ui bottom attached tab raised segment" data-tab="pipelines">
			
				<div class="ui message">
					<div class="content">
						<div class="header">
							Object summary
						</div>
						<?=$numpipelines?> pipelines
					</div>				
				</div>
			
				<? if (count($pipelines) > 0) { ?>
				<script type="text/javascript">
					$(function() {
						$("#selectallpipeline").click(function() {
							var checked_status = this.checked;
							$(".allpipeline").find("input[type='checkbox']").each(function() {
								this.checked = checked_status;
							});
						});
					});
				</script>
				
				<form method="post" action="packages.php">
				<input type="hidden" name="action" value="removeobject">
				<input type="hidden" name="objecttype" value="pipeline">
				<input type="hidden" name="packageid" value="<?=$packageid?>">
				<table class="ui basic very compact table">
					<thead>
						<th><input type="checkbox" id="selectallpipeline"></th>
						<th>Name</th>
						<th>Version</th>
						<th>Description</th>
						<th>Create date</th>
					</thead>
				<?
				foreach ($pipelines as $pipelineid => $pipeline) {
					?>
					<tr>
						<td class="allpipeline"><input type="checkbox" name="objectids[]" value="<?=$pipeline['objectid']?>"></td>
						<td><?=$pipeline['name']?></td>
						<td><?=$pipeline['version']?></td>
						<td><?=$pipeline['desc']?></td>
						<td><?=$pipeline['createdate']?></td>
					</tr>
					<?
				}
				?>
				</table>
				<button type="submit" class="ui orange button"><i class="trash icon"></i>Remove selected pipelines</button>
				</form>
				<? } else { ?>
				No pipeline objects in this package
				<? } ?>
			</div>
			
			<div class="ui bottom attached tab raised segment" data-tab="datadict">
			</div>
		</div>
			
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayPackageForm ----------------- */
	/* -------------------------------------------- */
	function DisplayPackageForm($packageid, $type) {
	
		/* populate the fields if this is an edit */
		if ($type == "edit") {
			if (!ValidID($packageid,'Package ID'))
				return;
			
			$sqlstring = "select * from packages where package_id = $packageid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$createdate = date('M j, Y h:ia',strtotime($row['package_date']));
			$name = $row['package_name'];
			$desc = $row['package_desc'];
			$subjectDirFormat = $row['package_subjectdirformat'];
			$studyDirFormat = $row['package_studydirformat'];
			$seriesDirFormat = $row['package_seriesdirformat'];
			$dataFormat = $row['package_dataformat'];
			$license = $row['package_license'];
			$readme = $row['package_readme'];
			$changes = $row['package_changes'];
			$notes = $row['package_notes'];
		
			$formaction = "updatepackage";
			$formtitle = "$name";
			$submitbuttonlabel = "Update";
		}
		else {
			$formaction = "addpackage";
			$formtitle = "New Package";
			$submitbuttonlabel = "Add";
		}
		
	?>
		<div class="ui container">
			<div class="ui attached raised tertiary segment">
				<h2 class="header" style="color: #000"><?=$formtitle?></h2>
			</div>
			<form method="post" action="packages.php" class="ui form attached fluid raised segment">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="packageid" value="<?=$packageid?>">

			<div class="field">
				<label>Name</label>
				<div class="field">
					<input type="text" name="packagename" value="<?=$name?>" maxlength="255" required>
				</div>
			</div>

			<div class="field">
				<label>Description</label>
				<div class="field">
					<textarea name="packagedesc" rows="4"><?=$desc?></textarea>
				</div>
			</div>
			<div class="ui grid">
				<div class="six wide column">
					<div class="field">
						<label>Package data format</label>
						<div class="ui selection dropdown">
							<input type="hidden" name="packageformat" value="<?=$dataFormat?>">
							<i class="dropdown icon"></i>
							<div class="default text">Package Data Format</div>
							<div class="scrollhint menu">
								<div class="item" data-value="orig"><b>Original</b> - <span style="font-size: smaller; color: #888">Leave data in original format</span></div>
								<div class="item" data-value="anon"><b>Anonymized</b> - <span style="font-size: smaller; color: #888">Remove PHI containing tags (DICOM only)</span></div>
								<div class="item" data-value="anonfull"><b>Full anonymization</b> - <span style="font-size: smaller; color: #888">Remove all tags, including dates and IDs (DICOM only)</span></div>
								<div class="item" data-value="nifti3d"><b>Nifti 3D</b> - <span style="font-size: smaller; color: #888">Convert any DICOM files to Nifti 3D format (.nii)</span></div>
								<div class="item" data-value="nifti3dgz"><b>Nifti 3D .gz</b> - <span style="font-size: smaller; color: #888">Convert any DICOM files to Nifti 3D gzip format (.nii.gz)</span></div>
								<div class="item" data-value="nifti4d"><b>Nifti 4D</b> - <span style="font-size: smaller; color: #888">Convert any DICOM files to Nifti 4D format (.nii)</span></div>
								<div class="item" data-value="nifti4dgz"><b>Nifti 4D .gz</b> - <span style="font-size: smaller; color: #888">Convert any DICOM files to Nifti 4D gzip format (.nii.gz)</span></div>
							</div>
						</div>
					</div>
				</div>
				<div class="three wide column">
					<div class="field">
						<label>Subject directory format</label>
						<div class="ui selection dropdown">
							<input type="hidden" name="subjectdirformat" value="<?=$subjectDirFormat?>">
							<i class="dropdown icon"></i>
							<div class="default text">Subject directory name</div>
							<div class="scrollhint menu">
								<div class="item" data-value="orig"><b>Original</b> - <span style="font-size: smaller; color: #888">Subject ID</span></div>
								<div class="item" data-value="seq"><b>Sequential</b> - <span style="font-size: smaller; color: #888">0001, 0002 ...</span></div>
							</div>
						</div>
					</div>
				</div>
				<div class="three wide column">
					<div class="field">
						<label>Study directory format</label>
						<div class="ui selection dropdown">
							<input type="hidden" name="studydirformat" value="<?=$studyDirFormat?>">
							<i class="dropdown icon"></i>
							<div class="default text">Study directory name</div>
							<div class="scrollhint menu">
								<div class="item" data-value="orig"><b>Original</b> - <span style="font-size: smaller; color: #888">Study num</span></div>
								<div class="item" data-value="seq"><b>Sequential</b> - <span style="font-size: smaller; color: #888">0001, 0002 ...</span></div>
							</div>
						</div>
					</div>
				</div>
				<div class="four wide column">
					<div class="field">
						<label>Series directory format</label>
						<div class="ui selection dropdown">
							<input type="hidden" name="seriesdirformat" value="<?=$seriesDirFormat?>">
							<i class="dropdown icon"></i>
							<div class="default text">Series directory name</div>
							<div class="scrollhint menu">
								<div class="item" data-value="orig"><b>Original</b> - <span style="font-size: smaller; color: #888">Series num</span></div>
								<div class="item" data-value="seq"><b>Sequential</b> - <span style="font-size: smaller; color: #888">0001, 0002 ...</span></div>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<script>
				$(document).ready(function() {
					$('.menu .item').tab();
					$('.tabular.menu .item').tab();
				});
			</script>
			
			<div class="ui top attached tabular menu">
				<a class="active item" data-tab="readme">Readme</a>
				<a class="item" data-tab="notes">Notes</a>
				<a class="item" data-tab="license">License</a>
				<a class="item" data-tab="changes">Changes</a>
			</div>
			<div class="ui bottom attached active tab segment" data-tab="readme">
				<textarea name="readme"><?=$readme?></textarea>
			</div>
			<div class="ui bottom attached tab segment" data-tab="notes">
				<textarea name="notes"><?=$notes?></textarea>
			</div>
			<div class="ui bottom attached tab segment" data-tab="license">
				<textarea name="license"><?=$license?></textarea>
			</div>
			<div class="ui bottom attached tab segment" data-tab="changes">
				<textarea name="changes"><?=$changes?></textarea>
			</div>
			
			<div class="ui two column grid">
				<div class="column">
					<? if ($type == "edit") { ?>
					<a class="ui red button" href="packages.php?packageid=<?=$packageid?>&action=delete" onclick="return confirm('Are you sure you want to delete this package?')"><i class="trash icon"></i>Delete</a>
					<? } ?>
				</div>
				<div class="column" align="right">
					<a class="ui button" href="packages.php?projectid=<?=$projectid?>">Cancel</a>
					<input type="submit" class="ui primary button" value="<?=$submitbuttonlabel?>">
				</div>
			</div>
		</form>
		<br><br><br><br>
		<br><br><br><br>
		<br><br><br><br>
	<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayPackageList ----------------- */
	/* -------------------------------------------- */
	function DisplayPackageList() {
		
		?>
		<div class="ui container">
		
			<div class="ui two column grid">
				<div class="column">
					<h2 class="ui header">
						<img src="images/squirrel-icon-64.png"></img>
						<div class="content">
							Packages
							<div class="sub header">Squirrel packages</div>
						</div>
					</h2>
				</div>
				<div class="right aligned column">
					<a class="ui primary large button" href="packages.php?action=addform"><i class="plus icon"></i> Create package</a>
				</div>
			</div>
			
			<table class="ui celled selectable grey compact table">
				<thead>
					<tr>
						<th>Name</th>
						<th></th>
						<th>Description</th>
						<th>Create date</th>
						<th>Objects</th>
					</tr>
				</thead>
				<tbody>
					<?
						$sqlstring = "select * from packages where user_id = " . $_SESSION['userid'];
						$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$packageid = $row['package_id'];
							$name = $row['package_name'];
							$desc = $row['package_desc'];
							$createdate = date('M j, Y h:ia',strtotime($row['package_date']));
							
							$numobjects = 0;
							?>
							<tr>
								<td valign="top">
									<a href="packages.php?action=displaypackage&packageid=<?=$packageid?>"><b><?=$name?></b></a>
								</td>
								<td valign="top">
									<a href="packages.php?action=editform&packageid=<?=$packageid?>"><i class="pen icon"></i> Edit</a>
								</td>
								<td valign="top"><?=$desc?></td>
								<td valign="top"><?=$createdate?></td>
								<td valign="top"><?=$numobjects?></td>
							</tr>
							<?
						}
					?>
				</tbody>
			</table>
		</div>
		<?
	}
?>


<? include("footer.php") ?>
