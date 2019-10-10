<?
 // ------------------------------------------------------------------------------
 // NiDB pipelines.php
 // Copyright (C) 2004 - 2019
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
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	//PrintVariable($_POST, "POST");
	//PrintVariable($_GET, "GET");
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$id = GetVariable("id");
	
	$viewname = GetVariable("viewname");
	$viewlevel = GetVariable("viewlevel");
	$viewowner = GetVariable("viewowner");
	$viewstatus = GetVariable("viewstatus");
	$viewenabled = GetVariable("viewenabled");
	$viewall = GetVariable("viewall");
	$viewuserid = GetVariable("viewuserid");
	
	$pipelinetitle = GetVariable("pipelinetitle");
	$pipelinedesc = GetVariable("pipelinedesc");
	$pipelinegroup = GetVariable("pipelinegroup");
	$pipelinenumproc = GetVariable("pipelinenumproc");
	$pipelineclustertype = GetVariable("pipelineclustertype");
	$pipelineclusteruser = GetVariable("pipelineclusteruser");
	$pipelinesubmithost = GetVariable("pipelinesubmithost");
	$pipelinemaxwalltime = GetVariable("pipelinemaxwalltime");
	$pipelinesubmitdelay = GetVariable("pipelinesubmitdelay");
	$pipelinequeue = GetVariable("pipelinequeue");
	$pipelinedatacopymethod = GetVariable("pipelinedatacopymethod");
	$pipelineremovedata = GetVariable("pipelineremovedata");
	$pipelineresultsscript = GetVariable("pipelineresultsscript");
	$pipelinedirectory = GetVariable("pipelinedirectory");
	$pipelinedirstructure = GetVariable("pipelinedirstructure");
	$pipelineusetmpdir = GetVariable("pipelineusetmpdir");
	$pipelinetmpdir = GetVariable("pipelinetmpdir");
	$pipelinenotes = GetVariable("pipelinenotes");
	$version = GetVariable("version");
	$completefiles = GetVariable("completefiles");
	$dependency = GetVariable("dependency");
	$deplevel = GetVariable("deplevel");
	$depdir = GetVariable("depdir");
	$deplinktype = GetVariable("deplinktype");
	$groupid = GetVariable("groupid");
	//$dynamicgroupid = GetVariable("dynamicgroupid");
	$level = GetVariable("level");
	$ishidden = GetVariable("pipelineishidden");
	$groupbysubject = GetVariable("groupbysubject");

	$newname = GetVariable("newname");
	$newuserid = GetVariable("newuserid");
	
	$commandlist = GetVariable("commandlist");
	$supplementcommandlist = GetVariable("supplementcommandlist");

	$dd_enabled = GetVariable("dd_enabled");
	$dd_order = GetVariable("dd_order");
	$dd_protocol = GetVariable("dd_protocol");
	$dd_modality = GetVariable("dd_modality");
	$dd_datalevel = GetVariable("dd_datalevel");
	$dd_studyassoc = GetVariable("dd_studyassoc");
	$dd_dataformat = GetVariable("dd_dataformat");
	$dd_imagetype = GetVariable("dd_imagetype");
	$dd_gzip = GetVariable("dd_gzip");
	$dd_location = GetVariable("dd_location");
	$dd_seriescriteria = GetVariable("dd_seriescriteria");
	$dd_numboldreps = GetVariable("dd_numboldreps");
	$dd_behformat = GetVariable("dd_behformat");
	$dd_behdir = GetVariable("dd_behdir");
	$dd_useseriesdirs = GetVariable("dd_useseriesdirs");
	$dd_optional = GetVariable("dd_optional");
	$dd_preserveseries = GetVariable("dd_preserveseries");
	$dd_usephasedir = GetVariable("dd_usephasedir");
	
	$returnpage = GetVariable("returnpage");
	
	/* determine action */
	switch ($action) {
		case 'editpipeline': DisplayPipelineForm("edit", $id); break;
		case 'viewversion': DisplayVersion($id, $version); break;
		case 'addform': DisplayPipelineForm("add", ""); break;
		case 'updatepipelineoptions':
			UpdatePipelineOptions($id, $commandlist, $supplementcommandlist, $steporder, $dd_enabled, $dd_order, $dd_protocol, $dd_modality, $dd_datalevel, $dd_studyassoc, $dd_dataformat, $dd_imagetype, $dd_gzip, $dd_location, $dd_seriescriteria, $dd_numboldreps, $dd_behformat, $dd_behdir, $dd_useseriesdirs, $dd_optional, $dd_preserveseries, $dd_usephasedir, $pipelineresultsscript, $completefiles, $deplevel, $depdir, $deplinktype, $groupid, $dependency, $groupbysubject);
			DisplayPipelineForm("edit", $id);
			break;
		case 'update':
			UpdatePipeline($id, $pipelinetitle, $pipelinedesc, $pipelinegroup, $pipelinenumproc, $pipelineclustertype, $pipelineclusteruser, $pipelinesubmithost, $pipelinemaxwalltime, $pipelinesubmitdelay, $pipelinedatacopymethod, $pipelinequeue, $pipelineremovedata, $pipelinedirectory, $pipelinedirstructure, $pipelineusetmpdir, $pipelinetmpdir, $pipelinenotes, $username, $level, $ishidden);
			DisplayPipelineForm("edit", $id);
			break;
		case 'add':
			$id = AddPipeline($pipelinetitle, $pipelinedesc, $pipelinegroup, $pipelinenumproc, $pipelineclustertype, $pipelineclusteruser, $pipelinesubmithost, $pipelinemaxwalltime, $pipelinesubmitdelay, $pipelinedatacopymethod, $pipelinequeue, $pipelineremovedata, $pipelinedirectory, $pipelinedirstructure, $pipelineusetmpdir, $pipelinetmpdir, $pipelinenotes, $username, $completefiles, $dependency, $deplevel, $depdir, $deplinktype, $groupid, $level, $groupbysubject);
			DisplayPipelineForm("edit", $id);
			break;
		case 'changeowner':
			ChangeOwner($id,$newuserid);
			DisplayPipelineTree($viewname, $viewlevel, $viewowner, $viewstatus, $viewenabled, $viewall, $viewuserid);
			break;
		case 'delete':
			DeletePipeline($id);
			DisplayPipelineTree($viewname, $viewlevel, $viewowner, $viewstatus, $viewenabled, $viewall, $viewuserid);
			break;
		case 'copy':
			CopyPipeline($id, $newname);
			DisplayPipelineTree($viewname, $viewlevel, $viewowner, $viewstatus, $viewenabled, $viewall, $viewuserid);
			break;
		case 'reset':
			ResetPipeline($id);
			DisplayPipelineTree($viewname, $viewlevel, $viewowner, $viewstatus, $viewenabled, $viewall, $viewuserid);
			break;
		case 'resetanalyses':
			ResetAnalyses($id);
			DisplayPipelineTree($viewname, $viewlevel, $viewowner, $viewstatus, $viewenabled, $viewall, $viewuserid);
			break;
		case 'disable':
			DisablePipeline($id);
			if ($returnpage == "home") {
				DisplayPipelineTree($viewname, $viewlevel, $viewowner, $viewstatus, $viewenabled, $viewall, $viewuserid);
			}
			else {
				DisplayPipelineForm("edit", $id);
			}
			break;
		case 'enable':
			EnablePipeline($id);
			if ($returnpage == "home") {
				DisplayPipelineTree($viewname, $viewlevel, $viewowner, $viewstatus, $viewenabled, $viewall, $viewuserid);
			}
			else {
				DisplayPipelineForm("edit", $id);
			}
			break;
		case 'viewpipelinelist':
			DisplayPipelineTree($viewname, $viewlevel, $viewowner, $viewstatus, $viewenabled, $viewall, $viewuserid);
			break;
		case 'viewusage': DisplayPipelineUsage();
			break;
		default: DisplayPipelineTree($viewname, $viewlevel, $viewowner, $viewstatus, $viewenabled, $viewall, $viewuserid);
	}
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- UpdatePipeline --------------------- */
	/* -------------------------------------------- */
	/* this function does NOT CHANGE the version    */
	/* number                                       */
	/* -------------------------------------------- */
	function UpdatePipeline($id, $pipelinetitle, $pipelinedesc, $pipelinegroup, $pipelinenumproc, $pipelineclustertype, $pipelineclusteruser, $pipelinesubmithost, $pipelinemaxwalltime, $pipelinesubmitdelay, $pipelinedatacopymethod, $pipelinequeue, $pipelineremovedata, $pipelinedirectory, $pipelinedirstructure, $pipelineusetmpdir, $pipelinetmpdir, $pipelinenotes, $username, $level, $ishidden) {
		
		if (!ValidID($id,'Pipeline ID - A')) { return; }
		
		/* perform data checks */
		$pipelinetitle = mysqli_real_escape_string($GLOBALS['linki'], $pipelinetitle);
		$pipelinedesc = mysqli_real_escape_string($GLOBALS['linki'], $pipelinedesc);
		$pipelinegroup = mysqli_real_escape_string($GLOBALS['linki'], $pipelinegroup);
		$pipelinenumproc = mysqli_real_escape_string($GLOBALS['linki'], $pipelinenumproc);
		$pipelineclustertype = mysqli_real_escape_string($GLOBALS['linki'], $pipelineclustertype);
		$pipelineclusteruser = mysqli_real_escape_string($GLOBALS['linki'], $pipelineclusteruser);
		$pipelinesubmithost = mysqli_real_escape_string($GLOBALS['linki'], $pipelinesubmithost);
		$pipelinemaxwalltime = mysqli_real_escape_string($GLOBALS['linki'], $pipelinemaxwalltime) + 0;
		$pipelinesubmitdelay = mysqli_real_escape_string($GLOBALS['linki'], $pipelinesubmitdelay) + 0;
		$pipelinedatacopymethod = mysqli_real_escape_string($GLOBALS['linki'], $pipelinedatacopymethod);
		$pipelinequeue = mysqli_real_escape_string($GLOBALS['linki'], $pipelinequeue);
		$pipelineremovedata = mysqli_real_escape_string($GLOBALS['linki'], $pipelineremovedata) + 0;
		$pipelinedirectory = mysqli_real_escape_string($GLOBALS['linki'], $pipelinedirectory);
		$pipelinedirstructure = mysqli_real_escape_string($GLOBALS['linki'], $pipelinedirstructure);
		$pipelineusetmpdir = mysqli_real_escape_string($GLOBALS['linki'], $pipelineusetmpdir) + 0;
		$pipelinetmpdir = mysqli_real_escape_string($GLOBALS['linki'], $pipelinetmpdir);
		$pipelinenotes = mysqli_real_escape_string($GLOBALS['linki'], $pipelinenotes);
		$ishidden = mysqli_real_escape_string($GLOBALS['linki'], $ishidden) + 0;
		$pipelinequeue = preg_replace('/\s+/', '', trim($pipelinequeue));
		
		/* update the pipeline */
		$sqlstring = "update pipelines set pipeline_name = '$pipelinetitle', pipeline_desc = '$pipelinedesc', pipeline_group = '$pipelinegroup', pipeline_numproc = $pipelinenumproc, pipeline_submithost = '$pipelinesubmithost', pipeline_maxwalltime = '$pipelinemaxwalltime', pipeline_submitdelay = '$pipelinesubmitdelay', pipeline_datacopymethod = '$pipelinedatacopymethod', pipeline_queue = '$pipelinequeue', pipeline_clustertype = '$pipelineclustertype', pipeline_clusteruser = '$pipelineclusteruser', pipeline_removedata = '$pipelineremovedata', pipeline_directory = '$pipelinedirectory', pipeline_dirstructure = '$pipelinedirstructure', pipeline_usetmpdir = '$pipelineusetmpdir', pipeline_tmpdir = '$pipelinetmpdir', pipeline_notes = '$pipelinenotes', pipeline_level = $level, pipeline_ishidden = '$ishidden' where pipeline_id = $id";
		//PrintVariable($sqlstring);
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);

		?><div align="center" class="message"><span class="message">Pipeline info [<?=$pipelinetitle?>] updated</span></div><?
	}

	
	/* -------------------------------------------- */
	/* ------- UpdatePipelineOptions -------------- */
	/* -------------------------------------------- */
	/* this function CHANGES the version number     */
	/* -------------------------------------------- */
	function UpdatePipelineOptions($id, $commandlist, $supplementcommandlist, $steporder, $dd_enabled, $dd_order, $dd_protocol, $dd_modality, $dd_datalevel, $dd_studyassoc, $dd_dataformat, $dd_imagetype, $dd_gzip, $dd_location, $dd_seriescriteria, $dd_numboldreps, $dd_behformat, $dd_behdir, $dd_useseriesdirs, $dd_optional, $dd_preserveseries, $dd_usephasedir, $pipelineresultsscript, $completefiles, $deplevel, $depdir, $deplinktype, $groupid, $dependency, $groupbysubject) {
		
		if (!ValidID($id,'Pipeline ID - C')) { return; }

		?>
		<span class="tiny">
		<ol>
		<?
		$sqlstring = "start transaction";
		echo "<li><b>Starting transaction</b>";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* get the current and next pipeline version # */
		$sqlstring = "select pipeline_version from pipelines where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$oldversion = $row['pipeline_version'];
		$newversion = $oldversion + 1;
		echo "<li>Got new version number [$newversion]";

		/* insert row in the pipeline version table */
		$sqlstring = "insert into pipeline_version (pipeline_id, version, version_datetime, version_notes) values ($id, $newversion, now(), '')";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		echo "<li>Updated pipeline_version table";
		
		/* the pipeline option information is updated in two tables, for backward compatibility...
		   old pipelines do not do version control on the pipeline options */
		$pipelineresultsscript = mysqli_real_escape_string($GLOBALS['linki'], $pipelineresultsscript);
		$completefiles = mysqli_real_escape_string($GLOBALS['linki'], $completefiles);
		$deplevel = mysqli_real_escape_string($GLOBALS['linki'], $deplevel);
		$depdir = mysqli_real_escape_string($GLOBALS['linki'], $depdir);
		$deplinktype = mysqli_real_escape_string($GLOBALS['linki'], $deplinktype);
		$groupbysubject = mysqli_real_escape_string($GLOBALS['linki'], $groupbysubject) + 0;
		if (is_array($dependency)) { $dependencies = implode(",",$dependency); }
		else { $dependencies = $dependency; }
		if (is_array($groupid)) { $groupids = implode(",",$groupid); }
		else { $groupids = $groupid; }

		/* update the pipeline table */
		$sqlstring = "update pipelines set pipeline_resultsscript = '$pipelineresultsscript', pipeline_completefiles = '$completefiles', pipeline_dependency = '$dependencies', pipeline_groupid = '$groupids', pipeline_dependencylevel = '$deplevel', pipeline_dependencydir = '$depdir', pipeline_deplinktype = '$deplinktype', pipeline_groupbysubject = $groupbysubject where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		echo "<li>Updated pipelines table";
		
		/* delete any existing dependencies, and insert the current dependencies */
		$sqlstring = "delete from pipeline_dependencies where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		echo "<li>Deleted old dependencies";

		if ($dependency != '') {
			if (is_array($dependency)) {
				foreach ($dependency as $dep) {
					$sqlstring = "insert into pipeline_dependencies (pipeline_id, parent_id) values ($id,'$dep')";
					$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
					echo "<li>Inserted dependency ($dep)";
				}
			}
			else {
				$sqlstring = "insert into pipeline_dependencies (pipeline_id, parent_id) values ($id,'$dependency')";
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
				echo "<li>Inserted dependency ($dependency)";
			}
		}

		/* add row to the pipeline_options table for the new version */
		$sqlstring = "insert into pipeline_options (pipeline_id, pipeline_version, pipeline_dependency, pipeline_dependencylevel, pipeline_dependencydir, pipeline_deplinktype, pipeline_groupid, pipeline_groupbysubject, pipeline_completefiles, pipeline_resultsscript) values ($id, $newversion, '$dependencies', '$deplevel', '$depdir', '$deplinktype', '$groupids', '$groupbysubject', '$completefiles', '$pipelineresultscript')";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		echo "<li>Updated pipeline_options table";
		
		/* split up the commandlist into commands, then split them into enabled, command, description, logged, etc */
		$commands = explode("\n",$commandlist);
		$step = 1;
		foreach ($commands as $line) {
			/* remove any trailing carriage returns or whitespace */
			$line = rtrim($line);
			
			/* check if the command should be logged */
			if (stristr($line, '{NOLOG}') === false) {
				$logged[$step] = 1;
			}
			else {
				$logged[$step] = 0;
				$line = str_replace('{NOLOG}','',$line);
			}
			
			/* check if the command should be enabled... or if the first character is a comment */
			if (preg_match('/^\s*\#/', $line)) {
				$stepenabled[$step] = 0;
				$parts = preg_split("/\s+\#/", $line);
				$command[$step] = $parts[0];
				$description[$step] = $parts[1];
				if ($command[$step] == "") {
					$command[$step] = " ";
				}
			}
			else {
				$stepenabled[$step] = 1;
				$parts = preg_split("/\s+\#/", $line);
				$command[$step] = $parts[0];
				$description[$step] = $parts[1];
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
				$steporder[$i] = trim(mysqli_real_escape_string($GLOBALS['linki'], $steporder[$i]));
				$command[$i] = rtrim(mysqli_real_escape_string($GLOBALS['linki'], $command[$i]));
				$workingdir[$i] = trim(mysqli_real_escape_string($GLOBALS['linki'], $workingdir[$i]));
				$description[$i] = trim(mysqli_real_escape_string($GLOBALS['linki'], $description[$i]));
				$stepenabled[$i] = trim(mysqli_real_escape_string($GLOBALS['linki'], $stepenabled[$i]));
				$logged[$i] = trim(mysqli_real_escape_string($GLOBALS['linki'], $logged[$i]));
				$sqlstring = "insert into pipeline_steps (pipeline_id, pipeline_version, ps_supplement, ps_command, ps_workingdir, ps_order, ps_description, ps_enabled, ps_logged) values ($id, $newversion, 0, '$command[$i]', '$workingdir[$i]', '$steporder[$i]', '$description[$i]', '$stepenabled[$i]', '$logged[$i]')";
				//printSQL($sqlstring);
				echo "<li>Inserted step $i: [$command[$i]]\n";
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			}
		}

		/* split up the SUPPLEMENT commandlist into commands, then split them into enabled, command, description, logged, etc */
		$supplementcommands = explode("\n",$supplementcommandlist);
		$step = 1;
		foreach ($supplementcommands as $line) {
			/* check if the line is blank */
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
			
			/* check if the command should be enabled... or if the first character is a comment */
			if (preg_match('/^\s*\#/', $line)) {
				$stepenabled[$step] = 0;
				$parts = preg_split("/\s+\#/", $line);
				$supplementcommand[$step] = $parts[0];
				$supplementdescription[$step] = $parts[1];
				if ($supplementcommand[$step] == "") {
					$supplementcommand[$step] = " ";
				}
			}
			else {
				$stepenabled[$step] = 1;
				$parts = preg_split("/\s+\#/", $line);
				$supplementcommand[$step] = $parts[0];
				$supplementdescription[$step] = $parts[1];
				if ($supplementcommand[$step] == "") {
					$supplementcommand[$step] = $line;
				}
			}

			$workingdir[$step] = "";
			$steporder[$step] = $step;
			$step++;
		}
		
		/* insert all the new fields with NEW version # */
		for($i=1; $i<=count($steporder); $i++) {
			if (trim($supplementcommand[$i]) != "") {
				/* perform data checks */
				$steporder[$i] = trim(mysqli_real_escape_string($GLOBALS['linki'], $steporder[$i]));
				$supplementcommand[$i] = rtrim(mysqli_real_escape_string($GLOBALS['linki'], $supplementcommand[$i]));
				$workingdir[$i] = trim(mysqli_real_escape_string($GLOBALS['linki'], $workingdir[$i]));
				$supplementdescription[$i] = trim(mysqli_real_escape_string($GLOBALS['linki'], $supplementdescription[$i]));
				$stepenabled[$i] = trim(mysqli_real_escape_string($GLOBALS['linki'], $stepenabled[$i]));
				$logged[$i] = trim(mysqli_real_escape_string($GLOBALS['linki'], $logged[$i]));
				$sqlstring = "insert into pipeline_steps (pipeline_id, pipeline_version, ps_supplement, ps_command, ps_workingdir, ps_order, ps_description, ps_enabled, ps_logged) values ($id, $newversion, 1, '$supplementcommand[$i]', '$workingdir[$i]', '$steporder[$i]', '$supplementdescription[$i]', '$stepenabled[$i]', '$logged[$i]')";
				//printSQL($sqlstring);
				echo "<li>Inserted step $i: [$supplementcommand[$i]]\n";
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			}
		}
		
		
		?><div align="center"><span class="message">Pipeline steps [<?=$id?>] updated</span></div><?
		
		/* insert all the new PRIMARY data fields with NEW version # */
		for($i=0; $i<=count($dd_protocol); $i++) {
			if (trim($dd_protocol[$i]) != "") {
				/* perform data checks */
				$dd_enabled[$i] = mysqli_real_escape_string($GLOBALS['linki'], $dd_enabled[$i]) + 0;
				$dd_order[$i] = mysqli_real_escape_string($GLOBALS['linki'], $dd_order[$i]);
				$dd_protocol[$i] = mysqli_real_escape_string($GLOBALS['linki'], $dd_protocol[$i]);
				$dd_modality[$i] = mysqli_real_escape_string($GLOBALS['linki'], $dd_modality[$i]);
				$dd_datalevel[$i] = mysqli_real_escape_string($GLOBALS['linki'], $dd_datalevel[$i]);
				$dd_studyassoc[$i] = trim(mysqli_real_escape_string($GLOBALS['linki'], $dd_studyassoc[$i]));
				$dd_dataformat[$i] = mysqli_real_escape_string($GLOBALS['linki'], $dd_dataformat[$i]);
				$dd_imagetype[$i] = mysqli_real_escape_string($GLOBALS['linki'], $dd_imagetype[$i]);
				$dd_gzip[$i] = mysqli_real_escape_string($GLOBALS['linki'], $dd_gzip[$i]) + 0;
				$dd_location[$i] = mysqli_real_escape_string($GLOBALS['linki'], $dd_location[$i]);
				$dd_seriescriteria[$i] = mysqli_real_escape_string($GLOBALS['linki'], $dd_seriescriteria[$i]);
				$dd_numboldreps[$i] = mysqli_real_escape_string($GLOBALS['linki'], $dd_numboldreps[$i]);
				$dd_behformat[$i] = mysqli_real_escape_string($GLOBALS['linki'], $dd_behformat[$i]);
				$dd_behdir[$i] = mysqli_real_escape_string($GLOBALS['linki'], $dd_behdir[$i]);
				$dd_useseriesdirs[$i] = mysqli_real_escape_string($GLOBALS['linki'], $dd_useseriesdirs[$i]) + 0;
				$dd_optional[$i] = mysqli_real_escape_string($GLOBALS['linki'], $dd_optional[$i]) + 0;
				$dd_preserveseries[$i] = mysqli_real_escape_string($GLOBALS['linki'], $dd_preserveseries[$i]) + 0;
				$dd_usephasedir[$i] = mysqli_real_escape_string($GLOBALS['linki'], $dd_usephasedir[$i]) + 0;
				
				$sqlstring = "insert into pipeline_data_def (pipeline_id, pipeline_version, pdd_order, pdd_seriescriteria, pdd_protocol, pdd_modality, pdd_dataformat, pdd_imagetype, pdd_gzip, pdd_location, pdd_useseries, pdd_preserveseries, pdd_usephasedir, pdd_behformat, pdd_behdir, pdd_enabled, pdd_optional, pdd_numboldreps, pdd_level, pdd_assoctype) values ($id, $newversion, '$dd_order[$i]', '$dd_seriescriteria[$i]', '$dd_protocol[$i]', '$dd_modality[$i]', '$dd_dataformat[$i]', '$dd_imagetype[$i]', '$dd_gzip[$i]', '$dd_location[$i]', '$dd_useseriesdirs[$i]', '$dd_preserveseries[$i]', '$dd_usephasedir[$i]', '$dd_behformat[$i]', '$dd_behdir[$i]', $dd_enabled[$i], '$dd_optional[$i]', '$dd_numboldreps[$i]', '$dd_datalevel[$i]', '$dd_studyassoc[$i]')";
				//PrintSQL($sqlstring);
				echo "<li>Inserted data definition [$dd_protocol[$i]]";
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			}
		}
		
		/* update pipeline with new version */
		$sqlstring = "update pipelines set pipeline_version = $newversion where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* ------ all done ------ */
		$sqlstring = "commit";
		//PrintSQL("$sqlstring");
		echo "<li><b>Commit the transaction</b>";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		?></ol></span><div align="center"><span class="message">Data specification [<?=$id?>] updated</span></div><?
	}


	/* -------------------------------------------- */
	/* ------- AddPipeline ------------------------ */
	/* -------------------------------------------- */
	function AddPipeline($pipelinetitle, $pipelinedesc, $pipelinegroup, $pipelinenumproc, $pipelineclustertype, $pipelineclusteruser, $pipelinesubmithost, $pipelinemaxwalltime, $pipelinesubmitdelay, $pipelinedatacopymethod, $pipelinequeue, $pipelineremovedata, $pipelinedirectory, $pipelinedirstructure, $pipelineusetmpdir, $pipelinetmpdir, $pipelinenotes, $username, $completefiles, $dependency, $deplevel, $depdir, $deplinktype, $groupid, $level, $groupbysubject) {
		/* perform data checks */
		$pipelinetitle = mysqli_real_escape_string($GLOBALS['linki'], trim($pipelinetitle));
		$pipelinedesc = mysqli_real_escape_string($GLOBALS['linki'], trim($pipelinedesc));
		$pipelinegroup = mysqli_real_escape_string($GLOBALS['linki'], trim($pipelinegroup));
		$pipelinenumproc = mysqli_real_escape_string($GLOBALS['linki'], trim($pipelinenumproc));
		$pipelineclustertype = mysqli_real_escape_string($GLOBALS['linki'], trim($pipelineclustertype));
		$pipelineclusteruser = mysqli_real_escape_string($GLOBALS['linki'], trim($pipelineclusteruser));
		$pipelinesubmithost = mysqli_real_escape_string($GLOBALS['linki'], trim($pipelinesubmithost));
		$pipelinemaxwalltime = mysqli_real_escape_string($GLOBALS['linki'], trim($pipelinemaxwalltime));
		$pipelinesubmitdelay = mysqli_real_escape_string($GLOBALS['linki'], trim($pipelinesubmitdelay));
		$pipelinedatacopymethod = mysqli_real_escape_string($GLOBALS['linki'], trim($pipelinedatacopymethod));
		$pipelinequeue = mysqli_real_escape_string($GLOBALS['linki'], trim($pipelinequeue));
		$pipelineremovedata = mysqli_real_escape_string($GLOBALS['linki'], trim($pipelineremovedata));
		$pipelineresultsscript = mysqli_real_escape_string($GLOBALS['linki'], trim($pipelineresultsscript));
		$pipelinedirectory = mysqli_real_escape_string($GLOBALS['linki'], trim($pipelinedirectory));
		$pipelinedirstructure = mysqli_real_escape_string($GLOBALS['linki'], trim($pipelinedirstructure));
		$pipelineusetmpdir = mysqli_real_escape_string($GLOBALS['linki'], trim($pipelineusetmpdir));
		$pipelinetmpdir = mysqli_real_escape_string($GLOBALS['linki'], trim($pipelinetmpdir));
		$pipelinenotes = mysqli_real_escape_string($GLOBALS['linki'], trim($pipelinenotes));
		$completefiles = mysqli_real_escape_string($GLOBALS['linki'], trim($completefiles));
		$groupbysubject = mysqli_real_escape_string($GLOBALS['linki'], $groupbysubject) + 0;
		$deplevel = mysqli_real_escape_string($GLOBALS['linki'], trim($deplevel));
		$depdir = mysqli_real_escape_string($GLOBALS['linki'], trim($depdir));
		$deplinktype = mysqli_real_escape_string($GLOBALS['linki'], trim($deplinktype));
		if (is_array($dependency)) {
			$dependencies = implode(",",$dependency);
		}
		if (is_array($groupid)) {
			$groupids = implode2(",",$groupid);
		}
		
		if (!ctype_alnum($pipelinetitle)) {
			?><div align="center"><span class="staticmessage">Error creating pipeline. Pipeline name can only contain numbers and letters, no spaces or special characters</span></div><?
		}
		
		if ($pipelinemaxwalltime == "") $pipelinemaxwalltime = "null";
		if ($pipelinesubmitdelay == "") $pipelinesubmitdelay = "null";
		if ($pipelineremovedata == "") $pipelineremovedata = "null";
		if ($pipelineusetmpdir == "") $pipelineusetmpdir = "null";
		
		/* get userid */
		$sqlstring = "select user_id from users where username = '$username'";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$userid = $row['user_id'];
		
		/* insert the new form */
		$sqlstring = "insert into pipelines (pipeline_name, pipeline_desc, pipeline_group, pipeline_admin, pipeline_createdate, pipeline_status, pipeline_numproc, pipeline_submithost, pipeline_maxwalltime, pipeline_submitdelay, pipeline_datacopymethod, pipeline_queue, pipeline_clustertype, pipeline_clusteruser, pipeline_removedata, pipeline_resultsscript, pipeline_completefiles, pipeline_dependency, pipeline_dependencylevel, pipeline_dependencydir, pipeline_deplinktype, pipeline_groupid, pipeline_level, pipeline_directory, pipeline_dirstructure, pipeline_usetmpdir, pipeline_tmpdir, pipeline_notes, pipeline_ishidden, pipeline_groupbysubject) values ('$pipelinetitle', '$pipelinedesc', '$pipelinegroup', '$userid', now(), 'stopped', '$pipelinenumproc', '$pipelinesubmithost', $pipelinemaxwalltime, $pipelinesubmitdelay, '$pipelinedatacopymethod', '$pipelinequeue', '$pipelineclustertype', '$pipelineclusteruser', $pipelineremovedata, '$pipelineresultsscript', '$completefiles', '$dependencies', '$deplevel', '$depdir', '$deplinktype', '$groupids', '$level', '$pipelinedirectory', '$pipelinedirstructure', $pipelineusetmpdir, '$pipelinetmpdir', '$pipelinenotes', 0, $groupbysubject)";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$pipelineid = mysqli_insert_id($GLOBALS['linki']);
		
		?><div align="center"><span class="message"><?=$formtitle?> added</span></div><?
		
		return $pipelineid;
	}

	
	/* -------------------------------------------- */
	/* ------- CopyPipeline ----------------------- */
	/* -------------------------------------------- */
	function CopyPipeline($id, $newname) {
		
		if (!ValidID($id,'Pipeline ID - B')) { return; }

		$newname = mysqli_real_escape_string($GLOBALS['linki'], trim($newname));

		if ($newname == "") {
			echo "New pipeline name is blank. Please fix and try again";
			return;
		}
		
		//if (!ctype_alnum($newname)) {
		//	echo "New pipeline name contains non-alphanumeric characters. Please fix and try again";
		//	return;
		//}
		
		// Validate alphanumeric
		if (preg_match('/[^a-z_\-0-9]/i', $newname)) {
			echo "New pipeline name contains non-alphanumeric characters. Please fix and try again";
			return;
		}
		
		/* check if the new pipeline name already exists */
		$sqlstring = "select * from pipelines where pipeline_name = '$newname'";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if (mysqli_num_rows($result) > 0) {
			echo "New pipeline name already exists";
			return;
		}
		
		?>
		<span class="tiny">
		<ol>
		<?
		
		/* this process below of copying a row is cumbersome...
		   ...BUT there is no need to change the column definitions in this code to reflect future table changes */
		
		$history = "";
		
		$sqlstring = "start transaction";
		//PrintSQL("$sqlstring");
		echo "<li><b>Starting transaction</b> [$sqlstring]\n";
		$history .= "1) Starting transaction [$sqlstring]\n";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);

		/* ------ copy the pipeline definition ------ */
		/* create a temp table, which automatically creates the columns */
		$sqlstring = "create temporary table tmp_pipeline$id select * from pipelines where pipeline_id = $id";
		//PrintSQL("$sqlstring");
		echo "<li>Creating temp table from existing pipeline table spec [$sqlstring]\n";
		$history .= "2) Creating temp table from existing pipeline table spec [$sqlstring]\n";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* for DEBUG, display the original table */
		$sqlstring = "select * from pipelines where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$history .= "Original TABLE [pipelines]\n" . PrintSQLTable($result,"","","",true) . "\n\n";

		/* for DEBUG, display the copied temp table */
		$sqlstring = "select * from tmp_pipeline$id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$history .= "Temp TABLE [tmp_pipeline$id]\n" . PrintSQLTable($result,"","","",true) . "\n\n";
		
		/* get the new pipeline id */
		$sqlstring = "select (max(pipeline_id)+1) 'newid' from pipelines";
		//PrintSQL("$sqlstring");
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$newid = $row['newid'];
		echo "<li>Getting new pipeline ID [$newid] [$sqlstring]\n";
		$history .= "3) Getting new pipeline ID [$newid] [$sqlstring]\n";

		/* this new pipeline_id does not exist... we know that. But the ID may still be in the pipeline_steps table
		   so delete everything from the pipeline_steps table with the new ID */
		$sqlstring = "delete from pipeline_steps where pipeline_id = $newid";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		echo "<li>Deleting from pipeline_steps table [$sqlstring]\n";
		$history .= "3.1) Deleting from pipeline_steps table [$sqlstring]\n";

		$sqlstring = "select pipeline_version from pipelines where pipeline_id = $id";
		//PrintSQL("$sqlstring");
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$version = $row['pipeline_version'];
		echo "<li>Getting pipeline version [$version] [$sqlstring]\n";
		$history .= "4) Getting pipeline version [$version] [$sqlstring]\n";

		/* make any changes to the new pipeline before inserting */
		$sqlstring = "update tmp_pipeline$id set pipeline_id = $newid, pipeline_name = '$newname', pipeline_version = 1, pipeline_createdate = now(), pipeline_status = 'stopped', pipeline_statusmessage = '', pipeline_laststart = null, pipeline_lastfinish = null, pipeline_enabled = 0, pipeline_admin = (select user_id from users where username = '" . $_SESSION['username'] . "')";
		echo "<li>Making changes to new pipeline in temp table [$sqlstring]\n";
		$history .= "5) Making changes to new pipeline in temp table [$sqlstring]\n";
		//PrintSQL("$sqlstring");
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* insert the changed row into the pipeline table */
		$sqlstring = "insert into pipelines select * from tmp_pipeline$id";
		//PrintSQL("$sqlstring");
		echo "<li>Getting new pipeline ID [$sqlstring]\n";
		$history .= "6) Getting new pipeline ID [$sqlstring]\n";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* delete the tmp table */
		$sqlstring = "drop table tmp_pipeline$id";
		//PrintSQL("$sqlstring");
		echo "<li>Deleting temp table [$sqlstring]\n";
		$history .= "7) Deleting temp table [$sqlstring]\n";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* ------ copy the data specification ------ */
		/* create a temp table, which automatically creates the columns */
		$sqlstring = "create temporary table tmp_dataspec$id (select * from pipeline_data_def where pipeline_id = $id and pipeline_version = $version)";
		//PrintSQL("$sqlstring");
		echo "<li>Create temp table from existing pipeline_data_def spec [$sqlstring]\n";
		$history .= "8) Create temp table from existing pipeline_data_def spec [$sqlstring]\n";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* for DEBUG, display the original table */
		$sqlstring = "select * from pipeline_data_def where pipeline_id = $id and pipeline_version = $version";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$history .= "Original TABLE [pipeline_data_def] \n" . PrintSQLTable($result,"","","",true) . "\n\n";

		/* for DEBUG, display the copied temp table */
		$sqlstring = "select * from tmp_dataspec$id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$history .= "Temp TABLE [tmp_dataspec$id] \n" . PrintSQLTable($result,"","","",true) . "\n\n";
		
		/* make any changes to the new pipeline before inserting */
		$sqlstring = "update tmp_dataspec$id set pipeline_id = $newid, pipeline_version = 1, pipelinedatadef_id = null";
		//PrintSQL("$sqlstring");
		echo "<li>Make changes to temp table [$sqlstring]\n";
		$history .= "9) Make changes to temp table [$sqlstring]\n";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* insert the changed rows into the pipeline_data_def table */
		$sqlstring = "insert into pipeline_data_def select * from tmp_dataspec$id";
		//PrintSQL("$sqlstring");
		echo "<li>Insert temp table rows into pipeline_data_def [$sqlstring]\n";
		$history .= "10) Insert temp table rows into pipeline_data_def [$sqlstring]\n";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* delete the tmp table */
		$sqlstring = "drop table tmp_dataspec$id";
		//PrintSQL("$sqlstring");
		echo "<li>Drop temp table [$sqlstring]\n";
		$history .= "11) Drop temp table [$sqlstring]\n";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* ------ copy the pipeline steps specification ------ */
		/* create a temp table, which automatically creates the columns */
		$sqlstring = "create temporary table tmp_steps$id (select * from pipeline_steps where pipeline_id = $id and pipeline_version = $version)";
		//PrintSQL("$sqlstring");
		echo "<li>Create temp table from pipeline_steps spec [$sqlstring]\n";
		$history .= "12) Create temp table from pipeline_steps spec [$sqlstring]\n";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);

		/* for DEBUG, display the original table */
		$sqlstring = "select * from pipeline_steps where pipeline_id = $id and pipeline_version = $version";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$history .= "Original TABLE [pipeline_steps] \n" . PrintSQLTable($result,"","","",true) . "\n\n";

		/* for DEBUG, display the copied temp table */
		$sqlstring = "select * from tmp_steps$id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$history .= "Temp TABLE [tmp_steps$id] \n" . PrintSQLTable($result,"","","",true) . "\n\n";
		
		/* make any changes to the new pipeline before inserting */
		$sqlstring = "update tmp_steps$id set pipeline_id = $newid, pipeline_version = 1, pipelinestep_id = ''";
		//PrintSQL("$sqlstring");
		echo "<li>Make changes to temp table [$sqlstring]\n";
		$history .= "13) Make changes to temp table [$sqlstring]\n";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* insert the changed rows into the pipeline_data_def table */
		$sqlstring = "insert into pipeline_steps select * from tmp_steps$id";
		//PrintSQL("$sqlstring");
		echo "<li>Insert temp rows into pipeline_steps table [$sqlstring]\n";
		$history .= "14) Insert temp rows into pipeline_steps table [$sqlstring]\n";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* delete the tmp table */
		$sqlstring = "drop table tmp_steps$id";
		//PrintSQL("$sqlstring");
		echo "<li>Drop temp table [$sqlstring]\n";
		$history .= "15) Drop temp table [$sqlstring]\n";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* copy any dependencies */
		$sqlstring = "select * from pipeline_dependencies where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$parentid = $row['parent_id'];
			$sqlstringA = "insert ignore into pipeline_dependencies (pipeline_id, parent_id) values ($newid,'$parentid')";
			echo "<li>Copy dependency [$sqlstringA]\n";
			$history .= "16) Copy dependency [$sqlstringA]\n";
			//PrintSQL($sqlstring);
			$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
		}
		
		/* ------ all done ------ */
		$sqlstring = "commit";
		//PrintSQL("$sqlstring");
		echo "<li><b>Commit the transaction</b> [$sqlstring]\n";
		$history .= "17) Commit the transaction [$sqlstring]\n";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		?>
		</ol>
		<?
		
		//$sqlstring = "select * from pipelines where pipeline_id = $newid";
		//$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		//$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		//echo "AFTER COPY (new pipeline)<br>\n";
		//PrintVariable($row);
		
		echo "DEBUG - ignore this stuff<br>";
		PrintVariable($history);
		
		$history = mysqli_real_escape_string($GLOBALS['linki'], trim($history));
		$sqlstring = "insert into changelog (performing_userid, change_datetime, change_event, change_desc) values (" . $GLOBALS['userid'] . ", now(), 'pipelinecopy', '$history')";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
	}


	/* -------------------------------------------- */
	/* ------- ChangeOwner ------------------------ */
	/* -------------------------------------------- */
	function ChangeOwner($id, $newuserid) {
		if (!ValidID($id,'Pipeline ID - D')) { return; }
		if (!ValidID($newuserid,'New userID')) { return; }
		
		/* update owner id */
		$sqlstring = "update pipelines set pipeline_admin = $newuserid where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		?><div align="center"><span class="message">Owner of pipeline [<?=$id?>] updated</span></div><?
	}


	/* -------------------------------------------- */
	/* ------- DeletePipeline --------------------- */
	/* -------------------------------------------- */
	function DeletePipeline($id) {
		if (!ValidID($id,'Pipeline ID - E')) { return; }
		
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
	/* ------- ResetAnalyses ---------------------- */
	/* -------------------------------------------- */
	function ResetAnalyses($id) {
		if (!ValidID($id,'Pipeline ID - F')) { return; }
		
		$sqlstring = "delete from analysis_data where analysis_id in (select analysis_id from analysis where pipeline_id = $id and analysis_status in ('NoMatchingStudies', 'NoMatchingSeries'))";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		?><div align="center"><span class="message">Reset analyses: <?echo mysqli_affected_rows(); ?> analysis <b>data</b> rows deleted</span></div><?
	
		$sqlstring = "delete from analysis where analysis_status in ('NoMatchingStudies', 'NoMatchingSeries') and pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		?><div align="center"><span class="message">Reset analyses: <?echo mysqli_affected_rows(); ?> analysis rows deleted</span></div><?
	}	


	/* -------------------------------------------- */
	/* ------- ResetPipeline ---------------------- */
	/* -------------------------------------------- */
	function ResetPipeline($id) {
		if (!ValidID($id,'Pipeline ID - G')) { return; }
		
		$sqlstring = "update pipelines set pipeline_status = 'stopped' where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
	}	
	
	
	/* -------------------------------------------- */
	/* ------- EnablePipeline --------------------- */
	/* -------------------------------------------- */
	function EnablePipeline($id) {
		if (!ValidID($id,'Pipeline ID - H')) { return; }
		
		$sqlstring = "update pipelines set pipeline_enabled = 1 where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
	}


	/* -------------------------------------------- */
	/* ------- DisablePipeline -------------------- */
	/* -------------------------------------------- */
	function DisablePipeline($id) {
		if (!ValidID($id,'Pipeline ID - I')) { return; }
		
		$sqlstring = "update pipelines set pipeline_enabled = 0 where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayPipelineForm ---------------- */
	/* -------------------------------------------- */
	function DisplayPipelineForm($type, $id) {
		if ($type != "add") { 
			if (!ValidID($id,'Pipeline ID - L')) { return; }
		}
	
		$level = 0;
		/* populate the fields if this is an edit */
		if ($type == "edit") {
			$sqlstring = "select a.*, b.username from pipelines a left join users b on a.pipeline_admin = b.user_id where a.pipeline_id = $id";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$title = $row['pipeline_name'];
			$pipeline_status = $row['pipeline_status'];
			$pipeline_statusmessage = $row['pipeline_statusmessage'];
			$pipeline_laststart = $row['pipeline_laststart'];
			$pipeline_lastfinish = $row['pipeline_lastfinish'];
			$pipeline_lastcheck = $row['pipeline_lastcheck'];
			$desc = $row['pipeline_desc'];
			$numproc = $row['pipeline_numproc'];
			$submithost = $row['pipeline_submithost'];
			$maxwalltime = $row['pipeline_maxwalltime'];
			$submitdelay = $row['pipeline_submitdelay'];
			$queue = $row['pipeline_queue'];
			$clustertype = $row['pipeline_clustertype'];
			$clusteruser = $row['pipeline_clusteruser'];
			$datacopymethod = $row['pipeline_datacopymethod'];
			$remove = $row['pipeline_removedata'];
			$version = $row['pipeline_version'];
			$directory = $row['pipeline_directory'];
			$dirstructure = $row['pipeline_dirstructure'];
			$usetmpdir = $row['pipeline_usetmpdir'];
			$tmpdir = $row['pipeline_tmpdir'];
			$pipelinenotes = $row['pipeline_notes'];
			$pipelinegroup = $row['pipeline_group'];
			$resultscript = $row['pipeline_resultsscript'];
			$deplevel = $row['pipeline_dependencylevel'];
			$depdir = $row['pipeline_dependencydir'];
			$deplinktype = $row['pipeline_deplinktype'];
			$completefiles = $row['pipeline_completefiles'];
			$dependency = $row['pipeline_dependency'];
			$groupid = $row['pipeline_groupid'];
			$level = $row['pipeline_level'];
			$owner = $row['username'];
			$ishidden = $row['pipeline_ishidden'];
			$groupbysubject = $row['pipeline_groupbysubject'];
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
			$submitbuttonlabel = "Save Pipeline";
		}
		else {
			$formaction = "add";
			$formtitle = "Add new pipeline";
			$submitbuttonlabel = "Add Pipeline";
			$remove = "0";
			$level = 1;
			//$directory = "/home/" . $GLOBALS['username'];
			$directory = "";
			$readonly = false;
		}
		
		if ($readonly) {
			$disabled = "disabled";
		}
		else {
			$disabled = "";
		}
		
		if ($numproc == "") { $numproc = 1; }
		
		$urllist['Pipelines'] = "pipelines.php";
		$urllist[$title] = "pipelines.php?action=editpipeline&id=$id";
		NavigationBar("", $urllist);
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
	
		<?
			if ($type != "add") {
				DisplayPipelineStatus($title, $isenabled, $id, $pipeline_status, $pipeline_statusmessage, $pipeline_laststart, $pipeline_lastfinish, $pipeline_lastcheck);
			}
		?>
		<br>
		<table width="100%">
			<tr>
				<td style="padding-right:40px" valign="top" width="50%">
					<fieldset style="border: 3px solid #999; border-radius:5px">
					<legend style="background-color: #3B5998; color:white; padding:5px 10px; border-radius:5px"> <b><?=$formtitle?></b> v<?=$version?> Settings</legend>
				
					<table class="entrytable" style="border:0px">
						<form method="post" action="pipelines.php">
						<input type="hidden" name="action" value="<?=$formaction?>">
						<input type="hidden" name="id" value="<?=$id?>">
						<tr>
							<td class="label" valign="top">Title</td>
							<td valign="top">
								<input type="text" name="pipelinetitle" required value="<?=$title?>" maxlength="50" size="60" onKeyPress="return AlphaNumeric(event)" <? if ($type == "edit") { echo "readonly style='background-color: #EEE; border: 1px solid gray; color: #888'"; } ?>>
							</td>
						</tr>
						<tr>
							<td class="label" valign="top">Description</td>
							<td valign="top"><input type="text" <?=$disabled?> name="pipelinedesc" value="<?=$desc?>" size="60"></td>
						</tr>
						<tr>
							<td class="label" valign="top">Stats level</td>
							<td valign="top">
								<input type="radio" name="level" id="level1" value="1" <?=$disabled?> <? if ($level == 1) echo "checked"; ?>>First <span class="tiny">subject level</span><br>
								<input type="radio" name="level" id="level2" value="2" <?=$disabled?> <? if ($level == 2) echo "checked"; ?>>Second <span class="tiny">group level</span><br>
							</td>
						</tr>
						<tr>
							<td class="label" valign="top">Directory <img src="images/help.gif" title="<b>Directory</b><br><br>A directory called <b>Title</b> (same name as this pipeline) will be created inside this directory and will contain all of the analyses for this pipeline.<br><br>If blank, the analyses for this pipeline will be written to the default pipeline directory: <span style='color: #E8FFFF'>[<?=$GLOBALS['cfg']['analysisdirb']?>]</span>"></td>
							<td valign="top">
								<input type="text" name="pipelinedirectory" <?=$disabled?> value="<?=$directory?>" maxlength="255" size="60" <? if ($type == "edit") { echo "readonly style='background-color: #EEE; border: 1px solid gray; color: #888'"; } ?> >
							</td>
						</tr>
						<tr>
							<td class="label" valign="top">Directory structure</td>
							<td valign="top">
								<input type="radio" name="pipelinedirstructure" id="level1" value="a" <?=$disabled?> <? if (($dirstructure == 'a') || ($dirstructure == '')) echo "checked"; ?>><?=$GLOBALS['cfg']['analysisdir']?> <tt style="font-size: 10pt">/S1234ABC/1/ThisPipeline</tt><br>
								<input type="radio" name="pipelinedirstructure" id="level1" value="b" <?=$disabled?> <? if ($dirstructure == 'b') echo "checked"; ?>><?=$GLOBALS['cfg']['analysisdirb']?> <tt style="font-size: 10pt">/ThisPipeline/S1234ABC/1</tt>
							</td>
						</tr>
						<tr>
							<td class="label" valign="top">Pipeline group <img src="images/help.gif" title="<b>Pipeline group</b><br><br>Pipelines can be grouped together using a group name. This is different than a group of subjects or studies"></td>
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
							<td class="label" valign="top">Notes</td>
							<td valign="top"><textarea name="pipelinenotes" <?=$disabled?> rows="8" cols="60"><?=$pipelinenotes?></textarea></td>
						</tr>
						<tr>
							<td class="label" valign="top">Data transfer method <img src="images/help.gif" title="<b>Data transfer method</b><br><br><b>NFS</b> copies via the the <tt>cp</tt> command assumes the filesystem you want to write to is mounted on this server<br><br>Copying via <b>scp</b> uses secure copy and assumes you have a passwordless login setup between this server and the one you are copying to"></td>
							<td valign="top">
								<input type="radio" name="pipelinedatacopymethod" id="datacopymethod1" value="nfs" <?=$disabled?> <? if (($datacopymethod == "nfs") || ($datacopymethod == "")) echo "checked"; ?>>NFS <span class="tiny">default</span><br>
								<input type="radio" name="pipelinedatacopymethod" id="datacopymethod2" value="scp" <?=$disabled?> <? if ($datacopymethod == "scp") echo "checked"; ?>>scp <span class="tiny">requires passwordless ssh</span><br>
							</td>
						</tr>
						<tr class="level1">
							<td class="label" valign="top">Concurrent processes <img src="images/help.gif" title="<b>Concurrent processes</b><br><br>This is the number of concurrent jobs allowed to be submitted to the cluster at a time. This number is separate from the number of slots available in the cluster queue, which specified in the grid engine setup"></td>
							<td valign="top"><input type="number" name="pipelinenumproc" <?=$disabled?> value="<?=$numproc?>" min="1" max="350"></td>
						</tr>
						<tr>
							<datalist id="clustertypelist">
								<option value="sge">
								<option value="slurm">
							</datalist>
							<td class="label" valign="top">Cluster type <img src="images/help.gif" title="<b>Cluster type</b><br><br>SGE (default) or slurm"></td>
							<td valign="top"><input type="text" name="pipelineclustertype" list="clustertypelist" <?=$disabled?> value="<?=$clustertype?>"></td>
						</tr>
						<tr>
							<td class="label" valign="top">Cluster user <img src="images/help.gif" title="<b>Cluster user</b><br><br>The username under which data copying and cluster job submission should be done. This user must already have ssh keys setup for password-less login between this sever and the cluster submit server. If blank, the default username is used (<?=$GLOBALS['cfg']['clusteruser']?>)"></td>
							<td valign="top"><input type="text" name="pipelineclusteruser" <?=$disabled?> value="<?=$clusteruser?>"></td>
						</tr>
						<tr>
							<td class="label" valign="top">Submit hostname <img src="images/help.gif" title="<b>Submit host</b><br><br>The hostname of the cluster node to submit to. This host will also be used for scp copy. If blank, the default submit host is used (<?=$GLOBALS['cfg']['clustersubmithost']?>)"></td>
							<td valign="top"><input type="text" name="pipelinesubmithost" <?=$disabled?> value="<?=$submithost?>"></td>
						</tr>
						<tr>
							<td class="label" valign="top">Max wall time <img src="images/help.gif" title="<b>Max wall time</b><br><br>Maximum wall time each analysis is allowed to run before being terminated"></td>
							<td valign="top"><input type="text" name="pipelinemaxwalltime" <?=$disabled?> value="<?=$maxwalltime?>" size="5" maxlength="7"> <span class="tiny">time in minutes (24 hours = 1440 minutes)</span></td>
						</tr>
						<tr>
							<td class="label" valign="top">Submit delay <img src="images/help.gif" title="<b>Submit delay</b><br><br>Number of hours after the study datetime that the job will be submitted. Default is 6 hours"></td>
							<td valign="top"><input type="text" name="pipelinesubmitdelay" <?=$disabled?> value="<?=$submitdelay?>" size="5" maxlength="7"> <span class="tiny">time in hours</span></td>
						</tr>
						<tr>
							<td class="label" valign="top">Queue name <img src="images/help.gif" title="<b>Queue name</b><br><br>The sun grid (SGE) queue to submit to"></td>
							<td valign="top"><input type="text" name="pipelinequeue" <?=$disabled?> value="<?=$queue?>" required> <span class="tiny">Comma separated list</span></td>
						</tr>
						<tr>
							<td class="label" valign="top">Use temporary directory <img src="images/help.gif" title="<b>Use tmp directory</b><br><br>This option will copy all data into the temporary directory first, process it there, and copy it back to its final location"></td>
							<td valign="top"><input type="checkbox" name="pipelineusetmpdir" <?=$disabled?> value="1" <? if ($usetmpdir == "1") { echo "checked"; } ?>> <input type="text" name="pipelinetmpdir" <?=$disabled?> value="<?=$tmpdir?>" size="56"><br>
							<span class="tiny">Usually <tt>/tmp</tt>. Check with your sysadmin</span></td>
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
					<script>
						function GetNewPipelineName(){
							var newname = prompt("Please enter a name for the new pipeline","<?=$title?>");
							if (newname != null){
							  $("#newname").attr("value", newname);
							  document.copypipeline.submit();
						   }
						}
					</script>
					<form action="pipelines.php" method="post" name="copypipeline">
					<input type="hidden" name="action" value="copy">
					<input type="hidden" name="id" value="<?=$id?>">
					<input type="hidden" name="newname" id="newname" value="<?=$id?>">
					</form>
				<td valign="top">
					<fieldset style="border: 3px solid #999; border-radius:5px">
					<legend style="background-color: #3B5998; color:white; padding:5px 10px; border-radius:5px"> Operations </legend>
					
					
					<table class="entrytable" style="border:0px">
						<tr>
							<td class="label" valign="top">View</td>
							<td valign="top" style="padding-bottom: 10pt">
								<a href="analysis.php?action=viewanalyses&id=<?=$id?>"><b>Analyses</b></a><br>
								<a href="analysis.php?action=viewfailedanalyses&id=<?=$id?>" title="View all imaging studies which did not meet the data criteria, and therefore the pipeline did not attempt to run the analysis">Ignored studies<br>
								<a href="pipelines.php?action=viewversion&id=<?=$id?>">Pipeline versions<br>
								<br>
							</td>
						</tr>
						<tr>
							<td class="label" valign="top">Operations</td>
							<td valign="top" style="padding-bottom: 10pt">
								<a href="pipelines.php?action=resetanalyses&id=<?=$id?>" onclick="return confirm('Are you sure you want to reset the analyses for this pipeline?')" title="This will remove any entries in the database for studies which were not analyzed. If you change your data specification, you will want to reset the analyses. This option does not remove existing analyses, it only removes the flag set for studies that indicates the study has been checked for the specified data"><img src="images/reset16.png"> Reprocess ignored studies</a>
								<br>
								<img src="images/copy16.gif"> <a href="#" onClick="GetNewPipelineName();">Copy to new pipeline...</a>
								<? if (!$readonly) { ?>
								<br><br>
								<form style="margin-left: 23px">
								<input type="hidden" name="action" value="changeowner">
								<input type="hidden" name="id" value="<?=$id?>">
								Change pipeline owner to: <select name="newuserid">
									<option value="">(Select new owner)</option>
									<?
										$sqlstring="select * from users where user_enabled = 1 order by username";
										$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
										while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
											$userid = $row['user_id'];
											$username = $row['username'];
											$userfullname = $row['user_fullname'];
											if ($username != "") {
												if ($userfullname != "") {
													$userfullname = "[$userfullname]";
												}
												?><option value="<?=$userid?>"><?=$username?> <?=$userfullname?></option><?
											}
										}
									?>
								</select>
								<input type="submit" value="Change">
								</form>
								<a href="pipelines.php?action=detach$id=<?=$id?>" onclick="return confirm('Are you sure you want to completely detach this pipeline?')" title="This will completely inactivate the pipeline and remove all analyses from the pipeline control. Since the data will no longer be under pipeline control, all analysis results will be deleted. All analysis data will be moved to the directory you specify"><img src="images/disconnect16.png"> Detach entire pipeline</a><br>
								<a href="pipelines.php?action=delete&id=<?=$id?>" onclick="return confirm('Are you sure you want to delete this pipeline?')"><img src="images/delete16.png"> Delete this pipeline</a>
								<? } ?>
							</td>
						</tr>
						<tr>
							<td class="label" valign="top">Analysis statistics</td>
							<td valign="top" style="padding-bottom: 10pt">
							<?
								/* gather statistics about the analyses */
								$sqlstring = "select sum(timestampdiff(second, analysis_clusterstartdate, analysis_clusterenddate)) 'cluster_time' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and analysis_status = 'complete'";
								$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
								$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
								$totaltime = $row['cluster_time'];
								$totaltime = number_format(($totaltime/60/60),2);
								
								$sqlstring = "select sum(timestampdiff(second, analysis_clusterstartdate, analysis_clusterenddate)) 'cluster_timesuccess' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and analysis_status = 'complete' and analysis_iscomplete = 1";
								$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
								$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
								$totaltimesuccess = $row['cluster_timesuccess'];
								$totaltimesuccess = number_format(($totaltimesuccess/60/60),2);
								
								$sqlstring = "select count(*) 'numcomplete' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and analysis_status = 'complete'";
								$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
								$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
								$numcomplete = $row['numcomplete'];

								$sqlstring = "select count(*) 'numcompletesuccess' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and analysis_status = 'complete' and analysis_iscomplete = 1";
								$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
								$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
								$numcompletesuccess = $row['numcompletesuccess'];
								
								$sqlstring = "select count(*) 'numprocessing' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and analysis_status = 'processing'";
								$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
								$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
								$numprocessing = $row['numprocessing'];
								
								$sqlstring = "select count(*) 'numpending' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and analysis_status = 'pending'";
								$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
								$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
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
							<table>
								<tr>
									<td style="font-weight: bold; font-size: 9pt; text-align: right">Finished processing<br><span style="font-weight: normal">Total CPU time</span></td>
									<td style="font-size: 9pt"><a href="analysis.php?action=viewanalyses&id=<?=$id?>"><?=$numcomplete?></a><br><?=$totaltime?> hours</td>
								</tr>
								<tr>
									<td style="font-weight: bold; font-size: 9pt; text-align: right">Completed successfuly<br><span style="font-weight: normal">Total CPU time</span></td>
									<td style="font-size: 9pt"><a href="analysis.php?action=viewanalyses&id=<?=$id?>"><?=$numcompletesuccess?></a><br><?=$totaltimesuccess?> hours</td>
								</tr>
								<tr>
									<td style="font-weight: bold; font-size: 9pt; text-align: right">Currently processing</td>
									<td style="font-size: 9pt"><a href="analysis.php?action=viewanalyses&id=<?=$id?>"><?=$numprocessing?></a></td>
								</tr>
								<tr>
									<td style="font-weight: bold; font-size: 9pt; text-align: right">Pending<br><span class="tiny">analyses yet to be submitted</span></td>
									<td style="font-size: 9pt"><a href="analysis.php?action=viewanalyses&id=<?=$id?>"><?=$numpending?></a></td>
								</tr>
								</tr>
									<td style="font-weight: bold; font-size: 9pt; text-align: right">Setup Time</td>
									<td style="font-size: 9pt"><?=number_format(min($analysistimes),1)?> - <?=number_format(max($analysistimes),1)?> seconds
									<br>Mean: <?=number_format(mean($analysistimes),1)?> seconds</td>
								</tr>
								<tr>
									<td style="font-weight: bold; font-size: 9pt; text-align: right">Cluster Time</td>
									<td style="font-size: 9pt"><?=number_format(min($clustertimes)/60/60,2)?> - <?=number_format(max($clustertimes)/60/60,2)?> hours
									<br>Mean: <?=number_format(mean($clustertimes)/60/60,2)?> hours</td>
								</tr>
								<tr>
									<td colspan="2">
										<!-- display performance by hostname -->
										<details>
											<summary style="color: #3B5998"> Computing Performance </summary>
											<table class="smallgraydisplaytable">
												<tr>
													<th colspan="3">Computing performance<br><span class="tiny">Successful analyses only</span></th>
												</tr>
												<tr>
													<td><b>Hostname</b></td>
													<td><b>Avg CPU</b></td>
													<td><b>Count</b></td>
												</tr>
											<?
												$sqlstring = "SELECT avg(timestampdiff(second, analysis_clusterstartdate, analysis_clusterenddate)) 'avgcpu', count(analysis_hostname) 'count', analysis_hostname FROM `analysis` WHERE pipeline_id = $id and (analysis_iscomplete = 1 or analysis_status = 'complete') group by analysis_hostname order by analysis_hostname";
												$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
												while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
													$cpuhrs = number_format(($row['avgcpu']/60/60),2);
													$count = $row['count'];
													$hostname = $row['analysis_hostname'];
													?>
													<tr>
														<td><?=$hostname?></td>
														<td><?=$cpuhrs?> hrs</td>
														<td><?=$count?></td>
													</tr>
													<?
												}
											?>
											</table>
										</details>
									</td>
								</tr>
							</table>
							</td>
						</tr>
						<tr>
							<td class="label" valign="top">Dependency</td>
							<td valign="top" style="padding-bottom: 10pt">
							<?
								if ($dependency != "") {
									$sqlstring = "select pipeline_name, pipeline_id, pipeline_desc, pipeline_notes from pipelines where pipeline_id in ($dependency)";
									$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
									while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
										$parentid = $row['pipeline_id'];
										$parents[$parentid]['name'] = $row['pipeline_name'];
										$parents[$parentid]['desc'] = $row['pipeline_desc'];
										$parents[$parentid]['notes'] = $row['pipeline_notes'];
									}
								}
								$sqlstring = "select pipeline_name, pipeline_id, pipeline_desc, pipeline_notes from pipelines where pipeline_id in (select pipeline_id from pipeline_dependencies where parent_id = '$id')";
								//PrintSQL($sqlstring);
								$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
								while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
									$childid = $row['pipeline_id'];
									$children[$childid]['name'] = $row['pipeline_name'];
									$children[$childid]['desc'] = $row['pipeline_desc'];
									$children[$childid]['notes'] = $row['pipeline_notes'];
								}
							?>
							<table style="border: 1px solid #ddd; border-radius:6px; font-size:10pt" cellspacing="0" cellpadding="5">
								<tr>
									<td align="center">
										<table style="font-size:10pt">
											<tr>
											<?
												if (count($parents) > 0) {
													foreach ($parents as $parentid => $info) {
														?><td align="center" style="padding: 2px 10px"><a href="pipelines.php?action=editpipeline&id=<?=$parentid?>" title="<?=$info['desc']?><br><br><?=$info['notes']?>"><?=$info['name']?></a><br>&darr;</td><?
													}
												}
												else {
													?><td align="center">This pipeline does not depend on any other pipelines<br>&darr;</td><?
												}
											?>
											</tr>
										</table>
									</td>
								</tr>
								<tr>
									<td align='center' style="font-weight: bold; font-size: 14pt; background-color:#eee" title="This pipeline"><?=$title?></td>
								</tr>
								<tr>
									<td align="center">
										<table style="font-size:10pt">
											<tr>
											<?
												if (count($children) > 0) {
													foreach ($children as $child => $info) {
														?>
														<tr>
															<td align="left" style="padding: 2px 10px">
															&rdsh; <a href="pipelines.php?action=editpipeline&id=<?=$child?>" title="<?=$info['desc']?><br><br><?=$info['notes']?>"><?=$info['name']?></a>
															</td>
														</tr>
														<?
													}
												}
												else {
													?><td align="center">&darr;<br>No pipelines depend on this pipeline</td><?
												}
											?>
											</tr>
										</table>
									</td>
								</tr>
							</table>
							</td>
						</tr>
						<tr>
							<td class="label" valign="top">Data location</td>
							<td valign="top" style="padding-bottom: 10pt">
								<span style="background-color: #ddd; padding:5px; font-family: monospace; border-radius:3px">
								<?
									if ($directory != "") {
										echo $directory;
									} else {
										if ($dirstructure == "b") {
											echo $GLOBALS['cfg']['analysisdirb'];
										}
										else {
											echo $GLOBALS['cfg']['analysisdir'];
										}
									}
								?>/<i>UID</i>/<i>StudyNum</i>/<?=$title?>
								</span>
							</td>
						</tr>
						<tr>
							<td class="label" valign="top">Help</td>
							<td valign="top" style="padding-bottom: 10pt">
							<details style="font-size:10pt">
								<summary style="color: #3B5998"> Pipeline not working? </summary>
								There are several things that can cause the pipeline not to (or appear not to) process your data
								<ol>
									<li><b>Data specification</b> - The most common problem is that the data specification is not quite right.
										<ul>
											<li>The protocol names can vary over time. For example "Resting State" becomes "Rest - noeyes" halfway through a project. You'll need to include both possible protocol names.
											<li>Check the "Image type". For MR, this can also vary over time.
											<li>Make sure the data items are enabled and at least one item is not optional
											<li>Make sure at least one data item is at the study level
											<li>If you are getting data from the subject level, check the subject linkage... for example, if you are working on fMRI data, and the T1 comes from another study, make sure you use the correct linkage
											<li>Check the criteria for the data. To specify the number of BOLD reps, the criteria must be set to "Use size criteria below"
										</ul>
									<li><b>Groups</b> - If you select a group, only the studies in that group will be checked if they match the pipeline's data criteria
									<li><b>Dependencies</b> - If you use dependencies, the study being processed in this pipeline must have already been processed <i>successfully</i> in the parent pipeline. Check the <a href="analysis.php?action=viewfailedanalyses&id=<?=$id?>">ignored studies</a> to see if any have been ignored because of a missing dependency. To retry those studies, click the "Reprocess ignored studies" link.
									<li><b>Pipeline state</b> - When the pipeline is enabled, there is a background process that launches every few minutes to check to see which pipelines need to be run. Once your pipeline is running, it will have a status of "running". Otherwise the status will be "stopped". While running, the pipeline is doing two things: 1) checking what studies need to run, and 2) submitting those that need to run. Once all of the studies have been submitted, the pipeline will be "stopped". Cluster jobs may still be running even though the status is "stopped".
									<li><b>Pipeline script</b> - If there are any errors in the pipeline script, even minor things like trying to cd into a non-existent directory will stop the cluster job entirely and put it in an error state. Currently there is no indicator that has happened on the pipeline web page. Check the individual analysis logs to see what's up
									<li><b>Pipeline manager has died</b> - In very rare circumstances, the background manager that was handling your pipeline may die. If that happens, your pipeline's status may be stuck on "running" for a couple days, even though you know it hasn't actually done anything. You can click the "reset" next to the pipeline status.
								</ol>
								The first step for pipeline processing is getting the data. This involves checking the data criteria, dependencies, and groups to find which subjects have the data required for the analysis. 
							</details>
							</td>
						</tr>
					</table>

					<!-- <a href="pipelines.php?action=viewversion&id=<?=$id?>"><img src="images/printer16.png" border="0"> View previous versions</a><br> -->
					</fieldset>
				</td>
					<?
				}
			?>
			</tr>
		</table>

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
			<legend style="background-color: #3B5998; color:white; padding:5px 10px; border-radius:5px"> Pipeline Options </legend>
		<form method="post" action="pipelines.php" name="stepsform" id="stepsform">
		<input type="hidden" name="action" value="updatepipelineoptions">
		<input type="hidden" name="id" value="<?=$id?>">
		<?
			if (($level == 1) || (($level == 2) && ($dependency == ''))) {
		?>
		<br>
		<style>
			td.dataheader { padding: 5px; border-bottom: 2px solid #999; background-color: #eee; text-align: center }
		</style>
		
		<div style="text-align:left; font-size:12pt; font-weight: bold; color:#214282;" class="level1">Options</div>
			<table class="entrytable">
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
					</td>
					<td valign="top">
						<table class="entrytable">
							<tr>
								<td valign="top" align="right" style="font-size:10pt; font-weight:bold;color: #555;">This pipeline depends on<br><span class="tiny">(it is a child pipeline of...)</span></td>
								<td valign="top">
									<select name="dependency[]" <?=$disabled?> multiple="multiple" size="7">
										<option value="">(No dependency)</option>
										<?
											$sqlstring = "select * from pipelines order by pipeline_name";
											$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
											while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
												$d_name = $row['pipeline_name'];
												$d_id = $row['pipeline_id'];
												$d_ver = $row['pipeline_version'];
												
												if (($d_name != "") && ($d_id != "")) {
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
											}
										?>
									</select><br>
									<span class="tiny">ctrl+click to select multiple</span>
								</td>
							</tr>
							<tr>
								<td valign="top" align="right" style="font-size:10pt; font-weight:bold;color: #555;">Criteria</td>
								<td valign="top">
									<input type="radio" name="deplevel" value="study" <?=$disabled?> <? if (($deplevel == "study") || ($deplevel == "")) { echo "checked"; } ?>> study <span class="tiny">use dependencies from same study (RECOMMENDED)</span><br>
									<input type="radio" name="deplevel" value="subject" <?=$disabled?> <? if ($deplevel == "subject") { echo "checked"; } ?>> subject <span class="tiny">use dependencies from same subject (other studies)</span>
									<!--
									<input type="radio" name="deplevel" value="studyfirst" <?=$disabled?> <? if ($deplevel == "subject") { echo "checked"; } ?>> subject <span class="tiny">use dependencies from same subject (other studies)</span>-->
								</td>
							</tr>
							<tr>
								<td valign="top" align="right" style="font-size:10pt; font-weight:bold;color: #555;">Directory</td>
								<td valign="top">
									<input type="radio" name="depdir" value="root" <?=$disabled?> <? if (($depdir == "root") || ($depdir == "")) { echo "checked"; } ?>> root directory <img src="images/help.gif" title="copies all files into the analysis root directory <code>{analysisrootdir}/*</code>"><br>
									<input type="radio" name="depdir" value="subdir" <?=$disabled?> <? if ($depdir == "subdir") { echo "checked"; } ?>> sub-directory <img src="images/help.gif" title="copies dependency into a subdirectory of the analysis <code>{analysisrootdir}/<i>DependencyName</i>/*</code>">
								</td>
							</tr>
							<tr>
								<td valign="top" align="right" style="font-size:10pt; font-weight:bold;color: #555;">File linking type</td>
								<td valign="top">
									<input type="radio" name="deplinktype" value="hardlink" <?=$disabled?> <? if (($deplinktype == "hardlink") || ($deplinktype == "")) { echo "checked"; } ?>> hard link<br>
									<input type="radio" name="deplinktype" value="softlink" <?=$disabled?> <? if ($deplinktype == "softlink") { echo "checked"; } ?>> soft link<br>
									<input type="radio" name="deplinktype" value="regularcopy" <?=$disabled?> <? if ($deplinktype == "regularcopy") { echo "checked"; } ?>> Regular copy<br>
								</td>
							</tr>
						</table>
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
								$sqlstring = "select * from groups where group_type = 'study' order by group_name";
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
						</select><br>
						<span class="tiny">ctrl+click to select multiple</span>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top">Group by Subject <img src="images/help.gif" title="<b>Group by Subject</b><br><br>Useful for longitudinal analyses. <u>Second level pipelines only</u>"><br>
					<span class="level2" style="color:darkred; font-size:8pt; font-weight:normal"> Second level only</span>
					</td>
					<td valign="top" title="<b>Group by Subject</b><br><br>Useful for longitudinal studies"><input type="checkbox" name="groupbysubject" value="1" <? if ($groupbysubject) { echo "checked"; } ?>></td>
				</tr>
			</table>

		<br>
		<div style="text-align:left; font-size:12pt; font-weight: bold; color:#214282;" class="level1">Data</div>
		<br>
		<table class="level1" cellspacing="0" cellpadding="0">
			<tr style="color:#444; font-size:10pt;">
				<td class="dataheader"><b>Enabled</b></td>
				<td class="dataheader"><b>Optional</b></td>
				<td class="dataheader"><b>Order</b></td>
				<td class="dataheader"><b>Protocol</b></td>
				<td class="dataheader"><b>Modality</b></td>
				<td class="dataheader"><b>Where's the data coming from?</b> <img src="images/help.gif" title="<b>Data Source</b><br>All analyses are run off of the study level. If you want data from this subject, but the data was collected in a different study, select the Subject data level. For example, the subject has been scanned on three different dates, but only one of them has"></td>
				<td class="dataheader"><b>Subject linkage</b> <img src="images/help.gif" title="<b>Data Level</b><br>Only use this option if your data is coming from the subject level"></td>
			</tr>
		<?
		$neworder = 1;
		/* display all other rows, sorted by order */
		$sqlstring = "select * from pipeline_data_def where pipeline_id = $id and pipeline_version = $version order by pdd_order + 0";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$pipelinedatadef_id = $row['pipelinedatadef_id'];
			$dd_order = $row['pdd_order'];
			$dd_seriescriteria = $row['pdd_seriescriteria'];
			$dd_protocol = $row['pdd_protocol'];
			$dd_modality = $row['pdd_modality'];
			$dd_dataformat = $row['pdd_dataformat'];
			$dd_imagetype = $row['pdd_imagetype'];
			$dd_gzip = $row['pdd_gzip'];
			$dd_location = $row['pdd_location'];
			$dd_useseries = $row['pdd_useseries'];
			$dd_preserveseries = $row['pdd_preserveseries'];
			$dd_usephasedir = $row['pdd_usephasedir'];
			$dd_behformat = $row['pdd_behformat'];
			$dd_behdir = $row['pdd_behdir'];
			$dd_numboldreps = $row['pdd_numboldreps'];
			$dd_enabled = $row['pdd_enabled'];
			$dd_assoctype = $row['pdd_assoctype'];
			$dd_optional = $row['pdd_optional'];
			$dd_datalevel = $row['pdd_level'];
			$dd_numimagescriteria = $row['pdd_numimagescriteria'];
			
			//PrintVariable($row);
			?>
			<script>
				$(document).ready(function() {
					$('.row<?=$neworder?>').mouseover(function() {
						$('.row<?=$neworder?>').css('background-color','#eee');
					})
					.mouseout(function() {
						$('.row<?=$neworder?>').css('background-color','');
					});
				});
			</script>
			<style>
				.row1 { background-color: lightyellow; }
			</style>
			<tr class="row<?=$neworder?>">
				<td width="10" valign="top" style="padding: 5px;" align="center">
					<input class="small" type="checkbox" name="dd_enabled[<?=$neworder?>]" value="1" <? if ($dd_enabled) {echo "checked";} ?>>
				</td>
				<td valign="top" style="padding: 5px" align="center">
					<input type="checkbox" name="dd_optional[<?=$neworder?>]" value="1" <? if ($dd_optional) { echo "checked"; } ?>>
				</td>
				<td style="padding: 5px" valign="top">
					<input class="small" type="text" name="dd_order[<?=$neworder?>]" size="2" maxlength="3" value="<?=$neworder?>">
				</td>
				<td valign="top" style="padding: 5px">
					<input class="small" type="text" name="dd_protocol[<?=$neworder?>]" size="50" value='<?=$dd_protocol?>' title='Enter exact protocol name(s). Use quotes if entering a protocol with spaces or entering more than one protocol: "Task1" "Task 2" "Etc". Use multiple protocol names ONLY if you do not expect the protocols to occur in the same study'>
				</td>
				<td id="row<?=$neworder?>" valign="top" style="padding: 5px">
					<select class="small" name="dd_modality[<?=$neworder?>]">
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
								if ($mod_code == $dd_modality) {
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
				</td>
				<td valign="top" style="font-size:8pt; padding: 5px" align="left">
					<select name="dd_datalevel[<?=$neworder?>]">
						<option value="">(select data level)
						<option value="study" <? if (($dd_datalevel == "study") || ($dd_datalevel == "")) { echo "selected"; } ?>>Study
						<option value="subject" <? if ($dd_datalevel == "subject") { echo "selected"; } ?>>Subject
					</select>
				</td>
				<td valign="top" style="font-size:8pt; padding: 5px" align="left">
					<select name="dd_studyassoc[<?=$neworder?>]">
						<option value="">(select study link)
						<option value="nearestintime" <? if (($dd_assoctype == "nearestintime") || ($dd_assoctype == "")) { echo "selected"; } ?>>Nearest in time
						<option value="samestudytype" <? if ($dd_assoctype == "samestudytype") { echo "selected"; } ?>>Same study type
					</select>
				</td>
			</tr>
			<tr class="row<?=$neworder?>">
				<td valign="top" colspan="7" style="padding-left:25px">
					<details class="level1" style="padding:0px;margin:0px">
						<summary style="padding:0px; font-size:9pt">Options</summary>
						<table class="entrytable" style="background-color: #EEE; border-radius:4px; border: 1px solid #999">
							<tr>
								<td class="label">Image type</td>
								<td title="Comma separated list of image types"><input class="small" type="text" name="dd_imagetype[<?=$neworder?>]" size="30" value="<?=$dd_imagetype?>"></td>
							</tr>
							<tr>
								<td class="label">Series criteria <img src="images/help.gif" title="<b>All</b> - All matching series will be downloaded<br><b>First</b> - Only the lowest numbered series will be downloaded<br><b>Last</b> - Only the highest numbered series will be downloaded<br><b>Largest</b> - Only one series with the most number of volumes or slices will be downloaded<br><b>Smallest</b> - Only one series with the least number of volumes or slices will be downloaded"></td>
								<td>
									<select class="small" name="dd_seriescriteria[<?=$neworder?>]">
										<option value="all" <? if ($dd_seriescriteria == "all") { echo "selected"; } ?>>All</option>
										<option value="first" <? if ($dd_seriescriteria == "first") { echo "selected"; } ?>>First</option>
										<option value="last" <? if ($dd_seriescriteria == "last") { echo "selected"; } ?>>Last</option>
										<option value="largestsize" <? if ($dd_seriescriteria == "largestsize") { echo "selected"; } ?>>Largest</option>
										<option value="smallestsize" <? if ($dd_seriescriteria == "smallestsize") { echo "selected"; } ?>>Smallest</option>
										<option value="usesizecriteria" <? if ($dd_seriescriteria == "usesizecriteria") { echo "selected"; } ?>>Use size criteria below</option>
									</select>
								</td>
							</tr>
							<tr>
								<td class="label">Number of BOLD reps <img src="images/help.gif" title="<b>Must be an integer or a criteria:</b><ul><li><i>N</i> (exactly N)<li>> <i>N</i> (greater than)<li>>= <i>N</i> (greater than or equal to)<li>< <i>N</i> (less than)<li><= <i>N</i> (less than or equal to)<li>~ <i>N</i> (not)</ul>"></td>
								<td><input type="text" name="dd_numboldreps[<?=$neworder?>]" value="<?=$dd_numboldreps?>"></td>
							</tr>
							<tr>
								<td colspan="2" style="font-weight:bold; border-top: 1px solid gray">Output Format</td>
							</tr>
							<tr>
								<td class="label">Directory <img src="images/help.gif" title="<b>Tip:</b> choose a directory called 'data/<i>taskname</i>'. If converting data or putting into a new directory structure, this data directory can be used as a staging area and can then be deleted later in your script"><br><span class="tiny">Relative to analysis root</span></td>
								<td><input class="small" type="text" name="dd_location[<?=$neworder?>]" size="30" value="<?=$dd_location?>"></td>
							</tr>
							<tr>
								<td class="label">Data format</td>
								<td>
									<select class="small" name="dd_dataformat[<?=$neworder?>]">
										<option value="native" <? if ($dd_dataformat == "native") { echo "selected"; } ?>>Native</option>
										<option value="dicom" <? if ($dd_dataformat == "dicom") { echo "selected"; } ?>>DICOM</option>
										<option value="nifti3d" <? if ($dd_dataformat == "nifti3d") { echo "selected"; } ?>>Nifti 3D</option>
										<option value="nifti4d" <? if ($dd_dataformat == "nifti4d") { echo "selected"; } ?>>Nifti 4D</option>
										<option value="analyze3d" <? if ($dd_dataformat == "analyze3d") { echo "selected"; } ?>>Analyze 3D</option>
										<option value="analyze4d" <? if ($dd_dataformat == "analyze4d") { echo "selected"; } ?>>Analyze 4D</option>
									</select>
								</td>
							</tr>
							<tr>
								<td class="label">g-zip</td>
								<td><input class="small" type="checkbox" name="dd_gzip[<?=$neworder?>]" value="1" <? if ($dd_gzip) {echo "checked";} ?>></td>
							</tr>
							<tr>
								<td class="label">Use series directories <img src="images/help.gif" title="<b>Tip:</b> If you plan to download multiple series with the same name, you will want to use series directories. This option will place each series into its own directory (data/task/1, data/task/2, etc)"></td>
								<td><input class="small" type="checkbox" name="dd_useseriesdirs[<?=$neworder?>]" value="1" <? if ($dd_useseries) {echo "checked";} ?>></td>
							</tr>
							<tr>
								<td class="label">Preserve series numbers <img src="images/help.gif" title="If data is placed in a series directory, check this box to preserve the original series number. Otherwise the series number directories will be sequential starting at 1, regardless of the orignal series number"></td>
								<td><input class="small" type="checkbox" name="dd_preserveseries[<?=$neworder?>]" value="1" <? if ($dd_preserveseries) {echo "checked";} ?>></td>
							</tr>
							<tr>
								<td class="label">Phase encoding direction <img src="images/help.gif" title="<b>Phase Encoding Direction</b> If selected, it will write the data to a subdirectory corresponding to the acquired phase encoding direction: AP, PA, RL, LR, COL, ROW, unknownPE"></td>
								<td><input class="small" type="checkbox" name="dd_usephasedir[<?=$neworder?>]" value="1" <? if ($dd_usephasedir) {echo "checked";} ?>></td>
							</tr>
							<tr>
								<td class="label">Behavioral data directory format</td>
								<td>
									<select class="small" name="dd_behformat[<?=$neworder?>]">
										<option value="behnone" <? if ($dd_behformat == "behnone") { echo "selected"; } ?>>Don't download behavioral data</option>
										<option value="behroot" <? if ($dd_behformat == "behroot") { echo "selected"; } ?>>Place in root (file.log)</option>
										<option value="behrootdir" <? if ($dd_behformat == "behrootdir") { echo "selected"; } ?>>Place in directory in root (beh/file.log)</option>
										<option value="behseries" <? if ($dd_behformat == "behseries") { echo "selected"; } ?>>Place in series (2/file.log)</option>
										<option value="behseriesdir" <? if ($dd_behformat == "behseriesdir") { echo "selected"; } ?>>Place in directory in series (2/beh/file.log)</option>
									</select>
								</td>
							</tr>
							<tr>
								<td class="label">Behavioral data directory name</td>
								<td><input class="small" type="text" name="dd_behdir[<?=$neworder?>]" value="<?=$dd_behdir?>"></td>
							</tr>
						</table>
						<br>
					</details>
				</td>
			</tr>
			<?
			$neworder++;
		}
		for ($ii=0;$ii<5;$ii++) {
		?>
			<script>
				$(document).ready(function() {
					$('.row<?=$neworder?>').mouseover(function() {
						$('.row<?=$neworder?>').css('background-color','#eee');
					})
					.mouseout(function() {
						$('.row<?=$neworder?>').css('background-color','');
					});
				});
			</script>
			<tr class="row<?=$neworder?>">
				<td width="10" valign="top" style="padding: 5px;" align="center">
					<input class="small" type="checkbox" name="dd_enabled[<?=$neworder?>]" value="1">
				</td>
				<td valign="top" style="padding: 5px" align="center">
					<input type="checkbox" name="dd_optional[<?=$neworder?>]" value="1">
				</td>
				<td style="padding: 5px" valign="top">
					<input class="small" type="text" name="dd_order[<?=$neworder?>]" size="2" maxlength="3" value="<?=$neworder?>">
				</td>
				<td valign="top" style="padding: 5px">
					<input class="small" type="text" name="dd_protocol[<?=$neworder?>]" size="50" title='Enter exact protocol name(s). Use quotes if entering a protocol with spaces or entering more than one protocol: "Task1" "Task 2" "Etc". Use multiple protocol names ONLY if you do not expect the protocols to occur in the same study'>
				</td>
				<td valign="top" style="padding: 5px">
					<select class="small" name="dd_modality[<?=$neworder?>]">
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
								?>
								<option value="<?=$mod_code?>"><?=$mod_code?></option>
								<?
							}
						}
					?>
					</select>
				</td>
				<td valign="top" style="font-size:8pt; padding: 5px" align="left">
					<select name="dd_datalevel[<?=$neworder?>]">
						<option value="">(select data level)
						<option value="study" selected>Study
						<option value="subject">Subject
					</select>
				</td>
				<td valign="top" style="font-size:8pt; padding: 5px" align="left">
					<select name="dd_studyassoc[<?=$neworder?>]">
						<option value="">(select study link)
						<option value="nearestintime" selected>Nearest in time
						<option value="samestudytype">Same study type
					</select>
				</td>
			</tr>
			<tr class="row<?=$neworder?>">
				<td valign="top" colspan="7" style="padding-left:25px">
					<details class="level1" style="padding:0px;margin:0px">
						<summary style="padding:0px; font-size:9pt">Options</summary>
						<table class="entrytable" style="background-color: #EEE; border-radius:4px; border: 1px solid #999">
							<tr>
								<td class="label">Image type</td>
								<td><input class="small" type="text" name="dd_imagetype[<?=$neworder?>]" size="30"></td>
							</tr>
							<tr>
								<td class="label">Series criteria <img src="images/help.gif" title="<b>All</b> - All matching series will be downloaded<br><b>First</b> - Only the lowest numbered series will be downloaded<br><b>Last</b> - Only the highest numbered series will be downloaded<br><b>Largest</b> - Only one series with the most number of volumes or slices will be downloaded<br><b>Smallest</b> - Only one series with the least number of volumes or slices will be downloaded"></td>
								<td>
									<select class="small" name="dd_seriescriteria[<?=$neworder?>]">
										<option value="all" selected>All</option>
										<option value="first">First</option>
										<option value="last">Last</option>
										<option value="largestsize">Largest</option>
										<option value="smallestsize">Smallest</option>
										<option value="usesizecriteria">Use size criteria below</option>
									</select>
								</td>
							</tr>
							<tr>
								<td class="label">Number of BOLD reps <img src="images/help.gif" title="<b>Must be an integer or a criteria:</b><ul><li><i>N</i> (exactly N)<li>> <i>N</i> (greater than)<li>>= <i>N</i> (greater than or equal to)<li>< <i>N</i> (less than)<li><= <i>N</i> (less than or equal to)<li>~ <i>N</i> (not)</ul>"></td>
								<td><input type="text" name="dd_numboldreps[<?=$neworder?>]"></td>
							</tr>
							<tr>
								<td colspan="2" style="font-weight:bold; border-top: 1px solid gray">Output Format</td>
							</tr>
							<tr>
								<td class="label">Directory <img src="images/help.gif" title="<b>Tip:</b> choose a directory called 'data/<i>taskname</i>'. If converting data or putting into a new directory structure, this data directory can be used as a staging area and can then be deleted later in your script"><br><span class="tiny">Relative to analysis root</span></td>
								<td><input class="small" type="text" name="dd_location[<?=$neworder?>]" size="30"></td>
							</tr>
							<tr>
								<td class="label">Data format</td>
								<td>
									<select class="small" name="dd_dataformat[<?=$neworder?>]">
										<option value="native" selected>Native</option>
										<option value="dicom">DICOM</option>
										<option value="nifti3d">Nifti 3D</option>
										<option value="nifti4d">Nifti 4D</option>
										<option value="analyze3d">Analyze 3D</option>
										<option value="analyze4d">Analyze 4D</option>
									</select>
								</td>
							</tr>
							<tr>
								<td class="label">g-zip</td>
								<td><input class="small" type="checkbox" name="dd_gzip[<?=$neworder?>]" value="1"></td>
							</tr>
							<tr>
								<td class="label">Use series directories <img src="images/help.gif" title="<b>Tip:</b> If you plan to download multiple series with the same name, you will want to use series directories. This option will place each series into its own directory (data/task/1, data/task/2, etc)"</td>
								<td><input class="small" type="checkbox" name="dd_useseriesdirs[<?=$neworder?>]" value="1"></td>
							</tr>
							<tr>
								<td class="label">Preserve series numbers <img src="images/help.gif" title="If data is placed in a series directory, check this box to preserve the original series number. Otherwise the series number directories will be sequential starting at 1, regardless of the orignal series number"></td>
								<td><input class="small" type="checkbox" name="dd_preserveseries[<?=$neworder?>]" value="1"></td>
							</tr>
							<tr>
								<td class="label">Phase encoding direction <img src="images/help.gif" title="<b>Phase Encoding Direction</b> If selected, it will write the data to a subdirectory corresponding to the acquired phase encoding direction: AP, PA, RL, LR, COL, ROW, noPE"></td>
								<td><input class="small" type="checkbox" name="dd_usephasedir[<?=$neworder?>]" value="1"></td>
							</tr>
							<tr>
								<td class="label">Behavioral data directory format</td>
								<td>
									<select class="small" name="dd_behformat[<?=$neworder?>]">
										<option value="behnone" selected>Don't download behavioral data</option>
										<option value="behroot">Place in root (file.log)</option>
										<option value="behrootdir">Place in directory in root (beh/file.log)</option>
										<option value="behseries">Place in series (2/file.log)</option>
										<option value="behseriesdir">Place in directory in series (2/beh/file.log)</option>
									</select>
								</td>
							</tr>
							<tr>
								<td class="label">Behavioral data directory name</td>
								<td><input class="small" type="text" name="dd_behdir[<?=$neworder?>]"></td>
							</tr>
						</table>
						<br>
					</details>
				</td>
			</tr>
			<? $neworder++; ?>
			<? } ?>
		</table>
		<?
		} /* end of the check to display the data specs */ ?>
		
		<br><br>
		<div style="text-align:left; font-size:12pt; font-weight: bold; color:#214282;">Main Script Commands<br><span class="tiny" style="font-weight:normal">Ctrl+S to save</span></div>
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
			#supplementcommandlist { 
				position: relative;
				width: 1000px;
				height: 300px;
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
				<?
			$sqlstring = "select * from pipeline_steps where pipeline_id = $id and pipeline_version = $version and ps_supplement <> 1 order by ps_order + 0";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			//PrintSQLTable($result);
			?>
		<textarea name="commandlist" style="font-weight:normal"><?
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
				if ((substr(trim($ps_command),0,1) == '#') || (trim($ps_command) == '')) {
echo "$ps_command $logged $ps_desc\n";
				}
				elseif ($ps_enabled) {
echo "$ps_command     # $logged $ps_desc\n";
				}
				else {
echo "#$ps_command     $logged $ps_desc\n";
				}
			}
		?></textarea>
		<div id="commandlist" style="border: 1px solid #666; font-weight: normal"></div>
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
					<tr><td class="pipelinevariable" onclick="insertText('{subjectuids}');" title="Space separated list of UIDs. For group analyses">{subjectuids}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{studydatetimes}');" title="Space separated list of datetimes, ordered by datetime. For group analyses">{studydatetimes}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{analysisgroupid}');" title="Group analysis ID">{analysisgroupid}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{uidstudynums}');" title="Space separated list of uidstudynums for all groups">{uidstudynums}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{numsubjects}');" title="Number of subjects from all groups">{numsubjects}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{groups}');" title="Space separated list of groups">{groups}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{numsubjects_groupname}');" title="Number of subjects (sessions) in the group specified">{numsubjects_groupname}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{uidstudynums_groupname}');" title="Space separated list of uidstudynums for the group specified">{uidstudynums_groupname}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{NOLOG}');" title="Insert in the comment and the line will not be logged. Useful if the command is using the > or >> operators to write to a file">{NOLOG}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{NOCHECKIN}');" title="Insert in the comment and the step will not be reported. Useful for command line for-loops">{NOCHECKIN}</td></tr>
					<tr><td class="pipelinevariable" onclick="insertText('{PROFILE}');" title="Enable profiling (measure RAM, CPU, disk IO usage) for this step using the <tt>time<tt> command. Will not work with certain linux commands such as <tt>export, for, while</tt>, etc">{PROFILE}</td></tr>
				</table>
				<br><br>
				<span class="editoroptions" onClick="toggleWrap()">Toggle text wrap</span>
				</td>
			</tr>
			<tr>
				<td colspan="6" align="left">
					<?
						$sqlstring2 = "select * from pipeline_steps where pipeline_id = $id and pipeline_version = '$version' and ps_supplement = 1 order by ps_order + 0";
						$result2 = MySQLiQuery($sqlstring2,__FILE__,__LINE__);
						if (mysqli_num_rows($result2) > 0) {
							$open = "open";
						}
						else {
							$open = "";
						}
					?>
					<details <?=$open?>>
					<summary style="text-align:left; font-size:12pt; font-weight: bold; color:#214282;">Supplementary script commands</summary>
					<br>
					<div id="supplementcommandlist" style="border: 1px solid #666; font-weight: normal"></div>
					<textarea name="supplementcommandlist"><?
						while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
							$pipelinestep_id = $row2['pipelinestep_id'];
							$ps_desc = $row2['ps_description'];
							$ps_order = $row2['ps_order'];
							$ps_command = $row2['ps_command'];
							$ps_workingdir = $row2['ps_workingdir'];
							$ps_enabled = $row2['ps_enabled'];
							$ps_logged = $row2['ps_logged'];
							if ($ps_enabled == 1) { $enabled = ""; } else { $enabled = "#"; }
							if ($ps_logged == 1) { $logged = ""; } else { $logged = "{NOLOG}"; }
							if ((substr(trim($ps_command),0,1) == '#') || (trim($ps_command) == '')) {
echo "$ps_command $logged $ps_desc\n";
							}
							elseif ($ps_enabled) {
echo "$ps_command     # $logged $ps_desc\n";
							}
							else {
echo "#$ps_command     $logged $ps_desc\n";
							}
						}
					?></textarea>
					<span class="editoroptions" onClick="toggleWrap2()">Toggle text wrap</span>
					</details>
				</td>
			</tr>
			<tr>
				<td colspan="6" align="center">
					<br><br>
					<input type="submit" <?=$disabled?> value="Save Pipeline Options">
				</td>
			</tr>
			</form>
		</table>
		</fieldset>
		<script src="scripts/aceeditor/ace.js" type="text/javascript" charset="utf-8"></script>
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
			
			var editor2 = ace.edit("supplementcommandlist");
			var textarea2 = $('textarea[name="supplementcommandlist"]').hide();
			editor2.setFontSize(12);
			editor2.getSession().setMode("ace/mode/sh");
			editor2.getSession().setUseWrapMode(false);
			editor2.getSession().setValue(textarea2.val());
			<?if ($readonly) { ?>
			editor2.setReadOnly();
			<? } ?>
			editor2.getSession().on('change', function(){
			  textarea2.val(editor2.getSession().getValue());
			});
			editor2.setTheme("ace/theme/xcode");
			
			function insertText2(text) {
				editor2.insert(text);
			}
			function toggleWrap2() {
				if (editor2.getSession().getUseWrapMode()) {
					editor2.getSession().setUseWrapMode(false);
				}
				else {
					editor2.getSession().setUseWrapMode(true);
				}
			};
		</script>
		
		<br><br>
		<br><br>
		
		<?
		}
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayPipelineStatus -------------- */
	/* -------------------------------------------- */
	function DisplayPipelineStatus($pipelinename, $isenabled, $id, $pipeline_status, $pipeline_statusmessage, $pipeline_laststart, $pipeline_lastfinish, $pipeline_lastcheck) {
		if (!ValidID($id,'Pipeline ID - M')) { return; }

		?>
		<div align="center">
			<table width="70%" border="0" cellspacing="0" cellpadding="0" style="border: 1px solid #444;">
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
								<td  align="center" style="padding: 2px 30px; background-color: #229320; color: #fff; font-size:11pt; border: 1px solid darkgreen"><b>Status:</b> Running (<a href="pipelines.php?action=reset&id=<?=$id?>" style="color: #ccc" title="Reset the status if you KNOW the pipeline has stopped running... ie, it hasn't updated the status in a couple days">reset</a>)<br>
								<span style="font-size: 8pt">Jobs are being (or waiting to be) submitted</span>
								</td>
							<? } else { ?>
								<td  align="center" style="padding: 2px 30px; background-color: #8e3023; color: #fff; font-size:11pt; border: 1px solid darkred"><b>Status:</b><?=$pipeline_status ?><br>
								<span style="font-size: 8pt">Jobs are not being submitted</span>
								</td>
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
	/* ------- DisplayVersion --------------------- */
	/* -------------------------------------------- */
	function DisplayVersion($id, $version) {
		/* check the parameters */
		if (!ValidID($id,'Pipeline ID - N')) { return; }
	
		$sqlstring = "select * from pipelines where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$title = $row['pipeline_name'];
		$desc = $row['pipeline_desc'];
		if ($version == "") {
			$version = $row['pipeline_version'];
		}
		
		$urllist['Pipelines'] = "pipelines.php";
		$urllist[$title] = "pipelines.php?action=editpipeline&id=$id";
		NavigationBar("$title", $urllist);

		?>
		<form method="post" action="pipelines.php" name="versionform">
		<input type="hidden" name="action" value="viewversion">
		<input type="hidden" name="id" value="<?=$id?>">
		<b>View previous version</b>
		<select name="version" onchange='versionform.submit()'>
			<option value="">(select version)</option>
		<?
		$sqlstring = "select * from pipeline_version where pipeline_id = $id order by version desc";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$versionnumber = $row['version'];
			$versiondatetime = date("M n, Y H:i:s", strtotime($row['version_datetime']));
			$versionnotes = $row['version_notes'];
			?>
			<option value="<?=$versionnumber?>"><b><?=$versionnumber?></b> - <?=$versiondatetime?>
			<?
		}
		?>
		</select>
		</form>
		
		<table class="entrytable">
			<tr>
				<td class="label">Pipeline</td>
				<td>
					<?=$title?> version <?=$version?>
					<br>
					<?=$desc?>
				</td>
			</tr>
			<tr>
				<td class="label">Options</td>
				<td>
					<?
						$sqlstring = "select a.*, b.group_name, c.pipeline_name from pipeline_options a left join groups b on a.pipeline_groupid = b.group_id left join pipelines c on a.pipeline_dependency = c.pipeline_id where a.pipeline_id = $id and a.pipeline_version = $version";
						$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
						$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
						$dependency = $row['pipeline_dependency'];
						$dependencylevel = $row['pipeline_dependencylevel'];
						$dependencydir = $row['pipeline_dependencydir'];
						$deplinktype = $row['pipeline_deplinktype'];
						$groupid = $row['pipeline_groupid'];
						$grouptype = $row['pipeline_grouptype'];
						$groupbysubject = $row['pipeline_groupbysubject'];
						$dynamicgroupid = $row['pipeline_dynamicgroupid'];
						$completefiles = $row['pipeline_completefiles'];
						$resultsscript = $row['pipeline_resultsscript'];
						$groupname = $row['group_name'];
						$parentname = $row['pipeline_name'];
						?>
						<table class="twocoltable">
							<tr>
								<td>Dependency<br><span class="tiny">parent pipeline</span></td>
								<td><?=$parentname?></td>
							</tr>
							<tr>
								<td>Dependency matching criteria</td>
								<td><?=$dependencylevel?></td>
							</tr>
							<tr>
								<td>Dependency Dir</td>
								<td><?=$dependencydir?></td>
							</tr>
							<tr>
								<td>Dependency Copy method</td>
								<td><?=$deplinktype?></td>
							</tr>
							<tr>
								<td>Group</td>
								<td><?=$groupname?></td>
							</tr>
							<tr>
								<td>Group type</td>
								<td><?=$grouptype?></td>
							</tr>
							<tr>
								<td>Group by subject</td>
								<td><?=$groupbysubject?></td>
							</tr>
							<tr>
								<td>Dependency Dir</td>
								<td><?=$completefiles?></td>
							</tr>
							<tr>
								<td>Results script</td>
								<td><?=$resultsscript?></td>
							</tr>
						</table>
						<?
					?>
				</td>
			</tr>
			<tr>
				<td class="label">Data</td>
				<td>
					<table class="displaytable">
						<thead>
						<tr>
							<th></th>
							<th>Enabled</th>
							<th>Optional</th>
							<th>Protocol</th>
							<th>Modality</th>
							<th>Image type</th>
							<th>Data format</th>
							<th>Series criteria</th>
							<th>Type</th>
							<th>Level</th>
							<th>Association type</th>
							<th>Num BOLD reps</th>
							<th>gzip</th>
							<th>Directory</th>
							<th>Use series?</th>
							<th>Preserve series?</th>
							<th>Use phase dir?</th>
							<th>Beh format</th>
							<th>Beh dir</th>
						</tr>
						</thead>
						<?
						$sqlstring = "select * from pipeline_data_def where pipeline_id = $id and pipeline_version = $version order by pdd_order + 0";
						//PrintSQL($sqlstring);
						$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$pipelinedatadef_id = $row['pipelinedatadef_id'];
							$pdd_order = $row['pdd_order'];
							$pdd_seriescriteria = $row['pdd_seriescriteria'];
							$pdd_type = $row['pdd_type'];
							$pdd_level = $row['pdd_level'];
							$pdd_assoctype = $row['pdd_assoctype'];
							$pdd_protocol = $row['pdd_protocol'];
							$pdd_imagetype = $row['pdd_imagetype'];
							$pdd_modality = $row['pdd_modality'];
							$pdd_dataformat = $row['pdd_dataformat'];
							$pdd_gzip = $row['pdd_gzip'];
							$pdd_location = $row['pdd_location'];
							$pdd_useseries = $row['pdd_useseries'];
							$pdd_preserveseries = $row['pdd_preserveseries'];
							$pdd_usephasedir = $row['pdd_usephasedir'];
							$pdd_behformat = $row['pdd_behformat'];
							$pdd_behdir = $row['pdd_behdir'];
							$pdd_enabled = $row['pdd_enabled'];
							$pdd_optional = $row['pdd_optional'];
							$pdd_numboldreps = $row['pdd_numboldreps'];
							?>
							<tr style="color:<? if (!$pdd_enabled) { echo "#BBBBBB"; } else { echo "#000000"; } ?>">
								<td><?=$pdd_order?></td>
								<td><? if ($pdd_enabled) { echo "&#10003"; } ?></td>
								<td><? if ($pdd_optional) { echo "&#10003"; } ?></td>
								<td><b><?=$pdd_protocol?></b></td>
								<td><?=$pdd_modality?></td>
								<td><tt><?=$pdd_imagetype?></tt></td>
								<td><?=$pdd_dataformat?></td>
								<td><?=$pdd_seriescriteria?></td>
								<td><?=$pdd_type?></td>
								<td><?=$pdd_level?></td>
								<td><?=$pdd_assoctype?></td>
								<td><?=$pdd_numboldreps?></td>
								<td><? if ($pdd_gzip) { echo "&#10003"; } ?></td>
								<td><tt><?=$pdd_location?></tt></td>
								<td><? if ($pdd_useseries) { echo "&#10003"; } ?></td>
								<td><? if ($pdd_preserveseries) { echo "&#10003"; } ?></td>
								<td><? if ($pdd_usephasedir) { echo "&#10003"; } ?></td>
								<td><?=$pdd_behformat?></td>
								<td><tt><?=$pdd_behdir?></tt></td>
							</tr>
							<?
						}
						?>
					</table>
				</td>
			</tr>
			<tr>
				<td class="label">Script</td>
				<td style="border-radius: 4px; padding: 10px">
					<style>
						ol.code { background-color: #ccc; margin-right: 5px; font-family: courier new; font-size:10pt; white-space: pre; border: 1px solid black; }
						li.code { counter-increment: custom; background-color: #fff; padding: 1px}
					</style>
					<ol class="code"><?
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
							?><li class="code"><?=$ps_command?> <span style="color:green"># <?=$ps_desc?></span><?
						}
					?></ol>
				</td>
			</tr>
		</table>
		<br><br>
	<?
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayPipelineUsage ---------------- */
	/* -------------------------------------------- */
	function DisplayPipelineUsage() {
	
		MarkTime("DisplayPipelineUsage()");
	
		$urllist['Pipelines'] = "pipelines.php";
		$urllist['New Pipeline'] = "pipelines.php?action=addform";
		NavigationBar("Pipelines", $urllist);
		
		$username = $GLOBALS['username'];
		
		global $imgdata;
		/* create the graphs for each pipeline group */
		$sqlstring = "select distinct(pipeline_group) 'pipeline_group' from pipelines where pipeline_group <> ''";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$group = $row['pipeline_group'];
			//$imgdata[$group] = CreatePipelineGraph($group);
		}
		list($myusage,$maxsize) = GetPipelineInfo();
		
		?>
		<style>
			a { color: #224ea5; }
		</style>
		<span style="font-size: 10pt">
			<b>My usage</b><br>
			<b>Disk</b> <?=number_format(($myusage['totaldisk']/1024/1024/1024),1) . '&nbsp;GB';?><br>
			<b># running</b> <?=$myusage['totalrunning']?><br>
			<b># complete</b> <?=$myusage['totalcomplete']?><br>
		</span>
		<br>
		<span style="font-size:10pt">View: <a href="pipelines.php?viewall=1">All</a> | <a href="pipelines.php?viewall=1" title="Does not display hidden pipelines">Normal</a></span>
		<br>
		<span style="font-size:10pt">View: <a href="pipelines.php?action=viewusage">Disk usage</a></span>
		<br>
		<?	
			$pipelinetree = GetPipelineTree($viewall, 0);
			//PrintVariable($pipelinetree);
		?>
		<br><br>
		<b>All usage</b>
		<table class="smallgraydisplaytable" style="margin-top: 5px; border-collapse: collapse">
			<thead>
				<tr style="vertical-align: top;text-align:left">
					<!--<th style="font-size:12pt">Pipeline Group</th>-->
					<th style="font-size:12pt">Name <span class="tiny">Mouseover for description</span></th>
					<th style="font-size:12pt">Owner<br></th>
					<!--<th style="font-size:12pt">Status</th>-->
					<!--<th style="font-size:12pt" align="right" title="processing / complete">Analyses</th>-->
					<th style="font-size:12pt" align="right">Disk size</th>
					<!--<th style="font-size:12pt" align="right">Parent disk</th>-->
					<th style="font-size:12pt" align="right">Net disk</th>
					<!--<th style="font-size:12pt" align="left">Path</th>-->
					<!--<th style="font-size:12pt">Queue</th>-->
				</tr>
			</thead>
			<tbody>
				<?
					PrintUsageTree($pipelinetree,0,0,$maxsize);
				?>
			</tbody>
		</table>
		<br><br><br><br><br>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayPipelineTree ---------------- */
	/* -------------------------------------------- */
	function DisplayPipelineTree($viewname, $viewlevel, $viewowner, $viewstatus, $viewenabled, $viewall, $viewuserid) {
	
		MarkTime("DisplayPipelineTree()");
	
		$urllist['Pipelines'] = "pipelines.php";
		$urllist['New Pipeline'] = "pipelines.php?action=addform";
		NavigationBar("Pipelines", $urllist);
		
		$username = $GLOBALS['username'];
		
		if ($viewuserid != "all") {
			if (($viewuserid == "") || ($viewuserid < 0)) {
				$viewuserid = $GLOBALS['userid'];
			}
		}
		
		/* get list of userids and usernames */
		$userids[$GLOBALS['userid']] = $GLOBALS['username'];
		$sqlstring = "select b.username, a.pipeline_admin 'userid' from pipelines a left join users b on a.pipeline_admin = b.user_id group by a.pipeline_admin order by b.username";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			if ($row['username'] != "")
				$userids[$row['userid']] = $row['username'];
		}
		
		global $imgdata;
		/* create the graphs for each pipeline group */
		$sqlstring = "select distinct(pipeline_group) 'pipeline_group' from pipelines where pipeline_group <> ''";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$group = $row['pipeline_group'];
			//$imgdata[$group] = CreatePipelineGraph($group);
		}
		list($myusage,$maxsize) = GetPipelineInfo();
		
	?>
	<style>
		a { color: #224ea5; }
	</style>
	<span style="font-size: 10pt">
		<b>My usage</b><br>
		<b>Disk</b> <?=number_format(($myusage['totaldisk']/1024/1024/1024),1) . '&nbsp;GB';?><br>
		<b># running</b> <?=$myusage['totalrunning']?><br>
		<b># complete</b> <?=$myusage['totalcomplete']?><br>
	</span>
	<br>
	<span style="font-size:10pt">View: <a href="pipelines.php?viewuserid=all"><b>All pipelines</b></a> &nbsp; Only pipelines owned by
	<?
		foreach ($userids as $userid => $username) {
			?> | <a href="pipelines.php?viewuserid=<?=$userid?>"><?=$username?></a><?
		}
	?>
	</span>
	<br>
<!--	<span style="font-size:10pt">View: <a href="pipelines.php?viewall=1">All</a> | <a href="pipelines.php?viewall=1" title="Does not display hidden pipelines">Normal</a></span>
	<br> -->
	<span style="font-size:10pt">View: <a href="pipelines.php?action=viewusage">Disk usage</a></span>
	<br>
	<?	
		if ($viewuserid == "all") {
			foreach ($userids as $userid => $username)
				$useridlist[] = $userid;
		}
		else {
			$useridlist[] = $viewuserid;
		}
		
		foreach ($useridlist as $userid) {
			$username = $userids[$userid];
			$pipelinetree = GetPipelineTree($viewall, $userid);
			if (trim($username) == "") { $username = "(blank)"; }
			?>
			<br><br>
			<table width="100%" style="border: 1px solid #ddd" cellspacing="0">
				<tr>
					<td style="background-color: #DDD; padding:5px">
			<b style="color: #00008B;font-size:14pt"><?=$username?></b> &nbsp; &nbsp; <input id="pipelinenamefilter<?=$username?>" type="text" placeholder="Filter by pipeline name"/>
			<script type="text/javascript">
				function filterTable(event) {
					var filter = event.target.value.toUpperCase();
					var rows = document.querySelector("#pipelinetable<?=$username?> tbody").rows;
					
					for (var i = 0; i < rows.length; i++) {
						var firstCol = rows[i].cells[0].textContent.toUpperCase();
						var secondCol = rows[i].cells[1].textContent.toUpperCase();
						if (firstCol.indexOf(filter) > -1 || secondCol.indexOf(filter) > -1) {
							rows[i].style.display = "";
						} else {
							rows[i].style.display = "none";
						}      
					}
				}

				document.querySelector('#pipelinenamefilter<?=$username?>').addEventListener('keyup', filterTable, false);
			</script>
					</td>
				</tr>
			</table>
			<table class="smallgraydisplaytable" id="pipelinetable<?=$username?>" width="100%">
				<thead>
					<tr style="vertical-align: top;text-align:left">
						<th style="font-size:12pt">Pipeline Group</th>
						<th style="font-size:12pt">Name <span class="tiny">Mouseover for description</span></th>
						<th style="font-size:12pt" align="right">Level</th>
						<th style="font-size:12pt">Owner<br></th>
						<th style="font-size:12pt">Status</th>
						<th style="font-size:12pt" align="right" title="processing / complete">Analyses</th>
						<th style="font-size:12pt" align="right">Disk size</th>
						<th style="font-size:12pt" align="left">Path</th>
						<th style="font-size:12pt">Queue</th>
					</tr>
				</thead>
				<tbody>
					<?
						PrintTree($pipelinetree,0);
					?>
				</tbody>
			</table>
			<?
		}
	?>
	<br><br><br><br><br>
	<?
	}

	
	/* -------------------------------------------- */
	/* ------- GetPipelineTree -------------------- */
	/* -------------------------------------------- */
	function GetPipelineTree($viewall, $userid) {
		MarkTime("GetPipelineTree($viewall, $userid)");
		
		/* get list of pipelines owned by this username */
		if ($viewall) {
			if ($userid != 0) {
				$whereclause = "where b.pipeline_admin = $userid";
			}
		}
		else {
			if ($userid == 0) {
				$whereclause = "where b.pipeline_ishidden <> 1";
			}
			else {
				$whereclause = "where b.pipeline_ishidden <> 1 and b.pipeline_admin = $userid";
			}
		}
		/* get list of pipelines */
		$sqlstring = "select a.parent_id,b.pipeline_id,b.pipeline_name from pipeline_dependencies a right join pipelines b on a.pipeline_id = b.pipeline_id $whereclause order by b.pipeline_group, b.pipeline_name";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			//print_r($row); echo "<br>\n";
			$childID = $row['pipeline_id'];
			$parentID = $row['parent_id'];
			$pipelineName = $row['pipeline_name'];
			if ($parentID == '') { $parentID = 0; }
			$arr[$childID][] = $parentID;
			
			/* if the parent ID doesn't exist in the list of IDs, add it, with a parent of 0 */
			if (!isset($arr[$parentID])) {
				if ($parentID != 0) {
					$arr[$parentID][] = 0;
				}
			}
		}
		foreach ($arr as $i => $node) {
			$arr[$i] = array_unique($arr[$i]);
		}
		$tree = ParseTree($arr);

		return $tree;
	}

	
	/* -------------------------------------------- */
	/* ------- ParseTree -------------------------- */
	/* -------------------------------------------- */
	function ParseTree($tree, $root = 0) {
		MarkTime("ParseTree()");
		$return = array();
		// Traverse the tree and search for direct children of the root
		foreach($tree as $child => $par) {
			// A direct child is found
			foreach ($par as $parent) {
				if($parent == $root) {
					// Remove item from tree (we don't need to traverse this again)
					unset($tree[$child]);
					// Append the child into result array and parse its children
					$return[] = array(
						'pipeline_id' => $child,
						'child_id' => parseTree($tree, $child)
					);
				}
			}
		}
		return empty($return) ? 0 : $return;    
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
				$level = PrintTree($node['child_id'], $level);
			}
			$level--;
		}
		return $level;
	}	

	/* -------------------------------------------- */
	/* ------- PrintUsageTree --------------------- */
	/* -------------------------------------------- */
	function PrintUsageTree($tree, $level, $parentusage, $maxsize) {
		MarkTime("PrintUsageTree()");
		if(!is_null($tree) && count($tree) > 0) {
			$level++;
			foreach($tree as $node) {
				$usage = PrintUsageRow($GLOBALS['info'][$node['pipeline_id']], $level, $parentusage, $maxsize);
				$level = PrintUsageTree($node['child_id'], $level, $usage, $maxsize);
			}
			$level--;
		}
		return $level;
	}	
	
	/* -------------------------------------------- */
	/* ------- PrintPipelineRow ------------------- */
	/* -------------------------------------------- */
	function PrintPipelineRow($info, $level) {
		
		//PrintVariable($info);
		
		MarkTime("PrintPipelineRow()");
		if ($level > 1) {
			$class = 'child';
		}

		if ($info['isenabled']) {
			$bgcolor = "#e3f7e6";
		}
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
			<td valign="top"><?=$info['creatorusername']?></td>
			<td valign="top" align="left" style="background-color: <?=$bgcolor?>">
				<?
					if ($info['isenabled']) {
						?><a href="pipelines.php?action=disable&returnpage=home&id=<?=$info['id']?>"><img src="images/checkedbox16.png" title="Enabled. Click to disable"></a><?
					}
					else {
						?><a href="pipelines.php?action=enable&returnpage=home&id=<?=$info['id']?>"><img src="images/uncheckedbox16.png" title="Disabled. Click to enable"></a><?
					}
				?>
				<span title="<b>Last message:</b> <?=$info['message']?><br><b>Last check:</b> <?=$info['lastcheck']?>">
				<? if ($info['status'] == 'running') { ?><b>running</b> <a href="pipelines.php?action=reset&id=<?=$info['id']?>">reset</a><? } else { echo $info['status']; }  ?>
				</span>
			</td>
			<td valign="top" align="right" style="font-size: 8pt; white-space:nowrap;" title="processing / complete">
				<?=$info['numprocessing']?> / <b><?=$info['numcomplete']?></b> &nbsp; <a href="analysis.php?action=viewanalyses&id=<?=$info['id']?>"><img src="images/preview.gif" title="View analysis list"></a>
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
	/* ------- PrintUsageRow ---------------------- */
	/* -------------------------------------------- */
	function PrintUsageRow($info, $level, $parentusage, $maxsize) {
		
		MarkTime("PrintUsageRow($info, $level, $parentusage)");

		if ($level > 1) { $class = 'child'; }

		if ($info['isenabled']) { $bgcolor = "#e3f7e6"; }
		
		if ($info['ishidden']) { $fontcolor = "gray"; }
		else { $fontcolor = "black"; }

		$usage = $info['disksize'];
		$parentusage;
		$netusage = $usage - $parentusage;
		if ($netusage < 0) $netusage = 0;
		$usage_f = number_format(($usage/1024/1024/1024),1) . '&nbsp;GB';
		$parentusage_f = number_format(($parentusage/1024/1024/1024),1) . '&nbsp;GB';
		$netusage_f = number_format(($netusage/1024/1024/1024),1) . '&nbsp;GB';

		$colors = GenerateColorGradient();

		$usageindex = 0;
		if ($usage > 0) {
			$usageindex = round(($usage/($maxsize))*100.0);
			if ($usageindex > 100) { $usageindex = 100; }
			$usagecolor = $colors[$usageindex];
		}
		else { $usagecolor = ""; }

		$parentindex = 0;
		if ($parentusage > 0) {
			$parentindex = round(($parentusage/($maxsize))*100.0);
			if ($parentindex > 100) { $parentindex = 100; }
			$parentcolor = $colors[$parentindex];
		}
		else { $parentcolor = ""; }

		$netindex = 0;
		if ($netusage > 0) {
			$netindex = round(($netusage/($maxsize))*100.0);
			if ($netindex > 100) { $netindex = 100; }
			$netcolor = $colors[$netindex];
		}
		else { $netcolor = ""; }
		
		?>
		<tr style="color: <?=$fontcolor?>">
			<td valign="top" style="padding-left: <?=($level-1)*20?>;" class="<?=$class?>" title="<b><?=$info['title']?></b> &nbsp; <?=$info['desc']?>"><? if ($level > 1) { echo "&#9495;&nbsp;"; } ?><a href="pipelines.php?action=editpipeline&id=<?=$info['id']?>" style="font-size:11pt"><?=$info['title']?></a> &nbsp; <span class="tiny">v<?=$info['version']?></span></td>
			<td valign="top"><?=$info['creatorusername']?>
			<!--<td valign="top" align="right" style="font-size: 8pt; white-space:nowrap;" title="processing / complete">
				<?=$info['numprocessing']?> / <b><?=$info['numcomplete']?></b> &nbsp; <a href="analysis.php?action=viewanalyses&id=<?=$info['id']?>"><img src="images/preview.gif" title="View analysis list"></a>
			</td>-->
			<td valign="top" align="right" style="font-size:8pt; background-color: <?=$usagecolor?>"><?=$usage_f?></td>
			<!--<td valign="top" align="right" style="font-size:8pt; background-color: <?=$parentcolor?>"><?=$parentusage_f?></td>-->
			<td valign="top" align="right" style="font-size:8pt; background-color: <?=$netcolor?>; border: 1px solid #666"><?=$netusage_f?></td>
		</tr>
		<?
		
		return $info['disksize'];
	}
	
	
	/* -------------------------------------------- */
	/* ------- GetPipelineInfo -------------------- */
	/* -------------------------------------------- */
	function GetPipelineInfo() {
		MarkTime("GetPipelineInfo() first call");

		/* yes, this variable is supposed to be global...
		   the reason being: there is a good chance the pipeline info will be needed many times,
		   with the info being needed at different locations in the code. Rather than loading
		   everything at once, this loads what is needed to display and keeps it for later,
		   kind of like caching ... */
		global $info;
		
		$maxsize = 0;
		$sqlstring = "select a.*,timediff(pipeline_lastfinish, pipeline_laststart) 'run_time', b.username 'creatorusername', b.user_fullname 'creatorfullname' from pipelines a left join users b on a.pipeline_admin = b.user_id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$id = $row['pipeline_id'];

			MarkTime("GetPipelineInfo($id)");
			
			if ($row['pipeline_name'] == "") { $row['pipeline_name'] = "(blank pipeline name)"; }
			
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
			$info[$id]['level'] = $row['pipeline_level'];
			$info[$id]['dirstructure'] = $row['pipeline_dirstructure'];
			if ($row['pipeline_directory'] == "") {
				if ($info[$id]['dirstructure'] == "b") {
					$info[$id]['directory'] = $GLOBALS['cfg']['analysisdirb'];
				}
				else {
					$info[$id]['directory'] = $GLOBALS['cfg']['analysisdir'];
				}
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

			MarkTime("GetPipelineInfo($id) pre size");
			
			$sqlstringD = "select sum(analysis_disksize) 'disksize' from analysis where pipeline_id = $id";
			$resultD = MySQLiQuery($sqlstringD,__FILE__,__LINE__);
			$rowD = mysqli_fetch_array($resultD, MYSQLI_ASSOC);
			$info[$id]['disksize'] = $rowD['disksize'];
			//PrintSQL($sqlstringD);
			//echo "$disksize<br>";

			if ($info[$id]['disksize'] > $maxsize) {
				$maxsize = $info[$id]['disksize'];
			}
			
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
			
			if ($info[$id]['creatorusername'] == $GLOBALS['username']) {
				$myusage['totaldisk'] += $info[$id]['disksize'];
				$myusage['totalcomplete'] += $info[$id]['numcomplete'];
				$myusage['totalrunning'] += $info[$id]['numrunning'];
			}
			
			MarkTime("GetPipelineInfo($id) post counts");
		}
		
		//PrintVariable($myusage);
		return array($myusage, $maxsize);
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
