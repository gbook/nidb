<?
 // ------------------------------------------------------------------------------
 // NiDB search.php
 // Copyright (C) 2004 - 2022
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
		<title>NiDB - Search</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	//PrintVariable($_POST);
	//PrintVariable($_SESSION);
	
	/* set debugging on/off only for this page */
	$GLOBALS['cfg']['debug'] = 0;
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");

	/* searching variables */
    $searchvars['s_searchhistoryid'] = GetVariable("s_searchhistoryid");
    $searchvars['s_projectids'] = GetVariable("s_projectids");
    $searchvars['s_enrollsubgroup'] = GetVariable("s_enrollsubgroup");
    $searchvars['s_subjectuid'] = GetVariable("s_subjectuid");
    $searchvars['s_subjectaltuid'] = GetVariable("s_subjectaltuid");
    $searchvars['s_subjectname'] = GetVariable("s_subjectname");
    $searchvars['s_subjectdobstart'] = GetVariable("s_subjectdobstart");
    $searchvars['s_subjectdobend'] = GetVariable("s_subjectdobend");
    $searchvars['s_ageatscanmin'] = GetVariable("s_ageatscanmin");
    $searchvars['s_ageatscanmax'] = GetVariable("s_ageatscanmax");
    $searchvars['s_subjectgender'] = GetVariable("s_subjectgender");
    $searchvars['s_subjectgroupid'] = GetVariable("s_subjectgroupid");
    $searchvars['s_measuresearch'] = GetVariable("s_measuresearch");
    $searchvars['s_measurelist'] = GetVariable("s_measurelist");
    $searchvars['s_studyinstitution'] = GetVariable("s_studyinstitution");
    $searchvars['s_studyequipment'] = GetVariable("s_studyequipment");
    $searchvars['s_studyid'] = GetVariable("s_studyid");
    $searchvars['s_studyaltscanid'] = GetVariable("s_studyaltscanid");
    $searchvars['s_studydatestart'] = GetVariable("s_studydatestart");
    $searchvars['s_studydateend'] = GetVariable("s_studydateend");
    $searchvars['s_studydesc'] = GetVariable("s_studydesc");
    $searchvars['s_studyphysician'] = GetVariable("s_studyphysician");
    $searchvars['s_studyoperator'] = GetVariable("s_studyoperator");
    $searchvars['s_studytype'] = GetVariable("s_studytype");
    $searchvars['s_studymodality'] = GetVariable("s_studymodality");
    $searchvars['s_studygroupid'] = GetVariable("s_studygroupid");
    $searchvars['s_seriesdesc'] = GetVariable("s_seriesdesc");
    $searchvars['s_usealtseriesdesc'] = GetVariable("s_usealtseriesdesc");
    $searchvars['s_seriessequence'] = GetVariable("s_seriessequence");
    $searchvars['s_seriesimagetype'] = GetVariable("s_seriesimagetype");
    $searchvars['s_seriestr'] = GetVariable("s_seriestr");
    $searchvars['s_seriesimagecomments'] = GetVariable("s_seriesimagecomments");
    $searchvars['s_seriesnum'] = GetVariable("s_seriesnum");
    $searchvars['s_seriesnumfiles'] = GetVariable("s_seriesnumfiles");
    $searchvars['s_seriesgroupid'] = GetVariable("s_seriesgroupid");
    $searchvars['s_pipelineid'] = GetVariable("s_pipelineid");
    $searchvars['s_pipelineresultname'] = GetVariable("s_pipelineresultname");
    $searchvars['s_pipelineresultunit'] = GetVariable("s_pipelineresultunit");
    $searchvars['s_pipelineresultvalue'] = GetVariable("s_pipelineresultvalue");
    $searchvars['s_pipelineresultcompare'] = GetVariable("s_pipelineresultcompare");
    $searchvars['s_pipelineresulttype'] = GetVariable("s_pipelineresulttype");
    $searchvars['s_pipelinecolorize'] = GetVariable("s_pipelinecolorize");
    $searchvars['s_pipelinecormatrix'] = GetVariable("s_pipelinecormatrix");
    $searchvars['s_pipelineresultstats'] = GetVariable("s_pipelineresultstats");
    $searchvars['s_resultoutput'] = GetVariable("s_resultoutput");
    $searchvars['s_formid'] = GetVariable("s_formid");
    $searchvars['s_formfieldid'] = GetVariable("s_formfieldid");
    $searchvars['s_formcriteria'] = GetVariable("s_formcriteria");
    $searchvars['s_formvalue'] = GetVariable("s_formvalue");
    $searchvars['s_audit'] = GetVariable("s_audit");
    $searchvars['s_qcbuiltinvariable'] = GetVariable("s_qcbuiltinvariable");
    $searchvars['s_qcvariableid'] = GetVariable("s_qcvariableid");

	/* data request variables */
	$requestvars['downloadimaging'] = GetVariable("downloadimaging");
	$requestvars['downloadbeh'] = GetVariable("downloadbeh");
	$requestvars['downloadqc'] = GetVariable("downloadqc");
	$requestvars['downloadexperiments'] = GetVariable("downloadexperiments");
	$requestvars['downloadresults'] = GetVariable("downloadresults");
	$requestvars['downloadpipelines'] = GetVariable("downloadpipelines");
	$requestvars['downloadvariables'] = GetVariable("downloadvariables");
	$requestvars['downloadminipipelines'] = GetVariable("downloadminipipelines");
	$requestvars['destination'] = GetVariable("destination");
	$requestvars['modality'] = GetVariable("modality");
	$requestvars['dirformat'] = GetVariable("dirformat");
	$requestvars['seriesid'] = GetVariable("seriesid");
	$requestvars['enrollmentid'] = GetVariable("enrollmentid");
	$requestvars['anonymize'] = GetVariable("anonymize");
	$requestvars['nfsdir'] = GetVariable("nfsdir");
	$requestvars['filetype'] = GetVariable("filetype");
	$requestvars['gzip'] = GetVariable("gzip");
	$requestvars['preserveseries'] = GetVariable("preserveseries");
	$requestvars['remoteftpserver'] = GetVariable("remoteftpserver");
	$requestvars['remoteftppath'] = GetVariable("remoteftppath");
	$requestvars['remoteftpusername'] = GetVariable("remoteftpusername");
	$requestvars['remoteftppassword'] = GetVariable("remoteftppassword");
	$requestvars['remoteftpport'] = GetVariable("remoteftpport");
	$requestvars['remoteftpsecure'] = GetVariable("remoteftpsecure");
	$requestvars['remoteconnid'] = GetVariable("remoteconnid");
	$requestvars['publicdownloaddesc'] = GetVariable("publicdownloaddesc");
	$requestvars['publicdownloadreleasenotes'] = GetVariable("publicdownloadreleasenotes");
	$requestvars['publicdownloadpassword'] = GetVariable("publicdownloadpassword");
	$requestvars['publicdownloadshareinternal'] = GetVariable("publicdownloadshareinternal");
	$requestvars['publicdownloadregisterrequired'] = GetVariable("publicdownloadregisterrequired");
	$requestvars['publicdownloadexpire'] = GetVariable("publicdownloadexpire");
	$requestvars['dicomtags'] = GetVariable("dicomtags");
	$requestvars['timepoints'] = GetVariable("timepoints");
	$requestvars['behformat'] = GetVariable("behformat");
	$requestvars['behdirnameroot'] = GetVariable("behdirnameroot");
	$requestvars['behdirnameseries'] = GetVariable("behdirnameseries");
    $requestvars['subjectmeta'] = GetVariable("subjectmeta");
    $requestvars['subjectdata'] = GetVariable("subjectdata");
    $requestvars['subjectphenotype'] = GetVariable("subjectphenotype");
    $requestvars['subjectforms'] = GetVariable("subjectforms");
    $requestvars['studymeta'] = GetVariable("studymeta");
    $requestvars['studydata'] = GetVariable("studydata");
    $requestvars['seriesmeta'] = GetVariable("seriesmeta");
    $requestvars['seriesdata'] = GetVariable("seriesdata");
    $requestvars['allsubject'] = GetVariable("allsubject");
    $requestvars['bidsreadme'] = GetVariable("bidsreadme");
    $requestvars['bidsflag_useuid'] = GetVariable("bidsflag_useuid");
    $requestvars['bidsflag_usestudyid'] = GetVariable("bidsflag_usestudyid");
    $requestvars['squirrelflag_metadata'] = GetVariable("squirrelflag_metadata");
    $requestvars['squirrelflag_anonymize'] = GetVariable("squirrelflag_anonymize");
    $requestvars['squirreltitle'] = GetVariable("squirreltitle");
    $requestvars['squirreldesc'] = GetVariable("squirreldesc");

	$numpostvars = count($_POST);
	$maxnumvars = ini_get('max_input_vars');
	if ($numpostvars >= $maxnumvars) {
		?>
		<div class="ui orange message">PHP has an inherent limit [<?=$maxnumvars?>] for the number of items you can request. You have requested [<?=$numpostvars?>] items. PHP will truncate the number of items to match its limit <b>with no warning</b>. To prevent you from receiving less data than you are expecting, this page will not process your transfer request. Please go back to the search page and transfer less than [<?=$maxnumvars?>] data items.</div>
		<?
		exit(0);
	}
	
	/* ----- determine which action to take ----- */
	switch ($action) {
		case 'searchform': DisplaySearchForm($searchvars, $action); break;
		case 'search':
			UpdateSearchHistory($searchvars);
			DisplaySearchForm($searchvars, $action);
			Search($searchvars);
			break;
		case 'submit': ProcessRequest($requestvars, $username); break;
		case 'anonymize': Anonymize($requestvars, $username); break;
		default:
			DisplaySearchForm($searchvars, $action);
	}


	/* -------------------------------------------- */
	/* ------- DisplaySearchForm ------------------ */
	/* -------------------------------------------- */
	function DisplaySearchForm($s, $action) {
	
		/* if using a previous search, load it up */
		if (isInteger($s['s_searchhistoryid'])) {
			$sqlstring = "select * from search_history where searchhistory_id = " . $s['s_searchhistoryid'];
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			
			$s['s_subjectuid'] = $row['subjectuid'];
			$s['s_subjectaltuid'] = $row['subjectaltuid'];
			$s['s_subjectname'] = $row['subjectname'];
			$s['s_subjectdobstart'] = $row['subjectdobstart'];
			$s['s_subjectdobend'] = $row['subjectdobend'];
			$s['s_ageatscanmin'] = $row['ageatscanmin'];
			$s['s_ageatscanmax'] = $row['ageatscanmax'];
			$s['s_subjectgender'] = $row['subjectgender'];
			$s['s_subjectgroupid'] = $row['subjectgroupid'];
			$s['s_projectids'] = $row['projectids'];
			$s['s_enrollsubgroup'] = $row['enrollsubgroup'];
			$s['s_measuresearch'] = $row['measuresearch'];
			$s['s_measurelist'] = $row['measurelist'];
			$s['s_studyinstitution'] = $row['studyinstitution'];
			$s['s_studyequipment'] = $row['studyequipment'];
			$s['s_studyid'] = $row['studyid'];
			$s['s_studyaltscanid'] = $row['studyaltscanid'];
			$s['s_studydatestart'] = $row['studydatestart'];
			$s['s_studydateend'] = $row['studydateend'];
			$s['s_studydesc'] = $row['studydesc'];
			$s['s_studyphysician'] = $row['studyphysician'];
			$s['s_studyoperator'] = $row['studyoperator'];
			$s['s_studytype'] = $row['studytype'];
			$s['s_studymodality'] = $row['studymodality'];
			$s['s_studygroupid'] = $row['studygroupid'];
			$s['s_seriesdesc'] = $row['seriesdesc'];
			$s['s_usealtseriesdesc'] = $row['usealtseriesdesc'];
			$s['s_seriessequence'] = $row['seriessequence'];
			$s['s_seriesimagetype'] = $row['seriesimagetype'];
			$s['s_seriestr'] = $row['seriestr'];
			$s['s_seriesimagecomments'] = $row['seriesimagecomments'];
			$s['s_seriesnum'] = $row['seriesnum'];
			$s['s_seriesnumfiles'] = $row['seriesnumfiles'];
			$s['s_seriesgroupid'] = $row['seriesgroupid'];
			$s['s_pipelineid'] = $row['pipelineid'];
			$s['s_pipelineresultname'] = $row['pipelineresultname'];
			$s['s_pipelineresultunit'] = $row['pipelineresultunit'];
			$s['s_pipelineresultvalue'] = $row['pipelineresultvalue'];
			$s['s_pipelineresultcompare'] = $row['pipelineresultcompare'];
			$s['s_pipelineresulttype'] = $row['pipelineresulttype'];
			$s['s_pipelinecolorize'] = $row['pipelinecolorize'];
			$s['s_pipelinecormatrix'] = $row['pipelinecormatrix'];
			$s['s_pipelineresultstats'] = $row['pipelineresultstats'];
			$s['s_resultoutput'] = $row['resultorder'];
			$s['s_formid'] = $row['formid'];
			$s['s_formfieldid'] = $row['formfieldid'];
			$s['s_formcriteria'] = $row['formcriteria'];
			$s['s_formvalue'] = $row['formvalue'];
			$s['s_audit'] = $row['audit'];
			$s['s_qcbuiltinvariable'] = $row['qcbuiltinvariable'];
			$s['s_qcvariableid'] = $row['qcvariableid'];
		}
		
	?>
	<style>
		.fieldlabel { color: #444; text-align: right; vertical-align: top; }
		.importantfield { background-color: lightyellow; }
		input.hasdata { font-weight: bold; box-shadow: 0px 0px 0px 2px #3B5998; }
		.slabel { font-size: 12pt; }
	</style>
	<script>
		$(document).ready(function(){
			$('#pageloading').hide();
			
			$('.custom_format_calendar')
			  .calendar({
				monthFirst: false,
				type: 'date',
				formatter: {
				  date: function (date, settings) {
					if (!date) return '';
					var day = date.getDate();
					if (day < 10) { day = "0" + day; }
					var month = date.getMonth() + 1;
					if (month < 10) { month = "0" + month; }
					var year = date.getFullYear();
					return year + '-' + month + '-' + day;
				  }
				}
			  })
			;
		});
		
		/* changed the results/view output type when a search element is clicked */
		function SwitchOption(option) {
			switch (option) {
				case 'viewpipeline':
					document.getElementById('viewpipeline').checked = true;
					break;
			}
		}
		
		/* this function is called from onChange, onInput, and onBlur events
		   because some Semantic UI inputs fire different events */
		function inputColor(i) {
			console.log("checkInput has been fired");
			
			if (i.value == "")
				i.style.backgroundColor='';
			else
				i.style.backgroundColor='LightGoldenRodYellow';
		}		
	</script>
	
	<div align="center">
	<form action="search.php" method="post" name="searchform" class="ui form">
	<input type="hidden" name="action" value="search">
	
	<div class="ui grid">
		<div class="one wide column">&nbsp;</div>
		<div class="fourteen wide column">
			
			<div class="ui grey secondary inverted top attached segment">
				<div class="ui three column grid">
					<div class="left aligned column">
						<a href="search.php" class="ui yellow large button"><i class="search plus icon"></i> New Search</a>
					</div>
					<div class="column">
						<? if ($action == "search") { ?>
						<div class="ui yellow message" align="center" id="pageloading">
							<h2 class="ui header">
								<em data-emoji=":chipmunk:" class="loading"></em> Searching...
							</h2>
						</div>
						<? } ?>
						&nbsp;
					</div>
					<div class="left aligned middle aligned column">
						<? DisplaySearchHistory(); ?>
					</div>
				</div>
			</div>
			
			<div class="ui grey attached segment">
				<div class="ui grid">
					<div class="one wide right aligned column">
						<h3 class="ui header">Subject</h3>
					</div>
					<div class="eight wide left aligned column">
					
						<div class="ui compact grid">
							<div class="four wide right aligned middle aligned column slabel">
								UIDs <i class="small blue question circle outline icon" title="<b>Subject UID(s)</b><br><br>Can be a list of UIDs, separated by commas, spaces, semi-colons, tabs, or Copy & Paste from Excel"></i>
							</div>
							<div class="twelve wide column">
								<input type="text" name="s_subjectuid" value="<?=$s['s_subjectuid'];?>" class="ui input importantfield <? echo (!isEmpty($s['s_subjectuid'])) ? 'hasdata' : '';?>" placeholder="UIDs" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
							</div>

							<div class="four wide right aligned middle aligned column slabel">
								Alternate UIDs <i class="small blue question circle outline icon" title="<b>Alternate Subject UID(s)</b><br><br>Can be a list of UIDs, separated by commas, spaces, semi-colons, tabs, or Copy&Paste from Excel"></i>
							</div>
							<div class="twelve wide column">
								<input type="text" name="s_subjectaltuid" value="<?=$s['s_subjectaltuid'];?>" class="importantfield <? echo (!isEmpty($s['s_subjectaltuid'])) ? 'hasdata' : '';?>" placeholder="Alternate UIDs" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
							</div>

							<div class="four wide right aligned middle aligned column slabel">
								Name
							</div>
							<div class="twelve wide column">
								<input type="text" name="s_subjectname" value="<?=$s['s_subjectname'];?>" class="importantfield <? echo (!isEmpty($s['s_subjectname'])) ? 'hasdata' : '';?>" placeholder="Name" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
							</div>
						</div>
						
					</div>
					
					<div class="seven wide column">
						<div class="ui compact grid">
							<div class="four wide right aligned middle aligned column slabel">
								DOB
							</div>
							<div class="twelve wide left aligned column">
								<div class="ui inline field">
									<div class="ui calendar custom_format_calendar">
										<div class="ui small input left icon">
											<i class="calendar icon"></i>
											<input type="text" name="s_subjectdobstart" value="<?=$s['s_subjectdobstart'];?>" placeholder="start" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
										</div>
									</div>
									<i class="arrows alternate horizontal icon"></i> &nbsp;
									<div class="ui calendar custom_format_calendar">
										<div class="ui small input left icon">
											<i class="calendar icon"></i>
											<input type="text" name="s_subjectdobend" value="<?=$s['s_subjectdobend'];?>" placeholder="end" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
										</div>
									</div>
								</div>
							</div>

							<div class="four wide right aligned middle aligned column slabel">
								Age-at-scan
							</div>
							<div class="twelve wide left aligned column">
								<div class="ui small right labeled input">
									<input type="number" name="s_ageatscanmin" value="<?=$s['s_ageatscanmin'];?>" maxlength="3" style="width: 80px" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
									<div class="ui label">
										years
									</div>
								</div>
								<i class="arrows alternate horizontal icon"></i>
								<div class="ui small right labeled input">
									<input type="number" name="s_ageatscanmax" value="<?=$s['s_ageatscanmax'];?>" maxlength="3" style="width: 80px" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
									<div class="ui label">
										years
									</div>
								</div>
							</div>

							<div class="four wide right aligned middle aligned column slabel">
								Sex
							</div>
							<div class="twelve wide left aligned column">
								<div class="ui small input">
									<input type="text" name="s_subjectgender" size="1" maxlength="1" value="<?=$s['s_subjectgender']?>" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
								</div>
								<span class="tiny">&nbsp;F, M, O, U</span>
							</div>

							<div class="four wide right aligned middle aligned column slabel">
								<i class="user friends icon"></i> Subject group
							</div>
							<div class="twelve wide left aligned column">
								<select name="s_subjectgroupid" class="ui small dropdown" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
									<option value="">Select a group</option>
								<?
									$sqlstring = "select * from groups where group_type = 'subject' order by group_name";
									$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
									while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
										$groupid = $row['group_id'];
										$groupname = $row['group_name'];
										$groupowner = $row['group_owner'];
										
										echo "[[$groupid -- [" . $s['s_subjectgroupid'] . "]]]";
										if ($groupid == $s['s_subjectgroupid']) {
											$selected = "selected";
										}
										else {
											$selected = "";
										}
										?>
										<option value="<?=$groupid?>" <?=$selected?>><?=$groupname?></option>
										<?
									}
								?>
								</select>
							</div>
							
						</div>
					</div>
				</div>
			</div>
			
			<div class="ui attached segment">
				<div class="ui grid">
					<div class="one wide right aligned column">
						<h3 class="ui header">Enrollment</h3>
					</div>
					<div class="eight wide left aligned column">
						<div class="ui compact grid">
							<div class="four wide right aligned middle aligned column slabel">
								Projects
							</div>
							<div class="twelve wide column">

								<select name="s_projectids[]" multiple class="ui fluid dropdown importantfield" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
									<option value=""></option>
									<option value="all">All Projects</option>
									<?
										$sqlstring = "select * from projects where instance_id = '" . $_SESSION['instanceid'] . "' order by project_name";
										$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
										while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
											$project_id = $row['project_id'];
											$project_name = $row['project_name'];
											$project_costcenter = $row['project_costcenter'];
											if (in_array($project_id, $s['s_projectids'])) { $selected = "selected"; } else { $selected = ""; }
											
											$perms = GetCurrentUserProjectPermissions(array($project_id));
											if (GetPerm($perms, 'viewdata', $project_id)) { $disabled = ""; } else { $disabled="disabled"; }
											?>
											<option value="<?=$project_id?>" <?=$selected?>  <?=$disabled?>><?=$project_name?> (<?=$project_costcenter?>)</option>
											<?
										}
									?>
								</select>
							</div>
						</div>
					</div>
					<div class="seven wide left aligned column">
						<div class="ui compact grid">
							<div class="four wide right aligned middle aligned column slabel">
								Enrollment sub-group
							</div>
							<div class="twelve wide left aligned column">
								<div class="ui small fluid input">
									<input type="text" name="s_enrollsubgroup" id="s_enrollsubgroup" list="s_enrollsubgroup" value="<?=$s['s_enrollsubgroup']?>" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
								</div>
								<datalist id="s_enrollsubgroup">
								<?
									$sqlstring = "select distinct(enroll_subgroup) from enrollment order by enroll_subgroup";
									$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
									while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
										?><option value="<?=$row['enroll_subgroup']?>"><?
									}
								?>
								</datalist>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="ui attached segment">
				<div class="ui grid">
					<div class="one wide right aligned column">
						<h3 class="ui header">Study</h3>
					</div>
					<div class="eight wide left aligned column">
						<div class="ui compact grid">

							<div class="four wide right aligned middle aligned column slabel">
								Study IDs
							</div>
							<div class="twelve wide column">
								<input type="text" name="s_studyid" value="<?=$s['s_studyid']?>" class="importantfield" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
							</div>

							<div class="four wide right aligned middle aligned column slabel">
								Alternate Scan IDs
							</div>
							<div class="twelve wide column">
								<input type="text" name="s_studyaltscanid" value="<?=$s['s_studyaltscanid']?>" size="50" class="importantfield" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
							</div>

							<div class="four wide right aligned middle aligned column slabel">
								Study Date <i class="small blue question circle outline icon" title="<b>Study date</b><br><br>Leave first date blank to search for anything earlier than the second date. Leave the second date blank to search for anything later than the first date"></i>
							</div>
							<div class="twelve wide column">
								<div class="inline fields">
									<div class="ui calendar custom_format_calendar">
										<div class="ui input left icon">
											<i class="calendar icon"></i>
											<input type="text" name="s_studydatestart" value="<?=$s['s_studydatestart'];?>" placeholder="start" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
										</div>
									</div>
									&nbsp; <i class="arrows alternate horizontal icon"></i> &nbsp;
									<div class="ui calendar custom_format_calendar">
										<div class="ui input left icon">
											<i class="calendar icon"></i>
											<input type="text" name="s_studydateend" value="<?=$s['s_studydateend'];?>" placeholder="end" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
										</div>
									</div>
								</div>
							</div>

							<div class="four wide right aligned middle aligned column slabel">
								Modality
							</div>
							<div class="twelve wide column">
								<select name="s_studymodality" class="ui fluid dropdown" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
								<?
									$sqlstring = "select * from modalities order by mod_desc";
									$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
									while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
										$mod_code = $row['mod_code'];
										$mod_desc = $row['mod_desc'];
										
										/* check if the modality table exists */
										$sqlstring2 = "show tables from " . $GLOBALS['cfg']['mysqldatabase'] . " like '" . strtolower($mod_code) . "_series'";
										$result2 = MySQLiQuery($sqlstring2,__FILE__,__LINE__);
										if (mysqli_num_rows($result2) > 0) {
										
											/* if the table does exist, allow the user to search on it */
											if (($mod_code == "MR") && ($s['s_studymodality'] == "")) {
												$selected = "selected";
											}
											else {
												if ($mod_code == $s['s_studymodality']) {
													$selected = "selected";
												}
												else {
													$selected = "";
												}
											}
											?>
											<option value="<?=$mod_code?>" <?=$selected?>><?=$mod_desc?></option>
											<?
										}
									}
								?>
								</select>
							
							</div>

						</div>
					</div>
					<div class="seven wide left aligned column">
						<div class="ui compact grid">

							<div class="four wide right aligned middle aligned column slabel">
								Institution
							</div>
							<div class="twelve wide column">
								<div class="ui small fluid input">
									<input type="text" name="s_studyinstitution" id="s_studyinstitution" list="s_studyinstitution" value="<?=$s['s_studyinstitution']?>" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
								</div>
								<datalist id="s_studyinstitution">
								<?
									$sqlstring = "select distinct(study_institution) from studies order by study_institution";
									$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
									while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
										?><option value="<?=$row['study_institution']?>"><?
									}
								?>
								</datalist>
							
							</div>

							<div class="four wide right aligned middle aligned column slabel">
								Equipment
							</div>
							<div class="twelve wide column">
								<select name="s_studyequipment" class="ui small fluid dropdown" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
									<option value="">Select equipment</option>
								<?
									$sqlstring = "select distinct(study_site) from studies order by study_site";
									$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
									while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
										$study_site = $row['study_site'];
										
										if ($study_site != "") {
											if ($study_site == $s['s_studyequipment']) {
												$selected = "selected";
											}
											else {
												$selected = "";
											}
											?>
											<option value="<?=$study_site?>" <?=$selected?>><?=$study_site?></option>
											<?
										}
									}
								?>
								</select>
							</div>
							
							<div class="four wide right aligned middle aligned column slabel">
								Description
							</div>
							<div class="twelve wide column">
								<div class="ui small fluid input">
									<input type="text" name="s_studydesc" list="s_studydesc" value="<?=$s['s_studydesc']?>" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
								</div>
								<datalist id="s_studydesc">
								<?
									$sqlstring = "select distinct(study_desc) from studies where study_desc <> '' order by study_desc";
									$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
									while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
										?><option value="<?=trim($row['study_desc'])?>"><?
									}
								?>
								</datalist>
							</div>
							
							<div class="four wide right aligned middle aligned column slabel">
								Physician
							</div>
							<div class="twelve wide column">
								<div class="ui small fluid input">
									<input type="text" name="s_studyphysician" list="s_studyphysician" value="<?=$s['s_studyphysician']?>" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
								</div>
								<datalist id="s_studyphysician">
								<?
									$sqlstring = "select distinct(study_performingphysician) from studies where study_performingphysician <> '' order by study_performingphysician";
									$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
									while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
										?><option value="<?=trim($row['study_performingphysician'])?>"><?
									}
								?>
								</datalist>
							</div>

							<div class="four wide right aligned middle aligned column slabel">
								Operator
							</div>
							<div class="twelve wide column">
								<div class="ui small fluid input">
									<input type="text" name="s_studyoperator" list="s_studyoperator" value="<?=$s['s_studyoperator']?>" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
								</div>
								<datalist id="s_studyoperator">
								<?
									$sqlstring = "select distinct(study_operator) from studies where study_operator <> '' order by study_operator";
									$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
									while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
										?><option value="<?=trim($row['study_operator'])?>"><?
									}
								?>
								</datalist>
							</div>
							
							<div class="four wide right aligned middle aligned column slabel">
								Visit type
							</div>
							<div class="twelve wide column">
								<div class="ui small fluid input">
									<input type="text" name="s_studytype" list="s_studytype" value="<?=$s['s_studytype']?>" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
								</div>
								<datalist id="s_studytype">
								<?
									$sqlstring = "select distinct(study_type) from studies where study_type <> '' order by study_type";
									$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
									while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
										?><option value="<?=trim($row['study_type'])?>"><?
									}
								?>
								</datalist>
							</div>
							
							<div class="four wide right aligned middle aligned column slabel">
								<i class="user friends icon"></i> Study group
							</div>
							<div class="twelve wide column">
								<select name="s_studygroupid" class="ui small fluid dropdown" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
									<option value="">Select a group</option>
								<?
									$sqlstring = "select * from groups where group_type = 'study' order by group_name";
									$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
									while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
										$groupid = $row['group_id'];
										$groupname = $row['group_name'];
										$groupowner = $row['group_owner'];
										
										if ($groupid == $s['s_studygroupid']) {
											$selected = "selected";
										}
										else {
											$selected = "";
										}
										?>
										<option value="<?=$groupid?>" <?=$selected?>><?=$groupname?></option>
										<?
									}
								?>
								</select>
							</div>
							
						</div>
					</div>
				</div>
			</div>

			<div class="ui attached segment">
				<div class="ui grid">
					<div class="one wide right aligned column">
						<h3 class="ui header">Series</h3>
					</div>
					<div class="eight wide left aligned column">
						<div class="ui compact grid">

							<div class="four wide right aligned middle aligned column slabel">
								Protocol <i class="small blue question circle outline icon" title="<b>Comma separated</b> protocols: search will be an AND<br><b>Semi-colon separated</b> protocols: search will be an OR"></i>
							</div>
							<div class="twelve wide column">
								<div class="ui fluid input">
									<input type="text" name="s_seriesdesc" value="<?=$s['s_seriesdesc']?>" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
								</div>
							</div>
							
						</div>
					</div>
					<div class="seven wide left aligned column">
						<div class="ui compact grid">

							<div class="four wide right aligned middle aligned column slabel">
								
							</div>
							<div class="twelve wide column">
								<div class="ui inline field">
									<div class="ui checkbox">
										<input type="checkbox" name="s_usealtseriesdesc" value="1" class="importantfield" <? if ($s['s_usealtseriesdesc']) { echo "checked"; } ?> onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
										<label>Use alternate protocol name <i class="small blue question circle outline icon" title="Perform the search using the alternate protocol name, and return the results using the alternate protocol name. The alternate protocol name often groups together series with similar names into one protocol. For example 'MPRAGE', 'Axial T1', and 'T1w_SPC' would all be labeled 'T1'"></i></label>
									</div>
								</div>
							</div>

							<div class="four wide right aligned middle aligned column slabel">
								Sequence
							</div>
							<div class="twelve wide column">
								<div class="ui small fluid input">
									<input type="text" name="s_seriessequence" value="<?=$s['s_seriessequence']?>" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
								</div>
							</div>
							
							<div class="four wide right aligned middle aligned column slabel">
								Image type
							</div>
							<div class="twelve wide column">
								<div class="ui small fluid input">
									<input type="text" name="s_seriesimagetype" value="<?=$s['s_seriesimagetype']?>" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
								</div>
							</div>
							
							<div class="four wide right aligned middle aligned column slabel">
								Image comments
							</div>
							<div class="twelve wide column">
								<div class="ui small fluid input">
									<input type="text" name="s_seriesimagecomments" value="<?=$s['s_seriesimagecomments']?>" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
								</div>
							</div>

							<div class="four wide right aligned middle aligned column slabel">
								TR
							</div>
							<div class="twelve wide column">
								<div class="ui small right labeled input">
									<input type="text" name="s_seriestr" value="<?=$s['s_seriestr']?>" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
									<div class="ui label">ms</div>
								</div>
							</div>
							
							<div class="four wide right aligned middle aligned column slabel">
								Series number <i class="small blue question circle outline icon" title="<b>Must be an integer or a criteria:</b><ul><li>> <i>N</i> (greater than)<li>>= <i>N</i> (greater than or equal to)<li>< <i>N</i> (less than)<li><= <i>N</i> (less than or equal to)<li>~ <i>N</i> (not)</ul>"></i>
							</div>
							<div class="twelve wide column">
								<div class="ui small input">
									<input type="text" name="s_seriesnum" value="<?=$s['s_seriesnum']?>" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
								</div>
							</div>
							
							<div class="four wide right aligned middle aligned column slabel">
								Number of files <i class="small blue question circle outline icon" title="<b>Must be an integer or a criteria:</b><ul><li>> <i>N</i> (greater than)<li>>= <i>N</i> (greater than or equal to)<li>< <i>N</i> (less than)<li><= <i>N</i> (less than or equal to)<li>~ <i>N</i> (not)</ul>"></i>
							</div>
							<div class="twelve wide column">
								<div class="ui small input">
									<input type="text" name="s_seriesnumfiles" value="<?=$s['s_seriesnumfiles']?>" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
								</div>
							</div>

							<div class="four wide right aligned middle aligned column slabel">
								<i class="user friends icon"></i> Series group
							</div>
							<div class="twelve wide column">
								<select name="s_seriesgroupid" class="ui small fluid dropdown" onChange="inputColor(this)" onInput="inputColor(this)" onBlur="inputColor(this)">
									<option value="">Select a group</option>
								<?
									$sqlstring = "select * from groups where group_type = 'series' order by group_name";
									$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
									while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
										$groupid = $row['group_id'];
										$groupname = $row['group_name'];
										$groupowner = $row['group_owner'];
										
										if ($groupid == $s['s_seriesgroupid']) {
											$selected = "selected";
										}
										else {
											$selected = "";
										}
										?>
										<option value="<?=$groupid?>" <?=$selected?>><?=$groupname?></option>
										<?
									}
								?>
								</select>
							</div>
							
						</div>
					</div>
				</div>
			</div>
			
			<div class="ui grey bottom attached segment">
				<div class="ui grid">
					<div class="one wide right aligned column">
						<h3 class="ui grey header">Output</h3>
					</div>
					<div class="eight wide left aligned column">
						<div class="ui top attached tabular menu">
							<a class="ui red active item" data-tab="first">Transfer Data</a>
							<a class="ui red item" data-tab="second" title="Enrollment and subject lists">Summary</a>
							<a class="ui red item" data-tab="third">Analysis</a>
							<a class="ui red item" data-tab="fourth">QC</a>
							<a class="ui red item" data-tab="fifth">Admin</a>
						</div>

						<div class="ui bottom attached active tab segment" data-tab="first">
							<div class="ui grouped fields">
								<div class="ui radio checkbox">
									<? if (($s['s_resultoutput'] == "study") || ($action == "")) { $checked = "checked"; } else { $checked = ""; }?>
									<input type="radio" name="s_resultoutput" id="downloadstudy" value="study" <?=$checked?>>
									<label>Group by study</label>
								</div>
								<br>
								<div class="ui radio checkbox">
									<? if ($s['s_resultoutput'] == "series") { $checked = "checked"; } else { $checked = ""; }?>
									<input type="radio" name="s_resultoutput" id="downloadseries" value="series" <?=$checked?>>
									<label>Display all series (use for "Select All")</label>
								</div>
								<br>
								<div class="ui radio checkbox">
									<? if ($s['s_resultoutput'] == "long") { $checked = "checked"; } else { $checked = ""; }?>
									<input type="radio" name="s_resultoutput" id="viewlong" value="long" <?=$checked?>>
									<label>Longitudinal</label>
								</div>
							</div>
						</div>
						
						<div class="ui bottom attached tab segment" data-tab="second">
							<div class="ui radio checkbox">
								<? if ($s['s_resultoutput'] == "table") { $checked = "checked"; } else { $checked = ""; }?>
								<input type="radio" name="s_resultoutput" id="viewtable" value="table" <?=$checked?>>
								<label>Table</label>
							</div>
							<br>
							<div class="ui radio checkbox">
								<? if ($s['s_resultoutput'] == "csv") { $checked = "checked"; } else { $checked = ""; }?>
								<input type="radio" name="s_resultoutput" id="viewcsv" value="csv" <?=$checked?>>
								<label>Spreadsheet <span class="tiny">.csv</span></label>
							</div>
							<br>
							<div class="ui radio checkbox">
								<? if ($s['s_resultoutput'] == "subject") { $checked = "checked"; } else { $checked = ""; }?>
								<input type="radio" name="s_resultoutput" id="downloadsubject" value="subject" <?=$checked?>>
								<label>Enrollment List</label>
							</div>
							<br>
							<div class="ui radio checkbox">
								<? if ($s['s_resultoutput'] == "uniquesubject") { $checked = "checked"; } else { $checked = ""; }?>
								<input type="radio" name="s_resultoutput" id="downloaduniquesubject" value="uniquesubject" <?=$checked?>>
								<label>Subject List</label>
							</div>
							<br>
							<div class="ui radio checkbox">
								<? if ($s['s_resultoutput'] == "thumbnails") { $checked = "checked"; } else { $checked = ""; }?>
								<input type="radio" name="s_resultoutput" id="viewthumbnails" value="thumbnails" <?=$checked?>>
								<label>Thumbnails</label>
							</div>
						</div>
						
						<div class="ui bottom attached tab segment" data-tab="third">
							<table width="100%" cellspacing="0" cellpadding="3" style="font-size:11pt">
								<tr>
									<td class="fieldlabel" width="150px">Pipeline</td>
									<td>
									<select name="s_pipelineid" onClick="SwitchOption('viewpipeline')">
										<option value="">Select pipeline</option>
									<?
										$sqlstring2 = "select pipeline_id, pipeline_name from pipelines order by pipeline_name";
										$result2 = MySQLiQuery($sqlstring2,__FILE__,__LINE__);
										while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
											$pipelineid = $row2['pipeline_id'];
											$pipelinename = $row2['pipeline_name'];
											?>
											<option value="<?=$pipelineid?>" <? if ($s['s_pipelineid'] == $pipelineid) { echo "selected"; } ?>><?=$pipelinename?></option>
											<?
										}
									?>
									</select>
									</td>
								</tr>
								<tr>
									<td class="fieldlabel" width="150px">Result name</td>
									<td><input type="text" name="s_pipelineresultname" onClick="SwitchOption('viewpipeline')" value="<?=$s['s_pipelineresultname']?>" size="50" class="importantfield"></td>
								</tr>
								<tr>
									<td class="fieldlabel" width="150px">Result unit</td>
									<td><input type="text" name="s_pipelineresultunit" onClick="SwitchOption('viewpipeline')" value="<?=$s['s_pipelineresultunit']?>" size="20" maxsize="20" class="importantfield"></td>
								</tr>
								<tr>
									<td class="fieldlabel" width="150px">Result type</td>
									<td>
										<div class="ui radio checkbox">
											<input type="radio" name="s_pipelineresulttype" value="" onClick="SwitchOption('viewpipeline')" <? if ($s['s_pipelineresulttype'] == '') { echo "checked"; } ?>>
											<label>None</label>
										</div>
										<br>
										<div class="ui radio checkbox">
											<input type="radio" name="s_pipelineresulttype" value="v" onClick="SwitchOption('viewpipeline')" <? if ($s['s_pipelineresulttype'] == 'v') { echo "checked"; } ?>>
											<label>Value</label>
										</div>
										<br>
										<div class="ui radio checkbox">
											<input type="radio" name="s_pipelineresulttype" value="i" onClick="SwitchOption('viewpipeline')" <? if ($s['s_pipelineresulttype'] == 'i') { echo "checked"; } ?>>
											<label>Image</label>
										</div>
										<br>
										<div class="ui radio checkbox">
											<input type="radio" name="s_pipelineresulttype" value="f" onClick="SwitchOption('viewpipeline')" <? if ($s['s_pipelineresulttype'] == 'f') { echo "checked"; } ?>>
											<label>File</label>
										</div>
										<br>
										<div class="ui radio checkbox">
											<input type="radio" name="s_pipelineresulttype" value="h" onClick="SwitchOption('viewpipeline')" <? if ($s['s_pipelineresulttype'] == 'h') { echo "checked"; } ?>>
											<label>HTML</label>
										</div>
									</td>
								</tr>
								<tr>
									<td class="fieldlabel" width="150px" valign="top">Result value</td>
									<td valign="top">
										<div class="ui inline field">
											<select name="s_pipelineresultcompare" onClick="SwitchOption('viewpipeline')">
												<option value="=" <? if ($s['s_pipelineresultcompare'] == '=') { echo "selected"; } ?>>=
												<option value=">" <? if ($s['s_pipelineresultcompare'] == '>') { echo "selected"; } ?>>&gt;
												<option value=">=" <? if ($s['s_pipelineresultcompare'] == '>=') { echo "selected"; } ?>>&gt;=
												<option value="<" <? if ($s['s_pipelineresultcompare'] == '<') { echo "selected"; } ?>>&lt;
												<option value="<=" <? if ($s['s_pipelineresultcompare'] == '<=') { echo "selected"; } ?>>&lt;=
											</select>
											<input type="text" name="s_pipelineresultvalue" onClick="SwitchOption('viewpipeline')" value="<?=$s['s_pipelineresultvalue']?>" size="15" class="smallsearchbox">
										</div>
										<div class="ui checkbox">
											<input type="checkbox" name="s_pipelinecolorize" onClick="SwitchOption('viewpipeline')" value="1" <? if ($s['s_pipelinecolorize'] == 1) { echo "checked"; } ?>>
											<label>Colorize <span class="tiny">low <img src="images/colorbar.png"> high</span></label>
										</div>
										<br>
										<!--<input type="checkbox" name="s_pipelinecormatrix" onClick="SwitchOption('viewpipeline')" value="1" <? if ($s['s_pipelinecormatrix'] == 1) { echo "checked"; } ?>>Display correlation matrix <span class="tiny">Slow for large result sets</span>
										<br>-->
										<div class="ui checkbox">
											<input type="checkbox" name="s_pipelineresultstats" onClick="SwitchOption('viewpipeline')" value="1" <? if ($s['s_pipelineresultstats'] == 1) { echo "checked"; } ?>>
											<label>Display result statistics</label>
										</div>
									</td>
								</tr>
							</table>
							<br><br>
						
							<? if ($s['s_resultoutput'] == "pipeline") { $checked = "checked"; } else { $checked = ""; }?>
							<div class="ui radio checkbox">
								<input type="radio" name="s_resultoutput" id="viewpipeline" value="pipeline" <?=$checked?>>
								<label>Pipeline results</label>
							</div>
							<br>
							
							<? if ($s['s_resultoutput'] == "pipelinecsv") { $checked = "checked"; } else { $checked = ""; }?>
							<div class="ui radio checkbox">
								<input type="radio" name="s_resultoutput" id="viewpipelinecsv" value="pipelinecsv" <?=$checked?>>
								<label>Pipeline results <span class="tiny">.csv</span></label>
							</div>
							<br>
							
							<? if ($s['s_resultoutput'] == "pipelinelong") { $checked = "checked"; } else { $checked = ""; }?>
							<div class="ui radio checkbox">
								<input type="radio" name="s_resultoutput" id="pipelinelong" value="pipelinelong" <?=$checked?>>
								<label>Longitudinal results <span class="tiny">bin by month</span></label>
							</div>
							<br>
							
							<? if ($s['s_resultoutput'] == "pipelinelongyear") { $checked = "checked"; } else { $checked = ""; }?>
							<div class="ui radio checkbox">
								<input type="radio" name="s_resultoutput" id="pipelinelongyear" value="pipelinelongyear" <?=$checked?>>
								<label>Longitudinal results <span class="tiny">bin by year</span></label>
							</div>
							<br>
						</div>
								
						<div class="ui bottom attached tab segment" data-tab="fourth">
							QC variable <span class="tiny">built-in</span>&nbsp;
							<select name="s_qcbuiltinvariable">
								<option value="">(Select built-in QC variable)
								<option value="all" selected>ALL available variables
								<option value="iosnr">IO SNR
								<option value="pvsnr">PV SNR
								<option value="totaldisp">Total displacement [mm]
							</select>
							<br>
							QC variable <span class="tiny">modular</span>&nbsp;
							<select name="s_qcvariableid">
								<option value="">(Select modular QC variable)
								<option value="all">ALL available variables
								<?
									$sqlstring2 = "select * from qc_resultnames where qcresult_type = 'number' order by qcresult_name";
									$result2 = MySQLiQuery($sqlstring2,__FILE__,__LINE__);
									while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
										$qcresultnameid = $row2['qcresultname_id'];
										$qcresultname = $row2['qcresult_name'];
										$qcresultunits = $row2['qcresult_units'];
										?>
										<option value="<?=$qcresultnameid?>" <? if ($s['s_qcvariableid'] == $qcresultnameid) { echo "selected"; } ?>><?=$qcresultname?> [<?=$qcresultunits?>]</option>
										<?
									}
									
								?>
							</select>
							<br><br>
							<? if ($s['s_resultoutput'] == "qcchart") { $checked = "checked"; } else { $checked = ""; }?>
							<div class="ui radio checkbox">
								<input type="radio" name="s_resultoutput" id="qcchart" value="qcchart" <?=$checked?>>
								<label>Chart</label>
							</div>
							<br>
							
							<? if ($s['s_resultoutput'] == "qctable") { $checked = "checked"; } else { $checked = ""; }?>
							<div class="ui radio checkbox">
								<input type="radio" name="s_resultoutput" id="qctable" value="qctable" <?=$checked?>>
								<label>Table</label>
							</div>
							<br>
						</div>
								
						<div class="ui bottom attached tab segment" data-tab="fifth">
							<? if ($s['s_resultoutput'] == "debug") { $checked = "checked"; } else { $checked = ""; }?>
							<div class="ui radio checkbox">
								<input type="radio" name="s_resultoutput" id="viewdebug" value="debug" <?=$checked?>>
								<label>Debug <span class="tiny">SQL</span></label>
							</div>
							<br>
							
							<? if ($GLOBALS['isadmin']) { ?>
							<? if ($s['s_resultoutput'] == "operations") { $checked = "checked"; } else { $checked = ""; }?>
							<div class="ui radio checkbox">
								<input type="radio" name="s_resultoutput" id="viewoperations" value="operations" <?=$checked?>>
								<label>File operations</label>
							</div>
							<? } ?>
							<br>
							
							<? if ($s['s_audit'] == "1") { $checked = "checked"; } else { $checked = ""; }?>
							<div class="ui checkbox">
								<input type="checkbox" name="s_audit" value="1" <?=$checked?>>
								<label>Audit <span class="tiny">files</span></label>
							</div>
						</div>
					
					</div>
					<div class="seven wide middle aligned column">
						<button class="ui huge primary button" type="submit"><i class="search icon"></i> Search</button>
					</div>
				</div>
			</div>
			
		</div>
		<div class="one wide column">&nbsp;</div>
	</div>
	
	
	</form>
	</div>
	<br><br><br>
	<?
	}

	
	/* -------------------------------------------- */
	/* ------- UpdateSearchHistory ---------------- */
	/* -------------------------------------------- */
	function UpdateSearchHistory($s) {
		
		/* get the users id */
		$sqlstring = "select user_id from users where username = '" . $_SESSION['username'] ."'";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$userid = $row['user_id'];
		
		if ($userid == "") {
			Error("Username was blank. You appear not to be logged in. Please login with your username to access NiDB");
			return;
		}
		
		/* only keep the 10 most recent searches */
		$sqlstring = "delete from search_history where user_id = $userid and searchhistory_id not in (select * from (select searchhistory_id from search_history where user_id = $userid order by date_added desc limit 10) temp_tab)";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* escape all the variables and put them back into meaningful variable names */
		foreach ($s as $key => $value) {
			if (is_array($value)) { $$key = mysqli_real_escape_array($s[$key]); }
			elseif (is_scalar($value)) { $$key = mysqli_real_escape_string($GLOBALS['linki'], $s[$key]); }
			else { $$key = $s[$key]; }
		}

		$s_subjectuid = ($s_subjectuid == '') ? "null" : "'$s_subjectuid'";
		$s_subjectaltuid = ($s_subjectaltuid == '') ? "null" : "'$s_subjectaltuid'";
		$s_subjectname = ($s_subjectname == '') ? "null" : "'$s_subjectname'";
		$s_subjectdobstart = ($s_subjectdobstart == '') ? "null" : "'$s_subjectdobstart'";
		$s_subjectdobend = ($s_subjectdobend == '') ? "null" : "'$s_subjectdobend'";
		$s_ageatscanmin = ($s_ageatscanmin == '') ? "null" : "'$s_ageatscanmin'";
		$s_ageatscanmax = ($s_ageatscanmax == '') ? "null" : "'$s_ageatscanmax'";
		$s_subjectgender = ($s_subjectgender == '') ? "null" : "'$s_subjectgender'";
		$s_subjectgroupid = ($s_subjectgroupid == '') ? "null" : "'$s_subjectgroupid'";
		
		if (($s_projectids == '') || ($s_projectids == 'all')) { $projectid = "null"; } else { $projectid = "'" . implode2(",", $s_projectids) . "'"; }
		
		$s_enrollsubgroup = ($s_enrollsubgroup == '') ? "null" : "'$s_enrollsubgroup'";
		$s_measuresearch = ($s_measuresearch == '') ? "null" : "'$s_measuresearch'";
		$s_measurelist = ($s_measurelist == '') ? "null" : "'$s_measurelist'";
		$s_studyinstitution = ($s_studyinstitution == '') ? "null" : "'$s_studyinstitution'";
		$s_studyequipment = ($s_studyequipment == '') ? "null" : "'$s_studyequipment'";
		$s_studyid = ($s_studyid == '') ? "null" : "'$s_studyid'";
		$s_studyaltscanid = ($s_studyaltscanid == '') ? "null" : "'$s_studyaltscanid'";
		$s_studydatestart = ($s_studydatestart == '') ? "null" : "'$s_studydatestart'";
		$s_studydateend = ($s_studydateend == '') ? "null" : "'$s_studydateend'";
		$s_studydesc = ($s_studydesc == '') ? "null" : "'$s_studydesc'";
		$s_studyphysician = ($s_studyphysician == '') ? "null" : "'$s_studyphysician'";
		$s_studyoperator = ($s_studyoperator == '') ? "null" : "'$s_studyoperator'";
		$s_studytype = ($s_studytype == '') ? "null" : "'$s_studytype'";
		$s_studymodality = ($s_studymodality == '') ? "null" : "'$s_studymodality'";
		$s_studygroupid = ($s_studygroupid == '') ? "null" : "'$s_studygroupid'";
		$s_seriesdesc = ($s_seriesdesc == '') ? "null" : "'$s_seriesdesc'";
		$s_usealtseriesdesc = ($s_usealtseriesdesc == '') ? "null" : "'$s_usealtseriesdesc'";
		$s_seriessequence = ($s_seriessequence == '') ? "null" : "'$s_seriessequence'";
		$s_seriesimagetype = ($s_seriesimagetype == '') ? "null" : "'$s_seriesimagetype'";
		$s_seriestr = ($s_seriestr == '') ? "null" : "'$s_seriestr'";
		$s_seriesimagecomments = ($s_seriesimagecomments == '') ? "null" : "'$s_seriesimagecomments'";
		$s_seriesnum = ($s_seriesnum == '') ? "null" : "'$s_seriesnum'";
		$s_seriesnumfiles = ($s_seriesnumfiles == '') ? "null" : "'$s_seriesnumfiles'";
		$s_seriesgroupid = ($s_seriesgroupid == '') ? "null" : "'$s_seriesgroupid'";
		$s_pipelineid = ($s_pipelineid == '') ? "null" : "'$s_pipelineid'";
		$s_pipelineresultname = ($s_pipelineresultname == '') ? "null" : "'$s_pipelineresultname'";
		$s_pipelineresultunit = ($s_pipelineresultunit == '') ? "null" : "'$s_pipelineresultunit'";
		$s_pipelineresultvalue = ($s_pipelineresultvalue == '') ? "null" : "'$s_pipelineresultvalue'";
		$s_pipelineresultcompare = ($s_pipelineresultcompare == '') ? "null" : "'$s_pipelineresultcompare'";
		$s_pipelineresulttype = ($s_pipelineresulttype == '') ? "null" : "'$s_pipelineresulttype'";
		$s_pipelinecolorize = ($s_pipelinecolorize == '') ? "null" : "'$s_pipelinecolorize'";
		$s_pipelinecormatrix = ($s_pipelinecormatrix == '') ? "null" : "'$s_pipelinecormatrix'";
		$s_pipelineresultstats = ($s_pipelineresultstats == '') ? "null" : "'$s_pipelineresultstats'";
		$s_resultoutput = ($s_resultoutput == '') ? "null" : "'$s_resultoutput'";
		$s_formid = ($s_formid == '') ? "null" : "'$s_formid'";
		$s_formfieldid = ($s_formfieldid == '') ? "null" : "'$s_formfieldid'";
		$s_formcriteria = ($s_formcriteria == '') ? "null" : "'$s_formcriteria'";
		$s_formvalue = ($s_formvalue == '') ? "null" : "'$s_formvalue'";
		$s_audit = ($s_audit == '') ? "null" : "'$s_audit'";
		$s_qcbuiltinvariable = ($s_qcbuiltinvariable == '') ? "null" : "'$s_qcbuiltinvariable'";
		$s_qcvariableid = ($s_qcvariableid == '') ? "null" : "'$s_qcvariableid'";

		$sqlstring = "insert into search_history (user_id, date_added, saved_name, subjectuid, subjectaltuid, subjectname, subjectdobstart, subjectdobend, ageatscanmin, ageatscanmax, subjectgender, subjectgroupid, projectids, enrollsubgroup, measuresearch, measurelist, studyinstitution, studyequipment, studyid, studyaltscanid, studydatestart, studydateend, studydesc, studyphysician, studyoperator, studytype, studymodality, studygroupid, seriesdesc, usealtseriesdesc, seriessequence, seriesimagetype, seriestr, seriesimagecomments, seriesnum, seriesnumfiles, seriesgroupid, pipelineid, pipelineresultname, pipelineresultunit, pipelineresultvalue, pipelineresultcompare, pipelineresulttype, pipelinecolorize, pipelinecormatrix, pipelineresultstats, resultorder, formid, formfieldid, formcriteria, formvalue, audit, qcbuiltinvariable, qcvariableid) values ($userid, now(), '', $s_subjectuid, $s_subjectaltuid, $s_subjectname, $s_subjectdobstart, $s_subjectdobend, $s_ageatscanmin, $s_ageatscanmax, $s_subjectgender, $s_subjectgroupid, $projectid, $s_enrollsubgroup, $s_measuresearch, $s_measurelist, $s_studyinstitution, $s_studyequipment, $s_studyid, $s_studyaltscanid, $s_studydatestart, $s_studydateend, $s_studydesc, $s_studyphysician, $s_studyoperator, $s_studytype, $s_studymodality, $s_studygroupid, $s_seriesdesc, $s_usealtseriesdesc, $s_seriessequence, $s_seriesimagetype, $s_seriestr, $s_seriesimagecomments, $s_seriesnum, $s_seriesnumfiles, $s_seriesgroupid, $s_pipelineid, $s_pipelineresultname, $s_pipelineresultunit, $s_pipelineresultvalue, $s_pipelineresultcompare, $s_pipelineresulttype, $s_pipelinecolorize, $s_pipelinecormatrix, $s_pipelineresultstats, $s_resultoutput, $s_formid, $s_formfieldid, $s_formcriteria, $s_formvalue, $s_audit, $s_qcbuiltinvariable, $s_qcvariableid)";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
	}

	/* -------------------------------------------- */
	/* ------- DisplaySearchHistory --------------- */
	/* -------------------------------------------- */
	function DisplaySearchHistory() {
		
		/* get the users id */
		$sqlstring = "select user_id from users where username = '" . $_SESSION['username'] ."'";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$userid = $row['user_id'];
		
		if ($userid == "") { return; }
		
		$sqlstring = "select * from search_history where user_id = $userid order by date_added desc";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if (mysqli_num_rows($result) < 1) {
			echo "No search history";
		}
		else {
			?>
			<div class="ui inverted accordion">
				<div class="title">
					<i class="dropdown icon"></i> Recent Searches
				</div>
				<div class="content">
					<div class="ui segment">
						<div class="ui divided list">
						<?
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$searchhistoryid = $row['searchhistory_id'];
							$userid = $row['user_id'];
							$date_added = $row['date_added'];
							$saved_name = $row['saved_name'];
							
							$s['UID(s)'] = $row['subjectuid'];
							$s['Alt UID(s)'] = $row['subjectaltuid'];
							$s['Name'] = $row['subjectname'];
							$s['DOB Start'] = $row['subjectdobstart'];
							$s['DOB End'] = $row['subjectdobend'];
							$s['Age Min'] = $row['ageatscanmin'];
							$s['Age Max'] = $row['ageatscanmax'];
							$s['Gender'] = $row['subjectgender'];
							$s['Subject Group ID'] = $row['subjectgroupid'];
							$s['Project ID'] = $row['projectid'];
							$s['Enroll Subgroup'] = $row['enrollsubgroup'];
							$s['Measure Search'] = $row['measuresearch'];
							$s['Measure List'] = $row['measurelist'];
							$s['Institution'] = $row['studyinstitution'];
							$s['Equipment'] = $row['studyequipment'];
							$s['Study ID'] = $row['studyid'];
							$s['Study Alt Scan ID'] = $row['studyaltscanid'];
							$s['Study Date Start'] = $row['studydatestart'];
							$s['Study Date End'] = $row['studydateend'];
							$s['Study Desc'] = $row['studydesc'];
							$s['study Physician'] = $row['studyphysician'];
							$s['Operator'] = $row['studyoperator'];
							$s['Study Type'] = $row['studytype'];
							$s['Modality'] = $row['studymodality'];
							$s['Study Group ID'] = $row['studygroupid'];
							$s['Series Desc'] = $row['seriesdesc'];
							$s['usealtseriesdesc'] = $row['usealtseriesdesc'];
							$s['Sequence'] = $row['seriessequence'];
							$s['Image Type'] = $row['seriesimagetype'];
							$s['TR'] = $row['seriestr'];
							$s['Image Comments'] = $row['seriesimagecomments'];
							$s['Series Num'] = $row['seriesnum'];
							$s['Num Files'] = $row['seriesnumfiles'];
							$s['Series Group ID'] = $row['seriesgroupid'];
							$s['Pipeline ID'] = $row['pipelineid'];
							$s['Pipeline Result Name'] = $row['pipelineresultname'];
							$s['Pipeline Result Unit'] = $row['pipelineresultunit'];
							$s['Pipeline Result Value'] = $row['pipelineresultvalue'];
							$s['Pipeline Result Type'] = $row['pipelineresulttype'];
							$s['Result Order'] = $row['resultorder'];
							$s['Form ID'] = $row['formid'];
							$s['Form Field ID'] = $row['formfieldid'];
							$s['Form Criteria'] = $row['formcriteria'];
							$s['Form Value'] = $row['formvalue'];
							
							$searchterms = "";
							foreach ($s as $key => $value) {
								if ((trim($value) != "") && (trim(strtolower($value)) != "null")) {
									$searchterms .= " <span style='color: gray'>$key</span> <b>$value</b> &nbsp; ";
								}
							}
							if ($searchterms != "") {
							?>
								<div class="item">
									<i class="sticky note outline icon"></i>
									<div class="content">
										<a href="search.php?s_searchhistoryid=<?=$searchhistoryid?>" class="header"><?=$searchterms?></a>
										<div class="description"><?=$date_added?></div>
									</div>
								</div>
							<?
							}
						}
						?>
						</div>
					</div>
				</div>
			</div>
			<?
		}
	}
	

	/* -------------------------------------------- */
	/* ------- Search ----------------------------- */
	/* -------------------------------------------- */
	function Search($s) {
		
		$msg = ValidateSearchVariables($s);
		
		if ($msg != "") {
			Error("Search error", $msg);
		}
		else {
		}
		
		/*
			***************** steps to searching *****************
			1) build the search string
			2) run the query
			3) depending on the query type, either...
				a) display the query, then end
				b) display the results
		*/
		
		/* --------- [1] get the SQL search string ---------- */
		$sqlstring = BuildSQLString($s);

		if ($sqlstring == "") { return; }
		
		/* ---------- [2] run the query ----------- */
		$starttime = microtime(true);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$querytime = microtime(true) - $starttime;
		
		/* escape all the variables and put them back into meaningful variable names */
		foreach ($s as $key => $value) {
			if (is_scalar($value)) { $$key = mysqli_real_escape_string($GLOBALS['linki'], $s[$key]); }
			else { $$key = $s[$key]; }
		}
		
		/* make modality lower case to conform with table names... MySQL table names are case sensitive when using the 'show tables' command */
		$s_studymodality = strtolower($s_studymodality);

		list($numrows, $numsubjects, $numstudies, $totalbytes, $missinguids, $missingaltuids, $misingstudynums) = GetResultMetrics($result, $sqlstring, $s);
		
		DisplayResultMetrics($numrows, $numsubjects, $numstudies, $totalbytes, $missinguids, $missingaltuids, $misingstudynums, $restrictedprojectnames, $querytime, $sqlstring, $s);
		
		if (($numrows > 100000) && ($s_resultoutput != "pipelinecsv"))
			return;

		/* display the results */
		if ($numrows > 0) {
		
			/* generate a color gradient in an array (green to yellow to red) */
			$colors = GenerateColorGradient();
			$colors2 = GenerateColorGradient2();
			
			/* display the number of rows and the search time */
			?>
			<style>
			#preview {
				position:absolute;
				border:1px solid #ccc;
				background:gray;
				padding:0px;
				display:none;
				color:#fff;
			}
			</style>
			<script type="text/javascript">
			// Popup window code
			function newPopup(url) {
				popupWindow = window.open(
					url,'popUpWindow','height=700,width=800,left=10,top=10,resizable=yes,scrollbars=yes,toolbar=no,menubar=no,location=no,directories=no,status=no')
			}
			</script>
			<?
			/* ---------- pipeline results ------------ */
			if (($s_resultoutput == "pipeline") || ($s_resultoutput == "pipelinecsv")) {
				DisplaySearchResultsPipeline($result, $s_resultoutput, $s_pipelineresulttype, $s_pipelinecolorize, $s_pipelinecormatrix, $s_pipelineresultstats);
			}
			elseif ($s_resultoutput == 'subject') {
				/* display only subject data */
				DisplaySearchResultsStudy($result);
			}
			elseif ($s_resultoutput == 'uniquesubject') {
				/* display only unique subject data */
				DisplaySearchResultsSubject($result);
			}
			elseif ($s_resultoutput == 'thumbnails') {
				/* display thumbnails */
				DisplaySearchResultsThumbnail($result, $s);
			}
			elseif ($s_resultoutput == 'long') {
				/* display longitudinal data */
				DisplaySearchResultsLongitudinal($result);
			}
			elseif (($s_resultoutput == 'pipelinelong') || ($s_resultoutput == 'pipelinelongyear')) {
				/* display longitudinal pipeline data */
				DisplaySearchResultsLongitudinalPipeline($result, $s_resultoutput);
			}
			elseif (($s_resultoutput == 'qcchart') || ($s_resultoutput == 'qctable')) {
				/* display longitudinal pipeline data */
				DisplaySearchResultsQC($result, $s_resultoutput, $s_qcbuiltinvariable, $s_qcvariableid);
			}
			elseif (($s_resultoutput == 'table') || ($s_resultoutput == 'csv')) {
				/* display table or csv */
				DisplaySearchResultsTable($result, $s);
			}
			else {
				/* regular old search */
				DisplaySearchResultsDefault($result, $s, $colors, $colors2);
			}
		}
	}


	/* -------------------------------------------- */
	/* ------- GetResultMetrics ------------------- */
	/* -------------------------------------------- */
	function GetResultMetrics($result, $sqlstring, $s) {
		$numresults = 0;
		$missinguids = array();
		$missingaltuids = array();
		$misingstudynums = array();
		
		/* ----- get number of results ----- */
		$numresults = mysqli_num_rows($result);
		
		/* ----- get list of projects they don't have access to ----- */
		$allowedprojectids = array();
		$allowedprojectnames = array();
		/* check to see which projects this user has access to view */
		$sqlstringC = "select a.project_id 'projectid', b.project_name 'projectname' from user_project a left join projects b on a.project_id = b.project_id where a.user_id = '" . $_SESSION['userid'] . "' and (a.view_data = 1 or a.view_phi = 1)";
		$resultC = MySQLiQuery($sqlstringC,__FILE__,__LINE__);
		while ($rowC = mysqli_fetch_array($resultC, MYSQLI_ASSOC)) {
			$restrictedprojectids[] = $rowC['projectid'];
		}
		
		/* ----- get list of unique subjects/studies/series ----- */
		/* ----- if a project or group was specified, get list of IDs (UIDs/StudyIDs) which were found, and which were not found ----- */
		$totalbytes = 0;
		$subjects = array();
		$studies = array();
		$uids = array();
		$subjectids = array();
		/* tell the user if there are results for projects they don't have access to */
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$projectid = $row['project_id'];
			$projectname = $row['project_name'];
			$studyid = $row['study_id'];
			$subjectid = $row['subject_id'];
			$uid = $row['uid'];

			if (!in_array($projectid, $allowedprojectids)) {
				//echo "$projectid is not in allowedprojectids<br>";
				if (!in_array($projectname, $allowedprojectnames)) {
					//echo "$projectname is not in allowedprojectnames<br>";
					$restrictedprojectnames[] = $projectname;
				}
			}
			
			/* ... AND ... while we're in this loop, count the number of unique studies ... */
			if ((!isset($studies)) || (!in_array($studyid, $studies))) {
				$studies[] = $studyid;
			}
			/* ... and # of unique subjects */
			if ((!isset($subjects)) || (!in_array($subjectid, $subjects))) {
				$subjects[] = $subjectid;
			}
			/* also a unique list of UIDs ... */
			if ((!isset($uids)) || (!in_array($uid, $uids))) {
				$uids[] = $uid;
			}
			/* ... and a unique list of SubjectIDs */
			if ((!isset($subjectids)) || (!in_array($subjectid, $subjectids))) {
				$subjectids[] = $subjectid;
			}
			/* and get the total size of the data */
			$totalbytes += $row['series_size'];
		}
		$numsubjects = count($subjects);
		$numstudies = count($studies);

		/* if a project is selected, get a list of the display IDs (the primary project ID) to be used instead of the UID */
		if (($s['s_projectids'] != "") && ($s['s_projectids'] != "all")) {
			foreach ($subjectids as $subjid) {
				foreach ($s['s_projectids'] as $projectid) {
					if ($projectid != "") {
						$displayids[$subjid] = GetPrimaryProjectID($subjid, $projectid);
					}
				}
			}
		}
		
		/* if there was a list of UIDs or alternate UIDs, determine which were not found */
		if ($s['s_subjectuid'] != "") {
			$uidsearchlist = preg_split('/[\^,;\'\s\t\n\f\r]+/', $s['s_subjectuid']);
			
			$missinguids = array_udiff($uidsearchlist, $uids, 'strcasecmp');
		}
		if ($s['s_subjectaltuid'] != "") {
			$altuidsearchlist = preg_split('/[\^,;\'\s\t\n\f\r]+/', $s['s_subjectaltuid']);

			/* get list of UIDs from the list of alternate UIDs */
			if (count($subjectids) > 0) {
				$sqlstringX = "select altuid from subject_altuid where subject_id in (" . implode2(',',$subjectids) . ")";
				$resultX = MySQLiQuery($sqlstringX,__FILE__,__LINE__);
				while ($rowX = mysqli_fetch_array($resultX, MYSQLI_ASSOC)) {
					$altuids[] = $rowX['altuid'];
				}
				$missingaltuids = array_udiff($altuidsearchlist,$altuids, 'strcasecmp');
			}
		}
		if ($s['s_subjectgroupid'] != "") {
			$subjectids = explode(',', GetIDListFromGroup($s['s_subjectgroupid']));
			$missingsubjects = array_udiff($subjectids,$subjects, 'strcasecmp');
			if (count($missingstudies) > 0) {
				$sqlstringY = "select uid from subjects where subject_id in (" . implode(',',$missingsubjects) . ")";
				$resultY = MySQLiQuery($sqlstringY,__FILE__,__LINE__);
				while ($rowY = mysqli_fetch_array($resultY, MYSQLI_ASSOC)) {
					$missinguids[] = $rowY['uid'];
				}
			}
		}
		if ($s['s_studygroupid'] != "") {
			$studyids = explode(',', GetIDListFromGroup($s['s_studygroupid']));
			$missingstudies = array_udiff($studyids,$studies, 'strcasecmp');
			if (count($missingstudies) > 0) {
				$sqlstringY = "select a.study_num, c.uid from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on c.subject_id = b.subject_id where study_id in (" . implode(',',$missingstudies) . ")";
				$resultY = MySQLiQuery($sqlstringY,__FILE__,__LINE__);
				while ($rowY = mysqli_fetch_array($resultY, MYSQLI_ASSOC)) {
					$missingstudynums[] = $rowY['uid'] . $rowY['study_num'];
				}
			}
		}
		
		$missinguids = array_filter($missinguids);
		$missingaltuids = array_filter($missingaltuids);
		$missingstudynums = array_filter($missingstudynums);
		$restrictedprojectnames = array_filter($restrictedprojectnames);
		
		return array($numresults, $numsubjects, $numstudies, $totalbytes, $missinguids, $missingaltuids, $misingstudynums, $restrictedprojectnames);
	}


	/* -------------------------------------------- */
	/* ------- DisplayResultMetrics --------------- */
	/* -------------------------------------------- */
	function DisplayResultMetrics($numrows, $numsubjects, $numstudies, $totalbytes, $missinguids, $missingaltuids, $misingstudynums, $restrictedprojectnames, $querytime, $sqlstring, $s) {
		?>
		<div class="ui container">
		<div class="ui top attached grey segment">
			Found <b><?=$numsubjects?> subjects</b> in <b><?=$numstudies?> studies</b> with <b><?=number_format($numrows,0)?> series</b> matching your query (<?=HumanReadableFilesize($totalbytes);?> data)
		</div>
		<?
			if ((mysqli_num_rows($result) > 100000) && ($s['s_resultoutput'] != "pipelinecsv")) {
				?>
				<div class="ui attached red message">
				<b>Your search returned <? echo number_format(mysqli_num_rows($result),0); ?> results... which is a lot</b>
				<br>
				Try changing the search criteria to return fewer results or select a .csv format
				</div>
				<?
			}

			if (count($missinguids) > 0) {
			?>
				<div class="ui inverted orange attached segment" style="padding-top:5px; padding-bottom:5px">
					<div class="ui inverted accordion">
						<div class="inverted title" style="padding-top:5px; padding-bottom:5px">
							<i class="dropdown icon"></i>
							<?=count($missinguids)?> UIDs not found
						</div>
						<div class="content">
							<?=implode('<br>',$missinguids)?>
						</div>
					</div>
				</div>
			<?
			}
			elseif ($s['s_subjectuid'] != "") {
			?>
				<div class="ui attached segment" style="padding-top:5px; padding-bottom:5px">All UIDs found</div>
			<?
			}
			
			if (count($missingaltuids) > 0) {
			?>
				<div class="ui inverted orange attached segment" style="padding-top:5px; padding-bottom:5px">
					<div class="ui accordion">
						<div class="title" style="padding-top:5px; padding-bottom:5px">
							<i class="dropdown icon"></i>
							<?=count($missingaltuids)?> alternate UIDs not found
						</div>
						<div class="content">
							<?=implode('<br>',$missingaltuids)?>
						</div>
					</div>
				</div>
			<?
			}
			elseif ($s['s_subjectaltuid'] != "") {
			?>
				<div class="ui attached segment" style="padding-top:5px; padding-bottom:5px">All alternate UIDs found</div>
			<?
			}
			
			if (count($missingstudynums) > 0) {
			?>
				<div class="ui inverted orange attached segment" style="padding-top:5px; padding-bottom:5px">
					<div class="ui accordion">
						<div class="title">
							<i class="dropdown icon"></i>
							<?=count($missingstudynums)?> Studies not found
						</div>
						<div class="content">
							<?=implode('<br>',$missingstudynums)?>
						</div>
					</div>
				</div>
			<?
			}
		?>
		<?
		if (count($restrictedprojectnames) > 0) {
		?>
			<div class="ui inverted orange attached segment">
				<b>Your search results contain subjects enrolled in the following projects to which you do not have view access</b>
				<br>Contact your PI or project administrator for access
				<ul>
					<?
					natcasesort($restrictedprojectnames);
					foreach ($restrictedprojectnames as $projectname) {
						echo "<li>$projectname</li>\n";
					}
					?>
				</ul>
			</div>
			<?
		}
		
		?>
			<div class="ui <? if ($numrows > 0) { echo "bottom"; } ?> attached segment" style="padding-top:5px; padding-bottom:5px; font-size: smaller">
				Query returned <? echo number_format($numrows,0); ?> rows in <?=number_format($querytime, 4)?> sec</span>
				<div class="ui accordion" style="padding-top:5px; padding-bottom:5px">
					<div class="title" style="padding-top:5px; padding-bottom:5px">
						<i class="dropdown icon"></i>
						View SQL query
					</div>
					<div class="content" style="padding-top:5px; padding-bottom:5px">
						<div class="ui segment"><span class="tt"><?=getFormattedSQL($sqlstring)?></span></div>
					</div>
				</div>
			</div>
		<?
		if ($numrows < 1) {
			?>
			<div class="ui bottom attached yellow message">Query returned no results</div>
			<?
		}
		?>
		</div>
		<br><br>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- ValidateSearchVariables ------------ */
	/* -------------------------------------------- */
	function ValidateSearchVariables($s) {
		
		/* check which resultorder (type of result display) was selected */
		switch ($s['s_resultoutput']) {
			case 'pipeline':
			case 'pipelinecsv':
				if (trim($s['s_pipelineid']) == "") {
					$msg = "Pipeline not selected";
				}
				break;
			default:
				break;
		}
		
		return $msg;
	}

	
	/* -------------------------------------------- */
	/* ------- DisplaySearchResultsDefault -------- */
	/* -------------------------------------------- */
	function DisplaySearchResultsDefault(&$result, $s, $colors, $colors2) {
		error_reporting(-1);
		ini_set('display_errors', '1');
	
		/* escape all the variables and put them back into meaningful variable names */
		foreach ($s as $key => $value) {
			if (is_scalar($value)) { $$key = mysqli_real_escape_string($GLOBALS['linki'], $s[$key]); }
			else { $$key = $s[$key]; }
		}

		/* ---------------- regular search --------------- */
		$s_studymodality = strtolower($s_studymodality);
		$sqlstring3 = "select data_id, rating_value from ratings where rating_type = 'series' and data_modality = '$s_studymodality'";
		$result3 = MySQLiQuery($sqlstring3,__FILE__,__LINE__);
		while ($row3 = mysqli_fetch_array($result3, MYSQLI_ASSOC)) {
			$ratingseriesid = $row3['data_id'];
			$ratings[$ratingseriesid][] = $row3['rating_value'];
		}
		?>
		<br><br>
		<form name="subjectlist" method="post" action="search.php" class="ui form">
		<input type="hidden" name="modality" value="<?=$s_studymodality?>">
		<input type="hidden" name="action" value="submit">
		<?
		
		/* if its MRI, get the basic QC data */
		if (strtolower($s_studymodality) == "mr") {
			/* get the movement & SNR stats by sequence name */
			$sqlstring2 = "SELECT b.series_sequencename, max(a.move_maxx) 'maxx', min(a.move_minx) 'minx', max(a.move_maxy) 'maxy', min(a.move_miny) 'miny', max(a.move_maxz) 'maxz', min(a.move_minz) 'minz', avg(a.pv_snr) 'avgpvsnr', avg(a.io_snr) 'avgiosnr', std(a.pv_snr) 'stdpvsnr', std(a.io_snr) 'stdiosnr', min(a.pv_snr) 'minpvsnr', min(a.io_snr) 'miniosnr', max(a.pv_snr) 'maxpvsnr', max(a.io_snr) 'maxiosnr', min(a.motion_rsq) 'minmotion', max(a.motion_rsq) 'maxmotion', avg(a.motion_rsq) 'avgmotion', std(a.motion_rsq) 'stdmotion' FROM mr_qa a left join mr_series b on a.mrseries_id = b.mrseries_id where a.io_snr > 0 group by b.series_sequencename";
			$result2 = MySQLiQuery($sqlstring2,__FILE__,__LINE__);
			while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
				$sequence = $row2['series_sequencename'];
				$pstats[$sequence]['avgpvsnr'] = $row2['avgpvsnr'];
				$pstats[$sequence]['stdpvsnr'] = $row2['stdpvsnr'];
				$pstats[$sequence]['minpvsnr'] = $row2['minpvsnr'];
				$pstats[$sequence]['maxpvsnr'] = $row2['maxpvsnr'];
				$pstats[$sequence]['avgiosnr'] = $row2['avgiosnr'];
				$pstats[$sequence]['stdiosnr'] = $row2['stdiosnr'];
				$pstats[$sequence]['miniosnr'] = $row2['miniosnr'];
				$pstats[$sequence]['maxiosnr'] = $row2['maxiosnr'];
				$pstats[$sequence]['avgmotion'] = $row2['avgmotion'];
				$pstats[$sequence]['stdmotion'] = $row2['stdmotion'];
				$pstats[$sequence]['minmotion'] = $row2['minmotion'];
				$pstats[$sequence]['maxmotion'] = $row2['maxmotion'];
	
				if ($row2['stdiosnr'] != 0) {
					$pstats[$sequence]['maxstdiosnr'] = ($row2['avgiosnr'] - $row2['miniosnr'])/$row2['stdiosnr'];
				} else { $pstats[$sequence]['maxstdiosnr'] = 0; }
				if ($row2['stdpvsnr'] != 0) {
					$pstats[$sequence]['maxstdpvsnr'] = ($row2['avgpvsnr'] - $row2['minpvsnr'])/$row2['stdpvsnr'];
				} else { $pstats[$sequence]['maxstdpvsnr'] = 0; }
				if ($row2['stdmotion'] != 0) {
					$pstats[$sequence]['maxstdmotion'] = ($row2['avgmotion'] - $row2['minmotion'])/$row2['stdmotion'];
				} else { $pstats[$sequence]['maxstdmotion'] = 0; }
			}
		}
		
		/* get a list of previously downloaded series and their dates */
		$sqlstring3 = "select req_seriesid, req_completedate, req_destinationtype from data_requests where req_username = '" . $_SESSION['username'] ."' and req_modality = '$s_studymodality'";
		$result3 = MySQLiQuery($sqlstring3,__FILE__,__LINE__);
		while ($row3 = mysqli_fetch_array($result3, MYSQLI_ASSOC)) {
			$req_seriesid = $row3['req_seriesid'];
			$downloadhistory[$req_seriesid]['date'] = $row3['req_completedate'];
			$downloadhistory[$req_seriesid]['dest'] = $row3['req_destinationtype'];
		}
		
		?>
		<? if ($s_resultoutput == "table") { ?>
		<table width="100%" class="searchresultssheet">
		<? } else { ?>
		<table width="100%" class="ui small very compact very basic selectable table">
		<? } ?>
			<script type="text/javascript">
			$(document).ready(function() {
				$("#seriesall").click(function() {
					var checked_status = this.checked;
					$(".allseries").find("input[type='checkbox']").each(function() {
						this.checked = checked_status;
					});
				});
			});
			</script>
		<?
		$projectids = array();
		$projectnames = array();

		/* get the users id */
		$sqlstringC = "select user_id from users where username = '" . $_SESSION['username'] ."'";
		$resultC = MySQLiQuery($sqlstringC,__FILE__,__LINE__);
		$rowC = mysqli_fetch_array($resultC, MYSQLI_ASSOC);
		$userid = $rowC['user_id'];
				
		/* check to see which projects this user has access to view */
		$sqlstringC = "select a.project_id 'projectid', b.project_name 'projectname' from user_project a left join projects b on a.project_id = b.project_id where a.user_id = '$userid' and (a.view_data = 1 or a.view_phi = 1)";
		//print "$sqlstringC<br>";
		$resultC = MySQLiQuery($sqlstringC,__FILE__,__LINE__);
		while ($rowC = mysqli_fetch_array($resultC, MYSQLI_ASSOC)) {
			$projectids[] = $rowC['projectid'];
		}
		
		/* tell the user if there are results for projects they don't have access to */
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$projectid = $row['project_id'];
			$projectname = $row['project_name'];
			$studyid = $row['study_id'];
			$subjectid = $row['subject_id'];
			$uid = $row['uid'];

			/* ... and a unique list of SubjectIDs */
			if ((!isset($subjectids)) || (!in_array($subjectid, $subjectids))) {
				$subjectids[] = $subjectid;
			}
		}
		
		/* if a project is selected, get a list of the display IDs (the primary project ID) to be used instead of the UID */
		if (($s_projectids != "") && ($s_projectids != "all")) {
			foreach ($subjectids as $subjid) {
				foreach ($s_projectids as $projectid) {
					if ($projectid != "") {
						$displayids[$subjid] = GetPrimaryProjectID($subjid, $projectid);
					}
				}
			}
		}

		/* create some variables to store info about the restuls */
		$foundprojectids = array();
		
		/* ----- loop through the results and display them ----- */
		mysqli_data_seek($result,0); /* rewind the record pointer */
		$laststudy_id = "";
		$headeradded = 0;
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {

			$project_id = $row['project_id'];
			/* if the user doesn't have view access to this project, skip to the next record */
			if (($projectids == null) || (!in_array($project_id, $projectids))) {
				continue;
			}
			$enrollment_id = $row['enrollment_id'];
			$project_id = $row['project_id'];
			$project_name = $row['project_name'];
			$project_costcenter = $row['project_costcenter'];
			$name = $row['name'];
			$birthdate = $row['birthdate'];
			$gender = $row['gender'];
			$uid = $row['uid'];
			$subject_id = $row['subject_id'];
			$study_id = $row['study_id'];
			$study_num = $row['study_num'];
			$study_desc = $row['study_desc'];
			$study_type = $row['study_type'];
			$study_height = $row['study_height'];
			$study_weight = $row['study_weight'];
			$study_alternateid = $row['study_alternateid'];
			$study_modality = strtolower($row['study_modality']);
			$study_datetime = $row['study_datetime'];
			$study_ageatscan = $row['study_ageatscan'];
			$study_type = $row['study_type'];
			$study_operator = $row['study_operator'];
			$study_performingphysician = $row['study_performingphysician'];
			$study_site = $row['study_site'];
			$study_institution = $row['study_institution'];
			$enrollsubgroup = $row['enroll_subgroup'];

			/* keep a list of projects to which this result belongs */
			$foundprojectids[$project_id] = "";
			
			/* determine the displayID - in case the user wants to see the project specific IDs instead */
			$displayid = $uid;
			$displayidcolor = "";
			if (($s_projectids != "") && ($s_projectids != "all")) {
				if ($displayids[$subject_id] != "") {
					$displayid = $displayids[$subject_id];
					$displayidcolor = "";
				}
				else {
					$displayidcolor = "red";
				}
			}
			
			/* get list of alternate subject UIDs */
			$altuids = GetAlternateUIDs($subject_id,'');
			if (count($altuids) > 0) {
				$altuidlist = implode2(",",$altuids);
			}
			else {
				$altuidlist = "(none)";
			}
			
			/* calculate the BMI */
			$study_bmi = GetBMI($study_height, $study_weight);

			$newstudyid = $uid . $study_num;

			/* calculate age at scan */
			list($studyAge, $calcStudyAge) = GetStudySearchResultsAge($birthdate, $study_ageatscan, $study_datetime);

			/* fix some fields */
			$name = GetFixedName($name);
			
			$study_desc = str_replace("^"," ",$study_desc);
			if (($s_resultoutput == "study") || ($s_resultoutput == "export")) {
				$study_datetime = date("M j, Y g:ia",strtotime($study_datetime));
			}
			else {
				$study_datetime = date("Y-m-d H:i",strtotime($study_datetime));
			}

			/* gather series specific info based on modality */
			if ($study_modality == "mr") {
				$series_id = $row['mrseries_id'];
				$series_datetime = $row['series_datetime'];
				$series_desc = $row['series_desc'];
				$series_protocol = $row['series_protocol'];
				$series_altdesc = $row['series_altdesc'];
				$sequence = $row['series_sequencename'];
				$series_num = $row['series_num'];
				$series_tr = $row['series_tr'];
				$series_spacingx = $row['series_spacingx'];
				$series_spacingy = $row['series_spacingy'];
				$series_spacingz = $row['series_spacingz'];
				$series_fieldstrength = $row['series_fieldstrength'];
				$series_notes = $row['series_notes'];
				$imagetype = $row['image_type'];
				$imagecomments = $row['image_comments'];
				$img_rows = $row['img_rows'];
				$img_cols = $row['img_cols'];
				$img_slices = $row['img_slices'];
				$dimn = $row['dimN'];
				$dimx = $row['dimX'];
				$dimy = $row['dimY'];
				$dimz = $row['dimZ'];
				$dimt = $row['dimT'];
				$bold_reps = $row['bold_reps'];
				$numfiles = $row['numfiles'];
				$series_size = $row['series_size'];
				$numfiles_beh = $row['numfiles_beh'];
				$beh_size = $row['beh_size'];
				$series_status = $row['series_status'];
				$is_derived = $row['is_derived'];
				$move_minx = $row['move_minx'];
				$move_miny = $row['move_miny'];
				$move_minz = $row['move_minz'];
				$move_maxx = $row['move_maxx'];
				$move_maxy = $row['move_maxy'];
				$move_maxz = $row['move_maxz'];
				$rot_maxp = $row['rot_maxp'];
				$rot_maxr = $row['rot_maxr'];
				$rot_maxy = $row['rot_maxy'];
				$rot_minp = $row['rot_minp'];
				$rot_minr = $row['rot_minr'];
				$rot_miny = $row['rot_miny'];
				$iosnr = $row['io_snr'];
				$pvsnr = $row['pv_snr'];
				$motion_rsq = $row['motion_rsq'];
				
				$thumbpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/thumb.png";
				$gifthumbpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/thumb.gif";
				
				$series_datetime = date("g:ia",strtotime($series_datetime));
				$series_size = HumanReadableFilesize($series_size);
				$beh_size = HumanReadableFilesize($beh_size);
				
				if (($sequence == "epfid2d1_64") && ($numfiles_beh < 1)) { $behcolor = "red"; } else { $behcolor = ""; }
				/* format the colors for realignment and SNR */
				$rangex = abs($move_minx) + abs($move_maxx);
				$rangey = abs($move_miny) + abs($move_maxy);
				$rangez = abs($move_minz) + abs($move_maxz);
				$rangePitch = abs($rot_minp) + abs($rot_maxp);
				$rangeRoll = abs($rot_minr) + abs($rot_maxr);
				$rangeYaw = abs($rot_miny) + abs($rot_maxy);
				
				/* calculate color based on voxel size... red (100) means more than 1 voxel displacement in that direction */
				if ($series_spacingx > 0) { $xindex = round(($rangex/$series_spacingx)*100); if ($xindex > 100) { $xindex = 100; } }
				if ($series_spacingy > 0) { $yindex = round(($rangey/$series_spacingy)*100); if ($yindex > 100) { $yindex = 100; } }
				if ($series_spacingz > 0) { $zindex = round(($rangez/$series_spacingz)*100); if ($zindex > 100) { $zindex = 100; } }

				/* get standard deviations from the mean for SNR */
				if ($pstats[$sequence]['stdiosnr'] != 0) {
					if ($iosnr > $pstats[$sequence]['avgiosnr']) {
						$stdsiosnr = 0;
					}
					else {
						$stdsiosnr = (($iosnr - $pstats[$sequence]['avgiosnr'])/$pstats[$sequence]['stdiosnr']);
					}
				}
				if ($pstats[$sequence]['stdpvsnr'] != 0) {
					if ($pvsnr > $pstats[$sequence]['avgpvsnr']) {
						$stdspvsnr = 0;
					}
					else {
						$stdspvsnr = (($pvsnr - $pstats[$sequence]['avgpvsnr'])/$pstats[$sequence]['stdpvsnr']);
					}
				}
				if ($pstats[$sequence]['stdmotion'] != 0) {
					if ($motion_rsq > $pstats[$sequence]['avgmotion']) {
						$stdsmotion = 0;
					}
					else {
						$stdsmotion = (($motion_rsq - $pstats[$sequence]['avgmotion'])/$pstats[$sequence]['stdmotion']);
					}
				}
				
				if ($pstats[$sequence]['maxstdpvsnr'] == 0) { $pvindex = 100; }
				else { $pvindex = round(($stdspvsnr/$pstats[$sequence]['maxstdpvsnr'])*100); }
				$pvindex = 100 + $pvindex;
				if ($pvindex > 100) { $pvindex = 100; }
				
				if ($pstats[$sequence]['maxstdiosnr'] == 0) { $ioindex = 100; }
				else { $ioindex = round(($stdsiosnr/$pstats[$sequence]['maxstdiosnr'])*100); }
				$ioindex = 100 + $ioindex;
				if ($ioindex > 100) { $ioindex = 100; }
				
				if ($pstats[$sequence]['maxstdmotion'] == 0) { $motionindex = 100; }
				else { $motionindex = round(($stdsmotion/$pstats[$sequence]['maxstdmotion'])*100); }
				$motionindex = 100 + $motionindex;
				if ($motionindex > 100) { $motionindex = 100; }
				
				$maxpvsnrcolor = $colors[100-$pvindex];
				$maxiosnrcolor = $colors[100-$ioindex];
				$maxmotioncolor = $colors[100-$motionindex];
				if ($pvsnr <= 0.0001) { $pvsnr = "-"; $maxpvsnrcolor = ""; }
				else { $pvsnr = number_format($pvsnr,2); }
				if ($iosnr <= 0.0001) { $iosnr = "-"; $maxiosnrcolor = ""; }
				else { $iosnr = number_format($iosnr,2); }
				if ($motion_rsq <= 0.0001) { $motion_rsq = "-"; $maxmotioncolor = ""; }
				else { $motion_rsq = number_format($motion_rsq,5); }
				
				/* setup movement colors */
				$maxxcolor = $colors[$xindex];
				$maxycolor = $colors[$yindex];
				$maxzcolor = $colors[$zindex];
				if ($rangex <= 0.0001) { $rangex = "-"; $maxxcolor = ""; }
				else { $rangex = number_format($rangex,2); }
				if ($rangey <= 0.0001) { $rangey = "-"; $maxycolor = ""; }
				else { $rangey = number_format($rangey,2); }
				if ($rangez <= 0.0001) { $rangez = "-"; $maxzcolor = ""; }
				else { $rangez = number_format($rangez,2); }
				
				/* check if this is real data, or unusable data based on the ratings, and get rating counts */
				$isbadseries = false;
				$istestseries = false;
				$ratingcount2 = '';
				$hasratings = false;
				$rowcolor = '';
				$ratingavg = '';
				if (isset($ratings)) {
					foreach ($ratings as $key => $ratingarray) {
						if ($key == $series_id) {
							$hasratings = true;
							if (in_array(5,$ratingarray)) {
								$isbadseries = true;
								//echo "IsBadSeries is true";
							}
							if (in_array(6,$ratingarray)) {
								$istestseries = true;
							}
							$ratingcount2 = count($ratingarray);
							$ratingavg = array_sum($ratingarray) / count($ratingarray);
							break;
						}
					}
				}
				if ($isbadseries) { $rowcolor = "red"; }
				if ($istestseries) { $rowcolor = "#AAAAAA"; }
			}
			else {
				$series_id = $row[$study_modality . 'series_id'];
				$series_num = $row['series_num'];
				$series_datetime = $row['series_datetime'];
				$series_protocol = $row['series_protocol'];
				$series_numfiles = $row['series_numfiles'];
				$series_size = $row['series_size'];
				$series_notes = $row['series_notes'];
				
				$series_datetime = date("g:ia",strtotime($series_datetime));
				if ($series_numfiles < 1) { $series_numfiles = "-"; }
				if ($series_size > 1) { $series_size = HumanReadableFilesize($series_size); } else { $series_size = "-"; }
			}
			
			/* check if this has been downloaded before */
			if (array_key_exists($series_id, $downloadhistory)) {
				$downloadmsg = "Series downloaded on [" . $downloadhistory[$series_id]['date'] . "] to [" . $downloadhistory[$series_id]['dest'] . "]";
			}
			else {
				$downloadmsg = "";
			}
			
			/* display study header if study */
			if ($study_id != $laststudy_id) {
				if (($s_resultoutput == "study") || ($s_resultoutput == "export")) {
					/* display study header */
					?>
					<script type="text/javascript">
					$(document).ready(function() {
						$("#study<?=$study_id?>").click(function() {
							var checked_status = this.checked;
							$(".tr<?=$study_id?>").find("input[type='checkbox']").each(function() {
								this.checked = checked_status;
							});
						});
					});
					</script>
					<tr>
						<td colspan="19" style="padding: 0px; background-color: #fff;">
							<br>
							<table class="ui very compact yellow celled selectable table" width="100%">
								<tr>
									<td class="one wide tertiary yellow segment">
										<h4 class="header" style="color: #222"><?=$name?></h4>
									</td>
									<td class="two wide tertiary yellow segment middle aligned tt">
										<a href="subjects.php?id=<?=$subject_id?>" style="color: <?=$displayidcolor?>; font-weight: bold" class="ui compact blue button"><?=$displayid?> &nbsp; <i class="external alternate icon"></i></a>
									</td>
									<td class="two wide yellow"><h4 class="header tt" style="color: #222">
										<?
										if (strlen($altuidlist) > 60) {
											?><span title="<?=$altuidlist?>"><?=substr($altuidlist,0,60)?>...</span><?
										}
										else {
											echo "$altuidlist";
										}
									?></h4></td>
									<td class="two wide tertiary yellow segment">
										<a href="studies.php?id=<?=$study_id?>" class="ui large image blue label"><?=$uid?><div class="detail"><?=$study_num?></div></a>
									</td>
									<td class="two wide tertiary yellow segment"><h4 class="header" style="color: #222"><?=$project_name?> (<?=$project_costcenter?>)</h4></td>
									<td class="one wide tertiary yellow segment"><span style="color: #222; font-weight: bold"><?=$study_datetime?></span></td>
									<td class="one wide tertiary yellow segment"><h4 class="header" style="color: #222"><?=$enrollsubgroup?></h4></td>
									<td class="one wide tertiary yellow segment"><h4 class="header" style="color: #222"><?=number_format($studyAge,1)?>Y , <?=number_format($calcStudyAge,1)?>Y</h4></td>
									<td class="one wide tertiary yellow segment"><h4 class="header" style="color: #222"><?=$gender?></h4></td>
									<td class="one wide tertiary yellow segment"><h4 class="header tt" style="color: #222"><?=$study_alternateid?></h4></td>
									<td class="one wide tertiary yellow segment"><h4 class="header" style="color: #222"><?=$study_type?></h4></td>
									<td class="one wide tertiary yellow segment"><h4 class="header" style="color: #222"><?=$study_site?></h4></td>
								</tr>
							</table>
						</td>
					</tr>
					<?
				}
				/* display the series header only once */
				if ($study_modality == "mr") {
					if (($laststudy_id == "") && ($s_resultoutput != "study") && ($s_resultoutput != "export") && ($s_resultoutput != "csv")) {
						DisplayMRSeriesHeader($s_resultoutput, $measurenames);
					}
					if (($s_resultoutput == "study") || ($s_resultoutput == "export")) {
						DisplayMRStudyHeader($study_id, true, $measurenames);
					}
					if ($s_resultoutput == "csv") {
						if (!$headeradded) {
							$header = DisplayMRStudyHeader($study_id, false, $measurenames);
							$csv .= "$header";
							if (count($measurenames) > 0) {
								foreach ($measurenames as $measurename) {
									$csv .= ",$measurename";
								}
							}
							$csv .= "\n";
						}
						$headeradded = 1;
					}
				}
				else {
					if (($laststudy_id == "") && ($s_resultoutput != "study") && ($s_resultoutput != "export")) {
						DisplayGenericSeriesHeader($s_resultoutput);
					}
					if (($s_resultoutput == "study") || ($s_resultoutput == "export")) {
						DisplayGenericStudyHeader($study_id);
					}
				}
			}
			/* set the css class for the rows */
			if (($s_resultoutput == "series") || ($s_resultoutput == "table") || ($s_resultoutput == "operations")) {
				$rowstyle = "seriesrowsmall";
			}
			else {
				$rowstyle = "seriesrow";
			}
			/* and then display the series... */
			if ($study_modality == "mr") {
				if ($s_resultoutput == "csv") {
					if ($s_usealtseriesdesc) {
						//$csv .= "$series_num, $series_altdesc, $uid, $gender, $ageatscan, " . implode2(' ',$altuids) . ", $newstudyid, $study_alternateid, $study_type, $study_num, $study_datetime, $study_type, $project_name($project_costcenter), $study_height, $study_weight, $study_bmi, $series_datetime, $move_minx, $move_miny, $move_minz, $move_maxx, $move_maxy, $move_maxz, $rangex, $rangey, $rangez, $rangePitch, $rangeRoll, $rangeYaw, $pvsnr, $iosnr, $img_cols, $img_rows, $numfiles, $series_size, $sequence, $series_tr, $numfiles_beh, $beh_size";

						$csv .= "$uid, $series_num, $series_altdesc, $series_protocol, $gender, $studyAge, $calcStudyAge, " . implode2(' ',$altuids) . ", $newstudyid, $study_alternateid, $study_num, $study_datetime, $study_type, $project_name($project_costcenter), $study_height, $study_weight, $study_bmi, $series_datetime, $move_minx, $move_miny, $move_minz, $move_maxx, $move_maxy, $move_maxz, $rangex, $rangey, $rangez, $rangePitch, $rangeRoll, $rangeYaw, $pvsnr, $iosnr, $dimn, $dimx, $dimy, $dimz, $dimt, $numfiles, $series_size, $sequence, $imagetype, $imagecomment, $series_tr, $numfiles_beh, $beh_size";
					}
					else {
						//$csv .= "$series_num, $series_desc, $uid, $gender, $ageatscan, " . implode2(' ',$altuids) . ", $newstudyid, $study_alternateid, $study_type, $study_num, $study_datetime, $study_type, $project_name($project_costcenter), $study_height, $study_weight, $study_bmi, $series_datetime, $move_minx, $move_miny, $move_minz, $move_maxx, $move_maxy, $move_maxz, $rangex, $rangey, $rangez, $rangePitch, $rangeRoll, $rangeYaw, $pvsnr, $iosnr, $img_cols, $img_rows, $numfiles, $series_size, $sequence, $series_tr, $numfiles_beh, $beh_size";
						
						$csv .= "$uid, $series_num, $series_desc, $series_protocol, $gender, $studyAge, $calcStudyAge, " . implode2(' ',$altuids) . ", $newstudyid, $study_alternateid, $study_num, $study_datetime, $study_type, $project_name($project_costcenter), $study_height, $study_weight, $study_bmi, $series_datetime, $move_minx, $move_miny, $move_minz, $move_maxx, $move_maxy, $move_maxz, $rangex, $rangey, $rangez, $rangePitch, $rangeRoll, $rangeYaw, $pvsnr, $iosnr, $dimn, $dimx, $dimy, $dimz, $dimt, $numfiles, $series_size, $sequence, $imagetype, $imagecomment, $series_tr, $numfiles_beh, $beh_size";
					}
					if (count($measurenames) > 0) {
						foreach ($measurenames as $measure) {
							$csv .= "," . $measuredata[$subject_id][$measure]['value'];
						}
					}
					$csv .= "\n";
				}
				else {
					//if ($series_num - $lastseriesnum > 1) {
					//	$firstmissing = $lastseriesnum+1;
					//	$lastmissing = $series_num-1;
					//	if ($firstmissing == $lastmissing) {
					//		$missingmsg = $firstmissing;
					//	}
					//	else {
					//		$missingmsg = "$firstmissing - $lastmissing";
					//	}
						?>
						<!--<tr>
							<td colspan="24" align="center" style="border-top: solid 1px #FF7F7F; border-bottom: solid 1px #FF7F7F; padding:3px; font-size:8pt">Non-consecutive series numbers in search results. Probably normal. Missing series <?=$missingmsg?></td>
						</tr>-->
						<?
					//}
					
				?>
					<tr class="tr<?=$study_id?> allseries" style="color: <?=$rowcolor?>; white-space: nowrap">
						<? if ($s_resultoutput != "table") { ?>
							<td class="<?=$rowstyle?>">
								<input type="checkbox" name="seriesid[]" value="<?=$series_id?>">
							</td>
						<? } ?>
						<td class="<?=$rowstyle?>"><b><?=$series_num?></b><? if ($downloadmsg != "") { ?>&nbsp;&nbsp;<img src="images/downloaded.png" title="<?=$downloadmsg?>"><?} ?>
						</td>
						<td class="<?=$rowstyle?>">
							<span><? if ($s_usealtseriesdesc) { echo $series_altdesc; } else { echo $series_desc; } ?></span></a>
							&nbsp;<a href="preview.php?image=<?=$thumbpath?>" class="preview"><i class="image icon"></i></a>
						</td>
						<? if (($s_resultoutput == "series") || ($s_resultoutput == "table") || ($s_resultoutput == "operations")) { ?>
							<td class="<?=$rowstyle?>"><a href="subjects.php?id=<?=$subject_id?>"><tt style="color: <?=$displayidcolor?>;"><?=$displayid?></tt></a></td>
							<td class="<?=$rowstyle?>"><?=$gender?></td>
							<td class="<?=$rowstyle?>"><?=number_format($ageatscan,1)?>Y</td>
							<td class="<?=$rowstyle?>"><a href="subjects.php?id=<?=$subject_id?>"><tt><? if (count($altuids) > 0) { echo implode2(', ',$altuids); } ?></tt></a></td>
							<td class="<?=$rowstyle?>"><a href="studies.php?id=<?=$study_id?>"><?=$newstudyid?></a></td>
							<td class="<?=$rowstyle?>"><a href="studies.php?id=<?=$study_id?>"><?=$study_alternateid?></a></td>
							<!--<td class="<?=$rowstyle?>"><a href="studies.php?id=<?=$study_id?>"><?=$study_type?></a></td>-->
							<td class="<?=$rowstyle?>"><a href="studies.php?id=<?=$study_id?>"><?=$study_num?></a></td>
							<td class="<?=$rowstyle?>"><?=$study_site?></td>
							<td class="<?=$rowstyle?>"><?=$study_datetime?></td>
							<td class="<?=$rowstyle?>"><?=$series_datetime?></td>
						<? } else { ?>
							<td class="<?=$rowstyle?>"><?=$series_datetime?></td>
						<? } ?>
						<td class="<?=$rowstyle?>" align="right" style="background-color: <?=$maxxcolor?>;"><?=$rangex;?></td>
						<td class="<?=$rowstyle?>" align="right" style="background-color: <?=$maxycolor?>;"><?=$rangey;?></td>
						<td class="<?=$rowstyle?>" align="right" style="background-color: <?=$maxzcolor?>;"><?=$rangez;?></td>
						<? if ($s_resultoutput != "table") { ?>
						<td class="<?=$rowstyle?>" style="padding: 0px 5px;">
							<a href="JavaScript:newPopup('mrseriesqa.php?id=<?=$series_id?>');"><i class="chart line icon"></i></a>
						</td>
						<td class="<?=$rowstyle?>" style="padding: 0px 5px;">
							<a href="JavaScript:newPopup('ratings.php?id=<?=$series_id?>&type=series&modality=mr');">
							<?
								if ($hasratings) {
									?><i class="red file outline icon" title="View/edit ratings"></i><?
								} else {
									?><i class="grey file outline icon" title="View/edit ratings"></i><?
								}
							?>
							</a>
							<span style="font-size:7pt" title="Scale of 1 to 5, where<br>1 = good<br>5 = bad"><?=$ratingavg;?></span>
						</td>
						<td class="<?=$rowstyle?>">
							<? if (trim($series_notes) != "") { ?>
							<i class="pencil alternate icon" title="<?=$series_notes?>"></i>
							<? } ?>
						</td>
						<? } ?>
						<td class="<?=$rowstyle?>" align="right" style="background-color: <?=$maxpvsnrcolor?>;">
							<a href="stddevchart.php?h=40&w=450&min=<?=$pstats[$sequence]['minpvsnr']?>&max=<?=$pstats[$sequence]['maxpvsnr']?>&mean=<?=$pstats[$sequence]['avgpvsnr']?>&std=<?=$pstats[$sequence]['stdpvsnr']?>&i=<?=$pvsnr?>&b=yes" class="preview" style="color: black; text-decoration: none"><?=$pvsnr;?></a> 
						</td>
						<td class="<?=$rowstyle?>" align="right" style="background-color: <?=$maxiosnrcolor?>;">
							<a href="stddevchart.php?h=40&w=450&min=<?=$pstats[$sequence]['miniosnr']?>&max=<?=$pstats[$sequence]['maxiosnr']?>&mean=<?=$pstats[$sequence]['avgiosnr']?>&std=<?=$pstats[$sequence]['stdiosnr']?>&i=<?=$iosnr?>&b=yes" class="preview" style="color: black; text-decoration: none"><?=$iosnr;?></a>
						</td>
						<td class="<?=$rowstyle?>" align="right" style="background-color: <?=$maxmotioncolor?>; font-size:8pt">
							<a href="stddevchart.php?h=40&w=450&min=<?=$pstats[$sequence]['minmotion']?>&max=<?=$pstats[$sequence]['maxmotion']?>&mean=<?=$pstats[$sequence]['avgmotion']?>&std=<?=$pstats[$sequence]['stdmotion']?>&i=<?=$motion_rsq?>&b=yes" class="preview" style="color: black; text-decoration: none"><?=$motion_rsq;?></a>
						</td>
						<td class="<?=$rowstyle?>"><?=$img_cols?>&times;<?=$img_rows?></td>
						<td class="<?=$rowstyle?>">
							<?=$numfiles?>
							<?
								if ($s_audit) {
									$files = glob($GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/dicom/*.dcm");
									//print_r($files);
									if (count($files) != $numfiles) { ?><span style="color: white; background-color: red; padding: 1px 5px; font-weight: bold"><?=count($files)?></span> <? }
								}
							?>
						</td>
						<td class="<?=$rowstyle?>"><?=$series_size?></td>
						<td class="<?=$rowstyle?>"><?=$sequence?></td>
						<td class="<?=$rowstyle?>"><?=$series_tr?></td>
						<? if ($s_resultoutput != "table") { ?>
						<td class="<?=$rowstyle?>" bgcolor="<?=$behcolor?>"><?=$numfiles_beh?> <span class="tiny">(<?=$beh_size?>)</span></td>
						<? }
							if (count($measurenames) > 0) {
								foreach ($measurenames as $measure) {
								?>
								<td class="<?=$rowstyle?>"><?=$measuredata[$subject_id][$measure]['value']?></td>
								<?
								}
							}
						?>
					</tr>
					<?
				}
			}
			else {
				?>
				<tr class="tr<?=$study_id?> allseries">
					<? if ($s_resultoutput != "table") { ?>
						<td class="<?=$rowstyle?>"><input type="checkbox" name="seriesid[]" value="<?=$series_id?>"></td>
					<? } ?>
					<td class="<?=$rowstyle?>"><b><?=$series_num?></b></td>
					<td class="<?=$rowstyle?>"><?=$series_protocol;?></td>
					<? if (($s_resultoutput == "series") || ($s_resultoutput == "table") || ($s_resultoutput == "operations")) { ?>
						<td class="<?=$rowstyle?>"><tt><?=$uid?></tt></td>
						<td class="<?=$rowstyle?>"><a href="subjects.php?id=<?=$subject_id?>"><tt><?=implode2(', ',$altuids)?></tt></a></td>
						<td class="<?=$rowstyle?>"><a href="studies.php?id=<?=$study_id?>"><?=$study_num?></a></td>
						<td class="<?=$rowstyle?>"><?=$study_datetime?></td>
						<td class="<?=$rowstyle?>"><?=$series_datetime?></td>
					<? } else { ?>
						<td class="<?=$rowstyle?>"><?=$series_datetime?></td>
					<? } ?>
					<td class="<?=$rowstyle?>"><?=$series_numfiles?></td>
					<td class="<?=$rowstyle?>"><?=$series_size?></td>
					<td class="<?=$rowstyle?>"><?=$series_notes?></td>
				</tr>
				<?
			}

			$laststudy_id = $study_id;
			$lastseriesnum = $series_num;
		}

		/* ---------- generate csv file ---------- */
		if ($s_resultoutput == "csv") {
			$filename = "query" . GenerateRandomString(10) . ".csv";
			file_put_contents("/tmp/" . $filename, $csv);
			?>
			<div width="50%" align="center" style="background-color: #FAF8CC; padding: 5px;">
			Download .csv file <a href="download.php?type=file&filename=<?="/tmp/$filename";?>"><img src="images/download16.png"></a>
			</div>
			<?
		}
		?>
		</table>
		
		<div style="padding-left: 15px">
		<?
			/* ---------- display download/group box ---------- */
			if (($s_resultoutput == "study") || ($s_resultoutput == "series") || ($s_resultoutput == "export")) {
				DisplayDownloadBox($s_studymodality, $s_resultoutput, $foundprojectids);
			}
			elseif ($s_resultoutput == "operations") {
				DisplayFileIOBox();
			}
		?>
		</div>
		<br>
		<br>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplaySearchResultsTable ---------- */
	/* -------------------------------------------- */
	function DisplaySearchResultsTable(&$result, $s) {
		error_reporting(-1);
		ini_set('display_errors', '1');
	
		/* escape all the variables and put them back into meaningful variable names */
		foreach ($s as $key => $value) {
			if (is_scalar($value)) { $$key = mysqli_real_escape_string($GLOBALS['linki'], $s[$key]); }
			else { $$key = $s[$key]; }
		}

		/* ---------------- regular search --------------- */
		$s_studymodality = strtolower($s_studymodality);
		$sqlstring3 = "select data_id, rating_value from ratings where rating_type = 'series' and data_modality = '$s_studymodality'";
		$result3 = MySQLiQuery($sqlstring3,__FILE__,__LINE__);
		while ($row3 = mysqli_fetch_array($result3, MYSQLI_ASSOC)) {
			$ratingseriesid = $row3['data_id'];
			$ratings[$ratingseriesid][] = $row3['rating_value'];
		}
		
		/* if its MRI, get the basic QC data */
		if (strtolower($s_studymodality) == "mr") {
			$pstats = GetMRSequenceQCList();
		}
		
		$projectids = array();

		/* get the user's id */
		$sqlstringC = "select user_id from users where username = '" . $_SESSION['username'] ."'";
		$resultC = MySQLiQuery($sqlstringC,__FILE__,__LINE__);
		$rowC = mysqli_fetch_array($resultC, MYSQLI_ASSOC);
		$userid = $rowC['user_id'];
				
		/* check to see which projects this user has access to view */
		$sqlstringC = "select a.project_id 'projectid', b.project_name 'projectname' from user_project a left join projects b on a.project_id = b.project_id where a.user_id = '$userid' and (a.view_data = 1 or a.view_phi = 1)";
		//print "$sqlstringC<br>";
		$resultC = MySQLiQuery($sqlstringC,__FILE__,__LINE__);
		while ($rowC = mysqli_fetch_array($resultC, MYSQLI_ASSOC)) {
			$projectids[] = $rowC['projectid'];
		}
		
		/* tell the user if there are results for projects they don't have access to */
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$subjectid = $row['subject_id'];
			
			/* ... and a unique list of SubjectIDs */
			if ((!isset($subjectids)) || (!in_array($subjectid, $subjectids))) {
				$subjectids[] = $subjectid;
			}
		}
		
		/* if a project is selected, get a list of the display IDs (the primary project ID) to be used instead of the UID */
		if (($s_projectids != "") && ($s_projectids != "all")) {
			foreach ($subjectids as $subjid) {
				foreach ($s_projectids as $projectid) {
					if ($projectid != "") {
						$displayids[$subjid] = GetPrimaryProjectID($subjid, $projectid);
					}
				}
			}
		}
		
		if ($s_resultoutput != "csv") {
		?>
		<table class="ui very compact small celled selectable table">
			<thead>
				<th class="ui inverted attached header">SeriesNum</th>
				<th class="ui inverted attached header">Protocol</th>
				<th class="ui inverted attached header">UID</th>
				<th class="ui inverted attached header">Sex</th>
				<th class="ui inverted attached header">StudyAge</th>
				<th class="ui inverted attached header">CalcStudyAge</th>
				<th class="ui inverted attached header">Project</th>
				<th class="ui inverted attached header">StudyDesc</th>
				<th class="ui inverted attached header">Height (cm)</th>
				<th class="ui inverted attached header">Weight (kg)</th>
				<th class="ui inverted attached header">AltUIDs</th>
				<th class="ui inverted attached header">StudyID</th>
				<th class="ui inverted attached header">AltStudyID</th>
				<th class="ui inverted attached header">StudyNum</th>
				<th class="ui inverted attached header">Site</th>
				<th class="ui inverted attached header">Visit</th>
				<th class="ui inverted attached header">StudyDate</th>
				<th class="ui inverted attached header">SeriesTime</th>
				<th class="ui inverted attached header">MotionX</th>
				<th class="ui inverted attached header">MotionY</th>
				<th class="ui inverted attached header">MotionZ</th>
				<th class="ui inverted attached header">Rating</th>
				<th class="ui inverted attached header">PV_SNR</th>
				<th class="ui inverted attached header">IO_SNR</th>
				<th class="ui inverted attached header">MotionR^2</th>
				<th class="ui inverted attached header">SizeX</th>
				<th class="ui inverted attached header">SizeY</th>
				<th class="ui inverted attached header">NumFiles</th>
				<th class="ui inverted attached header">Size</th>
				<th class="ui inverted attached header">Sequence</th>
				<th class="ui inverted attached header">TR</th>
				<th class="ui inverted attached header">NumBeh</th>
				<th class="ui inverted attached header">BehSize</th>
			</thead>
		<?
		}
		$csv = "SeriesNum,Protocol,UID,Sex,StudyAge,CalcStudyAge,Project,StudyDesc,Height_cm,Weight_kg,AltUIDs,StudyID,AltStudyID,StudyNum,Site,Visit,StudyDate,SeriesTime,MotionX,MotionY,MotionZ,Rating,PV_SNR,IO_SNR,MotionR^2,SizeX,SizeY,NumFiles,Size,Sequence,TR,NumBeh,BehSize\n";
		
		/* create some variables to store info about the restuls */
		$foundprojectids = array();
		
		/* ----- loop through the results and display them ----- */
		mysqli_data_seek($result,0); /* rewind the record pointer */
		$laststudy_id = "";
		$headeradded = 0;
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {

			$project_id = $row['project_id'];
			/* if the user doesn't have view access to this project, skip to the next record */
			if (($projectids == null) || (!in_array($project_id, $projectids))) {
				continue;
			}
			$enrollment_id = $row['enrollment_id'];
			$project_id = $row['project_id'];
			$project_name = $row['project_name'];
			$project_costcenter = $row['project_costcenter'];
			$name = $row['name'];
			$birthdate = $row['birthdate'];
			$gender = $row['gender'];
			$uid = $row['uid'];
			$subject_id = $row['subject_id'];
			$study_id = $row['study_id'];
			$studynum = $row['study_num'];
			$study_desc = $row['study_desc'];
			$study_type = $row['study_type'];
			$study_height = $row['study_height'];
			$study_weight = $row['study_weight'];
			$study_alternateid = $row['study_alternateid'];
			$study_modality = strtolower($row['study_modality']);
			$study_datetime = $row['study_datetime'];
			$study_ageatscan = $row['study_ageatscan'];
			$study_type = $row['study_type'];
			$study_operator = $row['study_operator'];
			$study_performingphysician = $row['study_performingphysician'];
			$study_site = $row['study_site'];
			$study_institution = $row['study_institution'];
			$enrollsubgroup = $row['enroll_subgroup'];

			/* keep a list of projects to which this result belongs */
			$foundprojectids[$project_id] = "";
			
			/* determine the displayID - in case the user wants to see the project specific IDs instead */
			$displayid = $uid;
			$displayidcolor = "";
			if (($s_projectids != "") && ($s_projectids != "all")) {
				if ($displayids[$subject_id] != "") {
					$displayid = $displayids[$subject_id];
					$displayidcolor = "";
				}
				else {
					$displayidcolor = "red";
				}
			}
			
			/* get list of alternate subject UIDs */
			$altuids = GetAlternateUIDs($subject_id,'');
			if (count($altuids) > 0) {
				$altuidlist = implode2(",",$altuids);
			}
			else {
				$altuidlist = "(none)";
			}
			
			/* fix some fields */
			$study_bmi = GetBMI($study_height, $study_weight);
			$newstudyid = $uid . $study_num;
			list($studyAge, $calcStudyAge) = GetStudySearchResultsAge($birthdate, $study_ageatscan, $study_datetime);
			$name = GetFixedName($name);
			$study_desc = str_replace("^"," ",$study_desc);
			$study_datetime = GetStudyDateTime($s_resultoutput, $study_datetime);

			/* gather series specific info based on modality */
			if ($study_modality == "mr") {
				$series_id = $row['mrseries_id'];
				$series_datetime = $row['series_datetime'];
				$series_desc = $row['series_desc'];
				$series_protocol = $row['series_protocol'];
				$series_altdesc = $row['series_altdesc'];
				$sequence = $row['series_sequencename'];
				$series_num = $row['series_num'];
				$series_tr = $row['series_tr'];
				$spacing['x'] = $row['series_spacingx'];
				$spacing['y'] = $row['series_spacingy'];
				$spacing['z'] = $row['series_spacingz'];
				$series_fieldstrength = $row['series_fieldstrength'];
				$series_notes = $row['series_notes'];
				$imagetype = $row['image_type'];
				$imagecomments = $row['image_comments'];
				$img_rows = $row['img_rows'];
				$img_cols = $row['img_cols'];
				$img_slices = $row['img_slices'];
				$dimn = $row['dimN'];
				$dimx = $row['dimX'];
				$dimy = $row['dimY'];
				$dimz = $row['dimZ'];
				$dimt = $row['dimT'];
				$bold_reps = $row['bold_reps'];
				$numfiles = $row['numfiles'];
				$series_size = $row['series_size'];
				$numfiles_beh = $row['numfiles_beh'];
				$beh_size = $row['beh_size'];
				$series_status = $row['series_status'];
				$is_derived = $row['is_derived'];
				$min['movex'] = $row['move_minx'];
				$min['movey'] = $row['move_miny'];
				$min['movez'] = $row['move_minz'];
				$max['movex'] = $row['move_maxx'];
				$max['movey'] = $row['move_maxy'];
				$max['movez'] = $row['move_maxz'];
				$max['rotp'] = $row['rot_maxp'];
				$max['rotr'] = $row['rot_maxr'];
				$max['roty'] = $row['rot_maxy'];
				$min['rotp'] = $row['rot_minp'];
				$min['rotr'] = $row['rot_minr'];
				$min['roty'] = $row['rot_miny'];
				$iosnr = $row['io_snr'];
				$pvsnr = $row['pv_snr'];
				$motion_rsq = $row['motion_rsq'];
				
				$thumbpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/thumb.png";
				$gifthumbpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/thumb.gif";
				
				$series_datetime = date("g:ia",strtotime($series_datetime));
				//$series_size = HumanReadableFilesize($series_size);
				//$beh_size = HumanReadableFilesize($beh_size);

				if (($sequence == "epfid2d1_64") && ($numfiles_beh < 1)) { $behcolor = "red"; } else { $behcolor = ""; }
				
				list($range, $index, $color, $snrs, $iosnr, $pvsnr) = GetFormattedSeriesQC($pstats, $sequence, $min, $max, $spacing, $iosnr, $pvsnr, $motion_rsq);
				
				/* check if this is real data, or unusable data based on the ratings, and get rating counts */
				$isbadseries = false;
				$istestseries = false;
				$ratingcount2 = '';
				$hasratings = false;
				$rowcolor = '';
				$ratingavg = '';
				if (isset($ratings)) {
					foreach ($ratings as $key => $ratingarray) {
						if ($key == $series_id) {
							$hasratings = true;
							if (in_array(5,$ratingarray)) {
								$isbadseries = true;
								//echo "IsBadSeries is true";
							}
							if (in_array(6,$ratingarray)) {
								$istestseries = true;
							}
							$ratingcount2 = count($ratingarray);
							$ratingavg = array_sum($ratingarray) / count($ratingarray);
							break;
						}
					}
				}
				if ($isbadseries) { $rowcolor = "red"; }
				if ($istestseries) { $rowcolor = "#AAAAAA"; }
			}
			else {
				$series_id = $row[$study_modality . 'series_id'];
				$series_num = $row['series_num'];
				$series_datetime = $row['series_datetime'];
				$series_protocol = $row['series_protocol'];
				$series_numfiles = $row['series_numfiles'];
				$series_size = $row['series_size'];
				$series_notes = $row['series_notes'];
				
				$series_datetime = date("g:ia",strtotime($series_datetime));
				if ($series_numfiles < 1) { $series_numfiles = "-"; }
				//if ($series_size > 1) { $series_size = HumanReadableFilesize($series_size); } else { $series_size = "-"; }
			}
			
			/* and then display the series... */
			if ($s_resultoutput != "csv") {
			?>
			<tr>
				<td><?=$series_num?></td>
				<td><?=$series_desc?></td>
				<td><?=$uid?></td>
				<td><?=$gender?></td>
				<td><?=$studyAge?></td>
				<td><?=$calcStudyAge?></td>
				<td><?=$project_name?></td>
				<td><?=$study_desc?></td>
				<td><?=$study_height?></td>
				<td><?=$study_weight?></td>
				<td><? echo implode2('|',$altuids);?></td>
				<td><?=$uid?><?=$studynum?></td>
				<td><?=$study_alternateid?></td>
				<td><?=$studynum?></td>
				<td><?=$study_site?></td>
				<td><?=$study_type?></td>
				<td><?=$study_datetime?></td>
				<td><?=$series_datetime?></td>
				<td><?=$range['x']?></td>
				<td><?=$range['y']?></td>
				<td><?=$range['z']?></td>
				<td><?=$ratingavg?></td>
				<td><?=$pvsnr?></td>
				<td><?=$iosnr?></td>
				<td><?=$motion_rsq?></td>
				<td><?=$img_cols?></td>
				<td><?=$img_rows?></td>
				<td><?=$numfiles?></td>
				<td><?=$series_size?></td>
				<td><?=$sequence?></td>
				<td><?=$series_tr?></td>
				<td><?=$numfiles_beh?></td>
				<td><?=$beh_size?></td>
			</tr>
			<?
			}
			
			//if ($study_modality == "mr") {
				if ($s_resultoutput == "csv") {

					$csv .= "$series_num,$series_desc,$uid,$gender,$studyAge,$calcStudyAge,$project_name,$study_desc,$study_height,$study_weight," . implode2('|',$altuids) . ",$uid$studynum,$study_alternateid,$studynum,$study_site,$study_type,$study_datetime,$series_datetime," . $range['x'] . "," . $range['y'] . "," . $range['z'] . ",$ratingavg,$pvsnr,$iosnr,$motion_rsq,$img_cols,$img_rows,$numfiles,$series_size,$sequence,$series_tr,$numfiles_beh,$beh_size";
					
					//if ($s_usealtseriesdesc) {
					//	$csv .= "$uid, $series_num, $series_altdesc, $series_protocol, $gender, $studyAge, $calcStudyAge, " . implode2(' ',$altuids) . ", $newstudyid, $study_alternateid, $study_num, $study_datetime, $study_type, $project_name($project_costcenter), $study_height, $study_weight, $study_bmi, $series_datetime, $move_minx, $move_miny, $move_minz, $move_maxx, $move_maxy, $move_maxz, $rangex, $rangey, $rangez, $rangePitch, $rangeRoll, $rangeYaw, $pvsnr, $iosnr, $dimn, $dimx, $dimy, $dimz, $dimt, $numfiles, $series_size, $sequence, $imagetype, $imagecomment, $series_tr, $numfiles_beh, $beh_size";
					//}
					//else {
					//	$csv .= "$uid, $series_num, $series_desc, $series_protocol, $gender, $studyAge, $calcStudyAge, " . implode2(' ',$altuids) . ", $newstudyid, $study_alternateid, $study_num, $study_datetime, $study_type, $project_name($project_costcenter), $study_height, $study_weight, $study_bmi, $series_datetime, $move_minx, $move_miny, $move_minz, $move_maxx, $move_maxy, $move_maxz, $rangex, $rangey, $rangez, $rangePitch, $rangeRoll, $rangeYaw, $pvsnr, $iosnr, $dimn, $dimx, $dimy, $dimz, $dimt, $numfiles, $series_size, $sequence, $imagetype, $imagecomment, $series_tr, $numfiles_beh, $beh_size";
					//}
					
					$csv .= "\n";
				}
			//}

			$laststudy_id = $study_id;
			$lastseriesnum = $series_num;
		}

		/* ---------- generate csv file ---------- */
		if ($s_resultoutput == "csv") {
			$filename = "query" . GenerateRandomString(10) . ".csv";
			file_put_contents("/tmp/" . $filename, $csv);
			?>
			<div class="ui container">
				<div class="ui center aligned segment">
					<a class="ui orange button" href="download.php?type=file&filename=<?="/tmp/$filename";?>"><i class="ui download icon"></i>Download .csv file</a>
				</div>
			</div>
			<?
		}
		?>
		</table>
		<?
	}


	/* -------------------------------------------- */
	/* ------- GetFixedName ----------------------- */
	/* -------------------------------------------- */
	function GetFixedName($name) {
		list($lname, $fname) = explode("^",$name);
		$name = strtoupper(substr($fname,0,1)) . strtoupper(substr($lname,0,1));
		return $name;
	}

	/* -------------------------------------------- */
	/* ------- GetBMI ----------------------------- */
	/* -------------------------------------------- */
	function GetBMI($study_height, $study_weight) {
		$study_bmi = 0;
		
		if (($study_height > 0) && ($study_weight == 0)) {
			$study_bmi = $study_weight / ( $study_height * $study_height);
		}
		
		return $study_bmi;
	}


	/* -------------------------------------------- */
	/* ------- GetStudyDateTime ------------------- */
	/* -------------------------------------------- */
	function GetStudyDateTime($s_resultoutput, $study_datetime) {
		if (($s_resultoutput == "study") || ($s_resultoutput == "export")) {
			$study_datetime = date("M j, Y g:ia",strtotime($study_datetime));
		}
		else {
			$study_datetime = date("Y-m-d H:i",strtotime($study_datetime));
		}
		
		return $study_datetime;
	}


	/* -------------------------------------------- */
	/* ------- GetStudySearchResultsAge ----------- */
	/* -------------------------------------------- */
	function GetStudySearchResultsAge($dob, $studyage, $studydate) {
		
		//echo "dob [$dob]  studyage [$studyage]  studydate [$studydate]<br>";
		
		# calculate study age
		if (($dobUnix = strtotime($dob)) === false) {
			//echo "Bad date/time format [$dob]<br>";
			$calculatedStudyAge = "-";
		}
		else {
			if (($studyUnix = strtotime($studydate)) === false) {
				//echo "Bad date/time format [$studydate]<br>";
				$calculatedStudyAge = "-";
			}
			else {
				$calculatedStudyAge = ($studyUnix - $dobUnix)/31536000;
				if (($calculatedStudyAge <= 0) || ($calculatedStudyAge > 150))
					$calculatedStudyAge = "-";
			}
				
		}
		
		$calculatedStudyAge = number_format($calculatedStudyAge,1);
		
		return array($studyage, $calculatedStudyAge);
	}


	/* -------------------------------------------- */
	/* ------- GetFormattedSeriesQC --------------- */
	/* -------------------------------------------- */
	function GetFormattedSeriesQC($pstats, $sequence, $min, $max, $spacing, $iosnr, $pvsnr, $motion_rsq) {
		
		/* format the colors for realignment and SNR */
		$range['x'] = abs($min['movex']) + abs($max['movex']);
		$range['y'] = abs($min['movey']) + abs($max['movey']);
		$range['z'] = abs($min['movez']) + abs($max['movez']);
		$range['Pitch'] = abs($min['rotp']) + abs($max['rotp']);
		$range['Roll'] = abs($min['rotr']) + abs($max['rotr']);
		$range['Yaw'] = abs($min['roty']) + abs($max['roty']);
		
		/* calculate color based on voxel size... red (100) means more than 1 voxel displacement in that direction */
		if ($spacing['x'] > 0) { $index['x'] = round(($range['x']/$spacing['x'])*100); if ($index['x'] > 100) { $index['x'] = 100; } }
		if ($spacing['y'] > 0) { $index['y'] = round(($range['y']/$spacing['y'])*100); if ($index['y'] > 100) { $index['y'] = 100; } }
		if ($spacing['z'] > 0) { $index['z'] = round(($range['z']/$spacing['z'])*100); if ($index['z'] > 100) { $index['z'] = 100; } }

		/* get standard deviations from the mean for SNR */
		if ($pstats[$sequence]['stdiosnr'] != 0) {
			if ($iosnr > $pstats[$sequence]['avgiosnr']) {
				$stds['iosnr'] = 0;
			}
			else {
				$stds['iosnr'] = (($iosnr - $pstats[$sequence]['avgiosnr'])/$pstats[$sequence]['stdiosnr']);
			}
		}
		if ($pstats[$sequence]['stdpvsnr'] != 0) {
			if ($pvsnr > $pstats[$sequence]['avgpvsnr']) {
				$stds['pvsnr'] = 0;
			}
			else {
				$stds['pvsnr'] = (($pvsnr - $pstats[$sequence]['avgpvsnr'])/$pstats[$sequence]['stdpvsnr']);
			}
		}
		if ($pstats[$sequence]['stdmotion'] != 0) {
			if ($motion_rsq > $pstats[$sequence]['avgmotion']) {
				$stds['motion'] = 0;
			}
			else {
				$stds['motion'] = (($motion_rsq - $pstats[$sequence]['avgmotion'])/$pstats[$sequence]['stdmotion']);
			}
		}
		
		if ($pstats[$sequence]['maxstdpvsnr'] == 0) { $index['pv'] = 100; }
		else { $pvindex = round(($stds['pvsnr']/$pstats[$sequence]['maxstdpvsnr'])*100); }
		$index['pv'] = 100 + $index['pv'];
		if ($index['pv'] > 100) { $index['pv'] = 100; }
		
		if ($pstats[$sequence]['maxstdiosnr'] == 0) { $index['io'] = 100; }
		else { $index['io'] = round(($stds['iosnr']/$pstats[$sequence]['maxstdiosnr'])*100); }
		$index['io'] = 100 + $index['io'];
		if ($index['io'] > 100) { $index['io'] = 100; }
		
		if ($pstats[$sequence]['maxstdmotion'] == 0) { $index['motion'] = 100; }
		else { $index['motion'] = round(($stds['motion']/$pstats[$sequence]['maxstdmotion'])*100); }
		$index['motion'] = 100 + $index['motion'];
		if ($index['motion'] > 100) { $index['motion'] = 100; }
		
		$color['maxpvsnr'] = $colors[100-$index['pv']];
		$color['maxiosnr'] = $colors[100-$index['io']];
		$color['maxmotion'] = $colors[100-$index['motion']];
		if ($pvsnr <= 0.0001) { $pvsnr = "-"; $color['maxpvsnr'] = "#fff"; }
		else { $pvsnr = number_format($pvsnr,2); }
		if ($iosnr <= 0.0001) { $iosnr = "-"; $color['maxiosnr'] = "#fff"; }
		else { $iosnr = number_format($iosnr,2); }
		if ($motion_rsq <= 0.0001) { $motion_rsq = "-"; $color['maxmotion'] = ""; }
		else { $motion_rsq = number_format($motion_rsq,5); }
		
		/* setup movement colors */
		$color['maxx'] = $colors[$index['x']];
		$color['maxy'] = $colors[$index['y']];
		$color['maxz'] = $colors[$index['z']];
		if ($range['x'] <= 0.0001) { $range['x'] = "-"; $color['maxx'] = "#fff"; }
		else { $range['x'] = number_format($range['x'],2); }
		if ($range['y'] <= 0.0001) { $range['y'] = "-"; $color['maxy'] = "#fff"; }
		else { $range['y'] = number_format($range['y'],2); }
		if ($range['z'] <= 0.0001) { $range['z'] = "-"; $color['maxz'] = "#fff"; }
		else { $range['z'] = number_format($range['z'],2); }
		
		return array($range, $index, $color, $snrs, $iosnr, $pvsnr);
	}

	
	/* -------------------------------------------- */
	/* ------- DisplaySearchResultsPipeline ------- */
	/* -------------------------------------------- */
	function DisplaySearchResultsPipeline($result, $s_resultoutput, $s_pipelineresulttype, $s_pipelinecolorize, $s_pipelinecormatrix, $s_pipelineresultstats) {
		
		mysqli_data_seek($result,0); /* rewind the record pointer */
		
		if ($s_pipelineresulttype == "i") {
			
			/* get the result names first (due to MySQL bug which prevents joining in this table in the main query) */
			$sqlstringX = "select * from analysis_resultnames where result_name like '%$s_pipelineresultname%' ";
			$resultX = MySQLiQuery($sqlstringX,__FILE__,__LINE__);
			while ($rowX = mysqli_fetch_array($resultX, MYSQLI_ASSOC)) {
				$resultnames[$rowX['resultname_id']] = $rowX['result_name'];
			}
			/* and get the result unit (due to the same MySQL bug) */
			$sqlstringX = "select * from analysis_resultunit where result_unit like '%$s_pipelineresultunit%' ";
			$resultX = MySQLiQuery($sqlstringX,__FILE__,__LINE__);
			while ($rowX = mysqli_fetch_array($resultX, MYSQLI_ASSOC)) {
				$resultunit[$rowX['resultunit_id']] = $rowX['result_unit'];
			}
			
			/* ---------------- pipeline results (images) --------------- */
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				//PrintVariable($row);
			
				$step = $row['analysis_step'];
				$pipelinename = $row['pipeline_name'];
				$uid = $row['uid'];
				$subject_id = $row['subject_id'];
				$gender = $row['gender'];
				$study_id = $row['study_id'];
				$study_num = $row['study_num'];
				$visittype = $row['study_type'];
				$type = $row['result_type'];
				$size = $row['result_size'];
				$name = $resultnames[$row['result_nameid']];
				$unit = $resultunit[$row['result_unitid']];
				$filename = $row['result_filename'];
				$swversion = $row['result_softwareversion'];
				$important = $row['result_isimportant'];
				$lastupdate = $row['result_lastupdate'];
				
				switch($type) {
					case "v": $thevalue = $value; break;
					case "f": $thevalue = $filename; break;
					case "t": $thevalue = $text; break;
					case "h": $thevalue = $filename; break;
					case "i": $thevalue = $filename; break;
				}
				$tables["$uid$study_num"][$name] = $thevalue;
				$tables["$uid$study_num"]['subjectid'] = $subject_id;
				$tables["$uid$study_num"]['studyid'] = $study_id;
				$tables["$uid$study_num"]['studynum'] = $study_num;
				$tables["$uid$study_num"]['visittype'] = $visittype;
				$names[$name] = "blah";
			}
			//PrintVariable($tables,'Tables');
			?>
			<table class="ui very small very compact celled table">
				<thead>
				<tr>
					<th>UID</th>
					<?
					foreach ($names as $name => $blah) {
						?>
						<th align="center" style="font-size:9pt"><?=$name?></th>
						<?
					}
				?>
				</tr>
				</thead>
				<?
					$maximgwidth = 1200/count($names);
					$maximgwidth -= ($maximgwidth*0.05); /* subtract 5% of image width to give a gap between them */
					if ($maximgwidth < 100) { $maximgwidth = 100; }
					foreach ($tables as $uid => $valuepair) {
						?>
						<tr style="font-weight: <?=$bold?>">
							<td>
								<a href="studies.php?id=<?=$tables[$uid]['studyid']?>"><b><?=$uid?></b></a>
							</td>
							<?
							foreach ($names as $name => $blah) {
								if ($tables[$uid][$name] == "") { $dispval = "-"; }
								else { $dispval = $tables[$uid][$name]; }
								list($width, $height, $type, $attr) = getimagesize($GLOBALS['cfg']['mountdir'] . "/$filename");
							?>
								<!--<td style="padding:2px">
									<a href="preview.php?image=<?=$GLOBALS['cfg']['mountdir']?>/<?=$dispval?>" class="preview">
										<img src="preview.php?image=<?=$GLOBALS['cfg']['mountdir']?>/<?=$dispval?>" style="max-width: <?=$maximgwidth?>px">
									</a>
								</td>-->
								
								<td>
									<div class="ui card">
										<div class="content">
											<div class="header"><a href="studies.php?id=<?=$studyid?>"><?="$uid$studynum"?></a></div>
											<div class="meta"><?=$dispval?></div>
											<div class="description">
												<a href="preview.php?image=<?=$GLOBALS['cfg']['mountdir']?>/<?=$dispval?>" class="preview"><img class="ui fluid image" src="preview.php?image=<?=$GLOBALS['cfg']['mountdir']?>/<?=$dispval?>"></a>
											</div>
										</div>
									</div>
								</td>
							<?
							}
							?>
						</tr>
						<?
					}
				?>
			</table>
			<br><br><br><br><br><br><br><br>
			<?
		}
		else {
			/* ---------------- pipeline results (values) --------------- */
			/* get the result names first (due to MySQL bug which prevents joining in this table in the main query) */
			$sqlstringX = "select * from analysis_resultnames where result_name like '%$s_pipelineresultname%' ";
			$resultX = MySQLiQuery($sqlstringX,__FILE__,__LINE__);
			while ($rowX = mysqli_fetch_array($resultX, MYSQLI_ASSOC)) {
				$resultnames[$rowX['resultname_id']] = $rowX['result_name'];
			}
			/* and get the result unit (due to the same MySQL bug) */
			$sqlstringX = "select * from analysis_resultunit where result_unit like '%$s_pipelineresultunit%' ";
			$resultX = MySQLiQuery($sqlstringX,__FILE__,__LINE__);
			while ($rowX = mysqli_fetch_array($resultX, MYSQLI_ASSOC)) {
				$resultunit[$rowX['resultunit_id']] = $rowX['result_unit'];
			}

			/* load the data into a useful table */
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				
				$step = $row['analysis_step'];
				$pipelinename = $row['pipeline_name'];
				$uid = $row['uid'];
				$subject_id = $row['subject_id'];
				$study_id = $row['study_id'];
				$studynum = $row['study_num'];
				$birthdate = $row['birthdate'];
				$gender = $row['gender'];
				$study_datetime = $row['study_datetime'];
				$visittype = $row['study_type'];
				$type = $row['result_type'];
				$size = $row['result_size'];
				$name = $resultnames[$row['result_nameid']];
				$name2 = $resultnames[$row['result_nameid']];
				$unit = $resultunit[$row['result_unitid']];
				$unit2 = $resultunit[$row['result_unitid']];
				$text = $row['result_text'];
				$value = $row['result_value'];
				$filename = $row['result_filename'];
				$swversion = $row['result_softwareversion'];
				$important = $row['result_isimportant'];
				$lastupdate = $row['result_lastupdate'];
				$study_ageatscan = $row['study_ageatscan'];
				
				/* calculate age at scan */
				list($studyAge, $calcStudyAge) = GetStudySearchResultsAge($birthdate, $study_ageatscan, $study_datetime);
				
				if (strpos($unit,'^') !== false) {
					$unit = str_replace('^','<sup>',$unit);
					$unit .= '</sup>';
				}
				
				switch($type) {
					case "v": $thevalue = $value; break;
					case "f": $thevalue = $filename; break;
					case "t": $thevalue = $text; break;
					case "h": $thevalue = $filename; break;
					case "i":
						?>
						<a href="preview.php?image=<?=$GLOBALS['cfg']['mountdir']?>/<?=$filename?>" class="preview"><img src="images/preview.gif" border="0"></a>
						<?
						break;
				}
				if (substr($name, -(strlen($unit))) != $unit) {
					$name .= " <b>$unit</b>";
					$name2 .= " " . $row['result_unit'];
				}
				$tables["$uid$studynum"][$name] = $thevalue;
				$tables["$uid$studynum"][$name2] = $thevalue;
				$tables["$uid$studynum"]['studyAge'] = $studyAge;
				$tables["$uid$studynum"]['calcStudyAge'] = $calcStudyAge;
				$tables["$uid$studynum"]['gender'] = $gender;
				$tables["$uid$studynum"]['subjectid'] = $subject_id;
				$tables["$uid$studynum"]['altuids'] = implode2("|", GetAlternateUIDs($subject_id,''));
				$tables["$uid$studynum"]['studyid'] = $study_id;
				$tables["$uid$studynum"]['studynum'] = $studynum;
				$tables["$uid$studynum"]['studydate'] = $study_datetime;
				$tables["$uid$studynum"]['visittype'] = $visittype;
				//$names[$name] = "blah";
				if (($thevalue > $names[$name]['max']) || ($names[$name]['max'] == "")) { $names[$name]['max'] = $thevalue; }
				if (($thevalue < $names[$name]['min']) || ($names[$name]['min'] == "")) { $names[$name]['min'] = $thevalue; }
				
				if (($thevalue > $names2[$name2]['max']) || ($names2[$name2]['max'] == "")) { $names2[$name2]['max'] = $thevalue; }
				if (($thevalue < $names2[$name2]['min']) || ($names2[$name2]['min'] == "")) { $names2[$name2]['min'] = $thevalue; }
			}

			if ($s_resultoutput == "pipelinecsv") {
				$csv = "uid,studynum,altuids,datetime,sex,age";
				foreach ($names2 as $name2 => $blah) {
					$csv .= ",$name2";
				}
				$csv .= "\n";
				foreach ($tables as $uid => $valuepair) {
					$csv .= $uid . ',' . $tables[$uid]['studynum'] . ',' . $tables[$uid]['altuids'] . ',' . $tables[$uid]['studydate'] . ',' . $tables[$uid]['gender'] . ',' . $tables[$uid]['age'];
					foreach ($names2 as $name2 => $blah) {
						$csv .= ',' . $tables[$uid][$name2];
					}
					$csv .= "\n";
				}
				$filename = "query" . GenerateRandomString(10) . ".csv";
				file_put_contents("/tmp/" . $filename, $csv);
				?>
				<br><br>
				<div width="50%" align="center" style="background-color: #FAF8CC; padding: 5px;">
				Download .csv file <a href="download.php?type=file&filename=<?="/tmp/$filename";?>"><img src="images/download16.png"></a>
				</div>
				<?
			}
		else {
		?>
			<br><br><br><br><br>
			<br><br><br><br><br>
			<br><br><br><br><br>
			<style>
				tr.rowhover:hover { background-color: ffff96; }
				td.tdhover:hover { background-color: yellow; }
			</style>
			<table cellspacing="0">
				<tr>
					<td>UID</td>
					<td>Study datetime</td>
					<td>Sex</td>
					<td>Age</td>
					<td>Visit</td>
					<?
					$csv = "studyid,sex,age";
					foreach ($names as $name => $blah) {
						$csv .= ",$name";
						?>
						<td style="max-width:25px;"><span style="padding-left: 8px; font-size:10pt; white-space:nowrap; display: block; -webkit-transform: rotate(-70deg) translate3d(0,0,0); -moz-transform: rotate(-70deg);"><?=$name?></span></td>
						<?
					}
					$csv .= "\n";
				?>
				</tr>
				<?
					foreach ($tables as $uid => $valuepair) {
						?>
						<tr style="font-weight: <?=$bold?>" class="rowhover">
							<td><a href="studies.php?id=<?=$tables[$uid]['studyid']?>"><b><?=$uid?></b></a></td>
							<td style="border-left: 1px solid #AAAAAA; border-top: 1px solid #AAAAAA; font-size:9pt; padding:2px;"><?=$tables[$uid]['studydate']?></td>
							<td style="border-left: 1px solid #AAAAAA; border-top: 1px solid #AAAAAA; font-size:9pt; padding:2px;"><?=$tables[$uid]['gender']?></td>
							<td style="border-left: 1px solid #AAAAAA; border-top: 1px solid #AAAAAA; font-size:9pt; padding:2px;"><?=$tables[$uid]['age']?></td>
							<td style="border-left: 1px solid #AAAAAA; border-top: 1px solid #AAAAAA; font-size:9pt; padding:2px;"><?=$tables[$uid]['visittype']?></td>
							<?
							$stats[0][$tables[$uid]['gender']]++;
							$stats[1][] = $tables[$uid]['age'];
							$csv .= $tables[$uid]['studyid'] . ',' . $tables[$uid]['gender'] . ',' . $tables[$uid]['age'];
							$i=2;
							foreach ($names as $name => $blah) {
								$val = $tables[$uid][$name];
								$range = $names[$name]['max'] - $names[$name]['min'];
								if (($val > 0) && ($range > 0)) {
									$cindex = round((($val - $names[$name]['min'])/$range)*100);
									//echo "[$val, $range, $cindex]<br>";
									if ($cindex > 100) { $cindex = 100; }
								}
								
								if ($tables[$uid][$name] == "") {
									$dispval = "-";
								}
								else {
									$dispval = $tables[$uid][$name];
									$stats[$i][] = $val;
									//$stats[$i]['numintotal'] ++;
								}
								$csv .= ',' . $tables[$uid][$name];
								if ($dispval != '-') {
									if (($dispval + 0) > 10000) { $dispval = number_format($dispval,0); }
									elseif (($dispval + 0) > 1000) { $dispval = number_format($dispval,2); }
									else { $dispval = number_format($dispval,4); }
								}
							?>
								<td class="tdhover" style="text-align: right; border-left: 1px solid #AAAAAA; border-top: 1px solid #AAAAAA; font-size:8pt; padding:2px; background-color: <? if ($s_pipelinecolorize) { if (trim($dispval) == '-') { echo "#EEE"; } else { echo $colors[$cindex]; } } ?>"><?=$dispval;?></td>
							<?
								$i++;
							}
							$csv .= "\n";
							?>
						</tr>
						<?
					}
					if ($s_pipelineresultstats == 1) {
						?>
						<tr class="rowhover">
							<td align="right"><b>N</b></td>
							<td class="tdhover" style="text-align: right; border-left: 1px solid #AAAAAA; border-top: 1px solid #AAAAAA; font-size:8pt; padding:2px;">
							<?
								foreach ($stats[0] as $key => $value) { echo "$key -> $value<br>"; }
							?>
							</td>
							<?
							for($i=1;$i<count($stats);$i++) {
								$count = count($stats[$i]);
								?><td class="tdhover" style="text-align: right; border-left: 1px solid #AAAAAA; border-top: 1px solid #AAAAAA; font-size:8pt; padding:2px;"><?=$count?></td><?
							}
							?>
						</tr>
						<tr class="rowhover">
							<td align="right"><b>Min</b></td>
							<td class="tdhover" style="text-align: right; border-left: 1px solid #AAAAAA; border-top: 1px solid #AAAAAA; font-size:8pt; padding:2px;"></td>
							<?
							for($i=1;$i<count($stats);$i++) {
								$min = min($stats[$i]);
								?><td class="tdhover" style="text-align: right; border-left: 1px solid #AAAAAA; border-top: 1px solid #AAAAAA; font-size:8pt; padding:2px;"><?=$min?></td><?
							}
							?>
						</tr>
						<tr class="rowhover">
							<td align="right"><b>Max</b></td>
							<td class="tdhover" style="text-align: right; border-left: 1px solid #AAAAAA; border-top: 1px solid #AAAAAA; font-size:8pt; padding:2px;"></td>
							<?
							for($i=1;$i<count($stats);$i++) {
								$max = max($stats[$i]);
								?><td class="tdhover" style="text-align: right; border-left: 1px solid #AAAAAA; border-top: 1px solid #AAAAAA; font-size:8pt; padding:2px;"><?=$max?></td><?
							}
							?>
						</tr>
						<tr class="rowhover">
							<td align="right"><b>Mean</b></td>
							<td class="tdhover" style="text-align: right; border-left: 1px solid #AAAAAA; border-top: 1px solid #AAAAAA; font-size:8pt; padding:2px;"></td>
							<?
							for($i=1;$i<count($stats);$i++) {
								$avg = number_format(array_sum($stats[$i])/count($stats[$i]),2);
								?><td class="tdhover" style="text-align: right; border-left: 1px solid #AAAAAA; border-top: 1px solid #AAAAAA; font-size:8pt; padding:2px;"><?=$avg?></td><?
							}
							?>
						</tr>
						<tr class="rowhover">
							<td align="right"><b>Median</b></td>
							<td class="tdhover" style="text-align: right; border-left: 1px solid #AAAAAA; border-top: 1px solid #AAAAAA; font-size:8pt; padding:2px;"></td>
							<?
							for($i=1;$i<count($stats);$i++) {
								$median = number_format(median($stats[$i]),2);
								?><td class="tdhover" style="text-align: right; border-left: 1px solid #AAAAAA; border-top: 1px solid #AAAAAA; font-size:8pt; padding:2px;"><?=$median?></td><?
							}
							?>
						</tr>
						<tr class="rowhover">
							<td align="right"><b>Std Dev</b></td>
							<td class="tdhover" style="text-align: right; border-left: 1px solid #AAAAAA; border-top: 1px solid #AAAAAA; font-size:8pt; padding:2px;"></td>
							<?
							for($i=1;$i<count($stats);$i++) {
								$stdev = number_format(sd($stats[$i]),2);
								?><td class="tdhover" style="text-align: right; border-left: 1px solid #AAAAAA; border-top: 1px solid #AAAAAA; font-size:8pt; padding:2px;"><?=$stdev?></td><?
							}
							?>
						</tr>
						<?
					}
				?>
			</table>
			<? if ($s_pipelinecormatrix == 1) { ?>
			<br><br><br><br>
			<br><br><br><br>
			<b>Correlation Matrix (r)</b><br>
			<?
				foreach ($names as $name => $blah) {
					foreach ($tables as $uid => $valuepair) {
						$lists['age'][] = $tables[$uid]['age'];
						
						/* this loop gets the data into an array */
						foreach ($names as $name => $blah) {
							$lists[$name][] = $tables[$uid][$name];
						}
						
					}
				}
			?>
			<table cellspacing="0">
				<tr>
					<td>&nbsp;</td>
					<? foreach ($lists as $label => $vals1) { ?>
					<td style="max-width:25px;"><span style="padding-left: 8px; font-size:10pt; white-space:nowrap; display: block; -webkit-transform: rotate(-70deg) translate3d(0,0,0); -moz-transform: rotate(-70deg);"><?=$label?></span></td>
					<? } ?>
				</tr>
				<?
					/* $kashi = new Kashi();
					foreach ($lists as $label => $vals1) {
						for ($i=0;$i<count($vals1);$i++) {
							if ($vals1[$i] == 0) { $vals1[$i] = 0.000001; }
						}
						?>
						<tr class="rowhover">
							<td align="right" style="font-size:10pt"><?=$label?></td>
						<?
						foreach ($lists as $label => $vals2) {
							$starttime1 = microtime(true);
							// compare vals1 to vals2
							//$coeff = Correlation($vals1,$vals2);
							for ($i=0;$i<count($vals2);$i++) {
								if ($vals2[$i] == 0) { $vals2[$i] = 0.000001; }
							}
							$coeff = $kashi->cor($vals1,$vals2);
							$coefftime = microtime(true) - $starttime1;
							
							$cindex = round((($coeff - (-1))/2)*100);
							//echo "[$val, $range, $cindex]<br>";
							if ($cindex > 100) { $cindex = 100; }
							// display correlation coefficient
							?>
							<td class="tdhover" style="text-align: right; border-left: 1px solid #AAAAAA; border-top: 1px solid #AAAAAA; font-size:8pt; padding:2px; background-color: <?=$colors2[$cindex]?>"><?=number_format($coeff,3);?></td>
							<?
							flush();
						}
						?>
						</tr>
						<?
					}
					*/
				?>
			</table>
			<?
				}
			}
		}
	}

	/* -------------------------------------------- */
	/* ------- DisplaySearchResultsThumbnail ------ */
	/* -------------------------------------------- */
	function DisplaySearchResultsThumbnail(&$result, $s) {
		error_reporting(-1);
		ini_set('display_errors', '1');
	
		/* escape all the variables and put them back into meaningful variable names */
		foreach ($s as $key => $value) {
			if (is_scalar($value)) { $$key = mysqli_real_escape_string($GLOBALS['linki'], $s[$key]); }
			else { $$key = $s[$key]; }
		}

		?>
		<? if ($s_resultoutput == "table") { ?>
		<table width="100%" class="searchresultssheet">
		<? } else { ?>
		<!--<table width="100%" class="searchresults" border="1">-->
		<? } ?>
			<script type="text/javascript">
			$(document).ready(function() {
				$("#seriesall").click(function() {
					var checked_status = this.checked;
					$(".allseries").find("input[type='checkbox']").each(function() {
						this.checked = checked_status;
					});
				});
			});
			</script>
		<?
		$projectids = array();
		$projectnames = array();

		/* get the users id */
		$sqlstringC = "select user_id from users where username = '" . $_SESSION['username'] ."'";
		$resultC = MySQLiQuery($sqlstringC,__FILE__,__LINE__);
		$rowC = mysqli_fetch_array($resultC, MYSQLI_ASSOC);
		$userid = $rowC['user_id'];
				
		/* check to see which projects this user has access to view */
		$sqlstringC = "select a.project_id 'projectid', b.project_name 'projectname' from user_project a left join projects b on a.project_id = b.project_id where a.user_id = '$userid' and (a.view_data = 1 or a.view_phi = 1)";
		//print "$sqlstringC<br>";
		$resultC = MySQLiQuery($sqlstringC,__FILE__,__LINE__);
		while ($rowC = mysqli_fetch_array($resultC, MYSQLI_ASSOC)) {
			$projectids[] = $rowC['projectid'];
		}
		
		/* tell the user if there are results for projects they don't have access to */
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$projectid = $row['project_id'];
			$projectname = $row['project_name'];
			$studyid = $row['study_id'];
			$subjectid = $row['subject_id'];
			$uid = $row['uid'];

			if (!in_array($projectid, $projectids)) {
				//echo "$projectid is not in projectids<br>";
				if (!in_array($projectname, $projectnames)) {
					//echo "$projectname is not in projectnames<br>";
					$projectnames[] = $projectname;
				}
			}
			
			/* BUT! while we're in this loop, count the number of unique studies ... */
			if ((!isset($studies)) || (!in_array($studyid, $studies))) {
				$studies[] = $studyid;
			}
			/* ... and # of unique subjects */
			if ((!isset($subjects)) || (!in_array($subjectid, $subjects))) {
				$subjects[] = $subjectid;
			}
			/* also a unique list of UIDs ... */
			if ((!isset($uids)) || (!in_array($uid, $uids))) {
				$uids[] = $uid;
			}
			/* ... and a unique list of SubjectIDs */
			if ((!isset($subjectids)) || (!in_array($subjectid, $subjectids))) {
				$subjectids[] = $subjectid;
			}
		}
		
		/* if a project is selected, get a list of the display IDs (the primary project ID) to be used instead of the UID */
		if (($s_projectids != "") && ($s_projectids != "all")) {
			foreach ($subjectids as $subjid) {
				foreach ($s_projectids as $projectid) {
					if ($projectid != "") {
						$displayids[$subjid] = GetPrimaryProjectID($subjid, $projectid);
					}
				}
			}
		}
		
		/* if there was a list of UIDs or alternate UIDs, determine which were not found */
		if ($s['s_subjectuid'] != "") {
			$uidsearchlist = preg_split('/[\^,;\'\s\t\n\f\r]+/', $s['s_subjectuid']);
			$missinguids = array_udiff($uidsearchlist,$uids, 'strcasecmp');
		}
		if ($s['s_subjectaltuid'] != "") {
			$altuidsearchlist = preg_split('/[\^,;\'\s\t\n\f\r]+/', $s['s_subjectaltuid']);

			/* get list of UIDs from the list of alternate UIDs */
			$sqlstringX = "select altuid from subject_altuid a left join subjects b on a.subject_id = b.subject_id where a.altuid in (" . MakeSQLList($s['s_subjectaltuid']) . ")";
			$resultX = MySQLiQuery($sqlstringX,__FILE__,__LINE__);
			while ($rowX = mysqli_fetch_array($resultX, MYSQLI_ASSOC)) {
				$altuids[] = $rowX['altuid'];
			}
			$missingaltuids = array_udiff($altuidsearchlist,$altuids, 'strcasecmp');
		}
		if ($s['s_subjectgroupid'] != "") {
			$subjectids = explode(',', GetIDListFromGroup($s['s_subjectgroupid']));
			$missingsubjects = array_udiff($subjectids,$subjects, 'strcasecmp');
			if (count($missingstudies) > 0) {
				$sqlstringY = "select uid from subjects where subject_id in (" . implode(',',$missingsubjects) . ")";
				$resultY = MySQLiQuery($sqlstringY,__FILE__,__LINE__);
				while ($rowY = mysqli_fetch_array($resultY, MYSQLI_ASSOC)) {
					$missinguids[] = $rowY['uid'];
				}
			}
		}
		if ($s['s_studygroupid'] != "") {
			$studyids = explode(',', GetIDListFromGroup($s['s_studygroupid']));
			$missingstudies = array_udiff($studyids,$studies, 'strcasecmp');
			if (count($missingstudies) > 0) {
				$sqlstringY = "select a.study_num, c.uid from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on c.subject_id = b.subject_id where study_id in (" . implode(',',$missingstudies) . ")";
				$resultY = MySQLiQuery($sqlstringY,__FILE__,__LINE__);
				while ($rowY = mysqli_fetch_array($resultY, MYSQLI_ASSOC)) {
					$missingstudynums[] = $rowY['uid'] . $rowY['study_num'];
				}
			}
		}
		?>
		Found <b><?=count($subjects)?> subjects</b> in <b><?=count($studies)?> studies</b> with <b><?=mysqli_num_rows($result)?> series</b> matching your query
		<?
			if (count($missinguids) > 0) {
			?>
				<details>
				<summary style="font-size:9pt; background-color: orangered; color: white;"><?=count($missinguids)?> UIDs not found</summary>
				<span style="font-size:9pt"><?=implode('<br>',$missinguids)?></span>
				</details>
			<?
			}
			elseif ($uidsearchlist != '') {
			?>
				<br><span style="font-size:8pt">All UIDs found</span>
			<?
			}
			
			if (count($missingaltuids) > 0) {
			?>
				<details>
				<summary style="font-size:9pt; background-color: orangered; color: white;"><?=count($missingaltuids)?> alternate UIDs not found</summary>
				<span style="font-size:9pt"><?=implode('<br>',$missingaltuids)?></span>
				</details>
			<?
			}
			elseif ($altuidsearchlist != '') {
			?>
				<br><span style="font-size:8pt">All alternate UIDs found</span>
			<?
			}
			
			if (count($missingstudynums) > 0) {
			?>
				<details>
				<summary style="font-size:9pt; background-color: orangered; color: white;"><?=count($missingstudynums)?> Studies not found</summary>
				<span style="font-size:9pt"><?=implode('<br>',$missingstudynums)?></span>
				</details>
			<?
			}
		?>
		<br><br>
		<?
		if (count($projectnames) > 0) {
		?>
			<div style="border: 2px solid darkred; background-color: #FFEEEE; text-align: left; padding:5px; border-radius: 5px">
			<b>Your search results contain subjects enrolled in the following projects to which you do not have view access</b>
			<br>Contact your PI or project administrator for access
			<ul>
			<?
			natcasesort($projectnames);
			foreach ($projectnames as $projectname) {
				echo "<li>$projectname</li>\n";
			}
			?>
			</ul>
			</div>
			<?
		}
		
		/* ----- loop through the results and display them ----- */
		?>
		<div class="ui eight cards">
		<?
		mysqli_data_seek($result,0); /* rewind the record pointer */
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {

			$project_id = $row['project_id'];
			/* if the user doesn't have view access to this project, skip to the next record */
			if (($projectids == null) || (!in_array($project_id, $projectids))) {
				continue;
			}
			$enrollment_id = $row['enrollment_id'];
			$subjectid = $row['subject_id'];
			$uid = $row['uid'];
			$studyid = $row['study_id'];
			$studynum = $row['study_num'];
			$seriesnum = $row['series_num'];
			$seriesdesc = $row['series_desc'];

			$thumbpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$studynum/$seriesnum/thumb.png";
			
			//echo "$thumbpath<br>";
			
			?>
				<!--<div class="ui center aligned column">-->
					<div class="ui card">
						<div class="content">
							<div class="header"><a href="studies.php?id=<?=$studyid?>"><?="$uid$studynum"?></a> series <?=$seriesnum?></div>
							<div class="meta"><?=$seriesdesc?></div>
							<div class="description">
								<a href="preview.php?image=<?=$thumbpath?>" class="preview"><img class="ui fluid image" src="preview.php?image=<?=$thumbpath?>"></a>
							</div>
						</div>
					</div>
				<!--</div>-->
			<?
		}
		?>
		</div>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplaySearchResultsSubject -------- */
	/* -------------------------------------------- */
	function DisplaySearchResultsSubject(&$result) {
		//PrintSQLTable(&$result);
		?>
		<form name="subjectlist" method="post" action="search.php">
		<input type="hidden" name="modality" value="">
		<input type="hidden" name="action" value="submit">
		<table class="graydisplaytable">
			<thead>
				<tr>
					<th colspan="2" style="border-right:1px solid #444">Subject</th>
					<th colspan="2">Imaging Study</th>
				</tr>
			</thead>
		<?
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$uid = $row['uid'];
			$subject_id = $row['subject_id'];
			$study_id = $row['study_id'];
			$study_num = $row['study_num'];
			$study_alternateid = $row['study_alternateid'];

			/* get list of alternate subject UIDs */
			$altuids = GetAlternateUIDs($subject_id,'');

			?>
			<tr>
				<td><a href="subjects.php?id=<?=$subject_id?>"><?=$uid?></a></td>
				<td style="border-right:1px solid #444"><?=implode2(', ',$altuids)?></td>
				<td><a href="studies.php?id=<?=$study_id?>"><?=$uid?><?=$study_num?></a></td>
				<td><?=$study_alternateid?></td>
			</tr>
			<?
		}
		?>
		</table>
		<?
		DisplayDownloadBox('', 'subject', $projectids);
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplaySearchResultsStudy ---------- */
	/* -------------------------------------------- */
	function DisplaySearchResultsStudy(&$result) {
		//PrintSQLTable(&$result);
		?>
		<form name="subjectlist" method="post" action="search.php">
		<input type="hidden" name="modality" value="">
		<input type="hidden" name="action" value="submit">
		<table class="ui very compact small celled selectable table" width="100%">
			<thead>
				<tr>
					<!--<th>&nbsp;</th>-->
					<th>UID</th>
					<th>Project<br><span class="tiny">Enroll dates</span></th>
					<th>DOB</th>
					<th>Gender</th>
					<th>Ethnicities</th>
					<th>Education</th>
					<th>Handedness</th>
					<th>uuid</th>
					<th>Alt UIDs</th>
				</tr>
			</thead>
		<?
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$uid = $row['uid'];
			$subject_id = $row['subject_id'];
			$enrollment_id = $row['enrollment_id'];
			$project_name = $row['project_name'];
			$enroll_startdate = $row['enroll_startdate'];
			$enroll_enddate = $row['enroll_enddate'];
			$birthdate = $row['birthdate'];
			$gender = $row['gender'];
			$ethnicity1 = $row['ethnicity1'];
			$ethnicity2 = $row['ethnicity2'];
			$weight = $row['weight'];
			$handedness = $row['handedness'];
			$education = $row['education'];
			$uid = $row['uid'];
			$uuid = strtoupper($row['uuid']);

			/* get list of alternate subject UIDs */
			$altuids = GetAlternateUIDs($subject_id,'');
			
			$enroll_startdate = date("Y-m-d",strtotime($enroll_startdate));
			if ($enroll_enddate = '0000-00-00 00:00:00') {
				$enroll_enddate = 'present';
			}
			else {
				$enroll_enddate = date("Y-m-d",strtotime($enroll_enddate));
			}
			
			if ($gender == '') { $gender = '-'; }
			if (($ethnicity1 == '') && ($ethnicity2 == '')) { $ethnicity = '-'; }
			else { $ethnicity = "$ethnicity $ethnicity2"; }
			if ($gender == '') { $gender = '-'; }
			//if ($handedness == '') { $handedness = '-'; }
			?>
			<tr>
				<!--<td><input type="checkbox" name="enrollmentid[]" value="<?=$enrollment_id?>"></td>-->
				<td><a href="subjects.php?id=<?=$subject_id?>"><?=$uid?></a></td>
				<td><?=$project_name?><br><span class="tiny"><?=$enroll_startdate?> - <?=$enroll_enddate?></span></td>
				<td><?=$birthdate?></td>
				<td><?=$gender?></td>
				<td><?=$ethnicity1?> <?=$ethnicity2?></td>
				<td><?=$education?></td>
				<td><?=$handedness?></td>
				<td class="tiny"><?=$uuid?></td>
				<td><?=implode2(', ',$altuids)?></td>
			</tr>
			<?
		}
		?>
		</table>
		<?
		DisplayDownloadBox('', 'subject', $projectids);
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplaySearchResultsLongitudinal --- */
	/* -------------------------------------------- */
	function DisplaySearchResultsLongitudinal(&$result) {
		//PrintSQLTable(&$result);
		
		$modality = '';
		/* gather scans into longitudinal format */
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$uid = $row['uid'];
			$studyid = $row['study_id'];
			$studynum = $row['study_num'];
			$studydate = $row['study_datetime'];
			$seriesdesc = $row['series_desc'];
			$seriesid = $row['mrseries_id'];
			$modality = strtolower($row['study_modality']);
			
			$longs[$uid][$seriesdesc][$studydate][] = "$seriesid,$studyid,$studynum";
			$subjects[$uid]++;
			$studies[$studyid]++;
			$series[$seriesid]++;
		}
		
		//echo "<pre>";
		//print_r($longs);
		//echo "</pre>";
		
		$maxcol = 0;
		/* get the counts for actual longitudinal studies: those with at least 2 studies */
		foreach ($longs as $uid => $value) {
			foreach ($longs[$uid] as $seriesdesc => $value2) {
				if (count($value2) > $maxcol) {
					$maxcol = count($value2);
				}
				if (count($value2) > 1) {
					foreach ($longs[$uid][$seriesdesc] as $studydate => $seriesids) {
						$subjects2[$uid]++;
						$studies2[$studydate]++;
						foreach ($longs[$uid][$seriesdesc][$studydate] as $seriesid) {
							$series2[$seriesid]++;
						}
					}
				}
			}
		}
		?>
		<form name="subjectlist" method="post" action="search.php">
		<input type="hidden" name="modality" value="<?=$modality?>">
		<input type="hidden" name="action" value="submit">
		<style>
			.darkblue { color: darkblue; font-weight: bold; }
			tr.rowhover:hover { background-color: ffff96; }
			td.tdhover:hover { background-color: yellow; }
		</style>
		<br>
		Of <span class="darkblue"><?=count($subjects)?> subjects</span>, <span class="darkblue"><?=count($studies)?> studies</span>, <span class="darkblue"><?=count($series)?> series</span>, longitudinal series were found in <span class="darkblue"><?=count($subjects2)?> subjects</span>, <span class="darkblue"><?=count($studies2)?> studies</span>, <span class="darkblue"><?=count($series2)?> series</span><br><br>
		<?

		$csv1 = "uid, protocol";
		$csv2 = "uid, protocol";
		
		?><table cellspacing="0" style="border-collapse:collapse;">
			<tr>
				<td style="padding: 1px 5px;"><b>UID</b><br><span class="tiny">(Alternate UIDs)</span></td>
				<td style="padding: 1px 5px; border-right: 2px solid #aaa"><b>Protocol</b></td>
				<?
					for ($col=1;$col<$maxcol;$col++) {
						$csv1 .= ", Time$col";
						$csv2 .= ", Time$col";
						?>
						<script type="text/javascript">
						$(document).ready(function() {
							$("#col<?=$col?>").click(function() {
								var checked_status = this.checked;
								$(".col<?=$col?>").find("input[type='checkbox']").each(function() {
									this.checked = checked_status;
								});
							});
						});
						</script>						
						<td align="right" style="color:darkblue"><b>Time <?=$col?> <input type="checkbox" name="col<?=$col?>" id="col<?=$col?>"> </b></td>
						<td class="tiny" align="center">&nbsp;</td>
						<?
					}
				?>
				<td align="right" style="color:darkblue"><b>Time <?=$maxcol?> <input type="checkbox" name="" onclick=""> </b></td>
			</tr>
		<?
		$csv1 .= "\n";
		$csv2 .= "\n";
		
		/* loop through the UIDs */
		foreach ($longs as $uid => $value) {
			$printeduid = false;
			$firstline = true;
			
			/* loop through the SeriesDescriptions */
			foreach ($longs[$uid] as $seriesdesc => $value2) {
				if (count($value2) > 1) {
					if ($firstline) { $borderstyle = "border-top: 2px solid #AAAAAA"; $firstline = false; }
					else { $borderstyle = ""; }
					?><tr class="rowhover" style="<?=$borderstyle?>"><?
					if ($printeduid != true) {
						/* get a list of alternate UIDs */
						$altuids = null;
						$sqlstringC = "select * from subject_altuid where subject_id in (select subject_id from subjects where uid = '$uid')";
						$resultC = MySQLiQuery($sqlstringC,__FILE__,__LINE__);
						while ($rowC = mysqli_fetch_array($resultC, MYSQLI_ASSOC)) {
							$altuids[] = $rowC['altuid'];
						}
						?>
						<td valign="top" style="border-top: solid black 1pt; padding: 1px 5px;"><b><?=$uid?></b><br>
						<span class="tiny">(<?=implode(', ', $altuids)?>)</span></td>
						<?
						$printeduid = true;
					}
					else {
						?><td></td><?
					}
					?><td valign="top" style="border-left: 1px solid #DDDDDD; border-right: 2px solid #aaa; white-space: nowrap; font-size:11pt; padding: 1px 5px"><?=$seriesdesc?></td><?
					$lastdate = "";
					$tspan = "";
					$csv1 .= "$uid,$seriesdesc";
					$csv2 .= "$uid,$seriesdesc";
					
					$numcolsdisplayed = 0;
					/* loop through the studies */
					foreach ($longs[$uid][$seriesdesc] as $studydate => $seriesids) {
						list($seriesid1,$studyid,$studynum) = explode(',',$seriesids[0]);
						//echo "seriesID $seriesid<br>";
						if ($lastdate != "") {
							$tspan = (strtotime($studydate) - strtotime($lastdate))/60/60/24/365;
							//echo $tspan;
							if ($tspan < 1) {
								$tspan = number_format($tspan * 365, 0) . " d";
							}
							else {
								$tspan = number_format($tspan,1) . " y";
							}
						}
						$csv1 .= ",$studydate";
						$csv2 .= ",$uid$studynum";
						$studydate = date("M j, Y", strtotime($studydate));
							if ($tspan != "") {
								$numcolsdisplayed++;
						?>
						<td class="tdhover" valign="top" align="center" style="font-size:8pt; white-space: nowrap; border-left: 1px solid #DDDDDD; border-right: 1px solid #DDDDDD; padding: 2px 5px;">&larr; <b><?=$tspan?></b> &rarr;</td>
						<?
							}
							$numcolsdisplayed++;
						?>
						<td class="tdhover col<?=ceil($numcolsdisplayed/2);?>" align="right" valign="top" style="font-size:8pt; white-space: nowrap; border-left: 1px solid #DDDDDD; border-right: 1px solid #DDDDDD; padding: 1px 5px;">
						<a href="studies.php?id=<?=$studyid?>"><?=$studydate?></a> [<?=$studynum?>]
						<?
						foreach ($seriesids as $ser) {
							list($seriesid,$studyid,$studynum) = explode(',',$ser);
							$sqlstring = "select * from " . strtolower($modality) . "_series where " . strtolower($modality) . "series_id = '$seriesid'";
							$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
							$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
							$seriesnum = $row['series_num'];
							$seriesdate = date("M j, Y h:m:s a", strtotime($row['series_datetime']));
							$protocol = $row['series_desc'];
							$sequence = $row['series_sequencename'];
							$series_num = $row['series_num'];
							$series_tr = $row['series_tr'];
							$series_spacingx = number_format($row['series_spacingx'],2);
							$series_spacingy = number_format($row['series_spacingy'],2);
							$series_spacingz = number_format($row['series_spacingz'],2);
							$series_fieldstrength = $row['series_fieldstrength'];
							$series_notes = $row['series_notes'];
							$img_rows = $row['img_rows'];
							$img_cols = $row['img_cols'];
							$img_slices = $row['img_slices'];
							$bold_reps = $row['bold_reps'];
							$numfiles = $row['numfiles'];
							$series_size = $row['series_size'];
							$numfiles_beh = $row['numfiles_beh'];
							$beh_size = $row['beh_size'];
							$series_status = $row['series_status'];
							$is_derived = $row['is_derived'];
							$title = "<b style='color:darkblue'><big>$protocol</big></b><br><br><b>Num files:</b> $numfiles<br><b>Date:</b> $seriesdate<br><b>Image dimensions (pixels):</b> $img_rows x $img_cols x $img_slices<br><b>Voxel Spacing (mm):</b> $series_spacingx x $series_spacingy x $series_spacingz";
							?><br><span title="<?=$title?>"><?=$seriesnum?> <input type="checkbox" name="seriesid[]" value="<?=$seriesid?>"></span>
							<input type="hidden" name="timepoints[<?=$seriesid?>]" value="<?=($numcolsdisplayed+1)/2?>"><!--<?=($numcolsdisplayed+1)/2?>--><?
						}
						?>
						</td><?
						$lastdate = $studydate;
					}
					for ($i=0;$i<(($maxcol*2)-$numcolsdisplayed-1);$i++) {
						?><td style="font-size:8pt; white-space: nowrap; border-left: 1px solid #DDDDDD; border-right: 1px solid #DDDDDD; padding: 1px 5px;"></td><?
						$csv1 .= ",";
						$csv2 .= ",";
					}
					?></tr><?
					$csv1 .= "\n";
					$csv2 .= "\n";
				}
			}
		}
		?></table>
		.csv file with scan dates<br>
		<textarea rows="8" cols="150"><?=$csv1?></textarea>
		<br><br>
		.csv file with study numbers<br>
		<textarea rows="8" cols="150"><?=$csv2?></textarea>
		<?
		DisplayDownloadBox(strtolower($modality), 'long', $projectids);
	}

	
	/* ---------------------------------------------------------- */
	/* ------- DisplaySearchResultsLongitudinalPipeline --------- */
	/* ---------------------------------------------------------- */
	function DisplaySearchResultsLongitudinalPipeline(&$result, $s_resultoutput) {
		//PrintSQLTable($result);
		
		if ($s_resultoutput == 'pipelinelong') {
			$agebin = 'M';
			$agecutoffmin = 10*12;
			$agecutoffmax = 95*12;
		}
		else {
			$agebin = 'Y';
			$agecutoffmin = 10;
			$agecutoffmax = 95;
		}
		
		$modality = '';
		$i = 0;
		/* gather scans into longitudinal format */
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$uid = strtoupper(trim($row['uid']));
			$encuid = crc32(strtoupper(trim($row['uid'])));
			$studyid = $row['study_id'];
			#$studynum = $row['study_num'];
			$sex = $row['gender'];
			$age = $row['ageinmonths'];
			#$seriesdesc = $row['series_desc'];
			$resultname = $row['result_nameid'];
			$resultvalue = $row['result_value'];
			
			$series[] = $resultname;
			
			# exclude anyone out of the age range and not M or F
			if ( (($age >= $agecutoffmin) && ($age <= $agecutoffmax)) && (($sex == "M") || ($sex == "F")) ) {
				$longs[$age][$resultname][] = $resultvalue;
		
				$exportdata[$resultname][$encuid]['age'] = $age;
				$exportdata[$resultname][$encuid]['value'] = $resultvalue;
				$exportdata[$resultname][$encuid]['sex'] = $sex;
				
				$exportdata2[$encuid][$resultname]['age'] = $age;
				$exportdata2[$encuid][$resultname]['value'] = $resultvalue;
				$exportdata2[$encuid][$resultname]['sex'] = $sex;
				$i++;
			}
		}
		$series = array_unique($series);
		sort($series);
		
		ksort($longs);
		
		//echo "<pre>";
		//print_r($exportdata2);
		//echo "</pre>";
		//exit(0);
		
		/* get the month ranges */
		$thekeys = array_keys($longs);
		$minage = $thekeys[0];
		$maxage = end($thekeys);
		echo "Age range in months [$minage] to [$maxage]<br>";
		
		$csv1 = "uid";
		$csv2 = "uid";
		
		/* loop through the age bins and calculate stats on each bin */
		foreach ($longs as $bin => $val) {
			/* loop through the SeriesDescriptions */
			foreach ($val as $resultid => $values) {
				$mean = array_sum($values) / count($values);
				$count = count($values);
				$min = min($values);
				$max = max($values);
				$stdev = sd($values);
				
				$summary[$bin][$resultid]['mean'] = $mean;
				$summary[$bin][$resultid]['count'] = $count;
				$summary[$bin][$resultid]['min'] = $min;
				$summary[$bin][$resultid]['max'] = $max;
				$summary[$bin][$resultid]['stdev'] = $stdev;
			}
		}
		//echo "<pre>";
		//print_r($summary);
		//echo "</pre>";
		
		foreach ($series as $resultid) {
			$sqlstring = "select result_name from analysis_resultnames where resultname_id = '$resultid'";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$resultname = $row['result_name'];
			?>
			<?=$resultname?> [<?=$resultid?>]<br>
			<table cellspacing="0" style="font-size: 8pt" border="1">
				<tr>
					<td>Bin (months)</td>
					<td>Count</td>
					<td>min</td>
					<td>max</td>
					<td>stdev</td>
					<td>Mean</td>
				</tr>
				<?
					foreach ($summary as $bin => $values) {
						$mean = $values[$resultid]['mean'];
						$count = $values[$resultid]['count'];
						$min = $values[$resultid]['min'];
						$max = $values[$resultid]['max'];
						$stdev = $values[$resultid]['stdev'];
						?>
						<tr>
							<td align="right" style="color:darkblue"><?=$bin?></td>
							<td align="right" style="color:darkblue"><?=$count?></td>
							<td align="right" style="color:darkblue"><?=$min?></td>
							<td align="right" style="color:darkblue"><?=$max?></td>
							<td align="right" style="color:darkblue"><?=$stdev?></td>
							<td align="right" style="color:darkblue"><?=$mean?></td>
						</tr>
						<?
						}
					?>
			</table>
			<?
		}
		
		$csv = "ID, age, sex, ROI, value\n";
		foreach ($exportdata as $resultid => $subject) {
			foreach ($subject as $uid => $values) {
				$age = $values['age'];
				$sex = strtoupper($values['sex']);
				$value = $values['value'];
				$csv .= "$uid, $age, $sex, $resultid, $value\n";
				$exportdatacombined[$uid]['age'] = $age;
				$exportdatacombined[$uid]['value'] += $value;
				$exportdatacombined[$uid]['sex'] = $sex;
			}
		}
		?>
		
		<br>
		Full table .csv (collapsed by UID)<br>
		<textarea rows="8" cols="150"><?=$csv?></textarea>
		
		
		<?
		$csv3 = "ID, age, sex";
		reset($exportdata2);
		$key = key($exportdata2);
		$rois = $exportdata2[$key];
		foreach ($rois as $roi => $val) {
			$csv3 .= ", $roi";
		}
		$csv3 .= "\n";
		foreach ($exportdata2 as $uid => $values) {
			$k = key($values);
			$age = $values[$k]['age'];
			$sex = strtoupper($values[$k]['sex']);
			$csv3 .= "$uid, $age, $sex";
			reset($values);
			foreach ($values as $vals) {
				$val = $vals['value'];
				$csv3 .= ", $val";
			}
			$csv3 .= "\n";
		}
		?>
		
		<br>
		Full table .csv (one UID per row, with ICV)<br>
		<textarea rows="8" cols="150"><?=$csv3?></textarea>

		<?
		$csv2 = "ID, age, sex, value\n";
		foreach ($exportdatacombined as $uid => $values) {
			$age = $values['age'];
			$sex = strtoupper($values['sex']);
			$value = $values['value'];
			$csv2 .= "$uid, $age, $sex, $value\n";
		}
		?>
		<br>
		Full table .csv (collapsed by UID, and combined regions: eg. right + left = total volume)<br>
		<textarea rows="8" cols="150"><?=$csv2?></textarea>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplaySearchResultsQC ------------- */
	/* -------------------------------------------- */
	function DisplaySearchResultsQC(&$result, $s_resultoutput, $s_qcbuiltinvariable, $s_qcvariableid) {
		//PrintSQLTable($result);
		
		/* gather scans into longitudinal format */
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$uid = strtoupper(trim($row['uid']));
			$studydate = trim($row['studydate']);
			$studyid = trim($row['study_id']);
			$studynum = trim($row['study_num']);
			$seriesdesc = trim($row['series_desc']);
			$qc[$seriesdesc][$studydate]['data']['IO SNR'] = trim($row['io_snr']);
			$qc[$seriesdesc][$studydate]['data']['PV SNR'] = trim($row['pv_snr']);
			$qc[$seriesdesc][$studydate]['data']['Total displacement X'] = $row['move_maxx'] - $row['move_minx'];
			$qc[$seriesdesc][$studydate]['data']['Total displacement Y'] = $row['move_maxy'] - $row['move_miny'];
			$qc[$seriesdesc][$studydate]['data']['Total displacement Z'] = $row['move_maxz'] - $row['move_minz'];
			$qc[$seriesdesc][$studydate]['data']['Motion rsq'] = trim($row['motion_rsq']);
			$qc[$seriesdesc][$studydate]['studyid'] = $studyid;
			$qc[$seriesdesc][$studydate]['studynum'] = "$uid$studynum";

			$mrseriesids[] = $row['mrseries_id'];
		}
		
		array_unique($mrseriesids);
		
		$mrserieslist = implode2(',', $mrseriesids);
		
		/* now we have a list of MR series ids, so lets get all modular QC for these seriesids */
		$sqlstring = "SELECT a.*, b.*,c.*,d.*, e.series_desc, DATE(series_datetime) 'studydate' FROM `qc_moduleseries` a LEFT JOIN `qc_results` b ON b.qcmoduleseries_id = a.qcmoduleseries_id LEFT JOIN `qc_modules` c ON c.qcmodule_id = a.qcmodule_id left join `qc_resultnames` d on d.qcresultname_id = b.qcresultname_id left join mr_series e on e.mrseries_id = a.series_id left join studies f on e.study_id = f.study_id WHERE a.series_id IN ($mrserieslist) and d.qcresult_type = 'number'";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$modulename = $row['qcm_name'];
			$variable = $row['qcresult_name'];
			$value = $row['qcresults_valuenumber'];
			$units = $row['qcresult_units'];
			$seriesdesc = $row['series_desc'];
			$seriesid = $row['series_id'];
			$studydate = $row['studydate'];
			
			$qc[$seriesdesc][$studydate]['data'][$variable] = trim($value);
			
			if ($qc[$seriesdesc][$studydate]['studyid'] == "") {
				list($path, $seriespath, $qpath, $uid, $studynum, $studyid, $subjectid) = GetDataPathFromSeriesID($seriesid, 'MR');
				$qc[$seriesdesc][$studydate]['studyid'] = $studyid;
				$qc[$seriesdesc][$studydate]['studynum'] = "$uid$studynum";
			}
		}
		
		//ksort($qc);
		
		//PrintVariable($qc);
		
		/* loop through the series */
		foreach ($qc as $series => $value) {
			$j=1;
			?>
			<br>
			<table width="100%" style="border: 1px solid #888; border-spacing: 0px;">
				<tr>
					<td style="background-color: lightblue; padding: 5px;" colspan="4">Charts for <b><?=$series?></b></td>
				</tr>
				<tr>
					<?
					//ksort($value);
					//PrintVariable($value);
					/* loop through the list of dates for this series */
					foreach ($value as $date => $vals) {
						/* loop through the list of variables for this date */
						foreach ($vals['data'] as $var => $val) {
							if ($val > 0) {
								$charts[$var][$date]['data']['value'] = $val;
								$charts[$var][$date]['studyid'] = $vals['studyid'];
								$charts[$var][$date]['studynum'] = $vals['studynum'];
							}
						}
					}
					
					//PrintVariable($charts);
					foreach ($charts as $chartname => $chart) {
						if (count($chart) > 2) {
							$i = 0;
							ksort($chart);
							foreach ($chart as $date => $val) {
								$xs[$i] = $date;
								$ys[$i] = $val['data']['value'];
								$studyids[$i] = $val['studyid'];
								$studynums[$i] = $val['studynum'];
								$i++;
							}
							$x = implode(',', $xs);
							$y = implode(',', $ys);
							
							?>
							<td valign="top">
							<img src='xygraph.php?h=200&w=420&t=<?=$chartname?>&x=<?=$x?>&y=<?=$y?>&xtype=dat&ytype=lin'>
							<details>
							<summary>Data</summary>
							<table class="ui very compact small celled table">
								<tr>
									<th>Study</th>
									<th>Date</th>
									<th>Value</th>
								</tr>
								<?
								foreach ($xs as $i => $blah) {
									?>
									<tr>
										<td><a href="studies.php?id=<?=$studyids[$i]?>"><?=$studynums[$i]?></a></td>
										<td><?=$xs[$i]?></td>
										<td><?=$ys[$i]?></td>
									</tr>
									<?
								}
								?>
							</table>
							</details>
							</td>
							<?
							
							$x = "";
							$y = "";
							unset($xs);
							unset($ys);
							unset($studyids);
							unset($studynums);
							
							if ($j > 3) {
								$j = 0;
								?>
								</tr>
								<tr>
								<?
							}
							$j++;
						}
					}
					?>
				</tr>
			</table>
			<?
		}
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayChart ----------------------- */
	/* -------------------------------------------- */
	function DisplayChart($data1, $data2, $data3, $title, $label1, $label2, $label3, $height, $id, $disptable) {
		$colors = GenerateColorGradient();
		ksort($data1);
		ksort($data2);
		ksort($data3);
		?>
			<table class="smallgrayrounded" width="100%">
				<tr>
					<td class="title"><?=$title?></td>
				</tr>
				<tr>
					<td class="body">
						<script>
							$(function() {
									var data1 = [<?
											foreach ($data1 as $date => $item) {
												$value = $data1[$date]['value'];
												$date = $date*1000;
												if (($date > 0) && ($value > 0)) {
													$jsonstrings[] .= "['$date', $value]";
												}
											}?><?=implode2(',',$jsonstrings)?>];
									<? if ($label2 != "") { ?>var data2 = [<?
											$jsonstrings = "";
											foreach ($data2 as $date => $item) {
												$value = $data2[$date]['value'];
												$date = $date*1000;
												if (($date > 0) && ($value > 0)) {
													$jsonstrings[] .= "['$date', $value]";
												}
											}?><?=implode2(',',$jsonstrings)?>];
									<? } ?>
									<? if ($label3 != "") { ?>var data3 = [<?
											$jsonstrings = "";
											foreach ($data3 as $date => $item) {
												$value = $data3[$date]['value'];
												$date = $date*1000;
												if (($date > 0) && ($value > 0)) {
													$jsonstrings[] .= "['$date', $value]";
												}
											}?><?=implode2(',',$jsonstrings)?>];
									<? } ?>
							
								var options = {
									series: {
										lines: { show: true, fill: false },
										points: { show: true }
									},
									grid: {
										hoverable: true,
										clickable: true
									},
									legend: { noColumns: 6 },
									xaxis: { mode: "time", timeformat: "%Y-%m-%d" },
									yaxis: { min: 0, tickDecimals: 1 },
									selection: { mode: "x" },
								};
								var placeholder = $("#placeholder<?=$id?>");
								var plot = $.plot(placeholder, [
								{ label: "<?=$label1?>", color: '#F00', data: data1}<? if ($label2 != "") { ?>, { label: "<?=$label2?>", color: '#4B4', data: data2} <? } ?><? if ($label3 != "") { ?>, { label: "<?=$label3?>", color: '#00F', data: data3} <? } ?> ],options);
							});
						</script>
						<div id="placeholder<?=$id?>" style="height:<?=$height?>px;" align="center"></div>
					</td>
				</tr>
				<? if ($disptable) { ?>
				<tr>
					<td class="body">
						<table class="ui very compact small celled table">
							<thead>
								<th>Date</th>
								<th>Subject</th>
								<th>Study</th>
								<th>Value</th>
							</thead>
							<tbody>
						<?
							// get min, max
							$min = $data[0];
							$max = $data[0];
							foreach ($data as $date => $value) {
								$value = $data[$date]['value'];
								if ($value > $max) { $max = $value; }
								if ($value < $min) { $min = $value; }
							}
							$range = $max - $min;
							
							foreach ($data as $date => $value) {
								$value = $data[$date]['value'];
								$uid = $data[$date]['uid'];
								$studynum = $data[$date]['studynum'];
								$subjectid = $data[$date]['subjectid'];
								$studyid = $data[$date]['studyid'];
								if (($value > 0) && ($range > 0)) {
									$cindex = round((($value - $min)/$range)*100);
									if ($cindex > 100) { $cindex = 100; }
								}
								$date = $date;
								$date = date("D, d M Y", $date);
								?>
								<tr>
									<td><?=$date?></td>
									<td><a href="subjects.php?id=<?=$subjectid?>"><?=$uid?></a></td>
									<td><a href="subjects.php?id=<?=$studyid?>"><?=$uid?><?=$studynum?></a></td>
									<td align="right" bgcolor="<?=$colors[$cindex];?>"><tt><?=$value?><tt></td>
								</tr>
								<?
							}
						?>
							</tbody>
						</table>
					</td>
				</tr>
				<? } ?>
			</table>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayFileIOBox ------------------- */
	/* -------------------------------------------- */
	function DisplayFileIOBox() {
		?>
		<br>
		<table>
			<tr>
				<td style="color:#444">
					<b>Enter list of <i>tag=value pairs</i>.</b> <i>tag=value</i> pairs should be separated with semi-colons. No leading zeros on tags and values should be in single quotes
					<br>
					Example: <tt>10,1030='Anonymous'; 10,103E='Anon'</tt>
					<br>
					<span class="tiny">For a list of tags, click <a href="http://www.sno.phy.queensu.ca/~phil/exiftool/TagNames/DICOM.html">here</a>.</span>
					<br><br>
					<textarea name="dicomtags" rows="8" cols="70"></textarea>
				</td>
			</tr>
		</table>
		<input class="ui primary button" type="submit" value="Submit" onclick="document.subjectlist.action.value='anonymize'">
		</form>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayDownloadBox ----------------- */
	/* -------------------------------------------- */
	function DisplayDownloadBox($s_studymodality, $s_resultoutput, $projectids) {
		
		?>
			<br><br>
			
			<script type="text/javascript">
				$(document).ready(function() {
					/* hide it by default */
					$('.remoteftp').hide();
					$('.remotenidb').hide();
					$('.bids').hide();
					$('.squirrel').hide();
					$('.publicdownload').hide();
					<? if ($s_resultoutput != 'subject') { ?>
					$('.export').hide();
					<? } else { ?>
					$('.dirstructure').hide();
					<?} ?>
					$('.dicom').hide();

					$('input[name=filetype]').click(function() {
						if ($('#filetype:checked').val() == 'dicom') {
							$('.dicom').show();
							$('.bids').hide();
							$('.dirstructure').show();
						}
						else if ($('#filetype:checked').val() == 'bids') {
							$('.bids').show();
							$('.dicom').hide();
							$('.dirstructure').hide();
						}
						else {
							$('.dicom').hide();
							$('.bids').hide();
							$('.dirstructure').show();
						}
					});
					
					/* types of information to download */
					$('input[name=downloadimaging]').click(function() {
						/* hide all ... */
						$('#sectionformat').hide();
						$('#sectiondirstructure').hide();
						$('.beh').hide();
						/* ... then show the appropriate sections */
						if ($('#downloadimaging:checked').val() == '1') {
							$('#sectionformat').show();
							$('#sectiondirstructure').show();
						}
						if ($('#downloadbeh:checked').val() == '1') {
							$('.beh').show();
							$('#sectiondirstructure').show();
						}
					});
					
				});
				
				
				function HighlightStudyDir() {
					var dirformat = $("[name='dirformat']:checked").val();
					
					if (dirformat == "shortid") {
						document.getElementById("label_shortid").classList.add('red');
						document.getElementById("label_shortstudyid").classList.remove('red');
						document.getElementById("label_altuid").classList.remove('red');
						document.getElementById("label_visittype").classList.remove('red');
						document.getElementById("label_daynum").classList.remove('red');
						document.getElementById("label_timepoint").classList.remove('red');
					}
					else if (dirformat == "shortstudyid") {
						document.getElementById("label_shortid").classList.remove('red');
						document.getElementById("label_shortstudyid").classList.add('red');
						document.getElementById("label_altuid").classList.remove('red');
						document.getElementById("label_visittype").classList.remove('red');
						document.getElementById("label_daynum").classList.remove('red');
						document.getElementById("label_timepoint").classList.remove('red');
					}
					else if (dirformat == "altuid") {
						document.getElementById("label_shortid").classList.remove('red');
						document.getElementById("label_shortstudyid").classList.remove('red');
						document.getElementById("label_altuid").classList.add('red');
						document.getElementById("label_visittype").classList.remove('red');
						document.getElementById("label_daynum").classList.remove('red');
						document.getElementById("label_timepoint").classList.remove('red');
					}
					else if (dirformat == "visittype") {
						document.getElementById("label_shortid").classList.remove('red');
						document.getElementById("label_shortstudyid").classList.remove('red');
						document.getElementById("label_altuid").classList.remove('red');
						document.getElementById("label_visittype").classList.add('red');
						document.getElementById("label_daynum").classList.remove('red');
						document.getElementById("label_timepoint").classList.remove('red');
					}
					else if (dirformat == "daynum") {
						document.getElementById("label_shortid").classList.remove('red');
						document.getElementById("label_shortstudyid").classList.remove('red');
						document.getElementById("label_altuid").classList.remove('red');
						document.getElementById("label_visittype").classList.remove('red');
						document.getElementById("label_daynum").classList.add('red');
						document.getElementById("label_timepoint").classList.remove('red');
					}
					else if (dirformat == "timepoint") {
						document.getElementById("label_shortid").classList.remove('red');
						document.getElementById("label_shortstudyid").classList.remove('red');
						document.getElementById("label_altuid").classList.remove('red');
						document.getElementById("label_visittype").classList.remove('red');
						document.getElementById("label_daynum").classList.remove('red');
						document.getElementById("label_timepoint").classList.add('red');
					}
				}

				function HighlightSeriesDir() {
					var dirformat = $("[name='preserveseries']:checked").val();
					
					if (dirformat == "1") {
						document.getElementById("label_preserveseries1").classList.add('red');
						document.getElementById("label_preserveseries0").classList.remove('red');
						document.getElementById("label_preserveseries2").classList.remove('red');
						document.getElementById("label_preserveseries3").classList.remove('red');
					}
					else if (dirformat == "0") {
						document.getElementById("label_preserveseries1").classList.remove('red');
						document.getElementById("label_preserveseries0").classList.add('red');
						document.getElementById("label_preserveseries2").classList.remove('red');
						document.getElementById("label_preserveseries3").classList.remove('red');
					}
					else if (dirformat == "2") {
						document.getElementById("label_preserveseries1").classList.remove('red');
						document.getElementById("label_preserveseries0").classList.remove('red');
						document.getElementById("label_preserveseries2").classList.add('red');
						document.getElementById("label_preserveseries3").classList.remove('red');
					}
					else if (dirformat == "3") {
						document.getElementById("label_preserveseries1").classList.remove('red');
						document.getElementById("label_preserveseries0").classList.remove('red');
						document.getElementById("label_preserveseries2").classList.remove('red');
						document.getElementById("label_preserveseries3").classList.add('red');
					}
				}

				function HighlightBehDir() {
					var dirformat = $("[name='behformat']:checked").val();
					
					if (dirformat == "behroot") {
						document.getElementById("label_behroot").classList.add('red');
						document.getElementById("label_behrootdir").classList.remove('red');
						document.getElementById("label_behseries").classList.remove('red');
						document.getElementById("label_behseriesdir").classList.remove('red');
					}
					else if (dirformat == "behrootdir") {
						document.getElementById("label_behroot").classList.remove('red');
						document.getElementById("label_behrootdir").classList.add('red');
						document.getElementById("label_behseries").classList.remove('red');
						document.getElementById("label_behseriesdir").classList.remove('red');
					}
					else if (dirformat == "behseries") {
						document.getElementById("label_behroot").classList.remove('red');
						document.getElementById("label_behrootdir").classList.remove('red');
						document.getElementById("label_behseries").classList.add('red');
						document.getElementById("label_behseriesdir").classList.remove('red');
					}
					else if (dirformat == "behseriesdir") {
						document.getElementById("label_behroot").classList.remove('red');
						document.getElementById("label_behrootdir").classList.remove('red');
						document.getElementById("label_behseries").classList.remove('red');
						document.getElementById("label_behseriesdir").classList.add('red');
					}
				}
				
				function CheckDestination() {
					
					var dest = $("[name='destination']:checked").val();
					console.log(dest);
					
					/* export */
					if (dest == 'export') {
						$('.export').show("highlight",{},1000);
						$('.format').hide();
						$('.dirstructure').hide();
					}
					else if ((dest == 'ndar') || (dest == 'ndarcsv')) {
						$('.export').hide();
						$('.format').hide();
						$('.dirstructure').hide();
						$('.datatoexport').hide();
					}
					else {
						$('.export').hide();
						$('.format').show("highlight",{},1000);
						$('.dirstructure').show("highlight",{},1000);
						$('.datatoexport').show("highlight",{},1000);
					}
					
					/* remote ftp */
					if (dest == 'remoteftp') {
						$('.remoteftp').show("highlight",{},1000);
					}
					else {
						$('.remoteftp').hide();
					}
					
					/* remote nidb */
					if (dest == 'remotenidb') {
						$('.remotenidb').show("highlight",{},1000);
						$('.export').hide();
						$('.dirstructure').hide();
						$('.format').hide();
						$('.datatoexport').hide();
					}
					else {
						$('.remotenidb').hide();
					}
					
					/* XNAT */
					if (dest == 'xnat') {
						$('.xnat').show("highlight",{},1000);
						$('.export').hide();
						$('.dirstructure').hide();
						$('.format').hide();
						$('.datatoexport').hide();
					}
					else {
						$('.xnat').hide();
					}
					
					/* public download */
					if (dest == 'publicdownload') {
						$('.publicdownload').show("highlight",{},1000);
					}
					else {
						$('.publicdownload').hide();
					}

					var filetype = $("[name='filetype']:checked").val();
					//var filetype = $('#filetype:checked').val();
					//console.log(filetype);

					if (filetype == 'dicom') {
						$('.dicom').show();
						$('.bids').hide();
						$('.squirrel').hide();
						//$('.dirstructure').show();
					}
					else if (filetype == 'bids') {
						$('.bids').show();
						$('.dicom').hide();
						$('.squirrel').hide();
						$('.dirstructure').hide();
					}
					else if (filetype == 'squirrel') {
						$('.squirrel').show();
						$('.bids').hide();
						$('.dicom').hide();
						$('.dirstructure').hide();
					}
					else {
						$('.dicom').hide();
						$('.bids').hide();
						$('.squirrel').hide();
						//$('.dirstructure').show();
					}
					
					if ($('#downloadbeh:checked').val() == '1') {
					//	/* hide all ... */
					//	$('#sectionformat').hide();
					//	$('#sectiondirstructure').hide();
						$('.beh').show();
					}
					else {
						$('.beh').hide();
					}
					//	/* ... then show the appropriate sections */
					//	if ($('#downloadimaging:checked').val() == '1') {
					//		$('#sectionformat').show();
					//		$('#sectiondirstructure').show();
					//	}
					//	if ($('#downloadbeh:checked').val() == '1') {
					//		$('.beh').show();
					//		$('#sectiondirstructure').show();
					//	}
					//});
					
				}
			</script>
			
			<div class="ui container">
				<h3 class="ui top attached header secondary inverted black segment">
					Operations
				</h3>
				<div class="ui bottom attached segment">
					<h4 class="ui header">With Selected Studies:</h4>
					<div class="ui left aligned horizontal divider">Add to group</div>
					<div class="ui labeled action input">
						<label for="subjectgroupid" class="ui label" style="width: 150px">Subject Group</label>
						<select name="subjectgroupid" id="subjectgroupid" class="ui selection dropdown">
						<?
							$sqlstring = "select * from groups where group_type = 'subject' order by group_name";
							$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$groupid = $row['group_id'];
								$groupname = $row['group_name'];
								?>
								<option value="<?=$groupid?>"><?=$groupname?>
								<?
							}
						?>
						</select>
						<div class="ui primary button" name="addtogroup" onclick="document.subjectlist.action='groups.php';document.subjectlist.action.value='addsubjectstogroup';document.subjectlist.submit();">Add</div>
					</div>
					<br><br>
					<div class="ui labeled action input">
						<label for="studygroupid" class="ui label" style="width: 150px">Study Group</label>
						<select name="studygroupid" id="studygroupid" class="ui selection dropdown">
						<?
							$sqlstring = "select * from groups where group_type = 'study' order by group_name";
							$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$groupid = $row['group_id'];
								$groupname = $row['group_name'];
								?>
								<option value="<?=$groupid?>"><?=$groupname?>
								<?
							}
						?>
						</select>
						<div class="ui primary button" name="addtogroup" onclick="document.subjectlist.action='groups.php';document.subjectlist.action.value='addstudiestogroup';document.subjectlist.submit();">Add</div>
					</div>
					<br><br>
					<div class="ui labeled action input">
						<label for="seriesgroupid" class="ui label" style="width: 150px">Series Group</label>
						<select name="seriesgroupid" id="seriesgroupid" class="ui selection dropdown">
						<?
							$sqlstring = "select * from groups where group_type = 'series' order by group_name";
							$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$groupid = $row['group_id'];
								$groupname = $row['group_name'];
								?>
								<option value="<?=$groupid?>"><?=$groupname?>
								<?
							}
						?>
						</select>
						<div class="ui primary button" name="addtogroup" onclick="document.subjectlist.action='groups.php';document.subjectlist.action.value='addseriestogroup';document.subjectlist.submit();">Add</div>
					</div>
					<div class="ui horizontal left aligned divider">Mini-pipelines</div>
					<div class="ui action input">
						<?
							if (count($projectids) > 0) {
								$projectids2 = array_unique(array_keys($projectids));
								$projidlist = implode2(',', $projectids2);
								$mpselectbox = "<select name='minipipelineid' class='ui selection dropdown'><option value='0' selected>(none)";
								$sqlstring = "select * from minipipelines a left join projects b on a.project_id = b.project_id where a.project_id in ($projidlist) order by b.project_name, a.mp_name";
								//PrintSQL($sqlstring);
								$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
								while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
									$mpid = $row['minipipeline_id'];
									$mpversion = $row['mp_version'];
									$mpname = $row['mp_name'];
									$projectname = $row['project_name'];
									$mpselectbox .= "<option value='".$mpid."'>$projectname - $mpname (v$mpversion)";
								}
								$mpselectbox .= "</select>";
							}
						?>
						<?=$mpselectbox?>
						<div class="ui primary button" onclick="document.subjectlist.action='studies.php';document.subjectlist.action.value='submitminipipelines';document.subjectlist.submit();">Run</div>
					</div>
					<div class="ui horizontal left aligned divider">Batch Upload Data</div>
					<div class="ui primary button" onclick="document.subjectlist.action='batchupload.php';document.subjectlist.action.value='displaystudylist';document.subjectlist.submit();">Upload</div>
				</div>
			
				<br><br>
			
				<h3 class="ui top attached header secondary inverted black segment">
					Transfer & Export Data
				</h3>
				<div class="ui bottom attached segment">
					<div class="ui horizontal left aligned divider header">Destination</div>
					<div class="ui grid">
						<div class="two wide column">&nbsp;</div>
						<div class="four wide column">
							<h4 class="ui header">This server</h4>
							<div class="ui vertically fitted basic segment">
								<? if ($GLOBALS['cfg']['enablewebexport']) { ?>
								<div class="ui radio checkbox" onChange="CheckDestination()">
									<input type="radio" name="destination" id="radio_web" value="web">
									<label title="Export can be downloaded from this website">Web</label>
								</div>
								<br>
								<? } ?>
								<? if (($GLOBALS['isadmin']) && ($GLOBALS['cfg']['enablepublicdownloads'])) { ?>
								<div class="ui radio checkbox" onChange="CheckDestination()">
									<input type="radio" name="destination" id="radio_publicdownload" value="publicdownload">
									<label title="Export can be downloaded by anyone, through the public downloads section">Public Download</label>
								</div>
								<br>
								<div class="ui segment publicdownload">
									<div class="field">
										Short description
										<input type="text" name="publicdownloaddesc" maxlength="255">
										<span class="tiny">Max 255 chars</span>
									</div>

									<div class="field">
										Release notes
										<textarea name="publicdownloadreleasenotes"></textarea>
									</div>

									<div class="field">
										Password <img src="images/help.gif" title="Set a password for the download link, otherwise anyone with the link can download the data. Leave blank for no password">
										<input type="password" name="publicdownloadpassword">
									</div>

									<div class="field">
										<div class="ui checkbox">
											<input type="checkbox" name="publicdownloadshareinternal" value="1">
											<label>Share download within this system <i class="small blue question circle outline icon" title="This option allows other users (users within this system, not public users) to modify or delete this public download"></i></label>
										</div>
									</div>

									<div class="field">
										<div class="ui checkbox">
											<input type="checkbox" name="publicdownloadregisterrequired" value="1" checked>
											<label>Require registration <i class="small blue question circle outline icon" title="If selected, anyone downloading the files must create an account on NiDB before downloading the file. Useful to keep track of who downloads this download"></i></label>
										</div>
									</div>

									<div class="field">
										Expiration Date <i class="small blue question circle outline icon" title="Time after creating the download when it will be deleted from the system and become unavailable for download"></i>
										<br>
										<div class="ui radio checkbox">
											<input type="radio" name="publicdownloadexpire" value="7" checked>
											<label>7 days</label>
										</div>
										<br>
										<div class="ui radio checkbox">
											<input type="radio" name="publicdownloadexpire" value="30">
											<label>30 days</label>
										</div>
										<br>
										<div class="ui radio checkbox">
											<input type="radio" name="publicdownloadexpire" value="90">
											<label>90 days</label>
										</div>
										<br>
										<div class="ui radio checkbox">
											<input type="radio" name="publicdownloadexpire" value="0">
											<label>No expiration</label>
										</div>
										<br>
									</div>
								</div>
							<?
							}
							if ($s_resultoutput != 'subject') {
								if (!$GLOBALS['cfg']['ispublic']) {
									?>
									<div class="ui radio checkbox" onChange="CheckDestination()">
										<input type="radio" name="destination" id="radio_localftp" value="localftp" <? if ($GLOBALS['isguest']) { echo "checked"; } ?>>
										<label>Local FTP/scp</label>
									</div>
									<br><?
								}

								if ($GLOBALS['cfg']['enablerdoc']) {
									?>
									<div class="ui radio checkbox" onChange="CheckDestination()">
										<input type="radio" name="destination" id="radio_ndar" value="ndar">
										<label>NDAR/RDoC submission</label>
									</div>
									<br>
									<div class="ui radio checkbox" onChange="CheckDestination()">
										<input type="radio" name="destination" id="radio_ndarcsv" value="ndarcsv">
										<label>NDAR/RDoC submission <span class="tiny">.csv</span></label>
									</div>
									<br>
									<?
								}
							}
							?>
							</div>
							
						</div>
						<div class="ten wide column">
							<h4 class="ui header">Remote server</h4>
							<div class="ui vertically fitted basic segment">
								<?
								if ($s_resultoutput != 'subject') {
									?>
									<script>
										function CheckNFSPath() {
											var xhttp = new XMLHttpRequest();
											xhttp.onreadystatechange = function() {
												if (this.readyState == 4 && this.status == 200) {
													document.getElementById("pathcheckresult").innerHTML = this.responseText;
												}
											};
											var nfsdir = document.getElementById("nfsdir").value;
											//alert(nfsdir);
											xhttp.open("GET", "ajaxapi.php?action=validatepath&nfspath=" + nfsdir, true);
											xhttp.send();
										}
									</script>
									<div class="ui radio checkbox" onChange="CheckDestination()">
										<input type="radio" name="destination" id="radio_nfs" value="nfs" checked>
										<label>Linux NFS Mount</label>
									</div>
									<div class="ui fluid input">
										<input type="text" id="nfsdir" name="nfsdir" onKeyUp="CheckNFSPath()" placeholder="NFS path..." onFocus="document.getElementById('radio_nfs').checked=true"><span id="pathcheckresult"></span>
									</div>
									<br>
									<div class="ui radio checkbox" onChange="CheckDestination()">
										<input type="radio" name="destination" id="radio_xnat" value="xnat">
										<label>Remote XNAT</label>
									</div>
									<br>
									<div class="ui radio checkbox" onChange="CheckDestination()">
										<input type="radio" name="destination" id="radio_remoteftp" value="remoteftp">
										<label>Remote FTP site</label>
									</div>
									<table class="remoteftp" style="margin-left:40px; border:1px solid gray">
										<tr><td align="right" width="30%" style="font-size:10pt">Remote FTP Server</td><td><input type="text" name="remoteftpserver"></td></tr>
										<tr><td align="right" width="30%" style="font-size:10pt">Remote Directory</td><td><input type="text" name="remoteftppath"></td></tr>
										<tr><td align="right" width="30%" style="font-size:10pt">Username</td><td><input type="text" name="remoteftpusername"></td></tr>
										<tr><td align="right" width="30%" style="font-size:10pt">Password</td><td><input type="text" name="remoteftppassword"></td></tr>
										<tr><td align="right" width="30%" style="font-size:10pt">Port number</td><td><input type="text" name="remoteftpport" value="21" size="5"></td></tr>
									</table>
									<br>
									<? if ($GLOBALS['cfg']['enableremoteconn']) { ?>
									<div class="ui radio checkbox" onChange="CheckDestination()">
										<input type="radio" name="destination" id="radio_remotenidb" value="remotenidb">
										<label>Remote NiDB site</label>
									</div>
									<select name="remoteconnid" class="remotenidb">
										<option value="">(Select connection)</option>
										<?
											$sqlstring = "select * from remote_connections where user_id = (select user_id from users where username = '" . $GLOBALS['username'] . "') order by conn_name";
											$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
											while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
												$connid = $row['remoteconn_id'];
												$connname = $row['conn_name'];
												$remoteserver = $row['remote_server'];
												$remoteusername = $row['remote_username'];
												$remotepassword = $row['remote_password'];
												$remoteinstanceid = $row['remote_instanceid'];
												$remoteprojectid = $row['remote_projectid'];
												$remotesiteid = $row['remote_siteid'];
												?>
												<option value="<?=$connid?>"><?=$connname?> - [<?=$remoteusername?>@<?=$remoteserver?> Project: <?=$remoteprojectid?>]
												<?
											}
										?>
									</select>
									<?
									}
								}
								?>
							</div>
						</div>
					</div>
					<div class="ui horizontal left aligned divider header">Data</div>
					<div class="ui grid">
						<div class="two wide column">&nbsp;</div>
						<div class="fourteen wide column">

							<div class="ui basic vertically fitted segment datatoexport" id="sectiondatatype">
								<div class="ui checkbox" style="padding: 3px">
									<input type="checkbox" name="downloadimaging" id="downloadimaging" value="1" checked onChange="CheckDestination()">
									<label>Imaging</label>
								</div>
								<br>
								<div class="ui checkbox" style="padding: 3px">
									<input type="checkbox" name="downloadbeh" id="downloadbeh" value="1" checked onChange="CheckDestination()">
									<label>Behavioral</label>
								</div>
								<!--<br>
								<div class="ui checkbox" style="padding: 3px">
									<input type="checkbox" name="downloadqc" id="downloadqc" value="1" onChange="CheckDestination()">
									<label>QC <i class="small blue question circle outline icon" title="Includes all QC metrics computed on the data"></i></label>
								</div>
								<br>
								<div class="ui checkbox" style="padding: 3px">
									<input type="checkbox" name="downloaddemo" id="downloaddemo" value="1" onChange="CheckDestination()">
									<label>Demographics <i class="small blue question circle outline icon" title="Includes age at scan, sex, and other demographics. This is places in a demographics.txt file in the root of the download directory"></i></label>
								</div>-->
								<br>
								<div class="ui checkbox" style="padding: 3px">
									<input type="checkbox" name="downloadexperiments" id="downloadexperiments" value="1" onChange="CheckDestination()">
									<label>Experiments</label>
								</div>
								<br>
								<div class="ui checkbox" style="padding: 3px">
									<input type="checkbox" name="downloadresults" id="downloadresults" value="1" onChange="CheckDestination()">
									<label>Analysis Results</label>
								</div>
								<br>
								<div class="ui checkbox" style="padding: 3px">
									<input type="checkbox" name="downloadpipelines" id="downloadpipelines" value="1" onChange="CheckDestination()">
									<label>Pipelines</label>
								</div>
								<br>
								<div class="ui checkbox" style="padding: 3px">
									<input type="checkbox" name="downloadvariables" id="downloadvariables" value="1" onChange="CheckDestination()">
									<label>Variables</label>
								</div>
								<br>
								<div class="ui checkbox" style="padding: 3px">
									<input type="checkbox" name="downloadminipipelines" id="downloadminipipelines" value="1" onChange="CheckDestination()">
									<label>Mini-Pipelines</label>
								</div>
							</div>

						</div>
					</div>
					
					<div class="ui horizontal left aligned divider header">Format</div>
					<div class="ui grid">
						<div class="two wide column">&nbsp;</div>
						<div class="fourteen wide column">
							<? if (strtolower($s_studymodality) == "mr") { ?>
							<div class="ui basic vertically fitted segment format" id="sectionformat">

								<span class="tiny">Nifti conversion only available if native data in DICOM or par/rec format</span>
								<br>
								<div class="ui radio checkbox">
									<input type="radio" name="filetype" id="filetype_nifti3d" value="nifti3d" checked onChange="CheckDestination()">
									<label>Nifti 3D</label>
								</div>
								<br>
								<div class="ui radio checkbox">
									<input type="radio" name="filetype" id="filetype_nifti4d" value="nifti4d" onChange="CheckDestination()">
									<label>Nifti 4D</label>
								</div>
								<br>
								<div class="ui checkbox" style="padding-left: 15px">
									<input type="checkbox" name="gzip" value="1" onChange="CheckDestination()">
									<label>Gzip nifti</label>
								</div>
								<br>
								<br>
								<div class="ui radio checkbox">
									<input type="radio" name="filetype" id="filetype_dicom" value="dicom" onChange="CheckDestination()">
									<label>DICOM <span class="tiny">or original format if non-DICOM</span></label>
								</div>
								<div class="dicom" style="padding-left: 15px;">
									<div class="ui two column grid">
										<div class="ui column">
											<? if ($GLOBALS['cfg']['allowrawdicomexport']) { ?>
											<div class="ui radio checkbox">
												<input type="radio" name="anonymize" value="0" onChange="CheckDestination()">
												<label>No DICOM anonymization</label>
											</div>
											<br>
											<? } ?>
											<div class="ui radio checkbox">
												<input type="radio" name="anonymize" value="1" checked onChange="CheckDestination()">
												<label>Anonymize DICOM - <i>light</i></label>
											</div>
											<br>
											<div class="ui radio checkbox">
												<input type="radio" name="anonymize" value="2" onChange="CheckDestination()">
												<label>Anonymize DICOM - <i>complete</i></label>
											</div>
										</div>

										<div class="ui column">
											<div class="ui styled accordion">
												<div class="title">
													<i class="dropdown icon"></i>
													Anonymization Notes
												</div>
												<div class="content">
													<ul>
													<? if ($GLOBALS['cfg']['allowrawdicomexport']) { ?>
													<li>No DICOM anonymization - not recommended
													<? } ?>
													<li>DICOM anonymization <u>partial</u> removes:
														<ul>
															<li style="white-space: nowrap"><code>0008,0090</code> ReferringPhysiciansName
															<li style="white-space: nowrap"><code>0008,1050</code> PerformingPhysiciansName
															<li style="white-space: nowrap"><code>0008,1070</code> OperatorsName
															<li style="white-space: nowrap"><code>0010,0010</code> PatientName
															<li style="white-space: nowrap"><code>0010,0030</code> PatientBirthDate
														</ul>
													<li>DICOM anonymization <u>complete</u> removes all of the above and the following:
														<ul>
															<li style="white-space: nowrap"><code>0008,0080</code> InstitutionName
															<li style="white-space: nowrap"><code>0008,0081</code> InstitutionAddress
															<li style="white-space: nowrap"><code>0008,1010</code> StationName
															<li style="white-space: nowrap"><code>0008,1030</code> StudyDescription
															<li style="white-space: nowrap"><code>0008,0020</code> StudyDate
															<li style="white-space: nowrap"><code>0008,0021</code> SeriesDate
															<li style="white-space: nowrap"><code>0008,0022</code> AcquisitionDate
															<li style="white-space: nowrap"><code>0008,0023</code> ContentDate
															<li style="white-space: nowrap"><code>0008,0030</code> StudyTime
															<li style="white-space: nowrap"><code>0008,0031</code> SeriesTime
															<li style="white-space: nowrap"><code>0008,0032</code> AcquisitionTime
															<li style="white-space: nowrap"><code>0008,0033</code> ContentTime
															<li style="white-space: nowrap"><code>0010,0020</code> PatientID
															<li style="white-space: nowrap"><code>0010,1030</code> PatientWeight
														</ul>
													</span>
													</ul>
												</div>
											</div>
										</div>
									</div>
								</div>
								
								<br>
								<div class="ui radio checkbox">
									<input type="radio" name="filetype" id="filetype_bids" value="bids" onChange="CheckDestination()">
									<label>BIDS</label>
								</div>
								<br>
								<div class="ui segment bids">
									<h4 class="ui dividing header">BIDS options</h4>
									<div class="field">
										<div class="ui checkbox">
											<input type="checkbox" name="bidsflag_useuid">
											<label>UID instead of sub-0001</label>
										</div>
										<br>
										<div class="ui checkbox">
											<input type="checkbox" name="bidsflag_usestudyid">
											<label>StudyNum instead of ses-0001</label>
										</div>
									</div>
									<div class="field">
										<label>Readme</label>
										<textarea name="bidsreadme" class="bids" placeholder="BIDS README file..." cols="40" rows="3"></textarea>
									</div>
								</div>
								<div class="ui radio checkbox">
									<input type="radio" name="filetype" id="filetype_squirrel" value="squirrel" onChange="CheckDestination()">
									<label>Squirrel</label>
								</div>
								<br>
								<div class="ui grey segment squirrel">
									<h4 class="ui dividing header">Squirrel options</h4>
									<div class="field">
										<div class="ui radio checkbox">
											<input type="radio" name="squirrelflag_metadata" value="subject" checked>
											<label>Metadata from subject</label>
										</div>
										<br>
										<div class="ui radio checkbox">
											<input type="radio" name="squirrelflag_metadata" value="enrollment">
											<label>Metadata from enrollment</label>
										</div>
									</div>
									<div class="field">
										<div class="ui checkbox">
											<input type="checkbox" name="squirrelflag_anonymize" value="1" checked>
											<label>Anonymize</label>
										</div>
									</div>
									<div class="field">
										<div class="ui checkbox">
											<input type="checkbox" name="squirrelflag_incstudy" value="1">
											<label>Use incremental study numbers</label>
										</div>
									</div>
									<div class="field">
										<div class="ui checkbox">
											<input type="checkbox" name="squirrelflag_incseries" value="1">
											<label>Use incremental series numbers</label>
										</div>
									</div>
									<div class="field">
										<label>Title</label>
										<input type="text" name="squirreltitle" placeholder="Squirrel package name...">
									</div>
									<div class="field">
										<label>Description</label>
										<textarea name="squirreldesc" placeholder="Squirrel package description..." cols="40" rows="3"></textarea>
									</div>
								</div>
							</div>
							<? } ?>
						</div>
					</div>

					<div class="ui horizontal left aligned divider header">Directory Structure</div>
					<div class="ui grid">
						<div class="two wide column">&nbsp;</div>
						<div class="fourteen wide column dirstructure">

							<div class="ui segment">
								<span class="ui blue text"><b>Study Directory Format</b></span>
								<br><br>
								<div class="ui very compact grid">
									<div class="three wide column">
										<div class="field">
											<div class="ui radio checkbox" onChange="HighlightStudyDir()">
												<input type="radio" name="dirformat" id="dirformat_shortid" value="shortid" checked>
												<label>Study ID</label>
											</div>
										</div>
									</div>
									<div class="thirteen wide column">
										<div class="ui red left pointing label" id="label_shortid">
											<tt>S1234ABC1</tt>
										</div>
									</div>
									
									<div class="three wide column">
										<div class="field">
											<div class="ui radio checkbox" onChange="HighlightStudyDir()">
												<input type="radio" name="dirformat" id="dirformat_shortstudyid" value="shortstudyid">
												<label>UID w/subdir</label>
											</div>
										</div>
									</div>
									<div class="thirteen wide column">
										<div class="ui left pointing label" id="label_shortstudyid">
											<tt>S1234ABC/1</tt>
										</div>
									</div>

									<div class="three wide column">
										<div class="field">
											<div class="ui radio checkbox" onChange="HighlightStudyDir()">
												<input type="radio" name="dirformat" id="dirformat_altuid" value="altuid">
												<label>Primary alternate subject ID<br><span class="tiny">With incremental study numbers</span></label>
											</div>
										</div>
									</div>
									<div class="thirteen wide column">
										<div class="ui left pointing label" id="label_altuid">
											<tt>23505/1<br>23505/2</tt>
										</div>
									</div>

									<div class="three wide column">
										<div class="field">
											<div class="ui radio checkbox" onChange="HighlightStudyDir()">
												<input type="radio" name="dirformat" id="dirformat_visittype" value="visittype">
												<label>Visit type</label>
											</div>
										</div>
									</div>
									<div class="thirteen wide column">
										<div class="ui left pointing label" id="label_visittype">
											<tt>S1234ABC/visit1</tt>
										</div>
									</div>

									<div class="three wide column">
										<div class="field">
											<div class="ui radio checkbox" onChange="HighlightStudyDir()">
												<input type="radio" name="dirformat" id="dirformat_daynum" value="daynum">
												<label>Day number</label>
											</div>
										</div>
									</div>
									<div class="thirteen wide column">
										<div class="ui left pointing label" id="label_daynum">
											<tt>S1234ABC/day1</tt>
										</div>
									</div>
									
									<div class="three wide column">
										<div class="field">
											<div class="ui radio checkbox" onChange="HighlightStudyDir()">
												<input type="radio" name="dirformat" id="dirformat_timepoint" value="timepoint">
												<label>Timepoint</label>
											</div>
										</div>
									</div>
									<div class="thirteen wide column">
										<div class="ui left pointing label" id="label_timepoint">
											<tt>S1234ABC/time1</tt>
										</div>
									</div>

									<? if ($s_resultoutput == 'long') { ?>
										<input type="radio" name="dirformat" value="longitudinal">Longitudinal
										<tt>S1234ABC<br>&nbsp;&nbsp;&nbsp;&#8627;&nbsp;time1<br>&nbsp;&nbsp;&nbsp;&#8627;&nbsp;time2</tt>
									<? } ?>
								</div>
							</div>

							<div class="ui segment">
								<span class="ui blue text"><b>Series Directory Format</b></span>
								<br><br>
								<div class="field">
									<div class="ui radio checkbox" onChange="HighlightSeriesDir()">
										<input type="radio" name="preserveseries" value="1" checked>
										<label>Preserve series number</label>
									</div>
									<div class="ui red left pointing label" id="label_preserveseries1">
										<tt>8 9 10 &rarr; 8 9 10</tt>
									</div>
								</div>
									
								<div class="field">
									<div class="ui radio checkbox" onChange="HighlightSeriesDir()">
										<input type="radio" name="preserveseries" value="0">
										<label>Renumber series</label>
									</div>
									<div class="ui left pointing label" id="label_preserveseries0">
										<tt>8 9 10 &rarr; 1 2 3</tt>
									</div>
								</div>
									
								<div class="field">
									<div class="ui radio checkbox" onChange="HighlightSeriesDir()">
										<input type="radio" name="preserveseries" value="2">
										<label>Use protocol name <i class="small blue question circle outline icon" title="Characters other than numbers and letters are replaced with underscores"></i></label>
									</div>
									<div class="ui left pointing label" id="label_preserveseries2">
										<tt>1 &nbsp;2 &nbsp;3 &nbsp;&rarr; &nbsp;Localizer &nbsp;Resting &nbsp;Task_A</tt>
									</div>
								</div>
									
								<div class="field">
									<div class="ui radio checkbox" onChange="HighlightSeriesDir()">
										<input type="radio" name="preserveseries" value="3">
										<label>ABIDE format</label>
									</div>
									<div class="ui left pointing label" id="label_preserveseries3">
										<tt>1 &nbsp;2 &nbsp;3 &nbsp;&rarr; &nbsp;anat_1 &nbsp;anat_2 &nbsp;anat_3</tt>
									</div>
								</div>
							</div>
							
							<div class="ui segment beh">
								<? if ($s_studymodality == "mr") { ?>
									<span class="ui blue text"><b>Behavioral Data</b></span>
									<br><br>
									<div class="field">
										<div class="ui radio checkbox" onChange="HighlightBehDir()">
											<input type="radio" name="behformat" value="behroot">
											<label>Place in in root</label>
										</div>
										<div class="ui left pointing label" id="label_behroot">
											<tt>S1234ABC/file.log</tt>
										</div>
									</div>

									<div class="field">
										<div class="ui radio checkbox" onChange="HighlightBehDir()">
											<input type="radio" name="behformat" value="behrootdir" checked>
											<label>Place in root directory</label>
										</div>
										<input type="text" name="behdirnameroot" value="beh" style="width: 90px; padding: 2px; vertical-align: middle">
										<div class="ui left pointing label" id="label_behrootdir">
											<tt>S1234ABC/beh/file.log</tt>
										</div>
									</div>

									<div class="field">
										<div class="ui radio checkbox" onChange="HighlightBehDir()">
											<input type="radio" name="behformat" value="behseries" checked>
											<label>Place in series directory</label>
										</div>
										<div class="ui left pointing label" id="label_behseries">
											<tt>S1234ABC/2/file.log</tt>
										</div>
									</div>
									
									<div class="field">
										<div class="ui radio checkbox" onChange="HighlightBehDir()">
											<input type="radio" name="behformat" value="behseriesdir" checked>
											<label>Place in series root directory</label>
										</div>
										<input type="text" name="behdirnameseries" value="beh" style="width: 90px; padding: 2px; vertical-align: middle">
										<div class="ui red left pointing label" id="label_behseriesdir">
											<tt>S1234ABC/2/beh/file.log</tt>
										</div>
									</div>
								<? } ?>
							</div>
						</div>
					</div>
					<div class="segment">
						<input class="ui primary button" type="submit" name="download" value="Transfer" onclick="document.subjectlist.action='search.php';document.subjectlist.action.value='submit'">
					</div>
				</div>
			</div>
		<?
	}
	

	/* -------------------------------------------- */
	/* ------- BuildSQLString --------------------- */
	/* -------------------------------------------- */
	function BuildSQLString($s) {
		
		Debug(__FILE__, __LINE__, "<pre>" . print_r($s,true) . "</pre>");
		
		/* escape all the variables and put them back into meaningful variable names */
		foreach ($s as $key => $value) {
			if (is_array($value)) {
				//PrintVariable($value);
				$$key = mysqli_real_escape_array($s[$key]);
			}
			elseif (is_scalar($value)) {
				$$key = trim(mysqli_real_escape_string($GLOBALS['linki'], $s[$key]));
			}
			else {
				$$key = $s[$key];
			}
		}

		/* make modality lower case to conform with table names... MySQL table names are case sensitive when using the 'show tables' command */
		$s_studymodality = strtolower($s_studymodality);
		$modality = $s_studymodality;
		/* also make a variable for the series table */
		$modalitytable = $s_studymodality . "_series";

		/* check if modality_series table actually exists */
		$sqlstring = "show tables from " . $GLOBALS['cfg']['mysqldatabase'] . " like '$modalitytable'";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if (mysqli_num_rows($result) < 1) {
			?>
			<?=$modality?>_series table does not exist. Unable to query information about <?=$modality?> series
			<?
			return "";
		}
		
		/* determine which fields have criteria, build the where clause */
		if (($s_subjectuid != "") || ($s_subjectgroupid != "")) {
			if ($s_subjectgroupid != "") {
				$ids = GetIDListFromGroup($s_subjectgroupid);
				$sqlwhere .= " and `subjects`.subject_id in (" . $ids . ")";
			}
			else {
				if (preg_match('/[\^\,;\-\'\s]/', $s_subjectuid) == 0) {
					$sqlwhere .= " and `subjects`.uid = '$s_subjectuid'";
				}
				else {
					$sqlwhere .= " and `subjects`.uid in (" . MakeSQLList($s_subjectuid) . ")";
				}
			}
		}
		if ($s_subjectaltuid != "") {
			if (preg_match('/[\^\,;\-\'\s]/', $s_subjectaltuid) == 0) {
				$sqlwhere .= "and `subject_altuid`.altuid like '%$s_subjectaltuid%'";
			}
			else {
				$sqlwhere .= "and `subject_altuid`.altuid in (" . MakeSQLList($s_subjectaltuid) . ")";
			}
		}
		if ($s_subjectname != "") { $sqlwhere .= " and `subjects`.name like '%$s_subjectname%'"; }
		if ($s_subjectdobstart != "") { $sqlwhere .= " and `subjects`.birthdate >= '$s_subjectdobstart'"; }
		if ($s_subjectdobend != "") { $sqlwhere .= " and `subjects`.birthdate <= '$s_subjectdobend'"; }
		if ($s_ageatscanmin != "") { $sqlwhere .= " and `studies`.study_ageatscan >= '$s_ageatscanmin'"; }
		if ($s_ageatscanmax != "") { $sqlwhere .= " and `studies`.study_ageatscan <= '$s_ageatscanmax'"; }
		if ($s_subjectgender != "") { $sqlwhere .= " and `subjects`.gender = '$s_subjectgender'"; }
		//PrintVariable($s_projectids);
		if ((!in_array("all", $s_projectids) && (count($s_projectids) != 0))) {
			$sqlwhere .= " and `projects`.project_id in (" . implode2(",", $s_projectids) . ")";
		}
		else {
			$tmpsqlstring = "select project_id from projects where instance_id = '" . $_SESSION['instanceid'] . "'";		
			$tmpresult = MySQLiQuery($tmpsqlstring,__FILE__,__LINE__);
			while ($tmprow = mysqli_fetch_array($tmpresult, MYSQLI_ASSOC)) {
				if ($tmprow['project_id'] != "") {
					$projectids[] = $tmprow['project_id'];
				}
			}
			$projectidlist = implode(",",$projectids);
			$sqlwhere .= " and `projects`.project_id in ($projectidlist)";
		}
		if ($s_enrollsubgroup != "") { $sqlwhere .= " and `enrollment`.enroll_subgroup = '$s_enrollsubgroup'"; }
		if ($s_studygroupid != "") {
			$studyids = GetIDListFromGroup($s_studygroupid);
			$sqlwhere .= " and `studies`.study_id in (" . $studyids . ")";
		}
		if ($s_studyinstitution != "") { $sqlwhere .= " and `studies`.study_institution like '%$s_studyinstitution%'"; }
		if ($s_studyequipment != "") { $sqlwhere .= " and `studies`.study_site like '%$s_studyequipment%'"; }
		if ($s_studyid != "") {
			/* check for any kind of delimiter, indicating more than one ID */
			$s1 = array();
			$arr = DelimitedListToArray($s_studyid);
			foreach ($arr as $sid) {
				/* first 8 characters are UID, remaining number(s) are studynum */
				//PrintVariable($sid);
				$uid = substr($sid,0,8);
				$studynum = substr($sid,8) + 0; // convert to integer
				$s1[] = "(`subjects`.uid = '$uid' and `studies`.study_num = $studynum)";
			}
			$s2 = implode2(' or ', $s1);
			$sqlwhere .= " and ($s2)";
		}
		if ($s_studyaltscanid != "") {
			if (preg_match('/[\^\,;\-\'\s]/', $s_studyaltscanid) == 0) {
				$sqlwhere .= " and `studies`.study_alternateid like '%$s_studyaltscanid%'";
			}
			else {
				$sqlwhere .= " and `studies`.study_alternateid in (" . MakeSQLList($s_studyaltscanid) . ")";
			}
		}
		if ($s_studydatestart != "") { $sqlwhere .= " and `studies`.study_datetime >= '$s_studydatestart 00:00:00'"; }
		if ($s_studydateend != "") { $sqlwhere .= " and `studies`.study_datetime <= '$s_studydateend 23:59:59'"; }
		if ($s_studydesc != "") { $sqlwhere .= " and `studies`.study_desc like '%$s_studydesc%'"; }
		if ($s_studyphysician != "") { $sqlwhere .= " and `studies`.study_performingphysician like '%$s_studyphysician%'"; }
		if ($s_studyoperator != "") { $sqlwhere .= " and `studies`.study_operator like '%$s_studyoperator%'"; }
		if ($s_studytype != "") { $sqlwhere .= " and `studies`.study_type like '%$s_studytype%'"; }
		if ($s_seriesgroupid != "") {
			$seriesids = GetIDListFromGroup($s_seriesgroupid);
			$sqlwhere .= " and `$modalitytable`.$modality" . "series_id in (" . $seriesids . ")";
		}
		if (($s_seriesdesc != "") && ($s_pipelineid == "")) {
			$sqlwhere .= " and (";
			/* if it contains a comma, the search will be OR */
			//if (strpos($s_seriesdesc,',') !== false) {
				$seriesdescs = explode(',',$s_seriesdesc);
				$wheres = array();
				foreach ($seriesdescs as $seriesdesc) {
					if ($s_usealtseriesdesc) {
						$wheres[] = "(`$modalitytable`.series_altdesc like '%" . trim($seriesdesc) . "%')";
					}
					else {
						/* protocol name for MR is stored in series_desc, all other modalities is series_protocol */
						if ($modality == "mr") {
							$wheres[] = "(`$modalitytable`.series_desc like '%" . trim($seriesdesc) . "%')";
						}
						else {
							$wheres[] = "(`$modalitytable`.series_protocol like '%" . trim($seriesdesc) . "%')";
						}
					}
				}
				$sqlwhere .= implode(" or ", $wheres);
			//}
			//else {
				/* otherwise the search is an AND */
				//$seriesdescs = explode(';',$s_seriesdesc);
				//$wheres = array();
				//foreach ($seriesdescs as $seriesdesc) {
				//	$wheres[] = "(`$modalitytable`.series_desc like '%" . trim($seriesdesc) . "%')";
				//}
				//$sqlwhere .= implode(" and ", $wheres);
			//}
			
			$sqlwhere .= ")";
		}
		if ($s_seriessequence != "") { $sqlwhere .= " and `$modalitytable`.series_sequencename like '%$s_seriessequence%'"; }
		if ($s_seriesimagetype != "") {
		
			$sqlwhere .= " and (";
			$seriesimagetypes = explode(',',$s_seriesimagetype);
			$wheres = array();
			foreach ($seriesimagetypes as $seriesimagetype) {
				if (strpos($seriesimagetype,'*') !== false) {
					$seriesimagetype = str_replace('*','%',$seriesimagetype);
					$wheres[] = "(`$modalitytable`.image_type like '" . trim($seriesimagetype) . "')";
				}
				else {
					$wheres[] = "(`$modalitytable`.image_type = '" . trim($seriesimagetype) . "')";
				}
			}
			$sqlwhere .= implode(" or ", $wheres);
			$sqlwhere .= ")";
		
			//$sqlwhere .= " and `$modalitytable`.image_type like '%$s_seriesimagetype%'";
		}
		if ($s_seriesimagecomments != "") { $sqlwhere .= " and `$modalitytable`.image_comments like '%$s_seriesimagecomments%'"; }
		if ($s_seriestr != "") { $sqlwhere .= " and `$modalitytable`.series_tr = '$s_seriestr'"; }
		if ($s_seriesnum != "") {
			if (substr($s_seriesnum,0,2) == '>=') {
				$val = substr($s_seriesnum,2);
				$sqlwhere .= " and `$modalitytable`.series_num >= '$val'";
			}
			elseif (substr($s_seriesnum,0,2) == '<=') {
				$val = substr($s_seriesnum,2);
				$sqlwhere .= " and `$modalitytable`.series_num <= '$val'";
			}
			elseif (substr($s_seriesnum,0,1) == '>') {
				$val = substr($s_seriesnum,1);
				$sqlwhere .= " and `$modalitytable`.series_num > '$val'";
			}
			elseif (substr($s_seriesnum,0,1) == '<') {
				$val = substr($s_seriesnum,1);
				$sqlwhere .= " and `$modalitytable`.series_num < '$val'";
			}
			elseif (substr($s_seriesnum,0,1) == '~') {
				$val = substr($s_seriesnum,1);
				$sqlwhere .= " and `$modalitytable`.series_num <> '$val'";
			}
			else {
				$sqlwhere .= " and `$modalitytable`.series_num = '$s_seriesnum'";
			}
		}
		if ($s_seriesnumfiles != "") {
			if (substr($s_seriesnumfiles,0,2) == '>=') {
				$val = substr($s_seriesnumfiles,2);
				$sqlwhere .= " and `$modalitytable`.numfiles >= '$val'";
			}
			elseif (substr($s_seriesnumfiles,0,2) == '<=') {
				$val = substr($s_seriesnumfiles,2);
				$sqlwhere .= " and `$modalitytable`.numfiles <= '$val'";
			}
			elseif (substr($s_seriesnumfiles,0,1) == '>') {
				$val = substr($s_seriesnumfiles,1);
				$sqlwhere .= " and `$modalitytable`.numfiles > '$val'";
			}
			elseif (substr($s_seriesnumfiles,0,1) == '<') {
				$val = substr($s_seriesnumfiles,1);
				$sqlwhere .= " and `$modalitytable`.numfiles < '$val'";
			}
			elseif (substr($s_seriesnumfiles,0,1) == '~') {
				$val = substr($s_seriesnumfiles,1);
				$sqlwhere .= " and `$modalitytable`.numfiles <> '$val'";
			}
			else {
				$sqlwhere .= " and `$modalitytable`.numfiles = '$s_seriesnumfiles'";
			}
		}
		if ($s_measuresearch != "") {
			$tmpsqlstring = "select measurename_id from measurenames where measure_name = '$s_measures'";
			$tmpresult = MySQLiQuery($tmpsqlstring,__FILE__,__LINE__);
			$tmprow = mysqli_fetch_array($tmpresult, MYSQLI_ASSOC);
			$measurenameid = $tmprow['measurename_id'];
			
			if (is_numeric($measurevalue)) {
				$valtype = "measure_valuenum";
			}
			else {
				$valtype = "measure_valuestring";
			}
			switch ($s_measurecriteria) {
				case "contains": $val = " like '%$s_measurevalue%'"; break;
				case "eq": $val = " = '$s_measurevalue'"; break;
				case "gt": $val = " > '$s_measurevalue'"; break;
				case "lt": $val = " < '$s_measurevalue'"; break;
			}
			
			$measuresearch = ParseMeasureSearchList($s_measuresearch);
			if ($measuresearch != "") {
				$sqlwhere .= " and " . $measuresearch;
			}
			
		}
		if ($s_formvalue[0] != "") {
			/* get the formfield datatype to make sure we compare against the correct assessment_data value */
			$tmpsqlstring = "select * from assessment_formfields where formfield_id = $s_formfieldid[0]";
			$tmpresult = MySQLiQuery($tmpsqlstring,__FILE__,__LINE__);
			$tmprow = mysqli_fetch_array($tmpresult, MYSQLI_ASSOC);
			$datatype = $tmprow['formfield_datatype'];
			
			switch ($datatype) {
				case "string": $valtype = "value_string"; break;
				case "number": $valtype = "value_number"; break;
				case "multichoice": $valtype = "value_text"; break;
				case "singlechoice": $valtype = "value_text"; break;
				case "text": $valtype = "value_text"; break;
				case "date": $valtype = "value_binary"; break;
				case "binary": $valtype = "value_binary"; break;
				case "header": $valtype = "value_text"; break;
			}
			switch ($s_formcriteria[0]) {
				case "contains": $val = " like '%$s_formvalue[0]%'"; break;
				case "eq": $val = " = '$s_formvalue[0]'"; break;
				case "gt": $val = " > '$s_formvalue[0]'"; break;
				case "lt": $val = " < '$s_formvalue[0]'"; break;
			}
			
			$sqlwhere .= " and `assessment_formfields`.formfield_id = $s_formfieldid[0] and `assessment_data`.$valtype $val";
		}
		Debug(__FILE__, __LINE__, "Checkpoint A");
		if ($s_pipelineid != ""){
			Debug(__FILE__, __LINE__, "Checkpoint B");
			$sqlwhere .= " and `analysis`.pipeline_id = $s_pipelineid";
			if ($s_pipelineresultname != "") {
				//echo "s_pipelineresultname is not blank";
				
				/* need to do a subquery outside of the main query to get the list of result names. This is due to a bug in the 5.x series of MySQL */
				$sqlstringX = "select resultname_id from analysis_resultnames where result_name like '%$s_pipelineresultname%' ";
				$resultX = MySQLiQuery($sqlstringX, __FILE__, __LINE__);
				if (mysqli_num_rows($resultX) > 0) {
					while ($rowX = mysqli_fetch_array($resultX, MYSQLI_ASSOC)) {
						$resultnames[] = $rowX['resultname_id'];
					}
					$resultnames[] = 5429; /* hack... to always include ICV */
					$resultnamelist = implode2(',',$resultnames);
					$sqlwhere .= " and `analysis_results`.`result_nameid` in ($resultnamelist) ";
				}
				else {
					$sqlwhere .= " and `analysis_results`.`result_nameid` = '' ";
				}
			}
			Debug(__FILE__, __LINE__, "Checkpoint C");
			if ($s_pipelineresultunit != "") {
				Debug(__FILE__, __LINE__, "Checkpoint D");
				
				/* need to do a subquery outside of the main query to get the list of result names. This is due to a bug in the 5.x series of MySQL */
				$sqlstringX = "select resultunit_id from analysis_resultunit where result_unit like '%$s_pipelineresultunit%' ";
				$resultX = MySQLiQuery($sqlstringX, __FILE__, __LINE__);
				if (mysqli_num_rows($resultX) > 0) {
					while ($rowX = mysqli_fetch_array($resultX, MYSQLI_ASSOC)) {
						$resultunit[] = $rowX['resultunit_id'];
					}
					$resultunitlist = implode2(',',$resultunit);
					$sqlwhere .= " and `analysis_results`.`result_unitid` in ($resultunitlist) ";
				}
				else {
					$sqlwhere .= " and `analysis_results`.`result_unitid` = '' ";
				}
			}
			if ($s_pipelineresultvalue != "") {
				Debug(__FILE__, __LINE__, "Checkpoint E");
				//echo "s_pipelineresultvalue is not blank";
				$sqlwhere .= " and `analysis_results`.`result_value` $s_pipelineresultcompare '$s_pipelineresultvalue' ";
			}
			if ($s_pipelineresulttype != "") {
				Debug(__FILE__, __LINE__, "Checkpoint F");
				//echo "s_pipelineresulttype is not blank";
				$sqlwhere .= " and `analysis_results`.`result_type` = '$s_pipelineresulttype' ";
			}
		}
	
		/* ----- put the whole SQL query together ----- */
		/* first setup the SELECT, depending on the type of query ... */
		if ($s_resultoutput == "pipeline") {
			Debug(__FILE__, __LINE__, "Checkpoint G");
			$sqlstring = "select subjects.uid, studies.study_num, studies.study_id, studies.study_datetime, studies.study_type, subjects.subject_id, subjects.birthdate, subjects.gender, timestampdiff(MONTH, subjects.birthdate, studies.study_datetime) 'ageinmonths', analysis_results.*";
		}
		elseif ($s_resultoutput == "pipelinelong") {
			Debug(__FILE__, __LINE__, "Checkpoint H");
			$sqlstring = "select subjects.uid, studies.study_num, studies.study_id, studies.study_datetime, subjects.subject_id, subjects.birthdate, subjects.gender, timestampdiff(MONTH, subjects.birthdate, studies.study_datetime) 'ageinmonths', analysis_results.*";
		}
		elseif ($s_resultoutput == "pipelinelongyear") {
			Debug(__FILE__, __LINE__, "Checkpoint I");
			$sqlstring = "select subjects.uid, studies.study_num, studies.study_id, studies.study_datetime, subjects.subject_id, subjects.birthdate, subjects.gender, timestampdiff(YEAR, subjects.birthdate, studies.study_datetime) 'ageinmonths', analysis_results.*";
		}
		elseif ($s_resultoutput == "long") {
			if ($s_usealtseriesdesc) {
				$sqlstring = "select subjects.uid, studies.study_id, studies.study_num, studies.study_datetime, studies.study_modality, subjects.subject_id, `$modalitytable`.series_altdesc";
			}
			else {
				$sqlstring = "select subjects.uid, studies.study_id, studies.study_num, studies.study_datetime, studies.study_modality, subjects.subject_id, `$modalitytable`.series_desc";
			}
		}
		elseif (($s_resultoutput == 'qctable') || ($s_resultoutput == 'qcchart')) {
			/* return all custom QC variables */
			//if ($s_qcvariableid != "") {
			//	$sqlstring = "select subjects.subject_id, subjects.uid, studies.study_datetime, studies.study_num, unix_timestamp(DATE(series_datetime)) 'studydate', $modalitytable.series_desc, qc_resultnames.qcresult_name, qc_resultnames.qcresult_units, qc_results.qcresults_valuenumber";
			//}
			//else {
				$sqlstring = "select subjects.subject_id, subjects.uid, studies.study_datetime, studies.study_id, studies.study_num, DATE(series_datetime) 'studydate', $modalitytable.series_desc, $modality" . "_qa.*";
			//}
		}
		elseif ($s_resultoutput == 'subject') {
			$sqlstring = "select subjects.*, projects.*, enrollment.*";
		}
		elseif ($s_resultoutput == 'uniquesubject') {
			$sqlstring = "select subjects.subject_id, subjects.uid, studies.study_id, studies.study_alternateid, studies.study_num";
		}
		else {
			$sqlstring = "select *";
		}
		/* check if the measures should be returned as well */
		if ($s_measuresearch != ""){
			$sqlstring .= ", measures.*, measurenames.measure_name";
		}
		
		if ($s_pipelineid == "") {
			$sqlstring .= ", `$modalitytable`.$modality" . "series_id";
		}
		
		/* ... then add the table JOINs ... */
		$sqlstring .= " from `enrollment`
		join `projects` on `enrollment`.project_id = `projects`.project_id
		join `subjects` on `subjects`.subject_id = `enrollment`.subject_id
		join `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id";
		/* join in other tables if necessary */
		if ($s_subjectaltuid != "") {
			$sqlstring .= " join `subject_altuid` on `subjects`.subject_id = `subject_altuid`.subject_id";
		}
		if ($s_pipelineid == ""){
			$sqlstring .= " join `$modalitytable` on `$modalitytable`.study_id = `studies`.study_id";
		}
		if ($s_measuresearch != ""){
			/* join in the measure table if there is a measure to search for */
			$sqlstring .= " left join `measures` on `measures`.enrollment_id = `enrollment`.enrollment_id left join `measurenames` on `measures`.measurename_id = `measurenames`.measurename_id";
		}
		if ($s_formvalue[0] != ""){
			/* join in the form tables if there is formfield criteria to search for */
			$sqlstring .= " join `assessments` on `assessments`.enrollment_id = `enrollment`.enrollment_id
			join `assessment_formfields` on `assessment_formfields`.form_id = `assessments`.form_id
			join `assessment_data` on `assessment_data`.formfield_id = `assessment_formfields`.formfield_id";
		}
		if ($s_pipelineid != ""){
			/* join in the pipeline tables if there is formfield criteria to search for */
			$sqlstring .= " join `analysis` on `analysis`.study_id = `studies`.study_id
			join `analysis_results` on `analysis_results`.analysis_id = `analysis`.analysis_id";
		}
		if (($modality == "mr") && ($s_pipelineid == "")) {
			$sqlstring .= " left join `mr_qa` on `mr_qa`.mrseries_id = `mr_series`.mrseries_id";
		}
		//if (($s_resultoutput == 'qctable') || ($s_resultoutput == 'qcchart')) {
		//	if ($s_qcvariableid != "") {
		//		if ($s_qcvariableid == "all") {
		//			$sqlstring .= " LEFT JOIN `qc_moduleseries` ON `qc_moduleseries`.series_id = `mr_series`.mrseries_id LEFT JOIN `qc_modules` ON `qc_modules`.qcmodule_id = `qc_moduleseries`.qcmodule_id LEFT JOIN `qc_results` ON `qc_results`.qcmoduleseries_id = `qc_moduleseries`.qcmoduleseries_id LEFT JOIN `qc_resultnames` ON `qc_results`.qcresultname_id = `qc_resultnames`.qcresult_name";
		//		}
		//	}
		//}
		
		/* ... then add the WHERE clause (created earlier) and the ORDER BY and GROUP BY clauses if necessary */
		$sqlstring .= " where `subjects`.isactive = 1 and `studies`.study_modality = '$modality' $sqlwhere ";
		if ($s_resultoutput == 'subject') {
			$sqlstring .= " group by enrollment.enrollment_id order by subjects.uid, projects.project_name";
		}
		elseif ($s_resultoutput == 'uniquesubject') {
			$sqlstring .= " group by studies.study_id";
		}
		else {
			if ($s_formvalue[0] != ""){
				$sqlstring .= " group by `$modalitytable`.$modality" . "series_id ";
			}
			if (($s_resultoutput != "pipeline") && ($s_resultoutput != "pipelinecsv") && ($s_resultoutput != "pipelinelong") && ($s_resultoutput != "pipelinelongyear")) {
				$sqlstring .= " group by `$modalitytable`.$modality" . "series_id order by `studies`.study_datetime, `studies`.study_id";
				if ($s_pipelineid == ""){
					$sqlstring .= ", `$modalitytable`.series_num";
				}
			}
		}
		return $sqlstring;
	}

	/* -------------------------------------------- */
	/* ------- GetIDListFromGroup ----------------- */
	/* -------------------------------------------- */
	function GetIDListFromGroup($groupid) {
		$sqlstring = "select data_id from group_data where group_id = $groupid";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$groupids[] = $row['data_id'];
		}
		return implode2(',',$groupids);
	}
	

	/* -------------------------------------------- */
	/* ------- DisplayMRStudyHeader --------------- */
	/* -------------------------------------------- */
	function DisplayMRStudyHeader($study_id, $display, $measures) {
		if ($display) {
			?>
			<tr>
				<td class="seriesheader"><input type="checkbox" id="study<?=$study_id?>"></td>
				<td class="seriesheader"><b>Series #</b></td>
				<td class="seriesheader">Protocol</td>
				<td class="seriesheader" title="Time of the start of the series acquisition">Time</td>
				<td class="seriesheader" title="Total displacement in X direction">X</td>
				<td class="seriesheader" title="Total displacement in Y direction">Y</td>
				<td class="seriesheader" title="Total displacement in Z direction">Z</td>
				<td class="seriesheader" title="View movement graph and FFT">QA</td>
				<td class="seriesheader" title="Data quality ratings">Rating</td>
				<td class="seriesheader" title="Series notes">Notes</td>
				<td class="seriesheader" title="Per Voxel SNR (timeseries) - Calculated from the fslstats command">PV SNR</td>
				<td class="seriesheader" title="Inside-Outside SNR - This calculates the brain signal (center of brain-extracted volume) compared to the average of the volume corners">IO SNR</td>
				<td class="seriesheader" title="Motion of structural image">Motion R<sup>2</sup></td>
				<td class="seriesheader">Size <span class="tiny">(x y)</span></td>
				<td class="seriesheader"># files</td>
				<td class="seriesheader">Size</td>
				<td class="seriesheader">Sequence</td>
				<td class="seriesheader">TR</td>
				<td class="seriesheader"># beh <span class="tiny">(size)</span></td>
				<?
					if (count($measures) > 0) {
						foreach ($measures as $measure) {
						?>
						<td class="seriesheader"><?=$measure?></td>
						<?
						}
					}
				?>
			</tr>
			<?
		}
		/* return a header for the csv file */
		return "UID, SeriesNum, SeriesDesc, Protocol, Sex, studyAge, calcStudyAge, AltUIDs, StudyID, AltStudyID, StudyNum, StudyDate, StudyType, Project, Height, Weight, BMI, SeriesTime, XMoveMin, YMoveMin, ZMoveMin, XMoveMax, YMoveMax, ZMoveMax, XMoveTotal, YMoveTotal, ZMoveTotal, PitchTotal, RollTotal, YawTotal, PVSNR, IOSNR, nDim, xDim, yDim, zDim, tDim, NumFiles, SeriesSize, SequenceName, ImageType, ImageComment, TR, NumBehFiles, BehSize";
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayMRSeriesHeader -------------- */
	/* -------------------------------------------- */
	function DisplayMRSeriesHeader($s_resultoutput, $measures) {
		?>
		<tr>
			<? if (($s_resultoutput == "series") || ($s_resultoutput == "operations")) { ?>
				<td class="seriesheader"><input type="checkbox" id="seriesall"></td>
			<? } ?>
			<td class="seriesheader"><b>Series #</b></td>
			<td class="seriesheader">Protocol</td>
			<td class="seriesheader">UID</td>
			<td class="seriesheader">Sex</td>
			<td class="seriesheader">Age@scan</td>
			<td class="seriesheader">AltUIDs</td>
			<td class="seriesheader">StudyID</td>
			<td class="seriesheader">Alt StudyID</td>
			<td class="seriesheader">Study #</td>
			<td class="seriesheader">Site</td>
			<td class="seriesheader">Study Date</td>
			<td class="seriesheader">Series Time</td>
			<td class="seriesheader">X</td>
			<td class="seriesheader">Y</td>
			<td class="seriesheader">Z</td>
			<? if (($s_resultoutput == "series") || ($s_resultoutput == "operations")) { ?>
				<td class="seriesheader">QA</td>
				<td class="seriesheader" title="Scale 1-5<br>1 = good<br>5 = bad">Rating</td>
			<? } ?>
			<td class="seriesheader" title="Per Voxel SNR (timeseries) - Calculated from the fslstats command">PV SNR</td>
			<td class="seriesheader" title="Inside-Outside SNR - This calculates the brain signal (center of brain-extracted volume) compared to the average of the volume corners">IO SNR</td>
			<td class="seriesheader" title="Motion in structural images">Motion R<sup>2</sup></td>
			<td class="seriesheader">Size <span class="tiny">(x y)</span></td>
			<td class="seriesheader"># files</td>
			<td class="seriesheader">Size</td>
			<td class="seriesheader">Sequence</td>
			<td class="seriesheader">TR</td>
			<? if ($s_resultoutput != "table") { ?>
			<td class="seriesheader"># beh <span class="tiny">(size)</span></td>
			<? }
				if (count($measures) > 0) {
					foreach ($measures as $measure) {
					?>
					<td class="seriesheader"><?=$measure?></td>
					<?
					}
				}
			?>
		</tr>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayGenericStudyHeader ---------- */
	/* -------------------------------------------- */
	function DisplayGenericStudyHeader($study_id) {
		?>
		<tr>
			<td class="seriesheader"><input type="checkbox" id="study<?=$study_id?>"></td>
			<td class="seriesheader"><b>Series #</b></td>
			<td class="seriesheader">Protocol</td>
			<td class="seriesheader">Time</td>
			<td class="seriesheader"># files</td>
			<td class="seriesheader">Size</td>
			<td class="seriesheader">Notes</td>
		</tr>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayGenericSeriesHeader --------- */
	/* -------------------------------------------- */
	function DisplayGenericSeriesHeader($s_resultoutput) {
		?>
		<tr>
			<? if (($s_resultoutput == "series") || ($s_resultoutput == "operations")) { ?>
				<td class="seriesheader"><input type="checkbox" id="seriesall"></td>
			<? } ?>
			<td class="seriesheader"><b>Series #</b></td>
			<td class="seriesheader">Protocol</td>
			<td class="seriesheader">UID</td>
			<td class="seriesheader">Alt UID 1</td>
			<td class="seriesheader">Alt UID 2</td>
			<td class="seriesheader">Alt UID 3</td>
			<td class="seriesheader">Study #</td>
			<td class="seriesheader">Study Date</td>
			<td class="seriesheader">Series Time</td>
			<td class="seriesheader"># files</td>
			<td class="seriesheader">Size</td>
			<td class="seriesheader">Notes</td>
		</tr>
		<?
	}


	/* -------------------------------------------- */
	/* ------- Anonymize -------------------------- */
	/* -------------------------------------------- */
	function Anonymize($r, $username) {
		$seriesids = $r['seriesid'];
		$modality = $r['modality'];
		$dicomtags = mysqli_real_escape_string($GLOBALS['linki'], $r['dicomtags']);
		
		if (($seriesids == "") && ($enrollmentids == "")) {
			echo "You didn't select any series or subjects to transfer! Go back and select something<br>";
			return;
		}
		
		foreach ($seriesids as $seriesid) {
			$sqlstring = "insert into fileio_requests (fileio_operation, data_type, data_id, modality, anonymize_fields, request_status, username, requestdate) values ('anonymize','series',$seriesid,'$modality','$dicomtags','pending','$username',now())";
			PrintSQL($sqlstring);
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		}
	}
	

	/* -------------------------------------------- */
	/* ------- ProcessRequest --------------------- */
	/* -------------------------------------------- */
	function ProcessRequest($r, $username) {
		//PrintVariable($r);
		
		$ip = getenv('REMOTE_ADDR');
		$modality = $r['modality'];
		$destinationtype = $r['destination'];
		$nfsdir = $r['nfsdir'];
		$realnfsdir = $GLOBALS['cfg']['mountdir'] . "/" . $r['nfsdir'];
		$seriesids = $r['seriesid'];
		$enrollmentids = $r['enrollmentid'];
		$remoteftpusername = $r['remoteftpusername'];
		$remoteftppassword = $r['remoteftppassword'];
		$remoteftpserver = $r['remoteftpserver'];
		$remoteftpport = $r['remoteftpport'];
		$remoteftppath = $r['remoteftppath'];
		$remoteconnid = $r['remoteconnid'];
		//$remotenidbserver = $r['remotenidbserver'];
		//$remotenidbusername = $r['remotenidbusername'];
		//$remotenidbpassword = $r['remotenidbpassword'];
		//$remoteinstanceid = $r['remoteinstanceid'];
		//$remotesiteid = $r['remotesiteid'];
		//$remoteprojectid = $r['remoteprojectid'];
		$publicdownloaddesc = mysqli_real_escape_string($GLOBALS['linki'], $r['publicdownloaddesc']);
		$publicdownloadreleasenotes = mysqli_real_escape_string($GLOBALS['linki'], $r['publicdownloadreleasenotes']);
		$publicdownloadpassword = $r['publicdownloadpassword'];
		$publicdownloadshareinternal = ($r['publicdownloadshareinternal'] == 1) ? 1 : 0;
		$publicdownloadregisterrequired = ($r['publicdownloadregisterrequired'] == 1) ? 1 : 0;
		$publicdownloadexpire = $r['publicdownloadexpire'];
		$bidsreadme = mysqli_real_escape_string($GLOBALS['linki'], $r['bidsreadme']);
		$bidsflaguseuid = mysqli_real_escape_string($GLOBALS['linki'], $r['bidsflag_useuid']);
		$bidsflagusestudyid = mysqli_real_escape_string($GLOBALS['linki'], $r['bidsflag_usestudyid']);
		$squirrelflag_metadata = mysqli_real_escape_string($GLOBALS['linki'], $r['squirrelflag_metadata']);
		$squirrelflag_anonymize = mysqli_real_escape_string($GLOBALS['linki'], $r['squirrelflag_anonymize']);
		$squirrelflag_incstudy = mysqli_real_escape_string($GLOBALS['linki'], $r['squirrelflag_incstudy']);
		$squirrelflag_incseries = mysqli_real_escape_string($GLOBALS['linki'], $r['squirrelflag_incseries']);
		$squirreltitle = mysqli_real_escape_string($GLOBALS['linki'], $r['squirreltitle']);
		$squirreldesc = mysqli_real_escape_string($GLOBALS['linki'], $r['squirreldesc']);
		if ($r['preserveseries'] == '') { $preserveseries = 0; } else { $preserveseries = $r['preserveseries']; }
		$filetype = mysqli_real_escape_string($GLOBALS['linki'], $r['filetype']);
		$gzip = ($r['gzip'] == 1) ? 1 : 0;
		$anonymize = ($r['anonymize'] == 1) ? 1 : 0;
		$dirformat = $r['dirformat'];
		$timepoints = $r['timepoints'];
		$behformat = $r['behformat'];
		$behdirnameroot = mysqli_real_escape_string($GLOBALS['linki'], $r['behdirnameroot']);
		$behdirnameseries = mysqli_real_escape_string($GLOBALS['linki'], $r['behdirnameseries']);
		$subjectmeta = $r['subjectmeta'];
		$subjectdata = $r['subjectdata'];
		$subjectphenotype = $r['subjectphenotype'];
		$subjectforms = $r['subjectforms'];
		$studymeta = $r['studymeta'];
		$studydata = $r['studydata'];
		$seriesmeta = $r['seriesmeta'];
		$seriesdata = $r['seriesdata'];
		$allsubject = $r['allsubject'];
		$downloadimaging = ($r['downloadimaging'] == 1) ? 1 : 0;
		$downloadbeh = ($r['downloadbeh'] == 1) ? 1 : 0;
		$downloadqc = ($r['downloadqc'] == 1) ? 1 : 0;
		$downloadexperiments = ($r['downloadexperiments'] == 1) ? 1 : 0;
		$downloadresults = ($r['downloadresults'] == 1) ? 1 : 0;
		$downloadpipelines = ($r['downloadpipelines'] == 1) ? 1 : 0;
		$downloadvariables = ($r['downloadvariables'] == 1) ? 1 : 0;
		$downloadminipipelines = ($r['downloadminipipelines'] == 1) ? 1 : 0;

		//echo "$downloadbeh";
		
		if (!$downloadbeh) { $behformat = "behnone"; }
		
		if (($seriesids == "") && ($enrollmentids == "")) {
			echo "You didn't select any series or subjects to transfer! Go back and select something<br>";
			exit(0);
		}
		if ($destinationtype == "nfs") {
			if ($nfsdir == "") {
				echo "NFS destination directory was blank! go back and enter a destination directory<br>";
				exit(0);
			}
			if (strpos($nfsdir," ") != false) {
				echo "Destination directory cannot contain spaces. You must choose a different destination directory that does not have spaces<br>";
				exit(0);
			}
			if ((file_exists("$realnfsdir") == false) || ($nfsdir == "/")) {
				echo "Invalid NFS destination directory! go back and enter a valid destination directory<br>";
				exit(0);
			}
			clearstatcache();
			$perms = substr(sprintf('%o', fileperms($realnfsdir)),-3);
			if ($perms != "777"){
				echo "Incorrect permissions [$perms] on destination directory [$realnfsdir]. Should be 777.<br>Set permissions to read/write for everyone by typing 'chmod -R 777 $nfsdir' at the command line in the parent directory of your destination<br>";
				exit(0);
			}
		}

		$requestingip = $ip;
		
		$safe = true;
		
		if (($img_format == "dicom") && ($destinationtype == "remoteftp")) {
			$safe = false;
		}
		
		$remoteftpport = 21;
		$remoteftpsecure = 0;
		
		if ($safe) {
			$totalseriessize += $series_size;
			
			$remoteconnid = ($remoteconnid == '') ? 'null' : $remoteconnid;
			//if (trim($remoteconnid) != "") {
				//$sqlstringC = "select * from remote_connections where remoteconn_id = $remoteconnid";
				//$resultC = MySQLiQuery($sqlstringC, __FILE__ , __LINE__);
				//$rowC = mysqli_fetch_array($resultC, MYSQLI_ASSOC);
				//$remotenidbserver = $rowC['remote_server'];
				//$remotenidbusername = $rowC['remote_username'];
				//$remotenidbpassword = $rowC['remote_password'];
				//$remoteinstanceid = (trim($rowC['remote_instanceid']) == '') ? 'null' : $rowC['remote_instanceid'];
				//$remoteprojectid = $rowC['remote_projectid'];
				//$remotesiteid = $rowC['remote_siteid'];
			//}
			//$remoteinstanceid = ($remoteinstanceid == '') ? 'null' : $remoteinstanceid;
			//$remoteprojectid = ($remoteprojectid == '') ? 'null' : $remoteprojectid;
			//$remotesiteid = ($remotesiteid == '') ? 'null' : $remotesiteid;
			$publicDownloadRowID = ($publicDownloadRowID == '') ? 'null' : $publicDownloadRowID;
			$behonly = ($behonly == 1) ? 1 : 0;
			//$downloadbeh = $behonly;
		}
		else {
			?>Cannot send non-anonymized DICOM data to a remote server<?
			return;
		}
		
		/* if this is a public download, create the row in the public_download table, and get the ID */
		if ($destinationtype == "publicdownload") {
			$sqlstring = "insert into public_downloads (pd_createdate, pd_createdby, pd_desc, pd_notes, pd_password, pd_shareinternal, pd_registerrequired, pd_expiredays, pd_status) values (now(), '$username', '$publicdownloaddesc', '$publicdownloadreleasenotes', sha1('$publicdownloadpassword'), '$publicdownloadshareinternal', '$publicdownloadregisterrequired', '$publicdownloadexpire', 'started')";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			$publicDownloadRowID = mysqli_insert_id($GLOBALS['linki']);
		}
		
		$downloadflags = array();
		if ($downloadimaging) $downloadflags[] = "DOWNLOAD_IMAGING";
		if ($downloadbeh) $downloadflags[] = "DOWNLOAD_BEH";
		if ($downloadqc) $downloadflags[] = "DOWNLOAD_QC";
		if ($downloadexperiments) $downloadflags[] = "DOWNLOAD_EXPERIMENTS";
		if ($downloadresults) $downloadflags[] = "DOWNLOAD_ANALYSIS";
		if ($downloadpipelines) $downloadflags[] = "DOWNLOAD_PIPELINES";
		if ($downloadvariables) $downloadflags[] = "DOWNLOAD_VARIABLES";
		if ($downloadminipipelines) $downloadflags[] = "DOWNLOAD_MINIPIPELINES";
		if (count($downloadflags) > 0)
			$downloadflagstr = "('" . implode2(",",$downloadflags) . "')";
		else
			$downloadflagstr = "null";
		
		$bidsflags = array();
		if ($bidsflaguseuid) $bidsflags[] = "BIDS_USEUID";
		if ($bidsflagusestudyid) $bidsflags[] = "BIDS_USESTUDYID";
		if (count($bidsflags) > 0)
			$bidsflagstr = "('" . implode2(",",$bidsflags) . "')";
		else
			$bidsflagstr = "null";

		$squirrelflagmetadata = mysqli_real_escape_string($GLOBALS['linki'], $r['squirrelflag_metadata']);
		$squirrelflaganonymize = mysqli_real_escape_string($GLOBALS['linki'], $r['squirrelflag_anonymize']);
		$squirrelflagincstudy = mysqli_real_escape_string($GLOBALS['linki'], $r['squirrelflag_incstudy']);
		$squirrelflagincseries = mysqli_real_escape_string($GLOBALS['linki'], $r['squirrelflag_incseries']);

		$squirrelflags = array();
		if ($squirrelflagmetadata == "subject") $squirrelflags[] = "SQUIRREL_METAFROMSUBJECT";
		if ($squirrelflagmetadata == "enrollment") $squirrelflags[] = "SQUIRREL_METAFROMENROLLMENT";
		if ($squirrelflaganonymize) $squirrelflags[] = "SQUIRREL_ANONYMIZE";
		if ($squirrelflagincstudy) $squirrelflags[] = "SQUIRREL_INCSTUDYNUM";
		if ($squirrelflagincseries) $squirrelflags[] = "SQUIRREL_INCSERIESNUM";
		if (count($squirrelflags) > 0)
			$squirrelflagstr = "('" . implode2(",",$squirrelflags) . "')";
		else
			$squirrelflagstr = "null";

		$squirreltitle = mysqli_real_escape_string($GLOBALS['linki'], $r['squirreltitle']);
		$squirreldesc = mysqli_real_escape_string($GLOBALS['linki'], $r['squirreldesc']);

		$sqlstring = "insert into exports (username, ip, download_imaging, download_beh, download_qc, download_flags, destinationtype, filetype, do_gzip, do_preserveseries, anonymization_level, dirformat, beh_format, beh_dirrootname, beh_dirseriesname, nfsdir, remoteftp_username, remoteftp_password, remoteftp_server, remoteftp_port, remoteftp_path, remoteftp_log, remotenidb_connectionid, publicdownloadid, bidsreadme, bids_flags, squirrel_flags, squirrel_title, squirrel_desc, submitdate, status) values ('$username', '$ip', $downloadimaging, $downloadbeh, $downloadqc, $downloadflagstr, '$destinationtype', '$filetype', $gzip, $preserveseries, $anonymize, '$dirformat', '$behformat', '$behdirnameroot','$behdirnameseries', '$nfsdir', '$remoteftpusername', '$remoteftppassword', '$remoteftpserver', $remoteftpport, '$remoteftppath', '$remoteftplog', $remoteconnid, $publicDownloadRowID, '$bidsreadme', $bidsflagstr, $squirrelflagstr, '$squirreltitle', '$squirreldesc', now(), 'submitted')";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$exportRowID = mysqli_insert_id($GLOBALS['linki']);
		
		$modality = strtolower($modality);
		
		/* insert all of the series into the exportseries table */
		foreach ($seriesids as $seriesid) {
			$sqlstring = "insert into exportseries (export_id, series_id, modality, status) values ($exportRowID, $seriesid, '$modality', 'submitted')";
			$result = MySQLiQuery($sqlstring, __FILE__ , __LINE__);
		}
		$numseries = count($seriesids);
		
		?>
		<div class="ui text container">
			<div class="ui message">
				<div class="header">
					Your data export, with <?=$numseries?> series, has been submitted
				</div>
				<br>
				<a href="requeststatus.php" class="ui primary button"><i class="external alternate icon"></i> View export status</a>
			</div>
			<?
			if (($destinationtype == "localftp") || ($destinationtype == "export")) {
				?>
				Your data has been queued for FTP transfer<br><br>
				<div align="center">
				<table><tr><td style="border: solid yellow 1pt; background-color:lightyellow">
				Use the following information to login to the FTP server and transfer your data:<br>
				<pre>
			Server/Host: <?=$GLOBALS['cfg']['localftphostname'];?>
			Login: <?=$GLOBALS['cfg']['localftpusername'];?>
			Password: <?=$GLOBALS['cfg']['localftppassword'];?>
			Port: 21
				</pre>
				</td></tr></table></div>
				<?
			}
		?>
		</div>
		<br><br>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- DelimitedListToArray --------------- */
	/* -------------------------------------------- */
	function DelimitedListToArray($str) {
		$parts = preg_split('/[\^,;\'\s\t\n\f\r]+/', $str);
		$newparts = array();
		foreach ($parts as $part) {
			$newparts[] = trim($part);
		}
		//PrintVariable($newparts);
		return $newparts;
	}
	
	
	/* -------------------------------------------- */
	/* ------- ParseMeasureSearchList ------------- */
	/* -------------------------------------------- */
	function ParseMeasureSearchList($str) {

		$parts = explode(',',$str);
		foreach ($parts as $part) {
			if (strpos($part,'=') !== false) {
				$subparts = explode('=',$part);
				$measurename = $subparts[0];
				$measurevalue = $subparts[1];
				$part = "(measurenames.measure_name = '$measurename' and (measures.measure_valuestring = '$measurevalue' or measures.measure_valuenum = '$measurevalue'))";
				$newparts[] = $part;
			}
			if (strpos($part,'>') !== false) {
				$subparts = explode('>',$part);
				$measurename = $subparts[0];
				$measurevalue = $subparts[1];
				$part = "(measurenames.measure_name = '$measurename' and (measures.measure_valuestring > '$measurevalue' or measures.measure_valuenum > '$measurevalue'))";
				$newparts[] = $part;
			}
			if (strpos($part,'<') !== false) {
				$subparts = explode('<',$part);
				$measurename = $subparts[0];
				$measurevalue = $subparts[1];
				$part = "(measurenames.measure_name = '$measurename' and (measures.measure_valuestring < '$measurevalue' or measures.measure_valuenum < '$measurevalue'))";
				$newparts[] = $part;
			}
			if (strpos($part,'~') !== false) {
				$subparts = explode('~',$part);
				$measurename = $subparts[0];
				$measurevalue = $subparts[1];
				$part = "(measurenames.measure_name = '$measurename' and (measures.measure_valuestring like '%$measurevalue%' or measures.measure_valuenum like '%$measurevalue%'))";
				$newparts[] = $part;
			}
		}
		print_r($newparts);
		if ($newparts == "") {
			return "";
		}
		else {
			return implode2(" and ", $newparts);
		}
	}

	
	/* -------------------------------------------- */
	/* ------- ParseMeasureResultList ------------- */
	/* -------------------------------------------- */
	function ParseMeasureResultList($str, $field) {

		$parts = explode(',',$str);
		foreach ($parts as $part) {
			if (strpos($part,'*') !== false) {
				$part = str_replace('*','%',$part);
				$part = "$field like '$part'";
			}
			else {
				$part = "$field = '$part'";
			}
			$newparts[] = $part;
		}
		return implode2(" or ", $newparts);
	}


	/* -------------------------------------------- */
	/* ------- GetMRSequenceQCList ---------------- */
	/* -------------------------------------------- */
	function GetMRSequenceQCList() {
		/* get the movement & SNR stats by sequence name */
		$sqlstring2 = "SELECT b.series_sequencename, max(a.move_maxx) 'maxx', min(a.move_minx) 'minx', max(a.move_maxy) 'maxy', min(a.move_miny) 'miny', max(a.move_maxz) 'maxz', min(a.move_minz) 'minz', avg(a.pv_snr) 'avgpvsnr', avg(a.io_snr) 'avgiosnr', std(a.pv_snr) 'stdpvsnr', std(a.io_snr) 'stdiosnr', min(a.pv_snr) 'minpvsnr', min(a.io_snr) 'miniosnr', max(a.pv_snr) 'maxpvsnr', max(a.io_snr) 'maxiosnr', min(a.motion_rsq) 'minmotion', max(a.motion_rsq) 'maxmotion', avg(a.motion_rsq) 'avgmotion', std(a.motion_rsq) 'stdmotion' FROM mr_qa a left join mr_series b on a.mrseries_id = b.mrseries_id where a.io_snr > 0 group by b.series_sequencename";
		$result2 = MySQLiQuery($sqlstring2,__FILE__,__LINE__);
		while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
			$sequence = $row2['series_sequencename'];
			$pstats[$sequence]['avgpvsnr'] = $row2['avgpvsnr'];
			$pstats[$sequence]['stdpvsnr'] = $row2['stdpvsnr'];
			$pstats[$sequence]['minpvsnr'] = $row2['minpvsnr'];
			$pstats[$sequence]['maxpvsnr'] = $row2['maxpvsnr'];
			$pstats[$sequence]['avgiosnr'] = $row2['avgiosnr'];
			$pstats[$sequence]['stdiosnr'] = $row2['stdiosnr'];
			$pstats[$sequence]['miniosnr'] = $row2['miniosnr'];
			$pstats[$sequence]['maxiosnr'] = $row2['maxiosnr'];
			$pstats[$sequence]['avgmotion'] = $row2['avgmotion'];
			$pstats[$sequence]['stdmotion'] = $row2['stdmotion'];
			$pstats[$sequence]['minmotion'] = $row2['minmotion'];
			$pstats[$sequence]['maxmotion'] = $row2['maxmotion'];

			if ($row2['stdiosnr'] != 0) {
				$pstats[$sequence]['maxstdiosnr'] = ($row2['avgiosnr'] - $row2['miniosnr'])/$row2['stdiosnr'];
			} else { $pstats[$sequence]['maxstdiosnr'] = 0; }
			if ($row2['stdpvsnr'] != 0) {
				$pstats[$sequence]['maxstdpvsnr'] = ($row2['avgpvsnr'] - $row2['minpvsnr'])/$row2['stdpvsnr'];
			} else { $pstats[$sequence]['maxstdpvsnr'] = 0; }
			if ($row2['stdmotion'] != 0) {
				$pstats[$sequence]['maxstdmotion'] = ($row2['avgmotion'] - $row2['minmotion'])/$row2['stdmotion'];
			} else { $pstats[$sequence]['maxstdmotion'] = 0; }
		}
	}

	
	/* -------------------------------------------- */
	/* ------- remove_outliers -------------------- */
	/* -------------------------------------------- */
	/* Function to remove outliers more than X stdev from the mean
	   X default of 1 */
	function remove_outliers($dataset, $magnitude = 1) {
		$count = count($dataset);
		$mean = array_sum($dataset) / $count; // Calculate the mean
		$deviation = sqrt(array_sum(array_map("sd_square", $dataset, array_fill(0, $count, $mean))) / $count) * $magnitude; // Calculate standard deviation and times by magnitude

		return array_filter($dataset, function($x) use ($mean, $deviation) { return ($x <= $mean + $deviation && $x >= $mean - $deviation); }); // Return filtered array of values that lie within $mean +- $deviation.
	}
	
?>

<? include("footer.php") ?>
