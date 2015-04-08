<?
 // ------------------------------------------------------------------------------
 // NiDB pipelines.php
 // Copyright (C) 2004 - 2015
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
	//PrintVariable($_GET, "GET");
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$id = GetVariable("id");
	$pagenum = GetVariable("pagenum");
	$numperpage = GetVariable("numperpage");
	
	$viewname = GetVariable("viewname");
	$viewlevel = GetVariable("viewlevel");
	$viewowner = GetVariable("viewowner");
	$viewstatus = GetVariable("viewstatus");
	$viewenabled = GetVariable("viewenabled");
	$viewall = GetVariable("viewall");
	
	$analysisid = GetVariable("analysisid");
	$analysisids = GetVariable("analysisids");
	$destination = GetVariable("destination");
	$pipelinetitle = GetVariable("pipelinetitle");
	$pipelinedesc = GetVariable("pipelinedesc");
	$pipelinegroup = GetVariable("pipelinegroup");
	$pipelinenumproc = GetVariable("pipelinenumproc");
	$pipelinesubmithost = GetVariable("pipelinesubmithost");
	$pipelinequeue = GetVariable("pipelinequeue");
	$pipelineremovedata = GetVariable("pipelineremovedata");
	$pipelineresultsscript = GetVariable("pipelineresultsscript");
	$pipelinedirectory = GetVariable("pipelinedirectory");
	$pipelinenotes = GetVariable("pipelinenotes");
	$version = GetVariable("version");
	$dataand = GetVariable("dataand");
	$completefiles = GetVariable("completefiles");
	$dependency = GetVariable("dependency");
	$deplevel = GetVariable("deplevel");
	$depdir = GetVariable("depdir");
	$groupid = GetVariable("groupid");
	$dynamicgroupid = GetVariable("dynamicgroupid");
	$level = GetVariable("level");
	$ishidden = GetVariable("pipelineishidden");

	$newname = GetVariable("newname");
	$newuserid = GetVariable("newuserid");
	
	$commandlist = GetVariable("commandlist");
	
	$primarydataorder = GetVariable("primarydataorder");
	$primaryprotocol = GetVariable("primaryprotocol");
	$primarymodality = GetVariable("primarymodality");
	$primarydataformat = GetVariable("primarydataformat");
	$primaryimagetype = GetVariable("primaryimagetype");
	$primarygzip = GetVariable("primarygzip");
	$primarylocation = GetVariable("primarylocation");
	$primaryseriescriteria = GetVariable("primaryseriescriteria");
	$primaryuseseriesdirs = GetVariable("primaryuseseriesdirs");
	$primarypreserveseries = GetVariable("primarypreserveseries");
	$primarybehformat = GetVariable("primarybehformat");
	$primarybehdir = GetVariable("primarybehdir");
	$primarydataenabled = GetVariable("primarydataenabled");

	$assocdataorder = GetVariable("assocdataorder");
	$assocprotocol = GetVariable("assocprotocol");
	$assocmodality = GetVariable("assocmodality");
	$assoctype = GetVariable("assoctype");
	$assocdataformat = GetVariable("assocdataformat");
	$associmagetype = GetVariable("associmagetype");
	$assocgzip = GetVariable("assocgzip");
	$assoclocation = GetVariable("assoclocation");
	$assocseriescriteria = GetVariable("assocseriescriteria");
	$assocuseseriesdirs = GetVariable("assocuseseriesdirs");
	$assocpreserveseries = GetVariable("assocpreserveseries");
	$assocbehformat = GetVariable("assocbehformat");
	$assocbehdir = GetVariable("assocbehdir");
	$assocdataenabled = GetVariable("assocdataenabled");

	$analysisnotes = GetVariable("analysisnotes");
	$fileviewtype = GetVariable("fileviewtype");
	
	/* determine action */
	switch ($action) {
		case 'viewjob': DisplayJob($id); break;
		case 'editpipeline': DisplayPipelineForm("edit", $id); break;
		case 'viewpipeline': DisplayPipeline($id, $version); break;
		case 'addform': DisplayPipelineForm("add", ""); break;
		case 'updatepipelinedef':
			UpdatePipelineDef($id, $commandlist, $steporder, $primarydataorder, $primaryprotocol, $primarymodality, $primarydataformat, $primaryimagetype, $primarygzip, $primarylocation, $primaryseriescriteria, $primaryuseseriesdirs, $primarypreserveseries, $primarybehformat, $primarybehdir, $primarydataenabled, $assocdataorder, $assocprotocol, $assoctype, $assocmodality, $assocdataformat, $associmagetype, $assocgzip, $assoclocation, $assocseriescriteria, $assocuseseriesdirs, $assocpreserveseries, $assocbehformat, $assocbehdir, $assocdataenabled);
			DisplayPipelineForm("edit", $id);
			break;
		case 'update':
			//echo "<pre><b>Passed this data to function</b> ($id, $pipelinetitle, $pipelinedesc, $pipelinegroup, $pipelinenumproc, $pipelinequeue, $pipelineremovedata, $pipelineresultsscript, $pipelinedirectory, $pipelinenotes, $username, $dataand, $completefiles, $dependency, $deplevel, $depdir, $groupid, $dynamicgroupid, $level, $ishidden)</pre>";
			UpdatePipeline($id, $pipelinetitle, $pipelinedesc, $pipelinegroup, $pipelinenumproc, $pipelinesubmithost, $pipelinequeue, $pipelineremovedata, $pipelineresultsscript, $pipelinedirectory, $pipelinenotes, $username, $dataand, $completefiles, $dependency, $deplevel, $depdir, $groupid, $dynamicgroupid, $level, $ishidden);
			
			DisplayPipelineTree($viewname, $viewlevel, $viewowner, $viewstatus, $viewenabled, $viewall);
			break;
		case 'add':
			AddPipeline($pipelinetitle, $pipelinedesc, $pipelinegroup, $pipelinenumproc, $pipelinesubmithost, $pipelinequeue, $pipelineremovedata, $pipelinedirectory, $pipelinenotes, $username, $dataand, $completefiles, $dependency, $deplevel, $depdir, $groupid, $dynamicgroupid, $level, $ishidden);
			DisplayPipelineTree($viewname, $viewlevel, $viewowner, $viewstatus, $viewenabled);
			break;
		case 'viewanalyses': DisplayAnalysisList($id, $numperpage, $pagenum); break;
		case 'viewfailedanalyses': DisplayFailedAnalysisList($id, $numperpage, $pagenum); break;
		case 'deleteanalyses':
			DeleteAnalyses($id, $analysisids);
			DisplayAnalysisList($id, $numperpage, $pagenum);
			break;
		case 'copyanalyses':
			CopyAnalyses($id, $analysisids, $destination);
			DisplayAnalysisList($id, $numperpage, $pagenum);
			break;
		case 'createlinks':
			CreateLinks($id, $analysisids, $destination);
			DisplayAnalysisList($id, $numperpage, $pagenum);
			break;
		case 'rerunresults':
			RerunResults($id, $analysisids);
			DisplayAnalysisList($id, $numperpage, $pagenum);
			break;
		case 'markbad':
			MarkAnalysis($id, $analysisids, 'bad');
			DisplayAnalysisList($id, $numperpage, $pagenum);
			break;
		case 'markgood':
			MarkAnalysis($id, $analysisids, 'good');
			DisplayAnalysisList($id, $numperpage, $pagenum);
			break;
		case 'viewlogs': DisplayLogs($id, $analysisid); break;
		case 'viewfiles': DisplayFiles($id, $analysisid, $fileviewtype); break;
		case 'changeowner':
			ChangeOwner($id,$newuserid);
			DisplayPipelineTree($viewname, $viewlevel, $viewowner, $viewstatus, $viewenabled, $viewall);
			break;
		case 'delete':
			DeletePipeline($id);
			DisplayPipelineTree($viewname, $viewlevel, $viewowner, $viewstatus, $viewenabled, $viewall);
			break;
		case 'copy':
			CopyPipeline($id, $newname);
			DisplayPipelineTree($viewname, $viewlevel, $viewowner, $viewstatus, $viewenabled, $viewall);
			break;
		case 'reset':
			ResetPipeline($id);
			DisplayPipelineTree($viewname, $viewlevel, $viewowner, $viewstatus, $viewenabled, $viewall);
			break;
		case 'resetanalyses':
			ResetAnalyses($id);
			DisplayPipelineTree($viewname, $viewlevel, $viewowner, $viewstatus, $viewenabled, $viewall);
			break;
		case 'disable':
			DisablePipeline($id);
			DisplayPipelineTree($viewname, $viewlevel, $viewowner, $viewstatus, $viewenabled, $viewall);
			break;
		case 'enable':
			EnablePipeline($id);
			DisplayPipelineTree($viewname, $viewlevel, $viewowner, $viewstatus, $viewenabled, $viewall);
			break;
		case 'testingoff':
			DisablePipelineTesting($id);
			DisplayPipelineTree($viewname, $viewlevel, $viewowner, $viewstatus, $viewenabled, $viewall);
			break;
		case 'testingon':
			EnablePipelineTesting($id);
			DisplayPipelineTree($viewname, $viewlevel, $viewowner, $viewstatus, $viewenabled, $viewall);
			break;
		case 'viewpipelinelist':
			DisplayPipelineTree($viewname, $viewlevel, $viewowner, $viewstatus, $viewenabled, $viewall);
			break;
		case 'setanalysisnotes':
			SetAnalysisNotes($analysisid, $analysisnotes);
			DisplayAnalysisList($id, $numperpage, $pagenum);
			break;
		default: DisplayPipelineTree($viewname, $viewlevel, $viewowner, $viewstatus, $viewenabled, $viewall);
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- UpdatePipeline --------------------- */
	/* -------------------------------------------- */
	function UpdatePipeline($id, $pipelinetitle, $pipelinedesc, $pipelinegroup, $pipelinenumproc, $pipelinesubmithost, $pipelinequeue, $pipelineremovedata, $pipelineresultsscript, $pipelinedirectory, $pipelinenotes, $username, $dataand, $completefiles, $dependency, $deplevel, $depdir, $groupid, $dynamicgroupid, $level, $ishidden) {
	
		//echo "<pre><b>Function received this data</b> ($id, $pipelinetitle, $pipelinedesc, $pipelinegroup, $pipelinenumproc, $pipelinequeue, $pipelineremovedata, $pipelineresultsscript, $pipelinedirectory, $pipelinenotes, $username, $dataand, $completefiles, $dependency, $deplevel, $depdir, $groupid, $dynamicgroupid, $level, $ishidden)</pre>";
		
		/* perform data checks */
		$pipelinetitle = mysql_real_escape_string($pipelinetitle);
		$pipelinedesc = mysql_real_escape_string($pipelinedesc);
		$pipelinegroup = mysql_real_escape_string($pipelinegroup);
		$pipelinenumproc = mysql_real_escape_string($pipelinenumproc);
		$pipelinesubmithost = mysql_real_escape_string($pipelinesubmithost);
		$pipelinequeue = mysql_real_escape_string($pipelinequeue);
		$pipelineremovedata = mysql_real_escape_string($pipelineremovedata);
		$pipelinedirectory = mysql_real_escape_string($pipelinedirectory);
		$pipelinenotes = mysql_real_escape_string($pipelinenotes);
		$pipelineresultsscript = mysql_real_escape_string($pipelineresultsscript);
		$completefiles = mysql_real_escape_string($completefiles);
		$deplevel = mysql_real_escape_string($deplevel);
		$depdir = mysql_real_escape_string($depdir);
		$ishidden = mysql_real_escape_string($ishidden);
		
		if (is_array($dependency)) {
			$dependencies = implode(",",$dependency);
		}
		else {
			$dependencies = $dependency;
		}
		
		if (is_array($groupid)) {
			$groupids = implode(",",$groupid);
		}
		else {
			$groupids = $groupid;
		}

		/* update the pipeline */
		$sqlstring = "update pipelines set pipeline_name = '$pipelinetitle', pipeline_desc = '$pipelinedesc', pipeline_group = '$pipelinegroup', pipeline_numproc = $pipelinenumproc, pipeline_submithost = '$pipelinesubmithost', pipeline_queue = '$pipelinequeue', pipeline_removedata = '$pipelineremovedata', pipeline_resultsscript = '$pipelineresultsscript', pipeline_dataand = $dataand, pipeline_completefiles = '$completefiles', pipeline_dependency = '$dependencies', pipeline_groupid = '$groupids', pipeline_dynamicgroupid = '$dynamicgroupid', pipeline_directory = '$pipelinedirectory', pipeline_notes = '$pipelinenotes', pipeline_level = $level, pipeline_dependencylevel = '$deplevel', pipeline_dependencydir = '$depdir', pipeline_ishidden = '$ishidden' where pipeline_id = $id";
		//PrintSQL($sqlstring);
		//return;
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* delete any existing dependencies, and insert the current dependencies */
		$sqlstring = "delete from pipeline_dependencies where pipeline_id = $id";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);

		if ($dependency != '') {
			if (is_array($dependency)) {
				foreach ($dependency as $dep) {
					$sqlstring = "insert into pipeline_dependencies (pipeline_id, parent_id) values ($id,'$dep')";
					//PrintSQL($sqlstring);
					$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
				}
			}
			else {
				$sqlstring = "insert into pipeline_dependencies (pipeline_id, parent_id) values ($id,'$dependency')";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			}
		}
		
		?><div align="center" class="message"><span class="message">Pipeline info [<?=$pipelinetitle?>] updated</span></div><?
	}


	/* -------------------------------------------- */
	/* ------- AddPipeline ------------------------ */
	/* -------------------------------------------- */
	function AddPipeline($pipelinetitle, $pipelinedesc, $pipelinegroup, $pipelinenumproc, $pipelinesubmithost, $pipelinequeue, $pipelineremovedata, $pipelinedirectory, $pipelinenotes, $username, $dataand, $completefiles, $dependency, $deplevel, $depdir, $groupid, $dynamicgroupid, $level, $ishidden) {
		/* perform data checks */
		$pipelinetitle = mysql_real_escape_string($pipelinetitle);
		$pipelinedesc = mysql_real_escape_string($pipelinedesc);
		$pipelinegroup = mysql_real_escape_string($pipelinegroup);
		$pipelinenumproc = mysql_real_escape_string($pipelinenumproc);
		$pipelinesubmithost = mysql_real_escape_string($pipelinesubmithost);
		$pipelinequeue = mysql_real_escape_string($pipelinequeue);
		$pipelineremovedata = mysql_real_escape_string($pipelineremovedata);
		$pipelineresultsscript = mysql_real_escape_string($pipelineresultsscript);
		$pipelinedirectory = mysql_real_escape_string($pipelinedirectory);
		$pipelinenotes = mysql_real_escape_string($pipelinenotes);
		$completefiles = mysql_real_escape_string($completefiles);
		$deplevel = mysql_real_escape_string($deplevel);
		$depdir = mysql_real_escape_string($depdir);
		$ishidden = mysql_real_escape_string($ishidden);
		if (is_array($dependency)) {
			$dependencies = implode(",",$dependency);
		}
		if (is_array($groupid)) {
			$groupids = implode2(",",$groupid);
		}
		if (is_array($dynamicgroupids)) {
			$dynamicgroupids = implode2(",",$dynamicgroupid);
		}
		
		/* get userid */
		$sqlstring = "select user_id from users where username = '$username'";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$userid = $row['user_id'];
		
		/* insert the new form */
		$sqlstring = "insert into pipelines (pipeline_name, pipeline_desc, pipeline_group, pipeline_admin, pipeline_createdate, pipeline_status, pipeline_numproc, pipeline_submithost, pipeline_queue, pipeline_removedata, pipeline_resultsscript, pipeline_dataand, pipeline_completefiles, pipeline_dependency, pipeline_dependencylevel, pipeline_dependencydir, pipeline_groupid, pipeline_dynamicgroupid, pipeline_level, pipeline_directory, pipeline_notes, pipeline_ishidden) values ('$pipelinetitle', '$pipelinedesc', '$pipelinegroup', '$userid', now(), 'stopped', '$pipelinenumproc', '$pipelinesubmithost', '$pipelinequeue', '$pipelineremovedata', '$pipelineresultsscript', '$dataand', '$completefiles', '$dependencies', '$deplevel', '$depdir', '$groupids', '$dynamicgroupids', '$level', '$pipelinedirectory', '$pipelinenotes', '$ishidden')";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		?><div align="center"><span class="message"><?=$formtitle?> added</span></div><?
	}

	
	/* -------------------------------------------- */
	/* ------- CopyPipeline ----------------------- */
	/* -------------------------------------------- */
	function CopyPipeline($id, $newname) {
	
		?>
		<span class="tiny">
		<ol>
		<?
		
		/* this process of copying a row is cumbersome...
		   ...but there is no need to change the column definitions below to reflect future table changes */
		
		$sqlstring = "start transaction";
		//PrintSQL("$sqlstring");
		echo "<li><b>Starting transaction</b>";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);

		/* ------ copy the pipeline definition ------ */
		/* create a temp table, which automatically creates the columns */
		$sqlstring = "create temporary table tmp_pipeline$id select * from pipelines where pipeline_id = $id";
		//PrintSQL("$sqlstring");
		echo "<li>Creating temp table from existing pipeline table spec [$sqlstring]";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* get the new pipeline id */
		$sqlstring = "select (max(pipeline_id)+1) 'newid', max(pipeline_version) 'version' from pipelines";
		//PrintSQL("$sqlstring");
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$newid = $row['newid'];
		echo "<li>Getting new pipeline ID [$newid] [$sqlstring]";

		$sqlstring = "select pipeline_version from pipelines where pipeline_id = $id";
		//PrintSQL("$sqlstring");
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$version = $row['pipeline_version'];
		echo "<li>Getting pipeline version [$version] [$sqlstring]";

		/* make any changes to the new pipeline before inserting */
		$sqlstring = "update tmp_pipeline$id set pipeline_id = $newid, pipeline_name = '$newname', pipeline_version = 1, pipeline_createdate = now(), pipeline_status = 'stopped', pipeline_statusmessage = '', pipeline_laststart = '', pipeline_lastfinish = '', pipeline_enabled = 0, pipeline_admin = (select user_id from users where username = '" . $_SESSION['username'] . "')";
		echo "<li>Making changes to new pipeline in temp table [$sqlstring]";
		//PrintSQL("$sqlstring");
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* insert the changed row into the pipeline table */
		$sqlstring = "insert into pipelines select * from tmp_pipeline$id";
		//PrintSQL("$sqlstring");
		echo "<li>Getting new pipeline ID [$sqlstring]";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* delete the tmp table */
		$sqlstring = "drop table tmp_pipeline$id";
		//PrintSQL("$sqlstring");
		echo "<li>Deleting temp table [$sqlstring]";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* ------ copy the data specification ------ */
		/* create a temp table, which automatically creates the columns */
		$sqlstring = "create temporary table tmp_dataspec$id (select * from pipeline_data_def where pipeline_id = $id and pipeline_version = $version)";
		//PrintSQL("$sqlstring");
		echo "<li>Create temp table from existing pipeline_data_def spec [$sqlstring]";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* make any changes to the new pipeline before inserting */
		$sqlstring = "update tmp_dataspec$id set pipeline_id = $newid, pipeline_version = 1, pipelinedatadef_id = ''";
		//PrintSQL("$sqlstring");
		echo "<li>Make changes to temp table [$sqlstring]";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* insert the changed rows into the pipeline_data_def table */
		$sqlstring = "insert into pipeline_data_def select * from tmp_dataspec$id";
		//PrintSQL("$sqlstring");
		echo "<li>Insert temp table rows into pipeline_data_def [$sqlstring]";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* delete the tmp table */
		$sqlstring = "drop table tmp_dataspec$id";
		//PrintSQL("$sqlstring");
		echo "<li>Drop temp table [$sqlstring]";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* ------ copy the pipeline steps specification ------ */
		/* create a temp table, which automatically creates the columns */
		$sqlstring = "create temporary table tmp_steps$id (select * from pipeline_steps where pipeline_id = $id and pipeline_version = $version)";
		//PrintSQL("$sqlstring");
		echo "<li>Create temp table from pipeline_steps spec [$sqlstring]";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* make any changes to the new pipeline before inserting */
		$sqlstring = "update tmp_steps$id set pipeline_id = $newid, pipeline_version = 1, pipelinestep_id = ''";
		//PrintSQL("$sqlstring");
		echo "<li>Make changes to temp table [$sqlstring]";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* insert the changed rows into the pipeline_data_def table */
		$sqlstring = "insert into pipeline_steps select * from tmp_steps$id";
		//PrintSQL("$sqlstring");
		echo "<li>Insert temp rows into pipeline_steps table [$sqlstring]";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* delete the tmp table */
		$sqlstring = "drop table tmp_steps$id";
		//PrintSQL("$sqlstring");
		echo "<li>Drop temp table [$sqlstring]";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* ------ all done ------ */
		$sqlstring = "commit";
		//PrintSQL("$sqlstring");
		echo "<li><b>Commit the transaction</b>";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		?>
		</ol>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- UpdatePipelineDef ------------------ */
	/* -------------------------------------------- */
	function UpdatePipelineDef($id, $commandlist, $steporder, $primarydataorder, $primaryprotocol, $primarymodality, $primarydataformat, $primaryimagetype, $primarygzip, $primarylocation, $primaryseriescriteria, $primaryuseseriesdirs, $primarypreserveseries, $primarybehformat, $primarybehdir, $primarydataenabled, $assocdataorder, $assocprotocol, $assoctype, $assocmodality, $assocdataformat, $associmagetype, $assocgzip, $assoclocation, $assocseriescriteria, $assocuseseriesdirs, $assocpreserveseries, $assocbehformat, $assocbehdir, $assocdataenabled) {
		
		/* determine the current and next pipeline version # */
		$sqlstring = "select pipeline_version from pipelines where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$oldversion = $row['pipeline_version'];
		$newversion = $oldversion + 1;

		//echo "<pre>";
		/* split up the commandlist into commands, then split them into enabled, command, description, logged, etc */
		$commands = explode("\n",$commandlist);
		$step = 1;
		foreach ($commands as $line) {
			//echo "[$step] $line ";
			//$line = trim($line);
			if ($line == "") {
				continue;
			}
			
			/* check if the command should be logged */
			if (stristr($line, '{NOLOG}') === false) {
				$logged[$step] = 1;
			}
			else {
				$logged[$step] = 0;
				$line = str_replace('{NOLOG}','',$line);
			}
			
			/* check if the command should be enabled */
			if (substr($line,0,1) == '#') {
				$stepenabled[$step] = 0;
				$subline = ltrim($line, '#');
				$description[$step] = substr(stristr($subline, '#'),1);
				$command[$step] = stristr($subline, '#', true);
				if ($command[$step] == "") {
					$command[$step] = $subline;
				}
			}
			else {
				$stepenabled[$step] = 1;
				$description[$step] = substr(stristr($line, '#'),1);
				$command[$step] = stristr($line, '#', true);
				if ($command[$step] == "") {
					$command[$step] = $line;
				}
			}

			$workingdir[$step] = "";
			$steporder[$step] = $step;
			$step++;
		}
		
		/* insert all the new fields with NEW version # */
		for($i=1; $i<=count($steporder); $i++) {
			if (trim($command[$i]) != "") {
				/* perform data checks */
				$steporder[$i] = trim(mysql_real_escape_string($steporder[$i]));
				$command[$i] = rtrim(mysql_real_escape_string($command[$i]));
				$workingdir[$i] = trim(mysql_real_escape_string($workingdir[$i]));
				$description[$i] = trim(mysql_real_escape_string($description[$i]));
				$stepenabled[$i] = trim(mysql_real_escape_string($stepenabled[$i]));
				$logged[$i] = trim(mysql_real_escape_string($logged[$i]));
				$sqlstring = "insert into pipeline_steps (pipeline_id, pipeline_version, ps_command, ps_workingdir, ps_order, ps_description, ps_enabled, ps_logged) values ($id, $newversion, '$command[$i]', '$workingdir[$i]', '$steporder[$i]', '$description[$i]', '$stepenabled[$i]', '$logged[$i]')";
				//printSQL($sqlstring);
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			}
		}
		
		?><div align="center"><span class="message">Pipeline steps [<?=$id?>] updated</span></div><?
		
		/* insert all the new PRIMARY data fields with NEW version # */
		for($i=0; $i<=count($primaryprotocol); $i++) {
			if (trim($primaryprotocol[$i]) != "") {
				/* perform data checks */
				$primarydataorder[$i] = mysql_real_escape_string($primarydataorder[$i]);
				$primaryprotocol[$i] = mysql_real_escape_string($primaryprotocol[$i]);
				$primarymodality[$i] = mysql_real_escape_string($primarymodality[$i]);
				$primarydataformat[$i] = mysql_real_escape_string($primarydataformat[$i]);
				$primaryimagetype[$i] = mysql_real_escape_string($primaryimagetype[$i]);
				$primarygzip[$i] = mysql_real_escape_string($primarygzip[$i]);
				$primarylocation[$i] = mysql_real_escape_string($primarylocation[$i]);
				$primaryseriescriteria[$i] = mysql_real_escape_string($primaryseriescriteria[$i]);
				$primaryuseseriesdirs[$i] = mysql_real_escape_string($primaryuseseriesdirs[$i]);
				$primarypreserveseries[$i] = mysql_real_escape_string($primarypreserveseries[$i]);
				$primarybehformat[$i] = mysql_real_escape_string($primarybehformat[$i]);
				$primarybehdir[$i] = mysql_real_escape_string($primarybehdir[$i]);
				$primarydataenabled[$i] = mysql_real_escape_string($primarydataenabled[$i]);

				$sqlstring = "insert into pipeline_data_def (pipeline_id, pipeline_version, pdd_order, pdd_seriescriteria, pdd_type, pdd_protocol, pdd_modality, pdd_dataformat, pdd_imagetype, pdd_gzip, pdd_location, pdd_useseries, pdd_preserveseries, pdd_behformat, pdd_behdir, pdd_enabled) values ($id, $newversion, '$primarydataorder[$i]', '$primaryseriescriteria[$i]', 'primary', '$primaryprotocol[$i]', '$primarymodality[$i]', '$primarydataformat[$i]', '$primaryimagetype[$i]', '$primarygzip[$i]', '$primarylocation[$i]', '$primaryuseseriesdirs[$i]', '$primarypreserveseries[$i]', '$primarybehformat[$i]', '$primarybehdir[$i]', '$primarydataenabled[$i]')";
				//printSQL($sqlstring);
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			}
		}

		/* insert all the new ASSOC data fields with NEW version # */
		for($i=0; $i<=count($assocprotocol); $i++) {
			if (trim($assocprotocol[$i]) != "") {
				/* perform data checks */
				$assocdataorder[$i] = mysql_real_escape_string($assocdataorder[$i]);
				$assocprotocol[$i] = mysql_real_escape_string($assocprotocol[$i]);
				$assocmodality[$i] = mysql_real_escape_string($assocmodality[$i]);
				$assoctype[$i] = mysql_real_escape_string($assoctype[$i]);
				$assocdataformat[$i] = mysql_real_escape_string($assocdataformat[$i]);
				$associmagetype[$i] = mysql_real_escape_string($associmagetype[$i]);
				$assocgzip[$i] = mysql_real_escape_string($assocgzip[$i]);
				$assoclocation[$i] = mysql_real_escape_string($assoclocation[$i]);
				$assocseriescriteria[$i] = mysql_real_escape_string($assocseriescriteria[$i]);
				$assocuseseriesdirs[$i] = mysql_real_escape_string($assocuseseriesdirs[$i]);
				$assocpreserveseries[$i] = mysql_real_escape_string($assocpreserveseries[$i]);
				$assocbehformat[$i] = mysql_real_escape_string($assocbehformat[$i]);
				$assocbehdir[$i] = mysql_real_escape_string($assocbehdir[$i]);
				$assocdataenabled[$i] = mysql_real_escape_string($assocdataenabled[$i]);

				$sqlstring = "insert into pipeline_data_def (pipeline_id, pipeline_version, pdd_order, pdd_seriescriteria, pdd_type, pdd_assoctype, pdd_protocol, pdd_modality, pdd_dataformat, pdd_imagetype, pdd_gzip, pdd_location, pdd_useseries, pdd_preserveseries, pdd_behformat, pdd_behdir, pdd_enabled) values ($id, $newversion, '$assocdataorder[$i]', '$assocseriescriteria[$i]', 'associated', '$assoctype[$i]', '$assocprotocol[$i]', '$assocmodality[$i]', '$assocdataformat[$i]', '$associmagetype[$i]', '$assocgzip[$i]', '$assoclocation[$i]', '$assocuseseriesdirs[$i]', '$assocpreserveseries[$i]', '$assocbehformat[$i]', '$assocbehdir[$i]', '$assocdataenabled[$i]')";
				//printSQL($sqlstring);
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			}
		}
		
		/* update pipeline with new version */
		$sqlstring = "update pipelines set pipeline_version = $newversion where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		?><div align="center"><span class="message">Data specification [<?=$id?>] updated</span></div><?
	}
	
	
	/* -------------------------------------------- */
	/* ------- ChangeOwner ------------------------ */
	/* -------------------------------------------- */
	function ChangeOwner($id, $newuserid) {
		/* update owner id */
		$sqlstring = "update pipelines set pipeline_admin = $newuserid where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		?><div align="center"><span class="message">Owner of pipeline [<?=$id?>] updated</span></div><?
	}


	/* -------------------------------------------- */
	/* ------- SetAnalysisNotes ------------------- */
	/* -------------------------------------------- */
	function SetAnalysisNotes($id, $notes) {
		$notes = mysql_real_escape_string($notes);
		$sqlstring = "update analysis set analysis_notes = '$notes' where analysis_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		//PrintSQL($sqlstring);
		
		?><div align="center"><span class="message">Analysis [<?=$id?>] notes updated</span></div><?
	}
	

	/* -------------------------------------------- */
	/* ------- DeletePipeline --------------------- */
	/* -------------------------------------------- */
	function DeletePipeline($id) {
		/* disable this pipeline */
		DisablePipeline($id);
		
		/* insert a row in the fileio_requests table */
		$sqlstring = "insert into fileio_requests (fileio_operation,data_type,data_id,username,requestdate) values ('delete','pipeline',$id,'" . $GLOBALS['username'] . "',now())";
		//$sqlstring = "delete from pipelines where pipeline_id = $id";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		$sqlstring = "update pipelines set pipeline_statusmessage = 'Queued for deletion' where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		?><div align="center"><span class="message"><?=$id?> queued for deletion</span></div><?
	}	

	
	/* -------------------------------------------- */
	/* ------- DeleteAnalyses --------------------- */
	/* -------------------------------------------- */
	function DeleteAnalyses($id, $analysisids) {
	
		/* disable this pipeline */
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
	function CopyAnalyses($id, $analysisids, $destination) {
	
		$destination = mysql_real_escape_string($destination);
		
		foreach ($analysisids as $analysisid) {
		
			$sqlstring = "insert into fileio_requests (fileio_operation, data_type, data_id, data_destination, username, requestdate) values ('copy', 'analysis', $analysisid, '$destination', '" . $GLOBALS['username'] . "', now())";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			
			$sqlstring = "select d.uid, b.study_num, e.pipeline_name from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id left join pipelines e on a.pipeline_id = e.pipeline_id where a.analysis_id = $analysisid";
			//echo "[$sqlstring]";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$uid = $row['uid'];
			$studynum = $row['study_num'];
			$pipelinename = $row['pipeline_name'];
			
			$datapath = $GLOBALS['cfg']['analysisdir'] . "/$uid/$studynum/$pipelinename";
			?><span class="codelisting"><?=$datapath?> queued for copy to <?=$destination?></span><br><?
			
		}
	}

	
	/* -------------------------------------------- */
	/* ------- CreateLinks ------------------------ */
	/* -------------------------------------------- */
	function CreateLinks($id, $analysisids, $destination) {
	
		$destination = mysql_real_escape_string($destination);
		
		foreach ($analysisids as $analysisid) {
		
			$sqlstring = "insert into fileio_requests (fileio_operation, data_type, data_id, data_destination, username, requestdate) values ('createlinks', 'analysis', $analysisid, '$destination', '" . $GLOBALS['username'] . "', now())";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			
			$sqlstring = "select d.uid, b.study_num, e.pipeline_name from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id left join pipelines e on a.pipeline_id = e.pipeline_id where a.analysis_id = $analysisid";
			//echo "[$sqlstring]";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$uid = $row['uid'];
			$studynum = $row['study_num'];
			$pipelinename = $row['pipeline_name'];
			
			$datapath = $GLOBALS['cfg']['analysisdir'] . "/$uid/$studynum/$pipelinename";
			?><span class="codelisting"><?=$datapath?> queued for link creation in <?=$destination?></span><br><?
			
		}
	}


	/* -------------------------------------------- */
	/* ------- RerunResults ----------------------- */
	/* -------------------------------------------- */
	function RerunResults($id, $analysisids) {
	
		/* disable this pipeline */
		//DisablePipeline($id);
		
		foreach ($analysisids as $analysisid) {
			
			$sqlstring = "update analysis set analysis_statusmessage = 'Results queued for rerun', analysis_rerunresults = 1 where analysis_id = $analysisid";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			
			$sqlstring = "select d.uid, b.study_num, e.pipeline_name, e.pipeline_level from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id left join pipelines e on a.pipeline_id = e.pipeline_id where a.analysis_id = $analysisid";
			//echo "[$sqlstring]";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$uid = $row['uid'];
			$studynum = $row['study_num'];
			$pipelinename = $row['pipeline_name'];
			$pipelinelevel = $row['pipeline_level'];

			if ($pipelinelevel == 1) {
				$datapath = $GLOBALS['cfg']['analysisdir'] . "/$uid/$studynum/$pipelinename";
			}
			elseif ($pipelinelevel == 2) {
				$datapath = $GLOBALS['cfg']['groupanalysisdir'] . "/$pipelinename";
			}
			?><span class="codelisting"><?=$datapath?> results queued to be rerun</span><br><?
		}
	}


	/* -------------------------------------------- */
	/* ------- MarkAnalysis ----------------------- */
	/* -------------------------------------------- */
	function MarkAnalysis($id, $analysisids, $status) {
		
		foreach ($analysisids as $analysisid) {
			
			if ($status == 'bad') {
				$sqlstring = "update analysis set analysis_isbad = 1 where analysis_id = $analysisid";
			}
			else {
				$sqlstring = "update analysis set analysis_isbad = 0 where analysis_id = $analysisid";
			}
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			
			$sqlstring = "select d.uid, b.study_num, e.pipeline_name, e.pipeline_level from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id left join pipelines e on a.pipeline_id = e.pipeline_id where a.analysis_id = $analysisid";
			//echo "[$sqlstring]";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$uid = $row['uid'];
			$studynum = $row['study_num'];
			$pipelinename = $row['pipeline_name'];
			$pipelinelevel = $row['pipeline_level'];

			if ($pipelinelevel == 1) {
				$datapath = $GLOBALS['cfg']['analysisdir'] . "/$uid/$studynum/$pipelinename";
			}
			elseif ($pipelinelevel == 2) {
				$datapath = $GLOBALS['cfg']['groupanalysisdir'] . "/$pipelinename";
			}
			?><span class="codelisting"><?=$datapath?> marked as bad</span><br><?
		}
	}
	
	
	/* -------------------------------------------- */
	/* ------- rrmdir ----------------------------- */
	/* -------------------------------------------- */
	/* recursive rmdir                              */
	function rrmdir($dir,$numdir,$numfile) {
		$allfiles = array_merge(glob($dir . '/*'), glob($dir . '/.*'), glob($dir . '/*.*'));
		foreach ($allfiles as $index => $file) {
			if ((strpos($file,'/.') !== false) || (strpos($file,'/..') !== false)) {
				unset($allfiles[$index]);
			}
		}

		foreach($allfiles as $file) {
			if(is_dir($file)) {
				if (($file != '.') && ($file != '..')) {
					list($numdir,$numfile) = rrmdir($file,$numdir,$numfile);
				}
			}
			else {
				if (file_exists($file)) {
					if (unlink("/mount$file")) {
						//echo "[$file] deleted<br>\n";
					}
					else {
						$undeleted[] = $file;
						echo "[$file] not deleted<br>\n";
					}
					$numfile++;
				}
			}
		}
		if (rmdir($dir)) {
			//echo "Dir [$dir] deleted<br>\n";
		}
		else {
			echo "Dir [$dir] not deleted<br>\n";
		}
		if (file_exists($dir)) {
			$undeleted[] = $dir;
		}
		
		return $undeleted;
	}	


	/* -------------------------------------------- */
	/* ------- ResetAnalyses ---------------------- */
	/* -------------------------------------------- */
	function ResetAnalyses($id) {
		$sqlstring = "delete from analysis_data where analysis_id in (select analysis_id from analysis where pipeline_id = $id and analysis_startdate is null)";
		$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
		?><div align="center"><span class="message">Reset analyses: <?echo mysql_affected_rows(); ?> analysis <b>data</b> rows deleted</span></div><?
	
		$sqlstring = "delete from analysis where analysis_startdate is null and pipeline_id = $id";
		$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
		?><div align="center"><span class="message">Reset analyses: <?echo mysql_affected_rows(); ?> analysis rows deleted</span></div><?
	}	


	/* -------------------------------------------- */
	/* ------- ResetPipeline ---------------------- */
	/* -------------------------------------------- */
	function ResetPipeline($id) {
		$sqlstring = "update pipelines set pipeline_status = 'stopped' where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
	}	
	
	
	/* -------------------------------------------- */
	/* ------- EnablePipeline --------------------- */
	/* -------------------------------------------- */
	function EnablePipeline($id) {
		$sqlstring = "update pipelines set pipeline_enabled = 1 where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
	}


	/* -------------------------------------------- */
	/* ------- DisablePipeline -------------------- */
	/* -------------------------------------------- */
	function DisablePipeline($id) {
		$sqlstring = "update pipelines set pipeline_enabled = 0 where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
	}

	
	/* -------------------------------------------- */
	/* ------- EnablePipelineTesting -------------- */
	/* -------------------------------------------- */
	function EnablePipelineTesting($id) {
		$sqlstring = "update pipelines set pipeline_testing = 1 where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
	}


	/* -------------------------------------------- */
	/* ------- DisablePipelineTesting ------------- */
	/* -------------------------------------------- */
	function DisablePipelineTesting($id) {
		$sqlstring = "update pipelines set pipeline_testing = 0 where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayPipelineForm ---------------- */
	/* -------------------------------------------- */
	function DisplayPipelineForm($type, $id) {
	
		$level = 0;
		/* populate the fields if this is an edit */
		if ($type == "edit") {
			$sqlstring = "select a.*, b.username from pipelines a left join users b on a.pipeline_admin = b.user_id where a.pipeline_id = $id";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$title = $row['pipeline_name'];
			$desc = $row['pipeline_desc'];
			$numproc = $row['pipeline_numproc'];
			$submithost = $row['pipeline_submithost'];
			$queue = $row['pipeline_queue'];
			$remove = $row['pipeline_removedata'];
			$version = $row['pipeline_version'];
			$directory = $row['pipeline_directory'];
			$pipelinenotes = $row['pipeline_notes'];
			$pipelinegroup = $row['pipeline_group'];
			$resultscript = $row['pipeline_resultsscript'];
			$dataand = $row['pipeline_dataand'];
			$deplevel = $row['pipeline_dependencylevel'];
			$depdir = $row['pipeline_dependencydir'];
			if ($dataand == "") { $dataand = 0; }
			$completefiles = $row['pipeline_completefiles'];
			$dependency = $row['pipeline_dependency'];
			$groupid = $row['pipeline_groupid'];
			$dynamicgroupid = $row['pipeline_dynamicgroupid'];
			$level = $row['pipeline_level'];
			$owner = $row['username'];
			$ishidden = $row['pipeline_ishidden'];
			$isenabled = $row['pipeline_enabled'];

			//echo "<pre>";
			//print_r($GLOBALS);
			//echo "</pre>";
			
			if (($owner == $GLOBALS['username']) || ($GLOBALS['issiteadmin'])) {
				$readonly = false;
			}
			else {
				$readonly = true;
			}
			
			$formaction = "update";
			$formtitle = "$title";
			$submitbuttonlabel = "Update Pipeline Info";
		}
		else {
			$formaction = "add";
			$formtitle = "Add new pipeline";
			$submitbuttonlabel = "Add Pipeline Info";
			$remove = "0";
			$level = 1;
			$directory = "/home/" . $GLOBALS['username'] . "/onrc/data";
			$readonly = false;
		}
		
		if ($readonly) {
			$disabled = "disabled";
		}
		else {
			$disabled = "";
		}
		
		if ($numproc == "") { $numproc = 1; }
		
		//$urllist['Analysis'] = "analysis.php";
		$urllist['Pipelines'] = "pipelines.php";
		$urllist[$title] = "pipelines.php?action=editpipeline&id=$id";
		NavigationBar("Analysis", $urllist);
	?>
	
		<script type="text/javascript">
			$(document).ready(function() {
				/* default action */
				<? if($level == 1) { ?>
				$('.level0').hide();
				$('.level1').show();
				$('.level2').hide();
				<? } elseif ($level == 0) { ?>
				$('.level0').show();
				$('.level1').hide();
				$('.level2').hide();
				<? } else { ?>
				$('.level0').hide();
				$('.level1').show();
				$('.level2').show();
				<? } ?>
				
				/* click events */
				$('#level0').click(function() {
					if($('#level0').is(':checked')) {
						$('.level0').show("highlight",{},1000);
						$('.level1').hide();
						$('.level2').hide();
					}
				});
				$('#level1').click(function() {
					if($('#level1').is(':checked')) {
						$('.level0').hide();
						$('.level1').show("highlight",{},1000);
						$('.level2').hide();
					}
				});
				$('#level2').click(function() {
					if($('#level2').is(':checked')) {
						$('.level0').hide();
						$('.level1').show();
						$('.level2').show("highlight",{},1000);
					}
				});
			});

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
	
		<fieldset style="border: 3px solid #999; border-radius:5px">
			<legend style="background-color: #3B5998; color:white; padding:5px 10px; border-radius:5px"> <b><?=$formtitle?></b> version <?=$version?> </legend>
		<table>
			<tr>
				<td style="padding-right:40px">
					<table class="entrytable" style="border:0px">
						<form method="post" action="pipelines.php">
						<input type="hidden" name="action" value="<?=$formaction?>">
						<input type="hidden" name="id" value="<?=$id?>">
						<tr>
							<td class="label" valign="top">Enabled<br><br></td>
							<td valign="top">
								<?
									if ($isenabled) {
										?><a href="pipelines.php?action=disable&id=<?=$id?>"><img src="images/checkedbox16.png" title="Pipeline enabled, click to disable"></a><?
									}
									else {
										?><a href="pipelines.php?action=enable&id=<?=$id?>"><img src="images/uncheckedbox16.png" title="Pipeline disabled, click to enable"></a><?
									}
								?>
								<br><br>
							</td>
						</tr>
						<tr>
							<td class="label" valign="top">Title</td>
							<td valign="top">
								<input type="text" name="pipelinetitle" value="<?=$title?>" maxlength="50" size="60" onKeyPress="return AlphaNumeric(event)" <? if ($type == "edit") { echo "readonly style='background-color: #EEE; border: 1px solid gray; color: #888'"; } ?>>
							</td>
						</tr>
						<tr>
							<td class="label" valign="top">Description</td>
							<td valign="top"><input type="text" <?=$disabled?> name="pipelinedesc" value="<?=$desc?>" size="60"></td>
						</tr>
						<tr>
							<td class="label" valign="top">Stats level</td>
							<td valign="top">
								<input type="radio" name="level" id="level0" value="0" <?=$disabled?> <? if ($level == 0) echo "checked"; ?>>One-shot <span class="tiny">Runs only once. No associated data</span><br>
								<input type="radio" name="level" id="level1" value="1" <?=$disabled?> <? if ($level == 1) echo "checked"; ?>>First <span class="tiny">subject level</span><br>
								<input type="radio" name="level" id="level2" value="2" <?=$disabled?> <? if ($level == 2) echo "checked"; ?>>Second <span class="tiny">group level</span><br>
							</td>
						</tr>
						<tr>
							<td class="label" valign="top">Group</td>
							<td valign="top">
								<input type="text" name="pipelinegroup" list="grouplist" <?=$disabled?> value="<?=$pipelinegroup?>" maxlength="255" size="60">
							</td>
							<datalist id="grouplist">
								<?
									$sqlstring = "select distinct(pipeline_group) 'pipeline_group' from pipelines";
									$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
									while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
										$pgroup = $row['pipeline_group'];
										echo "<option value='$pgroup'>";
									}
								?>
							</datalist>
						</tr>
						<tr>
							<td class="label" valign="top">Directory <img src="images/help.gif" title="<b>Directory</b><br><br>A directory called <b>Title</b> (same name as this analysis) will be created inside this directory and will contain the analyses for this pipeline.<br><br>If blank, the analyses for this pipeline will be written to the default pipeline directory: <span style='color: #E8FFFF'>[<?=$GLOBALS['cfg']['analysisdir']?>]</span>"></td>
							<td valign="top">
								<input type="text" name="pipelinedirectory" <?=$disabled?> value="<?=$directory?>" maxlength="255" size="60" <? if ($type == "edit") { echo "readonly style='background-color: #EEE; border: 1px solid gray; color: #888'"; } ?> >
							</td>
						</tr>
						<tr class="level1">
							<td class="label" valign="top">Concurrent processes <img src="images/help.gif" title="<b>Concurrent processes</b><br><br>This is the number of concurrent jobs allowed to be submitted to the cluster at a time. This number is separate from the number of slots available in the cluster queue, which specified in the grid engine setup"></td>
							<td valign="top"><input type="number" name="pipelinenumproc" <?=$disabled?> value="<?=$numproc?>" min="1" max="350"></td>
						</tr>
						<tr>
							<td class="label" valign="top">Submit host <img src="images/help.gif" title="<b>Submit host</b><br><br>The hostname of the SGE head node to submit to. If blank, the default submit host is used (<?=$GLOBALS['cfg']['clustersubmithost']?>)"></td>
							<td valign="top"><input type="text" name="pipelinesubmithost" <?=$disabled?> value="<?=$submithost?>"></td>
						</tr>
						<tr>
							<td class="label" valign="top">Queue name <img src="images/help.gif" title="<b>Queue name</b><br><br>The sun grid (SGE) queue to submit to"></td>
							<td valign="top"><input type="text" name="pipelinequeue" <?=$disabled?> value="<?=$queue?>" required></td>
						</tr>
						<tr class="level1">
							<td class="label" valign="top">Data download</td>
							<td valign="top">
								<input type="radio" name="dataand" value="0" <?=$disabled?> <? if ($dataand == 0) echo "checked"; ?>>or <span class="tiny">download any of the data specified below</span><br>
								<input type="radio" name="dataand" value="1" <?=$disabled?> <? if ($dataand == 1) echo "checked"; ?>>and <span class="tiny">only download data if all of the series specified exist in the study</span><br>
								<input type="radio" name="dataand" value="-1" <?=$disabled?> <? if ($dataand == -1) echo "checked"; ?>>none <span class="tiny">no data download. only use if the pipeline has a dependency</span>
							</td>
						</tr>
						<tr class="level1">
							<td class="label" valign="top">Remove downloaded data?</td>
							<td valign="top" title="<b>Remove downloaded data</b><br><br>Deletes all downloaded (raw) data after analysis is complete. Assumes that the analsysis will have copied or converted the necessary data and no longer needs it"><input type="checkbox" name="pipelineremovedata" value="1" <? if ($remove) { echo "checked"; } ?>></td>
						</tr>
						<tr>
							<td class="label" valign="top">Successful files <img src="images/help.gif" title="<b>Successful files</b><br><br>The analysis is marked as successful if ALL of the files specified exist at the end of the analysis. If left blank, the analysis will always be marked as successful"></td>
							<td valign="top"><textarea name="completefiles" <?=$disabled?> rows="5" cols="60"><?=$completefiles?></textarea><br>
							<span class="tiny">Comma seperated list of files (relative paths)</span></td>
						</tr>
						<tr>
							<td class="label" valign="top">Results script <img src="images/help.gif" title="<b>Results script</b><br><br>This script will be executed last and can be re-run separate from the analysis pipeline. The results script would often be used to create thumbnails of images and parse text files, and reinsert those results back into the database. The same pipeline variables available in the script command section below are available here to be passed as parameters to the results script"></td>
							<td valign="top">
								<textarea name="pipelineresultsscript" rows="3" cols="60"><?=$resultscript?></textarea>
							</td>
						</tr>
						<tr class="level1">
							<td class="label" valign="top">Pipeline dependency<br>
							<!--<span class="level2" style="color:darkred; font-size:8pt; font-weight:normal"> Second level must have<br> at least one dependency</span>-->
							</td>
							<td valign="top">
								<input type="radio" name="deplevel" value="study" <?=$disabled?> <? if (($deplevel == "study") || ($deplevel == "")) { echo "checked"; } ?>> study <span class="tiny">use dependencies from same study</span><br>
								<input type="radio" name="deplevel" value="subject" <?=$disabled?> <? if ($deplevel == "subject") { echo "checked"; } ?>> subject <span class="tiny">use dependencies from same subject (other studies)</span>
								<br>
								<select name="dependency[]" <?=$disabled?> multiple="multiple" size="7">
									<option value="">(No dependency)</option>
									<?
										$sqlstring = "select * from pipelines order by pipeline_name";
										$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
										while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
											$d_name = $row['pipeline_name'];
											$d_id = $row['pipeline_id'];
											$d_ver = $row['pipeline_version'];
											
											/* get the number of analyses in the pipeline */
											$sqlstringA = "select count(*) 'count' from analysis where pipeline_id = $d_id and analysis_status = 'complete'";
											$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
											$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
											$nummembers = $rowA['count'];
											
											if (in_array($d_id, explode(",",$dependency))) { $selected = "selected"; }
											//if ($d_id == $dependency) { $selected = "selected"; }
											else { $selected = ""; }
											if ($id != $d_id) {
											?>
											<option value="<?=$d_id?>" <?=$selected?>><?=$d_name?>  [<?=$nummembers?>]</option>
											<?
											}
										}
									?>
								</select>
								<br>
								<input type="radio" name="depdir" value="root" <?=$disabled?> <? if (($depdir == "root") || ($depdir == "")) { echo "checked"; } ?>> root directory <img src="images/help.gif" title="copies all files into the analysis root directory <code>{analysisrootdir}/*</code>"><br>
								<input type="radio" name="depdir" value="subdir" <?=$disabled?> <? if ($depdir == "subdir") { echo "checked"; } ?>> sub-directory <img src="images/help.gif" title="copies dependency into a subdirectory of the analysis <code>{analysisrootdir}/<i>DependencyName</i>/*</code>">
							</td>
						</tr>
						<tr class="level1">
							<td class="label" valign="top">Group(s) <img src="images/help.gif" title="Perform this analysis ONLY<br>on the studies in the specified groups"><br>
							<span class="level2" style="color:darkred; font-size:8pt; font-weight:normal"> Second level must have<br> at least one group.<br>Group(s) must be identical to<br>first level <b>dependency's</b> group(s)</span>
							</td>
							<td valign="top">
								<select name="groupid[]" <?=$disabled?> multiple="multiple" size="7">
									<option value="">(No group)</option>
									<?
										$sqlstring = "select * from groups where group_type = 'study'";
										$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
										while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
											$g_name = $row['group_name'];
											$g_id = $row['group_id'];
											
											/* get the number of members of the group */
											$sqlstringA = "select count(*) 'count' from group_data where group_id = $g_id";
											$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
											$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
											$nummembers = $rowA['count'];
											
											if (in_array($g_id, explode(",",$groupid))) { $selected = "selected"; }
											else { $selected = ""; }
											?>
											<option value="<?=$g_id?>" <?=$selected?>><?=$g_name?>  [<?=$nummembers?>]</option>
											<?
										}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<td class="label" valign="top">Notes<br><span class="tiny">Any information about the analysis</span></td>
							<td valign="top"><textarea name="pipelinenotes" <?=$disabled?> rows="8" cols="60"><?=$pipelinenotes?></textarea></td>
						</tr>
						<tr>
							<td class="label" valign="top">Hidden?</td>
							<td valign="top" title="<b>Hidden</b><br><br>Useful to hide a pipeline from the main pipeline list. The pipeline still exists, but it won't show up"><input type="checkbox" name="pipelineishidden" value="1" <? if ($ishidden) { echo "checked"; } ?>></td>
						</tr>
						<tr>
							<td colspan="2" align="center">
								<br>
								<input type="submit" <?=$disabled?> value="<?=$submitbuttonlabel?>">
							</td>
						</tr>
						</form>
					</table>
				</td>
				<? if ($formaction == "update") { ?>
				<td valign="top">
					<?
						/* gather statistics about the analyses */
						$sqlstring = "select sum(timestampdiff(second, analysis_clusterstartdate, analysis_clusterenddate)) 'cluster_time' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and analysis_status = 'complete'";
						$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
						$row = mysql_fetch_array($result, MYSQL_ASSOC);
						$totaltime = $row['cluster_time'];
						$totaltime = number_format(($totaltime/60/60),2);
						//$parts = explode(':', $totaltime);
						//$totaltime = $parts[0]. "h " . $parts[1] . "m " . $parts[2] . "s";
						
						$sqlstring = "select count(*) 'numcomplete' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and analysis_status = 'complete'";
						$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
						$row = mysql_fetch_array($result, MYSQL_ASSOC);
						$numcomplete = $row['numcomplete'];
						
						$sqlstring = "select count(*) 'numprocessing' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and analysis_status = 'processing'";
						$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
						$row = mysql_fetch_array($result, MYSQL_ASSOC);
						$numprocessing = $row['numprocessing'];
						
						$sqlstring = "select count(*) 'numpending' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and analysis_status = 'pending'";
						$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
						$row = mysql_fetch_array($result, MYSQL_ASSOC);
						$numpending = $row['numpending'];
						
						/* get mean processing times */
						$sqlstring = "select analysis_id, timestampdiff(second, analysis_startdate, analysis_enddate) 'analysis_time', timestampdiff(second, analysis_clusterstartdate, analysis_clusterenddate) 'cluster_time' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and analysis_status <> ''";
						//PrintSQL($sqlstring);
						$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							//$analysis_id = $row['analysis_id'];
							$analysistimes[] = $row['analysis_time'];
							$clustertimes[] = $row['cluster_time'];
						}
						if (count($clustertimes) == 0) {
							$clustertimes[] = 0;
						}
						if (count($analysistimes) == 0) {
							$analysistimes[] = 0;
						}
						
						?>
						<table class="twocoltable">
							<tr>
								<th colspan="2">Analysis Statistics</th>
							</tr>
							<tr>
								<td>Complete<br>Total analysis time</td>
								<td><a href="pipelines.php?action=viewanalyses&id=<?=$id?>"><?=$numcomplete?></a><br><?=$totaltime?> hr</td>
							</tr>
							<tr>
								<td>Processing</td>
								<td><?=$numprocessing?></td>
							</tr>
							<tr>
								<td>Pending</td>
								<td><?=$numpending?></td>
							</tr>
							<tr>
								<td>Mean Cluster time</td>
								<td><?=number_format(mean($clustertimes)/60/60,2)?> hr</td>
							</tr>
							<tr>
								<td>Median Cluster time</td>
								<td><?=number_format(median($clustertimes)/60/60,2)?> hr</td>
							</tr>
							<tr>
								<td>Min/Max Cluster time</td>
								<td><?=number_format(min($clustertimes)/60/60,2)?> - <?=number_format(max($clustertimes)/60/60,2)?> hr</td>
							</tr>
							<tr>
								<td>Mean Setup time</td>
								<td><?=number_format(mean($analysistimes),1)?> sec</td>
							</tr>
							<tr>
								<td>Median Setup time</td>
								<td><?=number_format(median($analysistimes),1)?> sec</td>
							<tr>
							</tr>
								<td>Min/Max Setup time</td>
								<td><?=number_format(min($analysistimes),1)?> - <?=number_format(max($analysistimes),1)?> sec</td>
							</tr>
						</table>
					<br>
					<script>
						function GetNewPipelineName(){
							var newname = prompt("Please enter a name for the new pipeline","<?=$title?>");
							if (newname != null){
							  $("#newname").attr("value", newname);
							  document.copypipeline.submit();
						   }
						}
					</script>

					<span style="color:#555; font-size:11pt; font-weight: bold">Where is my data?</span><br><br>
					<span style="background-color: #ddd; padding:5px; font-family: monospace; border-radius:3px">
					<? if ($directory != "") { echo $directory; } else { echo $GLOBALS['cfg']['analysisdir']; } ?>/<i>UID</i>/<i>StudyNum</i>/<?=$title?>
					</span>
					<br><br>
					<details>
						<summary style="color: #3B5998"> Pipeline Operations </summary>
						<br>
						<a href="pipelines.php?action=viewpipeline&id=<?=$id?>"><img src="images/printer16.png" border="0"> Print view</a> <span class="tiny">(and previous pipeline versions)</span><br><br>
						<a href="pipelines.php?action=viewanalyses&id=<?=$id?>"><img src="images/preview.gif"> View analyses</a><br><br>
						<a href="pipelines.php?action=viewfailedanalyses&id=<?=$id?>" title="View all imaging studies which did not meet the data criteria, and therefore the pipeline did not attempt to run the analysis"><img src="images/preview.gif"> View ignored studies</a><br><br>
						
						<form action="pipelines.php" method="post" name="copypipeline">
						<input type="hidden" name="action" value="copy">
						<input type="hidden" name="id" value="<?=$id?>">
						<input type="hidden" name="newname" id="newname" value="<?=$id?>">
						<img src="images/copy16.gif"> <input type="button" value="Copy to new pipeline..." onClick="GetNewPipelineName();"><br><br>
						</form>
						<? if (!$readonly) { ?>
						Change pipeline owner:
						<form>
						<input type="hidden" name="action" value="changeowner">
						<input type="hidden" name="id" value="<?=$id?>">
						<select name="newuserid">
							<?
								$sqlstring="select * from users where user_enabled = 1 order by username";
								$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
								while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
									$userid = $row['user_id'];
									$username = $row['username'];
									$userfullname = $row['user_fullname'];
									if ($userfullname != "") {
										$userfullname = "[$userfullname]";
									}
									?><option value="<?=$userid?>"><?=$username?> <?=$userfullname?></option><?
								}
							?>
						</select>
						<input type="submit" value="Change">
						</form>
						<a href="pipelines.php?action=detach$id=<?=$id?>" onclick="return confirm('Are you sure you want to completely detach this pipeline?')" title="This will completely inactivate the pipeline and remove all analyses from the pipeline control. Since the data will no longer be under pipeline control, all analysis results will be deleted. All analysis data will be moved to the directory you specify"><img src="images/disconnect16.png"> Detach entire pipeline</a><br><br>
						<a href="pipelines.php?action=resetanalyses&id=<?=$id?>" onclick="return confirm('Are you sure you want to reset the analyses for this pipeline?')" title="This will remove any entries in the database for studies which were not analyzed. If you change your data specification, you will want to reset the analyses. This option does not remove existing analyses, it only removes the flag set for studies that indicates the study has been checked for the specified data"><img src="images/reset16.png"> Reprocess ignored studies</a><br><br>
						<a href="pipelines.php?action=delete&id=<?=$id?>" onclick="return confirm('Are you sure you want to delete this pipeline?')"><img src="images/delete16.png"> Delete this pipeline</a>
						<? } ?>
					</details>
				</td>
					<?
				}
			?>
			</tr>
		</table>
		</fieldset>

		<?
		if ($type == "edit") {
		?>
		<br><br>
		
		<script>
			function addParam(value,id){
				var TheTextBox = document.getElementById(id);
				TheTextBox.value = TheTextBox.value + ' ' + value;
			}
		</script>

		
		<fieldset style="border: 3px solid #999; border-radius:5px">
			<legend style="background-color: #3B5998; color:white; padding:5px 10px; border-radius:5px"> Pipeline specification </legend>
			
		<form method="post" action="pipelines.php" name="stepsform" id="stepsform">
		<input type="hidden" name="action" value="updatepipelinedef">
		<input type="hidden" name="id" value="<?=$id?>">
		<? if (($level == 1) || (($level == 2) && ($dependency == ''))) { ?>
		<br>
		<table width="100%">
			<tr>
				<td valign="top" width="50%">
				<!-- ************* primary data spec *************** -->
					<div style="text-align:left; font-size:12pt; font-weight: bold; color:#214282;" class="level1">Primary Data</div>
					<table class="level1" cellspacing="0" cellpadding="0">
						<tr style="color:#444; font-size:10pt">
							<td width="60px"><b>Enabled</b></td>
							<td width="50px"><b>Order</b></td>
							<td width="245px"><b>Protocol</b></td>
							<td width="60px"><b>Modality</b></td>
						</tr>
					</table>
					<?
						$neworder = 1;
						/* display all other rows, sorted by order */
						$sqlstring = "select * from pipeline_data_def where pipeline_id = $id and pipeline_version = $version and pdd_type = 'primary' order by pdd_order + 0";
						$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$pipelinedatadef_id = $row['pipelinedatadef_id'];
							$pdd_order = $row['pdd_order'];
							$pdd_seriescriteria = $row['pdd_seriescriteria'];
							$pdd_protocol = $row['pdd_protocol'];
							$pdd_modality = $row['pdd_modality'];
							$pdd_dataformat = $row['pdd_dataformat'];
							$pdd_imagetype = $row['pdd_imagetype'];
							$pdd_gzip = $row['pdd_gzip'];
							$pdd_location = $row['pdd_location'];
							$pdd_useseries = $row['pdd_useseries'];
							$pdd_preserveseries = $row['pdd_preserveseries'];
							$pdd_behformat = $row['pdd_behformat'];
							$pdd_behdir = $row['pdd_behdir'];
							$pdd_enabled = $row['pdd_enabled'];
							?>
							<details class="level1" style="padding:0px">
								<summary>
									<input class="small" type="checkbox" name="primarydataenabled[<?=$neworder?>]" value="1" <? if ($pdd_enabled) {echo "checked";} ?>>
									<input class="small" type="text" name="primarydataorder[<?=$neworder?>]" size="2" maxlength="5" value="<?=$neworder?>">
									<input class="small" type="text" name="primaryprotocol[<?=$neworder?>]" size="40" value='<?=$pdd_protocol?>' title='Enter exact protocol name(s). Use quotes if entering a protocol with spaces or entering more than one protocol: "Task1" "Task 2" "Etc". Use multiple protocol names ONLY if you do not expect the protocols to occur in the same study'>
									<select class="small" name="primarymodality[<?=$neworder?>]">
										<option value="">(Select modality)</option>
									<?
										$sqlstringA = "select * from modalities order by mod_desc";
										$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
										while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
											$mod_code = $rowA['mod_code'];
											$mod_desc = $rowA['mod_desc'];
											
											/* check if the modality table exists */
											$sqlstring2 = "show tables from " . $GLOBALS['cfg']['mysqldatabase'] . " like '" . strtolower($mod_code) . "_series'";
											//echo $sqlstring2;
											$result2 = MySQLiQuery($sqlstring2,__FILE__,__LINE__);
											if (mysqli_num_rows($result2) > 0) {
											
												/* if the table does exist, allow the user to search on it */
												if ($mod_code == $pdd_modality) {
													$selected = "selected";
												}
												else {
													$selected = "";
												}
												?>
												<option value="<?=$mod_code?>" <?=$selected?>><?=$mod_code?></option>
												<?
											}
										}
									?>
									</select>
									
								</summary>
								<table class="entrytable" style="background-color: #EEE; border-radius:9px; border: 1px solid #999">
									<tr>
										<td class="label">Data format</td>
										<td>
											<select class="small" name="primarydataformat[<?=$neworder?>]">
												<option value="native" <? if ($pdd_dataformat == "native") { echo "selected"; } ?>>Native</option>
												<option value="dicom" <? if ($pdd_dataformat == "dicom") { echo "selected"; } ?>>DICOM</option>
												<option value="nifti3d" <? if ($pdd_dataformat == "nifti3d") { echo "selected"; } ?>>Nifti 3D</option>
												<option value="nifti4d" <? if ($pdd_dataformat == "nifti4d") { echo "selected"; } ?>>Nifti 4D</option>
												<option value="analyze3d" <? if ($pdd_dataformat == "analyze3d") { echo "selected"; } ?>>Analyze 3D</option>
												<option value="analyze4d" <? if ($pdd_dataformat == "analyze4d") { echo "selected"; } ?>>Analyze 4D</option>
											</select>
										</td>
									</tr>
									<tr>
										<td class="label">Image type</td>
										<td><input class="small" type="text" name="primaryimagetype[<?=$neworder?>]" size="30" value="<?=$pdd_imagetype?>"></td>
									</tr>
									<tr>
										<td class="label">g-zip</td>
										<td><input class="small" type="checkbox" name="primarygzip[<?=$neworder?>]" value="1" <? if ($pdd_gzip) {echo "checked";} ?>></td>
									</tr>
									<tr>
										<td class="label">Directory<br><span class="tiny">Relative to analysis root</span></td>
										<td title="<b>Tip:</b> choose a directory called 'data/<i>taskname</i>'. If converting data or putting into a new directory structure, this data directory can be used as a staging area and can then be deleted later in your script"><input class="small" type="text" name="primarylocation[<?=$neworder?>]" size="30" value="<?=$pdd_location?>"></td>
									</tr>
									<tr>
										<td class="label">Criteria <img src="images/help.gif" title="<b>All</b> - All matching series will be downloaded<br><b>First</b> - Only the lowest numbered series will be downloaded<br><b>Last</b> - Only the highest numbered series will be downloaded<br><b>Largest</b> - Only one series with the most number of volumes or slices will be downloaded<br><b>Smallest</b> - Only one series with the least number of volumes or slices will be downloaded"></td>
										<td>
											<select class="small" name="primaryseriescriteria[<?=$neworder?>]">
												<option value="all" <? if ($pdd_seriescriteria == "all") { echo "selected"; } ?>>All</option>
												<option value="first" <? if ($pdd_seriescriteria == "first") { echo "selected"; } ?>>First</option>
												<option value="last" <? if ($pdd_seriescriteria == "last") { echo "selected"; } ?>>Last</option>
												<option value="largestsize" <? if ($pdd_seriescriteria == "largestsize") { echo "selected"; } ?>>Largest</option>
												<option value="smallestsize" <? if ($pdd_seriescriteria == "smallestsize") { echo "selected"; } ?>>Smallest</option>
											</select>
										</td>
									</tr>
									<tr>
										<td class="label">Use series directories</td>
										<td title="<b>Tip:</b> If you plan to download multiple series with the same name, you will want to use series directories. This option will place each series into its own directory (data/task/1, data/task/2, etc)"><input class="small" type="checkbox" name="primaryuseseriesdirs[<?=$neworder?>]" value="1" <? if ($pdd_useseries) {echo "checked";} ?>></td>
									</tr>
									<tr>
										<td class="label">Preserve series numbers <img src="images/help.gif" title="If data is placed in a series directory, check this box to preserve the original series number. Otherwise the series number directories will be sequential starting at 1, regardless of the orignal series number"></td>
										<td><input class="small" type="checkbox" name="primarypreserveseries[<?=$neworder?>]" value="1" <? if ($pdd_preserveseries) {echo "checked";} ?>></td>
									</tr>
									<tr>
										<td class="label">Behavioral data directory format</td>
										<td>
											<select class="small" name="primarybehformat[<?=$neworder?>]">
												<option value="behnone" <? if ($pdd_behformat == "behnone") { echo "selected"; } ?>>Don't download behavioral data</option>
												<option value="behroot" <? if ($pdd_behformat == "behroot") { echo "selected"; } ?>>Place in root (file.log)</option>
												<option value="behrootdir" <? if ($pdd_behformat == "behrootdir") { echo "selected"; } ?>>Place in directory in root (beh/file.log)</option>
												<option value="behseries" <? if ($pdd_behformat == "behseries") { echo "selected"; } ?>>Place in series (2/file.log)</option>
												<option value="behseriesdir" <? if ($pdd_behformat == "behseriesdir") { echo "selected"; } ?>>Place in directory in series (2/beh/file.log)</option>
											</select>
										</td>
									</tr>
									<tr>
										<td class="label">Behavioral data directory name</td>
										<td><input class="small" type="text" name="primarybehdir[<?=$neworder?>]" size="30" value="<?=$pdd_behdir?>"></td>
									</tr>
								</table>
								<br>
							</details>
							<?
							$neworder++;
						}
						
					for ($ii=0;$ii<5;$ii++) {
					?>
					<details class="level1" style="padding:0px">
					<summary>
						<input class="small" type="checkbox" name="primarydataenabled[<?=$neworder?>]" value="1">
						<input class="small" type="text" name="primarydataorder[<?=$neworder?>]" size="2" maxlength="5" value="<?=$neworder?>">
						<input class="small" type="text" name="primaryprotocol[<?=$neworder?>]" size="40" title='Enter exact protocol name(s). Use quotes if entering a protocol with spaces or entering more than one protocol: "Task1" "Task 2" "Etc". Use multiple protocol names ONLY if you do not expect the protocols to occur in the same study'>
						<select class="small" name="primarymodality[<?=$neworder?>]">
							<option value="">(Select modality)</option>
						<?
							$sqlstring = "select * from modalities order by mod_desc";
							$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$mod_code = $row['mod_code'];
								$mod_desc = $row['mod_desc'];
								
								/* check if the modality table exists */
								$sqlstring2 = "show tables from " . $GLOBALS['cfg']['mysqldatabase'] . " like '" . strtolower($mod_code) . "_series'";
								//echo $sqlstring2;
								$result2 = MySQLiQuery($sqlstring2,__FILE__,__LINE__);
								if (mysqli_num_rows($result2) > 0) {
									if ($mod_code == $pdd_modality) {
										$selected = "selected";
									}
									else {
										$selected = "";
									}
									?>
									<option value="<?=$mod_code?>" <?=$selected?>><?=$mod_code?></option>
									<?
								}
							}
						?>
						</select>
					</summary>
						<table class="entrytable" style="background-color: #EEE; border-radius:9px; border: 1px solid #999">
							<tr>
								<td class="label">Data format</td>
								<td>
									<select class="small" name="primarydataformat[<?=$neworder?>]">
										<option value="native">Native</option>
										<option value="dicom">DICOM</option>
										<option value="nifti3d">Nifti 3D</option>
										<option value="nifti4d">Nifti 4D</option>
										<option value="analyze3d">Analyze 3D</option>
										<option value="analyze4d">Analyze 4D</option>
									</select>
								</td>
							</tr>
							<tr>
								<td class="label">Image type</td>
								<td><input class="small" type="text" name="primaryimagetype[<?=$neworder?>]" size="30"></td>
							</tr>
							<tr>
								<td class="label">g-zip</td>
								<td><input class="small" type="checkbox" name="primarygzip[<?=$neworder?>]" value="1"></td>
							</tr>
							<tr>
								<td class="label">Location</td>
								<td><input class="small" type="text" name="primarylocation[<?=$neworder?>]" size="30"></td>
							</tr>
							<tr>
								<td class="label">Criteria <img src="images/help.gif" title="<b>All</b> - All matching series will be downloaded<br><b>First</b> - Only the lowest numbered series will be downloaded<br><b>Last</b> - Only the highest numbered series will be downloaded<br><b>Largest</b> - Only one series with the most number of volumes or slices will be downloaded<br><b>Smallest</b> - Only one series with the least number of volumes or slices will be downloaded"></td>
								<td>
									<select class="small" name="primaryseriescriteria[<?=$neworder?>]">
										<option value="all">All</option>
										<option value="first">First</option>
										<option value="last">Last</option>
										<option value="largestsize">Largest</option>
										<option value="smallestsize">Smallest</option>
									</select>
								</td>
							</tr>
							<tr>
								<td class="label">Use series directories</td>
								<td><input class="small" type="checkbox" name="primaryuseseriesdirs[<?=$neworder?>]" value="1"></td>
							</tr>
							<tr>
								<td class="label">Preserve series numbers <img src="images/help.gif" title="If data is placed in a series directory, check this box to preserve the original series number. Otherwise the series number directories will be sequential starting at 1, regardless of the orignal series number"></td>
								<td><input class="small" type="checkbox" name="primarypreserveseries[<?=$neworder?>]" value="1"></td>
							</tr>
							<tr>
								<td class="label">Behavioral data directory format</td>
								<td>
									<select class="small" name="primarybehformat[<?=$neworder?>]">
										<option value="behnone">Don't download behavioral data</option>
										<option value="behroot">Place in root (file.log)</option>
										<option value="behrootdir">Place in directory in root (beh/file.log)</option>
										<option value="behseries">Place in series (2/file.log)</option>
										<option value="behseriesdir">Place in directory in series (2/beh/file.log)</option>
									</select>
								</td>
							</tr>
							<tr>
								<td class="label">Behavioral data directory name</td>
								<td><input class="small" type="text" name="primarybehdir[<?=$neworder?>]" size="30"></td>
							</tr>
						</table>
						<br>
					</details>
					<?
						$neworder++;
					}
					?>
				</td>
				<td valign="top" width="50%">
				<!-- ************* associated data spec *************** -->
					<div style="text-align:left; font-size:12pt; font-weight: bold; color:#214282;" class="level1">Associated Data</div>
					<span class="tiny level1">...will be downloaded from any other <u>studies</u> (imaging session) from the same subject if the adjacent primary data is found</span>
					<br class="level1">
					<table class="level1" cellspacing="0" cellpadding="0">
						<tr style="color:#444; font-size:10pt">
							<td width="60px">Enabled</td>
							<td width="50px">Order</td>
							<td width="250px">Protocol</td>
							<td width="215px">Data Association</td>
						</tr>
					</table>
					<?
						$neworder = 1;
						/* display all other rows, sorted by order */
						$sqlstring = "select * from pipeline_data_def where pipeline_id = $id and pipeline_version = $version and pdd_type = 'associated' order by pdd_order + 0";
						$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							//PrintVariable($row);
							$pipelinedatadef_id = $row['pipelinedatadef_id'];
							$pdd_order = $row['pdd_order'];
							$pdd_protocol = $row['pdd_protocol'];
							$pdd_modality = $row['pdd_modality'];
							$pdd_assoctype = $row['pdd_assoctype'];
							$pdd_seriescriteria = $row['pdd_seriescriteria'];
							$pdd_dataformat = $row['pdd_dataformat'];
							$pdd_imagetype = $row['pdd_imagetype'];
							$pdd_gzip = $row['pdd_gzip'];
							$pdd_location = $row['pdd_location'];
							$pdd_useseries = $row['pdd_useseries'];
							$pdd_preserveseries = $row['pdd_preserveseries'];
							$pdd_behformat = $row['pdd_behformat'];
							$pdd_behdir = $row['pdd_behdir'];
							$pdd_enabled = $row['pdd_enabled'];
							?>
							<details class="level1" style="padding:0px">
								<summary>
									<input class="small" type="checkbox" name="assocdataenabled[<?=$neworder?>]" value="1" <? if ($pdd_enabled) {echo "checked";} ?>>
									<input class="small" type="text" name="assocdataorder[<?=$neworder?>]" size="2" maxlength="5" value="<?=$neworder?>">
									<input class="small" type="text" name="assocprotocol[<?=$neworder?>]" size="30" value='<?=$pdd_protocol?>' title='Enter exact protocol name(s). Use quotes if entering a protocol with spaces or entering more than one protocol: "Task1" "Task 2" "Etc". Use multiple protocol names ONLY if you do not expect the protocols to occur in the same study'>
									<select class="small" name="assoctype[<?=$neworder?>]">
										<option value="">(Select association)</option>
										<option value="nearesttime" <? if ($pdd_assoctype == "nearesttime") { echo "selected"; } ?>>Nearest in time</option>
										<option value="samestudytype" <? if ($pdd_assoctype == "samestudytype") { echo "selected"; } ?>>Same visit type</option>
									</select>
								</summary>
								<table class="entrytable" style="background-color: #EEE; border-radius:9px; border: 1px solid #999">
									<tr>
										<td class="label">Modality</td>
										<td>
											<select class="small" name="assocmodality[<?=$neworder?>]">
												<option value="">(Select modality)</option>
											<?
												$sqlstringA = "select * from modalities order by mod_desc";
												$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
												while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
													$mod_code = $rowA['mod_code'];
													$mod_desc = $rowA['mod_desc'];
													
													/* check if the modality table exists */
													$sqlstring2 = "show tables from " . $GLOBALS['cfg']['mysqldatabase'] . " like '" . strtolower($mod_code) . "_series'";
													//echo $sqlstring2;
													$result2 = MySQLiQuery($sqlstring2,__FILE__,__LINE__);
													if (mysqli_num_rows($result2) > 0) {
													
														/* if the table does exist, allow the user to search on it */
														//if (($mod_code == "MR") && ($pdd_modality == "")) {
														//	$selected = "selected";
														//}
														//else {
															if ($mod_code == $pdd_modality) {
																$selected = "selected";
															}
															else {
																$selected = "";
															}
														//}
														?>
														<option value="<?=$mod_code?>" <?=$selected?>><?=$mod_code?></option>
														<?
													}
												}
											?>
											</select>
										</td>
									</tr>
									<tr>
										<td class="label">Data format</td>
										<td>
											<select class="small" name="assocdataformat[<?=$neworder?>]">
												<option value="native" <? if ($pdd_dataformat == "native") { echo "selected"; } ?>>Native</option>
												<option value="dicom" <? if ($pdd_dataformat == "dicom") { echo "selected"; } ?>>DICOM</option>
												<option value="nifti3d" <? if ($pdd_dataformat == "nifti3d") { echo "selected"; } ?>>Nifti 3D</option>
												<option value="nifti4d" <? if ($pdd_dataformat == "nifti4d") { echo "selected"; } ?>>Nifti 4D</option>
												<option value="analyze3d" <? if ($pdd_dataformat == "analyze3d") { echo "selected"; } ?>>Analyze 3D</option>
												<option value="analyze4d" <? if ($pdd_dataformat == "analyze4d") { echo "selected"; } ?>>Analyze 4D</option>
											</select>
										</td>
									</tr>
									<tr>
										<td class="label">Image type</td>
										<td><input class="small" type="text" name="associmagetype[<?=$neworder?>]" size="30" value="<?=$pdd_imagetype?>"></td>
									</tr>
									<tr>
										<td class="label">g-zip</td>
										<td><input class="small" type="checkbox" name="assocgzip[<?=$neworder?>]" value="1" <? if ($pdd_gzip) {echo "checked";} ?>></td>
									</tr>
									<tr>
										<td class="label">Directory<br><span class="tiny">Relative to analysis root</span></td>
										<td><input class="small" type="text" name="assoclocation[<?=$neworder?>]" size="30" value="<?=$pdd_location?>"></td>
									</tr>
									<tr>
										<td class="label">Criteria</td>
										<td>
											<select class="small" name="assocseriescriteria[<?=$neworder?>]">
												<option value="all" <? if ($pdd_seriescriteria == "all") { echo "selected"; } ?>>All</option>
												<option value="first" <? if ($pdd_seriescriteria == "first") { echo "selected"; } ?>>First</option>
												<option value="last" <? if ($pdd_seriescriteria == "last") { echo "selected"; } ?>>Last</option>
												<option value="largestsize" <? if ($pdd_seriescriteria == "largestsize") { echo "selected"; } ?>>Largest</option>
												<option value="smallestsize" <? if ($pdd_seriescriteria == "smallestsize") { echo "selected"; } ?>>Smallest</option>
											</select>
										</td>
									</tr>
									<tr>
										<td class="label">Use series directories</td>
										<td><input class="small" type="checkbox" name="assocuseseriesdirs[<?=$neworder?>]" value="1" <? if ($pdd_useseries) {echo "checked";} ?>></td>
									</tr>
									<tr>
										<td class="label">Preserve series numbers</td>
										<td><input class="small" type="checkbox" name="assocpreserveseries[<?=$neworder?>]" value="1" <? if ($pdd_preserveseries) {echo "checked";} ?>></td>
									</tr>
									<tr>
										<td class="label">Behavioral data directory format</td>
										<td>
											<select class="small" name="assocbehformat[<?=$neworder?>]">
												<option value="behnone" <? if ($pdd_behformat == "behnone") { echo "selected"; } ?>>Don't download behavioral data</option>
												<option value="behroot" <? if ($pdd_behformat == "behroot") { echo "selected"; } ?>>Place in root (file.log)</option>
												<option value="behrootdir" <? if ($pdd_behformat == "behrootdir") { echo "selected"; } ?>>Place in directory in root (beh/file.log)</option>
												<option value="behseries" <? if ($pdd_behformat == "behseries") { echo "selected"; } ?>>Place in series (2/file.log)</option>
												<option value="behseriesdir" <? if ($pdd_behformat == "behseriesdir") { echo "selected"; } ?>>Place in directory in series (2/beh/file.log)</option>
											</select>
										</td>
									</tr>
									<tr>
										<td class="label">Behavioral data directory name</td>
										<td><input class="small" type="text" name="assocbehdir[<?=$neworder?>]" size="30" value="<?=$pdd_behdir?>"></td>
									</tr>
								</table>
								<br>
							</details>
							<?
							$neworder++;
						}

						/* display rows for new data */
						for ($ii=0;$ii<5;$ii++) {
						?>
						<details class="level1" style="padding:0px">
							<summary>
								<input class="small" type="checkbox" name="assocdataenabled[<?=$neworder?>]" value="1">
								<input class="small" type="text" name="assocdataorder[<?=$neworder?>]" size="2" maxlength="5" value="<?=$neworder?>">
								<input class="small" type="text" name="assocprotocol[<?=$neworder?>]" size="30" title='Enter exact protocol name(s). Use quotes if entering a protocol with spaces or entering more than one protocol: "Task1" "Task 2" "Etc". Use multiple protocol names ONLY if you do not expect the protocols to occur in the same study'>
								<select class="small" name="assoctype[<?=$neworder?>]">
									<option value="">(Select association)</option>
									<option value="nearesttime">Nearest in time</option>
									<option value="samestudytype">Same visit type</option>
								</select>
							</summary>
							<table class="entrytable" style="background-color: #EEE; border-radius:9px; border: 1px solid #999">
								<tr>
									<td class="label">Modality</td>
									<td>
										<select class="small" name="assocmodality[<?=$neworder?>]">
											<option value="">(Select modality)</option>
										<?
											$sqlstring = "select * from modalities order by mod_desc";
											$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
											while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
												$mod_code = $row['mod_code'];
												$mod_desc = $row['mod_desc'];
												
												/* check if the modality table exists */
												$sqlstring2 = "show tables from " . $GLOBALS['cfg']['mysqldatabase'] . " like '" . strtolower($mod_code) . "_series'";
												//echo $sqlstring2;
												$result2 = MySQLiQuery($sqlstring2,__FILE__,__LINE__);
												if (mysqli_num_rows($result2) > 0) {
												
													/* if the table does exist, allow the user to search on it */
													//if (($mod_code == "MR") && ($pdd_modality == "")) {
													//	$selected = "selected";
													//}
													//else {
														if ($mod_code == $pdd_modality) {
															$selected = "selected";
														}
														else {
															$selected = "";
														}
													//}
													?>
													<option value="<?=$mod_code?>" <?=$selected?>><?=$mod_code?></option>
													<?
												}
											}
										?>
										</select>
									</td>
								</tr>
								<tr>
									<td class="label">Data format</td>
									<td>
										<select class="small" name="assocdataformat[<?=$neworder?>]">
											<option value="native">Native</option>
											<option value="dicom">DICOM</option>
											<option value="nifti3d">Nifti 3D</option>
											<option value="nifti4d">Nifti 4D</option>
											<option value="analyze3d">Analyze 3D</option>
											<option value="analyze4d">Analyze 4D</option>
										</select>
									</td>
								</tr>
								<tr>
									<td class="label">Image type</td>
									<td><input class="small" type="text" name="associmagetype[<?=$neworder?>]" size="30"></td>
								</tr>
								<tr>
									<td class="label">g-zip</td>
									<td><input class="small" type="checkbox" name="assocgzip[<?=$neworder?>]" value="1"></td>
								</tr>
								<tr>
									<td class="label">Directory<br><span class="tiny">Relative to analysis root</span></td>
									<td><input class="small" type="text" name="assoclocation[<?=$neworder?>]" size="30"></td>
								</tr>
								<tr>
									<td class="label">Criteria</td>
									<td>
										<select class="small" name="assocseriescriteria[<?=$neworder?>]">
											<option value="all">All</option>
											<option value="first">First</option>
											<option value="last">Last</option>
											<option value="largestsize">Largest</option>
											<option value="smallestsize">Smallest</option>
										</select>
									</td>
								</tr>
								<tr>
									<td class="label">Use series directories</td>
									<td><input class="small" type="checkbox" name="assocuseseriesdirs[<?=$neworder?>]" value="1"></td>
								</tr>
								<tr>
									<td class="label">Preserve series numbers</td>
									<td><input class="small" type="checkbox" name="assocpreserveseries[<?=$neworder?>]" value="1"></td>
								</tr>
								<tr>
									<td class="label">Behavioral data directory format</td>
									<td>
										<select class="small" name="assocbehformat[<?=$neworder?>]">
											<option value="behnone">Don't download behavioral data</option>
											<option value="behroot">Place in root (file.log)</option>
											<option value="behrootdir">Place in directory in root (beh/file.log)</option>
											<option value="behseries">Place in series (2/file.log)</option>
											<option value="behseriesdir">Place in directory in series (2/beh/file.log)</option>
										</select>
									</td>
								</tr>
								<tr>
									<td class="label">Behavioral data directory name</td>
									<td><input class="small" type="text" name="assocbehdir[<?=$neworder?>]" size="30"></td>
								</tr>
							</table>
							<br>
						</details>
						<? 
							$neworder++;
						}
					?>
				</td>
			</tr>
		</table>
		<?
		} /* end of the check to display the data specs */ ?>
		
		<br><br>
		<div style="text-align:left; font-size:12pt; font-weight: bold; color:#214282;">Script commands<br><span class="tiny" style="font-weight:normal">Ctrl+S to save</span></span>
		<br><br>
		
		<style type="text/css" media="screen">
			#commandlist { 
				position: relative;
				width: 1000px;
				height: 700px;
				top: 0;
				right: 0;
				bottom: 0;
				left: 0;
			}
		</style>
		</b>
		<table>
			<tr>
				<td valign="top">
		<textarea name="commandlist" style="font-weight:normal"><?
			$sqlstring = "select * from pipeline_steps where pipeline_id = $id and pipeline_version = $version order by ps_order + 0";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$pipelinestep_id = $row['pipelinestep_id'];
				$ps_desc = $row['ps_description'];
				$ps_order = $row['ps_order'];
				$ps_command = $row['ps_command'];
				$ps_workingdir = $row['ps_workingdir'];
				$ps_enabled = $row['ps_enabled'];
				$ps_logged = $row['ps_logged'];
				if ($ps_enabled == 1) { $enabled = ""; } else { $enabled = "#"; }
				if ($ps_logged == 1) { $logged = ""; } else { $logged = "{NOLOG}"; }
echo "$enabled$ps_command     # $logged $ps_desc\n";
			}
		?></textarea>
		<div id="commandlist" style="border: 1px solid #666; font-weight: normal"></div>
		<script src="scripts/aceeditor/ace.js" type="text/javascript" charset="utf-8"></script>
		<!--<script src="http://d1n0x3qji82z53.cloudfront.net/src-min-noconflict/ace.js" type="text/javascript" charset="utf-8"></script>-->
		<script>
			var editor = ace.edit("commandlist");
			var textarea = $('textarea[name="commandlist"]').hide();
			editor.setFontSize(12);
			editor.getSession().setMode("ace/mode/sh");
			editor.getSession().setUseWrapMode(false);
			editor.getSession().setValue(textarea.val());
			<?if ($readonly) { ?>
			editor.setReadOnly();
			<? } ?>
			editor.getSession().on('change', function(){
			  textarea.val(editor.getSession().getValue());
			});
			editor.setTheme("ace/theme/xcode");
			
			function insertText(text) {
				editor.insert(text);
			}
			function toggleWrap() {
				if (editor.getSession().getUseWrapMode()) {
					editor.getSession().setUseWrapMode(false);
				}
				else {
					editor.getSession().setUseWrapMode(true);
				}
			}
			$(window).bind('keydown', function(event) {
				if (event.ctrlKey || event.metaKey) {
					switch (String.fromCharCode(event.which).toLowerCase()) {
						case 's':
							event.preventDefault();
							//alert('ctrl-s');
							document.getElementById('stepsform').submit();
							break;
					}
				}
			});		
		</script>
				</td>
				<td valign="top" align="center">
				<b>Available pipeline variables</b><br>
				<span class="tiny">Click variable to insert at current editor location</span>
				<br><br>
				<table>
					<tr><td class="pipelinevariable" onclick="insertText('{analysisrootdir}');" title="Full path to the root directory of the analysis">{analysisrootdir}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{subjectuid}');" title="Example: S1234ABC">{subjectuid}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{studynum}');" title="Example: 1">{studynum}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{uidstudynum}');" title="Example: S1234ABC1">{uidstudynum}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{pipelinename}');" title="<?=$title?>">{pipelinename}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{studydatetime}');" title="YYYYMMDDHHMMSS">{studydatetime}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{first_ext_file}');" title="Expands to first file found with extenstion. Replace ext with the extension">{first_ext_file}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{first_n_ext_files}');" title="Finds first file with extension">{first_n_ext_files}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{last_ext_file}');" title="Finds last file (alphabetically) with extension">{last_ext_file}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{all_ext_files}');" title="Finds all files matching the extension">{all_ext_files}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{command}');" title="Full command, excluding comment">{command}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{workingdir}');" title="Not dynamic, not changed at run-time">{workingdir}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{description}');" title="The description (comment)">{description}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{analysisid}');" title="Analysis ID">{analysisid}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{NOLOG}');" title="Insert in the comment and the line will not be logged. Useful if the command is using the > or >> operators to write to a file">{NOLOG}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{NOCHECKIN}');" title="Insert in the comment and the step will not be reported. Useful for command line for-loops">{NOCHECKIN}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{subjectuids}');" title="Space separated list of UIDs. For group analyses">{subjectuids}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{studydatetimes}');" title="Space separated list of datetimes, ordered by datetime. For group analyses">{studydatetimes}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{analysisgroupid}');" title="Group analysis ID">{analysisgroupid}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{uidstudynums}');" title="Space separated list of uidstudynums for all groups">{uidstudynums}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{numsubjects}');" title="Number of subjects from all groups">{numsubjects}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{groups}');" title="Space separated list of groups">{groups}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{numsubjects_groupname}');" title="Number of subjects (sessions) in the group specified">{numsubjects_groupname}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{uidstudynums_groupname}');" title="Space separated list of uidstudynums for the group specified">{uidstudynums_groupname}</td></tr>
				</table>
				<br><br>
				<span class="editoroptions" onClick="toggleWrap()">Toggle text wrap</span>
				</td>
			</tr>
		</table>
				<tr>
					<td colspan="6" align="center">
						<br><br>
						<input type="submit" <?=$disabled?> value="Update Pipeline Definition Only">
					</td>
				</tr>
				</form>
			</tbody>
		</table>
		</fieldset>
		
		<br><br>
		<br><br>
		
		<?
		}
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayPipeline -------------------- */
	/* -------------------------------------------- */
	function DisplayPipeline($id, $version) {
	
		$sqlstring = "select * from pipelines where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$title = $row['pipeline_name'];
		$desc = $row['pipeline_desc'];
		if ($version == "") {
			$version = $row['pipeline_version'];
		}
		
		//$urllist['Analysis'] = "analysis.php";
		$urllist['Pipelines'] = "pipelines.php";
		$urllist[$title] = "pipelines.php?action=editpipeline&id=$id";
		NavigationBar("Analysis", $urllist);

		?>
		<form method="post" action="pipelines.php" name="versionform">
		<input type="hidden" name="action" value="viewpipeline">
		<input type="hidden" name="id" value="<?=$id?>">
		View different version:
		<select name="version" onchange='versionform.submit()'>
			<option value="">(select version)</option>
		<?
		$sqlstring = "select distinct(pipeline_version) from pipeline_steps where pipeline_id = $id order by pipeline_version desc";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$versionnumber = $row['pipeline_version'];
			?>
			<option value="<?=$versionnumber?>"><?=$versionnumber?>
			<?
		}
		?>
		</select>
		</form>
		<?
	?>

		<div align="center">

		<br><br>
		<table class="codelisting">
			<tr>
				<td class="title" colspan="3"><?=$title?> version <?=$version?></td>
			</tr>
			<tr>
				<td class="desc" colspan="3"><?=$desc?></td>
			</tr>
			<tr>
				<td colspan="3">&nbsp;</td>
			</tr>
			<tbody>
				<tr>
					<td class="sectionhead">
						Data
						<table>
							<tr>
								<td></td>
								<td class="colhead">Protocol (full)</td>
								<td class="colhead">Modality</td>
								<td class="colhead">Data format</td>
								<td class="colhead">Location</td>
								<td class="colhead">Use series?</td>
								<td class="colhead">Preserve series?</td>
								<td class="colhead">Beh format</td>
								<td class="colhead">Beh dir</td>
							</tr>
							<?
							$sqlstring = "select * from pipeline_data_def where pipeline_id = $id and pipeline_version = $version order by pdd_order + 0";
							//PrintSQL($sqlstring);
							$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$pipelinedatadef_id = $row['pipelinedatadef_id'];
								$pdd_order = $row['pdd_order'];
								$pdd_protocol = $row['pdd_protocol'];
								$pdd_modality = $row['pdd_modality'];
								$pdd_dataformat = $row['pdd_dataformat'];
								$pdd_imagetype = $row['pdd_imagetype'];
								$pdd_gzip = $row['pdd_gzip'];
								$pdd_location = $row['pdd_location'];
								$pdd_useseries = $row['pdd_useseries'];
								$pdd_preserveseries = $row['pdd_preserveseries'];
								$pdd_behformat = $row['pdd_behformat'];
								$pdd_behdir = $row['pdd_behdir'];
								$pdd_enabled = $row['pdd_enabled'];
								?>
								<tr style="color:<? if (!$pdd_enabled) { echo "#BBBBBB"; } else { echo "#000000"; } ?>">
									<td class="order"><?=$pdd_order?></td>
									<td class="datadef"><?=$pdd_protocol?></td>
									<td class="datadef"><?=$pdd_modality?></td>
									<td class="datadef"><?=$pdd_dataformat?></td>
									<td class="datadef"><?=$pdd_imagetype?></td>
									<td class="datadef"><?=$pdd_gzip?></td>
									<td class="datadef"><?=$pdd_location?></td>
									<td class="datadef"><?=$pdd_useseries?></td>
									<td class="datadef"><?=$pdd_preserveseries?></td>
									<td class="datadef"><?=$pdd_behformat?></td>
									<td class="datadef"><?=$pdd_behdir?></td>
								</tr>
								<?
							}
							?>
						</table>
					</td>
				</tr>
				
				<tr>
					<td class="sectionhead">
						<br>
						Steps
						<table>
							<?
								/* display all other rows, sorted by order */
								$sqlstring = "select * from pipeline_steps where pipeline_id = $id and pipeline_version = $version order by ps_order + 0";
								$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
								while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
									$pipelinestep_id = $row['pipelinestep_id'];
									$ps_desc = $row['ps_description'];
									$ps_order = $row['ps_order'];
									$ps_command = $row['ps_command'];
									$ps_workingdir = $row['ps_workingdir'];
									//$ps_parameters = $row['ps_parameters'];
									$ps_enabled = $row['ps_enabled'];
									$ps_logged = $row['ps_logged'];
									?>
									<tr style="color:<? if (!$ps_enabled) { echo "#BBBBBB"; } else { echo "#000000"; } ?>; font-family: courier new; font-size:10pt;">
										<td>
										<?=$ps_order?> <?=$ps_command?> <span style="color:green"># <?=$ps_desc?></span>
										</td>
									</tr>
									<?
								}
							?>
						</table>
					</td>
				</tr>

				<tr>
					<td class="sectionhead">
						<br>
						SGE job file
						<table>
							<tr>
								<td style="text-align: left; background-color: white; border: 1px solid #666666; padding:8px">
								<tt>
								#!/bin/sh<br>
								#$ -N <?=$title?><br>
								#$ -S /bin/sh<br>
								#$ -j y<br>
								#$ -o {$subjectpath}/pipeline<br>
								#$ -u nidb<br>
								
								cd {subjectroot};<br>
								<?
								/* display all other rows, sorted by order */
								$sqlstring = "select * from pipeline_steps where pipeline_id = $id and pipeline_version = $version order by ps_order + 0";
								$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
								while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
									$pipelinestep_id = $row['pipelinestep_id'];
									$ps_desc = $row['ps_description'];
									$ps_order = $row['ps_order'];
									$ps_command = $row['ps_command'];
									$ps_workingdir = $row['ps_workingdir'];
									$ps_enabled = $row['ps_enabled'];
									$ps_logged = $row['ps_logged'];
									
									if (!$ps_enabled) { echo "# "; }
									echo "<br># $ps_desc<br>";
									if (!$ps_enabled) { echo "# "; }
									echo "cd $ps_workingdir; ";
									if (!$ps_enabled) { echo "# "; }
									echo "$ps_command; ";
									if ($ps_logged) { echo " > $id-$ps_order.log"; }
									echo "<br>";
								}
								?>
								</tt>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				
			</tbody>
		</table>
		<br><br>
		
		</div>
	<?
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayAnalysisList ---------------- */
	/* -------------------------------------------- */
	function DisplayAnalysisList($id, $numperpage, $pagenum) {
	
		$sqlstring = "select pipeline_name, pipeline_level from pipelines where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$pipeline_name = $row['pipeline_name'];
		$pipeline_level = $row['pipeline_level'];
	
		//$urllist['Analysis'] = "analysis.php";
		$urllist['Pipelines'] = "pipelines.php";
		$urllist["$pipeline_name"] = "pipelines.php?action=editpipeline&id=$id";
		$urllist["Analysis List"] = "pipelines.php?action=viewanalyses&id=$id";
		NavigationBar("Analysis", $urllist);
		
		/* prep the pagination */
		if ($numperpage == "") { $numperpage = 1000; }
		if (($pagenum == "") || ($pagenum < 1)) { $pagenum = 1; }
		$limitstart = ($pagenum-1)*$numperpage;
		$limitcount = $numperpage;

		/* create the color lookup table */
		$colors = GenerateColorGradient();
		//echo "<pre>";
		//print_r($colors);
		//echo "</pre>";
		
		/* run the sql query here to get the row count */
		$sqlstring = "select *, timediff(analysis_enddate, analysis_startdate) 'analysis_time', timediff(analysis_clusterenddate, analysis_clusterstartdate) 'cluster_time' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and analysis_status <> 'NoMatchingStudies'";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$numrows = mysqli_num_rows($result);
		$numpages = ceil($numrows/$numperpage);
		if ($pagenum > $numpages) { $pagenum = $numpages; }
		?>
		<div id="dialogbox" title="Dialog Box" style="display:none;">Loading...</div>
		<script type="text/javascript">
			//$(document).ready(function() {
			//	$(".fancybox").fancybox();
			//});
		</script>
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
			<tr>
				<td class="label"><?=$numrows?> analyses</td>
				<td class="pagenum">Page <?=$pagenum?> of <?=$numpages?> <span class="tiny">(<?=$numperpage?>/page)</span></td>
				<td class="middle">&nbsp;</td>
				<td class="firstpage" title="First page"><a href="pipelines.php?action=viewanalyses&id=<?=$id?>&numperpage=<?=$numperpage?>&pagenum=1">&#171;</a></td>
				<td class="previouspage" title="Previous page"><a href="pipelines.php?action=viewanalyses&id=<?=$id?>&numperpage=<?=$numperpage?>&pagenum=<?=($pagenum-1)?>">&lsaquo;</a></td>
				<td title="Refresh page"><a href="" style="margin-left:20px; margin-right:20px; font-size:14pt">&#10227;</a></td>
				<td class="nextpage" title="Next page"><a href="pipelines.php?action=viewanalyses&id=<?=$id?>&numperpage=<?=$numperpage?>&pagenum=<?=($pagenum+1)?>">&rsaquo;</a></td>
				<td class="lastpage" title="Last page"><a href="pipelines.php?action=viewanalyses&id=<?=$id?>&numperpage=<?=$numperpage?>&pagenum=<?=$numpages?>">&#187;</a></td>
			</tr>
		</table>
		<form method="post" name="studieslist" action="pipelines.php">
		<input type="hidden" name="action" value="deleteanalyses" id="studieslistaction">
		<input type="hidden" name="destination" value="" id="studieslistdestination">
		<input type="hidden" name="analysisnotes" value="">
		<input type="hidden" name="analysisid" value="">
		<input type="hidden" name="id" value="<?=$id?>">
		<table id="analysistable" class="smallgraydisplaytable" width="100%">
		<!--<table id="analysistable" class="tablesorter" width="100%">-->
			<thead>
				<tr>
					<th><input type="checkbox" id="studiesall"> Study</th>
					<th>Pipeline<br>version</th>
					<? if ($pipeline_level == 1) { ?>
					<th>Study date</th>
					<th># series</th>
					<? } ?>
					<th>Status</th>
					<th>Complete</th>
					<th>Logs</th>
					<th>Files</th>
					<th>Results</th>
					<th>Notes</th>
					<th>Message</th>
					<th>Size<br><span class="tiny">bytes</span></th>
					<th>Hostname</th>
					<th>Setup time<br><span class="tiny">completed date</span></th>
					<th>Cluster time<br><span class="tiny">completed date</span></th>
					<th style="color:darkred">Delete <input type="checkbox" id="analysesall"></th>
				</tr>
			</thead>
			<tbody>
				<?
					$sqlstring = "select *, timediff(analysis_enddate, analysis_startdate) 'analysis_time', timediff(analysis_clusterenddate, analysis_clusterstartdate) 'cluster_time' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and a.analysis_status <> 'NoMatchingStudies' order by a.analysis_status desc, study_datetime desc limit $limitstart, $limitcount";
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
						$notes = $row['analysis_notes'];
						$analysis_hostname = $row['analysis_hostname'];
						$cluster_time = $row['cluster_time'];
						$analysis_enddate = date('Y-m-d H:i',strtotime($row['analysis_enddate']));
						$analysis_clusterenddate = date('Y-m-d H:i',strtotime($row['analysis_clusterenddate']));
						$study_id = $row['study_id'];
						$study_num = $row['study_num'];
						$study_datetime = date('M j, Y H:i',strtotime($row['study_datetime']));
						$uid = $row['uid'];
						$pipeline_version = $row['pipeline_version'];
						$pipeline_dependency = $row['pipeline_dependency'];
						
						$sqlstringA = "select pipeline_submithost from pipelines where pipeline_id = $id";
						//PrintSQL($sqlstringA);
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
						//echo "$analysis_size, $sizeindex, $sizecolor, $maxsize<br>";
				?>
				<script>
					function GetAnalysisNotes<?=$analysis_id?>(){
						var analysisnotes = prompt("Enter notes for this analysis","<?=$notestitle?>");
						if (analysisnotes != null){
						  //$("#analysisnotes").attr("value", analysisnotes);
						  document.studieslist.analysisnotes.value = analysisnotes;
						  document.studieslist.action.value = 'setanalysisnotes';
						  document.studieslist.id.value = '<?=$id?>';
						  document.studieslist.analysisid.value = '<?=$analysis_id?>';
						  document.studieslist.submit();
					   }
					}
				</script>
				<tr bgcolor="<?=$rowcolor?>">
					<td class="allstudies" style="text-align:left"><input type="checkbox" name="studyid[]" value="<?=$study_id?>">
						<a href="studies.php?id=<?=$study_id?>"><?=$uid?><?=$study_num?></a></td>
					<td><?=$pipeline_version?></td>
					<? if ($pipeline_level == 1) { ?>
					<td class="tiny"><?=$study_datetime?></td>
					<td><?=$analysis_numseries?></td>
					<? } ?>
					<td>
						<?
							if (($analysis_status == 'processing') && ($analysis_qsubid != 0)) {
								//$systemstring = "SGE_ROOT=/sge/sge-root; export SGE_ROOT; SGE_CELL=nrccell; export SGE_CELL; cd /sge/sge-root/bin/lx24-amd64; ./qstat -j $analysis_qsubid";
								$systemstring = "ssh $pipeline_submithost qstat -j $analysis_qsubid";
								//echo "$systemstring";
								//$out = shell_exec($systemstring);
							
								if (trim($out) == "hi") {
									?><img src="images/alert.png" title="Analysis is marked as running, but the cluster job is not.<br><br>This means the analysis is being setup and the data is being copied or the cluster job failed. Check log files for error"><?
								}
								?>
								<!--<a class="fancybox" title="SGE status" href="pipelines.php?action=viewjob&id=<?=$analysis_qsubid?>">processing</a>-->
								<a href="<?=$GLOBALS['cfg']['siteurl']?>/pipelines.php?action=viewjob&id=<?=$analysis_qsubid?>">processing</a>
								<?
							}
							else {
								if ($analysis_qsubid == 0) {
									echo "Preparing data";
								}
								else {
									echo $analysis_status;
								}
							}
						?>
					</td>
					<td style="font-weight: bold; color: green"><? if ($analysis_iscomplete) { echo "&#x2713;"; } ?></td>
					<? if ($analysis_status != "") { ?>
					<td>
						<a href="#" id="viewlog<?=$analysis_id?>"><img src="images/preview.gif"></a>
						<script>
							$(document).ready(function() {
								$("a#viewlog<?=$analysis_id?>").click(function(e) {
									e.preventDefault();
									$("#dialogbox").load("viewanalysis.php?action=viewlogs&analysisid=<?=$analysis_id?>").dialog({height:800, width:1200});
								});
							});
						</script>
					</td>
					<td>
						<a href="#" id="viewfiles<?=$analysis_id?>"><img src="images/folder.gif"></a>
						<script>
							$(document).ready(function() {
								$("a#viewfiles<?=$analysis_id?>").click(function(e) {
									e.preventDefault();
									$("#dialogbox").load("viewanalysis.php?action=viewfiles&analysisid=<?=$analysis_id?>").dialog({height:800, width:1200});
								});
							});
						</script>
					</td>
					<td>
						<a href="#" id="viewresults<?=$analysis_id?>"><img src="images/chart-vertical.png"></a>
						<script>
							$(document).ready(function() {
								$("a#viewresults<?=$analysis_id?>").click(function(e) {
									e.preventDefault();
									$("#dialogbox").load("viewanalysis.php?action=viewresults&analysisid=<?=$analysis_id?>&studyid=<?=$study_id?>").dialog({height:800, width:1200});
								});
							});
						</script>
					</td>
					<? } else { ?>
					<td></td>
					<td></td>
					<? } ?>
					<!--<form action="pipelines.php" method="post" name="setanalysisnotes<?=$analysis_id?>">
					<input type="hidden" name="action" value="setanalysisnotes">
					<input type="hidden" name="id" value="<?=$analysis_id?>">
					<input type="hidden" name="analysisnotes" id="analysisnotes" value="<?=$notestitle?>">-->
					<td>
						<span onClick="GetAnalysisNotes<?=$analysis_id?>();" style="cursor:hand; font-size:14pt; color: <?=$notescolor?>" title="<?=$notestitle?>">&#9998;</span>
					</td>
					<!--</form>-->
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
					var destination = prompt("Please enter a valid destination for the selected analyses","/home/<?=$GLOBALS['username']?>/onrc/data");
					if (destination != null){
					  //document.studieslist.destination.value = desination;
					  document.studieslist.action='pipelines.php';
					  //document.studieslist.action.value='copyanalyses';
					  $("#studieslistaction").attr("value", "copyanalyses");
					  $("#studieslistdestination").attr("value", destination);
					  document.studieslist.submit();
				   }
				}
				function GetDestination2(){
					var destination = prompt("Please enter a valid directory in which to create the 'data' directory and links","/home/<?=$GLOBALS['username']?>/onrc/data");
					if (destination != null){
					  document.studieslist.action='pipelines.php';
					  $("#studieslistaction").attr("value", "createlinks");
					  $("#studieslistdestination").attr("value", destination);
					  document.studieslist.submit();
				   }
				}
				function MarkAnalysis(){
					document.studieslist.action='pipelines.php';
					document.studieslist.submit();
				}
				</script>
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
					<td colspan="7" align="right" style="background-color: #fff; font-size: 12pt">
					With selected:&nbsp;<br><br>
					<input type="submit" value="Delete" style="border: 1px solid red; background-color: pink; width:150px; margin:4px" onclick="document.studieslist.action.value='deleteanalyses';return confirm('Are you absolutely sure you want to DELETE the selected analyses?')" title="<b style='color:pink'>Pipeline will be disabled until the deletion is finished</b><Br> This will delete the selected analyses, which will be regenerated using the latest pipeline version">
					<br><br><br>
					<input type="button" name="copyanalyses" value="Copy analyses to..." style="width: 150px; margin:4px" onclick="document.studieslist.action='pipelines.php';document.studieslist.action.value='copyanalyses';GetDestination()">
					<br>
					<input type="button" name="createlinks" value="Create Links..." style="width: 150px; margin:4px" onclick="document.studieslist.action='pipelines.php';document.studieslist.action.value='createlinks';GetDestination2()" title="Creates a directory called 'data' which contains links to all of the selected studies">
					<br>
					<input type="button" name="rerunresults" value="Re-run Results Script" style="width: 150px; margin:4px" onclick="document.studieslist.action='pipelines.php';document.studieslist.action.value='rerunresults';document.studieslist.submit();" title="This will delete any existing results inserted into NiDB and re-run the results script">
					<br>
					<input type="button" name="copyanalyses" value="Mark as bad" style="width: 150px; margin:4px" onclick="document.studieslist.action='pipelines.php';document.studieslist.action.value='markbad'; MarkAnalysis()" title="Mark the analyses as bad so they will not be used in dependent pipelines">
					<br>
					<input type="button" name="copyanalyses" value="Mark as good" style="width: 150px; margin:4px" onclick="document.studieslist.action='pipelines.php';document.studieslist.action.value='markgood'; MarkAnalysis()" title="Unmark an analysis as bad">&nbsp;
					</td>
				</tr>
			</tbody>
		</table>
		</form>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayFailedAnalysisList ---------- */
	/* -------------------------------------------- */
	function DisplayFailedAnalysisList($id, $numperpage, $pagenum) {
	
		$sqlstring = "select pipeline_name, pipeline_level from pipelines where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$pipeline_name = $row['pipeline_name'];
		$pipeline_level = $row['pipeline_level'];
	
		//$urllist['Analysis'] = "analysis.php";
		$urllist['Pipelines'] = "pipelines.php";
		$urllist["$pipeline_name"] = "pipelines.php?action=editpipeline&id=$id";
		$urllist["Analysis List"] = "pipelines.php?action=viewanalyses&id=$id";
		NavigationBar("Analysis", $urllist);
		
		/* prep the pagination */
		if ($numperpage == "") { $numperpage = 10000; }
		if (($pagenum == "") || ($pagenum < 1)) { $pagenum = 1; }
		$limitstart = ($pagenum-1)*$numperpage;
		$limitcount = $numperpage;
		
		/* run the sql query here to get the row count */
		$sqlstring = "select *, timediff(analysis_enddate, analysis_startdate) 'analysis_time', timediff(analysis_clusterenddate, analysis_clusterstartdate) 'cluster_time' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and analysis_status = 'NoMatchingStudies'";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$numrows = mysqli_num_rows($result);
		$numpages = ceil($numrows/$numperpage);
		if ($pagenum > $numpages) { $pagenum = $numpages; }
		?>
		<script type="text/javascript">
		//$(window).load(function(){
		//	$("a.fancybox").fancybox({
		//		openEffect: 'none',
		//		closeEffect: 'none',
		//		iframe: {
		//			preload: false
		//		}
		//	});
		//});
		$(function() {
			//$('.fancybox').fancybox({type: "iframe", iframe: {preload: false}});
			
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
			<tr>
				<td class="label"><?=$numrows?> items</td>
				<td class="pagenum">Page <?=$pagenum?> of <?=$numpages?> <span class="tiny">(<?=$numperpage?>/page)</span></td>
				<td class="middle">&nbsp;</td>
				<td class="firstpage" title="First page"><a href="pipelines.php?action=viewanalyses&id=<?=$id?>&numperpage=<?=$numperpage?>&pagenum=1">&#171;</a></td>
				<td class="previouspage" title="Previous page"><a href="pipelines.php?action=viewanalyses&id=<?=$id?>&numperpage=<?=$numperpage?>&pagenum=<?=($pagenum-1)?>">&lsaquo;</a></td>
				<td title="Refresh page"><a href="" style="margin-left:20px; margin-right:20px; font-size:14pt">&#10227;</a></td>
				<td class="nextpage" title="Next page"><a href="pipelines.php?action=viewanalyses&id=<?=$id?>&numperpage=<?=$numperpage?>&pagenum=<?=($pagenum+1)?>">&rsaquo;</a></td>
				<td class="lastpage" title="Last page"><a href="pipelines.php?action=viewanalyses&id=<?=$id?>&numperpage=<?=$numperpage?>&pagenum=<?=$numpages?>">&#187;</a></td>
			</tr>
		</table>
		<form method="post" name="studieslist" action="pipelines.php">
		<input type="hidden" name="action" value="deleteanalyses" id="studieslistaction">
		<input type="hidden" name="destination" value="" id="studieslistdestination">
		<input type="hidden" name="analysisnotes" value="">
		<input type="hidden" name="analysisid" value="">
		<input type="hidden" name="id" value="<?=$id?>">
		<table id="analysistable" class="smallgraydisplaytable" width="100%">
		<!--<table id="analysistable" class="tablesorter" width="100%">-->
			<thead>
				<tr>
					<th><input type="checkbox" id="studiesall"> Study</th>
					<th>Pipeline<br>version</th>
					<? if ($pipeline_level == 1) { ?>
					<th>Study date</th>
					<th># series</th>
					<? } ?>
					<th>Status</th>
					<th>Data log</th>
					<th>Notes</th>
					<th>Message</th>
					<th style="color:darkred">Delete <input type="checkbox" id="analysesall"></th>
				</tr>
			</thead>
			<tbody>
				<?
					$sqlstring = "select *, timediff(analysis_enddate, analysis_startdate) 'analysis_time', timediff(analysis_clusterenddate, analysis_clusterstartdate) 'cluster_time' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and a.analysis_status = 'NoMatchingStudies' order by a.analysis_status desc, study_datetime desc limit $limitstart, $limitcount";
					//$sqlstring = "select *, timediff(analysis_enddate, analysis_startdate) 'analysis_time', timediff(analysis_clusterenddate, analysis_clusterstartdate) 'cluster_time' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and analysis_status <> '' order by cluster_time asc limit $limitstart, $limitcount";
					$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
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
						$analysis_datalog = $row['analysis_datalog'];
						$notes = $row['analysis_notes'];
						$analysis_hostname = $row['analysis_hostname'];
						$cluster_time = $row['cluster_time'];
						$analysis_enddate = date('Y-m-d H:i',strtotime($row['analysis_enddate']));
						$analysis_clusterenddate = date('Y-m-d H:i',strtotime($row['analysis_clusterenddate']));
						$study_id = $row['study_id'];
						$study_num = $row['study_num'];
						$study_datetime = date('M j, Y H:i',strtotime($row['study_datetime']));
						$uid = $row['uid'];
						$pipeline_version = $row['pipeline_version'];
						$pipeline_dependency = $row['pipeline_dependency'];
						
						$sqlstringA = "select pipeline_name from pipelines where pipeline_id = $pipeline_dependency";
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
				?>
				<script>
					function GetAnalysisNotes<?=$analysis_id?>(){
						var analysisnotes = prompt("Enter notes for this analysis","<?=$notestitle?>");
						if (analysisnotes != null){
						  //$("#analysisnotes").attr("value", analysisnotes);
						  document.studieslist.analysisnotes.value = analysisnotes;
						  document.studieslist.action.value = 'setanalysisnotes';
						  document.studieslist.analysisid.value = '<?=$analysis_id?>';
						  document.studieslist.submit();
					   }
					}
				</script>
				<tr>
					<td class="allstudies" style="text-align:left"><input type="checkbox" name="studyid[]" value="<?=$study_id?>">
						<a href="studies.php?id=<?=$study_id?>"><?=$uid?><?=$study_num?></a></td>
					<td><?=$pipeline_version?></td>
					<? if ($pipeline_level == 1) { ?>
					<td class="tiny"><?=$study_datetime?></td>
					<td><?=$analysis_numseries?></td>
					<? } ?>
					<td>
						<?
							if (($analysis_status == 'processing') && ($analysis_qsubid != 0)) {
								$systemstring = "SGE_ROOT=/sge/sge-root; export SGE_ROOT; SGE_CELL=nrccell; export SGE_CELL; cd /sge/sge-root/bin/lx24-amd64; ./qstat -j $analysis_qsubid";
								$out = shell_exec($systemstring);
							
								if (trim($out) == "") {
									?><img src="images/alert.png" title="Analysis is marked as running, but the cluster job is not.<br><br>This means the analysis is being setup and the data is being copied or the cluster job failed. Check log files for error"><?
								}
								?>
								<a class="fancybox" data-fancybox-type="iframe" title="SGE status" href="pipelines.php?action=viewjob&id=<?=$analysis_qsubid?>">processing</a>
								<!--<a class="fancybox" data-fancybox-type="iframe" href="<?=$GLOBALS['cfg']['siteurl']?>/pipelines.php?action=viewjob&id=<?=$analysis_qsubid?>">processing</a>-->
								<?
							}
							else {
								echo $analysis_status;
							}
						?>
					</td>
					<td>
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
					</td>
					<td>
						<span onClick="GetAnalysisNotes<?=$analysis_id?>();" style="cursor:hand; font-size:14pt; color: <?=$notescolor?>" title="<?=$notestitle?>">&#9998;</span>
					</td>
					<!--</form>-->
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
					<td class="allanalyses" ><input type="checkbox" name="analysisids[]" value="<?=$analysis_id?>"></td>
				</tr>
				<? 
					}
				?>
				<script>
				function GetDestination(){
					var destination = prompt("Please enter a valid destination for the selected analyses","/home/<?=$GLOBALS['username']?>/onrc/data");
					if (destination != null){
					  //document.studieslist.destination.value = desination;
					  document.studieslist.action='pipelines.php';
					  //document.studieslist.action.value='copyanalyses';
					  $("#studieslistaction").attr("value", "copyanalyses");
					  $("#studieslistdestination").attr("value", destination);
					  document.studieslist.submit();
				   }
				}
				function GetDestination2(){
					var destination = prompt("Please enter a valid directory in which to create the 'data' directory and links","/home/<?=$GLOBALS['username']?>/onrc/data");
					if (destination != null){
					  document.studieslist.action='pipelines.php';
					  $("#studieslistaction").attr("value", "createlinks");
					  $("#studieslistdestination").attr("value", destination);
					  document.studieslist.submit();
				   }
				}
				</script>
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
					<td colspan="7" align="right" style="background-color: #fff; font-size: 12pt">
					With selected:&nbsp;<br><br>
					<input type="submit" value="Delete" style="border: 1px solid red; background-color: pink; width:150px; margin:4px" onclick="document.studieslist.action.value='deleteanalyses';return confirm('Are you absolutely sure you want to DELETE the selected analyses?')" title="<b style='color:pink'>Pipeline will be disabled until the deletion is finished</b><Br> This will delete the selected analyses, which will be regenerated using the latest pipeline version">
					<br><br><br>
					<input type="button" name="copyanalyses" value="Copy analyses to..." style="width: 150px; margin:4px" onclick="document.studieslist.action='pipelines.php';document.studieslist.action.value='copyanalyses';GetDestination()">
					<br>
					<input type="button" name="createlinks" value="Create Links..." style="width: 150px; margin:4px" onclick="document.studieslist.action='pipelines.php';document.studieslist.action.value='createlinks';GetDestination2()" title="Creates a directory called 'data' which contains links to all of the selected studies">
					<br>
					<input type="button" name="rerunresults" value="Re-run Results Script" style="width: 150px; margin:4px" onclick="document.studieslist.action='pipelines.php';document.studieslist.action.value='rerunresults';document.studieslist.submit();" title="This will delete any existing results inserted into NiDB and re-run the results script">&nbsp;
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
		$urllist['Analysis list'] = "pipelines.php?action=viewanalyses&id=$pipelineid";
		NavigationBar("Analysis", $urllist);

		/* get list of steps for the appropriate version */
		$sqlstring = "select * from pipeline_steps where pipeline_id = $pipelineid and pipeline_version = $pipelineversion";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$ps_command = $row['ps_command'];
			$ps_description = $row['ps_description'];
			$ps_order = $row['ps_order'] - 1;
			$commands[$ps_order] = $ps_command;
			$descriptions[$ps_order] = $ps_description;
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
				
				if (preg_match('/step(\d*)\.log/', $log, $matches)) {
					//echo "<pre>";
					//print_r($matches);
					//echo "</pre>";
					$step = $matches[1];
					$command = $commands[$step];
					$desc = $descriptions[$step];
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
		$urllist['Analysis list'] = "pipelines.php?action=viewanalyses&id=$pipelineid";
		NavigationBar("Analysis", $urllist);
		
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
			Showing files from <b><?=$path?></b> (<?=count($files)?> files) <span class="tiny">Reading from origfiles.log</span> Read from <a href="pipelines.php?action=viewfiles&analysisid=<?=$analysisid?>&fileviewtype=filesystem">filesystem</a>
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
	/* ------- DisplayPipelineTree ---------------- */
	/* -------------------------------------------- */
	function DisplayPipelineTree($viewname, $viewlevel, $viewowner, $viewstatus, $viewenabled, $viewall) {
	
		MarkTime("DisplayPipelineTree()");
	
		$urllist['Pipelines'] = "pipelines.php";
		$urllist['New Pipeline'] = "pipelines.php?action=addform";
		NavigationBar("Analysis", $urllist);
		
		$username = $GLOBALS['username'];
	?>
	<style>
		.ui-tooltip { padding: 7px 7px; border-radius: 5px; font-size: 10px; border: 1px solid black; }
		a { color: #224ea5; }
	</style>
	<span style="font-size:10pt">View: <a href="pipelines.php?viewall=1">All</a> | <a href="pipelines.php?viewall=1" title="Does not display hidden pipelines">Normal</a></span>
	<!--<details>-->
	<!-- display the cluster load -->
	<!--<summary style="font-size:10pt; color:#666">View cluster load</summary>
		<?
		list($statsoutput,$report,$queues,$hostnames) = GetClusterStats();
		
		$slotsusedcolor = "FF4500";
		$slotsunusedcolor = "EEEEEE";

		?>

		<table border="0" cellspacing="0" cellpadding="0" style="font-size:8pt">
			<?
				foreach ($queues as $queue) {
					$slotsused = 0;
					$slotsunused = 0;
					
					foreach ($hostnames as $hostname) {
						if (isset($report[$hostname]['queues'][$queue])) {
							//echo "<pre>";
							//print_r($report[$hostname]['queues'][$queue]['jobs']);
							$slotsused += $report[$hostname]['queues'][$queue]['slotsused'];
							$slotsunused += $report[$hostname]['queues'][$queue]['slotsavailable'];
							//echo "</pre>";
						}
					}
					?>
					<tr>
						<td><?=$queue?> &nbsp;</td>
						<td>
							<img src="horizontalchart.php?b=yes&w=200&h=12&v=<?=$slotsused?>,<?=($slotsunused-$slotsused)?>&c=<?=$slotsusedcolor?>,<?=$slotsunusedcolor?>">
							<? if ($slotsused == 0) { echo "Idle"; } else { echo "$slotsused of $slotsunused"; } ?>
						</td>
					</tr>
					<?
				}
			?>
		</table>
		<br><br>
	</details> -->
	
	<table class="smallgraydisplaytable" width="100%">
		<thead>
			<tr style="vertical-align: top;text-align:left">
				<th style="font-size:12pt">Pipeline Group</th>
				<th style="font-size:12pt">Name <span class="tiny">Mouseover for description</span></th>
				<th style="font-size:12pt" align="right">Level</th>
				<!--<th style="font-size:12pt">Study Group(s)</th>-->
				<th style="font-size:12pt">Owner<br>
					<!--<span style="font-weight: normal; font-size:8pt">
					<a class="linkhighlight" href="pipelines.php?action=viewpipelinelist&viewname=<?=$viewname?>&viewlevel=<?=$viewlevel?>&viewowner=all&viewstatus=<?=$viewstatus?>&viewenabled=<?=$viewenabled?>">All</a><br>
					<a class="linkhighlight" href="pipelines.php?action=viewpipelinelist&viewname=<?=$viewname?>&viewlevel=<?=$viewlevel?>&viewowner=mine&viewstatus=<?=$viewstatus?>&viewenabled=<?=$viewenabled?>">Mine</a>
					</span>-->
				</th>
				<th style="font-size:12pt">Status</th>
				<th style="font-size:12pt" align="right" title="processing / complete">Analyses</th>
				<th style="font-size:12pt" align="right">Disk size</th>
				<th style="font-size:12pt" align="left">Path</th>
				<th style="font-size:12pt">Queue</th>
			</tr>
		</thead>
		<tbody>
			<?
				$pipelinetree = GetPipelineTree($viewall);
				
				global $imgdata;
				/* create the graphs for each pipeline group */
				$sqlstring = "select distinct(pipeline_group) 'pipeline_group' from pipelines where pipeline_group <> ''";
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$group = $row['pipeline_group'];
					$imgdata[$group] = CreatePipelineGraph($group);
				}
				GetPipelineInfo();
				PrintTree($pipelinetree,0);
			?>
		</tbody>
	</table>
	
	<br><br><br><br><br>
	<?
	}

	
	/* -------------------------------------------- */
	/* ------- GetPipelineTree -------------------- */
	/* -------------------------------------------- */
	function GetPipelineTree($viewall) {
		MarkTime("GetPipelineTree()");
	
		if ($viewall) {
			$whereclause = "";
		}
		else {
			$whereclause = "where b.pipeline_ishidden <> 1";
		}
		/* get list of pipelines */
		$sqlstring = "select a.parent_id,b.pipeline_id from pipeline_dependencies a right join pipelines b on a.pipeline_id = b.pipeline_id $whereclause order by b.pipeline_group, pipeline_name";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$childID = $row['pipeline_id'];
			$parentID = $row['parent_id'];
			if ($parentID == '') { $parentID = null; }
			$arr[$childID][] = $parentID;
		}
		
		//PrintVariable($arr,'arr');
		$tree = ParseTree($arr);
		//PrintVariable($tree,'tree');
		
		return $tree;
	}
	

	/* -------------------------------------------- */
	/* ------- ParseTree -------------------------- */
	/* -------------------------------------------- */
	function ParseTree($tree, $root = null) {
		MarkTime("ParseTree()");
		$return = array();
		# Traverse the tree and search for direct children of the root
		foreach($tree as $child => $par) {
			# A direct child is found
			foreach ($par as $parent) {
				if($parent == $root) {
					# Remove item from tree (we don't need to traverse this again)
					unset($tree[$child]);
					# Append the child into result array and parse its children
					$return[] = array(
						'pipeline_id' => $child,
						'child_id' => parseTree($tree, $child)
					);
				}
			}
		}
		return empty($return) ? null : $return;    
	}	

	
	/* -------------------------------------------- */
	/* ------- PrintTree -------------------------- */
	/* -------------------------------------------- */
	function PrintTree($tree, $level) {
		MarkTime("PrintTree()");
		if(!is_null($tree) && count($tree) > 0) {
			$level++;
			foreach($tree as $node) {
				PrintPipelineRow($GLOBALS['info'][$node['pipeline_id']], $level);
				#PrintPipelineRow(GetPipelineInfo($node['pipeline_id']), $level);
				$level = printTree($node['child_id'], $level);
			}
			$level--;
		}
		return $level;
	}	

	/* -------------------------------------------- */
	/* ------- PrintPipelineRow ------------------- */
	/* -------------------------------------------- */
	function PrintPipelineRow($info, $level) {
		MarkTime("PrintPipelineRow()");
		if ($level > 1) {
			$class = 'child';
		}

		if ($info['isenabled']) {
			//$bgcolor = "#a4f2af";
			$bgcolor = "#e3f7e6";
		}
		//else {
		//	$bgcolor = "#e3f7e6";
		//}
		//echo "<pre>";
		//print_r($info);
		//echo "</pre>";
		if ($info['ishidden']) {
			$fontcolor = "gray";
		}
		else {
			$fontcolor = "black";
		}
		
		$imgdata = $GLOBALS['imgdata'];
		?>
		<tr style="color: <?=$fontcolor?>">
			<? if (($info['pipelinegroup'] == '') || ($level > 1)) { ?>
			<td valign="top" align="left" class="<?=$class?>">&nbsp;</td>
			<? } else { ?>
			<td valign="top" align="left" class="<?=$class?>" title="<img border=1 src='data:image/png;base64,<?=$imgdata[$info['pipelinegroup']]?>'>"><?=$info['pipelinegroup']?></td>
			<? } ?>
			<td valign="top" style="padding-left: <?=($level-1)*10?>;" class="<?=$class?>" title="<b><?=$info['title']?></b> &nbsp; <?=$info['desc']?>"><? if ($level > 1) { echo "&#9495;&nbsp;"; } ?><a href="pipelines.php?action=editpipeline&id=<?=$info['id']?>" style="font-size:11pt"><?=$info['title']?></a> &nbsp; <span class="tiny">v<?=$info['version']?></span></td>
			<td valign="top" align="right"><?=$info['level']?></td>
			<!--<td valign="top" style="font-size: 8pt">
				<?
					if (count($info['groupnames']) > 0) {
						echo implode(', ', $info['groupnames']);
					}
				?>
			</td>-->
			<td valign="top"><?=$info['creatorusername']?></td>
			<td valign="top" align="left" style="background-color: <?=$bgcolor?>">
				<?
					if ($info['isenabled']) {
						?><a href="pipelines.php?action=disable&id=<?=$info['id']?>"><img src="images/checkedbox16.png" title="Enabled. Click to disable"></a><?
					}
					else {
						?><a href="pipelines.php?action=enable&id=<?=$info['id']?>"><img src="images/uncheckedbox16.png" title="Disabled. Click to enable"></a><?
					}
				?>
				<span title="<b>Last message:</b> <?=$info['message']?><br><b>Last check:</b> <?=$info['lastcheck']?>">
				<? if ($info['status'] == 'running') { ?><b>running</b> <a href="pipelines.php?action=reset&id=<?=$info['id']?>">reset</a><? } else { echo $info['status']; }  ?>
				</span>
			</td>
			<!--<td valign="top" align="right" style="font-size: 8pt; white-space:nowrap;" title="error / submitted / pending / processing / complete">
				<?=$info['numerror']?> / <?=$info['numsubmitted']?> / <?=$info['numpending']?> / <?=$info['numprocessing']?> / <b><?=$info['numcomplete']?></b> &nbsp; <a href="pipelines.php?action=viewanalyses&id=<?=$info['id']?>"><img src="images/preview.gif" title="View analysis list"></a>
			</td>-->
			<td valign="top" align="right" style="font-size: 8pt; white-space:nowrap;" title="processing / complete">
				<?=$info['numprocessing']?> / <b><?=$info['numcomplete']?></b> &nbsp; <a href="pipelines.php?action=viewanalyses&id=<?=$info['id']?>"><img src="images/preview.gif" title="View analysis list"></a>
			</td>
			<td valign="top" align="right" style="font-size:8pt"><? if ($info['disksize'] > 0) { echo number_format(($info['disksize']/1024/1024/1024),1) . '&nbsp;GB'; } ?></td>
			<? if (strlen($info['directory']) > 40) {
				?><td valign="top" align="left" title="<?=$info['directory']?>" style="font-size:8pt"><tt><?
				echo substr($info['directory'],0,40) . "...";
				?></tt></td><?
			}
			else {
				?><td valign="top" align="left" style="font-size:8pt"><tt><?
				echo $info['directory'];
				?></tt></td><?
			}
			?>
			<td valign="top"><?=$info['queue']?></td>
		</tr>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- GetPipelineInfo -------------------- */
	/* -------------------------------------------- */
	function GetPipelineInfo() {
		MarkTime("GetPipelineInfo() first call");
		
		global $info;
		
		$sqlstring = "select a.*,timediff(pipeline_lastfinish, pipeline_laststart) 'run_time', b.username 'creatorusername', b.user_fullname 'creatorfullname' from pipelines a left join users b on a.pipeline_admin = b.user_id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$id = $row['pipeline_id'];

			MarkTime("GetPipelineInfo($id)");
			
			$info[$id]['id'] = $row['pipeline_id'];
			$info[$id]['title'] = $row['pipeline_name'];
			$info[$id]['desc'] = $row['pipeline_desc'];
			$info[$id]['creatorusername'] = $row['creatorusername'];
			$info[$id]['creatorfullname'] = $row['creatorfullname'];
			$info[$id]['createdate'] = date("M n,Y", strtotime($row['pipeline_createdate']));
			$info[$id]['isenabled'] = $row['pipeline_enabled'];
			$info[$id]['ishidden'] = $row['pipeline_ishidden'];
			$info[$id]['istesting'] = $row['pipeline_testing'];
			$info[$id]['pipelinegroup'] = $row['pipeline_group'];
			$info[$id]['dependency'] = $row['pipeline_dependency'];
			$info[$id]['groupid'] = $row['pipeline_groupid'];
			$info[$id]['dynamicgroupid'] = $row['pipeline_dynamicgroupid'];
			$info[$id]['level'] = $row['pipeline_level'];
			if ($row['pipeline_directory'] == "") {
				$info[$id]['directory'] = $GLOBALS['cfg']['analysisdir'];
			}
			else {
				$info[$id]['directory'] = $row['pipeline_directory'];
			}
			$info[$id]['numproc'] = $row['pipeline_numproc'];
			$info[$id]['queue'] = $row['pipeline_queue'];
			$info[$id]['version'] = $row['pipeline_version'];
			$info[$id]['status'] = $row['pipeline_status'];
			$info[$id]['message'] = $row['pipeline_statusmessage'];
			$info[$id]['start'] = $row['pipeline_laststart'];
			$info[$id]['finish'] = $row['pipeline_lastfinish'];
			$info[$id]['lastcheck'] = $row['pipeline_lastcheck'];

			//if ($info[$id]['runtime'] == '00:00:00') { $info[$id]['runtime'] = ''; } else { $info[$id]['runtime'] = "(".$info[$id]['runtime'].")";}
			
			//if ($info[$id]['dependency'] != "") {
			//	$sqlstring0 = "select pipeline_name from pipelines where pipeline_id in (".$info[$id]['dependency'].")";
			//	$result0 = MySQLiQuery($sqlstring0,__FILE__,__LINE__);
			//	while ($row0 = mysqli_fetch_array($result0, MYSQLI_ASSOC)) {
			//		$info[$id]['dependencynames'][] = $row0['pipeline_name'];
			//	}
			//}

			//if ($groupid != "") {
			//	$sqlstring0 = "select group_name from groups where group_id in (".$info[$id]['groupid'].")";
			//	$result0 = MySQLiQuery($sqlstring0,__FILE__,__LINE__);
			//	while ($row0 = mysqli_fetch_array($result0, MYSQLI_ASSOC)) {
			//		$info[$id]['groupnames'][] = $row0['group_name'];
			//	}
			//}
			
			//if ($dynamicgroupid != "") {
			//	$sqlstring0 = "select group_name from groups where group_id = ".$info[$id]['dynamicgroupid'];
			//	$result0 = MySQLiQuery($sqlstring0,__FILE__,__LINE__);
			//	$row0 = mysqli_fetch_array($result0, MYSQLI_ASSOC);
			//	$info[$id]['dynamicgroupname'] = $row0['group_name'];
			//}
			
			//$sqlstring2 = "select count(*) 'numsteps' from pipeline_steps where pipeline_id = $id and pipeline_version = ".$info[$id]['version'];
			//$result2 = MySQLiQuery($sqlstring2,__FILE__,__LINE__);
			//$row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC);
			//$info[$id]['numsteps'] = $row2['numsteps'];
			
			MarkTime("GetPipelineInfo($id) pre size");
			
			$sqlstringD = "select sum(analysis_disksize) 'disksize' from analysis where pipeline_id = $id";
			$resultD = MySQLiQuery($sqlstringD,__FILE__,__LINE__);
			$rowD = mysqli_fetch_array($resultD, MYSQLI_ASSOC);
			$info[$id]['disksize'] = $rowD['disksize'];
			//PrintSQL($sqlstringD);
			//echo "$disksize<br>";

			MarkTime("GetPipelineInfo($id) pre counts");
		
			//$sqlstring3 = "select (select count(*) from analysis where analysis_status = '' and pipeline_id = $id) 'numblank', (select count(*) from analysis where analysis_status = 'error' and pipeline_id = $id) 'numerror', (select count(*) from analysis where analysis_status = 'submitted' and pipeline_id = $id) 'numsubmitted', (select count(*) from analysis where analysis_status = 'pending' and pipeline_id = $id) 'numpending', (select count(*) from analysis where analysis_status = 'processing' and pipeline_id = $id) 'numprocessing', (select count(*) from analysis where analysis_status = 'complete' and pipeline_id = $id) 'numcomplete'";
			//$sqlstring3 = "select (select count(*) from analysis where analysis_status = 'processing' and pipeline_id = $id) 'numprocessing', (select count(*) from analysis where analysis_status = 'complete' and pipeline_id = $id) 'numcomplete'";
			//$result3 = MySQLiQuery($sqlstring3,__FILE__,__LINE__);
			//$row3 = mysqli_fetch_array($result3, MYSQLI_ASSOC);
			//$info[$id]['numblank'] = $row3['numblank'];
			//$info[$id]['numerror'] = $row3['numerror'];
			//$info[$id]['numsubmitted'] = $row3['numsubmitted'];
			//$info[$id]['numpending'] = $row3['numpending'];
			//$info[$id]['numprocessing'] = $row3['numprocessing'];
			//$info[$id]['numcomplete'] = $row3['numcomplete'];
			
			$sqlstring3 = "select count(*) 'numprocessing' from analysis where analysis_status = 'processing' and pipeline_id = $id";
			$result3 = MySQLiQuery($sqlstring3,__FILE__,__LINE__);
			$row3 = mysqli_fetch_array($result3, MYSQLI_ASSOC);
			$info[$id]['numprocessing'] = $row3['numprocessing'];
			
			$sqlstring3 = "select count(*) 'numcomplete' from analysis where analysis_status = 'complete' and pipeline_id = $id";
			$result3 = MySQLiQuery($sqlstring3,__FILE__,__LINE__);
			$row3 = mysqli_fetch_array($result3, MYSQLI_ASSOC);
			$info[$id]['numcomplete'] = $row3['numcomplete'];
			
			MarkTime("GetPipelineInfo($id) post counts");
		}
		
		//return $info;
	}
	
	
	/* -------------------------------------------- */
	/* ------- find_all_files --------------------- */
	/* -------------------------------------------- */
	function find_all_files($dir) 
	{ 
		$root = scandir($dir); 
		foreach($root as $value) 
		{ 
			if($value === '.' || $value === '..') {continue;} 
			if(is_file("$dir/$value")) {$result[]="$dir/$value";continue;}
			if (is_array(find_all_files("$dir/$value"))) {
				foreach(find_all_files("$dir/$value") as $value)
				{
					$result[]=$value; 
				}
			}
		} 
		return $result; 
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayJob ------------------------- */
	/* -------------------------------------------- */
	function DisplayJob($id) {
		if (($id == 0) || ($id == '')) {
			echo "Invalid cluster job ID";
		}
		else {
			$systemstring = "SGE_ROOT=/sge/sge-root; export SGE_ROOT; SGE_CELL=nrccell; export SGE_CELL; cd /sge/sge-root/bin/lx24-amd64; ./qstat -j $id";
			$out = shell_exec($systemstring);
			PrintVariable($out,'output');
		}
	}
	

	/* -------------------------------------------- */
	/* ------- CreatePipelineGraph ---------------- */
	/* -------------------------------------------- */
	function CreatePipelineGraph($g) {
		//return;
		MarkTime("CreatePipelineGraph($g)");
		$dotfile = tempnam("/tmp",'DOTDOT');
		$pngfile = tempnam("/tmp",'DOTPNG');
		
		$d[] = "digraph G {";
		$sqlstring = "select * from pipelines where pipeline_group = '$g'";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$pipelinename = $row['pipeline_name'];
			$deps = $row['pipeline_dependency'];
			$groupids = $row['pipeline_groupid'];
			
			if ($deps != '') {
				$sqlstringA = "select * from pipelines where pipeline_id in ($deps)";
				$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					$depname = $rowA['pipeline_name'];
					$d[] = " \"$depname\" -> \"$pipelinename\";";
				}
			}
			
			if ($groupids != '') {
				$sqlstringA = "select * from groups where group_id in ($groupids)";
				$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					$groupname = $rowA['group_name'];
					$d[] = " \"$groupname\" -> \"$pipelinename\";";
					$d[] = " \"$groupname\" [shape=box,style=filled,color=\"lightblue\"];";
				}
			}
		}
		$d[] = "}";
		$d = array_unique($d);
		$dot = implode("\n",$d);
		file_put_contents($dotfile,$dot);
		$systemstring = "dot -Tpng $dotfile -o $pngfile";
		exec($systemstring);
		//echo $dot;
		$imdata = base64_encode(file_get_contents($pngfile));
		return $imdata;
	}


	/* -------------------------------------------- */
	/* ------- GetClusterStats -------------------- */
	/* -------------------------------------------- */
	function GetClusterStats() {
		//$statsoutput = explode("\n",shell_exec("ssh $pipeline_submithost qstat -f -u '*'"));
		$statsoutput = explode("\n",shell_exec("ssh compute01 qstat -f -u '*'"));
		
		PrintVariable($statsoutput);

		$hostname = $queue = "";
		$hostnames = $queues = null;

		foreach ($statsoutput as $line) {
			$line = trim($line);
			//echo $line;
			if (!strstr($line,'------')) {
				if (trim($line == "")) {
					break;
				}
				if (strstr($line, 'queuename')) {
					continue;
				}

				//echo "$line\n";
				if (strstr($line, '@')) {
					list($queuehost, $unk, $usage, $cpu, $arch) = preg_split('/\s+/', $line);
					list($queue, $hostname) = explode('@',$queuehost);
					//echo "[$usage]\n";
					list($slotsres,$slotsused,$slotsavailable) = explode('/',$usage);
					//echo "Queue: [$queue], Host: [$hostname], [$slotsused] of [$slotsavailable], CPU: [$cpu]\n";
					$report[$hostname]['queues'][$queue] = null;
					$report[$hostname]['cpu'] = $cpu;
					$report[$hostname]['arch'] = $arch;
					$report[$hostname]['queues'][$queue]['slotsused'] = $slotsused;
					$report[$hostname]['queues'][$queue]['slotsavailable'] = $slotsavailable;
					
					if ( (!isset($hostnames)) || (!in_array($hostname, $hostnames)) ) {
						$hostnames[] = $hostname;
					}
					if ( (!isset($queues)) || (!in_array($queue, $queues)) ) {
						$queues[] = $queue;
					}
				}
				else {
					//echo "$line\n";
					$report[$hostname]['queues'][$queue]['jobs'][] = $line;
					$report[$hostname]['queues'][$queue]['slotsused'] = $slotsused;
					$report[$hostname]['queues'][$queue]['slotsavailable'] = $slotsavailable;
				}
			}
		}
		//print_r($hostnames);
		//print_r($queues);
		//print_r($report);
		
		sort($hostnames);
		sort($queues);
		
		return array($statsoutput,$report,$queues,$hostnames);
	}
	
	
	function MarkTime($msg) {
	
		$time = number_format((microtime(true) - $GLOBALS['timestart']), 3);
		
		$GLOBALS['t'][][$msg] = $time;
		//echo "<div class='tiny'>[$msg] $time s</div>\n";
	}

	//PrintVariable($GLOBALS['t']);
?>


<? include("footer.php") ?>
