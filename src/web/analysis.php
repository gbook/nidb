<?
 // ------------------------------------------------------------------------------
 // NiDB analysis.php
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
		<title>NiDB - Manage Pipelines</title>
	</head>

<body>
	<div id="wrapper">
<?
	$timestart = microtime(true);

	require "functions.php";
	require "pipeline_functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	//PrintVariable($_POST, "POST");
	//exit();
	//PrintVariable($_GET, "GET");
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$id = GetVariable("id");
	$pagenum = GetVariable("pagenum");
	$numperpage = GetVariable("numperpage");
	
	$analysisid = GetVariable("analysisid");
	$analysisids = GetVariable("analysisids");
	$destination = GetVariable("destination");
	$version = GetVariable("version");
	$completefiles = GetVariable("completefiles");
	$dependency = GetVariable("dependency");
	$deplevel = GetVariable("deplevel");
	$depdir = GetVariable("depdir");
	$deplinktype = GetVariable("deplinktype");
	$groupid = GetVariable("groupid");
	$dynamicgroupid = GetVariable("dynamicgroupid");
	$level = GetVariable("level");
	$ishidden = GetVariable("pipelineishidden");
	
	$analysisnotes = GetVariable("analysisnotes");
	$fileviewtype = GetVariable("fileviewtype");
	$listtype = GetVariable("listtype");
	
	$searchuid = GetVariable("searchuid");
	$searchstatus = GetVariable("searchstatus");
	$searchsuccess = GetVariable("searchsuccess");
	$sortby = GetVariable("sortby");
	$sortorder = GetVariable("sortorder");
	
	$returnpage = GetVariable("returnpage");
	
	/* determine action */
	switch ($action) {
		case 'viewjob': DisplayJob($id); break;
		case 'viewlists': DisplayPipelineLists($id, $listtype); break;
		case 'viewanalyses': DisplayAnalysisList($id, $numperpage, $pagenum, $searchuid, $searchstatus, $searchsuccess, $sortby, $sortorder); break;
		case 'viewfailedanalyses': DisplayFailedAnalysisList($id, $numperpage, $pagenum); break;
		case 'deleteanalyses':
			DeleteAnalyses($id, $analysisids);
			DisplayAnalysisList($id, $numperpage, $pagenum, $searchuid, $searchstatus, $searchsuccess, $sortby, $sortorder);
			break;
		case 'copyanalyses':
			CopyAnalyses($analysisids, $destination);
			DisplayAnalysisList($id, $numperpage, $pagenum, $searchuid, $searchstatus, $searchsuccess, $sortby, $sortorder);
			break;
		case 'createlinks':
			CreateLinks($analysisids, $destination);
			DisplayAnalysisList($id, $numperpage, $pagenum, $searchuid, $searchstatus, $searchsuccess, $sortby, $sortorder);
			break;
		case 'rerunresults':
			RerunResults($analysisids);
			DisplayAnalysisList($id, $numperpage, $pagenum, $searchuid, $searchstatus, $searchsuccess, $sortby, $sortorder);
			break;
		case 'runsupplement':
			RunSupplement($analysisids);
			DisplayAnalysisList($id, $numperpage, $pagenum, $searchuid, $searchstatus, $searchsuccess, $sortby, $sortorder);
			break;
		case 'markbad':
			MarkAnalysis($analysisids, 'bad');
			DisplayAnalysisList($id, $numperpage, $pagenum, $searchuid, $searchstatus, $searchsuccess, $sortby, $sortorder);
			break;
		case 'markgood':
			MarkAnalysis($analysisids, 'good');
			DisplayAnalysisList($id, $numperpage, $pagenum, $searchuid, $searchstatus, $searchsuccess, $sortby, $sortorder);
			break;
		case 'markcomplete':
			MarkComplete($analysisids);
			DisplayAnalysisList($id, $numperpage, $pagenum, $searchuid, $searchstatus, $searchsuccess, $sortby, $sortorder);
			break;
		case 'marksuccessful':
			MarkSuccessful($analysisids);
			DisplayAnalysisList($id, $numperpage, $pagenum, $searchuid, $searchstatus, $searchsuccess, $sortby, $sortorder);
			break;
		case 'markunsuccessful':
			MarkUnsuccessful($analysisids);
			DisplayAnalysisList($id, $numperpage, $pagenum, $searchuid, $searchstatus, $searchsuccess, $sortby, $sortorder);
			break;
		case 'rechecksuccess':
			RecheckSuccess($analysisids);
			DisplayAnalysisList($id, $numperpage, $pagenum, $searchuid, $searchstatus, $searchsuccess, $sortby, $sortorder);
			break;
		case 'disable':
			DisablePipeline($id);
			DisplayAnalysisList($id, $numperpage, $pagenum, $searchuid, $searchstatus, $searchsuccess, $sortby, $sortorder);
			break;
		case 'enable':
			EnablePipeline($id);
			DisplayAnalysisList($id, $numperpage, $pagenum, $searchuid, $searchstatus, $searchsuccess, $sortby, $sortorder);
			break;
		case 'viewlogs': DisplayLogs($id, $analysisid); break;
		case 'viewfiles': DisplayFiles($id, $analysisid, $fileviewtype); break;
		case 'setanalysisnotes':
			SetAnalysisNotes($analysisid, $analysisnotes);
			DisplayAnalysisList($id, $numperpage, $pagenum, $searchuid, $searchstatus, $searchsuccess, $sortby, $sortorder);
			break;
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	/* -------------------------------------------- */
	/* ------- SetAnalysisNotes ------------------- */
	/* -------------------------------------------- */
	function SetAnalysisNotes($id, $notes) {
		/* check input parameters */
		if (!ValidID($id,'Pipeline ID')) { return; }
		
		$notes = mysqli_real_escape_string($GLOBALS['linki'], $notes);
		$sqlstring = "update analysis set analysis_notes = '$notes' where analysis_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		//PrintSQL($sqlstring);
		
		?><div align="center"><span class="message">Analysis [<?=$id?>] notes updated</span></div><?
	}
	

	/* -------------------------------------------- */
	/* ------- DeleteAnalyses --------------------- */
	/* -------------------------------------------- */
	function DeleteAnalyses($id, $analysisids) {

		if (!ValidID($id,'Pipeline ID')) { return; }
	
		/*disable this pipeline */
		DisablePipeline($id);

		$sqlstring = "select max(group_id) 'maxgroupid' from fileio_requests";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$groupid = $row['maxgroupid'] + 1;
		
		foreach ($analysisids as $analysisid) {
			
			$sqlstring = "update analysis set analysis_statusmessage = 'Queued for deletion' where analysis_id = $analysisid";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			
			$sqlstring = "select d.uid, b.study_num, e.pipeline_name, e.pipeline_level from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id left join pipelines e on a.pipeline_id = e.pipeline_id where a.analysis_id = $analysisid";
			//echo "[$sqlstring]";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$uid = $row['uid'];
			$studynum = $row['study_num'];
			$pipelinename = $row['pipeline_name'];
			$pipelinelevel = $row['pipeline_level'];

			if (($pipelinelevel == 0) || ($pipelinelevel == 1)) {
				$analysislevel = 'analysis';
			}
			elseif ($pipelinelevel == 2) {
				$analysislevel = 'groupanalysis';
			}
			
			$sqlstring = "insert into fileio_requests (fileio_operation, group_id,data_type,data_id,username,requestdate) values ('delete', $groupid,'$analysislevel',$analysisid,'" . $GLOBALS['username'] . "', now())";
			//PrintSQL($sqlstring);
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			
			if ($pipelinelevel == 1) {
				$datapath = $GLOBALS['cfg']['analysisdir'] . "/$uid/$studynum/$pipelinename";
			}
			elseif ($pipelinelevel == 2) {
				$datapath = $GLOBALS['cfg']['groupanalysisdir'] . "/$pipelinename";
			}
			?><span class="codelisting"><?=$datapath?> queued for deletion</span><br><?
		}
	}


	/* -------------------------------------------- */
	/* ------- CopyAnalyses ----------------------- */
	/* -------------------------------------------- */
	function CopyAnalyses($analysisids, $destination) {
	
		$destination = mysqli_real_escape_string($GLOBALS['linki'], $destination);
		
		$sqlstring = "select max(group_id) 'maxgroupid' from fileio_requests";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$groupid = $row['maxgroupid'] + 1;
		
		foreach ($analysisids as $analysisid) {
		
			if (!ValidID($analysisid,'Analysis ID')) { return; }
			
			$sqlstring = "insert into fileio_requests (fileio_operation, group_id, data_type, data_id, data_destination, username, requestdate) values ('copy', $groupid, 'analysis', $analysisid, '$destination', '" . $GLOBALS['username'] . "', now())";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			
			?><span class="codelisting"><?=GetAnalysisPath($analysisid)?> queued for copy to <?=$destination?></span><br><?
			
		}
	}

	
	/* -------------------------------------------- */
	/* ------- CreateLinks ------------------------ */
	/* -------------------------------------------- */
	function CreateLinks($analysisids, $destination) {
	
		$destination = mysqli_real_escape_string($GLOBALS['linki'], $destination);
		
		$sqlstring = "select max(group_id) 'maxgroupid' from fileio_requests";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$groupid = $row['maxgroupid'] + 1;
		
		foreach ($analysisids as $analysisid) {
		
			if (!ValidID($analysisid,'Analysis ID')) { return; }
			
			$sqlstring = "insert into fileio_requests (fileio_operation, group_id, data_type, data_id, data_destination, username, requestdate) values ('createlinks', $groupid, 'analysis', $analysisid, '$destination', '" . $GLOBALS['username'] . "', now())";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			
			?><span class="codelisting"><?=GetAnalysisPath($analysisid)?> queued for link creation in <?=$destination?></span><br><?
			
		}
	}


	/* -------------------------------------------- */
	/* ------- RerunResults ----------------------- */
	/* -------------------------------------------- */
	function RerunResults($analysisids) {
	
		foreach ($analysisids as $analysisid) {
			
			if (!ValidID($analysisid,'Analysis ID')) { return; }
			
			$sqlstring = "update analysis set analysis_statusmessage = 'Results queued for rerun', analysis_rerunresults = 1 where analysis_id = $analysisid";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			
			?><span class="codelisting"><?=GetAnalysisPath($analysisid)?> results queued to be rerun</span><br><?
		}
	}

	
	/* -------------------------------------------- */
	/* ------- RunSupplement ---------------------- */
	/* -------------------------------------------- */
	function RunSupplement($analysisids) {
	
		foreach ($analysisids as $analysisid) {

			if (!ValidID($analysisid,'Analysis ID')) { return; }
			
			$sqlstring = "update analysis set analysis_statusmessage = 'Queued for supplement run', analysis_runsupplement = 1 where analysis_id = $analysisid";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			
			?><span class="codelisting"><?=GetAnalysisPath($analysisid)?> analysis queued for supplement run</span><br><?
		}
	}
	

	/* -------------------------------------------- */
	/* ------- MarkAnalysis ----------------------- */
	/* -------------------------------------------- */
	function MarkAnalysis($analysisids, $status) {
		
		foreach ($analysisids as $analysisid) {

			if (!ValidID($analysisid,'Analysis ID')) { return; }
			
			if ($status == 'bad') {
				$sqlstring = "update analysis set analysis_isbad = 1 where analysis_id = $analysisid";
				$mark = "bad";
			}
			else {
				$sqlstring = "update analysis set analysis_isbad = 0 where analysis_id = $analysisid";
				$mark = "good";
			}
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			
			?><span class="codelisting"><?=GetAnalysisPath($analysisid)?> marked as <?=$mark?></span><br><?
		}
	}

	
	/* -------------------------------------------- */
	/* ------- MarkComplete ----------------------- */
	/* -------------------------------------------- */
	function MarkComplete($analysisids) {
		
		foreach ($analysisids as $analysisid) {

			if (!ValidID($analysisid,'Analysis ID')) { return; }
			
			$sqlstring = "update analysis set analysis_status = 'complete', analysis_statusmessage = 'Marked as complete', analysis_rerunresults = 0, analysis_runsupplement = 0 where analysis_id = $analysisid";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			
			?><span class="codelisting"><?=GetAnalysisPath($analysisid)?> marked as complete</span><br><?
		}
	}


	/* -------------------------------------------- */
	/* ------- MarkSuccessful --------------------- */
	/* -------------------------------------------- */
	function MarkSuccessful($analysisids) {
		
		foreach ($analysisids as $analysisid) {

			if (!ValidID($analysisid,'Analysis ID')) { return; }
			
			$sqlstring = "update analysis set analysis_iscomplete = 1 where analysis_id = $analysisid";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			
			?><span class="codelisting"><?=GetAnalysisPath($analysisid)?> marked as successful</span><br><?
		}
	}


	/* -------------------------------------------- */
	/* ------- MarkUnsuccessful ------------------- */
	/* -------------------------------------------- */
	function MarkUnsuccessful($analysisids) {
		
		foreach ($analysisids as $analysisid) {

			if (!ValidID($analysisid,'Analysis ID')) { return; }
			
			$sqlstring = "update analysis set analysis_iscomplete = 0 where analysis_id = $analysisid";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			
			?><span class="codelisting"><?=GetAnalysisPath($analysisid)?> marked as unsuccessful</span><br><?
		}
	}
	
	
	/* -------------------------------------------- */
	/* ------- RecheckSuccess --------------------- */
	/* -------------------------------------------- */
	function RecheckSuccess($analysisids) {
		
		$sqlstring = "select max(group_id) 'maxgroupid' from fileio_requests";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$groupid = $row['maxgroupid'] + 1;
		
		foreach ($analysisids as $analysisid) {
		
			if (!ValidID($analysisid,'Analysis ID')) { return; }
			
			$sqlstring = "insert into fileio_requests (fileio_operation, group_id, data_type, data_id, username, requestdate) values ('rechecksuccess', $groupid, 'analysis', $analysisid, '" . $GLOBALS['username'] . "', now())";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			
			?><span class="codelisting"><?=GetAnalysisPath($analysisid)?> to be rechecked for successful file(s)</span><br><?
		}
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayPipelineLists --------------- */
	/* -------------------------------------------- */
	function DisplayPipelineLists($id, $listtype) {
		/* check input parameters */
		if (!ValidID($id,'Pipeline ID')) { return; }
		
		# get pipeline name
		$sqlstring = "select pipeline_name from pipelines where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		//PrintSQL($sqlstring);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$pipelinename = $row['pipeline_name'];

		switch ($listtype) {
			case 'failedanalyses': $sqlstring = "select uid, study_num, study_id, subject_id, study_datetime from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and a.analysis_status in ('NoMatchingSeries','NoMatchingStudies','NoMatchingStudyDependency','IncompleteDependency','BadDependency') order by a.analysis_status desc, study_datetime desc"; break;
			case 'successfulanalyses':
			default:
				?>Successful analyses<br><br><?
				$sqlstring = "select d.uid, b.study_num, b.study_id, d.subject_id, b.study_datetime, weekofyear(b.study_datetime) 'week', c.project_id from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and a.analysis_status not in ('NoMatchingSeries','NoMatchingStudies','NoMatchingStudyDependency','IncompleteDependency','BadDependency') order by a.analysis_status desc, study_datetime desc";
				break;
		}
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		//PrintSQLTable($result);
		echo "<textarea>\n";
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$uid = $row['uid'];
			$studynum = $row['study_num'];
			$studyid = $row['study_id'];
			$subjectid = $row['subject_id'];
			$studydatetime = $row['study_datetime'];
			$week = $row['week'];
			$projectid = $row['project_id'];
			echo "mkdir -m 777 -p /home/".$GLOBALS['username']."/onrc/data/defaultmode/week$week; ln -s /home/pipeline/onrc/data/pipeline/$uid/$studynum/$pipelinename /home/".$GLOBALS['username']."/onrc/data/defaultmode/week$week/$uid$studynum\n";
		}
		echo "</textarea>";
	}


	/* -------------------------------------------- */
	/* ------- DisplayAnalysisList ---------------- */
	/* -------------------------------------------- */
	function DisplayAnalysisList($id, $numperpage, $pagenum, $searchuid, $searchstatus, $searchsuccess, $sortby, $sortorder) {

		/* check input parameters */
		if (!ValidID($id,'Pipeline ID')) { return; }
		$searchuid = mysqli_real_escape_string($GLOBALS['linki'], $searchuid);
		$searchstatus = mysqli_real_escape_string($GLOBALS['linki'], $searchstatus);
		$searchsuccess = mysqli_real_escape_string($GLOBALS['linki'], $searchsuccess);
	
		$sqlstring = "select * from pipelines where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$pipeline_name = $row['pipeline_name'];
		$pipeline_desc = $row['pipeline_desc'];
		$pipeline_level = $row['pipeline_level'];
		$pipeline_version = $row['pipeline_version'];
		$pipeline_status = $row['pipeline_status'];
		$pipeline_statusmessage = $row['pipeline_statusmessage'];
		$pipeline_laststart = $row['pipeline_laststart'];
		$pipeline_lastfinish = $row['pipeline_lastfinish'];
		$pipeline_lastcheck = $row['pipeline_lastcheck'];
		$isenabled = $row['pipeline_enabled'];
		$isdebug = $row['pipeline_debug'];

		DisplayPipelineStatus($pipeline_name, $pipeline_desc, $isenabled, $isdebug, $id, "analysis", $pipeline_status, $pipeline_statusmessage, $pipeline_laststart, $pipeline_lastfinish, $pipeline_lastcheck);
		
		/* prep the pagination */
		if ($numperpage == "") { $numperpage = 500; }
		if (($pagenum == "") || ($pagenum < 1)) { $pagenum = 1; }
		$limitstart = ($pagenum-1)*$numperpage;
		$limitcount = $numperpage;

		if (($sortorder == "asc") || ($sortorder == "")) { $newsortorder = "desc"; }
		elseif ($sortorder == "desc") { $newsortorder = "asc"; }

		if ($sortorder == "asc") { $sortarrow = "<i class='arrow up icon'></i>"; } else { $sortarrow = "<i class='arrow down icon'></i>"; }
		
		/* create the color lookup table */
		$colors = GenerateColorGradient();
		
		/* run the sql query here to get the row count */
		if (($searchuid == "") && ($searchstatus == "") && ($searchsuccess == "")) {
			$sqlstring = "select count(*) 'count' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and analysis_status not in ('NoMatchingSeries','NoMatchingStudies','NoMatchingStudyDependency','IncompleteDependency','BadDependency')";
		}
		else {
			$sqlstring = "select count(*) 'count' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id";
			if ($searchuid != "") {
				$sqlstring .= " and d.uid like '%$searchuid%'";
			}
			if ($searchstatus != "") {
				if ($searchstatus == "allothers") {
					$sqlstring .= " and analysis_status in ('','NoMatchingSeries','NoMatchingStudies','NoMatchingStudyDependency','IncompleteDependency','BadDependency')";
				}
				else {
					$sqlstring .= " and analysis_status = '$searchstatus'";
				}
			}
		}
		if ($searchsuccess == 1) {
			$sqlstring .= " and a.analysis_iscomplete = 1";
		}
		if ($searchsuccess == 2) {
			$sqlstring .= " and a.analysis_iscomplete = 0 and a.analysis_status = 'complete'";
		}
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$numrows = $row['count'];
		//$numrows = mysqli_num_rows($result);
		$numpages = ceil($numrows/$numperpage);
		if ($pagenum > $numpages) { $pagenum = $numpages; }
		?>
		<div id="dialogbox" title="Dialog Box" style="display:none;">Loading...</div>
		<script type="text/javascript">
		$(function() {
			$("#studiesall").click(function() {
				var checked_status = this.checked;
				$(".allstudies").find("input[type='checkbox']").each(function() {
					this.checked = checked_status;
				});
			});
			$("#analysesall").click(function() {
				var checked_status = this.checked;
				$(".allanalyses").find("input[type='checkbox']").each(function() {
					this.checked = checked_status;
				});
			});
		});
		</script>

		<table width="100%" class="tablepage">
			<form method="post" action="analysis.php" id="numperpageform" class="ui form">
			<input type="hidden" name="action" value="viewanalyses">
			<input type="hidden" name="id" value="<?=$id?>">
			<input type="hidden" name="searchuid" value="<?=$searchuid?>">
			<input type="hidden" name="searchstatus" value="<?=$searchstatus?>">
			<input type="hidden" name="searchsuccess" value="<?=$searchsuccess?>">
			<input type="hidden" name="sortby" value="<?=$sortby?>">
			<input type="hidden" name="sortorder" value="<?=$sortorder?>">
			<tr>
				<td class="label"><?=$numrows?> analyses <? if (($searchuid != "") || ($searchstatus != "") || ($searchsuccess != "")) { echo "found"; } ?></td>
				<td class="pagenum">
					Page <?=$pagenum?> of <?=$numpages?> <span class="tiny">(<?=$numperpage?>/page)</span>
					<select name="numperpage" title="Change number per page" onChange="numperpageform.submit()" class="ui dropdown">
						<option value="100" <?if ($numperpage == 100) { echo "selected"; } ?>>100
						<option value="500" <?if ($numperpage == 500) { echo "selected"; } ?>>500
						<option value="1000" <?if ($numperpage == 1000) { echo "selected"; } ?>>1,000
						<option value="2000" <?if ($numperpage == 2000) { echo "selected"; } ?>>2,000
						<option value="5000" <?if ($numperpage == 5000) { echo "selected"; } ?>>5,000
						<option value="10000" <?if ($numperpage == 10000) { echo "selected"; } ?>>10,000
						<option value="50000" <?if ($numperpage == 50000) { echo "selected"; } ?>>50,000
					</select>
				</td>
				<td style="text-align: right">
					<div class="ui buttons">
						<a class="ui button" title="First page" href="analysis.php?action=viewanalyses&id=<?=$id?>&numperpage=<?=$numperpage?>&pagenum=1&searchuid=<?=$searchuid?>&searchstatus=<?=$searchstatus?>&searchsuccess=<?=$searchsuccess?>&sortby=<?=$sortby?>&sortorder=<?=$sortorder?>"><i class="step backward icon"></i></a>
				
						<a class="ui button" title="Previous page" href="analysis.php?action=viewanalyses&id=<?=$id?>&numperpage=<?=$numperpage?>&pagenum=<?=($pagenum-1)?>&searchuid=<?=$searchuid?>&searchstatus=<?=$searchstatus?>&searchsuccess=<?=$searchsuccess?>&sortby=<?=$sortby?>&sortorder=<?=$sortorder?>"><i class="angle left icon"></i></a>
						
						<a class="ui button" title="Reresh" href=""><i class="redo icon"></i></a>
				
						<a class="ui button" title="Next page" href="analysis.php?action=viewanalyses&id=<?=$id?>&numperpage=<?=$numperpage?>&pagenum=<?=($pagenum+1)?>&searchuid=<?=$searchuid?>&searchstatus=<?=$searchstatus?>&searchsuccess=<?=$searchsuccess?>&sortby=<?=$sortby?>&sortorder=<?=$sortorder?>"><i class="angle right icon"></i></a>
				
						<a class="ui button" title="Last page" href="analysis.php?action=viewanalyses&id=<?=$id?>&numperpage=<?=$numperpage?>&pagenum=<?=$numpages?>&searchuid=<?=$searchuid?>&searchstatus=<?=$searchstatus?>&searchsuccess=<?=$searchsuccess?>&sortby=<?=$sortby?>&sortorder=<?=$sortorder?>"><i class="step forward icon"></i></a>
					</div>
				</td>
			</tr>
			</form>
		</table>

		<script>
			function GetAnalysisNotes(id, analysisid){
				var analysisnotes = prompt("Enter notes for this analysis","");
				if (analysisnotes != null){
				  document.studieslist.analysisnotes.value = analysisnotes;
				  document.studieslist.action.value = 'setanalysisnotes';
				  document.studieslist.id.value = id;
				  document.studieslist.analysisid.value = analysisid;
				  document.studieslist.submit();
			   }
			}
		</script>

		<table id="analysistable" class="ui very compact small celled grey top attached table">
			<form method="post" name="filteranalysis" id="filteranalysis" action="analysis.php" class="ui form">
			<input type="hidden" name="action" value="viewanalyses">
			<input type="hidden" name="id" value="<?=$id?>">
			<input type="hidden" name="numperpage" value="<?=$numperpage?>">
			<input type="hidden" name="pagenum" value="<?=$pagenum?>">
			<input type="hidden" name="searchuid" value="<?=$searchuid?>">
			<input type="hidden" name="searchstatus" value="<?=$searchstatus?>">
			<input type="hidden" name="searchsuccess" value="<?=$searchsuccess?>">
			<input type="hidden" name="sortby" value="<?=$sortby?>">
			<input type="hidden" name="sortorder" value="<?=$sortorder?>">
			<thead>
				<tr>
					<th align="left" <? if ($sortby == "studynum") { echo "style='background-color: #fff'"; } ?>>
						<input type="checkbox" id="studiesall">
						<a href="analysis.php?action=viewanalyses&id=<?=$id?>&sortby=studynum&sortorder=<?=$newsortorder?>">Study</a> <? if ($sortby == "studynum") { echo $sortarrow; } ?>
					</th>
					<th align="left" <? if ($sortby == "visit") { echo "style='background-color: #fff'"; } ?>>
						<a href="analysis.php?action=viewanalyses&id=<?=$id?>&sortby=visit&sortorder=<?=$newsortorder?>">Visit</a> <? if ($sortby == "visit") { echo $sortarrow; } ?>
					</th>
					<th align="left" <? if ($sortby == "pipelineversion") { echo "style='background-color: #fff'"; } ?>>
						<a href="analysis.php?action=viewanalyses&id=<?=$id?>&sortby=pipelineversion&sortorder=<?=$newsortorder?>">Pipeline<br>version</a>  <? if ($sortby == "pipelineversion") { echo $sortarrow; } ?>
					</th>
					<? if ($pipeline_level == 1) { ?>
					<th align="left" <? if ($sortby == "studydate") { echo "style='background-color: #fff'"; } ?>>
						<a href="analysis.php?action=viewanalyses&id=<?=$id?>&sortby=studydate&sortorder=<?=$newsortorder?>">Study date</a> <? if ($sortby == "studydate") { echo $sortarrow; } ?>
					</th>
					<th align="left" <? if ($sortby == "numseries") { echo "style='background-color: #fff'"; } ?>>
						<a href="analysis.php?action=viewanalyses&id=<?=$id?>&sortby=numseries&sortorder=<?=$newsortorder?>"># series</a> <? if ($sortby == "numseries") { echo $sortarrow; } ?>
					</th>
					<? } ?>
					<th align="left" <? if ($sortby == "status") { echo "style='background-color: #fff'"; } ?>>
						<a href="analysis.php?action=viewanalyses&id=<?=$id?>&sortby=status&sortorder=<?=$newsortorder?>">Status</a> <? if ($sortby == "status") { echo $sortarrow; } ?><br><span class="tiny">flags</span>
					</th>
					<th align="left" <? if ($sortby == "successful") { echo "style='background-color: #fff'"; } ?>>
						<a href="analysis.php?action=viewanalyses&id=<?=$id?>&sortby=successful&sortorder=<?=$newsortorder?>">Successful</a> <? if ($sortby == "successful") { echo $sortarrow; } ?>
					</th>
					<th align="left">Logs</th>
					<th align="left">Files</th>
					<th align="left">Download</th>
					<th align="left">Results</th>
					<th align="left">Notes</th>
					<th align="left" <? if ($sortby == "message") { echo "style='background-color: #fff'"; } ?>>
						<a href="analysis.php?action=viewanalyses&id=<?=$id?>&sortby=message&sortorder=<?=$newsortorder?>">Message</a> <? if ($sortby == "message") { echo $sortarrow; } ?>
					</th>
					<th align="left" <? if ($sortby == "size") { echo "style='background-color: #fff'"; } ?>>
						<a href="analysis.php?action=viewanalyses&id=<?=$id?>&sortby=size&sortorder=<?=$newsortorder?>">Size</a> <? if ($sortby == "size") { echo $sortarrow; } ?><br><span class="tiny">bytes</span>
					</th>
					<th align="left" <? if ($sortby == "hostname") { echo "style='background-color: #fff'"; } ?>>
						<a href="analysis.php?action=viewanalyses&id=<?=$id?>&sortby=hostname&sortorder=<?=$newsortorder?>">Hostname</a> <? if ($sortby == "hostname") { echo $sortarrow; } ?>
					</th>
					<th align="left" <? if (($sortby == "setuptime") || ($sorty == "setupcompletedate")) { echo "style='background-color: #fff'"; } ?>>
						<a href="analysis.php?action=viewanalyses&id=<?=$id?>&sortby=setuptime&sortorder=<?=$newsortorder?>">Setup time</a> <? if ($sortby == "setuptime") { echo $sortarrow; } ?><br>
						<a href="analysis.php?action=viewanalyses&id=<?=$id?>&sortby=setupcompletedate&sortorder=<?=$newsortorder?>"><span class="tiny">completed date</span></a> <? if ($sortby == "setupcompletedate") { echo $sortarrow; } ?>
					</th>
					<th align="left" <? if (($sortby == "clustertime") || ($sortby == "clustercompletedate")) { echo "style='background-color: #fff'"; } ?>>
						<a href="analysis.php?action=viewanalyses&id=<?=$id?>&sortby=clustertime&sortorder=<?=$newsortorder?>">Cluster time</a> <? if ($sortby == "clustertime") { echo $sortarrow; } ?><br>
						<a href="analysis.php?action=viewanalyses&id=<?=$id?>&sortby=clustercompletedate&sortorder=<?=$newsortorder?>"><span class="tiny">completed date</span></a> <? if ($sortby == "clustercompletedate") { echo $sortarrow; } ?>
					</th>
					<th align="left" style="background-color: Lavender">Operations<br><input type="checkbox" id="analysesall"><span class="tiny">Select All</span></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td align="left">
						<div class="ui input">
							<input type="text" name="searchuid" placeholder="UID..." value="<?=$searchuid?>">
						</div>
					</td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td align="left">
						<select name="searchstatus" class="ui dropdown">
							<option value="" <? if ($searchstatus == "") { echo "selected"; } ?>>Select status...
							<option value="complete" <? if ($searchstatus == "complete") { echo "selected"; } ?>>Complete
							<option value="pending" <? if ($searchstatus == "pending") { echo "selected"; } ?>>Pending
							<option value="processing" <? if ($searchstatus == "processing") { echo "selected"; } ?>>Processing
							<option value="error" <? if ($searchstatus == "error") { echo "selected"; } ?>>Error
							<option value="submitted" <? if ($searchstatus == "submitted") { echo "selected"; } ?>>Submitted
							<option value="allothers" <? if ($searchstatus == "allothers") { echo "selected"; } ?>>All other status (ignored, etc)
						</select>
					</td>
					<td>
						<select name="searchsuccess" class="ui dropdown">
							<option value="" <? if ($searchsuccess == "") { echo "selected"; } ?>>Select success...
							<option value="1" <? if ($searchsuccess == "1") { echo "selected"; } ?>>Successful
							<option value="2" <? if ($searchsuccess == "2") { echo "selected"; } ?>>Not Successful
						</select>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td style="background-color: Lavender"><input type="submit" name="btnSubmitFilter" value="Filter" class="ui blue button"></td>
				</tr>
			</form>
			<form method="post" name="studieslist" id="studieslist" action="analysis.php" class="ui form">
			<input type="hidden" name="action" value="deleteanalyses" id="studieslistaction">
			<input type="hidden" name="destination" value="" id="studieslistdestination">
			<input type="hidden" name="analysisnotes" value="">
			<input type="hidden" name="analysisid" id="analysisid" value="">
			<input type="hidden" name="id" value="<?=$id?>">
				<?
					if (($searchuid == "") && ($searchstatus == "") && ($searchsuccess == "")) {
						$sqlstring = "select *, timediff(analysis_enddate, analysis_startdate) 'analysis_time', timediff(analysis_clusterenddate, analysis_clusterstartdate) 'cluster_time' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and a.analysis_status not in ('NoMatchingSeries','NoMatchingStudies','NoMatchingStudyDependency','IncompleteDependency','BadDependency')";
					}
					else {
						$sqlstring = "select *, timediff(analysis_enddate, analysis_startdate) 'analysis_time', timediff(analysis_clusterenddate, analysis_clusterstartdate) 'cluster_time' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id";
						if ($searchuid != "") {
							$sqlstring .= " and d.uid like '%$searchuid%'";
						}
						if ($searchstatus != "") {
							if ($searchstatus == "allothers") {
								$sqlstring .= " and a.analysis_status in ('','NoMatchingSeries','NoMatchingStudies','NoMatchingStudyDependency','IncompleteDependency','BadDependency')";
							}
							else {
								$sqlstring .= " and a.analysis_status = '$searchstatus'";
							}
						}
						if ($searchsuccess == 1) {
							$sqlstring .= " and a.analysis_iscomplete = 1";
						}
						if ($searchsuccess == 2) {
							$sqlstring .= " and a.analysis_iscomplete = 0 and a.analysis_status = 'complete'";
						}
					}
					/* figure out the sorting */
					switch ($sortby) {
						case 'studynum':
							$sqlstring .= " order by uid $sortorder, study_num $sortorder limit $limitstart, $limitcount";
							break;
						case 'visit':
							$sqlstring .= " order by study_type $sortorder limit $limitstart, $limitcount";
							break;
						case 'pipelineversion':
							$sqlstring .= " order by pipeline_version $sortorder limit $limitstart, $limitcount";
							break;
						case 'studydate':
							$sqlstring .= " order by study_datetime $sortorder limit $limitstart, $limitcount";
							break;
						case 'numseries':
							$sqlstring .= " order by analysis_numseries $sortorder limit $limitstart, $limitcount";
							break;
						case 'status':
							$sqlstring .= " order by analysis_status $sortorder limit $limitstart, $limitcount";
							break;
						case 'successful':
							$sqlstring .= " order by analysis_iscomplete $sortorder limit $limitstart, $limitcount";
							break;
						case 'message':
							$sqlstring .= " order by analysis_statusmessage $sortorder limit $limitstart, $limitcount";
							break;
						case 'size':
							$sqlstring .= " order by analysis_disksize $sortorder limit $limitstart, $limitcount";
							break;
						case 'hostname':
							$sqlstring .= " order by analysis_hostname $sortorder limit $limitstart, $limitcount";
							break;
						case 'setuptime':
							$sqlstring .= " order by analysis_time $sortorder limit $limitstart, $limitcount";
							break;
						case 'setupcompletedate':
							$sqlstring .= " order by analysis_enddate $sortorder limit $limitstart, $limitcount";
							break;
						case 'clustertime':
							$sqlstring .= " order by cluster_time $sortorder limit $limitstart, $limitcount";
							break;
						case 'clustercompletedate':
							$sqlstring .= " order by analysis_clusterenddate $sortorder limit $limitstart, $limitcount";
							break;
						default:
							$sqlstring .= " order by a.analysis_status desc, study_datetime desc limit $limitstart, $limitcount";
					}
					//PrintSQL($sqlstring);
					$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$numcomplete += $row['analysis_iscomplete'];
						$analysistimes[] = $row['analysis_time'];
						$analysissizes[] = $row['analysis_disksize'];
						$clustertimes[] = $row['cluster_time'];
					}
					$minsize = min($analysissizes);
					$maxsize = max($analysissizes);
					$minanalysistime = min($analysistimes);
					$maxanalysistime = max($analysistimes);
					$minclustertime = min($clustertimes);
					$maxclustertime = max($clustertimes);

					/* rewind the result */
					mysqli_data_seek($result, 0);
					
					if (mysqli_num_rows($result) > 0) {
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$analysis_id = $row['analysis_id'];
							$analysis_qsubid = $row['analysis_qsubid'];
							$analysis_status = $row['analysis_status'];
							$analysis_numseries = $row['analysis_numseries'];
							$analysis_statusmessage = $row['analysis_statusmessage'];
							$analysis_statusdatetime = $row['analysis_statusdatetime'];
							$analysis_swversion = $row['analysis_swversion'];
							$analysis_iscomplete = $row['analysis_iscomplete'];
							$analysis_time = $row['analysis_time'];
							$analysis_size = $row['analysis_disksize'];
							$analysis_isbad = $row['analysis_isbad'];
							$analysis_rerunresults = $row['analysis_rerunresults'];
							$analysis_runsupplement = $row['analysis_runsupplement'];
							$notes = $row['analysis_notes'];
							$analysis_hostname = $row['analysis_hostname'];
							$cluster_time = $row['cluster_time'];
							$analysis_enddate = date('Y-m-d H:i',strtotime($row['analysis_enddate']));
							if ($row['analysis_clusterenddate'] == "") {
								$analysis_clusterenddate = "-";
							}
							else {
								$analysis_clusterenddate = date('Y-m-d H:i',strtotime($row['analysis_clusterenddate']));
							}
							$study_id = $row['study_id'];
							$study_num = $row['study_num'];
							$study_datetime = date('M j, Y H:i',strtotime($row['study_datetime']));
							$uid = $row['uid'];
							$visittype = $row['study_type'];
							$pipeline_version = $row['pipeline_version'];
							$pipeline_dependency = $row['pipeline_dependency'];
							
							if ($analysis_status == "") { $analysis_status = "unknown"; }
							
							$sqlstringA = "select pipeline_submithost from pipelines where pipeline_id = $id";
							$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
							$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
							$pipeline_submithost = $rowA['pipeline_submithost'];
							if ($pipeline_submithost == "") { $pipeline_submithost = $GLOBALS['cfg']['clustersubmithost']; }
							
							$sqlstringA = "select pipeline_name, pipeline_submithost from pipelines where pipeline_id = $pipeline_dependency";
							$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
							$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
							$pipeline_dep_name = $rowA['pipeline_name'];
							
							if ($notes == "") {
								$notestitle = "Click to create notes";
								$notescolor = "grey";
							}
							else {
								$notestitle = $notes;
								$notescolor = "red";
							}
							
							if ($analysis_isbad) {
								$rowcolor = "#f2d7d7";
							}
							else {
								$rowcolor = "";
							}
							
							/* get color index for the size */
							$sizeindex = 0;
							if ($analysis_size > 0) {
								$sizeindex = round(($analysis_size/($maxsize-$minsize))*100.0);
								if ($sizeindex > 100) { $sizeindex = 100; }
								$sizecolor = $colors[$sizeindex];
							}
							else { $sizecolor = "#fff"; }
					?>
					<tr bgcolor="<?=$rowcolor?>">
						<td class="allstudies" style="text-align:left"><input type="checkbox" name="studyid[]" value="<?=$study_id?>">
							<a href="studies.php?id=<?=$study_id?>"><?=$uid?><?=$study_num?></a></td>
						<td><?=$visittype?></td>
						<td><?=$pipeline_version?></td>
						<? if ($pipeline_level == 1) { ?>
						<td class="tiny"><?=$study_datetime?></td>
						<td><?=$analysis_numseries?></td>
						<? } ?>
						<td>
							<?
								if (($analysis_status == 'processing') && ($analysis_qsubid != 0)) {
									?>
									<a href="<?=$GLOBALS['cfg']['siteurl']?>/analysis.php?action=viewjob&id=<?=$analysis_qsubid?>" title="Click to view SGE status">processing</a>
									<!--<iframe src="ajaxapi.php?action=sgejobstatus&jobid=<?=$analysis_qsubid?>" width="25px" height="25px" style="border: 0px">No iframes available?</iframe>-->
									<?
								}
								else {
									if (($analysis_qsubid == 0) && ($analysis_status != 'complete')) {
										echo "[$analysis_status] Copying data?";
									}
									else {
										switch ($analysis_status) {
											case 'pending':
												$tip = "Data has finished copying for this analysis, and job has been submitted. Waiting for the job to check in with NiDB";
												break;
											case 'complete':
												$tip = "Analysis is complete";
												break;
											case 'error':
												$tip = "An unspecified error has occured";
												break;
										}
										?><span style="text-decoration: underline; text-decoration-style: dashed; text-decoration-color: #aaa" title="<?=$tip?>"><?=$analysis_status?></span><?
									}
								}
								
								if ($analysis_runsupplement) { ?> <span class="tiny">supplement</span><? }
								if ($analysis_rerunresults) { ?> <span class="tiny">rerun results</span><? }
							?>
						</td>
						<td style="font-weight: bold; color: green">
							<? if ($analysis_iscomplete) { echo "&#x2713;"; } ?>
						</td>
						<? if ($analysis_status != "") { ?>
						<td class="center aligned">
							<a href="viewanalysis.php?action=viewgraph&analysisid=<?=$analysis_id?>&studyid=<?=$study_id?>&pipelineid=<?=$id?>&pipelineversion=<?=$pipeline_version?>" target="_viewgraph" title="View analysis graph"><i class="large grey file alternate outline icon"></i></a>
						</td>
						<td class="center aligned">
							<a href="viewanalysis.php?action=viewfiles&analysisid=<?=$analysis_id?>" target="_viewfiles" title="View file listing"><i class="large grey folder open outline icon"></i></a>
						</td>
						<td class="center aligned">
							<? if ($GLOBALS['cfg']['allowrawdicomexport']) { ?>
							<a href="download.php?modality=pipeline&analysisid=<?=$analysisid?>" border="0"><i class="large grey download icon"></i></a>
							<? } else { ?>
							
							<? } ?>
						</td>
						<td class="center aligned">
							<a href="viewanalysis.php?action=viewresults&analysisid=<?=$analysis_id?>&studyid=<?=$study_id?>" target="_viewresults" title="View analysis results"><i class="large grey chart bar icon"></i></a>
						</td>
						<? } else { ?>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<? } ?>
						<td>
							<span onClick="GetAnalysisNotes(<?=$id?>, <?=$analysis_id?>);" title="<?=$notestitle?>"><i class="large <?=$notescolor?> pencil alternate icon"></i></span>
						</td>
						<td style="font-size:9pt; white-space:nowrap">
							<? if (strlen($analysis_statusmessage) > 50) { echo "<span title='$analysis_statusmessage'>" . substr($analysis_statusmessage, 0, 50) . "...</span>"; } else { echo $analysis_statusmessage; } ?><br>
							<?
								if (strpos($analysis_statusmessage,"processing step") !== false) {
									$parts = explode(" ",$analysis_statusmessage);
									$stepnum = $parts[2];
									$steptotal = $parts[4];
							?>
							<img src="horizontalchart.php?b=no&w=150&h=3&v=<?=$stepnum?>,<?=($steptotal-$stepnum)?>&c=666666,DDDDDD" style="margin:2px"><br>
							<? } ?>
							<span class="tiny"><?=$analysis_statusdatetime?></span>
						</td>
						<td align="right" style="font-size:8pt; border-bottom: 5px solid <?=$sizecolor?>; margin-bottom:0px; padding-bottom:0px" valign="bottom">
							<?=number_format($analysis_size,0)?>
							<table cellspacing="0" cellpadding="0" border="0" width="100%" height="5px" style="margin-top:5px">
								<tr>
									<td width="100%" height="5px" style="background-color: <?=$sizecolor?>; height:5px; font-size: 1pt; border: 0px">&nbsp;</td>
								</tr>
							</table>
						</td>
						<td><?=$analysis_hostname?></td>
						<td><?=$analysis_time?><br><span class="tiny"><?=$analysis_enddate?></span></td>
						<td><?=$cluster_time?><br><span class="tiny"><?=$analysis_clusterenddate?></span></td>
						<td class="allanalyses" style="background-color: Lavender; text-align: center"><input type="checkbox" name="analysisids[]" value="<?=$analysis_id?>"></td>
					</tr>
					<? 
						}
					}
					else {
						?>
						<tr>
							<td colspan="18" class="center aligned" style="padding: 15px">No analyses found</td>
						</tr>
						<?
					}
					
				?>
				<script>
				function GetDestination(){
					var destination = prompt("Please enter a valid destination for the selected analyses","/home/<?=$GLOBALS['username']?>");
					if (destination != null){
					  document.studieslist.action='analysis.php';
					  $("#studieslistaction").attr("value", "copyanalyses");
					  $("#studieslistdestination").attr("value", destination);
					  document.studieslist.submit();
				   }
				}
				function GetDestination2(){
					var destination = prompt("Please enter a valid directory in which to create the 'data' directory and links","/home/<?=$GLOBALS['username']?>");
					if (destination != null){
					  document.studieslist.action='analysis.php';
					  $("#studieslistaction").attr("value", "createlinks");
					  $("#studieslistdestination").attr("value", destination);
					  document.studieslist.submit();
				   }
				}
				function MarkAnalysis(){
					document.studieslist.action='analysis.php';
					document.studieslist.submit();
				}
				</script>
			</tbody>
		</table>
		
		<script>
			$(document).ready(function() {
				$('#popupbutton2').popup({
					popup : $('#popupmenu2'),
					on : 'click'
				});
			});
		</script>
		<div class="ui bottom attached menu">
			<div class="item" href="studies.php?studyid=<?=$studyid?>&action=displayfiles">
				<div class="ui action input">
					<select name="studygroupid" class="ui selection dropdown">
						<option value="">Add to study group...
						<?
							$sqlstring = "select * from groups where group_type = 'study'";
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
					<button class="ui button" type="submit" name="addtogroup" value="Add" onclick="document.studieslist.action='groups.php';document.studieslist.action.value='addstudiestogroup'">Add</button>
				</div>
			</div>
			
			<div class="right menu" id="popupbutton2">
				<a class="browse item" style="background-color: Lavender">
					With Selected...
					<i class="dropdown icon"></i>
				</a>
			</div>
		</div>
		<div class="ui popup" id="popupmenu2">
			<h3 class="ui header">With Selected...</h3>
			
			<p><div class="ui fluid red button" type="submit" onclick="document.studieslist.action.value='deleteanalyses';return confirm('Are you absolutely sure you want to DELETE the selected analyses?')" title="<b style='color:pink'>Pipeline will be disabled. Wait until the deletions are compelte before reenabling the pipeline</b><Br> This will delete the selected analyses, which will be regenerated using the latest pipeline version"><i class="trash icon"></i> Delete</div></p>
			<br>
			
			<p><div class="ui fluid button" type="button" name="copyanalyses" onclick="document.studieslist.action='analysis.php';document.studieslist.action.value='copyanalyses'; GetDestination(); return;"><i class="copy icon"></i> Copy analyses to...</div></p>
			
			<p><div class="ui fluid button" type="button" name="createlinks" onclick="document.studieslist.action='analysis.php';document.studieslist.action.value='createlinks'; GetDestination2(); return;" title="Creates a directory called 'data' which contains links to all of the selected studies"><i class="linkify icon"></i> Create links...</div></p>

			<p><div class="ui fluid button" type="button" name="rerunresults" onclick="document.studieslist.action='analysis.php';document.studieslist.action.value='rerunresults';document.studieslist.submit();" title="This will delete any existing results inserted into NiDB and re-run the results script">Re-run results script</div></p>

			<p><div class="ui fluid button" name="runsupplement" onclick="document.studieslist.action='analysis.php';document.studieslist.action.value='runsupplement';document.studieslist.submit();" title="Run the script specified in the supplemental command script. This will not download new data or re-download existing data. It will only perform commands on the existing files in the analysis directory">Run supplement script</div></p>

			<p><div class="ui fluid button" type="button" name="rechecksuccess" onclick="document.studieslist.action='analysis.php';document.studieslist.action.value='rechecksuccess';document.studieslist.submit();" title="This option will check the selected analyses against the 'successfully completed files' field and mark them as successful if the file(s) exist"><i class="clipboard check icon"></i>Recheck success</div></p>

			<br>
			
			<p><div class="ui fluid button" name="markasbad" onclick="document.studieslist.action='analysis.php';document.studieslist.action.value='markbad'; MarkAnalysis()" title="Mark the analyses as bad so they will not be used in dependent pipelines"><i class="large red frown outline icon"></i> Mark bad</div></p>

			<p><div class="ui fluid button" name="markasgood" onclick="document.studieslist.action='analysis.php';document.studieslist.action.value='markgood'; MarkAnalysis()" title="Unmark an analysis as bad"><i class="large green smile outline icon"></i> Mark good</div></p>

			<p><div class="ui fluid button" name="markcomplete" onclick="document.studieslist.action='analysis.php';document.studieslist.action.value='markcomplete'; MarkAnalysis()" title="Mark the analysis as complete. In case the job was killed or died outside of the pipeline system. Also clears pending jobs and any flags as 'run supplement' or 'rerun results'"><i class="tasks icon"></i> Mark complete</div></p>

			<p><div class="ui fluid button" type="button" name="marksuccessful" onclick="document.studieslist.action='analysis.php';document.studieslist.action.value='marksuccessful'; MarkAnalysis()" title="Mark the analysis as successful">Mark successful</div></p>

			<p><div class="ui fluid button" type="button" name="markunsuccessful" onclick="document.studieslist.action='analysis.php';document.studieslist.action.value='markunsuccessful'; MarkAnalysis()" title="Mark the analysis as unsuccessful">Mark unsuccessful</div></p>
			
		</div>
		
		</form>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayFailedAnalysisList ---------- */
	/* -------------------------------------------- */
	function DisplayFailedAnalysisList($id, $numperpage, $pagenum) {

		/* check input parameters */
		if (!ValidID($id,'Pipeline ID')) { return; }
	
		$sqlstring = "select * from pipelines where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$pipeline_name = $row['pipeline_name'];
		$desc = $row['pipeline_desc'];
		$pipeline_level = $row['pipeline_level'];
		$pipeline_status = $row['pipeline_status'];
		$pipeline_statusmessage = $row['pipeline_statusmessage'];
		$pipeline_laststart = $row['pipeline_laststart'];
		$pipeline_lastfinish = $row['pipeline_lastfinish'];
		$pipeline_lastcheck = $row['pipeline_lastcheck'];
		$isenabled = $row['pipeline_enabled'];
		$isdebug = $row['pipeline_debug'];
	
		//$urllist['Analysis'] = "analysis.php";
		//$urllist['Pipelines'] = "pipelines.php";
		//$urllist["$pipeline_name"] = "pipelines.php?action=editpipeline&id=$id";
		//$urllist["Analysis List"] = "analysis.php?action=viewanalyses&id=$id";
		//NavigationBar("Ignored studies for $pipeline_name", $urllist);
		
		DisplayPipelineStatus($title, $desc, $isenabled, $isdebug, $id, "analysis", $pipeline_status, $pipeline_statusmessage, $pipeline_laststart, $pipeline_lastfinish, $pipeline_lastcheck);
		
		/* prep the pagination */
		if ($numperpage == "") { $numperpage = 1000; }
		if (($pagenum == "") || ($pagenum < 1)) { $pagenum = 1; }
		$limitstart = ($pagenum-1)*$numperpage;
		$limitcount = $numperpage;
		
		/* run the sql query here to get the row count */
		$sqlstring = "select *, timediff(analysis_enddate, analysis_startdate) 'analysis_time', timediff(analysis_clusterenddate, analysis_clusterstartdate) 'cluster_time' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and analysis_status in ('NoMatchingSeries','NoMatchingStudies','NoMatchingStudyDependency','IncompleteDependency','BadDependency')";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$numrows = mysqli_num_rows($result);
		$numpages = ceil($numrows/$numperpage);
		if ($pagenum > $numpages) { $pagenum = $numpages; }
		?>
		<script type="text/javascript">
		$(function() {
			$("#studiesall").click(function() {
				var checked_status = this.checked;
				$(".allstudies").find("input[type='checkbox']").each(function() {
					this.checked = checked_status;
				});
			});
			$("#analysesall").click(function() {
				var checked_status = this.checked;
				$(".allanalyses").find("input[type='checkbox']").each(function() {
					this.checked = checked_status;
				});
			});
		});
		</script>
		<table class="tablepage" width="100%">
			<tr>
				<td class="label"><?=$numrows?> items</td>
				<td class="pagenum">Page <?=$pagenum?> of <?=$numpages?> <span class="tiny">(<?=$numperpage?>/page)</span></td>
				<td class="middle">&nbsp;</td>
				<td class="firstpage" title="First page"><a href="analysis.php?action=viewanalyses&id=<?=$id?>&numperpage=<?=$numperpage?>&pagenum=1">&#171;</a></td>
				<td class="previouspage" title="Previous page"><a href="analysis.php?action=viewanalyses&id=<?=$id?>&numperpage=<?=$numperpage?>&pagenum=<?=($pagenum-1)?>">&lsaquo;</a></td>
				<td title="Refresh page"><a href="" style="margin-left:20px; margin-right:20px; font-size:14pt">&#10227;</a></td>
				<td class="nextpage" title="Next page"><a href="analysis.php?action=viewanalyses&id=<?=$id?>&numperpage=<?=$numperpage?>&pagenum=<?=($pagenum+1)?>">&rsaquo;</a></td>
				<td class="lastpage" title="Last page"><a href="analysis.php?action=viewanalyses&id=<?=$id?>&numperpage=<?=$numperpage?>&pagenum=<?=$numpages?>">&#187;</a></td>
			</tr>
		</table>
		<form method="post" name="studieslist" action="analysis.php">
		<input type="hidden" name="action" value="deleteanalyses" id="studieslistaction">
		<input type="hidden" name="destination" value="" id="studieslistdestination">
		<input type="hidden" name="analysisnotes" value="">
		<input type="hidden" name="analysisid" value="">
		<input type="hidden" name="id" value="<?=$id?>">
		
		<table id="analysistable" class="ui very compact small celled grey table">
			<thead>
				<tr>
					<th><input type="checkbox" id="studiesall"> Study</th>
					<? if ($pipeline_level == 1) { ?>
					<th>Study date</th>
					<? } ?>
					<th>Status</th>
					<th>Data log</th>
					<th style="color:darkred">Delete <input type="checkbox" id="analysesall"></th>
				</tr>
			</thead>
			<tbody>
				<?
					$sqlstring = "select * from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and a.analysis_status in ('NoMatchingSeries','NoMatchingStudies','NoMatchingStudyDependency','IncompleteDependency','BadDependency') order by a.analysis_status desc, study_datetime desc limit $limitstart, $limitcount";
					$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$analysis_id = $row['analysis_id'];
						$analysis_qsubid = $row['analysis_qsubid'];
						$analysis_status = $row['analysis_status'];
						$analysis_numseries = $row['analysis_numseries'];
						$analysis_statusmessage = $row['analysis_statusmessage'];
						$analysis_statusdatetime = $row['analysis_statusdatetime'];
						$analysis_datalog = $row['analysis_datalog'];
						$notes = $row['analysis_notes'];
						$analysis_hostname = $row['analysis_hostname'];
						$analysis_enddate = date('Y-m-d H:i',strtotime($row['analysis_enddate']));
						$analysis_clusterenddate = date('Y-m-d H:i',strtotime($row['analysis_clusterenddate']));
						$study_id = $row['study_id'];
						$study_num = $row['study_num'];
						$study_datetime = date('M j, Y H:i',strtotime($row['study_datetime']));
						$uid = $row['uid'];
						$pipeline_dependency = $row['pipeline_dependency'];
						
						$sqlstringA = "select pipeline_name from pipelines where pipeline_id = $pipeline_dependency";
						$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
						$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
						$pipeline_dep_name = $rowA['pipeline_name'];
				?>
				<tr>
					<td class="allstudies" style="text-align:left"><input type="checkbox" name="studyid[]" value="<?=$study_id?>"><a href="studies.php?id=<?=$study_id?>"><?=$uid?><?=$study_num?></a></td>
					<? if ($pipeline_level == 1) { ?>
					<td class="tiny"><?=$study_datetime?></td>
					<? } ?>
					<td><?=$analysis_status;?></td>
					<td>
						<a href="viewanalysis.php?action=viewgraph&analysisid=<?=$analysis_id?>&studyid=<?=$study_id?>&pipelineid=<?=$id?>&pipelineversion=<?=$pipeline_version?>" target="_viewgraph" title="View analysis graph">View log</a>
					</td>
					<td class="allanalyses" align="right"><input type="checkbox" name="analysisids[]" value="<?=$analysis_id?>"></td>
				</tr>
				<? 
					}
				?>
				<tr style="color: #444; font-size:12pt; font-weight:bold">
					<td colspan="3" valign="top" style="background-color: #fff">
						<table>
						<tr>
							<td valign="top" style="color: #444; font-size:12pt; font-weight:bold; border-top:none">
								Studies group
							</td>
							<td valign="top" style="border-top:none">
								<select name="studygroupid" style="width:150px">
									<?
										$sqlstring = "select * from groups where group_type = 'study'";
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
								<input type="submit" name="addtogroup" value="Add" onclick="document.studieslist.action='groups.php';document.studieslist.action.value='addstudiestogroup'">
							</td>
						</tr>
						</table>
					</td>
					<td colspan="2" align="right" style="background-color: #fff; font-size: 12pt">
					With selected:&nbsp;<br><br>
					<input type="submit" value="Delete" style="border: 1px solid red; background-color: pink; width:150px; margin:4px" onclick="document.studieslist.action.value='deleteanalyses';return confirm('Are you absolutely sure you want to DELETE the selected (failed) analyses?')" title="<b style='color:pink'>Pipeline will be disabled until the deletion is finished</b><Br> This will delete the selected analyses, which will be regenerated using the latest pipeline version">
					</td>
				</tr>
			</tbody>
		</table>
		</form>
		<?
	}
	

	/* -------------------------------------------- */
	/* ------- DisplayLogs ------------------------ */
	/* -------------------------------------------- */
	function DisplayLogs($id, $analysisid) {

		/* check input parameters */
		if (!ValidID($analysisid,'Analysis ID')) { return; }
		
		$sqlstring = "select * from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id left join pipelines e on e.pipeline_id = a.pipeline_id where a.analysis_id = $analysisid";
		//echo $sqlstring;
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$uid = $row['uid'];
		$studynum = $row['study_num'];
		$pipelinename = $row['pipeline_name'];
		$pipelineid = $row['pipeline_id'];
		$pipelineversion = $row['pipeline_version'];
		$pipeline_level = $row['pipeline_level'];
		$pipelinedirectory = $row['pipeline_directory'];

		/* build navigation bar */
		//$urllist['Analysis'] = "analysis.php";
		//$urllist['Pipelines'] = "pipelines.php";
		//$urllist["$pipelinename"] = "pipelines.php?action=editpipeline&id=$pipelineid";
		//$urllist['Analysis list'] = "analysis.php?action=viewanalyses&id=$pipelineid";
		//NavigationBar("Logs for $uid &rarr; $studynum &rarr; $pipelinename", $urllist);

		/* get list of steps for the appropriate version */
		$sqlstring = "select * from pipeline_steps where pipeline_id = $pipelineid and pipeline_version = $pipelineversion";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$ps_command = $row['ps_command'];
			$ps_description = $row['ps_description'];
			$ps_supplement = $row['ps_supplement'];
			$ps_order = $row['ps_order'] - 1;
			if ($ps_supplement) {
				$descriptions['supp'][$ps_order] = $ps_description;
				$commands['supp'][$ps_order] = $ps_command;
			}
			else {
				$descriptions['reg'][$ps_order] = $ps_description;
				$commands['reg'][$ps_order] = $ps_command;
			}
		}
		//echo "<pre>";
		//print_r($descriptions);
		//echo "</pre>";
		
		/* build the correct path */
		if (($pipeline_level == 1) && ($pipelinedirectory == "")) {
			$path = $GLOBALS['cfg']['analysisdir'] . "/$uid/$studynum/$pipelinename/pipeline";
			#echo "(1) Path is [$path]<br>";
		}
		elseif (($pipeline_level == 0) || ($pipelinedirectory != "")) {
			$path = $GLOBALS['cfg']['mountdir'] . "/$pipelinedirectory/$uid/$studynum/$pipelinename/pipeline";
			#echo "(2) Path is [$path]<br>";
		}
		else {
			$path = $GLOBALS['cfg']['groupanalysisdir'] . "/$pipelinename/pipeline";
			#echo "(3) Path is [$path]<br>";
		}
		
		/* check if the path exists */
		if (file_exists($path)) {
			?>
			Showing log files from <b><?=$path?></b>
			<br><br>
			<?
			$files = scandir($path);
			$logs = array_diff($files, array('..', '.'));
			natsort($logs);
			foreach ($logs as $log) {
				$file = file_get_contents("$path/$log");
				$size = filesize("$path/$log");
				$filedate = date ("F d Y H:i:s.", filemtime("$path/$log"));
				echo "$path/$log<br>";
				$desc = "";
				if (preg_match('/^step(\d*)\.log/', $log, $matches)) {
					//echo "<pre>";
					//print_r($matches);
					//echo "</pre>";
					$step = $matches[1];
					$command = $commands['reg'][$step];
					$desc = $descriptions['reg'][$step];
				}
				elseif (preg_match('/^supplement-step(\d*)\.log/', $log, $matches)) {
					//echo "<pre>";
					//print_r($matches);
					//echo "</pre>";
					$step = $matches[1];
					$command = $commands['supp'][$step];
					$desc = $descriptions['supp'][$step];
				}
				?>
				<details>
					<summary><?="<b>$log</b>"?> <span class="tiny"><?=number_format($size)?> bytes - <?=$filedate?></style> &nbsp; <span style="color: darkred;"><?=$desc?></span></span></summary>
					<pre style="font-size:9pt; background-color: #EEEEEE">
<?=$file?>
					</pre>
				</details>
				<?
			}
		}
		else {
			echo "<b>$path does not exist</b><br><br>Perhaps data is still being downloaded by the pipeline.pl program?<br>";
		}
	}


	/* -------------------------------------------- */
	/* ------- DisplayFiles ----------------------- */
	/* -------------------------------------------- */
	function DisplayFiles($id, $analysisid, $fileviewtype) {

		/* check input parameters */
		if (!ValidID($analysisid,'Analysis ID')) { return; }
	
		$sqlstring = "select * from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id left join pipelines e on e.pipeline_id = a.pipeline_id where a.analysis_id = $analysisid";
		//echo $sqlstring;
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$uid = $row['uid'];
		$studynum = $row['study_num'];
		$pipelinename = $row['pipeline_name'];
		$pipelineid = $row['pipeline_id'];
		$pipeline_level = $row['pipeline_level'];
		$pipelinedirectory = $row['pipeline_directory'];
		
		/* build navigation bar */
		//$urllist['Analysis'] = "analysis.php";
		//$urllist['Pipelines'] = "pipelines.php";
		//$urllist["$pipelinename"] = "pipelines.php?action=editpipeline&id=$pipelineid";
		//$urllist['Analysis list'] = "analysis.php?action=viewanalyses&id=$pipelineid";
		//NavigationBar("File list for $uid &rarr; $studynum &rarr; $pipelinename", $urllist);
		
		//$path = $GLOBALS['pipelinedatapath'] . "/$uid/$studynum/$pipelinename/";
		/* build the correct path */
		//if (($pipeline_level == 1) && ($pipelinedirectory == "")) {
		if ($pipeline_level == 1) {
			$path = $GLOBALS['cfg']['analysisdir'] . "/$uid/$studynum/$pipelinename";
			//echo "(1) Path is [$path]<br>";
		}
		//elseif (($pipeline_level == 0) || ($pipelinedirectory != "")) {
		elseif ($pipeline_level == 0) {
			$path = $GLOBALS['cfg']['mountdir'] . "/$pipelinedirectory/$uid/$studynum/$pipelinename/pipeline";
			//echo "(2) Path is [$path]<br>";
		}
		else {
			$path = $GLOBALS['cfg']['groupanalysisdir'] . "/$pipelinename/pipeline";
			//echo "(3) Path is [$path]<br>";
		}
		
		$origfileslog = $path . "origfiles.log";
		$finfo = finfo_open(FILEINFO_MIME);
		if ((!file_exists($origfileslog)) || ($fileviewtype == "filesystem")) {
			$files = find_all_files($path);
			//print_r($files);
			?>
			Showing files from <b><?=$path?></b> (<?=count($files)?> files) <span class="tiny">Reading from filesystem</span>
			<br><br>
			<table cellspacing="0" cellpadding="2">
				<tr>
					<td style="font-weight: bold; border-bottom:2px solid #999999">File</td>
					<td style="font-weight: bold; border-bottom:2px solid #999999">Timestamp</td>
					<td style="font-weight: bold; border-bottom:2px solid #999999">Permissions</td>
					<td style="font-weight: bold; border-bottom:2px solid #999999">Size <span class="tiny">bytes</span></td>
				</tr>
			<?
			foreach ($files as $line) {
				//$file\t$mtime\t$perm\t$isdir\t$islink\t$size
				
				$timestamp2 = "N/A";
				$perm2 = 'N/A';
				$islink2 = '';
				$isdir2 = '';
				$size2 = 0;
				list($file,$timestamp1,$perm1,$isdir1,$islink1,$size1) = explode("\t",$line);
				
				$fullpath = $GLOBALS['cfg']['mountdir'] . "/$file";
				
				if (is_link($fullpath)) { $islink2 = 1; }
				if (is_dir($fullpath)) { $isdir2 = 1; }
				if (file_exists($fullpath)) {
					$timestamp2 = filemtime($fullpath);
					$perm2 = substr(sprintf('%o', fileperms($fullpath)), -4);
					$size2 = filesize($fullpath);
					$filetype = "";
					if (stristr(strtolower($file),'.nii') !== FALSE) { $filetype = 'nifti'; }
					if (stristr(strtolower($file),'.nii.gz') !== FALSE) { $filetype = 'nifti'; }
					if (stristr(strtolower($file),'.inflated') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.smoothwm') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.sphere') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.pial') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.fsm') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.orig') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.png') !== FALSE) { $filetype = 'image'; }
					if (stristr(strtolower($file),'.ppm') !== FALSE) { $filetype = 'image'; }
					if (stristr(strtolower($file),'.jpg') !== FALSE) { $filetype = 'image'; }
					if (stristr(strtolower($file),'.jpeg') !== FALSE) { $filetype = 'image'; }
					if (stristr(strtolower($file),'.gif') !== FALSE) { $filetype = 'image'; }
					if (stristr(strtolower($file),'.txt') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),'.log') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),'.sh') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),'.job') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".o") !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".e") !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".par") !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".mat") !== FALSE) { $filetype = 'text'; }
					if ($istext) { $filetype = "text"; }
					//echo "[$file $filetype]";
				}
				$filecolor = "black";
				if ($islink2) { $filecolor = "red"; } else { $filecolor = ''; }
				if ($isdir1) { $filecolor = "darkblue"; $fileweight = ''; } else { $filecolor = ''; $fileweight = ''; }
				
				$clusterpath = str_replace($GLOBALS['cfg']['mountdir'],'',$path);
				$displayfile = str_replace($clusterpath,'',$file);
				$lastslash = strrpos($displayfile,'/');
				$displayfile = substr($displayfile,0,$lastslash) . '<b>' . substr($displayfile,$lastslash) . '</b>';
				
				$displayperms = '';
				for ($i=1;$i<=3;$i++) {
					switch (substr($perm2,$i,1)) {
						case 0: $displayperms .= '---'; break;
						case 1: $displayperms .= '--x'; break;
						case 2: $displayperms .= '-w-'; break;
						case 3: $displayperms .= '-wx'; break;
						case 4: $displayperms .= 'r--'; break;
						case 5: $displayperms .= 'r-x'; break;
						case 6: $displayperms .= 'rw-'; break;
						case 7: $displayperms .= 'rwx'; break;
					}
				}
				?>
				<tr>
					<td style="font-size:10pt; border-bottom: solid 1px #DDDDDD; color:<?=$filecolor?>; font-weight: <?=$fileweight?>">
					<?
						$fullpath = $GLOBALS['cfg']['mountdir'] . "/$file";
						switch ($filetype) {
							case 'text':
					?>
					<a href="viewfile.php?file=<?=$fullpath?>"><span style="color:<?=$filecolor?>; font-weight: <?=$fileweight?>"><?=$displayfile?></span></a>
					<?
								break;
							case 'image':
					?>
					<a href="viewimagefile.php?file=<?=$fullpath?>"><span style="color:<?=$filecolor?>; font-weight: <?=$fileweight?>"><?=$displayfile?></span></a>
					<?
								break;
							case 'nifti':
							case 'mesh':
					?>
					<a href="viewimage.php?type=<?=$filetype?>&filename=<?=$fullpath?>"><span style="color:<?=$filecolor?>; font-weight: <?=$fileweight?>"><?=$displayfile?></span></a>
					<?
								break;
							default:
					?>
					<?=$displayfile?>
					<? } ?>
					</td>
					<td style="font-size:10pt; border-bottom: solid 1px #DDDDDD"><?=date("M j, Y H:i:s",$timestamp2)?></span></td>
					<td style="font-size:10pt; border-bottom: solid 1px #DDDDDD"><?=$displayperms?></td>
					<td style="font-size:10pt; border-bottom: solid 1px #DDDDDD"><?=number_format($size2)?></td>
				</tr>
				<?
			}
		}
		else {
			$origfiles = file_get_contents($path . "origfiles.log");
			
			$files = explode("\n",trim($origfiles));
			?>
			Showing files from <b><?=$path?></b> (<?=count($files)?> files) <span class="tiny">Reading from origfiles.log</span> Read from <a href="analysis.php?action=viewfiles&analysisid=<?=$analysisid?>&fileviewtype=filesystem">filesystem</a>
			<br><br>
			<table cellspacing="0" cellpadding="2">
				<tr>
					<td style="font-weight: bold; border-bottom:2px solid #999999">File</td>
					<td style="font-weight: bold; border-bottom:2px solid #999999">Timestamp</td>
					<td style="font-weight: bold; border-bottom:2px solid #999999">Permissions</td>
					<td style="font-weight: bold; border-bottom:2px solid #999999">Size <span class="tiny">bytes</span></td>
				</tr>
			<?
			foreach ($files as $line) {
				//$file\t$mtime\t$perm\t$isdir\t$islink\t$size
				
				$timestamp2 = "N/A";
				$perm2 = 'N/A';
				$islink2 = '';
				$isdir2 = '';
				$size2 = 0;
				list($file,$timestamp1,$perm1,$isdir1,$islink1,$size1) = explode("\t",$line);
				
				if (file_exists($GLOBALS['cfg']['mountdir'] . "/$file")) {
					$filetype = "";
					if (stristr(strtolower($file),'.nii') !== FALSE) { $filetype = 'nifti'; }
					if (stristr(strtolower($file),'.nii.gz') !== FALSE) { $filetype = 'nifti'; }
					if (stristr(strtolower($file),'.inflated') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.smoothwm') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.sphere') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.pial') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.fsm') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.orig') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.png') !== FALSE) { $filetype = 'image'; }
					if (stristr(strtolower($file),'.ppm') !== FALSE) { $filetype = 'image'; }
					if (stristr(strtolower($file),'.jpg') !== FALSE) { $filetype = 'image'; }
					if (stristr(strtolower($file),'.jpeg') !== FALSE) { $filetype = 'image'; }
					if (stristr(strtolower($file),'.gif') !== FALSE) { $filetype = 'image'; }
					if (stristr(strtolower($file),'.txt') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),'.log') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),'.sh') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),'.job') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".o") !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".e") !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".par") !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".mat") !== FALSE) { $filetype = 'text'; }
					if ($istext) { $filetype = "text"; }
					if ($size1 < 1) { $filetype = ""; }
					
				}
				$filecolor = "black";
				if ($islink2) { $filecolor = "red"; } else { $filecolor = ''; }
				if ($isdir1) { $filecolor = "darkblue"; $fileweight = ''; } else { $filecolor = ''; $fileweight = ''; }
				
				$clusterpath = str_replace($GLOBALS['cfg']['mountdir'],'',$path);
				$displayfile = str_replace($clusterpath,'',$file);
				$lastslash = strrpos($displayfile,'/');
				$displayfile = substr($displayfile,0,$lastslash) . '<b>' . substr($displayfile,$lastslash) . '</b>';
				
				$displayperms1 = '';
				for ($i=1;$i<=3;$i++) {
					switch (substr($perm1,$i,1)) {
						case 0: $displayperms1 .= '---'; break;
						case 1: $displayperms1 .= '--x'; break;
						case 2: $displayperms1 .= '-w-'; break;
						case 3: $displayperms1 .= '-wx'; break;
						case 4: $displayperms1 .= 'r--'; break;
						case 5: $displayperms1 .= 'r-x'; break;
						case 6: $displayperms1 .= 'rw-'; break;
						case 7: $displayperms1 .= 'rwx'; break;
					}
				}
				?>
				<tr>
					<td style="font-size:9pt; border-bottom: solid 1px #DDDDDD; color:<?=$filecolor?>; font-weight: <?=$fileweight?>">
					<?
						$fullpath = $GLOBALS['cfg']['mountdir'] . "/$file";
						switch ($filetype) {
							case 'text':
					?>
					<a href="viewfile.php?file=<?=$fullpath?>"><span style="color:<?=$filecolor?>; font-weight: <?=$fileweight?>"><?=$displayfile?></span></a>
					<?
								break;
							case 'image':
					?>
					<a href="viewimagefile.php?file=<?=$fullpath?>"><span style="color:<?=$filecolor?>; font-weight: <?=$fileweight?>"><?=$displayfile?></span></a>
					<?
								break;
							case 'nifti':
							case 'mesh':
					?>
					<a href="viewimage.php?type=<?=$filetype?>&filename=<?=$fullpath?>"><span style="color:<?=$filecolor?>; font-weight: <?=$fileweight?>"><?=$displayfile?></span></a>
					<?
								break;
							default:
					?>
					<?=$displayfile?>
					<? } ?>
					</td>
					<td style="font-size:10pt; border-bottom: solid 1px #DDDDDD"><?=date("M j, Y H:i:s",$timestamp1)?><? //if ($timestamp1 != $timestamp2) { echo "&nbsp;<span class='smalldiff'>$timestamp2</span>"; } ?></span></td>
					<td style="font-size:10pt; border-bottom: solid 1px #DDDDDD"><?=$displayperms1?><? //if ($perm1 != $perm2) { echo "&nbsp;<span class='smalldiff'>$perm2</span>"; } ?></td>
					<td style="font-size:10pt; border-bottom: solid 1px #DDDDDD"><?=number_format($size1)?><? //if ($size1 != $size2) { echo "&nbsp;<span class='smalldiff'>" . number_format($size2) . "</span>"; } ?></td>
				</tr>
				<?
			}
			?>
			</table>
			<?
		}
	}


	/* -------------------------------------------- */
	/* ------- DisplayJob ------------------------- */
	/* -------------------------------------------- */
	function DisplayJob($id) {
		if (($id == 0) || ($id == '')) {
			echo "Invalid cluster job ID";
		}
		else {
			$systemstring = "ssh " . $GLOBALS['cfg']['clustersubmithost'] . " qstat -j $id";
			$out = shell_exec($systemstring);
			PrintVariable($out,'output');
		}
	}
	
?>

<? include("footer.php") ?>
