<?
 // ------------------------------------------------------------------------------
 // NiDB analysisbuilder.php
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
    $a['includeallobservations'] = GetVariable("includeallobservations");
    $a['observationname'] = GetVariable("observationname");
    $a['includeallvitals'] = GetVariable("includeallvitals");
    $a['vitalname'] = GetVariable("vitalname");
    $a['includeallinterventions'] = GetVariable("includeallinterventions");
    $a['includeinterventiondetails'] = GetVariable("includeinterventiondetails");
    $a['interventionname'] = GetVariable("interventionname");
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
	$a['pinnedvariable'] = trim(GetVariable("pinnedvariable"));
	$a['distancevariable'] = trim(GetVariable("distancevariable"));
	
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

			PrintVariable($a['pinnedvariable']);
			
			if ($a['pinnedvariable'] == "") {
				list($h, $t, $n) = CreateLongReport($projectid, $a);
				PrintCSV($h,$t,'long');
			}
			else {
				list($h, $t, $n) = TimelineCorrelationReport($projectid, $a);
				PrintCSV($h,$t,'long');
			}
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
		$MRprotocols = implode2(",", mysqli_real_escape_array($GLOBALS['linki'], $a['mr_protocols']));
		$EEGprotocols = implode2(",", mysqli_real_escape_array($GLOBALS['linki'], $a['eeg_protocols']));
		$ETprotocols = implode2(",", mysqli_real_escape_array($GLOBALS['linki'], $a['et_protocols']));
		$pipelineid = mysqli_real_escape_string($GLOBALS['linki'], $a['pipelineid']);
		$pipelineresultname = mysqli_real_escape_string($GLOBALS['linki'], $a['pipelineresultname']);
		$pipelineseriesdatetime = mysqli_real_escape_string($GLOBALS['linki'], $a['pipelineseriesdatetime']);
		$includeprotocolparms = mysqli_real_escape_string($GLOBALS['linki'], $a['includeprotocolparms']);
		$includemrqa = mysqli_real_escape_string($GLOBALS['linki'], $a['includemrqa']);
		$groupmrbyvisittype = mysqli_real_escape_string($GLOBALS['linki'], $a['groupmrbyvisittype']);
		$includeallobservations = mysqli_real_escape_string($GLOBALS['linki'], $a['includeallobservations']);
		$includeallvitals = mysqli_real_escape_string($GLOBALS['linki'], $a['includeallvitals']);
		$includeinterventiondetails = mysqli_real_escape_string($GLOBALS['linki'], $a['includeinterventiondetails']);
		$includetimesincedose = mysqli_real_escape_string($GLOBALS['linki'], $a['includetimesincedose']);
		$doseVariable = mysqli_real_escape_string($GLOBALS['linki'], $a['dosevariable']);
		$doseTimeRange = mysqli_real_escape_string($GLOBALS['linki'], $a['dosetimerange']);
		$doseDisplayTime = mysqli_real_escape_string($GLOBALS['linki'], $a['dosedisplaytime']);
		$groupByDate = mysqli_real_escape_string($GLOBALS['linki'], $a['groupbydate']);
		$includeemptysubjects = mysqli_real_escape_string($GLOBALS['linki'], $a['includeemptysubjects']);
		$reportformat = mysqli_real_escape_string($GLOBALS['linki'], $a['reportformat']);
		$outputformat = mysqli_real_escape_string($GLOBALS['linki'], $a['outputformat']);
		$observationname = mysqli_real_escape_string($GLOBALS['linki'], $a['observationname']);
		$vitalname = mysqli_real_escape_string($GLOBALS['linki'], $a['vitalname']);
		$interventionname = mysqli_real_escape_string($GLOBALS['linki'], $a['interventionname']);
		$includeallinterventions = mysqli_real_escape_string($GLOBALS['linki'], $a['includeallinterventions']);
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
		if ($includeallobservations == "") $includeallobservations = "null";
		if ($includeallvitals == "") $includeallvitals = "null";
		if ($includeinterventiondetails == "") $includeinterventiondetails = "null";
		if ($includetimesincedose == "") $includetimesincedose = "null";
		if ($includeemptysubjects == "") $includeemptysubjects = "null";
		if ($includeallinterventions == "") $includeallinterventions = "null";
		if ($includeenddate == "") $includeenddate = "null";
		if ($includeheightweight == "") $includeheightweight = "null";
		if ($includedob == "") $includedob = "null";
		if ($includeduration == "") $includeduration = "null";
		if ($collapsevariables == "") $collapsevariables = "null";

		$sqlstring = "select savedsearch_id from saved_search where saved_name = '$savedsearchname'";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if (mysqli_num_rows($result) > 0){
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$savedsearchid = $row['savedsearch_id'];

			$sqlstring = "update saved_search 
			set user_id = '$userid',
			saved_datetime = now(), 
			search_projectid = '$projectid', 
			search_mrincludeprotocolparams = $includeprotocolparms, 
			search_mrincludeqa = $includemrqa, 
			search_groupmrbyvisittype = '$groupmrbyvisittype', 
			search_mrprotocol = '$MRprotocols', 
			search_eegprotocol = '$EEGprotocols', 
			search_etprotocol = '$ETprotocols', 
			search_pipelineid = $pipelineid, 
			search_pipelineresultname = '$pipelineresultname', 
			search_pipelineseries = '$pipelineseriesdatetime', 
			search_observationname = '$observationname', 
			search_includeallobservations = $includeallobservations, 
			search_vitalname = '$vitalname', 
			search_includeallvitals = $includeallvitals, 
			search_interventionname = '$interventionname', 
			search_includeallinterventions = $includeallinterventions, 
			search_includeinterventiondetails = $includeinterventiondetails, 
			search_includetimesincedose = $includetimesincedose, 
			search_dosevariable = '$doseVariable', 
			search_groupdosetime = '$doseTimeRange', 
			search_displaytime = '$doseDisplayTime', 
			search_groupbyeventdate = '$groupByDate', 
			search_collapsevariables = '$collapsevariables', 
			search_collapseexpression = '$collapsebyexpression', 
			search_includeemptysubjects = $includeemptysubjects, 
			search_blankvalue = '$blankValue', 
			search_missingvalue = '$missingValue', 
			search_includeeventduration = $includeduration, 
			search_includeendate = $includeenddate, 
			search_includeheightweight = $includeheightweight, 
			search_includedob = $includedob, 
			search_reportformat = '$reportformat', 
			search_outputformat = '$outputformat'
			where savedsearch_id = $savedsearchid";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			
			Notice("Search updated <b>$savedsearchname</b>");
		}
		else {
			$sqlstring = "insert into saved_search (
			user_id,saved_datetime, saved_name, search_projectid, search_mrincludeprotocolparams, search_mrincludeqa, search_groupmrbyvisittype, search_mrprotocol, search_eegprotocol, search_etprotocol, search_pipelineid, search_pipelineresultname, search_pipelineseries, search_observationname, search_includeallobservations, search_vitalname, search_includeallvitals, search_interventionname, search_includeallinterventions, search_includeinterventiondetails, search_includetimesincedose, search_dosevariable, search_groupdosetime, search_displaytime, search_groupbyeventdate, search_collapsevariables, search_collapseexpression, search_includeemptysubjects, search_blankvalue, search_missingvalue, search_includeeventduration, search_includeendate, search_includeheightweight, search_includedob, search_reportformat, search_outputformat)
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
				'$observationname',
				$includeallobservations,
				'$vitalname',
				$includeallvitals,
				'$interventionname',
				$includeallinterventions,
				$includeinterventiondetails,
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

	}
	
	
	/* -------------------------------------------- */
	/* ------- LoadSavedSearch -------------------- */
	/* -------------------------------------------- */
	function LoadSavedSearch($savedsearchid) {
		$savedsearchid = mysqli_real_escape_string($GLOBALS['linki'], $savedsearchid);
		
		$a = array();
		
		if ($savedsearchid != "") {
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
			$a['includeallobservations'] = $row['search_includeallobservations'];
			$a['observationname'] = $row['search_observationname'];
			$a['includeallvitals'] = $row['search_includeallvitals'];
			$a['vitalname'] = $row['search_vitalname'];
			$a['includeallinterventions'] = $row['search_includeallinterventions'];
			$a['includeinterventiondetails'] = $row['search_includeinterventiondetails'];
			$a['interventionname'] = $row['search_interventionname'];
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
			$a['savedsearchname'] = $row['saved_name'];
			
			$a['includeduration'] = $row['search_includeeventduration'];
			$a['includeenddate'] = $row['search_includeendate'];
			$a['includeheightweight'] = $row['search_includeheightweight'];
			$a['includedob'] = $row['search_includedob'];
		}
		
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
		<script>
			$(document).ready(function(){
				$('#pageloading').hide();
				document.getElementById("resultsTable").style.height = screen.availHeight * 0.75;
			});

			window.onload = function exampleFunction() { 
				//console.log('The Script will load now.');
				CheckForInterventionCriteria();
				CheckForEEGCriteria();
				CheckForETCriteria();
				CheckForMRICriteria();
				CheckForObservationCriteria();
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
			
			/* observation */
			function CheckForObservationCriteria() {
				if ((document.getElementById("observationname").value != "") || (document.getElementById("includeallobservations").checked == true) ) {
					document.getElementById("observationIndicator").innerHTML = "<div class='ui small yellow label'><i class='black tasks icon'></i>has search criteria</div>";
				}
				else {
					document.getElementById("observationIndicator").innerHTML = "";
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
			
			/* interventions */
			function CheckForInterventionCriteria() {
				if ((document.getElementById("interventionname").value != "") || (document.getElementById("includeallinterventions").checked == true) || (document.getElementById("includeinterventiondetails").checked == true) || (document.getElementById("includetimesincedose").checked == true) || (document.getElementById("dosevariable").checked == true) ) {
					document.getElementById("interventionIndicator").innerHTML = "<div class='ui small yellow label'><i class='black tasks icon'></i>has search criteria</div>";
				}
				else {
					document.getElementById("interventionIndicator").innerHTML = "";
				}
			}
			
		</script>
		<style>
			.indicator { font-size: smaller; padding-left: 10px; white-space: nowrap; }
			input { padding: 3px; }
		</style>		

		<div class="ui grey secondary inverted segment">
			<div class="ui very compact grid">
				<div class="ui four wide column">
					<h2 class="ui inverted header">Analysis Builder</h2>
				</div>
				<div class="ui eight wide column">
					<div style="text-align: center; width: 100%" id="pageloading">
						<i class="large inverted blue spinner loading icon"></i> Loading...
					</div>
				</div>
				<div class="ui four wide right aligned column">
					<form method="post" action="analysisbuilder.php" class="ui form">
						<input type="hidden" name="action" value="usesavedsearch">
						<div class="ui fluid action input">
							<select name="savedsearchid" class="ui fluid dropdown" required>
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
							<button class="ui button">Use saved search</button>
						</div>
					</form>
				</div>
			</div>
		</div>

		<?
		//if (($projectid == '') || ($projectid == 0)) {
			//return;
		//}
		?>

		<div class="ui grid">
			<div class="ui four wide column">

					<div class="ui styled segment">
						<form method="post" name="analysisbuilder" action="analysisbuilder.php" class="ui small form">
						<input type="hidden" name="action" value="viewanalysissummary">
						<input type="hidden" name="projectid" value="<?=$projectid?>">

						<div class="ui fluid labeled input">
							<div class="ui label">
							Project
							</div>
							<select name="projectid" class="ui fluid search dropdown" required>
								<option value="">Select Project...</option>
								<option value="0">All Projects</option>
								<?
									$sqlstring = "select * from projects a left join user_project b on a.project_id = b.project_id where b.user_id = (select user_id from users where username = '" . $_SESSION['username'] . "') and a.instance_id = '" . $_SESSION['instanceid'] . "' order by project_name";
									
									$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
									while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
										$project_id = $row['project_id'];
										$project_name = $row['project_name'];
										$project_costcenter = $row['project_costcenter'];
										if ($projectid == $project_id)
											$selected = "selected";
										else
											$selected = "";
										?>
										<option value="<?=$project_id?>" <?=$selected?>><?=$project_name?> (<?=$project_costcenter?>)</option>
										<?
									}
								?>
							</select>
						</div>
						
						<h4 class="ui dividing blue header">Select Data</h4>
						<div class="ui top attached styled accordion">
							<div class="title" style="padding:5px;">
								<h3 class="ui black header"><i class="dropdown icon"></i>MR&nbsp;<span id="mriIndicator" class="indicator"></span></h3>
							</div>
							<div class="content">
								<div class="ui checkbox">
									<input type="checkbox" name="includeprotocolparms" id="includeprotocolparms" <? if ($a['includeprotocolparms']) { echo "checked"; } ?> value="1" onChange="CheckForMRICriteria()">
									<label>Include protocol parameters</label>
								</div>
								<br>
								<div class="ui checkbox">
									<input type="checkbox" name="includemrqa" id="includemrqa" <? if ($a['includemrqa']) { echo "checked"; } ?> value="1" onChange="CheckForMRICriteria()">
									<label>Include QA</label>
								</div>
								<br>
								<div class="ui checkbox">
									<input type="checkbox" name="groupmrbyvisittype" id="groupmrbyvisittype" <? if ($a['groupmrbyvisittype']) { echo "checked"; } ?> value="1" onChange="CheckForMRICriteria()">
									<label>Separate by study visit type</label>
								</div>
								<br>
								<div class="ui inline field">
									<label>Protocol(s)</label>
									<select name="mr_protocols[]" id="mr_protocols" multiple class="ui search fluid dropdown" onChange="CheckForMRICriteria()">
										<option value="" <? if (!is_null($a) && (in_array("NONE", $a['mr_protocols']) || ($a['mr_protocols'] == ""))) echo "selected"; ?>>Select MRI protocol(s)...
										<option value="ALLPROTOCOLS" <? if (in_array("ALLPROTOCOLS", $a['mr_protocols'])) echo "selected"; ?>>(ALL protocols)
										<?
										/* get unique list of MR protocols from this project */
										if ($projectid == "")
											$sqlstring = "select a.series_desc from mr_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id where a.series_desc <> '' and a.series_desc is not null group by series_desc order by series_desc";
										else
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
							</div>

							<div class="title" style="padding:5px;">
								<h3 class="ui black header"><i class="dropdown icon"></i>EEG&nbsp;<span id="eegIndicator" class="indicator"></span></h3>
							</div>
							<div class="content">
								<div class="ui inline field">
									<label>EEG Protocol</label>
									<select name="eeg_protocols[]" id="eeg_protocols" multiple class="ui search fluid dropdown" onChange="CheckForEEGCriteria()">
										<option value="" <? if (in_array("NONE", $a['eeg_protocols']) || ($a['eeg_protocols'] == "")) echo "selected"; ?>>Select EEG protocol(s)...
										<option value="ALLPROTOCOLS" <? if (in_array("ALLPROTOCOLS", $a['eeg_protocols'])) echo "selected"; ?>>(ALL protocols)
										<?
										/* get unique list of EEG protocols from this project */
										if ($projectid == "")
											$sqlstring = "select a.series_desc from eeg_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id where a.series_desc <> '' and a.series_desc is not null group by series_desc order by series_desc";
										else
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
							</div>
							
							<div class="title" style="padding:5px;">
								<h3 class="ui black header"><i class="dropdown icon"></i>ET&nbsp;<span id="etIndicator" class="indicator"></span></h3>
							</div>
							<div class="content">
								<div class="ui field">
									<label>ET Protocol</label>
									<select name="et_protocols[]" id="et_protocols" multiple onChange="CheckForETCriteria()" class="ui search dropdown">
										<option value="" <? if (in_array("NONE", $a['et_protocols']) || ($a['et_protocols'] == "")) echo "selected"; ?>>Select ET protocol(s)...
										<option value="ALLPROTOCOLS" <? if (in_array("ALLPROTOCOLS", $a['et_protocols'])) echo "selected"; ?>>(ALL protocols)
										<?
										/* get unique list of ET protocols from this project */
										if ($projectid == "")
											$sqlstring = "select a.series_desc from et_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id where a.series_desc <> '' and a.series_desc is not null group by series_desc order by series_desc";
										else
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
							</div>
							
							<div class="title" style="padding:5px;">
								<h3 class="ui black header"><i class="dropdown icon"></i>Pipeline&nbsp;<span id="pipelineIndicator" class="indicator"></span></h3>
							</div>
							<div class="content">
								<div class="ui field">
									Pipeline
									<select class="ui search dropdown" name="pipelineid[]" id="pipelineid" onChange="CheckForPipelineCriteria()" multiple="multiple" style="width: 100%"><?
										if ($projectid == "")
											$sqlstring2 = "select pipeline_id, pipeline_name from pipelines where pipeline_id in (select a.pipeline_id from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id group by a.pipeline_id) order by pipeline_name";
										else
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
									?>
									</select>
								</div>
								<div class="ui field">
									Result name <i class="small blue question circle outline icon" title="For all text fields: Use * as a wildcard. Enclose strings in 'apostrophes' to search for exact match (or to match the * character). Separate multiple names with commas"></i>
									<input type="text" name="pipelineresultname" id="pipelineresultname" value="<?=$a['pipelineresultname']?>" onChange="CheckForPipelineCriteria()">
								</div>
								<div class="ui field">
									Get Datetime from Series. Enter series description <i class="small blue question circle outline icon" title="Try to obtain the date/time of the pipeline result from the series matching this value, instead of the StudyDateTime. Use * as a wildcard. Enclose strings in 'apostrophes' to search for exact match (or to match the * character). Separate multiple names with commas"></i>
									<input type="text" name="pipelineseriesdatetime" id="pipelineseriesdatetime" value="<?=$a['pipelineseriesdatetime']?>" onChange="CheckForPipelineCriteria()" placeholder="Series description...">
								</div>
							</div>
							
							<div class="title" style="padding:5px;">
								<h3 class="ui black header"><i class="dropdown icon"></i>Cognitive and Other Observations&nbsp;<span id="observationIndicator" class="indicator"></span></h3>
							</div>
							<div class="content">
								<div class="ui field">
									Observation name(s)
									<select class="ui search dropdown" name="observationname[]" id="observationname" onChange="CheckForObservationCriteria()" multiple="multiple" style="width: 100%"><?
										if ($projectid == "")
											$sqlstringA = "SELECT distinct(observation_name) from observations order by c.observation_name";
										else
											$sqlstringA = "SELECT distinct(a.observation_name) from observations a left join enrollment b on a.enrollment_id = b.enrollment_id where b.project_id = $projectid order by c.observation_name";
											
										$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
										while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
											$observationname = $rowA['observation_name'];
											if (trim($observationname) != "") {
												$selected = "";
												if (is_array($a['observationname']))
													if (in_array($observationname, $a['observationname']))
														$selected = "selected";
												if (trim($observationname) == trim($a['observationname']))
													$selected = "selected";
												?><option value="<?=$observationname?>" <?=$selected?>><?=$observationname?><?
											}
										}
									?>
									</select>
								</div>
								<div class="ui checkbox">
									<input type="checkbox" name="includeallobservations" id="includeallobservations" value="1" <? if ($a['includeallobservations']) echo "checked"; ?> onChange="CheckForObservationCriteria()">
									<label>Include all observations</label>
								</div>
							</div>
							
							<div class="title" style="padding:5px;">
								<h3 class="ui black header"><i class="dropdown icon"></i>Biological Observations&nbsp;<span id="vitalIndicator" class="indicator"></span></h3>
							</div>
							<div class="content">
								<div class="ui field">
									<label>Vital name(s)</label>
									<select class="ui search dropdown" name="vitalname[]" id="vitalname" onChange="CheckForVitalCriteria()" multiple="multiple" style="width: 100%"><?
										if ($projectid == "")
											$sqlstringA = "SELECT distinct(c.vital_name) FROM vitals a left join enrollment b on a.enrollment_id = b.enrollment_id left join vitalnames c on a.vitalname_id = c.vitalname_id order by c.vital_name";
										else
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
									?>
									</select>
								</div>
								<div class="ui checkbox">
									<input type="checkbox" name="includeallvitals" id="includeallvitals" value="1" <? if ($a['includeallvitals']) echo "checked"; ?> onChange="CheckForVitalCriteria()">
									<label>Include all vitals</label>
								</div>
							</div>

							<div class="title" style="padding:5px;">
								<h3 class="ui black header"><i class="dropdown icon"></i>Interventions/Dosing&nbsp;<span id="interventionIndicator" class="indicator"></span></h3>
							</div>
							<div class="content">
								<div class="ui field">
									<label>Intervention variable name(s) <i class="small blue question circle outline icon" title="Find all of the following interventions and display the 'value'. Depending on where the data was imported from, 'value' may likely be blank"></i></label>
									<select class="ui search dropdown" name="interventionname[]" id="interventionname" onChange="CheckForInterventionCriteria()" multiple="multiple" style="width: 100%">
									<?
										if ($projectid == "")
											$sqlstringA = "SELECT distinct(c.intervention_name) FROM interventions a left join enrollment b on a.enrollment_id = b.enrollment_id left join interventionnames c on a.interventionname_id = c.interventionname_id order by c.intervention_name";
										else
											$sqlstringA = "SELECT distinct(c.intervention_name) FROM interventions a left join enrollment b on a.enrollment_id = b.enrollment_id left join interventionnames c on a.interventionname_id = c.interventionname_id where b.project_id = $projectid order by c.intervention_name";
											
										$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
										while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
											$interventionname = $rowA['intervention_name'];
											$selected = "";
											if (is_array($a['interventionname']))
												if (in_array($interventionname, $a['interventionname']))
													$selected = "selected";
											if (trim($interventionname) == trim($a['interventionname']))
												$selected = "selected";
											?><option value="<?=$interventionname?>" <?=$selected?>><?=$interventionname?><?
										}
									?>
									</select>
								</div>
								<div class="ui checkbox">
									<input type="checkbox" name="includeallinterventions" id="includeallinterventions" value="1" <? if ($a['includeallinterventions']) echo "checked"; ?>>
									<label>Include all intervention/dosing variables</label>
								</div>
								<br>
								<div class="ui checkbox">
									<input type="checkbox" name="includeinterventiondetails" id="includeinterventiondetails" value="1" <? if ($a['includeinterventiondetails']) echo "checked"; ?>>
									<label>Include intervention/dose extended details</label>
								</div>
								<div class="ui styled segment">
									<div class="ui checkbox">
										<input type="checkbox" name="includetimesincedose" id="includetimesincedose" value="1" <? if ($a['includetimesincedose']) echo "checked"; ?> onChange="CheckForInterventionCriteria()">
										<label>Include <b>time since dose</b> <i class="small blue question circle outline icon" title="Includes this dose as the first dose of the specified time period, and calculates 'time since dose' for all other events that happen within the specified timeframe"></i></label>
									</div>

									<div class="ui field">
										<label>Dose variable(s)</label>
										<select class="ui search dropdown" name="dosevariable[]" id="dosevariable" onChange="CheckForInterventionCriteria()" multiple="multiple">
										<?
											if ($projectid == "")
												$sqlstringA = "SELECT distinct(c.intervention_name) FROM interventions a left join enrollment b on a.enrollment_id = b.enrollment_id left join interventionnames c on a.interventionname_id = c.interventionname_id order by c.intervention_name";
											else
												$sqlstringA = "SELECT distinct(c.intervention_name) FROM interventions a left join enrollment b on a.enrollment_id = b.enrollment_id left join interventionnames c on a.interventionname_id = c.interventionname_id where b.project_id = $projectid order by c.intervention_name";
											$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
											while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
												$interventionname = $rowA['intervention_name'];
												$selected = "";
												if (is_array($a['dosevariable']))
													if (in_array($interventionname, $a['dosevariable']))
														$selected = "selected";
												if (trim($interventionname) == trim($a['dosevariable']))
													$selected = "selected";
												?><option value="<?=$interventionname?>" <?=$selected?>><?=$interventionname?><?
											}
										?>
										</select>
									</div>

									<div class="ui field">
										<label>Group dose time by</label>
										<select name="dosetimerange" id="dosetimerange" onChange="CheckForInterventionCriteria()" class="ui dropdown">
											<!--<option value="hour">Hour-->
											<option value="day" selected>Day
											<!--<option value="week">Week
											<option value="month">Month
											<option value="year">Year-->
										</select>
									</div>

									<div class="ui field">
										<label>Display time since dose in</label>
										<select name="dosedisplaytime" id="dosedisplaytime" onChange="CheckForInterventionCriteria()" class="ui dropdown">
											<option value="sec" <? if ($a['dosedisplaytime'] == "sec") echo "selected"; ?> >Seconds
											<option value="min" <? if ( ($a['dosedisplaytime'] == "min") || ($a['dosedisplaytime'] == "")) echo "selected"; ?> >Minutes
											<option value="hour" <? if ($a['dosedisplaytime'] == "hour") echo "selected"; ?> >Hours
										</select>
									</div>
								</div>
								
							</div>
						</div>

						<h4 class="ui dividing blue header">Timeline Correlation</h4>
						<div class="ui field">
							<label>Pinned variable <i class="small blue question circle outline icon" title="From the selected variables above, this is the variable from which distance-in-time to all other selected variables will be calculated"></i></label>
							<input name="pinnedvariable" value="<?=$a['pinnedvariable']?>">
						</div>
						<div class="ui field">
							<label>Distance variable <i class="small blue question circle outline icon" title="From the selected variables above, this is the variable to which distance-in-time from the pinned variable will be calculated"></i></label>
							<input name="distancevariable" value="<?=$a['distancevariable']?>">
						</div>
						
						<h4 class="ui dividing blue header">Grouping Options</h4>
						<div class="ui checkbox">
							<input type="checkbox" name="groupbydate" value="1" <? if ($a['groupbydate']) echo "checked"; ?>>
							<label>Group by event DATE <i class="small blue question circle outline icon" title="Group output rows by UID, then <i>date</i> [<?=date('Y-m-d')?>], not date<u>time</u> [<?=date('Y-m-d H:i:s')?>]."></i></label>
						</div>
						<div class="ui inline fields">
							<div class="ui checkbox">
								<input type="checkbox" name="collapsevariables" value="1" <? if ($a['collapsevariables']) echo "checked"; ?>>
								<label>Collapse variables <i class="small blue question circle outline icon" title="Expression to match a grouping, <i>by day</i>. For example, to collapse <tt>var1_xyz</tt>, <tt>var1_abc</tt>, <tt>var1_a</tt>, into 1 row and 3 columns, use <code style='color: #000'>var#_*</code>. <tt>#</tt> represents any integer number, and <tt>*</tt> represents any string."></i></label>
							</div>
							<input type="text" name="collapsebyexpression" value="<?=$a['collapsebyexpression']?>" placeholder="Collapse by expression...">
						</div>

						<h4 class="ui dividing blue header">Output Options</h4>
						<div class="ui checkbox">
							<input type="checkbox" name="includeemptysubjects" value="1" <? if ($a['includeemptysubjects']) echo "checked"; ?>>
							<label>Include subjects without data <i class="small blue question circle outline icon" title="Includes subjects which are part of this project, but have none of the selected data"></i></label>
						</div>
						<br>
						<div class="ui field">
							<label>Blank value string <i class="small blue question circle outline icon" title="If a value exists, but the value is blank, display this string instead"></i></label>
							<input name="blankvalueplaceholder" value="<?=$a['blankvalueplaceholder']?>" required>
						</div>
						<div class="ui field">
							<label>Missing value string <i class="small blue question circle outline icon" title="If a value is missing, display this string instead"></i></label>
							<input name="missingvalueplaceholder" value="<?=$a['missingvalueplaceholder']?>" placeholder="Missing value placeholder...">
						</div>
						<div class="ui checkbox">
							<input type="checkbox" name="includeduration" value="1" <? if ($a['includeduration']) echo "checked"; ?>>
							<label>Include event duration <i class="small blue question circle outline icon" title="If an event has a start and stop time, include the duration in the output"></i></label>
						</div>
						<br>
						<div class="ui checkbox">
							<input type="checkbox" name="includeenddate" value="1" <? if ($a['includeenddate']) echo "checked"; ?>>
							<label>Include end datetime <i class="small blue question circle outline icon" title="If an event has a end date, include the end date in the output"></i></label>
						</div>
						<br>
						<div class="ui checkbox">
							<input type="checkbox" name="includeheightweight" value="1" <? if ($a['includeheightweight']) echo "checked"; ?>>
							<label>Include subject heigh/weight <i class="small blue question circle outline icon" title="Include the subject's height and weight in the output"></i></label>
						</div>
						<br>
						<div class="ui checkbox">
							<input type="checkbox" name="includedob" value="1" <? if ($a['includedob']) echo "checked"; ?>>
							<label>Include subject date of birth <i class="small blue question circle outline icon" title="Include the subject's date of birth in the output"></i></label>
						</div>
						<br>
						<table>
							<tr>
								<td width="50%">
									Reporting format<br>
									<div class="ui radio checkbox">
										<input type="radio" name="reportformat" value="long" <? if (($a['reportformat'] == "long") || ($a['reportformat'] == "")) echo "checked"; ?>>
										<label>Long</label>
									</div>
									<!--<input type="radio" name="reportformat" value="wide" <? if ($a['reportformat'] == "wide") echo "checked"; ?>>Wide-->
								</td>
								<td style="padding-left: 20px">
									Output format<br>
									<div class="ui radio checkbox">
										<input type="radio" name="outputformat" value="table" <? if (($a['outputformat'] == "table") || ($a['outputformat'] == "")) echo "checked"; ?>>
										<label>Table (screen)</label>
									</div>
									<br>
									<div class="ui radio checkbox">
										<input type="radio" name="outputformat" value="csv" <? if ($a['outputformat'] == "csv") echo "checked"; ?>>
										<label>.csv</label>
									</div>
								</td>
							</tr>
						</table>
						<br>
						<button class="ui fluid primary button" onClick="document.analysisbuilder.action.value='viewanalysissummary'; return;"><i class="search icon"></i>Update Summary</button>
						<br><br>
						<div class="ui fluid action input">
							<input type="text" name="savedsearchname" placeholder="Saved search name..." value="<?=$a['savedsearchname']?>">
							<button class="ui basic compact button" onClick="document.analysisbuilder.action.value='savesearch'; return;"><i class="save icon"></i> Save search</button>
						</div>
						</form>
					</div>
				</div>
				
				<!-- ************** right side results table ************** -->
				<div class="ui twelve wide column" style="overflow: auto; padding:0px" id="resultsTable">
					<?
						if ($a['reportformat'] == "long") {
							if ($a['pinnedvariable'] == "") {
								list($h, $t, $n) = CreateLongReport($projectid, $a);
							}
							else {
								list($h, $t, $n) = TimelineCorrelationReport($projectid, $a);
							}
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
				</div>
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
		$pinnedvariable = trim($a['pinnedvariable']);
		$distancevariable = trim($a['distancevariable']);
		
		/* create the table */
		$t;
		
		if ($projectid == "") {
			Error("Project is blank. Select a project to generate a report");
			return;
		}
		
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
			$interventiondoses = array();
			if ($a['includetimesincedose']) {
				if ($doseVariable != "") {
					$sqlstringA = "select a.*, b.intervention_name from interventions a left join interventionnames b on a.interventionname_id = b.interventionname_id where a.enrollment_id = $enrollmentid and (" . CreateSQLSearchString("b.intervention_name", $a['dosevariable']) . ")";
					$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
					$i=0;
					while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
						/* add the observation info to this row */
						$interventiondoses[$i]['date'] = $rowA['startdate'];
						$interventiondoses[$i]['doseamount'] = $rowA['doseamount'];
						$interventiondoses[$i]['dosekey'] = $rowA['dosekey'];
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
					
					list($timeSinceDose, $doseamount, $dosekey) = GetTimeSinceDose($doses, $seriesdatetime, $doseDisplayTime);
					if ($timeSinceDose != null) {
						$t[$row]["$seriesdesc-TIMESINCEDOSE-$doseDisplayTime"] = $timeSinceDose;
						$t[$row]['DoseAmount'] = $doseamount;
						$t[$row]['DoseKey'] = $dosekey;
					}
					else {
						$n .= $subj['uid'] . ": $seriesdesc-TIMESINCEDOSE-$doseDisplayTime was null. Comparing DOSE TIMES " . json_encode($doses) . " to ITEM TIME $seriesdatetime\n";
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

			/* ---------- Observations ---------- */
			if (($a['includeallobservations']) || ($a['observationname'] != "")) {
				if ($a['includeallobservations']) {
					$sqlstringA = "select * from observations where enrollment_id = $enrollmentid";
				}
				else {
					$sqlstringA = "select observation_name from observations where enrollment_id = $enrollmentid and (" . CreateSQLSearchString("observation_name", $a['observationname']) . ")";
				}
				$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {

					$observationname = $rowA['observation_name'];
					
					/* attempt to collapse variables based on the expression provided by the user */
					$timepoint = "";
					if ($collapseByVars)
						if ($collapseExpression != "") {
							/* replace all potential regex characters that the user may have entered */
							$preg = preg_quote2($collapseExpression);
							//$n .= "CollapseBy expression after preg_replace2(observations): [$preg]\n";
							
							/* replace the escaped # and * with the equivalent actual regex chars */
							$preg = str_replace("*", "+", $preg);
							$preg = "/^" . str_replace("#", "(\d+)", $preg) . "/";
							$n .= "Final collapseBy expression (observations): [$preg]\n";
							preg_match($preg, $observationname, $matches);
							$timepoint = $matches[1];
							$observationname = str_replace($timepoint, "", $observationname);
						}
						else
							$n .= "Collapse variables was selected, but an expression was not specified\n";

					if ($groupByDate || $collapseByVars)
						$row = $uid . substr($rowA['observation_startdate'], 0, 10) . $timepoint;
					else
						$row = $uid . $rowA['observation_startdate'];
					
					if ($collapseByVars)
						$t[$row]['collapseGroup'] = $timepoint;
					
					$observationvalue = $rowA['observation_value'];
					
					$dob = date_create($subj['dob']);
					$eventdate = date_create($rowA['observation_startdate']);
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

					/* add the observation info to this row */
					$t[$row]['EventDateTime'] = $rowA['observation_startdate'];
					if ($includeDuration)
						$t[$row][$observationname . '_DURATION'] = $rowA['observation_duration'];
					if ($includeEndDate)
						$t[$row][$observationname . '_ENDDATETIME'] = $rowA['observation_enddate'];
					if ($observationvalue == "")
						$t[$row][$observationname] = $blankValuePlaceholder;
					else
						$t[$row][$observationname] = $observationvalue;

					list($timeSinceDose, $doseamount, $dosekey) = GetTimeSinceDose($doses, $rowA['observation_startdate'], $doseDisplayTime);
					if ($timeSinceDose != null) {
						$t[$row]["$observationname-TimeSinceDose-$doseDisplayTime"] = $timeSinceDose;
						$t[$row]['DoseAmount'] = $doseamount;
						$t[$row]['DoseKey'] = $dosekey;
					}
					else {
						$n .= $subj['uid'] . ": $observationname-TimeSinceDose-$doseDisplayTime was null. Comparing DOSE TIMES " . json_encode($doses) . " to ITEM TIME " . $rowA['observation_startdate'] . "\n";
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

					list($timeSinceDose, $doseamount, $dosekey) = GetTimeSinceDose($doses, $vitalDate, $doseDisplayTime);
					if ($timeSinceDose != null) {
						$t[$row]["$vitalname-TimeSinceDose-$doseDisplayTime"] = $timeSinceDose;
						$t[$row]['DoseAmount'] = $doseamount;
						$t[$row]['DoseKey'] = $dosekey;
					}
					else {
						$n .= $subj['uid'] . ": $vitalname-TimeSinceDose-$doseDisplayTime was null. Comparing DOSE TIMES " . json_encode($doses) . " to ITEM TIME " . $vitalDate . "\n";
					}

					$hasdata = true;
				}
			}
			
			/* ---------- Interventions ---------- */
			if (($a['includeallinterventions']) || ($a['interventionname'] != "") || ($a['includeinterventiondetails']) || ($a['dosevariable'] != "")) {
				
				if ($a['includeallinterventions']) {
					$sqlstringA = "select a.*, b.intervention_name from interventions a left join interventionnames b on a.interventionname_id = b.interventionname_id where enrollment_id = $enrollmentid";
				}
				else {
					$interventionarray = array();
					
					if (is_array($a['interventionname']))
						$interventionarray = array_merge($interventionarray, $a['interventionname']);
					elseif (($a['interventionname'] != "") && ($a['interventionname'] != null))
						$interventionarray[] = $a['interventionname'];
						
					$interventionarray = array_unique($interventionarray);

					$interventionstr = CreateSQLSearchString("b.intervention_name", $interventionarray);
					if ($interventionstr == "") {
						$sqlstringA = "select a.*, b.intervention_name from interventions a left join interventionnames b on a.interventionname_id = b.interventionname_id where a.enrollment_id = $enrollmentid";
					}
					else {
						$sqlstringA = "select a.*, b.intervention_name from interventions a left join interventionnames b on a.interventionname_id = b.interventionname_id where a.enrollment_id = $enrollmentid and ($interventionstr)";
					}
				}
				if (count($interventionarray) > 0) {
					$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
					while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {

						$interventionname = $rowA['intervention_name'];

						/* attempt to collapse variables based on the expression provided by the user */
						$timepoint = "";
						if ($collapseByVars)
							if ($collapseExpression != "") {
								/* replace all potential regex characters that the user may have entered */
								$preg = preg_quote2($collapseExpression);
								//$n .= "CollapseBy expression after preg_replace2(interventions): [$preg]\n";
								
								/* replace the escaped # and * with the equivalent actual regex chars */
								$preg = str_replace("*", "+", $preg);
								$preg = "/^" . str_replace("#", "(\d+)", $preg) . "/";
								$n .= "Final collapseBy expression (interventions): [$preg]\n";
								preg_match($preg, $interventionname, $matches);
								$timepoint = $matches[1];
								$interventionname = str_replace($timepoint, "", $interventionname);
							}
							else
								$n .= "Collapse variables was selected, but an expression was not specified\n";

						if ($groupByDate || $collapseByVars)
							$row = $uid . substr($rowA['startdate'], 0, 10) . $timepoint;
						else
							$row = $uid . $rowA['startdate'];
						
						if ($collapseByVars)
							$t[$row]['collapseGroup'] = $timepoint;

						$interventionvalue = $rowA['intervention_value'];
						
						$dob = date_create($subj['dob']);
						$eventdate = date_create($rowA['startdate']);
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

						/* add the intervention info to this row */
						$t[$row]['EventDateTime'] = $rowA['startdate'];
						if ($includeDuration)
							$t[$row][$interventionname . '_DURATION'] = $rowA['duration'];
						if ($includeEndDate)
							$t[$row][$interventionname . '_ENDDATETIME'] = $rowA['enddate'];
						if ($interventionvalue == "")
							$t[$row][$interventionname] = $blankValuePlaceholder;
						else
							$t[$row][$interventionname] = $interventionvalue;

						list($timeSinceDose, $doseamount, $dosekey) = GetTimeSinceDose($doses, $rowA['startdate'], $doseDisplayTime);
						if ($timeSinceDose != null) {
							$t[$row]["$interventionname-TimeSinceDose-$doseDisplayTime"] = $timeSinceDose;
							$t[$row]['DoseAmount'] = $doseamount;
							$t[$row]['DoseKey'] = $dosekey;
						}
						else {
							$n .= $subj['uid'] . ": $interventionname-TimeSinceDose-$doseDisplayTime was null. Comparing DOSE TIMES " . json_encode($doses) . " to ITEM TIME " . $rowA['startdate'] . "\n";
						}

						/* add the intervention details */
						if ($a['includeinterventiondetails']) {
							$t[$row][$interventionname . '_doseamount'] = $rowA['doseamount'];
							$t[$row][$interventionname . '_dosekey'] = $rowA['dosekey'];
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
										$row = $uid . substr($rowA['startdate'], 0, 10);
									else
										$row = $uid . $rowA['startdate'];
									
									//$t[$row]['SeriesDateTime'] = $variabledatetime;
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
							$t[$row]['EventDateTime'] = $variabledatetime;
							$t[$row]['VisitType'] = $studyvisit;

							$resultname = trim($resultnames[$rowA['result_nameid']]);
							
							/* add the observation info to this row */
							$t[$row][$resultname] = $rowA['result_value'];

							list($timeSinceDose, $doseamount, $dosekey) = GetTimeSinceDose($doses, $variabledatetime, $doseDisplayTime);
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

		/* calculate nearest-in-time for the distance variable based on pinned variable */
		if ($pinnedvariable != "") {
			$pinnedLookupTable = array();

			/* loop through the rows, create new table with the pinned variable, indexed by UID and date */
			foreach ($t as $id => $subject) {
				$uid = substr($id, 0, 18);
				if ($t[$id][$pinnedvariable] != "") {
					$pinnedLookupTable[$uid]['date'] = substr($t[$id]['EventDateTime'], 0, 10);
					$pinnedLookupTable[$uid]['datetime'] = $t[$id]['EventDateTime'];
				}
			}
			
			//PrintVariable($pinnedLookupTable);
			
			/* loop through the rows */
			foreach ($t as $id => $subject) {
				
				/* loop through each column in the row */
				foreach ($h as $col) {
					
					/* highlight the pinned variable */
					if (trim($col) == $pinnedvariable) {
						$t[$id][$col] = "*" . $t[$id][$col] . "*";
					}
					
					/* check if this column matches the distance variable */
					if (trim($col) == $distancevariable) {
						/* get datetime of the pinned variable, for the same day... */
						
						$uid = substr($id, 0, 18);
						$pinnedvardatetime = $pinnedLookupTable[$uid]['datetime'];
						
						$distvardatetime = $t[$id]['EventDateTime'];
						$t[$id][$distancevariable . "_TimeDistance"] = DatetimeDiff($pinnedvardatetime, $distvardatetime);
					}
				}
			}
		}
		
		/* create table header again, after the new columns have been added */
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
	/* ------- TimelineCorrelationReport ---------- */
	/* -------------------------------------------- */
	function TimelineCorrelationReport($projectid, $a) {

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
		$pinnedvariable = trim($a['pinnedvariable']);
		$distancevariable = trim($a['distancevariable']);
		
		/* create the table */
		$t;
		
		if ($projectid == "") {
			Error("Project is blank. Select a project to generate a report");
			return;
		}
		
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
			$interventiondoses = array();
			if ($a['includetimesincedose']) {
				if ($doseVariable != "") {
					$sqlstringA = "select a.*, b.intervention_name from interventions a left join interventionnames b on a.interventionname_id = b.interventionname_id where a.enrollment_id = $enrollmentid and (" . CreateSQLSearchString("b.intervention_name", $a['dosevariable']) . ")";
					$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
					$i=0;
					while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
						/* add the observation info to this row */
						$interventiondoses[$i]['date'] = $rowA['startdate'];
						$interventiondoses[$i]['doseamount'] = $rowA['doseamount'];
						$interventiondoses[$i]['dosekey'] = $rowA['dosekey'];
						$i++;
					}
				}
			}

			/* ----- Get pinned variable info from Pipeline ----- */
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
							$studynum = $rowA['study_num'];
							$studyid = $rowA['study_id'];
							$studyvisit = $rowA['study_type'];
							$studymodality = strtolower($rowA['study_modality']);
							
							if (($studyage == "") || ($studyage == "null") || ($studyage == 0)) $age = strtotime($studydate) - strtotime($subj['dob']);
							else $age = $studyage;
							
							/* if we should search for a series datetime */
							$variabledatetime = $studydatetime;
							if ($a['pipelineseriesdatetime'] != "") {
								$sqlstringB = "select series_datetime from $studymodality" . "_series where (" . CreateSQLSearchString("series_protocol", $a['pipelineseriesdatetime']) . ") and study_id = $studyid limit 1";
								$resultB = MySQLiQuery($sqlstringB,__FILE__,__LINE__);
								if (mysqli_num_rows($resultB) > 0) {
									$rowB = mysqli_fetch_array($resultB, MYSQLI_ASSOC);
									$variabledatetime = $rowB['series_datetime'];

									if ($groupByDate)
										$row = $uid . substr($rowA['startdate'], 0, 10);
									else
										$row = $uid . $rowA['startdate'];
									
									//$t[$row]['SeriesDateTime'] = $variabledatetime;
								}
							}
							if ($groupByDate)
								$row = $uid . substr($variabledatetime, 0, 10);
							else
								$row = $uid . $variabledatetime;
							
							
							/* find the nearest-in-time vital/observation */
							$sqlstringB = "select a.*, b.vital_name, timestampdiff(minute, '$variabledatetime', a.vital_startdate) 'timediff' from vitals a left join vitalnames b on a.vitalname_id = b.vitalname_id where a.enrollment_id = $enrollmentid and (" . CreateSQLSearchString("b.vital_name", $a['vitalname']) . ") order by abs(timestampdiff(minute, '$variabledatetime', a.vital_startdate)) limit 1";
							//PrintSQL($sqlstringB);
							$resultB = MySQLiQuery($sqlstringB,__FILE__,__LINE__);
							while ($rowB = mysqli_fetch_array($resultB, MYSQLI_ASSOC)) {

								/* add the vital info to this row */
								$vitalname = $rowB['vital_name'];
								$vitalvalue = $rowB['vital_value'];
								//if (($rowB['vital_startdate'] == "0000-00-00 00:00:00") || ($rowB['vital_startdate'] == "")) {
								//	$vitalDate = $rowB['vital_date'];
								//}
								//else {
									$vitalDate = $rowB['vital_startdate'];
									$vitalDiff = $rowB['timediff'];
								//}
							}
							
							$t[$row]["Pipeline-Time"] = $variabledatetime;
							$t[$row]["Pipeline-Value"] = $rowA['result_value'];
							$t[$row]["Vital-Time"] = $vitalDate;
							$t[$row]["Vital-Value"] = $vitalvalue;
							$t[$row]["TimeDiffCalc"] = DatetimeDiff($variabledatetime, $vitalDate);
							$t[$row]["TimeDiffSQL"] = $vitalDiff;
							
							/* need to add the demographic info to every row */
							$t[$row]['UID'] = $subj['uid'];
							$t[$row]['Sex'] = $subj['sex'];
							//$t[$row]['AgeAtEvent'] = $age;
							$t[$row]['EnrollGroup'] = $subj['enrollgroup'];
							//$t[$row]['AltUIDs'] = $subj['altuids'];
							$t[$row]['Pipeline-StudyID'] = $subj['uid'] . $studynum;
							$t[$row]['Pipeline-StudyDateTime'] = $studydatetime;
							$t[$row]['EventDateTime'] = $variabledatetime;
							$t[$row]['VisitType'] = $studyvisit;

							$resultname = trim($resultnames[$rowA['result_nameid']]);
							
							/* add the observation info to this row */
							$t[$row][$resultname] = $rowA['result_value'];

							list($timeSinceDose, $doseamount, $dosekey) = GetTimeSinceDose($doses, $variabledatetime, $doseDisplayTime);
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
			
			/* ---------- Interventions ---------- */
			if (($a['includeallinterventions']) || ($a['interventionname'] != "") || ($a['includeinterventiondetails']) || ($a['dosevariable'] != "")) {
				
				if ($a['includeallinterventions']) {
					$sqlstringA = "select a.*, b.intervention_name from interventions a left join interventionnames b on a.interventionname_id = b.interventionname_id where enrollment_id = $enrollmentid";
				}
				else {
					$interventionarray = array();
					
					if (is_array($a['interventionname']))
						$interventionarray = array_merge($interventionarray, $a['interventionname']);
					elseif (($a['interventionname'] != "") && ($a['interventionname'] != null))
						$interventionarray[] = $a['interventionname'];
						
					$interventionarray = array_unique($interventionarray);

					$interventionstr = CreateSQLSearchString("b.intervention_name", $interventionarray);
					if ($interventionstr == "") {
						$sqlstringA = "select a.*, b.intervention_name from interventions a left join interventionnames b on a.interventionname_id = b.interventionname_id where a.enrollment_id = $enrollmentid";
					}
					else {
						$sqlstringA = "select a.*, b.intervention_name from interventions a left join interventionnames b on a.interventionname_id = b.interventionname_id where a.enrollment_id = $enrollmentid and ($interventionstr)";
					}
				}
				if (count($interventionarray) > 0) {
					$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
					while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {

						$interventionname = $rowA['intervention_name'];

						/* attempt to collapse variables based on the expression provided by the user */
						$timepoint = "";
						if ($collapseByVars)
							if ($collapseExpression != "") {
								/* replace all potential regex characters that the user may have entered */
								$preg = preg_quote2($collapseExpression);
								//$n .= "CollapseBy expression after preg_replace2(interventions): [$preg]\n";
								
								/* replace the escaped # and * with the equivalent actual regex chars */
								$preg = str_replace("*", "+", $preg);
								$preg = "/^" . str_replace("#", "(\d+)", $preg) . "/";
								$n .= "Final collapseBy expression (interventions): [$preg]\n";
								preg_match($preg, $interventionname, $matches);
								$timepoint = $matches[1];
								$interventionname = str_replace($timepoint, "", $interventionname);
							}
							else
								$n .= "Collapse variables was selected, but an expression was not specified\n";

						if ($groupByDate || $collapseByVars)
							$row = $uid . substr($rowA['startdate'], 0, 10) . $timepoint;
						else
							$row = $uid . $rowA['startdate'];
						
						if ($collapseByVars)
							$t[$row]['collapseGroup'] = $timepoint;

						$interventionvalue = $rowA['intervention_value'];
						
						$dob = date_create($subj['dob']);
						$eventdate = date_create($rowA['startdate']);
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

						/* add the intervention info to this row */
						$t[$row]['EventDateTime'] = $rowA['startdate'];
						if ($includeDuration)
							$t[$row][$interventionname . '_DURATION'] = $rowA['duration'];
						if ($includeEndDate)
							$t[$row][$interventionname . '_ENDDATETIME'] = $rowA['enddate'];
						if ($interventionvalue == "")
							$t[$row][$interventionname] = $blankValuePlaceholder;
						else
							$t[$row][$interventionname] = $interventionvalue;

						list($timeSinceDose, $doseamount, $dosekey) = GetTimeSinceDose($doses, $rowA['startdate'], $doseDisplayTime);
						if ($timeSinceDose != null) {
							$t[$row]["$interventionname-TimeSinceDose-$doseDisplayTime"] = $timeSinceDose;
							$t[$row]['DoseAmount'] = $doseamount;
							$t[$row]['DoseKey'] = $dosekey;
						}
						else {
							$n .= $subj['uid'] . ": $interventionname-TimeSinceDose-$doseDisplayTime was null. Comparing DOSE TIMES " . json_encode($doses) . " to ITEM TIME " . $rowA['startdate'] . "\n";
						}

						/* add the intervention details */
						if ($a['includeinterventiondetails']) {
							$t[$row][$interventionname . '_doseamount'] = $rowA['doseamount'];
							$t[$row][$interventionname . '_dosekey'] = $rowA['dosekey'];
						}
						
						$hasdata = true;
					}
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

		//PrintVariable($t);
		
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
	/* ------- DatetimeDiff ----------------------- */
	/* -------------------------------------------- */
	function DatetimeDiff($datetime1, $datetime2) {
		if ($datetime1 == "") return "";
		if ($datetime2 == "") return "";
		
		$time1 = strtotime($datetime1);
		$time2 = strtotime($datetime2);
		
		return round(abs($time1 - $time2) / 60,1);
	}


	/* -------------------------------------------- */
	/* ------- GetTimeSinceDose ------------------- */
	/* -------------------------------------------- */
	function GetTimeSinceDose($doses, $event, $doseDisplayTime) {
		$eventParts = date_parse($event);
		
		//PrintVariable($eventParts);
		$dosetimes = array();
		//foreach ($doses as $d) {
		//	$dosetimes[] = $d['date'];
		//}
		
		//PrintVariable($doses);
		
		$timeSinceDose = null;
		foreach ($doses as $d) {
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
