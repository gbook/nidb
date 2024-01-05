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
	$includeanalyses = GetVariable("includeanalyses");
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
			AddObjectsToPackage($packageid, $enrollmentids, $subjectids, $studyids, $seriesids, $modality, $experimentids, $analysisids, $pipelineids, $datadictionaryids, $drugids, $measureids, $includedrugs, $includemeasures, $includeexperiments, $includeanalyses, $includepipelines);
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
			case "subject":
				DisplayAddSubjectForm($objectids);
				break;
			case "study":
				DisplayAddStudyForm($objectids);
				break;
			case "series":
				DisplayAddSeriesForm($objectids, $modality);
				break;
			case "enrollment":
				DisplayAddEnrollmentForm($objectids);
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
	/* ------- DisplayAddSubjectForm -------------- */
	/* -------------------------------------------- */
	function DisplayAddSubjectForm($subjectids) {
		?>
		The following information related to the subject(s) will be added to the package
		
		<pre>
		[x] Subject info
			[ ] details from enrollment
				[ ] Vitals
				[ ] Measures
				[ ] Drugs
		
			[ ] series (and studies)
				[ ] series list
					[ ] experiments
					[ ] analyses
					[ ] pipelines
		</pre>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayAddStudyForm ---------------- */
	/* -------------------------------------------- */
	function DisplayAddStudyForm($studyids) {
		?>
		The following information related to the study(s) will be added to the package
		
		<pre>
		[x] details from enrollment --> Subject info
			[ ] Vitals
			[ ] Measures
			[ ] Drugs
		
		[x] study information (the user wanted this study added, so we'll add the study even if no series are selected)
			[ ] analyses
				[ ] pipelines
		
		[ ] series
			[ ] series list
				[ ] experiments
				[ ] analyses
				[ ] pipelines
		</pre>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayAddSeriesForm --------------- */
	/* -------------------------------------------- */
	function DisplayAddSeriesForm($seriesids, $modality) {
		
		if (count($seriesids > 0)) {
			
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
				if ($row[$modality . '_seriesid'] != "")
					$seriesids[] = $row[$modality . '_seriesid'];
			}
			$enrollmentids = array_unique($enrollmentids);
			$subjectids = array_unique($subjectids);
			$studyids = array_unique($studyids);
			$seriesids = array_unique($seriesids);
			
			$numenrollments = count($enrollmentids);
			$numsubjects = count($subjectids);
			$numstudies = count($studyids);
			$numseries = count($seriesids);
		}
		
		?>
		
		<div class="ui container">
			<div class="ui raised segment">
				
				<form method="post" action="packages.php">
				<input type="hidden" name="action" value="addobjectstopackage">
				<? foreach ($enrollmentids as $enrollmentid) {?>
				<input type="hidden" name="enrollmentids[]" value="<?=$enrollmentid?>">
				<? } ?>
				<? foreach ($subjectids as $subjectid) {?>
				<input type="hidden" name="subjectids[]" value="<?=$subjectid?>">
				<? } ?>
				<? foreach ($studyids as $studyid) {?>
				<input type="hidden" name="studyids[]" value="<?=$studyid?>">
				<? } ?>
				<? foreach ($seriesids as $seriesid) {?>
				<input type="hidden" name="seriesids[]" value="<?=$seriesid?>">
				<? } ?>
				<input type="hidden" name="modality" value="<?=$modality?>">
				
				<h2>The following objects will be added to the package</h2>
				
				<? DisplayFormSubjects($enrollmentids, true); ?>
				<br>
				
				<? DisplayFormStudies($studyids, true); ?>
				<br>

				<? DisplayFormSeries($seriesids, $modality, true); ?>
				
				<h2>Optional related objects</h3>
				<? DisplayFormExperiments($experimentids, false); ?>
				<br>
				<? DisplayFormAnalyses($analysisids, false); ?>
				<br>
				<? DisplayFormPipelines($pipelineids, false); ?>
				<br>
				<? DisplayFormDrugs($drugids, false); ?>
				<br>
				<? DisplayFormMeasures($measureids, false); ?>
				
				<? foreach ($experimentids as $experimentid) {?>
				<input type="hidden" name="experimentids[]" value="<?=$experimentid?>">
				<? } ?>

				<? foreach ($analysisids as $analysisid) {?>
				<input type="hidden" name="analysisids[]" value="<?=$analysisid?>">
				<? } ?>

				<? foreach ($pipelineids as $pipelineid) {?>
				<input type="hidden" name="pipelineids[]" value="<?=$pipelineid?>">
				<? } ?>

				<? foreach ($drugids as $drugid) {?>
				<input type="hidden" name="drugids[]" value="<?=$drugid?>">
				<? } ?>

				<? foreach ($measureids as $measureid) {?>
				<input type="hidden" name="measureids[]" value="<?=$measureid?>">
				<? } ?>

					<br><br>
					
					<h4 class="ui horizontal divider header">Select Package</h4>
					
					<div style="text-align: center">
						<div class="ui selection dropdown">
							<input type="hidden" name="packageid" required>
							<i class="dropdown icon"></i>
							<div class="default text">Select package</div>
							<div class="scrollhint menu">
						<?				
							$sqlstring = "select * from packages where user_id = " . $_SESSION['userid'];
							$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$packageid = $row['package_id'];
								$name = $row['package_name'];
								$desc = $row['package_desc'];
								$createdate = date('M j, Y h:ia',strtotime($row['package_date']));
								?>
								<div class="item" data-value="<?=$packageid?>"><?=$name?></div>
								<?
							}
						?>
							</div>
						</div>

						<br><br>
						<input type="submit" value="Add to package" class="ui primary button">
					</div>
				</form>
			</div>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayAddEnrollmentForm ----------- */
	/* -------------------------------------------- */
	function DisplayAddEnrollmentForm($enrollmentids) {
		?>
		The following information related to the enrollment(s) will be added to the package
		
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
		The following information related to the experiment(s) will be added to the package
		
		<pre>
			[x] experiment list
		</pre>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayAddPipelineForm ------------- */
	/* -------------------------------------------- */
	function DisplayAddPipelineForm($pipelineids) {
		?>
		The following information related to the pipeline(s) will be added to the package
		
		<pre>
			[x] pipeline
				[ ] analyses
		</pre>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayAddAnalysisForm ------------- */
	/* -------------------------------------------- */
	function DisplayAddAnalysisForm($analysisids) {
		?>
		The following information related to the analysis(s) will be added to the package
		
		<pre>
			[*] analyses
		</pre>
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


	/* -------------------------------------------------------------------------------------
	    The following functions display a list of objects from the list of input IDs
		Functions display HTML that contains <input> elements, but do not contain
		any <form></form> elements
	   ------------------------------------------------------------------------------------- */

	/* -------------------------------------------- */
	/* ------- DisplayFormSubjects ---------------- */
	/* -------------------------------------------- */
	/* this function expects a list of enrollment IDs */
	function DisplayFormSubjects($enrollmentids, $required) {
		$numsubjects = count($enrollmentids);
		
		?>
			<span style="font-size:larger"><i class="check circle outline icon"></i> <b>Subjects</b></span> <div class="ui blue basic label"><?=$numsubjects?> of <?=$numsubjects?> subjects will be added</div>
			<div class="ui accordion">
				<div class="title">
					<i class="dropdown icon"></i>
					View subjects
				</div>
				<div class="content">
					<div class="ui segment">
						<table class="ui very compact collapsing table">
							<thead>
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
									$uid = $row['uid'];
									$subjectid = $row['subject_id'];
									$sex = $row['sex'];
									$projectname = $row['project_name'];
									
									?>
										<tr>
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
			</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayFormStudies ----------------- */
	/* -------------------------------------------- */
	function DisplayFormStudies($studyids, $required) {
		?>
			<span style="font-size:larger"><i class="check circle outline icon"></i> <b>Studies</b></span> <div class="ui blue basic label"><?=$numstudies?> of <?=$numstudies?> studies will be added</div>
			<div class="ui accordion">
				<div class="title">
					<i class="dropdown icon"></i>
					View studies
				</div>
				<div class="content">
					<div class="ui segment">
						<table class="ui very compact collapsing table">
							<thead>
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
			</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayFormSeries ------------------ */
	/* -------------------------------------------- */
	function DisplayFormSeries($seriesids, $modality, $required) {
		$numseries = count($seriesids);
		?>
			<span style="font-size:larger"><i class="check circle outline icon"></i> <b>Series</b></span> <div class="ui blue basic label"><?=$numseries?> of <?=$numseries?> series will be added</div>
			<div class="ui accordion">
				<div class="title">
					<i class="dropdown icon"></i>
					View series
				</div>
				<div class="content">
					<table class="ui very compact table">
						<thead>
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
							$seriesidstr = implode2(",", $seriesids);
						
							$sqlstring = "select * from $modality" . "_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.$modality" . "series_id in (" . $seriesidstr . ")";
							$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								//PrintVariable($row);
								$uid = $row['uid'];
								$studynum = $row['study_num'];
								$studydesc = $row['study_desc'];
								$seriesnum = $row['series_num'];
								$seriesdesc = $row['series_desc'];
								$seriessize = $row['series_size'];
								$seriesnumfiles = $row['numfiles'];
								
								?>
									<tr>
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
						?>
						</tbody>
					</table>
				</div>
			</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayFormExperiments ------------- */
	/* -------------------------------------------- */
	function DisplayFormExperiments($experimentids, $required) {
		
		$experimentidstr = implode2(",", $experimentids);
		
		?>
			<div class="ui checkbox">
				<input type="checkbox" name="includeexperiments" value="1">
				<label>Experiments</label>
			</div>
			<div class="ui accordion">
				<div class="title">
					<i class="dropdown icon"></i>
					View experiments
				</div>
				<div class="content">
					<table class="ui very compact collapsing table">
						<thead>
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


	/* -------------------------------------------- */
	/* ------- DisplayFormAnalyses ---------------- */
	/* -------------------------------------------- */
	function DisplayFormAnalyses($analysisids, $required) {

		$analysisidstr = implode2(",", $analysisids);
		
		?>
			<div class="ui checkbox">
				<input type="checkbox" name="includeanalyses" value="1">
				<label>Analyses</label>
			</div>
			<div class="ui accordion">
				<div class="title">
					<i class="dropdown icon"></i>
					View analyses
				</div>
				<div class="content">
					<table class="ui very compact collapsing table">
						<thead>
							<th>Analysis</th>
							<th>Date</th>
						</thead>
						<tbody>
						<?
							/* get subject info. there may be series from multiple subjects in this list */
							$sqlstring = "select * from analysis where analysis_id in (" . $analysisidstr . ")";
							$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$analysisid = $row['analysis_id'];
								$analysisdate = $row['analysis_date'];
								?>
									<tr>
										<td><a href="analysis.php?analysisid=<?=$analysisid?>"><?=$analysisid?></a></td>
										<td><?=$analysisdate?></td>
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


	/* -------------------------------------------- */
	/* ------- DisplayFormPipelines --------------- */
	/* -------------------------------------------- */
	function DisplayFormPipelines($pipelineids, $required) {

		$pipelineidstr = implode2(",", $pipelineids);

		?>
			<div class="ui checkbox">
				<input type="checkbox" name="includepipelines" value="1">
				<label>Pipelines</label>
			</div>
			<div class="ui accordion">
				<div class="title">
					<i class="dropdown icon"></i>
					View pipelines
				</div>
				<div class="content">
					<table class="ui very compact collapsing table">
						<thead>
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


	/* -------------------------------------------- */
	/* ------- DisplayFormMeasures ---------------- */
	/* -------------------------------------------- */
	function DisplayFormMeasures($measureids, $required) {

		$measureidstr = implode2(",", $measureids);
		
		?>
			<div class="ui checkbox" title="Include all measures for all selected subjects">
				<input type="checkbox" name="includemeasures" value="1">
				<label>Measures</label>
			</div>
			<div class="ui accordion">
				<div class="title">
					<i class="dropdown icon"></i>
					View measures
				</div>
				<div class="content">
					<table class="ui very compact collapsing table">
						<thead>
							<th>UID</th>
							<th>Measure</th>
							<th>Date</th>
						</thead>
						<tbody>
						<?
							/* get subject info. there may be series from multiple subjects in this list */
							$sqlstring = "select * from measures a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id left join measurenames d on a.measurename_id = d.measurename_id where a.enrollment_id in (" . implode2(",", $enrollmentids) . ")";
							$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$uid = $row['uid'];
								$subjectid = $row['subject_id'];
								$mesauredate = $row['measure_startdate'];
								$measureid = $row['measure_id'];
								$measurename = $row['measure_name'];
								
								$measureids[] = $measureid;
								?>
									<tr>
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


	/* -------------------------------------------- */
	/* ------- DisplayFormDrugs ------------------- */
	/* -------------------------------------------- */
	function DisplayFormDrugs($drugids, $required) {
		
		$drugidstr = implode2(",", $drugids);
		
		?>
			<div class="ui checkbox" title="Include all drug records for all selected subjects">
				<input type="checkbox" name="includedrugs" value="1">
				<label>Drugs</label>
			</div>
			<div class="ui accordion">
				<div class="title">
					<i class="dropdown icon"></i>
					View drugs
				</div>
				<div class="content">
					<table class="ui very compact collapsing table">
						<thead>
							<th>UID</th>
							<th>Drug</th>
							<th>Dose desc</th>
							<th>Date</th>
						</thead>
						<tbody>
						<?
							/* get subject info. there may be series from multiple subjects in this list */
							$sqlstring = "select * from drugs a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id left join drugnames d on a.drugname_id = d.drugname_id where a.enrollment_id in (" . implode2(",", $enrollmentids) . ")";
							$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
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

	/* -------------------------------------------- */
	/* ------- AddObjectsToPackage ---------------- */
	/* -------------------------------------------- */
	function AddObjectsToPackage($packageid, $enrollmentids, $subjectids, $studyids, $seriesids, $modality, $experimentids, $analysisids, $pipelineids, $datadictionaryids, $drugids, $measureids, $includedrugs, $includemeasures, $includeexperiments, $includeanalyses, $includepipelines) {

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
		$includeanalyses = mysqli_real_escape_string($GLOBALS['linki'], $includeanalyses);
		$includepipelines = mysqli_real_escape_string($GLOBALS['linki'], $includepipelines);
		
		/* add any enrollments */
		if (count($enrollmentids) > 0) {
			foreach ($enrollmentids as $enrollmentid) {
				$sqlstring = "insert ignore into package_enrollments (package_id, enrollment_id) values ($packageid, $enrollmentid)";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
			$numobjects += count($enrollmentids);
			$msg .= "Added " . count($enrollmentids) . " enrollments<br>";
		}

		/* add any series */
		if (count($seriesids) > 0) {
			foreach ($seriesids as $seriesid) {
				$sqlstring = "insert ignore into package_series (package_id, modality, series_id) values ($packageid, '$modality', $seriesid)";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
			$numobjects += count($seriesids);
			$msg .= "Added " . count($seriesids) . " series<br>";
		}

		/* add any experiments */
		if ((count($experimentids) > 0) && ($includeexperiments)) {
			foreach ($experimentids as $experimentid) {
				$sqlstring = "insert ignore into package_experiments (package_id, experiment_id) values ($packageid, $experimentid)";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
			$numobjects += count($experimentids);
			$msg .= "Added " . count($experimentids) . " experiments<br>";
		}

		/* add any analyses */
		if ((count($analysisids) > 0) && ($includeanalyses)) {
			foreach ($analysisids as $analysisid) {
				$sqlstring = "insert ignore into package_analyses (package_id, analysis_id) values ($packageid, $analysisid)";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
			$numobjects += count($analysisids);
			$msg .= "Added " . count($analysisids) . " analyses<br>";
		}
		
		/* add any pipelines */
		if ((count($pipelineids) > 0) && ($includepipelines)) {
			foreach ($pipelineids as $pipelineid) {
				$sqlstring = "insert ignore into package_pipelines (package_id, pipeline_id) values ($packageid, $pipelineid)";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
			$numobjects += count($pipelineids);
			$msg .= "Added " . count($pipelineids) . " pipelines<br>";
		}

		/* add any drugs */
		if ((count($drugids) > 0) && ($includedrugs)) {
			foreach ($drugids as $drugid) {
				$sqlstring = "insert ignore into package_drugs (package_id, drug_id) values ($packageid, $drugid)";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
			$numobjects += count($drugids);
			$msg .= "Added " . count($drugids) . " drugs<br>";
		}
		
		/* add any measures */
		if ((count($measureids) > 0) && ($includemeasures)) {
			foreach ($measureids as $measureid) {
				$sqlstring = "insert ignore into package_measures (package_id, measure_id) values ($packageid, $measureid)";
				//PrintSQL($sqlstring);
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
			
			if (contains($optionflags, 'DRUGS')) {
				$subjects[$uid]['drugs'] = GetDrugsByEnrollment($enrollmentid);
			}
			if (contains($optionflags, 'MEASURES')) {
				$subjects[$uid]['measures'] = GetVitalsByEnrollment($enrollmentid);
				$subjects[$uid]['measures'] .= GetMeasuresByEnrollment($enrollmentid);
			}
			
		}
		
		/* get series data */
		$sqlstring = "select * from package_series where package_id = $packageid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$packageseriesid = $row['packageseries_id'];
			$modality = $row['modality'];
			$seriesid = $row['series_id'];
			list($path, $uid, $studynum, $seriesnum, $studyid, $subjectid, $modality, $type, $studydatetime, $enrollmentid, $projectname, $projectid) = GetSeriesInfo($seriesid, $modality);
			
			$subjects[$uid][$studynum][$seriesnum] = "$modality-$seriesid";
		}
		
		/* get experiments */
		$sqlstring = "select * from package_experiments a left join experiments b on a.experiment_id = b.experiment_id where a.package_id = $packageid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$packageexperimentid = $row['packageexperiment_id'];
			$experimentid = $row['experiment_id'];
			$experiments[$experimentid]['name'] = $row['exp_name'];
			$experiments[$experimentid]['version'] = $row['exp_version'];
			$experiments[$experimentid]['desc'] = $row['exp_desc'];
			$experiments[$experimentid]['createdate'] = $row['exp_createdate'];
			$experiments[$experimentid]['creator'] = $row['exp_creator'];
		}
		
		/* get pipelines */
		$sqlstring = "select * from package_pipelines a left join pipelines b on a.pipeline_id = b.pipeline_id where a.package_id = $packageid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$packagepipelineid = $row['packagepipeline_id'];
			$pipelineid = $row['pipeline_id'];
			$pipelines[$pipelineid]['name'] = $row['pipeline_name'];
			$pipelines[$pipelineid]['version'] = $row['pipeline_version'];
			$pipelines[$pipelineid]['desc'] = $row['pipeline_desc'];
			$pipelines[$pipelineid]['createdate'] = $row['pipeline_createdate'];
		}
		
		/* get counts of all of the data objects */
		$numsubjects = count($subjects);
		$numstudies = 0;
		$numseries = 0;
		$nummeasures = 0;
		$numdrugs = 0;
		foreach ($subjects as $uid => $study) {
			$numstudies += count($study);
			foreach ($study as $ser => $series) {
				$numseries += count($series);
			}
			$nummeasures += count($subjects[$uid]['measures']);
			$numdrugs += count($subjects[$uid]['drugs']);
		}
		$numanalyses = count($analyses);
		$numexperiments = count($experiments);
		$numpipelines = count($pipelines);
		$numdatadict = count($datadictionaries);
		$numgroupanalyses = count($groupanalyses);
		
		?>
		<div class="ui container">
			<div class="segment">
				<div class="ui header">
					<em data-emoji=":chipmunk:" class="medium"></em>
					<h2 class="content"><?=$name?></h2>
					<div class="sub header"><?=$desc?></div>
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
			
			<div class="ui top attached large tabular menu">
				<a class="active item item2" data-tab="overview">Package overview</a>
				<a class="item item2" data-tab="subjects">Subjects</a>
				<a class="item item2" data-tab="experiments">Experiments</a>
				<a class="item item2" data-tab="pipelines">Pipelines</a>
				<a class="item item2" data-tab="datadict">Data dictionary</a>
			</div>
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
							if ($numdatadict > 0) { $dictcolor = "fill:#FFFFCC,stroke:#444,stroke-width:1px"; $dicttext = "data-dictionary ($numdatadict)"; } else { $dictcolor = "fill:#fff,stroke:#aaa,color:#999,stroke-width:1px"; $dicttext = "data-dictionary"; }
							if ($numanalyses > 0) { $analysiscolor = "fill:#FFFFCC,stroke:#444,stroke-width:1px"; $analysistext = "analysis ($numanalyses)"; } else { $analysiscolor = "fill:#fff,stroke:#aaa,color:#999,stroke-width:1px"; $analysistext = "analysis"; }
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
								root-->datadict("<?=$dicttext?>");
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
								style datadict <?=$dictcolor?>;
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
			<div class="ui bottom attached tab raised segment" data-tab="subjects">
				<?
				PrintVariable($subjects);
				?>
			</div>
			<div class="ui bottom attached tab raised segment" data-tab="experiments">
				<?
				PrintVariable($experiments);
				?>
			</div>
			<div class="ui bottom attached tab raised segment" data-tab="pipelines">
				<?
				PrintVariable($pipelines);
				?>
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
			<a class="ui primary large button" href="packages.php?action=addform"><i class="plus square outline icon"></i> Create package</a>
			<br><br>
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
									<a href="packages.php?action=editform&packageid=<?=$packageid?>">Edit</a>
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
