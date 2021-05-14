<?
 // ------------------------------------------------------------------------------
 // NiDB analysisbuilder.php
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
	//ob_implicit_flush();

	define("LEGIT_REQUEST", true);
	
	session_start();
	require "functions.php";
	require "includes_php.php";
	
	//PrintVariable($_POST);
	//PrintVariable($_SESSION);
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$projectid = GetVariable("projectid");
	$enrollmentid = GetVariable("enrollmentid");

	$savedsearchname = GetVariable("savedsearchname");
	$savedsearchid = GetVariable("savedsearchid");
	
	$a = array(); /* have the variable ready in case we're loading a saved search */
	
	$a['mr_protocols'] = GetVariable("mr_protocols");
    $a['eeg_protocols'] = GetVariable("eeg_protocols");
    $a['et_protocols'] = GetVariable("et_protocols");
    $a['pipelineid'] = GetVariable("pipelineid");
    $a['pipelineresultname'] = GetVariable("pipelineresultname");
    $a['pipelineseriesdatetime'] = GetVariable("pipelineseriesdatetime");
    $a['includeprotocolparms'] = GetVariable("includeprotocolparms");
    $a['includemrqa'] = GetVariable("includemrqa");
    $a['groupmrbyvisittype'] = GetVariable("groupmrbyvisittype");
    $a['includeallmeasures'] = GetVariable("includeallmeasures");
    $a['measurename'] = GetVariable("measurename");
    $a['includeallvitals'] = GetVariable("includeallvitals");
    $a['vitalname'] = GetVariable("vitalname");
    $a['includealldrugs'] = GetVariable("includealldrugs");
    $a['includedrugdetails'] = GetVariable("includedrugdetails");
    $a['drugname'] = GetVariable("drugname");
    $a['includetimesincedose'] = GetVariable("includetimesincedose");
    $a['dosevariable'] = GetVariable("dosevariable");
    $a['dosetimerange'] = GetVariable("dosetimerange");
    $a['dosedisplaytime'] = GetVariable("dosedisplaytime");
    $a['groupbydate'] = GetVariable("groupbydate");
    $a['includeemptysubjects'] = GetVariable("includeemptysubjects");
    $a['blankvalueplaceholder'] = GetVariable("blankvalueplaceholder");
    $a['missingvalueplaceholder'] = GetVariable("missingvalueplaceholder");
    $a['includeduration'] = GetVariable("includeduration");
    $a['includeenddate'] = GetVariable("includeenddate");
    $a['includeheightweight'] = GetVariable("includeheightweight");
    $a['includedob'] = GetVariable("includedob");
    $a['reportformat'] = GetVariable("reportformat");
    $a['outputformat'] = GetVariable("outputformat");
	$a['collapsevariables'] = GetVariable("collapsevariables");
	$a['collapsebyexpression'] = GetVariable("collapsebyexpression");
	
	/* determine action */
	switch ($action) {
		case 'savesearch':
			SaveSearch($projectid, $savedsearchname, $a);
			break;
		case 'deletesavedsearch':
			DeleteSavedSearch($savedsearchid);
			break;
		case 'usesavedsearch':
			$a = LoadSavedSearch($savedsearchid);
			$projectid = $a['projectid'];
			break;
	}
	
	/* perform the default operation for this page, which is to display the search criteria and results */
	if ($a['outputformat'] == "csv") {
		if ($a['reportformat'] == "long") {
			list($h, $t, $n) = CreateLongReport($projectid, $a);
			PrintCSV($h,$t,'long');
		}
		else {
			list($h, $t, $n) = CreateWideReport($projectid, $a);
			PrintCSV($h,$t,'wide');
		}
		exit(0);
	}
	?>

	<html>
		<head>
			<link rel="icon" type="image/png" href="images/squirrel.png">
			<title>NiDB - Analysis report builder</title>
		</head>

	<body>
		<div id="wrapper">
	<?
	/* requires don't work inside of functions */
	require "includes_html.php";
	require "menu.php";
	DisplayAnalysisSummaryBuilder($projectid, $savedsearchid, $a);
	
	
	/* ------------------------------------ functions ------------------------------------ */

	/* -------------------------------------------- */
	/* ------- DeleteSavedSearch ------------------ */
	/* -------------------------------------------- */
	function DeleteSavedSearch($savedsearchid) {
		$savedsearchid = mysqli_real_escape_string($GLOBALS['linki'], $savedsearchid);
		
		$sqlstring = "delete from saved_search where savedsearch_id = $savedsearchid";
		PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);

		echo "Search saved deleted [$savedsearchid]<br>";
	}
	
	
	/* -------------------------------------------- */
	/* ------- SaveSearch ------------------------- */
	/* -------------------------------------------- */
	function SaveSearch($projectid, $savedsearchname, $a) {
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], $projectid);
		$savedSearchName = mysqli_real_escape_string($GLOBALS['linki'], $savedsearchname);
		$MRprotocols = implode2(",", mysqli_real_escape_array($a['mr_protocols']));
		$EEGprotocols = implode2(",", mysqli_real_escape_array($a['eeg_protocols']));
		$ETprotocols = implode2(",", mysqli_real_escape_array($a['et_protocols']));
		$pipelineid = mysqli_real_escape_string($GLOBALS['linki'], $a['pipelineid']);
		$pipelineresultname = mysqli_real_escape_string($GLOBALS['linki'], $a['pipelineresultname']);
		$pipelineseriesdatetime = mysqli_real_escape_string($GLOBALS['linki'], $a['pipelineseriesdatetime']);
		$includeprotocolparms = mysqli_real_escape_string($GLOBALS['linki'], $a['includeprotocolparms']);
		$includemrqa = mysqli_real_escape_string($GLOBALS['linki'], $a['includemrqa']);
		$groupmrbyvisittype = mysqli_real_escape_string($GLOBALS['linki'], $a['groupmrbyvisittype']);
		$includeallmeasures = mysqli_real_escape_string($GLOBALS['linki'], $a['includeallmeasures']);
		$includeallvitals = mysqli_real_escape_string($GLOBALS['linki'], $a['includeallvitals']);
		$includedrugdetails = mysqli_real_escape_string($GLOBALS['linki'], $a['includedrugdetails']);
		$includetimesincedose = mysqli_real_escape_string($GLOBALS['linki'], $a['includetimesincedose']);
		$doseVariable = mysqli_real_escape_string($GLOBALS['linki'], $a['dosevariable']);
		$doseTimeRange = mysqli_real_escape_string($GLOBALS['linki'], $a['dosetimerange']);
		$doseDisplayTime = mysqli_real_escape_string($GLOBALS['linki'], $a['dosedisplaytime']);
		$groupByDate = mysqli_real_escape_string($GLOBALS['linki'], $a['groupbydate']);
		$includeemptysubjects = mysqli_real_escape_string($GLOBALS['linki'], $a['includeemptysubjects']);
		$reportformat = mysqli_real_escape_string($GLOBALS['linki'], $a['reportformat']);
		$outputformat = mysqli_real_escape_string($GLOBALS['linki'], $a['outputformat']);
		$measurename = mysqli_real_escape_string($GLOBALS['linki'], $a['measurename']);
		$vitalname = mysqli_real_escape_string($GLOBALS['linki'], $a['vitalname']);
		$drugname = mysqli_real_escape_string($GLOBALS['linki'], $a['drugname']);
		$includealldrugs = mysqli_real_escape_string($GLOBALS['linki'], $a['includealldrugs']);
		$blankValue = mysqli_real_escape_string($GLOBALS['linki'], $a['blankvalueplaceholder']);
		$missingValue = mysqli_real_escape_string($GLOBALS['linki'], $a['missingvalueplaceholder']);
		$includeduration = mysqli_real_escape_string($GLOBALS['linki'], $a['includeduration']);
		$includeenddate = mysqli_real_escape_string($GLOBALS['linki'], $a['includeenddate']);
		$includeheightweight = mysqli_real_escape_string($GLOBALS['linki'], $a['includeheightweight']);
		$includedob = mysqli_real_escape_string($GLOBALS['linki'], $a['includedob']);
		$collapsevariables = mysqli_real_escape_string($GLOBALS['linki'], $a['collapsevariables']);
		$collapsebyexpression = mysqli_real_escape_string($GLOBALS['linki'], $a['collapsebyexpression']);

		$userid = $_SESSION['userid'];
		
		if ($pipelineid == "") $pipelineid = "null";
		if ($includeprotocolparms == "") $includeprotocolparms = "null";
		if ($includemrqa == "") $includemrqa = "null";
		if ($groupmrbyvisittype == "") $groupmrbyvisittype = "null";
		if ($includeallmeasures == "") $includeallmeasures = "null";
		if ($includeallvitals == "") $includeallvitals = "null";
		if ($includedrugdetails == "") $includedrugdetails = "null";
		if ($includetimesincedose == "") $includetimesincedose = "null";
		if ($includeemptysubjects == "") $includeemptysubjects = "null";
		if ($includealldrugs == "") $includealldrugs = "null";
		if ($includeenddate == "") $includeenddate = "null";
		if ($includeheightweight == "") $includeheightweight = "null";
		if ($includedob == "") $includedob = "null";
		if ($includeduration == "") $includeduration = "null";
		if ($collapsevariables == "") $collapsevariables = "null";

		$sqlstring = "insert into saved_search (
		user_id,saved_datetime, saved_name, search_projectid, search_mrincludeprotocolparams, search_mrincludeqa, search_groupmrbyvisittype, search_mrprotocol, search_eegprotocol, search_etprotocol, search_pipelineid, search_pipelineresultname, search_pipelineseries, search_measurename, search_includeallmeasures, search_vitalname, search_includeallvitals, search_drugname, search_includealldrugs, search_includedrugdetails, search_includetimesincedose, search_dosevariable, search_groupdosetime, search_displaytime, search_groupbyeventdate, search_collapsevariables, search_collapseexpression, search_includeemptysubjects, search_blankvalue, search_missingvalue, search_includeeventduration, search_includeendate, search_includeheightweight, search_includedob, search_reportformat, search_outputformat)
		values (
			'$userid',
			now(), 
			'$savedSearchName',
			'$projectid',
			$includeprotocolparms,
			$includemrqa,
			'$groupmrbyvisittype',
			'$MRprotocols',
			'$EEGprotocols',
			'$ETprotocols',
			$pipelineid,
			'$pipelineresultname',
			'$pipelineseriesdatetime',
			'$measurename',
			$includeallmeasures,
			'$vitalname',
			$includeallvitals,
			'$drugname',
			$includealldrugs,
			$includedrugdetails,
			$includetimesincedose,
			'$doseVariable',
			'$doseTimeRange',
			'$doseDisplayTime',
			'$groupByDate',
			'$collapsevariables',
			'$collapsebyexpression',
			$includeemptysubjects,
			'$blankValue',
			'$missingValue',
			$includeduration,
			$includeenddate,
			$includeheightweight,
			$includedob,
			'$reportformat',
			'$outputformat'
		)";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);

		Notice("Search saved <b>$savedsearchname</b>");
	}
	
	
	/* -------------------------------------------- */
	/* ------- LoadSavedSearch -------------------- */
	/* -------------------------------------------- */
	function LoadSavedSearch($savedsearchid) {
		$savedsearchid = mysqli_real_escape_string($GLOBALS['linki'], $savedsearchid);
		
		$sqlstring = "select * from saved_search where savedsearch_id = $savedsearchid";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);

		$a['projectid'] = $row['search_projectid'];
		$a['mr_protocols'] = explode(",", $row['search_mrprotocol']);
		$a['eeg_protocols'] = explode(",", $row['search_eegprotocol']);
		$a['et_protocols'] = explode(",", $row['search_etprotocol']);
		$a['pipelineid'] = $row['search_pipelineid'];
		$a['pipelineresultname'] = $row['search_pipelineresultname'];
		$a['pipelineseriesdatetime'] = $row['search_pipelineseries'];
		$a['includeprotocolparms'] = $row['search_mrincludeprotocolparams'];
		$a['includemrqa'] = $row['search_mrincludeqa'];
		$a['groupmrbyvisittype'] = $row['search_groupmrbyvisittype'];
		$a['includeallmeasures'] = $row['search_includeallmeasures'];
		$a['measurename'] = $row['search_measurename'];
		$a['includeallvitals'] = $row['search_includeallvitals'];
		$a['vitalname'] = $row['search_vitalname'];
		$a['includealldrugs'] = $row['search_includealldrugs'];
		$a['includedrugdetails'] = $row['search_includedrugdetails'];
		$a['drugname'] = $row['search_drugname'];
		$a['includetimesincedose'] = $row['search_includetimesincedose'];
		$a['dosevariable'] = $row['search_dosevariable'];
		$a['dosetimerange'] = $row['search_groupdosetime'];
		$a['dosedisplaytime'] = $row['search_displaytime'];
		$a['groupbydate'] = $row['search_groupbyeventdate'];
		$a['includeemptysubjects'] = $row['search_includeemptysubjects'];
		$a['reportformat'] = $row['search_reportformat'];
		$a['outputformat'] = $row['search_outputformat'];
		$a['collapsevariables'] = $row['search_collapsevariables'];
		$a['collapsebyexpression'] = $row['search_collapseexpression'];
		$a['blankvalueplaceholder'] = $row['search_blankvalue'];
		$a['missingvalueplaceholder'] = $row['search_missingvalue'];
		
		$a['includeduration'] = $row['search_includeeventduration'];
		$a['includeenddate'] = $row['search_includeendate'];
		$a['includeheightweight'] = $row['search_includeheightweight'];
		$a['includedob'] = $row['search_includedob'];
		
		
		//PrintVariable($a);
		return $a;
	}
	

	/* -------------------------------------------- */
	/* ------- DisplayAnalysisSummaryBuilder ------ */
	/* -------------------------------------------- */
	function DisplayAnalysisSummaryBuilder($projectid, $savedid, $a) {
		
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], $projectid);

		if ($a['blankvalueplaceholder'] == "")
			$a['blankvalueplaceholder'] = "BlankValue";
		//if ($a['missingvalueplaceholder'] == "")
		//	$a['missingvalueplaceholder'] = "MissingValue";
		
		?>
		<div style="text-align: center; width: 100%" id="pageloading">
			<i class="large blue spinner loading icon"></i> Loading...
		</div>
		<script>
			$(document).ready(function(){
				$('#pageloading').hide();
			});
		
			$(document).ready(function() {
				$('.js-example-basic-multiple').select2();
			});

			window.onload = function exampleFunction() { 
				console.log('The Script will load now.');
				CheckForDrugCriteria();
				CheckForEEGCriteria();
				CheckForETCriteria();
				CheckForMRICriteria();
				CheckForMeasureCriteria();
				CheckForPipelineCriteria();
				CheckForVitalCriteria();
			} 
			
			/* MRI */
			function CheckForMRICriteria() {
				if ( (document.getElementById("includeprotocolparms").checked == true) || (document.getElementById("includemrqa").checked == true) || (document.getElementById("groupmrbyvisittype").checked == true) || (document.getElementById("mr_protocols").value != "") ) {
					document.getElementById("mriIndicator").innerHTML = "<div class='ui small yellow label'><i class='black tasks icon'></i>has search criteria</div>";
				}
				else {
					document.getElementById("mriIndicator").innerHTML = "";
				}
			}
			
			/* EEG */
			function CheckForEEGCriteria() {
				if (document.getElementById("eeg_protocols").value != "") {
					document.getElementById("eegIndicator").innerHTML = "<div class='ui small yellow label'><i class='black tasks icon'></i>has search criteria</div>";
				}
				else {
					document.getElementById("eegIndicator").innerHTML = "";
				}
			}
			
			/* ET */
			function CheckForETCriteria() {
				if (document.getElementById("et_protocols").value != "") {
					document.getElementById("etIndicator").innerHTML = "<div class='ui small yellow label'><i class='black tasks icon'></i>has search criteria</div>";
				}
				else {
					document.getElementById("etIndicator").innerHTML = "";
				}
			}
			
			/* pipeline */
			function CheckForPipelineCriteria() {
				if ( (document.getElementById("pipelineresultname").value != "") || (document.getElementById("pipelineseriesdatetime").value != "") ) {
					document.getElementById("pipelineIndicator").innerHTML = "<div class='ui small yellow label'><i class='black tasks icon'></i>has search criteria</div>";
				}
				else {
					document.getElementById("pipelineIndicator").innerHTML = "";
				}
			}
			
			/* measure */
			function CheckForMeasureCriteria() {
				if ((document.getElementById("measurename").value != "") || (document.getElementById("includeallmeasures").checked == true) ) {
					document.getElementById("measureIndicator").innerHTML = "<div class='ui small yellow label'><i class='black tasks icon'></i>has search criteria</div>";
				}
				else {
					document.getElementById("measureIndicator").innerHTML = "";
				}
			}

			/* vital */
			function CheckForVitalCriteria() {
				if ((document.getElementById("vitalname").value != "") || (document.getElementById("includeallvitals").checked == true) ) {
					document.getElementById("vitalIndicator").innerHTML = "<div class='ui small yellow label'><i class='black tasks icon'></i>has search criteria</div>";
				}
				else {
					document.getElementById("vitalIndicator").innerHTML = "";
				}
			}
			
			/* drugs */
			function CheckForDrugCriteria() {
				if ((document.getElementById("drugname").value != "") || (document.getElementById("includealldrugs").checked == true) || (document.getElementById("includedrugdetails").checked == true) || (document.getElementById("includetimesincedose").checked == true) || (document.getElementById("dosevariable").checked == true) ) {
					document.getElementById("drugIndicator").innerHTML = "<div class='ui small yellow label'><i class='black tasks icon'></i>has search criteria</div>";
				}
				else {
					document.getElementById("drugIndicator").innerHTML = "";
				}
			}
			
		</script>
		<style>
			.indicator { font-size: smaller; padding-left: 10px; white-space: nowrap; }
			details { border: 1px solid #444; border-radius: 6px; padding: 0px; margin: 5px; }
			summary { border: none; background-color: #444; color: #fff; outline: none; border-radius: 5px; padding:4px; }
			summary:hover { border: none; background-color: #444; color: #fff; outline: none; border-radius: 5px; padding:4px; }
			summary:focus { border: none; background-color: #444; color: #fff; outline: none; border-radius: 5px; padding:4px; }
			details div:first-of-type { padding: 10px; }
			input { padding: 3px; }
		</style>		

		<table width="100%" style="table-layout:fixed; overflow: auto;">
			<tr>
				<td width="20%" style="vertical-align: top;">
					<div class="ui top attached inverted styled segment">
						<h2 class="ui inverted header">Analysis Builder</h2>
					</div>
					<div class="ui attached styled segment">
						<form method="post" action="analysisbuilder.php">
							<input type="hidden" name="action" value="usesavedsearch">
							<div class="ui fluid action input">
								<select name="savedsearchid" class="ui dropdown" required>
									<option value="">Select saved search...
									<?
									$sqlstring = "select * from saved_search where user_id = " . $_SESSION['userid'];
									$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
									while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
										$savedsearchid = $row['savedsearch_id'];
										$savedname = $row['saved_name'];
										if ($savedid == $savedsearchid) {
											$selected = "selected";
										}
										else {
											$selected = "";
										}
										?>
										<option value="<?=$savedsearchid?>" <?=$selected?>><?=$savedname?>
										<?
									}
									?>
								</select>
								<button class="ui primary button">Use saved search</button>
							</div>
						</form>
					</div>
					<div class="ui attached styled segment">
						<form method="post" action="analysisbuilder.php" class="ui form">
							<input type="hidden" name="action" value="viewanalysissummary">
							<div class="ui fluid action input">
								<select name="projectid" class="ui dropdown" required>
									<option value="">Select Project...</option>
									<option value="0">All Projects</option>
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
								<button class="ui primary button">Use project</button>
							</div>
						</form>
						<?
						if (($projectid == '') || ($projectid == 0)) {
							return;
						}
						?>
					</div>
					<div class="ui bottom attached styled segment">
						<form method="post" name="analysisbuilder" action="analysisbuilder.php">
						<input type="hidden" name="action" value="viewanalysissummary">
						<input type="hidden" name="projectid" value="<?=$projectid?>">
					
						<div class="ui top attached styled accordion">
							<div class="title" style="padding:5px;">
								<h3 class="ui black header"><i class="dropdown icon"></i>MR&nbsp;<span id="mriIndicator" class="indicator"></span></h3>
							</div>
							<div class="content">
								<input type="checkbox" name="includeprotocolparms" id="includeprotocolparms" <? if ($a['includeprotocolparms']) { echo "checked"; } ?> value="1" onChange="CheckForMRICriteria()">Include protocol parameters
								<br>
								<input type="checkbox" name="includemrqa" id="includemrqa" <? if ($a['includemrqa']) { echo "checked"; } ?> value="1" onChange="CheckForMRICriteria()">Include QA<br>
								<input type="checkbox" name="groupmrbyvisittype" id="groupmrbyvisittype" <? if ($a['groupmrbyvisittype']) { echo "checked"; } ?> value="1" onChange="CheckForMRICriteria()">Separate by study visit type
								<br>
								<b>Protocol(s)</b><br>
								<select name="mr_protocols[]" id="mr_protocols" multiple class="ui search fluid dropdown" onChange="CheckForMRICriteria()">
									<option value="" <? if (in_array("NONE", $a['mr_protocols']) || ($a['mr_protocols'] == "")) echo "selected"; ?>>Select MRI protocol(s)...
									<option value="ALLPROTOCOLS" <? if (in_array("ALLPROTOCOLS", $a['mr_protocols'])) echo "selected"; ?>>(ALL protocols)
									<?
									/* get unique list of MR protocols from this project */
									$sqlstring = "select a.series_desc from mr_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id where c.project_id = $projectid and a.series_desc <> '' and a.series_desc is not null group by series_desc order by series_desc";
									$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
									while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
										$seriesdesc = trim($row['series_desc']);
										
										if (in_array($seriesdesc, $a['mr_protocols']))
											$selected = "selected";
										else
											$selected = "";
										
										$seriesdesc = str_replace("<", "&lt;", $seriesdesc);
										$seriesdesc = str_replace(">", "&gt;", $seriesdesc);
										?><option value="<?=$seriesdesc?>" <?=$selected?>><?=$seriesdesc?><?
									}
									?>
								</select>
							</div>

							<div class="title" style="padding:5px;">
								<h3 class="ui black header"><i class="dropdown icon"></i>EEG&nbsp;<span id="eegIndicator" class="indicator"></span></h3>
							</div>
							<div class="content">
								EEG Protocol<br>
								<select name="eeg_protocols[]" id="eeg_protocols" multiple class="ui search fluid dropdown" onChange="CheckForEEGCriteria()">
									<option value="" <? if (in_array("NONE", $a['eeg_protocols']) || ($a['eeg_protocols'] == "")) echo "selected"; ?>>Select EEG protocol(s)...
									<option value="ALLPROTOCOLS" <? if (in_array("ALLPROTOCOLS", $a['eeg_protocols'])) echo "selected"; ?>>(ALL protocols)
									<?
									/* get unique list of EEG protocols from this project */
									$sqlstring = "select a.series_desc from eeg_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id where c.project_id = $projectid and a.series_desc <> '' and a.series_desc is not null group by series_desc order by series_desc";
									$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
									while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
										$seriesdesc = $row['series_desc'];
										
										if (in_array($seriesdesc, $a['eeg_protocols']))
											$selected = "selected";
										else
											$selected = "";
										
										$seriesdesc = str_replace("<", "&lt;", $seriesdesc);
										$seriesdesc = str_replace(">", "&gt;", $seriesdesc);
										?><option value="<?=$seriesdesc?>" <?=$selected?>><?=$seriesdesc?><?
									}
									?>
								</select>
							</div>
							
							<div class="title" style="padding:5px;">
								<h3 class="ui black header"><i class="dropdown icon"></i>ET&nbsp;<span id="etIndicator" class="indicator"></span></h3>
							</div>
							<div class="content">
								ET Protocol<br>
								<select name="et_protocols[]" id="et_protocols" multiple style="width: 400px" size="10" onChange="CheckForETCriteria()">
									<option value="" <? if (in_array("NONE", $a['et_protocols']) || ($a['et_protocols'] == "")) echo "selected"; ?>>Select ET protocol(s)...
									<option value="ALLPROTOCOLS" <? if (in_array("ALLPROTOCOLS", $a['et_protocols'])) echo "selected"; ?>>(ALL protocols)
									<?
									/* get unique list of ET protocols from this project */
									$sqlstring = "select a.series_desc from et_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id where c.project_id = $projectid and a.series_desc <> '' and a.series_desc is not null group by series_desc order by series_desc";
									$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
									while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
										$seriesdesc = $row['series_desc'];
										
										if (in_array($seriesdesc, $a['et_protocols']))
											$selected = "selected";
										else
											$selected = "";
										
										$seriesdesc = str_replace("<", "&lt;", $seriesdesc);
										$seriesdesc = str_replace(">", "&gt;", $seriesdesc);
										?><option value="<?=$seriesdesc?>" <?=$selected?>><?=$seriesdesc?><?
									}
									?>
								</select>
							</div>
							
							<div class="title" style="padding:5px;">
								<h3 class="ui black header"><i class="dropdown icon"></i>Pipeline&nbsp;<span id="pipelineIndicator" class="indicator"></span></h3>
							</div>
							<div class="content">
								Pipeline<br>
								<select class="js-example-basic-multiple" name="pipelineid[]" id="pipelineid" onChange="CheckForPipelineCriteria()" multiple="multiple" style="width: 100%"><?
									$sqlstring2 = "select pipeline_id, pipeline_name from pipelines where pipeline_id in (select a.pipeline_id from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id where c.project_id = $projectid group by a.pipeline_id) order by pipeline_name";
									$result2 = MySQLiQuery($sqlstring2,__FILE__,__LINE__);
									while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
										$pipelineid = $row2['pipeline_id'];
										$pipelinename = $row2['pipeline_name'];

										$selected = "";
										if (is_array($a['pipelineid']))
											if (in_array($pipelineid, $a['pipelineid']))
												$selected = "selected";
										if (trim($pipelineid) == trim($a['pipelineid']))
											$selected = "selected";
										?>
										<option value="<?=$pipelineid?>" <?=$selected?>><?=$pipelinename?></option>
										<?
									}
								?></select>
								Result name <i class="blue question circle icon" title="For all text fields: Use * as a wildcard. Enclose strings in 'apostrophes' to search for exact match (or to match the * character). Separate multiple names with commas"></i> <input type="text" name="pipelineresultname" id="pipelineresultname" value="<?=$a['pipelineresultname']?>" onChange="CheckForPipelineCriteria()">
								<br>
								Get Datetime from Series. Enter series description <i class="blue question circle icon" title="Try to obtain the date/time of the pipeline result from the series matching this value, instead of the StudyDateTime. Use * as a wildcard. Enclose strings in 'apostrophes' to search for exact match (or to match the * character). Separate multiple names with commas"></i> <input type="text" name="pipelineseriesdatetime" id="pipelineseriesdatetime" value="<?=$a['pipelineseriesdatetime']?>" onChange="CheckForPipelineCriteria()">
							</div>
							
							<div class="title" style="padding:5px;">
								<h3 class="ui black header"><i class="dropdown icon"></i>Cognitive and Other Measures&nbsp;<span id="measureIndicator" class="indicator"></span></h3>
							</div>
							<div class="content">
								Measure name(s)
								<br>
								<select class="js-example-basic-multiple" name="measurename[]" id="measurename" onChange="CheckForMeasureCriteria()" multiple="multiple" style="width: 100%"><?
									$sqlstringA = "SELECT distinct(c.measure_name) FROM measures a left join enrollment b on a.enrollment_id = b.enrollment_id left join measurenames c on a.measurename_id = c.measurename_id where b.project_id = $projectid order by c.measure_name";
									$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
									while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
										$measurename = $rowA['measure_name'];
										if (trim($measurename) != "") {
											$selected = "";
											if (is_array($a['measurename']))
												if (in_array($measurename, $a['measurename']))
													$selected = "selected";
											if (trim($measurename) == trim($a['measurename']))
												$selected = "selected";
											?><option value="<?=$measurename?>" <?=$selected?>><?=$measurename?><?
										}
									}
								?></select>
								<br>
								<input type="checkbox" name="includeallmeasures" id="includeallmeasures" value="1" <? if ($a['includeallmeasures']) echo "checked"; ?> onChange="CheckForMeasureCriteria()">Include all measures
							</div>
							
							<div class="title" style="padding:5px;">
								<h3 class="ui black header"><i class="dropdown icon"></i>Biological Measurements&nbsp;<span id="vitalIndicator" class="indicator"></span></h3>
							</div>
							<div class="content">
								Vital name(s)
								<br>
								<select class="js-example-basic-multiple" name="vitalname[]" id="vitalname" onChange="CheckForVitalCriteria()" multiple="multiple" style="width: 100%"><?
									$sqlstringA = "SELECT distinct(c.vital_name) FROM vitals a left join enrollment b on a.enrollment_id = b.enrollment_id left join vitalnames c on a.vitalname_id = c.vitalname_id where b.project_id = $projectid order by c.vital_name";
									$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
									while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
										$vitalname = $rowA['vital_name'];
										$selected = "";
										if (is_array($a['vitalname']))
											if (in_array($vitalname, $a['vitalname']))
												$selected = "selected";
										if (trim($vitalname) == trim($a['vitalname']))
											$selected = "selected";
										?><option value="<?=$vitalname?>" <?=$selected?>><?=$vitalname?><?
									}
								?></select>
								<br>
								<input type="checkbox" name="includeallvitals" id="includeallvitals" value="1" <? if ($a['includeallvitals']) echo "checked"; ?> onChange="CheckForVitalCriteria()">Include all vitals
							</div>

							<div class="title" style="padding:5px;">
								<h3 class="ui black header"><i class="dropdown icon"></i>Drugs/Dosing&nbsp;<span id="drugIndicator" class="indicator"></span></h3>
							</div>
							<div class="content">
								Drug variable name(s) <i class="blue question circle icon" title="Find all of the following drugs and display the 'value'. Depending on where the data was imported from, 'value' may likely be blank"></i><br>
								<select class="js-example-basic-multiple" name="drugname[]" id="drugname" onChange="CheckForDrugCriteria()" multiple="multiple" style="width: 100%">
								<?
									$sqlstringA = "SELECT distinct(c.drug_name) FROM drugs a left join enrollment b on a.enrollment_id = b.enrollment_id left join drugnames c on a.drugname_id = c.drugname_id where b.project_id = $projectid order by c.drug_name";
									$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
									while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
										$drugname = $rowA['drug_name'];
										$selected = "";
										if (is_array($a['drugname']))
											if (in_array($drugname, $a['drugname']))
												$selected = "selected";
										if (trim($drugname) == trim($a['drugname']))
											$selected = "selected";
										?><option value="<?=$drugname?>" <?=$selected?>><?=$drugname?><?
									}
								?>
								</select>
								<br>
								<input type="checkbox" name="includealldrugs" id="includealldrugs" value="1" <? if ($a['includealldrugs']) echo "checked"; ?>>Include all drug/dosing variables
								<br>
								<input type="checkbox" name="includedrugdetails" id="includedrugdetails" value="1" <? if ($a['includedrugdetails']) echo "checked"; ?>>Include drug/dose extended details
								<br>
								<br>
								<div style="border: 1px solid #ccc; padding: 5px; border-radius: 4px">
								<input type="checkbox" name="includetimesincedose" id="includetimesincedose" value="1" <? if ($a['includetimesincedose']) echo "checked"; ?> onChange="CheckForDrugCriteria()">Include <b>time since dose</b> <i class="blue question circle icon" title="Includes this dose as the first dose of the specified time period, and calculates 'time since dose' for all other events that happen within the specified timeframe"></i><br>
								Dose variable(s)
								<select class="js-example-basic-multiple" name="dosevariable[]" id="dosevariable" onChange="CheckForDrugCriteria()" multiple="multiple" style="width: 100%">
								<?
									$sqlstringA = "SELECT distinct(c.drug_name) FROM drugs a left join enrollment b on a.enrollment_id = b.enrollment_id left join drugnames c on a.drugname_id = c.drugname_id where b.project_id = $projectid order by c.drug_name";
									$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
									while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
										$drugname = $rowA['drug_name'];
										$selected = "";
										if (is_array($a['dosevariable']))
											if (in_array($drugname, $a['dosevariable']))
												$selected = "selected";
										if (trim($drugname) == trim($a['dosevariable']))
											$selected = "selected";
										?><option value="<?=$drugname?>" <?=$selected?>><?=$drugname?><?
									}
								?>
								</select>
								<br>
								Group dose time by 
								<select name="dosetimerange" id="dosetimerange" onChange="CheckForDrugCriteria()">
									<!--<option value="hour">Hour-->
									<option value="day" selected>Day
									<!--<option value="week">Week
									<option value="month">Month
									<option value="year">Year-->
								</select>
								<br>
								Display time since dose in 
								<select name="dosedisplaytime" id="dosedisplaytime" onChange="CheckForDrugCriteria()">
									<option value="sec" <? if ($a['dosedisplaytime'] == "sec") echo "selected"; ?> >Seconds
									<option value="min" <? if ( ($a['dosedisplaytime'] == "min") || ($a['dosedisplaytime'] == "")) echo "selected"; ?> >Minutes
									<option value="hour" <? if ($a['dosedisplaytime'] == "hour") echo "selected"; ?> >Hours
								</select>
								</div>
								
							</div>
						</div>
						
						<br>
						<b>Grouping Options</b>
						<br>
						<input type="checkbox" name="groupbydate" value="1" <? if ($a['groupbydate']) echo "checked"; ?>>Group by event DATE <i class="blue question circle icon" title="Group output rows by UID, then <i>date</i> [<?=date('Y-m-d')?>], not date<u>time</u> [<?=date('Y-m-d H:i:s')?>]."></i><br>
						<input type="checkbox" name="collapsevariables" value="1" <? if ($a['collapsevariables']) echo "checked"; ?>>Collapse variables <i class="blue question circle icon" title="Expression to match a grouping, <i>by day</i>. For example, to collapse <tt>var1_xyz</tt>, <tt>var1_abc</tt>, <tt>var1_a</tt>, into 1 row and 3 columns, use <code style='color: #000'>var#_*</code>. <tt>#</tt> represents any integer number, and <tt>*</tt> represents any string."></i> <input type="text" name="collapsebyexpression" value="<?=$a['collapsebyexpression']?>" placeholder="Matching expression...">
						<br>
						<b>Output Options</b>
						<br>
						<input type="checkbox" name="includeemptysubjects" value="1" <? if ($a['includeemptysubjects']) echo "checked"; ?>>Include subjects without data <i class="blue question circle icon" title="Includes subjects which are part of this project, but have none of the selected data"></i><br>
						Blank value string <input name="blankvalueplaceholder" value="<?=$a['blankvalueplaceholder']?>" required> <i class="blue question circle icon" title="If a value exists, but the value is blank, display this string instead"></i><br>
						Missing value string <i class="blue question circle icon" title="If a value is missing, display this string instead"></i> <input name="missingvalueplaceholder" value="<?=$a['missingvalueplaceholder']?>" placeholder="Missing value placeholder..."><br>
						<input type="checkbox" name="includeduration" value="1" <? if ($a['includeduration']) echo "checked"; ?>>Include event duration <i class="blue question circle icon" title="If an event has a start and stop time, include the duration in the output"></i><br>
						<input type="checkbox" name="includeenddate" value="1" <? if ($a['includeenddate']) echo "checked"; ?>>Include end datetime <i class="blue question circle icon" title="If an event has a end date, include the end date in the output"></i><br>
						<input type="checkbox" name="includeheightweight" value="1" <? if ($a['includeheightweight']) echo "checked"; ?>>Include subject heigh/weight <i class="blue question circle icon" title="Include the subject's height and weight in the output"></i><br>
						<input type="checkbox" name="includedob" value="1" <? if ($a['includedob']) echo "checked"; ?>>Include subject date of birth <i class="blue question circle icon" title="Include the subject's date of birth in the output"></i><br>
						<br>
						<table>
							<tr>
								<td width="50%">
									Reporting format<br>
									<input type="radio" name="reportformat" value="long" <? if (($a['reportformat'] == "long") || ($a['reportformat'] == "")) echo "checked"; ?>>Long<br>
									<!--<input type="radio" name="reportformat" value="wide" <? if ($a['reportformat'] == "wide") echo "checked"; ?>>Wide-->
								</td>
								<td style="padding-left: 20px">
									Output format<br>
									<input type="radio" name="outputformat" value="table" <? if (($a['outputformat'] == "table") || ($a['outputformat'] == "")) echo "checked"; ?>>Table (screen)<br>
									<input type="radio" name="outputformat" value="csv" <? if ($a['outputformat'] == "csv") echo "checked"; ?>>.csv
								</td>
							</tr>
						</table>
						<br>
						<button class="ui fluid primary button" onClick="document.analysisbuilder.action.value='viewanalysissummary'; return;"><i class="search icon"></i>Update Summary</button>
						<br><br>
						<div class="ui fluid action input">
							<input type="text" name="savedsearchname" placeholder="Saved search name...">
							<button class="ui basic compact button" onClick="document.analysisbuilder.action.value='savesearch'; return;"><i class="save icon"></i> Save search</button>
						</div>
						</form>
					</div>
				</td>
				
				<!-- ************** right side results table ************** -->
				<td width="80%" style="vertical-align: top; overflow: auto">
					<?
						if ($a['reportformat'] == "long") {
							list($h, $t, $n) = CreateLongReport($projectid, $a);
						}
						elseif ($a['reportformat'] == "wide") {
							list($h, $t, $n) = CreateWideReport($projectid, $a);
						}
						
						//PrintVariable($t);
						
						if ($a['outputformat'] == "table") {
							?>
							<div class="ui accordion">
								<div class="title">
									<i class="dropdown icon"></i>Debug notes
								</div>
								<div class="content">
									<pre class="tt" style="font-size: smaller"><?=$n?></pre>
								</div>
							</div>
							<?
							PrintTable($h, $t, $a);
						}
						elseif ($a['outputformat'] == "csv") {
							PrintCSV($h, $t, $a);
						}
					?>
				</td>
			</tr>
		</table>
		<?
	}


	/* -------------------------------------------- */
	/* ------- CreateLongReport ------------------- */
	/* -------------------------------------------- */
	function CreateLongReport($projectid, $a) {

		/* setup some global-ish variables */
		$doseVariable = $a['dosevariable'];
		$doseTimeRange = $a['dosetimerange'];
		$doseDisplayTime = $a['dosedisplaytime'];
		$includeDuration = $a['includeduration'];
		$includeEndDate = $a['includeenddate'];
		$includeHeightWeight = $a['includeheightweight'];
		$includeDOB = $a['includedob'];
		$blankValuePlaceholder = $a['blankvalueplaceholder'];
		$missingValuePlaceholder = $a['missingvalueplaceholder'];
		$groupByDate = $a['groupbydate'];
		$collapseByVars = $a['collapsevariables'];
		$collapseExpression = $a['collapsebyexpression'];
		
		/* create the table */
		$t;
		
		/* get all of the subject information */
		$sqlstring = "select a.*, b.* from subjects a left join enrollment b on a.subject_id = b.subject_id where b.project_id = $projectid order by a.uid";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$i = 0;
		while ($rowA = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$subjects[$i]['subjectid'] = $rowA['subject_id'];
			$subjects[$i]['uid'] = $rowA['uid'];
			$subjects[$i]['enrollmentid'] = $rowA['enrollment_id'];
			$subjects[$i]['dob'] = $rowA['birthdate'];
			$subjects[$i]['sex'] = $rowA['gender'];
			$subjects[$i]['height'] = $rowA['height'];
			$subjects[$i]['weight'] = $rowA['weight'];
			$subjects[$i]['enrollgroup'] = $rowA['enroll_subgroup'];
			
			$altuids = GetAlternateUIDs($subjects[$i]['subjectid'], $subjects[$i]['enrollmentid']);
			$subjects[$i]['altuids'] = implode2(" | ", $altuids);
			
			$i++;
		}
		
		/* loop through the subjects and add their info to the table */
		$row = 0;
		foreach ($subjects as $i => $subj) {
			$hasdata = false;
			
			$uid = $subj['uid'];
			if ($groupByDate)
				$row = $uid . "0000-00-00";
			else
				$row = $uid . "0000-00-00 00:00:00";
				
			$enrollmentid = $subj['enrollmentid'];
			$age = "";
			
			/* get dose datetimes for this enrollment */
			$drugdoses = array();
			if ($a['includetimesincedose']) {
				if ($doseVariable != "") {
					$sqlstringA = "select a.*, b.drug_name from drugs a left join drugnames b on a.drugname_id = b.drugname_id where a.enrollment_id = $enrollmentid and (" . CreateSQLSearchString("b.drug_name", $a['dosevariable']) . ")";
					$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
					$i=0;
					while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
						/* add the measure info to this row */
						$drugdoses[$i]['date'] = $rowA['drug_startdate'];
						$drugdoses[$i]['doseamount'] = $rowA['drug_doseamount'];
						$drugdoses[$i]['dosekey'] = $rowA['drug_dosekey'];
						$i++;
					}
				}
			}
			
			/* ---------- get all of the MR protocol info ---------- */
			if (!empty($a['mr_protocols'])) {
				
				if (in_array("ALLPROTOCOLS", $a['mr_protocols'])) {
					$sqlstringA = "select a.*, b.* from mr_series a left join studies b on a.study_id = b.study_id where b.enrollment_id = $enrollmentid";
				}
				else {
					$mrprotocollist = MakeSQLListFromArray($a['mr_protocols']);
					$sqlstringA = "select a.*, b.* from mr_series a left join studies b on a.study_id = b.study_id where b.enrollment_id = $enrollmentid and a.series_desc in ($mrprotocollist)";
					//$sqlstringA = "select a.*, b.*, count(a.series_desc) 'seriescount' from mr_series a left join studies b on a.study_id = b.study_id where b.enrollment_id = $enrollmentid and a.series_desc in ($mrprotocollist) group by a.series_desc";
				}
			
				/* add in the protocols */
				$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					$seriesdesc = preg_replace('/\s+/', '', $rowA['series_desc']);
					$seriesid = $rowA['mrseries_id'];
					$seriesdatetime = $rowA['series_datetime'];
					$pixdimX = $rowA['series_spacingx'];
					$pixdimY = $rowA['series_spacingy'];
					$pixdimZ = $rowA['series_spacingz'];
					$dimX = $rowA['dimX'];
					$dimY = $rowA['dimY'];
					$dimZ = $rowA['dimZ'];
					$dimT = $rowA['dimT'];
					$tr = $rowA['series_tr'];
					$te = $rowA['series_te'];
					$ti = $rowA['series_ti'];
					$flip = $rowA['series_flip'];
					$seriesnum = $rowA['series_num'];
					$studynum = $rowA['study_num'];
					$numseries = $rowA['seriescount'];
					$studyheight = $rowA['study_height'];
					$studyweight = $rowA['study_weight'];
					$studydatetime = $rowA['study_datetime'];
					$studyage = $rowA['study_ageatscan'];
					$studynotes = $rowA['study_notes'];
					$studyvisit = $rowA['study_type'];
					
					list($studyAge, $calcStudyAge) = GetStudyAge($subj['dob'], $studyage, $studydatetime);
					
					if ($studyAge == null)
						$studyAge = "-";
					else
						$studyAge = number_format($studyAge,1);

					if ($calcStudyAge == null)
						$calcStudyAge = "-";
					else
						$calcStudyAge = number_format($calcStudyAge,1);
					
					if (($studyheight == "") || ($studyheight == "null") || ($studyheight == 0))
						$height = $subj['height'];
					else
						$height = $studyheight;
					
					if (($studyweight == "") || ($studyweight == "null") || ($studyweight == 0))
						$weight = $subj['weight'];
					else
						$weight = $studyweight;
						
					if ($a['groupmrbyvisittype'])
						$seriesdesc = "$seriesdesc" . "_$studyvisit";
					
					/* need to add the demographic info to every row */
					if ($groupByDate)
						$row = $uid . substr($studydatetime, 0, 10);
					else
						$row = "$uid$studydatetime";
						
					$t[$row]['UID'] = $subj['uid'];
					$t[$row]['Sex'] = $subj['sex'];
					$t[$row]['AgeAtEvent'] = $studyAge;
					$t[$row]['CalcAgeAtEvent'] = $calcStudyAge;
					if ($includeHeightWeight) {
						$t[$row]['Height'] = $height;
						$t[$row]['Weight'] = $weight;
					}
					if ($includeDOB) {
						$t[$row]['DOB'] = $subj['dob'];
					}
					$t[$row]['EnrollGroup'] = $subj['enrollgroup'];
					$t[$row]['AltUIDs'] = $subj['altuids'];
					$t[$row]['VisitType'] = $studyvisit;
					
					/* add the protocol info to the row */
					$t[$row]["$seriesdesc-SeriesNum"] = $seriesnum;
					$t[$row]["$seriesdesc-StudyDateTime"] = $studydatetime;
					$t[$row]['EventDateTime'] = $seriesdatetime;
					$t[$row]["$seriesdesc-StudyID"] = $subj['uid'] . $studynum;
					//$t[$row]["$seriesdesc-NumSeries"] = $numseries;
					//$t[$row]["$seriesdesc-Notes"] = $studynotes;
					
					list($timeSinceDose, $doseamount, $dosekey) = GetTimeSinceDose($drugdoses, $seriesdatetime, $doseDisplayTime);
					if ($timeSinceDose != null) {
						$t[$row]["$seriesdesc-TIMESINCEDOSE-$doseDisplayTime"] = $timeSinceDose;
						$t[$row]['DoseAmount'] = $doseamount;
						$t[$row]['DoseKey'] = $dosekey;
					}
					else {
						$n .= $subj['uid'] . ": $seriesdesc-TIMESINCEDOSE-$doseDisplayTime was null. Comparing DOSE TIMES " . json_encode($drugdoses) . " to ITEM TIME $seriesdatetime\n";
					}
					
					if ($a["includeprotocolparms"]) {
						$t[$row]["$seriesdesc-voxX"] = $pixdimX;
						$t[$row]["$seriesdesc-voxY"] = $pixdimY;
						$t[$row]["$seriesdesc-voxZ"] = $pixdimZ;
						$t[$row]["$seriesdesc-dimX"] = $dimX;
						$t[$row]["$seriesdesc-dimY"] = $dimY;
						$t[$row]["$seriesdesc-dimZ"] = $dimZ;
						$t[$row]["$seriesdesc-dimT"] = $dimT;
						$t[$row]["$seriesdesc-TR"] = $tr;
						$t[$row]["$seriesdesc-TE"] = $te;
						$t[$row]["$seriesdesc-TI"] = $ti;
						$t[$row]["$seriesdesc-flip"] = $flip;
					}
					
					if ($a['includemrqa']) {
						$sqlstringC = "select * from mr_qa where mrseries_id = $seriesid";
						$resultC = MySQLiQuery($sqlstringC,__FILE__,__LINE__);
						$rowC = mysqli_fetch_array($resultC, MYSQLI_ASSOC);
						
						$t[$row]["$seriesdesc-io_snr"] = $rowC['io_snr'];
						$t[$row]["$seriesdesc-pv_snr"] = $rowC['pv_snr'];
						$t[$row]["$seriesdesc-move_minx"] = $rowC['move_minx'];
						$t[$row]["$seriesdesc-move_miny"] = $rowC['move_miny'];
						$t[$row]["$seriesdesc-move_minz"] = $rowC['move_minz'];
						$t[$row]["$seriesdesc-move_maxx"] = $rowC['move_maxx'];
						$t[$row]["$seriesdesc-move_maxy"] = $rowC['move_maxy'];
						$t[$row]["$seriesdesc-move_maxz"] = $rowC['move_maxz'];
						$t[$row]["$seriesdesc-acc_minx"] = $rowC['acc_minx'];
						$t[$row]["$seriesdesc-acc_miny"] = $rowC['acc_miny'];
						$t[$row]["$seriesdesc-acc_minz"] = $rowC['acc_minz'];
						$t[$row]["$seriesdesc-acc_maxx"] = $rowC['acc_maxx'];
						$t[$row]["$seriesdesc-acc_maxy"] = $rowC['acc_maxy'];
						$t[$row]["$seriesdesc-acc_maxz"] = $rowC['acc_maxz'];
						$t[$row]["$seriesdesc-rot_minp"] = $rowC['rot_minp'];
						$t[$row]["$seriesdesc-rot_minr"] = $rowC['rot_minr'];
						$t[$row]["$seriesdesc-rot_miny"] = $rowC['rot_miny'];
						$t[$row]["$seriesdesc-rot_maxp"] = $rowC['rot_maxp'];
						$t[$row]["$seriesdesc-rot_maxr"] = $rowC['rot_maxr'];
						$t[$row]["$seriesdesc-rot_maxy"] = $rowC['rot_maxy'];
					}
					
					$hasdata = true;
				}
			}

			/* ---------- Measures ---------- */
			if (($a['includeallmeasures']) || ($a['measurename'] != "")) {
				if ($a['includeallmeasures']) {
					$sqlstringA = "select a.*, b.measure_name from measures a left join measurenames b on a.measurename_id = b.measurename_id where enrollment_id = $enrollmentid";
				}
				else {
					$sqlstringA = "select a.*, b.measure_name from measures a left join measurenames b on a.measurename_id = b.measurename_id where a.enrollment_id = $enrollmentid and (" . CreateSQLSearchString("b.measure_name", $a['measurename']) . ")";
				}
				$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {

					$measurename = $rowA['measure_name'];
					
					/* attempt to collapse variables based on the expression provided by the user */
					$timepoint = "";
					if ($collapseByVars)
						if ($collapseExpression != "") {
							/* replace all potential regex characters that the user may have entered */
							$preg = preg_quote2($collapseExpression);
							//$n .= "CollapseBy expression after preg_replace2(measures): [$preg]\n";
							
							/* replace the escaped # and * with the equivalent actual regex chars */
							$preg = str_replace("*", "+", $preg);
							$preg = "/^" . str_replace("#", "(\d+)", $preg) . "/";
							$n .= "Final collapseBy expression (measures): [$preg]\n";
							preg_match($preg, $measurename, $matches);
							$timepoint = $matches[1];
							$measurename = str_replace($timepoint, "", $measurename);
						}
						else
							$n .= "Collapse variables was selected, but an expression was not specified\n";

					if ($groupByDate || $collapseByVars)
						$row = $uid . substr($rowA['measure_startdate'], 0, 10) . $timepoint;
					else
						$row = $uid . $rowA['measure_startdate'];
					
					if ($collapseByVars)
						$t[$row]['collapseGroup'] = $timepoint;
					
					$measurevalue = $rowA['measure_value'];
					
					$dob = date_create($subj['dob']);
					$eventdate = date_create($rowA['measure_startdate']);
					$diff = date_diff($eventdate, $dob);
					$age = $diff->format("%a")/365.25;
					
					/* need to add the demographic info to every row */
					$t[$row]['UID'] = $subj['uid'];
					$t[$row]['Sex'] = $subj['sex'];
					if ($includeHeightWeight) {
						$t[$row]['Height'] = $subj['height'];
						$t[$row]['Weight'] = $subj['weight'];
					}
					if ($includeDOB) {
						$t[$row]['DOB'] = $subj['dob'];
					}
					$t[$row]['AgeAtEvent'] = $age;
					$t[$row]['EnrollGroup'] = $subj['enrollgroup'];
					$t[$row]['AltUIDs'] = $subj['altuids'];

					/* add the measure info to this row */
					$t[$row]['EventDateTime'] = $rowA['measure_startdate'];
					if ($includeDuration)
						$t[$row][$measurename . '_DURATION'] = $rowA['measure_duration'];
					if ($includeEndDate)
						$t[$row][$measurename . '_ENDDATETIME'] = $rowA['measure_enddate'];
					if ($measurevalue == "")
						$t[$row][$measurename] = $blankValuePlaceholder;
					else
						$t[$row][$measurename] = $measurevalue;

					list($timeSinceDose, $doseamount, $dosekey) = GetTimeSinceDose($drugdoses, $rowA['measure_startdate'], $doseDisplayTime);
					if ($timeSinceDose != null) {
						$t[$row]["$measurename-TimeSinceDose-$doseDisplayTime"] = $timeSinceDose;
						$t[$row]['DoseAmount'] = $doseamount;
						$t[$row]['DoseKey'] = $dosekey;
					}
					else {
						$n .= $subj['uid'] . ": $measurename-TimeSinceDose-$doseDisplayTime was null. Comparing DOSE TIMES " . json_encode($drugdoses) . " to ITEM TIME " . $rowA['measure_startdate'] . "\n";
					}

					$hasdata = true;
				}
			}
			
			/* ---------- Vitals ---------- */
			if (($a['includeallvitals']) || ($a['vitalname'] != "")) {
				if ($a['includeallvitals']) {
					$sqlstringA = "select a.*, b.vital_name from vitals a left join vitalnames b on a.vitalname_id = b.vitalname_id where enrollment_id = $enrollmentid";
				}
				else {
					$sqlstringA = "select a.*, b.vital_name from vitals a left join vitalnames b on a.vitalname_id = b.vitalname_id where a.enrollment_id = $enrollmentid and (" . CreateSQLSearchString("b.vital_name", $a['vitalname']) . ")";
				}
				$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {

					/* add the vital info to this row */
					$vitalname = $rowA['vital_name'];
					if (($rowA['vital_startdate'] == "0000-00-00 00:00:00") || ($rowA['vital_startdate'] == "")) {
						$vitalDate = $rowA['vital_date'];
					}
					else {
						$vitalDate = $rowA['vital_startdate'];
					}
						
					
					/* attempt to collapse variables based on the expression provided by the user */
					$timepoint = "";
					if ($collapseByVars)
						if ($collapseExpression != "") {
							/* replace all potential regex characters that the user may have entered */
							$preg = preg_quote2($collapseExpression);
							//$n .= "CollapseBy expression after preg_replace2(vitals): [$preg]\n";
							
							/* replace the escaped # and * with the equivalent actual regex chars */
							$preg = str_replace("*", "+", $preg);
							$preg = "/^" . str_replace("#", "(\d+)", $preg) . "/";
							$n .= "Final collapseBy expression (vitals): [$preg]\n";
							preg_match($preg, $vitalname, $matches);
							$timepoint = $matches[1];
							$vitalname = str_replace($timepoint, "", $vitalname);
						}
						else
							$n .= "Collapse variables was selected, but an expression was not specified\n";

					/* create the unique row identifier */
					if ($groupByDate || $collapseByVars)
						$row = $uid . substr($vitalDate, 0, 10) . $timepoint;
					else
						$row = $uid . $vitalDate;
					
					if ($collapseByVars)
						$t[$row]['collapseGroup'] = $timepoint;

					$vitalvalue = $rowA['vital_value'];
					
					$dob = date_create($subj['dob']);
					$eventdate = date_create($vitalDate);
					$diff = date_diff($eventdate, $dob);
					$age = $diff->format("%a")/365.25;
					
					/* need to add the demographic info to every row */
					$t[$row]['UID'] = $subj['uid'];
					$t[$row]['Sex'] = $subj['sex'];
					if ($includeHeightWeight) {
						$t[$row]['Height'] = $subj['height'];
						$t[$row]['Weight'] = $subj['weight'];
					}
					if ($includeDOB) {
						$t[$row]['DOB'] = $subj['dob'];
					}
					$t[$row]['AgeAtEvent'] = $age;
					$t[$row]['EnrollGroup'] = $subj['enrollgroup'];
					$t[$row]['AltUIDs'] = $subj['altuids'];
					
					$t[$row]['EventDateTime'] = $vitalDate;
					if ($includeDuration)
						$t[$row][$vitalname . '_Duration'] = $rowA['vital_duration'];
					if ($includeEndDate)
						$t[$row][$vitalname . '_EndDateTime'] = $rowA['vital_enddate'];
					if ($vitalvalue == "")
						$t[$row][$vitalname] = $blankValuePlaceholder;
					else
						$t[$row][$vitalname] = $vitalvalue;

					list($timeSinceDose, $doseamount, $dosekey) = GetTimeSinceDose($drugdoses, $vitalDate, $doseDisplayTime);
					if ($timeSinceDose != null) {
						$t[$row]["$vitalname-TimeSinceDose-$doseDisplayTime"] = $timeSinceDose;
						$t[$row]['DoseAmount'] = $doseamount;
						$t[$row]['DoseKey'] = $dosekey;
					}
					else {
						$n .= $subj['uid'] . ": $vitalname-TimeSinceDose-$doseDisplayTime was null. Comparing DOSE TIMES " . json_encode($drugdoses) . " to ITEM TIME " . $vitalDate . "\n";
					}

					$hasdata = true;
				}
			}
			
			/* ---------- Drugs ---------- */
			if (($a['includealldrugs']) || ($a['drugname'] != "") || ($a['includedrugdetails']) || ($a['dosevariable'] != "")) {
				
				if ($a['includealldrugs']) {
					$sqlstringA = "select a.*, b.drug_name from drugs a left join drugnames b on a.drugname_id = b.drugname_id where enrollment_id = $enrollmentid";
				}
				else {
					$drugarray = array();
					
					if (is_array($a['drugname']))
						$drugarray = array_merge($drugarray, $a['drugname']);
					elseif (($a['drugname'] != "") && ($a['drugname'] != null))
						$drugarray[] = $a['drugname'];
						
					$drugarray = array_unique($drugarray);

					$drugstr = CreateSQLSearchString("b.drug_name", $drugarray);
					if ($drugstr == "") {
						$sqlstringA = "select a.*, b.drug_name from drugs a left join drugnames b on a.drugname_id = b.drugname_id where a.enrollment_id = $enrollmentid";
					}
					else {
						$sqlstringA = "select a.*, b.drug_name from drugs a left join drugnames b on a.drugname_id = b.drugname_id where a.enrollment_id = $enrollmentid and ($drugstr)";
					}
				}
				if (count($drugarray) > 0) {
					$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
					while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {

						$drugname = $rowA['drug_name'];

						/* attempt to collapse variables based on the expression provided by the user */
						$timepoint = "";
						if ($collapseByVars)
							if ($collapseExpression != "") {
								/* replace all potential regex characters that the user may have entered */
								$preg = preg_quote2($collapseExpression);
								//$n .= "CollapseBy expression after preg_replace2(drugs): [$preg]\n";
								
								/* replace the escaped # and * with the equivalent actual regex chars */
								$preg = str_replace("*", "+", $preg);
								$preg = "/^" . str_replace("#", "(\d+)", $preg) . "/";
								$n .= "Final collapseBy expression (drugs): [$preg]\n";
								preg_match($preg, $drugname, $matches);
								$timepoint = $matches[1];
								$drugname = str_replace($timepoint, "", $drugname);
							}
							else
								$n .= "Collapse variables was selected, but an expression was not specified\n";

						if ($groupByDate || $collapseByVars)
							$row = $uid . substr($rowA['drug_startdate'], 0, 10) . $timepoint;
						else
							$row = $uid . $rowA['drug_startdate'];
						
						if ($collapseByVars)
							$t[$row]['collapseGroup'] = $timepoint;

						$drugvalue = $rowA['drug_value'];
						
						$dob = date_create($subj['dob']);
						$eventdate = date_create($rowA['drug_startdate']);
						$diff = date_diff($eventdate, $dob);
						$age = $diff->format("%a")/365.25;
						
						/* need to add the demographic info to every row */
						$t[$row]['UID'] = $subj['uid'];
						$t[$row]['Sex'] = $subj['sex'];
						if ($includeHeightWeight) {
							$t[$row]['Height'] = $subj['height'];
							$t[$row]['Weight'] = $subj['weight'];
						}
						if ($includeDOB) {
							$t[$row]['DOB'] = $subj['dob'];
						}
						$t[$row]['AgeAtEvent'] = $age;
						$t[$row]['EnrollGroup'] = $subj['enrollgroup'];
						$t[$row]['AltUIDs'] = $subj['altuids'];

						/* add the drug info to this row */
						$t[$row]['EventDateTime'] = $rowA['drug_startdate'];
						if ($includeDuration)
							$t[$row][$drugname . '_DURATION'] = $rowA['drug_duration'];
						if ($includeEndDate)
							$t[$row][$drugname . '_ENDDATETIME'] = $rowA['drug_enddate'];
						if ($drugvalue == "")
							$t[$row][$drugname] = $blankValuePlaceholder;
						else
							$t[$row][$drugname] = $drugvalue;

						list($timeSinceDose, $doseamount, $dosekey) = GetTimeSinceDose($drugdoses, $rowA['drug_startdate'], $doseDisplayTime);
						if ($timeSinceDose != null) {
							$t[$row]["$drugname-TimeSinceDose-$doseDisplayTime"] = $timeSinceDose;
							$t[$row]['DoseAmount'] = $doseamount;
							$t[$row]['DoseKey'] = $dosekey;
						}
						else {
							$n .= $subj['uid'] . ": $drugname-TimeSinceDose-$doseDisplayTime was null. Comparing DOSE TIMES " . json_encode($drugdoses) . " to ITEM TIME " . $rowA['drug_startdate'] . "\n";
						}

						/* add the drug details */
						if ($a['includedrugdetails']) {
							$t[$row][$drugname . '_doseamount'] = $rowA['drug_doseamount'];
							$t[$row][$drugname . '_dosekey'] = $rowA['drug_dosekey'];
						}
						
						$hasdata = true;
					}
				}
			}
			
			/* ----- Pipeline ----- */
			if (($a['pipelineresultname'] != "") && ($a['pipelineid'] != "NONE")) {
				/* get the pipeline result names first (due to MySQL bug which prevents joining in this table in the main query) */
				$resultnameids = array();
				$sqlstringX = "select * from analysis_resultnames where " . CreateSQLSearchString("result_name", $a['pipelineresultname']);
				$resultX = MySQLiQuery($sqlstringX,__FILE__,__LINE__);
				while ($rowX = mysqli_fetch_array($resultX, MYSQLI_ASSOC)) {
					$resultnameids[] = $rowX['resultname_id'];
					$resultnames[$rowX['resultname_id']] = $rowX['result_name'];
				}

				if (count($resultnameids) > 0) {
					$sqlstringA = "SELECT c.study_datetime, c.study_height, c.study_weight, c.study_type, c.study_id, c.study_num, c.study_modality, e.birthdate, TIMESTAMPDIFF( MONTH, e.birthdate, c.study_datetime ) 'ageinmonths', b.* FROM analysis a LEFT JOIN analysis_results b ON a.analysis_id = b.analysis_id LEFT JOIN studies c ON a.study_id = c.study_id LEFT JOIN enrollment d on c.enrollment_id = d.enrollment_id LEFT JOIN subjects e ON d.subject_id = e.subject_id WHERE e.isactive = 1 AND d.project_id = $projectid AND (" . CreateSQLSearchString("a.pipeline_id", $a['pipelineid']) . ") AND b.result_nameid IN(" . implode2(",", $resultnameids) . ") AND b.result_type = 'v' AND e.subject_id = " . $subj['subjectid'] . " ORDER BY c.study_num, c.study_datetime";
					
					$laststudyid = "";
					
					/* create a hash of series datetimes */
					$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
					if (mysqli_num_rows($resultA) > 0) {
						while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
							$studyage = $rowA['ageinmonths']/12.0;
							$studydatetime = $rowA['study_datetime'];
							$studyheight = $rowA['study_height'];
							$studyweight = $rowA['study_weight'];
							$studynum = $rowA['study_num'];
							$studyid = $rowA['study_id'];
							$studyvisit = $rowA['study_type'];
							$studymodality = strtolower($rowA['study_modality']);
							
							if (($studyage == "") || ($studyage == "null") || ($studyage == 0)) $age = strtotime($studydate) - strtotime($subj['dob']);
							else $age = $studyage;
							
							if (($studyheight == "") || ($studyheight == "null") || ($studyheight == 0)) $height = $subj['height'];
							else $height = $studyheight;
							
							if (($studyweight == "") || ($studyweight == "null") || ($studyweight == 0)) $weight = $subj['weight'];
							else $weight = $studyweight;
							
							/* if we should search for a series datetime */
							$variabledatetime = $studydatetime;
							if ($a['pipelineseriesdatetime'] != "") {
								$sqlstringB = "select series_datetime from $studymodality" . "_series where (" . CreateSQLSearchString("series_protocol", $a['pipelineseriesdatetime']) . ") and study_id = $studyid limit 1";
								$resultB = MySQLiQuery($sqlstringB,__FILE__,__LINE__);
								if (mysqli_num_rows($resultB) > 0) {
									$rowB = mysqli_fetch_array($resultB, MYSQLI_ASSOC);
									$variabledatetime = $rowB['series_datetime'];

									if ($groupByDate)
										$row = $uid . substr($rowA['drug_startdate'], 0, 10);
									else
										$row = $uid . $rowA['drug_startdate'];
									
									//$t[$row]['Pipeline-SeriesDateTime'] = $variabledatetime;
								}
							}
							if ($groupByDate)
								$row = $uid . substr($variabledatetime, 0, 10);
							else
								$row = $uid . $variabledatetime;
							
							/* need to add the demographic info to every row */
							$t[$row]['UID'] = $subj['uid'];
							$t[$row]['Sex'] = $subj['sex'];
							$t[$row]['AgeAtEvent'] = $age;
							if ($includeHeightWeight) {
								$t[$row]['Height'] = $height;
								$t[$row]['Weight'] = $weight;
							}
							if ($includeDOB) {
								$t[$row]['DOB'] = $subj['dob'];
							}
							$t[$row]['EnrollGroup'] = $subj['enrollgroup'];
							$t[$row]['AltUIDs'] = $subj['altuids'];
							$t[$row]['Pipeline-StudyID'] = $subj['uid'] . $studynum;
							$t[$row]['Pipeline-StudyDateTime'] = $studydatetime;
							$t[$row]['VisitType'] = $studyvisit;

							$resultname = $resultnames[$rowA['result_nameid']];
							
							/* add the measure info to this row */
							$t[$row]["Pipeline_$resultname"] = $rowA['result_value'];

							list($timeSinceDose, $doseamount, $dosekey) = GetTimeSinceDose($drugdoses, $variabledatetime, $doseDisplayTime);
							if ($timeSinceDose != null) {
								$t[$row][$resultname . "_TIMESINCEDOSE_$doseDisplayTime"] = $timeSinceDose;
								$t[$row]['DoseAmount'] = $doseamount;
								$t[$row]['DoseKey'] = $dosekey;
							}
							
							$hasdata = true;
							$laststudyid = $studyid;
						}
						$row++;
					}
				}
				else {
					$n .= "Pipeline results not found using search criteria entered. Check the analysis result name and try again. SQL [$sqlstringX]\n";
				}
			}
			
			/* ----- add a row if the subject had no data ----- */
			if ((!$hasdata) && ($a['includeemptysubjects'] == 1)) {
				$t[$row]['UID'] = $subj['uid'];
				$t[$row]['Sex'] = $subj['sex'];
				if ($includeHeightWeight) {
					$t[$row]['Height'] = $subj['height'];
					$t[$row]['Weight'] = $subj['weight'];
				}
				if ($includeDOB) {
					$t[$row]['DOB'] = $subj['dob'];
				}
				$t[$row]['EnrollGroup'] = $subj['enrollgroup'];
				$t[$row]['AltUIDs'] = $subj['altuids'];
				$t[$row]['AgeAtEvent'] = $age;
			}
		}
		
		/* create table header */
		$h2 = array();
		foreach ($t as $r => $subj) {
			foreach ($subj as $col => $vals) {
				$h2[$col] = "";
			}
		}
		$h = array_keys($h2);
		
		return array($h, $t, $n);
	}
	
	
	/* -------------------------------------------- */
	/* ------- CreateWideReport ------------------- */
	/* -------------------------------------------- */
	function CreateWideReport($projectid, $a) {

		// /* setup some global-ish variables */
		// $doseVariable = $a['dosevariable'];
		// $doseTimeRange = $a['dosetimerange'];
		// $doseDisplayTime = $a['dosedisplaytime'];

		// /* create the table */
		// $t;
		
		// /* get all of the subject information */
		// $sqlstring = "select a.*, b.* from subjects a left join enrollment b on a.subject_id = b.subject_id where b.project_id = $projectid order by a.uid";
		// $result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		// while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			// $uid = $row['uid'];
			// $t[$uid]['uid'] = $uid;
			
			// $subjectid 		= $t[$uid]['subjectid'] 	= $row['subject_id'];
			// $enrollmentid 	= $t[$uid]['enrollmentid'] 	= $row['enrollment_id'];
			// $dob 			= $t[$uid]['dob'] 			= $row['birthdate'];
			// $sex 			= $t[$uid]['sex'] 			= $row['gender'];
			// $subjectheight 	= $t[$uid]['subjectheight'] = $row['height'];
			// $subjectweight 	= $t[$uid]['subjectweight'] = $row['weight'];
			// $enrollgroup 	= $t[$uid]['enrollgroup'] 	= $row['enroll_subgroup'];
			
			// $altuids = implode2(" | ", GetAlternateUIDs($subjectid, $enrollmentid));
			// $t[$uid]['altuids'] = $altuids;

			// /* get dose datetimes for this enrollment */
			// $dosedates = array();
			// if ($a['includetimesincedose']) {
				// if ($doseVariable != "") {
					// $sqlstringA = "select a.*, b.drug_name from drugs a left join drugnames b on a.drugname_id = b.drugname_id where a.enrollment_id = $enrollmentid and b.drug_name = '$doseVariable'";
					// $resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
					// while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
						// /* add the measure info to this row */
						// $dosedates[]['date'] = $rowA['drug_startdate'];
					// }
				// }
			// }
			
			// /* get all of the protocol info */
			// if (!empty($a['mr_protocols'])) {
				
				// if (in_array("ALLPROTOCOLS", $a['mr_protocols'])) {
					// $sqlstringA = "select a.*, b.* from mr_series a left join studies b on a.study_id = b.study_id where b.enrollment_id = $enrollmentid";
				// }
				// else {
					// $mrprotocollist = MakeSQLListFromArray($a['mr_protocols']);
					// $sqlstringA = "select a.*, b.*, count(a.series_desc) 'seriescount' from mr_series a left join studies b on a.study_id = b.study_id where b.enrollment_id = $enrollmentid and a.series_desc in ($mrprotocollist) group by a.series_desc";
				// }
			
				// /* add in the protocols */
				// $resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
				// $i=1;
				// while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					// $seriesdesc = preg_replace('/\s+/', '', $rowA['series_desc']);
					// $seriesid = $rowA['mrseries_id'];
					// $seriesdatetime = $rowA['series_datetime'];
					
					// $pixdimX = $rowA['series_spacingx'];
					// $pixdimY = $rowA['series_spacingy'];
					// $pixdimZ = $rowA['series_spacingz'];
					// $dimX = $rowA['dimX'];
					// $dimY = $rowA['dimY'];
					// $dimZ = $rowA['dimZ'];
					// $dimT = $rowA['dimT'];
					// $tr = $rowA['series_tr'];
					// $te = $rowA['series_te'];
					// $ti = $rowA['series_ti'];
					// $flip = $rowA['series_flip'];
					// $seriesnum = $rowA['series_num'];
					// $studynum = $rowA['study_num'];
					// $numseries = $rowA['seriescount'];
					// $studyheight = $rowA['study_height'];
					// $studyweight = $rowA['study_weight'];
					// $studydatetime = $rowA['study_datetime'];
					// $studyage = $rowA['study_ageatscan'];
					// $studynotes = $rowA['study_notes'];
					// $studyvisit = $rowA['study_type'];
					
					// if (($studyage == "") || ($studyage == "null") || ($studyage == 0))
						// $age = strtotime($studydate) - strtotime($dob);
					// else
						// $age = $studyage;
					
					// if (($studyheight == "") || ($studyheight == "null") || ($studyheight == 0))
						// $height = $subjectheight;
					// else
						// $height = $studyheight;
					
					// if (($studyweight == "") || ($studyweight == "null") || ($studyweight == 0))
						// $weight = $subjectweight;
					// else
						// $weight = $studyweight;
					
					// /* add the protocol info to the row */
					// $t[$uid]["$seriesdesc"."_SeriesNum_$i"] = $seriesnum;
					// $t[$uid]["$seriesdesc"."_StudyDateTime_$i"] = $studydatetime;
					// $t[$uid]["$seriesdesc"."_StudyNum_$i"] = $studynum;
					// $t[$uid]["$seriesdesc"."_NumSeries_$i"] = $numseries;
					// $t[$uid]["$seriesdesc"."_AgeAtScan_$i"] = $age;
					// $t[$uid]["$seriesdesc"."_Height_$i"] = $height;
					// $t[$uid]["$seriesdesc"."_Weight_$i"] = $weight;
					// $t[$uid]["$seriesdesc"."_Notes_$i"] = $studynotes;
					// $t[$row]['$seriesdesc"."_VisitType_$i'] = $studyvisit;
					
					// if ($a["includeprotocolparms"]) {
						// $t[$uid]["$seriesdesc"."_voxX_$i"] = $pixdimX;
						// $t[$uid]["$seriesdesc"."_voxY_$i"] = $pixdimY;
						// $t[$uid]["$seriesdesc"."_voxZ_$i"] = $pixdimZ;
						// $t[$uid]["$seriesdesc"."_dimX_$i"] = $dimX;
						// $t[$uid]["$seriesdesc"."_dimY_$i"] = $dimY;
						// $t[$uid]["$seriesdesc"."_dimZ_$i"] = $dimZ;
						// $t[$uid]["$seriesdesc"."_dimT_$i"] = $dimT;
						// $t[$uid]["$seriesdesc"."_TR_$i"] = $tr;
						// $t[$uid]["$seriesdesc"."_TE_$i"] = $te;
						// $t[$uid]["$seriesdesc"."_TI_$i"] = $ti;
						// $t[$uid]["$seriesdesc"."_flip_$i"] = $flip;
					// }
					
					// if ($a['includemrqa']) {
						// $sqlstringC = "select * from mr_qa where mrseries_id = $seriesid";
						// $resultC = MySQLiQuery($sqlstringC,__FILE__,__LINE__);
						// $rowC = mysqli_fetch_array($resultC, MYSQLI_ASSOC);
						
						// $t[$uid]["$seriesdesc"."_io_snr_$i"] = $rowC['io_snr'];
						// $t[$uid]["$seriesdesc"."_pv_snr_$i"] = $rowC['pv_snr'];
						// $t[$uid]["$seriesdesc"."_move_minx_$i"] = $rowC['move_minx'];
						// $t[$uid]["$seriesdesc"."_move_miny_$i"] = $rowC['move_miny'];
						// $t[$uid]["$seriesdesc"."_move_minz_$i"] = $rowC['move_minz'];
						// $t[$uid]["$seriesdesc"."_move_maxx_$i"] = $rowC['move_maxx'];
						// $t[$uid]["$seriesdesc"."_move_maxy_$i"] = $rowC['move_maxy'];
						// $t[$uid]["$seriesdesc"."_move_maxz_$i"] = $rowC['move_maxz'];
						// $t[$uid]["$seriesdesc"."_acc_minx_$i"] = $rowC['acc_minx'];
						// $t[$uid]["$seriesdesc"."_acc_miny_$i"] = $rowC['acc_miny'];
						// $t[$uid]["$seriesdesc"."_acc_minz_$i"] = $rowC['acc_minz'];
						// $t[$uid]["$seriesdesc"."_acc_maxx_$i"] = $rowC['acc_maxx'];
						// $t[$uid]["$seriesdesc"."_acc_maxy_$i"] = $rowC['acc_maxy'];
						// $t[$uid]["$seriesdesc"."_acc_maxz_$i"] = $rowC['acc_maxz'];
						// $t[$uid]["$seriesdesc"."_rot_minp_$i"] = $rowC['rot_minp'];
						// $t[$uid]["$seriesdesc"."_rot_minr_$i"] = $rowC['rot_minr'];
						// $t[$uid]["$seriesdesc"."_rot_miny_$i"] = $rowC['rot_miny'];
						// $t[$uid]["$seriesdesc"."_rot_maxp_$i"] = $rowC['rot_maxp'];
						// $t[$uid]["$seriesdesc"."_rot_maxr_$i"] = $rowC['rot_maxr'];
						// $t[$uid]["$seriesdesc"."_rot_maxy_$i"] = $rowC['rot_maxy'];
					// }
					
					// $timeSinceDose = GetTimeSinceDose($drugdoses, $seriesdatetime, $doseDisplayTime);
					// if ($timeSinceDose != null)
						// $t[$uid]["$seriesdesc-TimeSinceDose-$doseDisplayTime-$i"] = $timeSinceDose;
					
					// $i++;
				// }
			// }

			// /* get all of the measures */
			// if ($a['includeallmeasures']) {
				// $sqlstringA = "select a.*, b.measure_name from measures a left join measurenames b on a.measurename_id = b.measurename_id where enrollment_id = $enrollmentid";
				// $resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
				// $i=1;
				// while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					// /* add the measure info to this row */
					// $t[$uid]["measure_startdatetime_$i"] = $rowA['measure_startdate'];
					// $t[$uid]["measure_Duration_$i"] = $rowA['measure_duration'];
					// $t[$uid]["measure_Enddatetime_$i"] = $rowA['measure_enddate'];
					// $measurename = $rowA['measure_name'];
					// $t[$uid]["measure_$measurename"."_$i"] = $rowA['measure_value'];

					// $timeSinceDose = GetTimeSinceDose($drugdoses, $rowA['measure_startdate'], $doseDisplayTime);
					// if ($timeSinceDose != null)
						// $t[$uid]["$measurename-TimeSinceDose-$doseDisplayTime-$i"] = $timeSinceDose;
					
					// $i++;
				// }
			// }
			
			// /* get all of the vitals */
			// if ($a['includeallvitals']) {
				// $sqlstringA = "select a.*, b.vital_name from vitals a left join vitalnames b on a.vitalname_id = b.vitalname_id where a.enrollment_id = $enrollmentid";
				// $resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
				// $i=1;
				// while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					// /* add the measure info to this row */
					// $t[$uid]["vital_startdatetime_$i"] = $rowA['vital_startdate'];
					// $t[$uid]["vital_duration_$i"] = $rowA['vital_duration'];
					// $t[$uid]["vital_enddatetime_$i"] = $rowA['vital_enddate'];
					// $vitalname = $rowA['vital_name'];
					// $t[$uid]["vital_$vitalname"."_$i"] = $rowA['vital_value'];
					
					// $timeSinceDose = GetTimeSinceDose($drugdoses, $rowA['vital_startdate'], $doseDisplayTime);
					// if ($timeSinceDose != null)
						// $t[$uid]["$vitalname-TimeSinceDose-$doseDisplayTime-$i"] = $timeSinceDose;
					
					// $i++;
				// }
			// }
			
			// /* get all of the drugs/dosing */
			// if ($a['includealldrugs']) {
				// $sqlstringA = "select a.*, b.drug_name from drugs a left join drugnames b on a.drugname_id = b.drugname_id where a.enrollment_id = $enrollmentid";
				// $resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
				// $i=1;
				// while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					// /* add the measure info to this row */
					// $t[$uid]["drug_startdatetime_$i"] = $rowA['drug_startdate'];
					// $t[$uid]["drug_duration_$i"] = $rowA['drug_duration'];
					// $t[$uid]["drug_enddatetime_$i"] = $rowA['drug_enddate'];
					// $drugname = $rowA['drug_name'];
					// $t[$uid]["drug_$drugname"."_$i"] = $rowA['drug_value'];

					// $timeSinceDose = GetTimeSinceDose($drugdoses, $rowA['drug_startdate'], $doseDisplayTime);
					// if ($timeSinceDose != null)
						// $t[$uid]["$drugname-TimeSinceDose-$doseDisplayTime-$i"] = $timeSinceDose;
					
					// $i++;
				// }
			// }

			// /* get the pipeline info */
			// if (($a['pipelineresultname'] != "") && ($a['pipelineid'] != "NONE")) {
				// /* get the pipeline result names first (due to MySQL bug which prevents joining in this table in the main query) */
				// $resultnameids = array();
				// $sqlstringX = "select * from analysis_resultnames where " . CreateSQLSearchString("result_name", $a['pipelineresultname']);
				// $resultX = MySQLiQuery($sqlstringX,__FILE__,__LINE__);
				// while ($rowX = mysqli_fetch_array($resultX, MYSQLI_ASSOC)) {
					// $resultnameids[] = $rowX['resultname_id'];
					// $resultnames[$rowX['resultname_id']] = $rowX['result_name'];
				// }

				// if (count($resultnameids) > 0) {
					// $sqlstringA = "SELECT c.study_datetime, c.study_height, c.study_weight, c.study_type, c.study_id, c.study_num, c.study_modality, e.birthdate, TIMESTAMPDIFF( MONTH, e.birthdate, c.study_datetime ) 'ageinmonths', b.* FROM analysis a LEFT JOIN analysis_results b ON a.analysis_id = b.analysis_id LEFT JOIN studies c ON a.study_id = c.study_id LEFT JOIN enrollment d on c.enrollment_id = d.enrollment_id LEFT JOIN subjects e ON d.subject_id = e.subject_id WHERE e.isactive = 1 AND d.project_id = $projectid AND a.pipeline_id = " . $a['pipelineid'] . " AND b.result_nameid IN(" . implode2(",", $resultnameids) . ") AND b.result_type = 'v' AND e.subject_id = $subjectid ORDER BY c.study_num, c.study_datetime";
					
					// $laststudyid = "";
					
					// /* create a hash of series datetimes */
					// $resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
					// if (mysqli_num_rows($resultA) > 0) {
						// while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {

							// $studyage = $rowA['ageinmonths']/12.0;
							// $studydatetime = $rowA['study_datetime'];
							// $studyheight = $rowA['study_height'];
							// $studyweight = $rowA['study_weight'];
							// $studynum = $rowA['study_num'];
							// $studyid = $rowA['study_id'];
							// $studyvisit = $rowA['study_type'];
							// $studymodality = strtolower($rowA['study_modality']);
							
							// if (($studyage == "") || ($studyage == "null") || ($studyage == 0)) $age = strtotime($studydate) - strtotime($subj['dob']);
							// else $age = $studyage;
							
							// if (($studyheight == "") || ($studyheight == "null") || ($studyheight == 0)) $height = $subj['height'];
							// else $height = $studyheight;
							
							// if (($studyweight == "") || ($studyweight == "null") || ($studyweight == 0)) $weight = $subj['weight'];
							// else $weight = $studyweight;
							
							// //if ( ($studyid != $laststudyid) && ($laststudyid != "") )
							// //	$row++;
							
							// /* need to add the demographic info to every row */
							// //$t[$uid]['Row'] = $row;
							// //$t[$uid]['UID'] = $subj['uid'];
							// //$t[$uid]['Sex'] = $subj['sex'];
							// //$t[$uid]['Age'] = $age;
							// //$t[$uid]['Height'] = $height;
							// //$t[$uid]['Weight'] = $weight;
							// //$t[$uid]['EnrollGroup'] = $subj['enrollgroup'];
							// //$t[$uid]['AltUIDs'] = $subj['altuids'];
							// //$t[$uid]['Pipeline-StudyID'] = $subj['uid'] . $studynum;
							// //$t[$uid]['Pipeline-StudyDateTime'] = $studydatetime;
							// //$t[$uid]['VisitType'] = $studyvisit;

							// $resultname = $resultnames[$rowA['result_nameid']];
							
							// /* add the measure info to this row */
							// $t[$uid]["Pipeline_$resultname"."_$i"] = $rowA['result_value'];

							// /* if we should search for a series datetime */
							// $variabledatetime = $studydatetime;
							// if ($a['pipelineseriesdatetime'] != "") {
								// $sqlstringB = "select series_datetime from $studymodality" . "_series where (" . CreateSQLSearchString("series_protocol", $a['pipelineseriesdatetime']) . ") and study_id = $studyid limit 1";
								// //PrintSQL($sqlstringB);
								// $resultB = MySQLiQuery($sqlstringB,__FILE__,__LINE__);
								// if (mysqli_num_rows($resultB) > 0) {
									// $rowB = mysqli_fetch_array($resultB, MYSQLI_ASSOC);
									// $variabledatetime = $rowB['series_datetime'];
									// $t[$uid]["Pipeline-SeriesDateTime-$resultname-$i"] = $variabledatetime;
								// }
							// }

							// $timeSinceDose = GetTimeSinceDose($drugdoses, $variabledatetime, $doseDisplayTime);
							// if ($timeSinceDose != null)
								// $t[$uid][$resultname . "_TimeSinceDose_$doseDisplayTime"."_$i"] = $timeSinceDose;
							
							// $hasdata = true;
							// $laststudyid = $studyid;
						// }
						// //$row++;
					// }
				// }
				// else {
					// echo "Result names not found [$sqlstringX]";
				// }
			// }
			
			// /* add a row if the subject had no data */
			// if ((!$hasdata) && ($a['includeemptysubjects'] == 1)) {
				// $t[$uid]['UID'] = $uid;
				// $t[$uid]['Sex'] = $sex;
				// $t[$uid]['SubjectHeight'] = $subjectheight;
				// $t[$uid]['SubjectWeight'] = $subjectweight;
				// $t[$uid]['EnrollGroup'] = $enrollgroup;
				// $t[$uid]['AltUIDs'] = $altuids;
			// }
		// }
		
		// /* create table header */
		// $h2 = array();
		// foreach ($t as $row => $subj) {
			// foreach ($subj as $col => $vals) {
				// $h2[$col] = "";
			// }
		// }
		// $h = array_keys($h2);
		
		// return array($h, $t);
	}


	/* -------------------------------------------- */
	/* ------- PrintCSV --------------------------- */
	/* -------------------------------------------- */
	function PrintCSV($h, $t, $format) {
		
		$csv = "";
		$cols = array();
		
		$csv .= implode2(",", $h) . "\n";
		
		foreach ($t as $id => $subject) {
			$cols = array();
			foreach ($h as $col) {
				if (($t[$id][$col] == "") && ($a['missingvalueplaceholder'] != ""))
					$cols[] = $a['missingvalueplaceholder'];
				else
					$cols[] = $t[$id][$col];
			}
			$csv .= implode2(",", $cols) . "\n";
		}

		header("Content-Description: File Transfer");
		header("Content-Disposition: attachment; filename=NiDB-AnalysisBuilder-$format.csv");
		header("Content-Type: text/plain");
		header("Content-length: " . strlen($csv) . "\n\n");
		header("Content-Transfer-Encoding: text");
		// output csv to the browser
		echo $csv;
	}


	/* -------------------------------------------- */
	/* ------- PrintTable ------------------------- */
	/* -------------------------------------------- */
	function PrintTable($h, $t, $a) {
		$reportformat = $a['reportformat'];
		
		$numcols = count($h);
		$numrows = count($t);
		
		if ($numcols == 0 && $numrows == 0) {
			?>
			No data to display
			<?
			return;
		}
		
		?>
		<span style="padding-left: 15px">Displaying <b><?=$numrows?></b> rows by <b><?=$numcols?></b> columns</span>
		<br><br>
		<table class="summarytable">
			<thead>
				<tr>
				<?
				foreach ($h as $col) {
					?><th><?=$col?></th><?
				}
				?>
				</tr>
			</thead>
			<tbody>
				<?
				foreach ($t as $id => $subject) {
					?>
					<tr>
					<?
						foreach ($h as $col) {
							if (is_numeric($t[$id][$col]) && (strpos($t[$id][$col], '.') !== false))
								$disp = number_format($t[$id][$col], 3);
							else
								if (($t[$id][$col] == "") && ($a['missingvalueplaceholder'] != ""))
									$disp = $a['missingvalueplaceholder'];
								else
									$disp = $t[$id][$col];
							?><td><?=$disp?></td><?
						}
					?>
					</tr>
				<? } ?>
			</tbody>
		</table>
		<?
	}


	/* -------------------------------------------- */
	/* ------- GetTimeSinceDose ------------------- */
	/* -------------------------------------------- */
	function GetTimeSinceDose($drugdoses, $event, $doseDisplayTime) {
		$eventParts = date_parse($event);
		
		//PrintVariable($eventParts);
		$dosetimes = array();
		//foreach ($drugdoses as $d) {
		//	$dosetimes[] = $d['date'];
		//}
		
		//PrintVariable($drugdoses);
		
		$timeSinceDose = null;
		foreach ($drugdoses as $d) {
			//PrintVariable($d);
			
			$dtime = $d['date'];
			$doseamount = $d['doseamount'];
			$dosekey = $d['dosekey'];
			
			$dtimeParts = date_parse($dtime);
			
			/* check if the event is on the same date as the dose datetime */
			if ( ($eventParts['day'] == $dtimeParts['day']) && ($eventParts['month'] == $dtimeParts['month']) && ($eventParts['years'] == $dtimeParts['years']) ) {
				/* get date diff in seconds */
				$dt = strtotime($dtime);
				$et = strtotime($event);

				$timeSinceDose = $et - $dt;
				
				if ($doseDisplayTime == "min")
					$timeSinceDose = $timeSinceDose/60;
				if ($doseDisplayTime == "hour")
					$timeSinceDose = $timeSinceDose/60/60;
				
				break;
			}
		}
		
		return array($timeSinceDose, $doseamount, $dosekey);
	}


	/* -------------------------------------------- */
	/* ------- CreateSQLSearchString -------------- */
	/* -------------------------------------------- */
	function CreateSQLSearchString($variable, $str) {
		/* input string can be in the following format, and the output should be the adjacent format
		   taskname				variable = 'taskname'
		   task*				variable like 'task%'
		   'task*'				variable = 'task*'
		*/
		//PrintVariable($variable);
		//PrintVariable($str);
		
		if (is_array($str))
			$str2 = implode2(",", $str);
		else
			$str2 = $str;

		$strings = array();
		/* split string by commas */
		$parts = explode(",", $str2);
		foreach ($parts as $item) {
			$s = trim($item);

			if (($s != "*") && ($s != "")) {
				/* check if it has apostrophes at beginning and end */
				if ( ($s[0] == "'") && (substr($s,-1)) ) {
					$s = trim($s, "'");
					
					$strings[] = "($variable = '$s')";
				}
				else {
					if (contains($s, "*")) {
						$s = str_replace("*", "%", $s);
						$s = mysqli_real_escape_string($GLOBALS['linki'], $s);
						$strings[] = "($variable like '$s')";
					}
					else {
						$s = mysqli_real_escape_string($GLOBALS['linki'], $s);
						$strings[] = "($variable = '$s')";
					}
				}
			}
			elseif ($s == "*") {
				$strings[] = "($variable like '%')";
			}
		}
		
		return implode2(" or ", $strings);
	}
	
?>


<? include("footer.php") ?>
