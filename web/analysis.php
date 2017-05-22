<?
 // ------------------------------------------------------------------------------
 // NiDB analysis.php
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
	require "includes.php";
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
	
	$returnpage = GetVariable("returnpage");
	
	/* determine action */
	switch ($action) {
		case 'viewjob': DisplayJob($id); break;
		case 'viewlists': DisplayPipelineLists($id, $listtype); break;
		case 'viewanalyses': DisplayAnalysisList($id, $numperpage, $pagenum); break;
		case 'viewfailedanalyses': DisplayFailedAnalysisList($id, $numperpage, $pagenum); break;
		case 'deleteanalyses':
			DeleteAnalyses($id, $analysisids);
			DisplayAnalysisList($id, $numperpage, $pagenum);
			break;
		case 'copyanalyses':
			CopyAnalyses($analysisids, $destination);
			DisplayAnalysisList($id, $numperpage, $pagenum);
			break;
		case 'createlinks':
			CreateLinks($analysisids, $destination);
			DisplayAnalysisList($id, $numperpage, $pagenum);
			break;
		case 'rerunresults':
			RerunResults($analysisids);
			DisplayAnalysisList($id, $numperpage, $pagenum);
			break;
		case 'runsupplement':
			RunSupplement($analysisids);
			DisplayAnalysisList($id, $numperpage, $pagenum);
			break;
		case 'markbad':
			MarkAnalysis($analysisids, 'bad');
			DisplayAnalysisList($id, $numperpage, $pagenum);
			break;
		case 'markgood':
			MarkAnalysis($analysisids, 'good');
			DisplayAnalysisList($id, $numperpage, $pagenum);
			break;
		case 'markcomplete':
			MarkComplete($analysisids);
			DisplayAnalysisList($id, $numperpage, $pagenum);
			break;
		case 'rechecksuccess':
			RecheckSuccess($analysisids);
			DisplayAnalysisList($id, $numperpage, $pagenum);
			break;
		case 'viewlogs': DisplayLogs($id, $analysisid); break;
		case 'viewfiles': DisplayFiles($id, $analysisid, $fileviewtype); break;
		case 'setanalysisnotes':
			SetAnalysisNotes($analysisid, $analysisnotes);
			DisplayAnalysisList($id, $numperpage, $pagenum);
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
	
		echo "id: $id; ";

		/*disable this pipeline */
		DisablePipeline($id);
		
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
			
			$sqlstring = "insert into fileio_requests (fileio_operation,data_type,data_id,username,requestdate) values ('delete','$analysislevel',$analysisid,'" . $GLOBALS['username'] . "', now())";
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
		
		foreach ($analysisids as $analysisid) {
		
			if (!ValidID($analysisid,'Analysis ID')) { return; }
			
			$sqlstring = "insert into fileio_requests (fileio_operation, data_type, data_id, data_destination, username, requestdate) values ('copy', 'analysis', $analysisid, '$destination', '" . $GLOBALS['username'] . "', now())";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			
			?><span class="codelisting"><?=GetAnalysisPath($analysisid)?> queued for copy to <?=$destination?></span><br><?
			
		}
	}

	
	/* -------------------------------------------- */
	/* ------- CreateLinks ------------------------ */
	/* -------------------------------------------- */
	function CreateLinks($analysisids, $destination) {
	
		$destination = mysqli_real_escape_string($GLOBALS['linki'], $destination);
		
		foreach ($analysisids as $analysisid) {
		
			if (!ValidID($analysisid,'Analysis ID')) { return; }
			
			$sqlstring = "insert into fileio_requests (fileio_operation, data_type, data_id, data_destination, username, requestdate) values ('createlinks', 'analysis', $analysisid, '$destination', '" . $GLOBALS['username'] . "', now())";
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
			}
			else {
				$sqlstring = "update analysis set analysis_isbad = 0 where analysis_id = $analysisid";
			}
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			
			?><span class="codelisting"><?=GetAnalysisPath($analysisid)?> marked as bad</span><br><?
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
	/* ------- RecheckSuccess --------------------- */
	/* -------------------------------------------- */
	function RecheckSuccess($analysisids) {
		
		foreach ($analysisids as $analysisid) {
		
			if (!ValidID($analysisid,'Analysis ID')) { return; }
			
			$sqlstring = "insert into fileio_requests (fileio_operation, data_type, data_id, username, requestdate) values ('rechecksuccess', 'analysis', $analysisid, '" . $GLOBALS['username'] . "', now())";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			
			?><span class="codelisting"><?=GetAnalysisPath($analysisid)?> to be rechecked for successful file(s)</span><br><?
		}
	}

	
	/* -------------------------------------------- */
	/* ------- DisablePipeline -------------------- */
	/* -------------------------------------------- */
	function DisablePipeline($id) {
		/* check input parameters */
		if (!ValidID($id,'Pipeline ID')) { return; }
		
		$sqlstring = "update pipelines set pipeline_enabled = 0 where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
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
			case 'failedanalyses': $sqlstring = "select uid, study_num, study_id, subject_id, study_datetime from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and a.analysis_status in ('NoMatchingStudies','NoMatchingStudyDependency','IncompleteDependency','BadDependency') order by a.analysis_status desc, study_datetime desc"; break;
			case 'successfulanalyses':
			default:
				?>Successful analyses<br><br><?
				$sqlstring = "select d.uid, b.study_num, b.study_id, d.subject_id, b.study_datetime, weekofyear(b.study_datetime) 'week', c.project_id from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and a.analysis_status not in ('NoMatchingStudies','NoMatchingStudyDependency','IncompleteDependency','BadDependency') order by a.analysis_status desc, study_datetime desc";
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
	function DisplayAnalysisList($id, $numperpage, $pagenum) {

		/* check input parameters */
		if (!ValidID($id,'Pipeline ID')) { return; }
	
		$sqlstring = "select * from pipelines where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$pipeline_name = $row['pipeline_name'];
		$pipeline_level = $row['pipeline_level'];
		$pipeline_version = $row['pipeline_version'];
		$pipeline_status = $row['pipeline_status'];
		$pipeline_statusmessage = $row['pipeline_statusmessage'];
		$pipeline_laststart = $row['pipeline_laststart'];
		$pipeline_lastfinish = $row['pipeline_lastfinish'];
		$pipeline_lastcheck = $row['pipeline_lastcheck'];
		$isenabled = $row['pipeline_enabled'];
	
		$urllist['Pipelines'] = "pipelines.php";
		$urllist["$pipeline_name"] = "pipelines.php?action=editpipeline&id=$id";
		$urllist["Analysis List"] = "analysis.php?action=viewanalyses&id=$id";
		NavigationBar("Analyses", $urllist);

		DisplayPipelineStatus($pipeline_name, $isenabled, $id, $pipeline_status, $pipeline_statusmessage, $pipeline_laststart, $pipeline_lastfinish, $pipeline_lastcheck);
		
		/* prep the pagination */
		if ($numperpage == "") { $numperpage = 500; }
		if (($pagenum == "") || ($pagenum < 1)) { $pagenum = 1; }
		$limitstart = ($pagenum-1)*$numperpage;
		$limitcount = $numperpage;

		/* create the color lookup table */
		$colors = GenerateColorGradient();
		
		/* run the sql query here to get the row count */
		$sqlstring = "select *, timediff(analysis_enddate, analysis_startdate) 'analysis_time', timediff(analysis_clusterenddate, analysis_clusterstartdate) 'cluster_time' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and analysis_status not in ('NoMatchingStudies','NoMatchingStudyDependency','IncompleteDependency','BadDependency')";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$numrows = mysqli_num_rows($result);
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
			$("a.viewlog").click(function(e) {
				var id = jQuery(this).attr("id");
				e.preventDefault();
				$("#dialogbox").load("viewanalysis.php?action=viewlogs&analysisid=" + id).dialog({height:800, width:1200});
			});
			$("a.viewfiles").click(function(e) {
				var id = jQuery(this).attr("id");
				e.preventDefault();
				$("#dialogbox").load("viewanalysis.php?action=viewfiles&analysisid=" + id).dialog({height:800, width:1200});
			});
			$("a.viewresults").click(function(e) {
				var id = jQuery(this).attr("id");
				e.preventDefault();
				$("#dialogbox").load("viewanalysis.php?action=viewresults&analysisid=" + id + "&studyid=<?=$study_id?>").dialog({height:800, width:1200});
			});
			$("a.viewhistory").click(function(e) {
				var id = jQuery(this).attr("id");
				e.preventDefault();
				$("#dialogbox").load("viewanalysis.php?action=viewhistory&analysisid=" + id + "&studyid=<?=$study_id?>&pipelineid=<?=$id?>&pipelineversion=<?=$pipeline_version?>").dialog({height:800, width:1200});
			});
		});
		</script>
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
		<table width="100%" class="tablepage">
			<form method="post" action="analysis.php" id="numperpageform">
			<input type="hidden" name="action" value="viewanalyses">
			<input type="hidden" name="id" value="<?=$id?>">
			<tr>
				<td class="label"><?=$numrows?> analyses</td>
				<td class="pagenum">
					Page <?=$pagenum?> of <?=$numpages?> <span class="tiny">(<?=$numperpage?>/page)</span>
					<select name="numperpage" title="Change number per page" onChange="numperpageform.submit()">
						<option value="100" <?if ($numperpage == 100) { echo "selected"; } ?>>100
						<option value="500" <?if ($numperpage == 500) { echo "selected"; } ?>>500
						<option value="1000" <?if ($numperpage == 1000) { echo "selected"; } ?>>1,000
						<option value="2000" <?if ($numperpage == 2000) { echo "selected"; } ?>>2,000
						<option value="5000" <?if ($numperpage == 5000) { echo "selected"; } ?>>5,000
						<option value="10000" <?if ($numperpage == 10000) { echo "selected"; } ?>>10,000
						<option value="50000" <?if ($numperpage == 50000) { echo "selected"; } ?>>50,000
					</select>
				</td>
				<td class="middle">&nbsp;</td>
				<td class="firstpage" title="First page"><a href="analysis.php?action=viewanalyses&id=<?=$id?>&numperpage=<?=$numperpage?>&pagenum=1">&#171;</a></td>
				<td class="previouspage" title="Previous page"><a href="analysis.php?action=viewanalyses&id=<?=$id?>&numperpage=<?=$numperpage?>&pagenum=<?=($pagenum-1)?>">&lsaquo;</a></td>
				<td title="Refresh page"><a href="" style="margin-left:20px; margin-right:20px; font-size:14pt">&#10227;</a></td>
				<td class="nextpage" title="Next page"><a href="analysis.php?action=viewanalyses&id=<?=$id?>&numperpage=<?=$numperpage?>&pagenum=<?=($pagenum+1)?>">&rsaquo;</a></td>
				<td class="lastpage" title="Last page"><a href="analysis.php?action=viewanalyses&id=<?=$id?>&numperpage=<?=$numperpage?>&pagenum=<?=$numpages?>">&#187;</a></td>
			</tr>
			</form>
		</table>
		<form method="post" name="studieslist" action="analysis.php">
		<input type="hidden" name="action" value="deleteanalyses" id="studieslistaction">
		<input type="hidden" name="destination" value="" id="studieslistdestination">
		<input type="hidden" name="analysisnotes" value="">
		<input type="hidden" name="analysisid" value="">
		<input type="hidden" name="id" value="<?=$id?>">
		<p id="msg" style="color: #0A0; text-align: center;">&nbsp;</p>		
		<table id="analysistable" class="sortable smallgraydisplaytable" width="100%">
			<thead>
				<tr>
					<th data-sort="string-ins"><input type="checkbox" id="studiesall">Study</th>
					<th data-sort="string-ins">Visit</th>
					<th data-sort="int">Pipeline<br>version</th>
					<? if ($pipeline_level == 1) { ?>
					<th>Study date</th>
					<th data-sort="int"># series</th>
					<? } ?>
					<th data-sort="string-ins">Status <span class="tiny">flags</span></th>
					<th data-sort="string-ins">Successful</th>
					<th>Logs</th>
					<th>History</th>
					<th>Files</th>
					<th>Results</th>
					<th>Notes</th>
					<th data-sort="string-ins">Message</th>
					<th data-sort="string-ins">Size<br><span class="tiny">bytes</span></th>
					<th data-sort="string-ins">Hostname</th>
					<th>Setup time<br><span class="tiny">completed date</span></th>
					<th>Cluster time<br><span class="tiny">completed date</span></th>
					<th>Operations<br><input type="checkbox" id="analysesall"><span class="tiny">Select All</span></th>
				</tr>
			</thead>
			<tbody>
				<?
					$sqlstring = "select *, timediff(analysis_enddate, analysis_startdate) 'analysis_time', timediff(analysis_clusterenddate, analysis_clusterstartdate) 'cluster_time' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and a.analysis_status not in ('NoMatchingStudies','NoMatchingStudyDependency','IncompleteDependency','BadDependency') order by a.analysis_status desc, study_datetime desc limit $limitstart, $limitcount";
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
							$notescolor = "#DDD";
						}
						else {
							$notestitle = $notes;
							$notescolor = "C00";
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
								$systemstring = "ssh $pipeline_submithost qstat -j $analysis_qsubid";
							
								if (trim($out) == "hi") {
									?><img src="images/alert.png" title="Analysis is marked as running, but the cluster job is not.<br><br>This means the analysis is being setup and the data is being copied or the cluster job failed. Check log files for error"><?
								}
								?>
								<a href="<?=$GLOBALS['cfg']['siteurl']?>/analysis.php?action=viewjob&id=<?=$analysis_qsubid?>">processing</a>
								<?
							}
							else {
								if ($analysis_qsubid == 0) {
									echo "Preparing data";
								}
								else {
									switch ($analysis_status) {
										case 'pending':
											$tip = "Data has finished copying for this analysis, and job has been submitted. Waiting for the job to check in with NiDB";
											break;
										case 'complete':
											$tip = "Analysis is complete";
											break;
									}
									?><span style="text-decoration: underline; text-decoration-style: dashed; text-decoration-color: #aaa" title="<?=$tip?>"><?=$analysis_status?></span><?
								}
							}
							
							if ($analysis_runsupplement) { ?> <span class="tiny">supplement</span><? }
							if ($analysis_rerunresults) { ?> <span class="tiny">rerun results</span><? }
						?>
					</td>
					<td style="font-weight: bold; color: green"><? if ($analysis_iscomplete) { echo "&#x2713;"; } ?></td>
					<? if ($analysis_status != "") { ?>
					<td><a href="#" class="viewlog" id="<?=$analysis_id?>" title="View log files"><img src="images/log16.png"></a></td>
					<td><a href="#" class="viewhistory" id="<?=$analysis_id?>" title="View analysis history"><img src="images/history16.png"></a></td>
					<td><a href="#" class="viewfiles" id="<?=$analysis_id?>" title="View file listing"><img src="images/folder16.png"></a></td>
					<td><a href="#" class="viewresults" id="<?=$analysis_id?>" title="View analysis results"><img src="images/chart16.png"></a></td>
					<? } else { ?>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<? } ?>
					<td>
						<span onClick="GetAnalysisNotes(<?=$id?>, <?=$analysis_id?>);" style="cursor:hand; font-size:14pt; color: <?=$notescolor?>" title="<?=$notestitle?>">&#9998;</span>
					</td>
					<td style="font-size:9pt; white-space:nowrap">
						<?=$analysis_statusmessage?><br>
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
					<td class="allanalyses" ><input type="checkbox" name="analysisids[]" value="<?=$analysis_id?>"></td>
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
				
				<tfoot>
				<tr style="color: #444; font-size:12pt; font-weight:bold">
					<td colspan="8" valign="top" style="background-color: #fff">
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
					<td colspan="8" align="right" style="background-color: #fff; font-size: 12pt">
					With selected:&nbsp;<br><br>
					<input type="submit" value="Delete" style="border: 1px solid red; background-color: pink; width:150px; margin:4px" onclick="document.studieslist.action.value='deleteanalyses';return confirm('Are you absolutely sure you want to DELETE the selected analyses?')" title="<b style='color:pink'>Pipeline will be disabled. Wait until the deletions are compelte before reenabling the pipeline</b><Br> This will delete the selected analyses, which will be regenerated using the latest pipeline version">
					<br><br><br>
					<input type="button" name="copyanalyses" value="Copy analyses to..." style="width: 150px; margin:4px" onclick="document.studieslist.action='analysis.php';document.studieslist.action.value='copyanalyses';GetDestination()">
					<br>
					<input type="button" name="createlinks" value="Create links..." style="width: 150px; margin:4px" onclick="document.studieslist.action='analysis.php';document.studieslist.action.value='createlinks';GetDestination2()" title="Creates a directory called 'data' which contains links to all of the selected studies">
					<br>
					<input type="button" name="rerunresults" value="Re-run results script" style="width: 150px; margin:4px" onclick="document.studieslist.action='analysis.php';document.studieslist.action.value='rerunresults';document.studieslist.submit();" title="This will delete any existing results inserted into NiDB and re-run the results script">
					<br>
					<input type="button" name="runsupplement" value="Run supplement script" style="width: 150px; margin:4px" onclick="document.studieslist.action='analysis.php';document.studieslist.action.value='runsupplement';document.studieslist.submit();" title="Run the script specified in the supplemental command script. This will not download new data or re-download existing data. It will only perform commands on the existing files in the analysis directory">
					<br>
					<input type="button" name="rechecksuccess" value="Re-check if successful" style="width: 150px; margin:4px" onclick="document.studieslist.action='analysis.php';document.studieslist.action.value='rechecksuccess';document.studieslist.submit();" title="This option will check the selected analyses against the 'successfully completed files' field and mark them as successful if the file(s) exist">
					<br><br>
					<input type="button" name="markasbad" value="Mark as bad" style="width: 150px; margin:4px" onclick="document.studieslist.action='analysis.php';document.studieslist.action.value='markbad'; MarkAnalysis()" title="Mark the analyses as bad so they will not be used in dependent pipelines">
					<br>
					<input type="button" name="markasgood" value="Mark as good" style="width: 150px; margin:4px" onclick="document.studieslist.action='analysis.php';document.studieslist.action.value='markgood'; MarkAnalysis()" title="Unmark an analysis as bad">
					<br>
					<input type="button" name="markcomplete" value="Mark complete" style="width: 150px; margin:4px" onclick="document.studieslist.action='analysis.php';document.studieslist.action.value='markcomplete'; MarkAnalysis()" title="Mark the analysis as complete. In case the job was killed or died outside of the pipeline system. Also clears pending jobs and any flags as 'run supplement' or 'rerun results'">&nbsp;
					</td>
				</tr>
				</tfoot>
			</tbody>
		</table>
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
		$pipeline_level = $row['pipeline_level'];
		$pipeline_status = $row['pipeline_status'];
		$pipeline_statusmessage = $row['pipeline_statusmessage'];
		$pipeline_laststart = $row['pipeline_laststart'];
		$pipeline_lastfinish = $row['pipeline_lastfinish'];
		$pipeline_lastcheck = $row['pipeline_lastcheck'];
		$isenabled = $row['pipeline_enabled'];
	
		//$urllist['Analysis'] = "analysis.php";
		$urllist['Pipelines'] = "pipelines.php";
		$urllist["$pipeline_name"] = "pipelines.php?action=editpipeline&id=$id";
		$urllist["Analysis List"] = "analysis.php?action=viewanalyses&id=$id";
		NavigationBar("Ignored studies for $pipeline_name", $urllist);
		
		DisplayPipelineStatus($title, $isenabled, $id, $pipeline_status, $pipeline_statusmessage, $pipeline_laststart, $pipeline_lastfinish, $pipeline_lastcheck);
		
		/* prep the pagination */
		if ($numperpage == "") { $numperpage = 1000; }
		if (($pagenum == "") || ($pagenum < 1)) { $pagenum = 1; }
		$limitstart = ($pagenum-1)*$numperpage;
		$limitcount = $numperpage;
		
		/* run the sql query here to get the row count */
		$sqlstring = "select *, timediff(analysis_enddate, analysis_startdate) 'analysis_time', timediff(analysis_clusterenddate, analysis_clusterstartdate) 'cluster_time' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and analysis_status in ('NoMatchingStudies','NoMatchingStudyDependency','IncompleteDependency','BadDependency')";
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
		
		<table id="analysistable" class="smallgraydisplaytable dropshadow">
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
					$sqlstring = "select * from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and a.analysis_status in ('NoMatchingStudies','NoMatchingStudyDependency','IncompleteDependency','BadDependency') order by a.analysis_status desc, study_datetime desc limit $limitstart, $limitcount";
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
						<? if (trim($analysis_datalog) != "") { ?>
						<a href="#" id="viewlog<?=$analysis_id?>">view log</a>
						<div id="datalog<?=$analysis_id?>" title="Data log" style="display:none;">
						<pre style="font-size:9pt; border: 1px solid gray; padding: 5px"><?=$analysis_datalog?></pre>
						</div>
						<script>
							$(document).ready(function() {
								$("a#viewlog<?=$analysis_id?>").click(function(e) {
									e.preventDefault();
									$("#datalog<?=$analysis_id?>").dialog({height:500, width:800});
								});
							});
						</script>
						<? } ?>
					</td>
					<td class="allanalyses" ><input type="checkbox" name="analysisids[]" value="<?=$analysis_id?>"></td>
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
		$urllist['Pipelines'] = "pipelines.php";
		$urllist["$pipelinename"] = "pipelines.php?action=editpipeline&id=$pipelineid";
		$urllist['Analysis list'] = "analysis.php?action=viewanalyses&id=$pipelineid";
		NavigationBar("Logs for $uid &rarr; $studynum &rarr; $pipelinename", $urllist);

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
			$path = $GLOBALS['cfg']['mountdir'] . "$pipelinedirectory/$uid/$studynum/$pipelinename/pipeline";
			#echo "(2) Path is [$path]<br>";
		}
		else {
			$path = $GLOBALS['cfg']['groupanalysisdir'] . "/$pipelinename/pipeline";
			#echo "(3) Path is [$path]<br>";
		}
		
		/* check if the path exists */
		if (file_exists($path)) {
			?>
			Showing blog files from <b><?=$path?></b>
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
					echo "<pre>";
					print_r($matches);
					echo "</pre>";
					$step = $matches[1];
					$command = $commands['reg'][$step];
					$desc = $descriptions['reg'][$step];
				}
				elseif (preg_match('/^supplement-step(\d*)\.log/', $log, $matches)) {
					echo "<pre>";
					print_r($matches);
					echo "</pre>";
					$step = $matches[1];
					$command = $commands['supp'][$step];
					$desc = $descriptions['supp'][$step];
				}
				?>
				<details>
					<summary><?="$path/<b>$log</b>"?> <span class="tiny"><?=number_format($size)?> bytes - <?=$filedate?></style> &nbsp; <span style="color: darkred;"><?=$desc?></span></span></summary>
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
		$urllist['Pipelines'] = "pipelines.php";
		$urllist["$pipelinename"] = "pipelines.php?action=editpipeline&id=$pipelineid";
		$urllist['Analysis list'] = "analysis.php?action=viewanalyses&id=$pipelineid";
		NavigationBar("File list for $uid &rarr; $studynum &rarr; $pipelinename", $urllist);
		
		//$path = $GLOBALS['pipelinedatapath'] . "/$uid/$studynum/$pipelinename/";
		/* build the correct path */
		//if (($pipeline_level == 1) && ($pipelinedirectory == "")) {
		if ($pipeline_level == 1) {
			$path = $GLOBALS['cfg']['analysisdir'] . "/$uid/$studynum/$pipelinename";
			//echo "(1) Path is [$path]<br>";
		}
		//elseif (($pipeline_level == 0) || ($pipelinedirectory != "")) {
		elseif ($pipeline_level == 0) {
			$path = $GLOBALS['cfg']['mountdir'] . "$pipelinedirectory/$uid/$studynum/$pipelinename/pipeline";
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
				
				if (is_link('/mount' . $file)) { $islink2 = 1; }
				if (is_dir('/mount' . $file)) { $isdir2 = 1; }
				if (file_exists('/mount' . $file)) {
					$timestamp2 = filemtime('/mount' . $file);
					$perm2 = substr(sprintf('%o', fileperms('/mount' . $file)), -4);
					$size2 = filesize('/mount' . $file);
					//if (substr(finfo_file($finfo, "/mount$file"), 0, 4) == 'text') {
					//	$istext = true;
					//}
					//else {
					//	$istext = false;
					//}
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
				
				$clusterpath = str_replace('/mount','',$path);
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
						switch ($filetype) {
							case 'text':
					?>
					<a href="viewfile.php?file=<?="/mount$file"?>"><span style="color:<?=$filecolor?>; font-weight: <?=$fileweight?>"><?=$displayfile?></span></a>
					<?
								break;
							case 'image':
					?>
					<a href="viewimagefile.php?file=<?="/mount$file"?>"><span style="color:<?=$filecolor?>; font-weight: <?=$fileweight?>"><?=$displayfile?></span></a>
					<?
								break;
							case 'nifti':
							case 'mesh':
					?>
					<a href="viewimage.php?type=<?=$filetype?>&filename=<?="/mount$file"?>"><span style="color:<?=$filecolor?>; font-weight: <?=$fileweight?>"><?=$displayfile?></span></a>
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
				
				//if (is_link('/mount' . $file)) { $islink2 = 1; }
				//if (is_dir('/mount' . $file)) { $isdir2 = 1; }
				if (file_exists('/mount' . $file)) {
					#$timestamp2 = filemtime('/mount' . $file);
					#$perm2 = substr(sprintf('%o', fileperms('/mount' . $file)), -4);
					#$size2 = filesize('/mount' . $file);
					//if (substr(finfo_file($finfo, "/mount$file"), 0, 4) == 'text') {
					//	$istext = true;
					//}
					//else {
					//	$istext = false;
					//}
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
				
				$clusterpath = str_replace('/mount','',$path);
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
						switch ($filetype) {
							case 'text':
					?>
					<a href="viewfile.php?file=<?="/mount$file"?>"><span style="color:<?=$filecolor?>; font-weight: <?=$fileweight?>"><?=$displayfile?></span></a>
					<?
								break;
							case 'image':
					?>
					<a href="viewimagefile.php?file=<?="/mount$file"?>"><span style="color:<?=$filecolor?>; font-weight: <?=$fileweight?>"><?=$displayfile?></span></a>
					<?
								break;
							case 'nifti':
							case 'mesh':
					?>
					<a href="viewimage.php?type=<?=$filetype?>&filename=<?="/mount$file"?>"><span style="color:<?=$filecolor?>; font-weight: <?=$fileweight?>"><?=$displayfile?></span></a>
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
	/* ------- DisplayPipelineStatus -------------- */
	/* -------------------------------------------- */
	function DisplayPipelineStatus($pipelinename, $isenabled, $id, $pipeline_status, $pipeline_statusmessage, $pipeline_laststart, $pipeline_lastfinish, $pipeline_lastcheck) {
		
		if (!ValidID($id,'Pipeline ID')) { return; }
		
		?>
		<div align="center">
			<table width="70%" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td style="color: #fff; background-color: #444; font-size: 18pt; text-align: center; padding: 10px"><?=$pipelinename?></td>
				</tr>
				<tr>
					<td align="center">
					<table cellspacing="0" cellpadding="5" width="100%">
						<tr>
							<?
								if ($isenabled) {
									?>
									<td align="center" style="padding: 2px 30px; background-color: #229320; color: #fff; font-size:11pt; border: 1px solid darkgreen">
										<b>Enabled</b><br>
										<a href="pipelines.php?action=disable&returnpage=pipeline&id=<?=$id?>"><img src="images/checkedbox16.png"title="Pipeline enabled, click to disable"></a> <span style="font-size: 8pt">Uncheck the box to stop the pipeline from running</span>
									</td>
									<?
								}
								else {
									?>
									<td align="center" style="padding: 2px 30px; background-color: #8e3023; color: #fff; font-size:11pt; border: 1px solid darkred">
										<b>Disabled</b><br>
										<a href="pipelines.php?action=enable&returnpage=pipeline&id=<?=$id?>"><img src="images/uncheckedbox16.png" title="Pipeline disabled, click to enable"></a> <span style="font-size: 8pt">Check the box to allow the pipeline to run</span>
									</td>
									<?
								}
							?>
							<?
							if ($pipeline_status == "running") {
								?>
								<td  align="center" style="padding: 2px 30px; background-color: #229320; color: #fff; font-size:11pt; border: 1px solid darkgreen"><b>Status:</b> Running (<a href="pipelines.php?action=reset&id=<?=$id?>" style="color: #ccc" title="Reset the status if you KNOW the pipeline has stopped running... ie, it hasn't updated the status in a couple days">reset</a>)
								</td>
							<? } else { ?>
								<td  align="center" style="padding: 2px 30px; background-color: #8e3023; color: #fff; font-size:11pt; border: 1px solid darkred"><b>Status:</b><?=$pipeline_status ?></td>
							<? } ?>
							<td align="center"><b>Last status message:</b><br><?=$pipeline_statusmessage ?></td>
							<td style="font-size:8pt">
								<b>Last start</b> <?=$pipeline_laststart ?><br>
								<b>Last finish</b> <?=$pipeline_lastfinish ?><br>
								<b>Last check</b> <?=$pipeline_lastcheck ?><br>
							</td>
						</tr>
					</table>
					</td>
				</tr>
			</table>
			<br><br>
		</div>
		<?
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
