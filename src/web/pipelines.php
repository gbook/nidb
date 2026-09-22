<?
 // ------------------------------------------------------------------------------
 // NiDB pipelines.php
 // Copyright (C) 2004 - 2026
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
	ob_start(); /* buffer output so POST/Redirect/GET (a header('Location') redirect) works despite the HTML rendered below */
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
	//PrintVariable($_GET, "GET");
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$id = (int)GetVariable("id");
	
	$viewname = GetVariable("viewname");
	$viewlevel = GetVariable("viewlevel");
	$viewowner = GetVariable("viewowner");
	$viewstatus = GetVariable("viewstatus");
	$viewenabled = GetVariable("viewenabled");
	$viewhidden = GetVariable("viewhidden");
	$viewall = GetVariable("viewall");
	$viewuserid = (int)GetVariable("viewuserid");
	if ($viewuserid != 0)
		$_SESSION['viewuserid'] = $viewuserid;

	$pipelinetitle = GetVariable("pipelinetitle");
	$pipelinedesc = GetVariable("pipelinedesc");
	$pipelinegroup = GetVariable("pipelinegroup");
	$pipelinenumproc = GetVariable("pipelinenumproc");
	$pipelineclustertype = GetVariable("pipelineclustertype");
	$pipelineclusteruser = GetVariable("pipelineclusteruser");
	$pipelinesubmithost = GetVariable("pipelinesubmithost");
	$pipelinesubmithostuser = GetVariable("pipelinesubmithostuser");
	$pipelinemaxwalltime = GetVariable("pipelinemaxwalltime");
	$pipelinesubmitdelay = GetVariable("pipelinesubmitdelay");
	$pipelinequeue = GetVariable("pipelinequeue");
	$pipelinenumcores = GetVariable("pipelinenumcores");
	$pipelinememory = GetVariable("pipelinememory");
	$pipelinedatacopymethod = GetVariable("pipelinedatacopymethod");
	$pipelineremovedata = GetVariable("pipelineremovedata");
	$pipelineresultsscript = GetVariable("pipelineresultsscript");
	$pipelinedirectory = GetVariable("pipelinedirectory");
	$pipelinedirstructure = GetVariable("pipelinedirstructure");
	$pipelineusetmpdir = GetVariable("pipelineusetmpdir");
	$pipelinetmpdir = GetVariable("pipelinetmpdir");
	$pipelinenotes = GetVariable("pipelinenotes");
	$version = (int)GetVariable("version");
	$completefiles = GetVariable("completefiles");
	$dependency = GetVariable("dependency");
	$deplevel = GetVariable("deplevel");
	$depdir = GetVariable("depdir");
	$deplinktype = GetVariable("deplinktype");
	$groupid = GetVariable("groupid");
	$projectid = GetVariable("projectid");
	//$dynamicgroupid = GetVariable("dynamicgroupid");
	$level = GetVariable("level");
	$ishidden = GetVariable("pipelineishidden");
	$groupbysubject = GetVariable("groupbysubject");
	$outputbids = GetVariable("outputbids");
	$bidsoutputdir = GetVariable("bidsoutputdir");

	$newname = GetVariable("newname");
	$newuserid = (int)GetVariable("newuserid");
	
	$commandlist = GetVariable("commandlist");
	$supplementcommandlist = GetVariable("supplementcommandlist");

	$dd_enabled = GetVariable("dd_enabled");
	$dd_order = GetVariable("dd_order");
	$dd_protocol = GetVariable("dd_protocol");
	if (!is_array($dd_protocol)) $dd_protocol = array();
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
	$dd_isprimary = GetVariable("dd_isprimary");
	$dd_preserveseries = GetVariable("dd_preserveseries");
	$dd_usephasedir = GetVariable("dd_usephasedir");
	$dd_behonly = GetVariable("dd_behonly");
	
	$returnpage = GetVariable("returnpage");
	$returntab = GetVariable("returntab");
	
	/* determine action */
	switch ($action) {
		case 'editpipeline':
			DisplayPipelineForm("edit", $id, $returntab);
			break;
		case 'viewversion':
			DisplayVersion($id, $version);
			break;
		case 'addform':
			DisplayPipelineForm("add", "", $returntab);
			break;
		case 'updatepipelineoptions':
			ob_start();
			UpdatePipelineOptions($id, $commandlist, $supplementcommandlist, array(), $dd_enabled, $dd_order, $dd_protocol, $dd_modality, $dd_datalevel, $dd_studyassoc, $dd_dataformat, $dd_imagetype, $dd_gzip, $dd_location, $dd_seriescriteria, $dd_numboldreps, $dd_behformat, $dd_behdir, $dd_useseriesdirs, $dd_optional, $dd_isprimary, $dd_preserveseries, $dd_usephasedir, $dd_behonly, $pipelineresultsscript, $completefiles, $deplevel, $depdir, $deplinktype, $groupid, $projectid, $dependency, $groupbysubject, $outputbids, $bidsoutputdir);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo(PipelineEditURL($id, $returntab));
			break;
		case 'update':
			ob_start();
			UpdatePipeline($id, $pipelinetitle, $pipelinedesc, $pipelinegroup, $pipelinenumproc, $pipelineclustertype, $pipelineclusteruser, $pipelinesubmithost, $pipelinesubmithostuser, $pipelinemaxwalltime, $pipelinesubmitdelay, $pipelinedatacopymethod, $pipelinequeue, $pipelinenumcores, $pipelinememory, $pipelineremovedata, $pipelinedirectory, $pipelinedirstructure, $pipelineusetmpdir, $pipelinetmpdir, $pipelinenotes, $username, $level, $ishidden);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo(PipelineEditURL($id, $returntab));
			break;
		case 'add':
			ob_start();
			$id = AddPipeline($pipelinetitle, $pipelinedesc, $pipelinegroup, $pipelinenumproc, $pipelineclustertype, $pipelineclusteruser, $pipelinesubmithost, $pipelinesubmithostuser, $pipelinemaxwalltime, $pipelinesubmitdelay, $pipelinedatacopymethod, $pipelinequeue, $pipelinenumcores, $pipelinememory, $pipelineremovedata, $pipelinedirectory, $pipelinedirstructure, $pipelineusetmpdir, $pipelinetmpdir, $pipelinenotes, $username, $completefiles, $dependency, $deplevel, $depdir, $deplinktype, $groupid, $projectid, $level, $groupbysubject, $outputbids, $bidsoutputdir);
			$_SESSION['flash'] = ob_get_clean();
			if ($id > 0)
				RedirectTo(PipelineEditURL($id, $returntab));
			else
				RedirectTo("pipelines.php?action=addform");
			break;
		case 'changeowner':
			ob_start();
			ChangeOwner($id,$newuserid);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo(PipelineEditURL($id, $returntab));
			break;
		case 'delete':
			ob_start();
			DeletePipeline($id);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("pipelines.php");
			break;
		case 'copy':
			ob_start();
			CopyPipeline($id, $newname);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("pipelines.php");
			break;
		case 'reset':
			ob_start();
			ResetPipeline($id);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("pipelines.php");
			break;
		case 'resetanalyses':
			ob_start();
			ResetAnalyses($id);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo(PipelineEditURL($id, $returntab));
			break;
		case 'disable':
		case 'enable':
		case 'disabledebug':
		case 'enabledebug':
			ob_start();
			if ($action == 'disable') DisablePipeline($id);
			elseif ($action == 'enable') EnablePipeline($id);
			elseif ($action == 'disabledebug') DisablePipelineDebug($id);
			else EnablePipelineDebug($id);
			$_SESSION['flash'] = ob_get_clean();
			if ($returnpage == "home")
				RedirectTo("pipelines.php");
			else
				RedirectTo(PipelineEditURL($id, $returntab));
			break;
		case 'viewpipelinelist':
			DisplayPipelineTree($viewname, $viewlevel, $viewowner, $viewstatus, $viewenabled, $viewall, $viewhidden, $viewuserid);
			break;
		case 'viewusage':
			DisplayPipelineUsage();
			break;
		case 'exportpipeline':
			ob_start();
			ExportPipeline($id);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo(PipelineEditURL($id, $returntab));
			break;
		case 'exportanalysisresults':
			ob_start();
			ExportAnalysisResults($id);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo(PipelineEditURL($id, $returntab));
			break;
		default:
			DisplayPipelineTree($viewname, $viewlevel, $viewowner, $viewstatus, $viewenabled, $viewall, $viewhidden, $viewuserid);
	}
	//PrintVariable($GLOBALS['t']);

	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- PipelineEditURL -------------------- */
	/* -------------------------------------------- */
	/* GET url of the pipeline edit page, used as the PRG redirect target after a mutating action */
	function PipelineEditURL($id, $returntab) {
		$url = "pipelines.php?action=editpipeline&id=" . (int)$id;
		if ($returntab != "") { $url .= "&returntab=" . urlencode($returntab); }
		return $url;
	}


	/* -------------------------------------------- */
	/* ------- UpdatePipeline --------------------- */
	/* -------------------------------------------- */
	/* this function does NOT CHANGE the version    */
	/* number                                       */
	/* -------------------------------------------- */
	function UpdatePipeline($id, $pipelinetitle, $pipelinedesc, $pipelinegroup, $pipelinenumproc, $pipelineclustertype, $pipelineclusteruser, $pipelinesubmithost, $pipelinesubmithostuser, $pipelinemaxwalltime, $pipelinesubmitdelay, $pipelinedatacopymethod, $pipelinequeue, $pipelinenumcores, $pipelinememory, $pipelineremovedata, $pipelinedirectory, $pipelinedirstructure, $pipelineusetmpdir, $pipelinetmpdir, $pipelinenotes, $username, $level, $ishidden) {
		
		if (!ValidID($id,'Pipeline ID - A')) { return; }
		
		/* perform data checks */
		$pipelinenumproc = (int)$pipelinenumproc;
		$pipelinemaxwalltime = (int)$pipelinemaxwalltime;
		$pipelinesubmitdelay = (int)$pipelinesubmitdelay;
		$pipelineremovedata = (int)$pipelineremovedata;
		$pipelineusetmpdir = (int)$pipelineusetmpdir;
		$ishidden = GetMySQLTinyInt($ishidden);
		$pipelinequeue = preg_replace('/\s+/', '', trim($pipelinequeue));
		
		/* update the pipeline */
		$sqlstring = "update pipelines set pipeline_name = ?, pipeline_desc = ?, pipeline_group = ?, pipeline_numproc = ?, pipeline_submithost = ?, pipeline_submithostuser = ?, pipeline_maxwalltime = ?, pipeline_submitdelay = ?, pipeline_datacopymethod = ?, pipeline_queue = ?, pipeline_numcores = ?, pipeline_memory = ?, pipeline_clustertype = ?, pipeline_clusteruser = ?, pipeline_removedata = ?, pipeline_directory = ?, pipeline_dirstructure = ?, pipeline_usetmpdir = ?, pipeline_tmpdir = ?, pipeline_notes = ?, pipeline_ishidden = ? where pipeline_id = ?";
		$params = [$pipelinetitle, $pipelinedesc, $pipelinegroup, $pipelinenumproc, $pipelinesubmithost, $pipelinesubmithostuser, $pipelinemaxwalltime, $pipelinesubmitdelay, $pipelinedatacopymethod, $pipelinequeue, $pipelinenumcores, $pipelinememory, $pipelineclustertype, $pipelineclusteruser, $pipelineremovedata, $pipelinedirectory, $pipelinedirstructure, $pipelineusetmpdir, $pipelinetmpdir, $pipelinenotes, $ishidden, $id];
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'sssissiissssssississii', ...$params);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);

		Notice("Pipeline info for <b>$pipelinetitle</b> updated");
	}

	
	/* -------------------------------------------- */
	/* ------- CanEditPipeline -------------------- */
	/* -------------------------------------------- */
	/* returns true if the current user owns the pipeline or is a site admin. Mirrors the $readonly check in DisplayPipelineForm() */
	function CanEditPipeline($id) {
		if ($GLOBALS['issiteadmin']) { return true; }

		$id = (int)$id;
		$sqlstring = "select b.username from pipelines a left join users b on a.pipeline_admin = b.user_id where a.pipeline_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$row) { return false; }

		$owner = $row['username'] ?? '';
		return (($owner != '') && (strtolower($owner) == strtolower($GLOBALS['username'])));
	}


	/* -------------------------------------------- */
	/* ------- SplitStepLine ---------------------- */
	/* -------------------------------------------- */
	/* split an editor line into [command, description]. The description starts at the first '#' that
	   begins a bash comment: preceded by whitespace, and not quoted or escaped. A leading '#' (disabled
	   step) stays part of the command. Everything after the comment '#' is the description, including
	   any further '#'. If the quotes are unbalanced, fall back to splitting at the first whitespace+'#' */
	function SplitStepLine($line) {
		$start = 0;
		if (preg_match('/^\s*#/', $line, $m)) { $start = strlen($m[0]); }

		$quote = '';
		$len = strlen($line);
		for ($i = $start; $i < $len; $i++) {
			$c = $line[$i];
			if ($quote == "'") {
				if ($c == "'") { $quote = ''; }
				continue;
			}
			if ($c == '\\') { $i++; continue; }
			if ($quote == '"') {
				if ($c == '"') { $quote = ''; }
				continue;
			}
			if (($c == '"') || ($c == "'")) { $quote = $c; continue; }
			if (($c == '#') && ($i > 0) && ctype_space($line[$i-1])) {
				return array(rtrim(substr($line, 0, $i)), trim(substr($line, $i+1)));
			}
		}

		if ($quote != '') {
			$parts = preg_split('/\s+#/', substr($line, $start), 2);
			if (count($parts) == 2) {
				return array(rtrim(substr($line, 0, $start) . $parts[0]), trim($parts[1]));
			}
		}

		return array(rtrim($line), '');
	}


	/* -------------------------------------------- */
	/* ------- RenderStepLine --------------------- */
	/* -------------------------------------------- */
	/* build the editor line for a pipeline step, in the format SplitStepLine() parses back */
	function RenderStepLine($command, $description, $enabled, $logged) {
		$line = $command;
		/* disabled steps are shown commented out. Older rows may be disabled without a leading '#' */
		if (!$enabled && (substr(ltrim($command), 0, 1) != '#')) {
			$line = "#$command";
		}
		$comment = trim(($logged ? '' : '{NOLOG}') . " $description");
		if ($comment != '') {
			$line .= "     # $comment";
		}
		return $line;
	}


	/* -------------------------------------------- */
	/* ------- UpdatePipelineOptions -------------- */
	/* -------------------------------------------- */
	/* this function CHANGES the version number     */
	/* -------------------------------------------- */
	function UpdatePipelineOptions($id, $commandlist, $supplementcommandlist, $steporder, $dd_enabled, $dd_order, $dd_protocol, $dd_modality, $dd_datalevel, $dd_studyassoc, $dd_dataformat, $dd_imagetype, $dd_gzip, $dd_location, $dd_seriescriteria, $dd_numboldreps, $dd_behformat, $dd_behdir, $dd_useseriesdirs, $dd_optional, $dd_isprimary, $dd_preserveseries, $dd_usephasedir, $dd_behonly, $pipelineresultsscript, $completefiles, $deplevel, $depdir, $deplinktype, $groupid, $projectid, $dependency, $groupbysubject, $outputbids, $bidsoutputdir) {
		
		if (!ValidID($id,'Pipeline ID - C')) { return; }
		if (!CanEditPipeline($id)) {
			Error("You do not have permission to edit this pipeline. Only the pipeline owner or a site admin can make changes.");
			return;
		}

		$msg = "<ol style='font-size:smaller'>";

		$sqlstring = "start transaction";
		$msg .= "<li><b>Starting transaction</b>";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* get the current and next pipeline version # */
		$sqlstring = "select pipeline_version from pipelines where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$oldversion = (int)$row['pipeline_version'];
		$newversion = $oldversion + 1;
		$msg .= "<li>Got new version number [$newversion]";

		/* insert row in the pipeline version table */
		$sqlstring = "insert into pipeline_version (pipeline_id, version, version_datetime, version_notes) values ($id, $newversion, now(), '')";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$msg .= "<li>Updated pipeline_version table";
		
		/* the pipeline option information is updated in two tables, for backward compatibility...
		   old pipelines do not do version control on the pipeline options */
		$groupbysubject = GetMySQLTinyInt($groupbysubject);
		$outputbids = GetMySQLTinyInt($outputbids);

		/* dependency, group, and project lists are comma-separated IDs. They are later inlined into 'in (...)' clauses, so keep only integers */
		$dependencies = implode(",", array_filter(array_map('intval', is_array($dependency) ? $dependency : explode(",", (string)$dependency))));
		$groupids = implode(",", array_filter(array_map('intval', is_array($groupid) ? $groupid : explode(",", (string)$groupid))));
		$projectids = implode(",", array_filter(array_map('intval', is_array($projectid) ? $projectid : explode(",", (string)$projectid))));

		/* update the pipeline table */
		$sqlstring = "update pipelines set pipeline_resultsscript = ?, pipeline_completefiles = ?, pipeline_dependency = ?, pipeline_groupid = ?, pipeline_projectid = ?, pipeline_dependencylevel = ?, pipeline_dependencydir = ?, pipeline_deplinktype = ?, pipeline_groupbysubject = ?, pipeline_outputbids = ?, pipeline_bidsoutputdir = ? where pipeline_id = ?";
		$params = [$pipelineresultsscript, $completefiles, $dependencies, $groupids, $projectids, $deplevel, $depdir, $deplinktype, $groupbysubject, $outputbids, $bidsoutputdir, $id];
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ssssssssiisi', ...$params);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);
		$msg .= "<li>Updated pipelines table";
		
		/* delete any existing dependencies, and insert the current dependencies */
		$sqlstring = "delete from pipeline_dependencies where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$msg .= "<li>Deleted old dependencies";

		if ($dependencies != '') {
			$sqlstring = "insert into pipeline_dependencies (pipeline_id, parent_id) values (?, ?)";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			foreach (explode(",", $dependencies) as $dep) {
				$dep = (int)$dep;
				mysqli_stmt_bind_param($stmt, 'ii', $id, $dep);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id, $dep]);
				$msg .= "<li>Inserted dependency ($dep)";
			}
			mysqli_stmt_close($stmt);
		}

		/* add row to the pipeline_options table for the new version */
		$sqlstring = "insert into pipeline_options (pipeline_id, pipeline_version, pipeline_dependency, pipeline_dependencylevel, pipeline_dependencydir, pipeline_deplinktype, pipeline_groupid, pipeline_projectid, pipeline_groupbysubject, pipeline_outputbids, pipeline_bidsoutputdir, pipeline_completefiles, pipeline_resultsscript) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$params = [$id, $newversion, $dependencies, $deplevel, $depdir, $deplinktype, $groupids, $projectids, $groupbysubject, $outputbids, $bidsoutputdir, $completefiles, $pipelineresultsscript];
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'iissssssiisss', ...$params);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);
		$msg .= "<li>Updated pipeline_options table";
		
		$steporder = array();
		$command = array();
		$workingdir = array();
		$description = array();
		$stepenabled = array();
		$logged = array();
		
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
			$stepenabled[$step] = preg_match('/^\s*\#/', $line) ? 0 : 1;
			list($command[$step], $description[$step]) = SplitStepLine($line);
			
			$workingdir[$step] = "";
			$steporder[$step] = $step;
			$step++;
		}
		/* insert all the new fields with NEW version # */
		$sqlstring = "insert into pipeline_steps (pipeline_id, pipeline_version, ps_supplement, ps_command, ps_workingdir, ps_order, ps_description, ps_enabled, ps_logged) values (?, ?, 0, ?, ?, ?, ?, ?, ?)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		for($i=1; $i<=count($steporder); $i++) {
			if (trim($command[$i]) != "") {
				$cmd = rtrim($command[$i]);
				$desc = str_replace("\r",'', trim($description[$i]));
				$params = [$id, $newversion, $cmd, $workingdir[$i], $steporder[$i], $desc, $stepenabled[$i], $logged[$i]];
				mysqli_stmt_bind_param($stmt, 'iissisii', ...$params);
				$msg .= "<li>Inserted step $i: [" . htmlspecialchars($cmd) . "]\n";
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
			}
		}
		mysqli_stmt_close($stmt);
		
		$steporder = array();
		$supplementcommand = array();
		$workingdir = array();
		$supplementdescription = array();
		$stepenabled = array();
		$logged = array();

		/* split up the SUPPLEMENT commandlist into commands, then split them into enabled, command, description, logged, etc */
		$supplementcommands = explode("\n",$supplementcommandlist);
		$step = 1;
		foreach ($supplementcommands as $line) {
			/* remove any trailing carriage returns or whitespace */
			$line = rtrim($line);

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
			$stepenabled[$step] = preg_match('/^\s*\#/', $line) ? 0 : 1;
			list($supplementcommand[$step], $supplementdescription[$step]) = SplitStepLine($line);

			$workingdir[$step] = "";
			$steporder[$step] = $step;
			$step++;
		}
		
		/* insert all the new fields with NEW version # */
		$sqlstring = "insert into pipeline_steps (pipeline_id, pipeline_version, ps_supplement, ps_command, ps_workingdir, ps_order, ps_description, ps_enabled, ps_logged) values (?, ?, 1, ?, ?, ?, ?, ?, ?)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		for($i=1; $i<=count($steporder); $i++) {
			if (trim($supplementcommand[$i]) != "") {
				$cmd = rtrim($supplementcommand[$i]);
				$desc = str_replace("\r",'', trim($supplementdescription[$i]));
				$params = [$id, $newversion, $cmd, $workingdir[$i], $steporder[$i], $desc, $stepenabled[$i], $logged[$i]];
				mysqli_stmt_bind_param($stmt, 'iissisii', ...$params);
				$msg .= "<li>Inserted supplement step $i: [" . htmlspecialchars($cmd) . "]\n";
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
			}
		}
		mysqli_stmt_close($stmt);
		
		$msg .= "<li>Pipeline steps updated";

		/* insert all the new PRIMARY data fields with NEW version # */
		$sqlstring = "insert into pipeline_data_def (pipeline_id, pipeline_version, pdd_isprimaryprotocol, pdd_order, pdd_seriescriteria, pdd_protocol, pdd_modality, pdd_dataformat, pdd_imagetype, pdd_gzip, pdd_location, pdd_useseries, pdd_preserveseries, pdd_usephasedir, pdd_behonly, pdd_behformat, pdd_behdir, pdd_enabled, pdd_optional, pdd_numboldreps, pdd_level, pdd_assoctype) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		foreach ($dd_protocol as $i => $protocol) {
			if (trim($protocol) != "") {
				/* perform data checks */
				$order = $dd_order[$i] ?? '';
				$primary = ($dd_isprimary == $order) ? 1 : 0;
				$params = [
					$id, $newversion, $primary, $order,
					$dd_seriescriteria[$i] ?? '',
					$protocol,
					$dd_modality[$i] ?? '',
					$dd_dataformat[$i] ?? '',
					$dd_imagetype[$i] ?? '',
					GetMySQLTinyInt($dd_gzip[$i] ?? ''),
					$dd_location[$i] ?? '',
					GetMySQLTinyInt($dd_useseriesdirs[$i] ?? ''),
					GetMySQLTinyInt($dd_preserveseries[$i] ?? ''),
					GetMySQLTinyInt($dd_usephasedir[$i] ?? ''),
					GetMySQLTinyInt($dd_behonly[$i] ?? ''),
					$dd_behformat[$i] ?? '',
					$dd_behdir[$i] ?? '',
					GetMySQLTinyInt($dd_enabled[$i] ?? ''),
					GetMySQLTinyInt($dd_optional[$i] ?? ''),
					$dd_numboldreps[$i] ?? '',
					$dd_datalevel[$i] ?? '',
					trim($dd_studyassoc[$i] ?? '')
				];
				mysqli_stmt_bind_param($stmt, 'iiissssssisiiiissiisss', ...$params);
				$msg .= "<li>Inserted data definition [" . htmlspecialchars($protocol) . "]";
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
			}
		}
		mysqli_stmt_close($stmt);
		
		/* update pipeline with new version */
		$sqlstring = "update pipelines set pipeline_version = $newversion where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* ------ all done ------ */
		$sqlstring = "commit";
		//PrintSQL("$sqlstring");
		$msg .= "<li><b>Commit the transaction</b>";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		$msg .= "</ol> Data specification [$id] updated";
		
		Notice($msg);
	}


	/* -------------------------------------------- */
	/* ------- AddPipeline ------------------------ */
	/* -------------------------------------------- */
	function AddPipeline($pipelinetitle, $pipelinedesc, $pipelinegroup, $pipelinenumproc, $pipelineclustertype, $pipelineclusteruser, $pipelinesubmithost, $pipelinesubmithostuser, $pipelinemaxwalltime, $pipelinesubmitdelay, $pipelinedatacopymethod, $pipelinequeue, $pipelinenumcores, $pipelinememory, $pipelineremovedata, $pipelinedirectory, $pipelinedirstructure, $pipelineusetmpdir, $pipelinetmpdir, $pipelinenotes, $username, $completefiles, $dependency, $deplevel, $depdir, $deplinktype, $groupid, $projectid, $level, $groupbysubject, $outputbids, $bidsoutputdir) {
		/* perform data checks */
		$pipelinetitle = trim($pipelinetitle);
		$pipelinedesc = trim($pipelinedesc);
		$pipelinegroup = trim($pipelinegroup);
		$pipelinenumproc = trim($pipelinenumproc);
		$pipelineclustertype = trim($pipelineclustertype);
		$pipelineclusteruser = trim($pipelineclusteruser);
		$pipelinesubmithost = trim($pipelinesubmithost);
		$pipelinesubmithostuser = trim($pipelinesubmithostuser);
		$pipelinedatacopymethod = trim($pipelinedatacopymethod);
		$pipelinequeue = trim($pipelinequeue);
		$pipelinenumcores = trim($pipelinenumcores);
		$pipelinememory = trim($pipelinememory);
		$pipelineresultsscript = "";
		$pipelinedirectory = trim($pipelinedirectory);
		$pipelinedirstructure = trim($pipelinedirstructure);
		$pipelinetmpdir = trim($pipelinetmpdir);
		$pipelinenotes = trim($pipelinenotes);
		$completefiles = trim($completefiles);
		$groupbysubject = GetMySQLTinyInt($groupbysubject);
		$outputbids = GetMySQLTinyInt($outputbids);
		$deplevel = trim($deplevel);
		$depdir = trim($depdir);
		$deplinktype = trim($deplinktype);
		$level = (int)$level;

		/* dependency, group, and project lists are comma-separated IDs. They are later inlined into 'in (...)' clauses, so keep only integers */
		$dependencies = implode(",", array_filter(array_map('intval', is_array($dependency) ? $dependency : explode(",", (string)$dependency))));
		$groupids = implode(",", array_filter(array_map('intval', is_array($groupid) ? $groupid : explode(",", (string)$groupid))));
		$projectids = implode(",", array_filter(array_map('intval', is_array($projectid) ? $projectid : explode(",", (string)$projectid))));

		if (!ctype_alnum($pipelinetitle)) {
			Error("Error creating pipeline. Pipeline name can only contain numbers and letters, no spaces or special characters");
			return -1;
		}
		
		/* blank -> NULL */
		$pipelinemaxwalltime = (trim($pipelinemaxwalltime) === '') ? null : (int)$pipelinemaxwalltime;
		$pipelinesubmitdelay = (trim($pipelinesubmitdelay) === '') ? null : (int)$pipelinesubmitdelay;
		$pipelineremovedata = (trim($pipelineremovedata) === '') ? null : (int)$pipelineremovedata;
		$pipelineusetmpdir = (trim($pipelineusetmpdir) === '') ? null : (int)$pipelineusetmpdir;

		/* check if the pipeline name already exists */
		$sqlstring = "select * from pipelines where pipeline_name = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 's', $pipelinetitle);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$pipelinetitle]);
		mysqli_stmt_close($stmt);
		if (mysqli_num_rows($result) > 0) {
			Error("Pipeline name already in use. Please go back and fix it");
			return -1;
		}
		else {
			/* get userid */
			$sqlstring = "select user_id from users where username = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 's', $username);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$username]);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			$userid = (int)($row['user_id'] ?? 0);
			
			/* insert the new form */
			$sqlstring = "insert into pipelines (pipeline_name, pipeline_desc, pipeline_group, pipeline_admin, pipeline_createdate, pipeline_status, pipeline_numproc, pipeline_submithost, pipeline_submithostuser, pipeline_maxwalltime, pipeline_submitdelay, pipeline_datacopymethod, pipeline_queue, pipeline_numcores, pipeline_memory, pipeline_clustertype, pipeline_clusteruser, pipeline_removedata, pipeline_resultsscript, pipeline_completefiles, pipeline_dependency, pipeline_dependencylevel, pipeline_dependencydir, pipeline_deplinktype, pipeline_groupid, pipeline_projectid, pipeline_level, pipeline_directory, pipeline_dirstructure, pipeline_usetmpdir, pipeline_tmpdir, pipeline_notes, pipeline_ishidden, pipeline_groupbysubject, pipeline_outputbids, pipeline_bidsoutputdir) values (?, ?, ?, ?, now(), 'stopped', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)";
			$params = [$pipelinetitle, $pipelinedesc, $pipelinegroup, $userid, $pipelinenumproc, $pipelinesubmithost, $pipelinesubmithostuser, $pipelinemaxwalltime, $pipelinesubmitdelay, $pipelinedatacopymethod, $pipelinequeue, $pipelinenumcores, $pipelinememory, $pipelineclustertype, $pipelineclusteruser, $pipelineremovedata, $pipelineresultsscript, $completefiles, $dependencies, $deplevel, $depdir, $deplinktype, $groupids, $projectids, $level, $pipelinedirectory, $pipelinedirstructure, $pipelineusetmpdir, $pipelinetmpdir, $pipelinenotes, $groupbysubject, $outputbids, $bidsoutputdir];
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'sssisssiissssssissssssssississiis', ...$params);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
			$pipelineid = mysqli_insert_id($GLOBALS['linki']);
			mysqli_stmt_close($stmt);
			
			Notice(htmlspecialchars($pipelinetitle) . " added. Pipeline is disabled by default.");
			
			return $pipelineid;
		}
	}

	
	/* -------------------------------------------- */
	/* ------- CopyPipeline ----------------------- */
	/* -------------------------------------------- */
	function CopyPipeline($id, $newname) {
		
		if (!ValidID($id,'Pipeline ID - B')) { return; }

		$newname = trim($newname);

		if ($newname == "") {
			echo "New pipeline name is blank. Please fix and try again";
			return;
		}
		
		// Validate alphanumeric
		if (preg_match('/[^a-z_\-0-9]/i', $newname)) {
			echo "New pipeline name contains non-alphanumeric characters. Please fix and try again";
			return;
		}
		
		/* check if the new pipeline name already exists */
		$sqlstring = "select * from pipelines where pipeline_name = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 's', $newname);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$newname]);
		mysqli_stmt_close($stmt);
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
		$error = false;
		
		mysqli_autocommit($GLOBALS['linki'], false);
		/* start transaction */
		echo "<li><b>Starting transaction</b>\n";
		$history .= "1) Starting transaction\n";
		mysqli_begin_transaction($GLOBALS['linki']);

		/* ------ copy the pipeline definition ------ */
		/* create a temp table, which automatically creates the columns */
		$sqlstring = "create temporary table tmp_pipeline$id select * from pipelines where pipeline_id = $id";
		echo "<li>Creating temp table from existing pipeline table spec [$sqlstring]\n";
		$history .= "2) Creating temp table from existing pipeline table spec [$sqlstring]\n";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if ($result['error'] == 1) $error = true;
		
		/* for DEBUG, display the original table */
		$sqlstring = "select * from pipelines where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if (is_array($result) && $result['error'] == 1) $error = true;
		$history .= "Original TABLE [pipelines]\n" . PrintSQLTable($result,"","","",true) . "\n\n";

		/* for DEBUG, display the copied temp table */
		$sqlstring = "select * from tmp_pipeline$id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if (is_array($result) && $result['error'] == 1) $error = true;
		$history .= "Temp TABLE [tmp_pipeline$id]\n" . PrintSQLTable($result,"","","",true) . "\n\n";
		
		/* get the new pipeline id */
		$sqlstring = "select (max(pipeline_id)+1) 'newid' from pipelines";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if (is_array($result) && $result['error'] == 1) $error = true;
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$newid = (int)$row['newid'];
		echo "<li>Getting new pipeline ID [$newid] [$sqlstring]\n";
		$history .= "3) Getting new pipeline ID [$newid] [$sqlstring]\n";

		/* this new pipeline_id does not exist... we know that. But the ID may still be in the pipeline_steps table
		   so delete everything from the pipeline_steps table with the new ID */
		$sqlstring = "delete from pipeline_steps where pipeline_id = $newid";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if (is_array($result) && $result['error'] == 1) $error = true;
		echo "<li>Deleting from pipeline_steps table [$sqlstring]\n";
		$history .= "3.1) Deleting from pipeline_steps table [$sqlstring]\n";

		$sqlstring = "select pipeline_version from pipelines where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if (is_array($result) && $result['error'] == 1) $error = true;
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$version = (int)$row['pipeline_version'];
		echo "<li>Getting pipeline version [$version] [$sqlstring]\n";
		$history .= "4) Getting pipeline version [$version] [$sqlstring]\n";

		/* make any changes to the new pipeline before inserting */
		$sqlstring = "update tmp_pipeline$id set pipeline_id = $newid, pipeline_name = ?, pipeline_version = 1, pipeline_createdate = now(), pipeline_status = 'stopped', pipeline_statusmessage = '', pipeline_laststart = null, pipeline_lastfinish = null, pipeline_enabled = 0, pipeline_admin = (select user_id from users where username = ?)";
		$params = [$newname, $_SESSION['username']];
		echo "<li>Making changes to new pipeline in temp table [$sqlstring]\n";
		$history .= "5) Making changes to new pipeline in temp table [$sqlstring] [" . implode(", ", $params) . "]\n";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ss', ...$params);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);
		if ($result === null) $error = true;
		
		/* insert the changed row into the pipeline table */
		$sqlstring = "insert into pipelines select * from tmp_pipeline$id";
		echo "<li>Getting new pipeline ID [$sqlstring]\n";
		$history .= "6) Getting new pipeline ID [$sqlstring]\n";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if (is_array($result) && $result['error'] == 1) $error = true;
		
		/* delete the tmp table */
		$sqlstring = "drop table tmp_pipeline$id";
		echo "<li>Deleting temp table [$sqlstring]\n";
		$history .= "7) Deleting temp table [$sqlstring]\n";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if (is_array($result) && $result['error'] == 1) $error = true;
		
		/* ------ copy the data specification ------ */
		/* create a temp table, which automatically creates the columns */
		$sqlstring = "create temporary table tmp_dataspec$id (select * from pipeline_data_def where pipeline_id = $id and pipeline_version = $version)";
		echo "<li>Create temp table from existing pipeline_data_def spec [$sqlstring]\n";
		$history .= "8) Create temp table from existing pipeline_data_def spec [$sqlstring]\n";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if (is_array($result) && $result['error'] == 1) $error = true;

		//$sqlstring = "alter table tmp_dataspec$id drop primary key";
		//echo "<li>Remove pipelinedatadef_id from temp table pipeline_data_def [$sqlstring]\n";
		//$history .= "8.1) Remove pipelinedatadef_id from temp table pipeline_data_def [$sqlstring]\n";
		//$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		//if (is_array($result) && $result['error'] == 1) $error = true;

		$sqlstring = "alter table tmp_dataspec$id modify pipelinedatadef_id int(11)";
		echo "<li>Remove pipelinedatadef_id not null [$sqlstring]\n";
		$history .= "8.2) Remove pipelinedatadef_id not null [$sqlstring]\n";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if (is_array($result) && $result['error'] == 1) $error = true;
		
		/* for DEBUG, display the original table */
		$sqlstring = "select * from pipeline_data_def where pipeline_id = $id and pipeline_version = $version";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if (is_array($result) && $result['error'] == 1) $error = true;
		$history .= "Original TABLE [pipeline_data_def] \n" . PrintSQLTable($result,"","","",true) . "\n\n";

		/* for DEBUG, display the copied temp table */
		$sqlstring = "select * from tmp_dataspec$id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if (is_array($result) && $result['error'] == 1) $error = true;
		$history .= "Temp TABLE [tmp_dataspec$id] \n" . PrintSQLTable($result,"","","",true) . "\n\n";
		
		/* make any changes to the new pipeline before inserting */
		$sqlstring = "update tmp_dataspec$id set pipeline_id = $newid, pipeline_version = 1, pipelinedatadef_id = null";
		echo "<li>Make changes to temp table [$sqlstring]\n";
		$history .= "9) Make changes to temp table [$sqlstring]\n";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if (is_array($result) && $result['error'] == 1) $error = true;
		
		/* insert the changed rows into the pipeline_data_def table */
		$sqlstring = "insert into pipeline_data_def select * from tmp_dataspec$id";
		echo "<li>Insert temp table rows into pipeline_data_def [$sqlstring]\n";
		$history .= "10) Insert temp table rows into pipeline_data_def [$sqlstring]\n";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if (is_array($result) && $result['error'] == 1) $error = true;
		
		/* delete the tmp table */
		$sqlstring = "drop table tmp_dataspec$id";
		echo "<li>Drop temp table [$sqlstring]\n";
		$history .= "11) Drop temp table [$sqlstring]\n";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if (is_array($result) && $result['error'] == 1) $error = true;
		
		/* ------ copy the pipeline steps specification ------ */
		/* create a temp table, which automatically creates the columns */
		$sqlstring = "create temporary table tmp_steps$id (select * from pipeline_steps where pipeline_id = $id and pipeline_version = $version)";
		echo "<li>Create temp table from pipeline_steps spec [$sqlstring]\n";
		$history .= "12) Create temp table from pipeline_steps spec [$sqlstring]\n";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if (is_array($result) && $result['error'] == 1) $error = true;

		/* for DEBUG, display the original table */
		$sqlstring = "select * from pipeline_steps where pipeline_id = $id and pipeline_version = $version";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if (is_array($result) && $result['error'] == 1) $error = true;
		$history .= "Original TABLE [pipeline_steps] \n" . PrintSQLTable($result,"","","",true) . "\n\n";

		/* for DEBUG, display the copied temp table */
		$sqlstring = "select * from tmp_steps$id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if (is_array($result) && $result['error'] == 1) $error = true;
		$history .= "Temp TABLE [tmp_steps$id] \n" . PrintSQLTable($result,"","","",true) . "\n\n";

		$sqlstring = "alter table tmp_steps$id modify pipelinestep_id int(11)";
		echo "<li>Remove pipelinestep_id not null [$sqlstring]\n";
		$history .= "8.2) Remove pipelinestep_id not null [$sqlstring]\n";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if (is_array($result) && $result['error'] == 1) $error = true;
		
		/* make any changes to the new pipeline before inserting */
		$sqlstring = "update tmp_steps$id set pipeline_id = $newid, pipeline_version = 1, pipelinestep_id = null";
		echo "<li>Make changes to temp table [$sqlstring]\n";
		$history .= "13) Make changes to temp table [$sqlstring]\n";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if (is_array($result) && $result['error'] == 1) $error = true;
		
		/* insert the changed rows into the pipeline_data_def table */
		$sqlstring = "insert into pipeline_steps select * from tmp_steps$id";
		echo "<li>Insert temp rows into pipeline_steps table [$sqlstring]\n";
		$history .= "14) Insert temp rows into pipeline_steps table [$sqlstring]\n";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if (is_array($result) && $result['error'] == 1) $error = true;
		
		/* delete the tmp table */
		$sqlstring = "drop table tmp_steps$id";
		echo "<li>Drop temp table [$sqlstring]\n";
		$history .= "15) Drop temp table [$sqlstring]\n";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if (is_array($result) && $result['error'] == 1) $error = true;
		
		/* copy any dependencies */
		$sqlstring = "select * from pipeline_dependencies where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if (is_array($result) && $result['error'] == 1) $error = true;
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$parentid = (int)$row['parent_id'];
			$sqlstringA = "insert ignore into pipeline_dependencies (pipeline_id, parent_id) values ($newid, $parentid)";
			echo "<li>Copy dependency [$sqlstringA]\n";
			$history .= "16) Copy dependency [$sqlstringA]\n";
			$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
		}
		
		/* ------ all done ------ */
		if ($error) {
			echo "<li><b>Encountered error. Rollback transaction</b>\n";
			$history .= "17) Rollback transaction\n";
			mysqli_rollback($GLOBALS['linki']);
		}
		else {
			echo "<li><b>Commit the transaction</b>\n";
			$history .= "17) Commit the transaction\n";
			mysqli_commit($GLOBALS['linki']);
		}

		mysqli_autocommit($GLOBALS['linki'], true);

		?>
		</ol>
		<?
		
		//$sqlstring = "select * from pipelines where pipeline_id = $newid";
		//$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		//$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		//echo "AFTER COPY (new pipeline)<br>\n";
		//PrintVariable($row);
		
		echo "DEBUG - ignore this stuff<br>";
		//PrintVariable($history);
		
		$history = trim($history);
		$performinguserid = (int)$GLOBALS['userid'];
		$sqlstring = "insert into changelog (performing_userid, change_datetime, change_event, change_desc) values (?, now(), 'pipelinecopy', ?)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'is', $performinguserid, $history);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$performinguserid, '(history)']);
		mysqli_stmt_close($stmt);
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
		
		$sqlstring = "select max(group_id) 'maxgroupid' from fileio_requests";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$groupid = $row['maxgroupid'] + 1;
		
		/* insert a row in the fileio_requests table */
		$groupid = (int)$groupid;
		$id = (int)$id;
		$sqlstring = "insert into fileio_requests (fileio_operation, group_id, data_type,data_id,username,requestdate) values ('delete', ?,'pipeline',?,?,now())";
		$params = [$groupid, $id, $GLOBALS['username']];
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'iis', ...$params);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);
		
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
		?><div align="center"><span class="message">Reset analyses: <?echo mysqli_affected_rows($GLOBALS['linki']); ?> analysis <b>data</b> rows deleted</span></div><?
	
		$sqlstring = "delete from analysis where analysis_status in ('NoMatchingStudies', 'NoMatchingSeries') and pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		?><div align="center"><span class="message">Reset analyses: <?echo mysqli_affected_rows($GLOBALS['linki']); ?> analysis rows deleted</span></div><?
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
	/* ------- DisplayPipelineForm ---------------- */
	/* -------------------------------------------- */
	function DisplayPipelineForm($type, $id, $returntab) {
		MarkTime("DisplayPipelineForm() start");
		ShowFlashMessage(); /* show any message from a mutating action that redirected here (PRG) */

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
			$submithostuser = $row['pipeline_submithostuser'];
			$maxwalltime = $row['pipeline_maxwalltime'];
			$submitdelay = $row['pipeline_submitdelay'];
			$queue = $row['pipeline_queue'];
			$numcores = $row['pipeline_numcores'];
			$memory = $row['pipeline_memory'];
			$clustertype = $row['pipeline_clustertype'];
			$clusteruser = $row['pipeline_clusteruser'];
			$datacopymethod = $row['pipeline_datacopymethod'];
			$remove = $row['pipeline_removedata'];
			$version = (int)$row['pipeline_version'];
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
			$projectid = $row['pipeline_projectid'];
			$level = $row['pipeline_level'];
			$owner = $row['username'];
			$ishidden = $row['pipeline_ishidden'];
			$groupbysubject = $row['pipeline_groupbysubject'];
			$outputbids = $row['pipeline_outputbids'];
			$bidsoutputdir = $row['pipeline_bidsoutputdir'];
			$isenabled = $row['pipeline_enabled'];
			$isdebug = $row['pipeline_debug'];
			
			if ($submithost == "") { $submithost = $GLOBALS['cfg']['clustersubmithost']; }
			if ($clustertype == "") { $clustertype = "sge"; }
			
			if ((strtolower($owner) == strtolower($GLOBALS['username'])) || ($GLOBALS['issiteadmin'])) {
				$readonly = false;
			}
			else {
				$readonly = true;
			}
			
			$formaction = "update";
			$formtitle = "$title";
			$submitbuttonlabel = "Save Settings";
		}
		else {
			$formaction = "add";
			$formtitle = "Add new pipeline";
			$submitbuttonlabel = "Add Pipeline";
			$remove = "0";
			$level = 1;
			$directory = "";
			$readonly = false;
			
			$submithost = $GLOBALS['cfg']['clustersubmithost'];
			$clustertype = "sge";
		}
		
		if ($readonly) {
			$disabled = "disabled";
		}
		else {
			$disabled = "";
		}
		
		if ($numproc == "") { $numproc = 1; }
		
		/* perform validity checks on the pipeline */
		if (($deplevel == "subject") && ($dependency != "") && ($groupid == "")) {
			$checks['Dependency criteria']['level'] = 'error';
			$checks['Dependency criteria']['message'] = "Subject dependency without group specified";
			$checks['Dependency criteria']['description'] = "When using the Data & Scripts &rarr; Pipeline dependency &rarr; Matching criteria &rarr; subject, a group must be specified. Matching dependencies based on subject may result in far more analyses than expected, and thus a group must be specified to narrow down the matches";
		}
		else {
			$checks['Dependency criteria']['level'] = 'ok';
		}
		
	?>
	
		<script type="text/javascript">
		
			$(document).ready(function() {

				$('.tabular.menu .item').tab();
				
				$('.pageloading').hide();
				
				/* default action */
				<? if($level == 1) { ?>
				$('.level1').show();
				$('.level2').hide();
				<? } elseif ($level == 0) { ?>
				$('.level1').hide();
				$('.level2').hide();
				<? } else { ?>
				$('.level1').show();
				$('.level2').show();
				<? } ?>
				
				/* click events */
				$('#level1').click(function() {
					if($('#level1').is(':checked')) {
						$('.level1').show("highlight",{},1000);
						$('.level2').hide();
					}
				});
				$('#level2').click(function() {
					if($('#level2').is(':checked')) {
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
		<div class="ui text container">
			<div class="ui small yellow message pageloading" align="center" id="pageloading" style="margin-bottom:15px">
				<em data-emoji=":chipmunk:" class="loading"></em> Loading...
			</div>
		</div>
		
		<div class="ui text container pageloading">
			<div class="ui active inverted dimmer">
				<div class="ui text loader">Loading</div>
			</div>
		</div>
		
		<? MarkTime("Finished display 'Loading...'"); ?>
		
		<?
			if ($type != "add") {
				DisplayPipelineStatus($title, $desc, $isenabled, $isdebug, $id, "pipelines", $pipeline_status, $pipeline_statusmessage, $pipeline_laststart, $pipeline_lastfinish, $pipeline_lastcheck);
			}
			
			if ($type == "add") {
				$tab_oneactive = "";
				$tab_twoactive = "active";
				$tab_threeactive = "";
				$tab_fouractive = "";
				$tab_fiveactive = "";
			}
			else {
				$tab_oneactive = "";
				$tab_twoactive = "";
				$tab_threeactive = "";
				$tab_fouractive = "";
				$tab_fiveactive = "";
				switch ($returntab) {
					case 'settings': $tab_twoactive = "active"; break;
					case 'datascripts': $tab_threeactive = "active"; break;
					case 'operations': $tab_fouractive = "active"; break;
					default: $tab_oneactive = "active";
				}
			}
		?>
		<br>
		<? MarkTime("Checkin - 1"); ?>
		
		<style>
			.item2.active { background-color: #333 !important; color: #fff !important; }
		</style>
		
		<div class="ui container">
			<div class="ui top attached tabular menu">
				<? if ($type != "add") { ?>
				<a class="<?=$tab_oneactive?> item item2" data-tab="first"><i class="info circle icon"></i> Information</a>
				<? } ?>
				<a class="<?=$tab_twoactive?> item item2" data-tab="second"><i class="cog icon"></i> Settings</a>
				<? if ($type != "add") { ?>
				<a class="<?=$tab_threeactive?> item item2" data-tab="third"><i class="file alternate icon"></i> Data & Scripts</a>
				<a class="<?=$tab_fouractive?> item item2" data-tab="fourth"><i class="wrench icon"></i> Operations</a>
				<a class="<?=$tab_fiveactive?> item item2" data-tab="fifth" id="checkTabTitle"><i class="check icon"></i> Checks</a>
				<? } ?>
			</div>

		<!-- -------------------- Information tab -------------------- -->

		<? if ($type != "add") { ?>
		<div class="ui bottom attached <?=$tab_oneactive?> tab raised segment" data-tab="first">
			<table class="entrytable" style="border:0px">
				<tr>
					<td><h3 class="ui header">View</h3></td>
					<td valign="top" style="padding-bottom: 10pt">
						<p><a href="analysis.php?action=viewanalyses&id=<?=$id?>" class="ui green button" style="width:170px">Analyses</a> View running and completed analyses</p>

						<p><a href="pipeline_history.php?pipelineid=<?=$id?>" class="ui green button" style="width:170px">History</a> View pipeline event history</p>

						<p><a href="analysis.php?action=viewfailedanalyses&id=<?=$id?>" class="ui basic green button" style="width:170px">Ignored studies</a> View studies that did not meet criteria to be analyzed (<b>Helpful for debugging</b>)</p>
						
						<p><a href="pipelines.php?action=viewversion&id=<?=$id?>" class="ui basic green button" style="width:170px"><i class="ui code branch icon"></i>Pipeline versions</a></p>
					</td>
				</tr>
				<tr>
					<td><h3 class="ui header">Analysis statistics</h3></td>
					<td valign="top" style="padding-bottom: 10pt">
						<div class="ui segment">
							<?
								MarkTime("Info Tab - A");
								
								/* gather statistics about the analyses */
								$sqlstring = "select sum(timestampdiff(second, analysis_clusterstartdate, analysis_clusterenddate)) 'cluster_time' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and analysis_status = 'complete'";
								//PrintSQL($sqlstring);
								$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
								$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
								$totaltime = $row['cluster_time'];
								$totaltime = number_format(($totaltime/60/60),2);

								$sqlstring = "select count(*) 'numcomplete' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and analysis_status = 'complete'";
								//PrintSQL($sqlstring);
								$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
								$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
								$numcomplete = $row['numcomplete'];

								/* get mean processing times */
								$analysistimes = array();
								$clustertimes = array();
								$sqlstring = "select analysis_id, timestampdiff(second, analysis_startdate, analysis_enddate) 'analysis_time', timestampdiff(second, analysis_clusterstartdate, analysis_clusterenddate) 'cluster_time' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $id and analysis_status <> ''";
								//PrintSQL($sqlstring);
								//$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
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
							<div class="ui mini statistics">
								<div class="ui statistic">
									<div class="value"><?=$numcomplete?></div>
									<div class="label" style="font-size: smaller">Completed</div>
								</div>
								<div class="ui grey statistic">
									<div class="value"><?=$totaltime?> hr</div>
									<div class="label" style="font-size: smaller">Total CPU Time</div>
								</div>
							</div>
							<br>
							<a href="pipeline_performance.php?pipelineid=<?=$id?>">Pipeline performance</a>
						</div>
					</td>
				</tr>
				<tr>
					<td><h3 class="ui header">Dependency</h3></td>
					<td valign="top" style="padding-bottom: 10pt">
					<?
						/* PHP 8: count() on null is fatal - ensure these are always arrays */
						$parents = array();
						$children = array();
						/* ints (intval-sanitized) - safe to inline */
						$depids = implode(",", array_filter(array_map('intval', explode(",", (string)$dependency))));
						if ($depids != "") {
							$sqlstring = "select pipeline_name, pipeline_id, pipeline_desc, pipeline_notes from pipelines where pipeline_id in ($depids)";
							$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$parentid = $row['pipeline_id'];
								$parents[$parentid]['name'] = $row['pipeline_name'];
								$parents[$parentid]['desc'] = $row['pipeline_desc'];
								$parents[$parentid]['notes'] = $row['pipeline_notes'];
							}
						}
						$sqlstring = "select pipeline_name, pipeline_id, pipeline_desc, pipeline_notes from pipelines where pipeline_id in (select pipeline_id from pipeline_dependencies where parent_id = $id)";
						//PrintSQL($sqlstring);
						$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$childid = $row['pipeline_id'];
							$children[$childid]['name'] = $row['pipeline_name'];
							$children[$childid]['desc'] = $row['pipeline_desc'];
							$children[$childid]['notes'] = $row['pipeline_notes'];
						}
					?>
					<div class="ui compact segment">
						<div class="ui very compact grid">
							<div class="four wide right aligned column"><i class="user icon"></i> <b>Parents</b></div>
							<div class="twelve wide center aligned column">
								<?
									if (count($parents) > 0) {
										foreach ($parents as $parentid => $info) {
											?><a href="pipelines.php?action=editpipeline&id=<?=$parentid?>" title="<?=$info['desc']?><br><br><?=$info['notes']?>"><?=$info['name']?></a><?
										}
									}
									else {
										?>This pipeline does not depend on any other pipelines<?
									}
								?>
							</div>

							<div class="four wide column">&nbsp;</div>
							<div class="twelve wide center aligned column"><i class="arrow down icon"></i></div>

							<div class="four wide column"></div>
							<div class="twelve wide center aligned column">
								<span class="ui big red text"><?=$title?></span>
							</div>

							<div class="four wide column">&nbsp;</div>
							<div class="twelve wide center aligned column"><i class="arrow down icon"></i></div>

							<div class="four wide right aligned column"><i class="child icon"></i> <b>Children</b></div>
							<div class="twelve wide center aligned column">
								<?
									if (count($children) > 0) {
										foreach ($children as $child => $info) {
											?>
												&rdsh; <a href="pipelines.php?action=editpipeline&id=<?=$child?>" title="<?=$info['desc']?><br><br><?=$info['notes']?>"><?=$info['name']?></a>
												<br>
											<?
										}
									}
									else {
										?>No pipelines depend on this pipeline<?
									}
								?>
							</div>
						</div>
					</div>
					
					</td>
				</tr>
				<tr>
					<td><h3 class="ui header">Data location</h3></td>
					<td valign="top" style="padding-bottom: 10pt">
						<?
							$dfmount = "";
							if ($directory != "") {
								$dfmount = $directory;
								//echo $directory;
							} else {
								if ($dirstructure == "b") {
									$dfmount = $GLOBALS['cfg']['analysisdirb'];
									//echo $GLOBALS['cfg']['analysisdirb'];
									$nidbpath = $dfmount . "/<b>ThePipeline</b>/S1234ABC/1";
									$clusterpath = $GLOBALS['cfg']['clusteranalysisdirb'] . "/<b>ThePipeline</b>/S1234ABC/1";
								}
								elseif ($dirstructure == "a") {
									$dfmount = $GLOBALS['cfg']['analysisdir'];
									//echo $GLOBALS['cfg']['analysisdir'];
									$nidbpath .= $dfmount . "/S1234ABC/1/<b>ThePipeline</b>";
									$clusterpath .= $GLOBALS['cfg']['clusteranalysisdir'] . "/S1234ABC/1/<b>ThePipeline</b>";
								}
								elseif (is_integer($dirstructure)) {
									$sqlstring = "select * from analysisdirs where analysisdir_id = $dirstructure";
									$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
									$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
									$nidbpath = $row['nidbpath'];
									$clusterpath = $row['clusterpath'];
									$dirformat = $row['dirformat'];
									if ($dirformat == "uidfirst") {
										$nidbpath .= "/S1234ABC/1/<b>ThePipeline</b>";
										$clusterpath .= "/S1234ABC/1/<b>ThePipeline</b>";
									}
									else {
										$nidbpath .= "/<b>ThePipeline</b>/S1234ABC/1";
										$clusterpath .= "/<b>ThePipeline</b>/S1234ABC/1";
									}
									
								}
							}

							if (is_dir($dfmount)) {
								$freespace = disk_free_space($dfmount);
								$totalspace = disk_total_space($dfmount);

								/* PHP 8: division by zero is fatal - guard against disk_total_space() returning 0/false */
								$percentfree = ($totalspace > 0) ? ($freespace / $totalspace) * 100.0 : 0;

								if ($percentfree > 10) {
									$diskcolor = "green";
									$diskicon = "check icon";
									$checks['Disk space']['level'] = 'ok';
									$checks['Disk space']['message'] = "More than 10% disk free space on $clusterpath";
									$checks['Disk space']['description'] = "No issues with disk space";
								}
								elseif ($percentfree > 1) {
									$diskcolor = "orange";
									$diskicon = "exclamation";
									$checks['Disk space']['level'] = 'warning';
									$checks['Disk space']['message'] = "Less than 10% disk free space on $clusterpath";
									$checks['Disk space']['description'] = "The disk is running out of free space. It is possible that this pipeline will fail with an out of space error";
								} 
								else {
									$diskcolor = "red";
									$diskicon = "exclamation circle icon";
									$checks['Disk space']['level'] = 'error';
									$checks['Disk space']['message'] = "Less than 1% disk free space on $clusterpath";
									$checks['Disk space']['description'] = "The disk is nearly full. It is probable that this pipeline will fail with an out of space error";
								}
								$diskmsg = number_format($percentfree,1) . "% free <div class='detail'>(" . HumanReadableFilesize($freespace) . " free)</div>";
							}
							else {
								$diskcolor = "red";
								$diskicon = "exclamation circle icon";
								$diskmsg = "<tt>$dfmount</tt> does not exist";
							}
						?>
						<table class="ui very basic compact collapsing table">
							<tr>
								<td>Cluster <i class="question circle outline icon" title="The path as seen by the cluster"></i></td>
								<td><tt><?=$clusterpath?></tt></td>
							</tr>
							<tr class="disabled">
								<td>NiDB <i class="question circle outline icon" title="The path as seen by NiDB"></i></td>
								<td><tt><?=$nidbpath?></tt></td>
							</tr>
							<tr>
								<td>Disk free space</td>
								<td><div class="ui <?=$diskcolor?> basic label"><i class="<?=$diskicon?>"></i> <?=$diskmsg?></div></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td><h3 class="ui header"><i class="question circle icon"></i> Help</h3></td>
					<td valign="top" style="padding-bottom: 10pt">
						<div class="ui accordion">
							<div class="title">
								<i class="dropdown icon"></i>
								Pipeline not working?
							</div>
							<div class="content">
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
							</div>
						</div>
					</td>
				</tr>
			</table>
		</div>
		<? } ?>

		<? MarkTime("Between Info and Settings tabs"); ?>
		
		<!-- -------------------- Settings tab -------------------- -->

		<script>
			/* check if the submit host is up (and qsub is accessible via passwordless ssh) */
			$(document).ready(function() {
				CheckHostnameStatus();
			});
		
			function CheckHostnameStatus() {
				var xhttp = new XMLHttpRequest();
				xhttp.onreadystatechange = function() {
					if (this.readyState == 4 && this.status == 200) {
						var clustertype = document.getElementById("pipelineclustertype").value;
						//console.log(this.responseText);
						var retCode = this.responseText.charAt(0);
						if (retCode == "1") {
							document.getElementById("hostup").innerHTML = "<div class='ui left pointing basic label'><i class='ui green check circle icon'></i> Valid submit host</div>";
							document.getElementById("pipelinesubmithostinput").classList.remove('error');
						}
						else {
							errMsg = this.responseText;
							document.getElementById("hostup").innerHTML = "<div class='ui left pointing red label'><i class='ui exclamation circle icon'></i> Invalid " + clustertype + " submit host [" + errMsg + "]</div>";
							//document.getElementById("pipelinesubmithostinput").classList.add('error');
						}
					}
				};
				var hostname = document.getElementById("pipelinesubmithost").value;
				var clustertype = document.getElementById("pipelineclustertype").value;
				var submithostuser = document.getElementById("pipelinesubmithostuser").value;
				xhttp.open("GET", "ajaxapi.php?action=checksgehost&hostname=" + hostname + "&clustertype=" + clustertype + "&submithostuser=" + submithostuser, true);
				xhttp.send();
			}
		</script>
		
		<div class="ui bottom attached <?=$tab_twoactive?> tab raised segment" data-tab="second">
			<div class="ui right close rail">
				<div class="ui segment">
					<div class="ui accordion">
						<div class="title">
							<i class="dropdown icon"></i>
							Help
						</div>
						<div class="content">
							<h3 class="ui header">Title</h3>
							The pipeline name. This will be the directory name on disk. Limit of 255 characters.

							<h3 class="ui header">Description</h3>
							Longer description.

							<h3 class="ui header">Stats level</h3>
								<ul>
									<li><b>First</b> Subject level, from individual studies
									<li><b>Second</b> Group level, from first-level pipelines
								</ul>

							<h3 class="ui header">Directory</h3>
							Full path into which this pipeline will be stored. A directory called <b>Title</b> (same name as this pipeline) will be created inside this directory and that directory will contain all of the analyses for this pipeline. If this option is blank (the default), analyses for this pipeline will be written to the default pipeline directory <code>/nidb/data/pipelineb</code>	

							<h3 class="ui header">Directory structure</h3>
							<ul>
								<li><b>pipeline</b> <code>/S1234ABC/1/ThisPipeline</code>
								<li><b>pipelineb</b> <code>/ThisPipeline/S1234ABC/1</code>
							</ul>

							<h3 class="ui header">Pipeline group</h3>
							Pipelines can be grouped together using a group name. This is different than a group of subjects or studies.

							<h3 class="ui header">Notes</h3>
							Any notes for the pipeline.

							<h3 class="ui header">Data transfer method</h3>
							<ul>
								<li><b>NFS</b> copies via the the <tt>cp</tt> command assumes the filesystem you want to write to is mounted on this server
								<li><b>scp</b> uses secure copy and assumes you have a passwordless login setup between this server and the one you are copying to
							</ul>

							<h3 class="ui header">Concurrent processes</h3>This is the number of concurrent jobs allowed to be submitted to the cluster at a time. This number is separate from the number of slots available in the cluster queue, which specified in the grid engine setup	

							<h3 class="ui header">Cluster type</h3>SGE (default) or slurm	

							<h3 class="ui header">Cluster user</h3>The username under which data copying and cluster job submission should be done. This user must already have ssh keys setup for password-less login between this sever and the cluster submit server. If blank, the default username is used ()	

							<h3 class="ui header">Submit hostname</h3>The hostname of the cluster node to submit to. This host will also be used for scp copy. If blank, the default submit host is used ()	

							<h3 class="ui header">Max wall time</h3>
							Maximum wall time (in minutes) that each analysis is allowed to run before being terminated. 24 hours = 1440 minutes. Default is unlimited.

							<h3 class="ui header">Submit delay</h3>
							Number of hours after the study datetime that the job will be submitted. This option exists to allow a manual data import process to occur. For example, MRI data will be automatically imported into NiDB and is available for analysis immediately, but behavioral data may need to to be manually uploaded and may take a certain number of hours to be available. Default delay is 6 hours.

							<h3 class="ui header">Queue name</h3>
							The sun grid (SGE) queue to submit t (Comma separated list)

							<h3 class="ui header">Use temporary directory</h3>
							This option will copy all data into the temporary directory first, process it there, and copy it back to its final location. Usually <code>/tmp</code>. Check with your sysadmin

							<h3 class="ui header">Hidden?</h3>
							Useful to hide a pipeline from the main pipeline list. The pipeline still exists, but it won't show up in the main list.
						</div>
					</div>
				</div>
			</div>
			<table class="entrytable" width="100%">
				<form method="post" action="pipelines.php">
				<input type="hidden" name="action" value="<?=$formaction?>">
				<input type="hidden" name="id" value="<?=$id?>">
				<input type="hidden" name="returntab" value="settings">
				<tr>
					<td class="label" valign="top" align="right">Name</td>
					<td valign="top">
						<div class="ui input">
							<input type="text" name="pipelinetitle" required value="<?=$title?>" maxlength="50" size="60" onKeyPress="return AlphaNumeric(event)" <? if ($type == "edit") { echo "readonly style='background-color: #EEE;"; } ?>>
						</div>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top" align="right">Description</td>
					<td valign="top">
						<div class="ui input">
							<input type="text" <?=$disabled?> name="pipelinedesc" value="<?=$desc?>" size="60">
						</div>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top" align="right">Stats level</td>
					<td valign="top">
						<div class="field">
							<div class="ui radio checkbox">
								<input type="radio" name="level" id="level1" value="1" <?=$disabled?> <? if ($level == 1) echo "checked"; ?>>
								<label>First <span class="tiny">subject level</span></label>
							</div>
						</div>
						<div class="field">
							<div class="ui radio checkbox">
								<input type="radio" name="level" id="level2" value="2" <?=$disabled?> <? if ($level == 2) echo "checked"; ?>>
								<label>Second <span class="tiny">group level</span></label>
							</div>
						</div>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top" align="right">Directory</td>
					<td valign="top">
						<div class="ui input">
							<input type="text" name="pipelinedirectory" <?=$disabled?> value="<?=$directory?>" maxlength="255" size="60" <? if ($type == "edit") { echo "readonly style='background-color: #EEE;"; } ?> >
						</div>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top" align="right">Directory structure</td>
					<td valign="top">
						<div class="ui fluid selection dropdown">
							<input type="hidden" name="pipelinedirstructure" value="<?=$dirstructure?>">
							<i class="dropdown icon"></i>
							<div class="default text">Directory...</div>
							<div class="scrollhint menu">
								<div class="item" data-value="a"><tt><?=$GLOBALS['cfg']['analysisdir']?></tt> <div class="ui label">/S1234ABC/1/<b>ThePipeline</b></div></div>
								<div class="item" data-value="b"><tt><?=$GLOBALS['cfg']['analysisdirb']?></tt> <div class="ui label">/<b>ThePipeline</b>/S1234ABC/1</div></div>
								<?
									$sqlstring = "select * from analysisdirs order by shortname";
									$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
									while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
										$analysisdirid = $row['analysisdir_id'];
										$shortname = $row['shortname'];
										$nidbpath = $row['nidbpath'];
										$clusterpath = $row['clusterpath'];
										$dirformat = $row['dirformat'];
										if ($dirformat == "uidfirst") {
											$dispformat = "/S1234ABC/1/<b>ThePipeline</b>";
										}
										else {
											$dispformat = "/<b>ThePipeline</b>/S1234ABC/1";
										}
										?>
										<div class="item" data-value="<?=$analysisdirid?>"><tt><?=$clusterpath?></tt> <div class="ui label"><?=$dispformat?></div></div>
										<?
									}
								?>
							</div>
						</div>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top" align="right">Pipeline group</td>
					<td valign="top">
						<div class="ui input">
							<input type="text" name="pipelinegroup" list="grouplist" <?=$disabled?> value="<?=$pipelinegroup?>" maxlength="255" size="60">
						</div>
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
					<td class="label" valign="top" align="right">Notes</td>
					<td valign="top">
						<div class="ui input">
							<textarea name="pipelinenotes" <?=$disabled?> rows="8" cols="60"><?=$pipelinenotes?></textarea>
						</div>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top" align="right">Data transfer method</td>
					<td valign="top">
						<div class="field">
							<div class="ui radio checkbox">
								<input type="radio" name="pipelinedatacopymethod" id="datacopymethod1" value="nfs" <?=$disabled?> <? if (($datacopymethod == "nfs") || ($datacopymethod == "")) echo "checked"; ?>>
								<label>NFS <span class="tiny">default</span></label>
							</div>
						</div>
						<div class="field">
							<div class="ui radio checkbox">
								<input type="radio" name="pipelinedatacopymethod" id="datacopymethod2" value="scp" <?=$disabled?> <? if ($datacopymethod == "scp") echo "checked"; ?>>
								<label>scp <span class="tiny">requires passwordless ssh</span></label>
							</div>
						</div>
					</td>
				</tr>
				<tr class="level1">
					<td class="label" valign="top" align="right">Concurrent processes</td>
					<td valign="top">
						<div class="ui input">
							<input type="number" name="pipelinenumproc" <?=$disabled?> value="<?=$numproc?>" min="1" max="350">
						</div>
						<?
							if ($numproc > 1) {
								$checks['Concurrent jobs']['level'] = 'ok';
								$checks['Concurrent jobs']['message'] = "More than 1 concurrent process";
								$checks['Concurrent jobs']['description'] = "Jobs will run in parallel";
							}
							else {
								$checks['Concurrent jobs']['level'] = 'warning';
								$checks['Concurrent jobs']['message'] = "Only 1 concurrent process allowed";
								$checks['Concurrent jobs']['description'] = "Setting <tt>concurrent processes</tt> to 1 will only allow one cluster job to run at a time. Each job must finish before another can start. If a job errors or gets stuck, no other jobs will run.";
							}
						?>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top" align="right">Use temporary directory<br><span class="tiny">Usually <tt>/tmp</tt>. Check with your sysadmin</span></td>
					<td valign="top">
						<div class="ui checkbox">
							<input type="checkbox" name="pipelineusetmpdir" <?=$disabled?> value="1" <? if ($usetmpdir == "1") { echo "checked"; } ?>>
						</div>
						<div class="ui input">
							<input type="text" name="pipelinetmpdir" <?=$disabled?> value="<?=$tmpdir?>" size="60" placeholder="/path/to/tmp/dir">
						</div>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top" align="right">Hidden?</td>
					<td valign="top" title="<b>Hidden</b><br><br>Useful to hide a pipeline from the main pipeline list. The pipeline still exists, but it won't show up">
						<div class="ui checkbox">
							<input type="checkbox" name="pipelineishidden" value="1" <? if ($ishidden) { echo "checked"; } ?>>
						</div>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<h4 class="ui horizontal divider header">
							<i class="server icon"></i>
							Cluster options
						</h4>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top" align="right">Cluster type</td>
					<td valign="top">
						<div class="ui selection dropdown">
							<input type="hidden" name="pipelineclustertype" id="pipelineclustertype" <?=$disabled?> value="<?=$clustertype?>" onChange="CheckHostnameStatus()">
							<i class="dropdown icon"></i>
							<div class="default text">Cluster...</div>
							<div class="scrollhint menu">
								<div class="item" data-value="slurm">slurm</div>
								<div class="item" data-value="sge">sge</div>
							</div>
						</div>
					</td>
				</tr>
				<?
					if ($submithost == "") {
						$checks['Cluster submit host']['level'] = 'error';
						$checks['Cluster submit host']['message'] = "Cluster submit host is blank";
						$checks['Cluster submit host']['description'] = "The cluster submit host must be specified. This is the hostname of the server to which cluster jobs are submitted.";
					}
					else {
						$checks['Cluster submit host']['level'] = 'ok';
					}
					
					if ($submithost == "") {
						$checks['Cluster submit host username']['level'] = 'error';
						$checks['Cluster submit host username']['message'] = "Cluster submit host username is blank";
						$checks['Cluster submit host username']['description'] = "The cluster submit host username must be specified. This is the username used to login to the submit server to submit jobs.";
					}
					else {
						$checks['Cluster submit host username']['level'] = 'ok';
					}

					if ($clusteruser == "") {
						$checks['Cluster user']['level'] = 'error';
						$checks['Cluster user']['message'] = "Cluster user is blank";
						$checks['Cluster user']['description'] = "The cluster user must be specified. This is the username under which a job is run on the cluster.";
					}
					else {
						$checks['Cluster user']['level'] = 'ok';
					}
					
					if ($queue == "") {
						$checks['Cluster queue']['level'] = 'error';
						$checks['Cluster queue']['message'] = "Cluster queue is blank";
						$checks['Cluster queue']['description'] = "The cluster queue must be specified. This is the queue (SGE) or partition (slurm) under which a job is run on the cluster.";
					}
					else {
						$checks['Cluster queue']['level'] = 'ok';
					}
					
					if ($numcores < 1) {
						$checks['Number of cores']['level'] = 'error';
						$checks['Number of cores']['message'] = "Cluster number of cores is not specified";
						$checks['Number of cores']['description'] = "This the number of cores allocated per job on the cluster. This must be specified when submitting to a slurm cluster.";
					}
					else {
						$checks['Number of cores']['level'] = 'ok';
					}
					
					if ($memory == "") {
						$checks['Memory']['level'] = 'error';
						$checks['Memory']['message'] = "Cluster memory is blank";
						$checks['Memory']['description'] = "This is the memory needed (in GB) by each job submitted to the cluster. This must be specified when submitting to a slurm cluster.";
					}
					else {
						$checks['Memory']['level'] = 'ok';
					}
					
				?>
				<tr>
					<td class="label" valign="top" align="right">Cluster user</td>
					<td valign="top">
						<div class="ui input">
							<input type="text" name="pipelineclusteruser" <?=$disabled?> value="<?=$clusteruser?>" id="pipelineclusteruser" onChange="CheckHostnameStatus()">
						</div>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top" align="right">Submit hostname</td>
					<td valign="top">
						<div class="ui error input" id="pipelinesubmithostinput">
							<input type="text" name="pipelinesubmithost" id="pipelinesubmithost" <?=$disabled?> value="<?=$submithost?>" onChange="CheckHostnameStatus()" onLoad="CheckHostnameStatus()">
							<div id="hostup"></div>
						</div>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top" align="right">Submit host username</td>
					<td valign="top">
						<div class="ui input">
							<input type="text" name="pipelinesubmithostuser" <?=$disabled?> value="<?=$submithostuser?>" id="pipelinesubmithostuser" onChange="CheckHostnameStatus()">
						</div>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top" align="right">Queue(s)<br><span class="tiny">Comma separated list</span></td>
					<td valign="top">
						<div class="ui input">
							<input type="text" name="pipelinequeue" <?=$disabled?> value="<?=$queue?>" required>
						</div>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top" align="right">Number of cores per job</td>
					<td valign="top">
						<div class="ui right labeled input">
							<input type="number" name="pipelinenumcores" <?=$disabled?> value="<?=$numcores?>">
							<div class="ui basic label">cores</div>
						</div>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top" align="right">Memory</td>
					<td valign="top">
						<div class="ui right labeled input">
							<input type="number" name="pipelinememory" step="0.1" <?=$disabled?> value="<?=$memory?>">
							<div class="ui basic label">GB</div>
						</div>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top" align="right">Max wall time</td>
					<td valign="top">
						<div class="ui right labeled input">
							<input type="number" name="pipelinemaxwalltime" <?=$disabled?> value="<?=$maxwalltime?>">
							<div class="ui basic label">mins</div>
						</div>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top" align="right">Submit delay</td>
					<td valign="top">
						<div class="ui right labeled input">
							<input type="number" name="pipelinesubmitdelay" <?=$disabled?> value="<?=$submitdelay?>">
							<div class="ui basic label">hrs</div>
						</div>
					</td>
				</tr>
				
				<tr>
					<td colspan="2" align="right">
						<br>
						<button class="ui primary button" type="submit" <?=$disabled?>><?=$submitbuttonlabel?></button>
					</td>
				</tr>
				</form>
			</table>
		</div>

		<!-- -------------------- Data & Scripts tab -------------------- -->
		
		<? if ($type != "add") { ?>
		<div class="ui bottom attached <?=$tab_threeactive?> tab raised segment" data-tab="third">
			<form method="post" action="pipelines.php" name="stepsform" id="stepsform" class="ui form">
			<input type="hidden" name="action" value="updatepipelineoptions">
			<input type="hidden" name="id" value="<?=$id?>">
			<input type="hidden" name="returntab" value="datascripts">
			<?
				//if (($level == 1) || (($level == 2) && ($dependency == ''))) {
			?>
			
			<div class="ui blue secondary top attached segment">
				<h3 class="ui header">Options</h3>
			</div>
			<div class="ui attached segment">
				<table class="entrytable ui table">
					<tr>
						<td class="label" valign="top">
							Successful files <i class="grey question outline circle icon" title="<b>Successful files</b><br><br>The analysis is marked as successful if ALL of the files specified exist at the end of the analysis. If left blank, the analysis will always be marked as successful.<br>Example: <tt>analysis/T1w/T1w_acpc_dc_restore_brain.nii.gz</tt>"></i>
						</td>
						<td valign="top">
							<textarea name="completefiles" <?=$disabled?> rows="4" cols="60"><?=$completefiles?></textarea><br>
							<span class="tiny">Comma seperated list of files (relative paths)</span>
						</td>
					</tr>
					<tr>
						<td class="label" valign="top">
							Results script <i class="grey question outline circle icon" title="<b>Results script</b><br><br>This script will be executed last and can be re-run separate from the analysis pipeline. The results script would often be used to create thumbnails of images and parse text files, and reinsert those results back into the database. The same pipeline variables available in the script command section below are available here to be passed as parameters to the results script"></i>
						</td>
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
									<td valign="top" align="right" style="font-size:10pt; font-weight:bold;color: #555;">This pipeline depends on<br><span class="tiny">it is a child pipeline of...</span></td>
									<td valign="top">
										<select name="dependency[]" id="dependency" <?=$disabled?> multiple="multiple" class="ui dropdown">
											<option value="" <? if ($dependency == "") { echo "selected"; } ?>>(Select dependency)</option>
											<?
												$sqlstring = "select * from pipelines order by pipeline_name";
												$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
												while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
													$d_name = $row['pipeline_name'];
													$d_id = $row['pipeline_id'];
													$d_ver = $row['pipeline_version'];
													
													if (($d_name != "") && ($d_id != "")) {
														if (in_array($d_id, explode(",",$dependency))) {
															$selected = "selected";
															$dependencies[] = $d_name;
														}
														else { $selected = ""; }
														
														if ($id != $d_id) {
															?>
															<option value="<?=$d_id?>" <?=$selected?>><?=$d_name?></option>
															<?
														}
													}
												}
											?>
										</select>
									</td>
								</tr>
								<tr>
									<td valign="top" align="right" style="font-size:10pt; font-weight:bold;color: #555;">Matching criteria</td>
									<td valign="top">
										<div class="ui radio checkbox">
											<input type="radio" name="deplevel" id="deplevel" value="study" <?=$disabled?> <? if (($deplevel == "study") || ($deplevel == "")) { echo "checked"; } ?>>
											<label>study (Recommended)<i class="question circle icon" title="Use dependencies from same study (RECOMMENDED)"></i></label>
										</div>
										<br>
										<div class="ui radio checkbox">
											<input type="radio" name="deplevel" id="deplevel2" value="subject" <?=$disabled?> <? if ($deplevel == "subject") { echo "checked"; } ?>>
											<label>subject (Must be used with group option)<i class="question circle icon" title="Use dependencies from same subject (other studies). ** This option should be used with groups **"></i></label>
										</div>
									</td>
								</tr>
								<tr>
									<td valign="top" align="right" style="font-size:10pt; font-weight:bold;color: #555;">Directory</td>
									<td valign="top">
										<div class="ui radio checkbox">
											<input type="radio" name="depdir" value="root" <?=$disabled?> <? if (($depdir == "root") || ($depdir == "")) { echo "checked"; } ?>>
											<label>root directory <i class="question circle icon" title="copies all files into the analysis root directory <tt>{analysisrootdir}/*</tt>"></i></label>
										</div>
										<br>
										<div class="ui radio checkbox">
											<input type="radio" name="depdir" value="subdir" <?=$disabled?> <? if ($depdir == "subdir") { echo "checked"; } ?>>
											<label>sub-directory <i class="question circle icon" title="copies dependency into a subdirectory of the analysis <tt>{analysisrootdir}/<i>DependencyName</i>/*</tt>"></i></label>
										</div>
									</td>
								</tr>
								<tr>
									<td valign="top" align="right" style="font-size:10pt; font-weight:bold;color: #555;">File linking type</td>
									<td valign="top">
										<div class="ui radio checkbox">
											<input type="radio" name="deplinktype" value="hardlink" <?=$disabled?> <? if (($deplinktype == "hardlink") || ($deplinktype == "")) { echo "checked"; } ?>>
											<label>hard link</label>
										</div>
										<br>
										<div class="ui radio checkbox">
											<input type="radio" name="deplinktype" value="softlink" <?=$disabled?> <? if ($deplinktype == "softlink") { echo "checked"; } ?>>
											<label>soft link</label>
										</div>
										<br>
										<div class="ui radio checkbox">
											<input type="radio" name="deplinktype" value="regularcopy" <?=$disabled?> <? if ($deplinktype == "regularcopy") { echo "checked"; } ?>>
											<label>Regular copy</label>
										</div>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr class="level1">
						<td class="label" valign="top">
							Study Group(s) <i class="grey question outline circle icon" title="Perform this analysis ONLY on the studies in the specified groups"></i><br>
							<span class="level2" style="color:darkred; font-size:8pt; font-weight:normal"> Second level must have<br> at least one group.<br>Group(s) must be identical to<br>first level <b>dependency's</b> group(s)</span>
						</td>
						<td valign="top" class="right red marked">
							
							<select name="groupid[]" id="groupid" <?=$disabled?> multiple="multiple" class="ui dropdown">
								<option value="" <? if ($groupid == "") { echo "selected"; } ?>>(Select group)</option>
								<?
									$sqlstring = "select * from groups where group_type = 'study' order by group_name";
									$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
									while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
										$g_name = $row['group_name'];
										$g_id = (int)$row['group_id'];
										
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
							<div class="ui basic red left pointing label">If a group and project are selected, then only studies that exist in BOTH will be selected to run</div>
						</td>
					</tr>
					<tr class="level1">
						<td class="label" valign="top">Project(s) <i class="grey question outline circle icon" title="Perform this analysis ONLY<br>on the studies in the specified project(s)"></i></td>
						<td valign="top" class="right red marked">
							<select name="projectid[]" id="projectid" <?=$disabled?> multiple="multiple" class="ui dropdown">
								<option value="" <? if ($projectid == "") { echo "selected"; } ?>>(Select project)</option>
								<?
									$sqlstring = "select * from projects order by project_name";
									$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
									while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
										$p_name = $row['project_name'];
										$p_id = $row['project_id'];
										
										if (in_array($p_id, explode(",",$projectid))) { $selected = "selected"; }
										else { $selected = ""; }
										?>
										<option value="<?=$p_id?>" <?=$selected?>><?=$p_name?></option>
										<?
									}
								?>
							</select><br>
						</td>
					</tr>
					<tr class="level2">
						<td class="label" valign="top">
							Group by Subject <i class="help icon" title="Group by Subject - Useful for longitudinal analyses. Second level pipelines only"></i><br>
							<span class="level2" style="color:darkred; font-size:8pt; font-weight:normal"> Second level only</span>
						</td>
						<td valign="top" title="<b>Group by Subject</b><br><br>Useful for longitudinal studies"><input type="checkbox" name="groupbysubject" value="1" <? if ($groupbysubject) { echo "checked"; } ?>></td>
					</tr>
				</table>
			</div>

			<div class="ui blue secondary attached segment">
				<h3 class="ui header">Data</h3>
			</div>
			<div class="ui attached fitted segment">
				<div class="ui right close rail" style="width: 350px">
					<div class="ui blue segment" id="exportTreePanel">
						<h3 class="ui header">
							Sample data export
							<div class="sub header">Sample directories/files for each analysis (relative to the analysis root). Uses placeholder subject <tt>S1234ABC</tt>, study <tt>1</tt>, series <tt>3</tt> and <tt>7</tt>. Multiple output files displayed if <i>series criteria</i> is <b>All</b></div>
						</h3>
						<pre id="exportTree"></pre>
						<div id="exportTreeWarnings"></div>
					</div>
				</div>

				<table>
					<tr>
						<td>
							<div class="ui accordion">
								<div class="title">
									<i class="dropdown icon"></i> Data Graph
								</div>
								<div class="content">
									<?=GetDataGraph($id, $version, $dependencies)?>
								</div>
							</div>
						</td>
						<td>
							<script>
								/* estimate how many studies the pipeline would analyze, using the current (unsaved) form values */
								function TestDataSearch() {
									$('#searchwaiting').html("Searching... <img src='images/SpinningSquirrel.gif'>");
									$('#testsearchresult').html("");

									var form = $('#stepsform');
									$.post("ajaxapi.php", {
										action: "pipelinetestsearch",
										pipelineid: <?=(int)$id?>,
										dependency: ($('#dependency').val() || []).join(),
										deplevel: form.find("input[name='deplevel']:checked").val() || "",
										groupid: ($('#groupid').val() || []).join(),
										projectid: ($('#projectid').val() || []).join(),
										datasteps: JSON.stringify(ReadDataSteps())
									})
									.done(function(html) {
										$('#testsearchresult').html(html);
										$('#searchwaiting').html("Done searching");
									})
									.fail(function(xhr, status, error) {
										$('#searchwaiting').text("Search failed: " + (error || status));
									});
								}
							</script>
							<div class="ui accordion">
								<div class="title">
									<i class="dropdown icon"></i> Test Search<br><span class="tiny">Check how many studies will be found based on the search criteria. This search has a 30 sec time limit.</span>
								</div>
								<div class="content">
									<input type="button" value="Run test search" onClick="TestDataSearch()"> <span id="searchwaiting" style="color: red; font-weight: bold;"></span>
									<br>
									<div id="testsearchresult" style="padding: 15px;"></div>
								</div>
							</div>
						</td>
					</tr>
				</table>
				
				<script>
					$(document).ready(function(){
						/* if the options TD is clicked */
						$(".optionToggler").click(function(e){
							e.preventDefault();
							$('.optionRow'+$(this).attr('data-prod-row')).toggle();
							$('.optionRow'+$(this).attr('data-prod-row')).toggleClass("yellow");
							$(this).toggleClass("yellow");
						});
					});
				</script>
				
				<table class="level1 ui very compact celled table">
					<thead>
						<th>Enabled</th>
						<th>Optional</th>
						<th title="<b>Primary</b> This flag indicates which criteria will specify the primary study which will be later used in the analysis listing. The primary study determines the primary modality, which also determines how other modalities and data steps are related to the primary study. Primary study also determines how dependent pipelines act.">Primary <i class="grey question outline circle icon"></i></td>
						<th>Order</th>
						<th>Protocol</th>
						<th>Modality</th>
						<th>
							Output &nbsp; <input type="checkbox" name="outputbids" value="1" <? if ($outputbids) { echo "checked"; } ?>> BIDS<i class="grey question outline circle icon" title="If this option is checked, all data will be written in BIDS format. Output formats for individual data items will be ignored."></i>
							<div class="ui small input">
								<input type="text" name="bidsoutputdir" value="<?=$bidsoutputdir?>" placeholder="BIDS output directory">
							</div>
						</th>
					</thead>
				<?
				$neworder = 1;
				$hasprimary = false;
				$blankmodality = true;
				/* display all other rows, sorted by order */
				$sqlstring = "select * from pipeline_data_def where pipeline_id = $id and pipeline_version = $version order by pdd_order + 0";
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$pipelinedatadef_id = $row['pipelinedatadef_id'];
					$dd_order = $row['pdd_order'];
					$dd_isprimaryprotocol = $row['pdd_isprimaryprotocol'];
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
					$dd_behonly = $row['pdd_behonly'];
					$dd_behformat = $row['pdd_behformat'];
					$dd_behdir = $row['pdd_behdir'];
					$dd_numboldreps = $row['pdd_numboldreps'];
					$dd_enabled = $row['pdd_enabled'];
					$dd_assoctype = $row['pdd_assoctype'];
					$dd_optional = $row['pdd_optional'];
					$dd_datalevel = $row['pdd_level'];
					$dd_numimagescriteria = $row['pdd_numimagescriteria'];
					
					$dd[$dd_order]['isprimaryprotocol'] = $row['pdd_isprimaryprotocol'];
					$dd[$dd_order]['seriescriteria'] = $row['pdd_seriescriteria'];
					$dd[$dd_order]['protocol'] = $row['pdd_protocol'];
					$dd[$dd_order]['modality'] = $row['pdd_modality'];
					$dd[$dd_order]['dataformat'] = $row['pdd_dataformat'];
					$dd[$dd_order]['imagetype'] = $row['pdd_imagetype'];
					$dd[$dd_order]['gzip'] = $row['pdd_gzip'];
					$dd[$dd_order]['location'] = $row['pdd_location'];
					$dd[$dd_order]['useseries'] = $row['pdd_useseries'];
					$dd[$dd_order]['preserveseries'] = $row['pdd_preserveseries'];
					$dd[$dd_order]['usephasedir'] = $row['pdd_usephasedir'];
					$dd[$dd_order]['behonly'] = $row['pdd_behonly'];
					$dd[$dd_order]['behformat'] = $row['pdd_behformat'];
					$dd[$dd_order]['behdir'] = $row['pdd_behdir'];
					$dd[$dd_order]['numboldreps'] = $row['pdd_numboldreps'];
					$dd[$dd_order]['enabled'] = $row['pdd_enabled'];
					$dd[$dd_order]['assoctype'] = $row['pdd_assoctype'];
					$dd[$dd_order]['optional'] = $row['pdd_optional'];
					$dd[$dd_order]['datalevel'] = $row['pdd_level'];
					$dd[$dd_order]['numimagescriteria'] = $row['pdd_numimagescriteria'];

					if ($dd_isprimaryprotocol) $hasprimary = true;
					if ($dd_modality) $blankmodality = false;
					
					?>
					<style>
						.row1 { background-color: lightyellow; }
					</style>
					<tr class="row<?=$neworder?> ui top aligned">
						<td style="border-top: 2px solid #999;" class="center aligned middle aligned">
							<div class="ui checkbox">
								<input type="checkbox" name="dd_enabled[<?=$neworder?>]" value="1" <? if ($dd_enabled) {echo "checked";} ?>><label></label>
							</div>
						</td>
						<td style="border-top: 2px solid #999;" class="center aligned middle aligned">
							<div class="ui checkbox">
								<input type="checkbox" name="dd_optional[<?=$neworder?>]" value="1" <? if ($dd_optional) { echo "checked"; } ?>><label></label>
							</div>
						</td>
						<td style="border-top: 2px solid #999;" class="center aligned middle aligned">
							<div class="ui radio checkbox">
								<input type="radio" name="dd_isprimary" value="<?=$neworder?>" <? if ($dd_isprimaryprotocol) { echo "checked"; } ?>><label></label>
							</div>
						</td>
						<td style="border-top: 2px solid #999;" class="center aligned middle aligned">
							<input type="text" name="dd_order[<?=$neworder?>]" size="2" maxlength="3" value="<?=$neworder?>">
						</td>
						<td style="border-top: 2px solid #999;" class="center aligned middle aligned">
							<input type="text" name="dd_protocol[<?=$neworder?>]" size="50" value='<?=$dd_protocol?>' title='Enter exact protocol name(s). Use quotes if entering a protocol with spaces or entering more than one protocol: "Task1" "Task 2" "Etc". Use multiple protocol names ONLY if you do not expect the protocols to occur in the same study'>
						</td>
						<td id="row<?=$neworder?>" style="border-top: 2px solid #999;">
							<select class="ui fluid dropdown" name="dd_modality[<?=$neworder?>]">
								<option value="">Modality...</option>
							<?
								$sqlstringA = "select * from modalities order by mod_desc";
								$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
								while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
									$mod_code = $rowA['mod_code'];
									$mod_desc = $rowA['mod_desc'];
									
									/* check if the modality table exists */
									$seriestable = GetSeriesTableName($mod_code);
									$numtables = 0;
									if ($seriestable != '') {
										$sqlstring2 = "select table_name from information_schema.tables where table_schema = ? and table_name = ?";
										$stmt2 = mysqli_prepare($GLOBALS['linki'], $sqlstring2);
										mysqli_stmt_bind_param($stmt2, 'ss', $GLOBALS['cfg']['mysqldatabase'], $seriestable);
										$result2 = MySQLiBoundQuery($stmt2, __FILE__, __LINE__, $sqlstring2, [$GLOBALS['cfg']['mysqldatabase'], $seriestable]);
										$numtables = mysqli_num_rows($result2);
										mysqli_stmt_close($stmt2);
									}
									if ($numtables > 0) {
									
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
						<td style="border-top: 2px solid #999; border-bottom: 0px" class="left aligned optionToggler" data-prod-row="<?=$neworder?>">
							<div class="ui accordion">
								<div class="title">
									<i class="dropdown icon"></i> Options
								</div>
								<div class="content">
								</div>
							</div>
						</td>
					</tr>
					<tr class="optionRow<?=$neworder?>" style="display:none">
						
						<script>
							$(document).ready(function() {
								$('#studycriteria<?=$neworder?>').hide();
								$('#numboldreps<?=$neworder?>').hide();
								$('#behdirname<?=$neworder?>').hide();
								$('#preserveseries<?=$neworder?>').hide();
								
								ShowHideOptions<?=$neworder?>();
							});
							
							function ShowHideOptions<?=$neworder?>() {
								
								/* study criteria */
								var element = document.getElementById('dd_datalevel<?=$neworder?>');
								if (element.value == "subject") {
									$('#studycriteria<?=$neworder?>').show();
								}
								else {
									$('#studycriteria<?=$neworder?>').hide();
								}

								/* num bold reps */
								var element2 = document.getElementById('dd_seriescriteria<?=$neworder?>');
								if (element2.value == "usesizecriteria") {
									$('#numboldreps<?=$neworder?>').show();
								}
								else {
									$('#numboldreps<?=$neworder?>').hide();
								}
								
								/* preserve series numbers, only used with series directories */
								if (document.getElementById('dd_useseriesdirs<?=$neworder?>').checked) {
									$('#preserveseries<?=$neworder?>').show();
								}
								else {
									$('#preserveseries<?=$neworder?>').hide();
								}

								/* beh directory */
								var element3 = document.getElementById('dd_behformat<?=$neworder?>');
								if (element3.value == "behrootdir" || element3.value == "behseriesdir") {
									$('#behdirname<?=$neworder?>').show();
								}
								else {
									$('#behdirname<?=$neworder?>').hide();
								}
							}
						</script>
						
						<td colspan="7" style="padding: 20px; border-top: 0px">
							<div class="ui grid">
								<div class="eight wide column">
									<h3 class="ui blue header">Search criteria</h3>
								
									<div class="two fields">
										<div class="field">
											<label>Data source <i class="grey question outline circle icon" title="<b>Data Source - Should we search only within this study or search within the entire subject?</b><br>Analyses are run on the <u>study</u> level. If you want data from this <u>subject</u>, but the data was collected in a different study, select the Subject data level. For example, the subject has been scanned on three different dates but only one of them has a T1."></i></label>
											<div class="ui fluid search selection dropdown">
												<input type="hidden" name="dd_datalevel[<?=$neworder?>]" id="dd_datalevel<?=$neworder?>" onChange="ShowHideOptions<?=$neworder?>()" value="<?=$dd_datalevel?>" onLoad="ShowHideOptions<?=$neworder?>()">
												<div class="default text">Where to search for data?</div>
												<i class="dropdown icon"></i>
												<div class="menu">
													<div class="item" data-value="study">Only this <b>study</b></div>
													<div class="item" data-value="subject">The entire <b>subject</b></div>
												</div>
											</div>
										</div>
										
										<div class="field" id="studycriteria<?=$neworder?>">
											<label>Study criteria <i class="grey question outline circle icon" title="<b>Data Level</b><br>Only use this option to search for your data in another study (same subject)"></i></label>
											<div class="ui fluid search selection dropdown">
												<input type="hidden" name="dd_studyassoc[<?=$neworder?>]" value="<?=$dd_assoctype?>">
												<div class="default text">Which studies to search?</div>
												<i class="dropdown icon"></i>
												<div class="menu">
													<div class="item" data-value="nearestintime">Search only the study <b>nearest in time</b></div>
													<div class="item" data-value="samestudytype">Search only studies with the <b>same VisitType</b></div>
													<div class="item" data-value="entiresubject">Search <b>all studies</b></div>
												</div>
											</div>
										</div>
									</div>
									<div class="field">
										<label>Image type <i class="grey question outline circle icon" title="Comma separated list of image types. Useful to differentiate intensity normalized vs non-normalized data on Siemens MRIs. For example <tt>ORIGINALPRIMARYMND</tt> or <tt>ORIGINAL/PRIMARY/M/ND/NORM</tt>"></i></label>
										<input type="text" name="dd_imagetype[<?=$neworder?>]" value="<?=$dd_imagetype?>">
									</div>
									<div class="two fields">
										<div class="field">
											<label>Series criteria <i class="grey question outline circle icon" title="<b>All</b> - All matching series will be downloaded<br><b>First</b> - Only the lowest numbered series will be downloaded<br><b>Last</b> - Only the highest numbered series will be downloaded<br><b>Largest</b> - Only one series with the most number of volumes or slices will be downloaded<br><b>Smallest</b> - Only one series with the least number of volumes or slices will be downloaded"></i></label>
											<select name="dd_seriescriteria[<?=$neworder?>]" class="ui dropdown" id="dd_seriescriteria<?=$neworder?>" onChange="ShowHideOptions<?=$neworder?>()">
												<option value="all" <? if ($dd_seriescriteria == "all") { echo "selected"; } ?>>All</option>
												<option value="first" <? if ($dd_seriescriteria == "first") { echo "selected"; } ?>>First</option>
												<option value="last" <? if ($dd_seriescriteria == "last") { echo "selected"; } ?>>Last</option>
												<option value="largestsize" <? if ($dd_seriescriteria == "largestsize") { echo "selected"; } ?>>Largest</option>
												<option value="smallestsize" <? if ($dd_seriescriteria == "smallestsize") { echo "selected"; } ?>>Smallest</option>
												<option value="usesizecriteria" <? if ($dd_seriescriteria == "usesizecriteria") { echo "selected"; } ?>>Use num BOLD reps criteria</option>
											</select>
										</div>
										<div class="field" id="numboldreps<?=$neworder?>">
											<label>Number of BOLD reps <i class="grey question outline circle icon" title="<b>Must be an integer or a criteria:</b><ul><li><i>N</i> (exactly N)<li>> <i>N</i> (greater than)<li>>= <i>N</i> (greater than or equal to)<li>< <i>N</i> (less than)<li><= <i>N</i> (less than or equal to)<li>~ <i>N</i> (not)</ul>"></i></label>
											<input type="text" name="dd_numboldreps[<?=$neworder?>]" value="<?=$dd_numboldreps?>">
										</div>
									</div>
								</div>
								<div class="eight wide column">
									<h3 class="ui blue header">Output format</h3>

									<div class="field">
										<label>Directory <i class="grey question outline circle icon" title="<b>Tip:</b> choose a directory called 'data/<i>taskname</i>'. If converting data or putting into a new directory structure, this data directory can be used as a staging area and can then be deleted later in your script"></i> <span class="tiny">Relative to analysis root</span></label>
										<input type="text" name="dd_location[<?=$neworder?>]" size="30" value="<?=$dd_location?>">
									</div>
									<div class="field">
										<label>Data format</label>
										<select name="dd_dataformat[<?=$neworder?>]" class="ui fluid dropdown">
											<option value="native" <? if ($dd_dataformat == "native") { echo "selected"; } ?>>Native</option>
											<option value="dicom" <? if ($dd_dataformat == "dicom") { echo "selected"; } ?>>DICOM</option>
											<option value="nifti3d" <? if ($dd_dataformat == "nifti3d") { echo "selected"; } ?>>Nifti 3D</option>
											<option value="nifti4d" <? if ($dd_dataformat == "nifti4d") { echo "selected"; } ?>>Nifti 4D</option>
											<option value="analyze3d" <? if ($dd_dataformat == "analyze3d") { echo "selected"; } ?>>Analyze 3D</option>
											<option value="analyze4d" <? if ($dd_dataformat == "analyze4d") { echo "selected"; } ?>>Analyze 4D</option>
											<option value="bids" <? if ($dd_dataformat == "bids") { echo "selected"; } ?>>BIDS</option>
										</select>
									</div>
									<div class="field">
										<div class="ui checkbox">
											<input type="checkbox" name="dd_gzip[<?=$neworder?>]" value="1" <? if ($dd_gzip) {echo "checked";} ?>>
											<label>g-zip</label>
										</div>
									</div>
									<div class="field">
										<div class="ui checkbox">
											<input type="checkbox" name="dd_useseriesdirs[<?=$neworder?>]" id="dd_useseriesdirs<?=$neworder?>" onChange="ShowHideOptions<?=$neworder?>()" value="1" <? if ($dd_useseries) {echo "checked";} ?>>
											<label>Use series directories <i class="grey question outline circle icon" title="<b>Tip:</b> If you plan to download multiple series with the same name, you will want to use series directories. This option will place each series into its own directory (data/task/1, data/task/2, etc)"></i></label>
										</div>
									</div>
									<div class="field" id="preserveseries<?=$neworder?>" style="margin-left: 25px">
										<div class="ui checkbox">
											<input type="checkbox" name="dd_preserveseries[<?=$neworder?>]" value="1" <? if ($dd_preserveseries) {echo "checked";} ?>>
											<label>Preserve series numbers <i class="grey question outline circle icon" title="If data is placed in a series directory, check this box to preserve the original series number. Otherwise the series number directories will be sequential starting at 1, regardless of the orignal series number"></i></label>
										</div>
									</div>
									<div class="field">
										<div class="ui checkbox">
											<input type="checkbox" name="dd_usephasedir[<?=$neworder?>]" value="1" <? if ($dd_usephasedir) {echo "checked";} ?>>
											<label>Phase encoding direction <i class="grey question outline circle icon" title="<b>Phase Encoding Direction</b> If selected, it will write the data to a subdirectory corresponding to the acquired phase encoding direction: AP, PA, RL, LR, COL, ROW, unknownPE"></i></label>
										</div>
									</div>
									<div class="field">
										<div class="ui checkbox">
											<input type="checkbox" name="dd_behonly[<?=$neworder?>]" value="1" <? if ($dd_behonly) {echo "checked";} ?>>
											<label>Download behavioral data only</label>
										</div>
									</div>
									<div class="two fields">
										<div class="field">
											<label>Behavioral data directory format</label>
											<select name="dd_behformat[<?=$neworder?>]" class="ui fluid dropdown" id="dd_behformat<?=$neworder?>" onChange="ShowHideOptions<?=$neworder?>()">
												<option value="behnone" <? if ($dd_behformat == "behnone") { echo "selected"; } ?>>Don't download behavioral data</option>
												<option value="behroot" <? if ($dd_behformat == "behroot") { echo "selected"; } ?>>Place in root (file.log)</option>
												<option value="behrootdir" <? if ($dd_behformat == "behrootdir") { echo "selected"; } ?>>Place in directory in root (beh/file.log)</option>
												<option value="behseries" <? if ($dd_behformat == "behseries") { echo "selected"; } ?>>Place in series (2/file.log)</option>
												<option value="behseriesdir" <? if ($dd_behformat == "behseriesdir") { echo "selected"; } ?>>Place in directory in series (2/beh/file.log)</option>
											</select>
										</div>
										<div class="field" id="behdirname<?=$neworder?>">
											<label>Behavioral data directory name</label>
											<input type="text" name="dd_behdir[<?=$neworder?>]" value="<?=$dd_behdir?>">
										</div>
									</div>
								</div>
							</div>
						</td>
					</tr>
					<?
					$neworder++;
				}
				for ($ii=0;$ii<5;$ii++) {
				?>
					<tr class="row<?=$neworder?> ui top aligned">
						<td style="border-top: 2px solid #999;" class="center aligned middle aligned">
							<div class="ui checkbox">
								<input type="checkbox" name="dd_enabled[<?=$neworder?>]" value="1"><label></label>
							</div>
						</td>
						<td style="border-top: 2px solid #999;" class="center aligned middle aligned">
							<div class="ui checkbox">
								<input type="checkbox" name="dd_optional[<?=$neworder?>]" value="1"><label></label>
							</div>
						</td>
						<td style="border-top: 2px solid #999;" class="center aligned middle aligned">
							<div class="ui radio checkbox">
								<input type="radio" name="dd_isprimary" value="<?=$neworder?>"><label></label>
							</div>
						</td>
						<td style="border-top: 2px solid #999;">
							<input type="text" name="dd_order[<?=$neworder?>]" value="<?=$neworder?>" size="2" maxlength="3">
						</td>
						<td style="border-top: 2px solid #999;">
							<input type="text" name="dd_protocol[<?=$neworder?>]" size="50" title='Enter exact protocol name(s). Use quotes if entering a protocol with spaces or entering more than one protocol: "Task1" "Task 2" "Etc". Use multiple protocol names ONLY if you do not expect the protocols to occur in the same study'>
						</td>
						<td id="row<?=$neworder?>" style="border-top: 2px solid #999;">
							<select class="ui fluid dropdown" name="dd_modality[<?=$neworder?>]">
								<option value="">Modality...</option>
							<?
								$sqlstringA = "select * from modalities order by mod_desc";
								$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
								while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
									$mod_code = $rowA['mod_code'];
									$mod_desc = $rowA['mod_desc'];
									
									/* check if the modality table exists */
									$seriestable = GetSeriesTableName($mod_code);
									$numtables = 0;
									if ($seriestable != '') {
										$sqlstring2 = "select table_name from information_schema.tables where table_schema = ? and table_name = ?";
										$stmt2 = mysqli_prepare($GLOBALS['linki'], $sqlstring2);
										mysqli_stmt_bind_param($stmt2, 'ss', $GLOBALS['cfg']['mysqldatabase'], $seriestable);
										$result2 = MySQLiBoundQuery($stmt2, __FILE__, __LINE__, $sqlstring2, [$GLOBALS['cfg']['mysqldatabase'], $seriestable]);
										$numtables = mysqli_num_rows($result2);
										mysqli_stmt_close($stmt2);
									}
									if ($numtables > 0) {
										?>
										<option value="<?=$mod_code?>"><?=$mod_code?></option>
										<?
									}
								}
							?>
							</select>
						</td>
						<td style="border-top: 2px solid #999; border-bottom: 0px" class="left aligned optionToggler" data-prod-row="<?=$neworder?>">
							<div class="ui accordion">
								<div class="title">
									<i class="dropdown icon"></i> Options
								</div>
								<div class="content">
								</div>
							</div>
						</td>
					</tr>
					<tr class="optionRow<?=$neworder?>" style="display:none">
						
						<script>
							$(document).ready(function() {
								$('#studycriteria<?=$neworder?>').hide();
								$('#numboldreps<?=$neworder?>').hide();
								$('#behdirname<?=$neworder?>').hide();
								$('#preserveseries<?=$neworder?>').hide();
							});
							
							function ShowHideOptions<?=$neworder?>() {
								
								/* study criteria */
								var element = document.getElementById('dd_datalevel<?=$neworder?>');
								if (element.value == "subject") {
									$('#studycriteria<?=$neworder?>').show();
								}
								else {
									$('#studycriteria<?=$neworder?>').hide();
								}

								/* num bold reps */
								var element2 = document.getElementById('dd_seriescriteria<?=$neworder?>');
								if (element2.value == "usesizecriteria") {
									$('#numboldreps<?=$neworder?>').show();
								}
								else {
									$('#numboldreps<?=$neworder?>').hide();
								}
								
								/* preserve series numbers, only used with series directories */
								if (document.getElementById('dd_useseriesdirs<?=$neworder?>').checked) {
									$('#preserveseries<?=$neworder?>').show();
								}
								else {
									$('#preserveseries<?=$neworder?>').hide();
								}

								/* beh directory */
								var element3 = document.getElementById('dd_behformat<?=$neworder?>');
								if (element3.value == "behrootdir" || element3.value == "behseriesdir") {
									$('#behdirname<?=$neworder?>').show();
								}
								else {
									$('#behdirname<?=$neworder?>').hide();
								}
							}
						</script>
						
						<td colspan="7" style="padding: 20px; border-top: 0px">
							<div class="ui grid">
								<div class="eight wide column">
									<h3 class="ui blue header">Search criteria</h3>
								
									<div class="two fields">
										<div class="field">
											<label>Data source <i class="grey question outline circle icon" title="<b>Data Source - Should we search only within this study or search within the entire subject?</b><br>Analyses are run on the <u>study</u> level. If you want data from this <u>subject</u>, but the data was collected in a different study, select the Subject data level. For example, the subject has been scanned on three different dates but only one of them has a T1."></i></label>
											<div class="ui fluid search selection dropdown">
												<input type="hidden" name="dd_datalevel[<?=$neworder?>]" id="dd_datalevel<?=$neworder?>" onChange="ShowHideOptions<?=$neworder?>()">
												<div class="default text">Where to search for data?</div>
												<i class="dropdown icon"></i>
												<div class="menu">
													<div class="item" data-value="study">Only this <b>study</b></div>
													<div class="item" data-value="subject">The entire <b>subject</b></div>
												</div>
											</div>
										</div>
										
										<div class="field" id="studycriteria<?=$neworder?>">
											<label>Study criteria <i class="grey question outline circle icon" title="<b>Data Level</b><br>Only use this option to search for your data in another study (same subject)"></i></label>
											<div class="ui fluid search selection dropdown">
												<input type="hidden" name="dd_studyassoc[<?=$neworder?>]">
												<div class="default text">Which studies to search?</div>
												<i class="dropdown icon"></i>
												<div class="menu">
													<div class="item" data-value="nearestintime">Search only the study <b>nearest in time</b></div>
													<div class="item" data-value="samestudytype">Search only studies with the <b>same VisitType</b></div>
													<div class="item" data-value="entiresubject">Search <b>all studies</b></div>
												</div>
											</div>
										</div>
									</div>
									<div class="field">
										<label>Image type <i class="grey question outline circle icon" title="Comma separated list of image types. Useful to differentiate intensity normalized vs non-normalized data on Siemens MRIs. For example <tt>ORIGINALPRIMARYMND</tt> or <tt>ORIGINAL/PRIMARY/M/ND/NORM</tt>"></i></label>
										<input type="text" name="dd_imagetype[<?=$neworder?>]">
									</div>
									<div class="two fields">
										<div class="field">
											<label>Series criteria <i class="grey question outline circle icon" title="<b>All</b> - All matching series will be downloaded<br><b>First</b> - Only the lowest numbered series will be downloaded<br><b>Last</b> - Only the highest numbered series will be downloaded<br><b>Largest</b> - Only one series with the most number of volumes or slices will be downloaded<br><b>Smallest</b> - Only one series with the least number of volumes or slices will be downloaded"></i></label>
											<select name="dd_seriescriteria[<?=$neworder?>]" class="ui dropdown" id="dd_seriescriteria<?=$neworder?>" onChange="ShowHideOptions<?=$neworder?>()">
												<option value="all">All</option>
												<option value="first">First</option>
												<option value="last">Last</option>
												<option value="largestsize">Largest</option>
												<option value="smallestsize">Smallest</option>
												<option value="usesizecriteria">Use num BOLD reps criteria</option>
											</select>
										</div>
										<div class="field" id="numboldreps<?=$neworder?>">
											<label>Number of BOLD reps <i class="grey question outline circle icon" title="<b>Must be an integer or a criteria:</b><ul><li><i>N</i> (exactly N)<li>> <i>N</i> (greater than)<li>>= <i>N</i> (greater than or equal to)<li>< <i>N</i> (less than)<li><= <i>N</i> (less than or equal to)<li>~ <i>N</i> (not)</ul>"></i></label>
											<input type="text" name="dd_numboldreps[<?=$neworder?>]">
										</div>
									</div>
								</div>
								<div class="eight wide column">
									<h3 class="ui blue header">Output format</h3>

									<div class="field">
										<label>Directory <i class="grey question outline circle icon" title="<b>Tip:</b> choose a directory called 'data/<i>taskname</i>'. If converting data or putting into a new directory structure, this data directory can be used as a staging area and can then be deleted later in your script"></i> <span class="tiny">Relative to analysis root</span></label>
										<input type="text" name="dd_location[<?=$neworder?>]" size="30">
									</div>
									<div class="field">
										<label>Data format</label>
										<select name="dd_dataformat[<?=$neworder?>]" class="ui fluid dropdown">
											<option value="native">Native</option>
											<option value="dicom">DICOM</option>
											<option value="nifti3d">Nifti 3D</option>
											<option value="nifti4d">Nifti 4D</option>
											<option value="analyze3d">Analyze 3D</option>
											<option value="analyze4d">Analyze 4D</option>
											<option value="bids">BIDS</option>
										</select>
									</div>
									<div class="field">
										<div class="ui checkbox">
											<input type="checkbox" name="dd_gzip[<?=$neworder?>]" value="1">
											<label>g-zip</label>
										</div>
									</div>
									<div class="field">
										<div class="ui checkbox">
											<input type="checkbox" name="dd_useseriesdirs[<?=$neworder?>]" id="dd_useseriesdirs<?=$neworder?>" onChange="ShowHideOptions<?=$neworder?>()" value="1">
											<label>Use series directories <i class="grey question outline circle icon" title="<b>Tip:</b> If you plan to download multiple series with the same name, you will want to use series directories. This option will place each series into its own directory (data/task/1, data/task/2, etc)"></i></label>
										</div>
									</div>
									<div class="field" id="preserveseries<?=$neworder?>" style="margin-left: 25px">
										<div class="ui checkbox">
											<input type="checkbox" name="dd_preserveseries[<?=$neworder?>]" value="1">
											<label>Preserve series numbers <i class="grey question outline circle icon" title="If data is placed in a series directory, check this box to preserve the original series number. Otherwise the series number directories will be sequential starting at 1, regardless of the orignal series number"></i></label>
										</div>
									</div>
									<div class="field">
										<div class="ui checkbox">
											<input type="checkbox" name="dd_usephasedir[<?=$neworder?>]" value="1">
											<label>Phase encoding direction <i class="grey question outline circle icon" title="<b>Phase Encoding Direction</b> If selected, it will write the data to a subdirectory corresponding to the acquired phase encoding direction: AP, PA, RL, LR, COL, ROW, unknownPE"></i></label>
										</div>
									</div>
									<div class="field">
										<div class="ui checkbox">
											<input type="checkbox" name="dd_behonly[<?=$neworder?>]" value="1">
											<label>Download behavioral data only</label>
										</div>
									</div>
									<div class="two fields">
										<div class="field">
											<label>Behavioral data directory format</label>
											<select name="dd_behformat[<?=$neworder?>]" class="ui fluid dropdown" id="dd_behformat<?=$neworder?>" onChange="ShowHideOptions<?=$neworder?>()">
												<option value="behnone">Don't download behavioral data</option>
												<option value="behroot">Place in root (file.log)</option>
												<option value="behrootdir">Place in directory in root (beh/file.log)</option>
												<option value="behseries">Place in series (2/file.log)</option>
												<option value="behseriesdir">Place in directory in series (2/beh/file.log)</option>
											</select>
										</div>
										<div class="field" id="behdirname<?=$neworder?>">
											<label>Behavioral data directory name</label>
											<input type="text" name="dd_behdir[<?=$neworder?>]">
										</div>
									</div>
								</div>
							</div>
						</td>
					</tr>
					<? $neworder++; ?>
					<? } ?>
				</table>

				<style>
					#exportTreePanel { position: sticky; top: 10px; max-height: 95vh; overflow: auto; }
					#exportTree { font-size: 9pt; line-height: 1.35; margin: 0; white-space: pre; overflow-x: auto; }
					#exportTree .treedisabled { color: #bbb; }
					#exportTree .treedir { color: #2360a5; font-weight: bold; }
					#exportTree .treedir.treedisabled { color: #bbb; font-weight: normal; }
					#exportTree .treenote { color: #999; font-style: italic; }
					#exportTree .treeline { display: inline-block; min-width: 100%; }
					#exportTree .treeline.treehighlight { background-color: #e6e0fa; }
					#stepsform tr.stephighlight > td { border-top-color: #7e57c2 !important; }
					/* inset shadows for the side borders, so the table layout doesn't shift */
					#stepsform tr.stephighlight > td:first-child, #stepsform tr.stepoptionhighlight > td:first-child { box-shadow: inset 2px 0 0 #7e57c2; }
					#stepsform tr.stephighlight > td:last-child, #stepsform tr.stepoptionhighlight > td:last-child { box-shadow: inset -2px 0 0 #7e57c2; }
					#stepsform tr.stepoptionhighlight > td:only-child { box-shadow: inset 2px 0 0 #7e57c2, inset -2px 0 0 #7e57c2; }
				</style>
				<script>
					/* Simulates the directories/files that modulePipeline::GetData() (C++) creates for one analysis.
					   The path logic mirrors GetData(), GetBehPath(), imageIO::ConvertDicom(), BatchRenameFiles()
					   and archiveIO::WriteBIDS(), including their quirks, so keep them in sync */
					var simUID = "S1234ABC";
					var simStudyNum = 1;
					var simDicomModalities = ["MR", "CT", "PT", "NM", "US", "XA", "CR", "DX", "MG", "RF", "OT"];

					/* read all data steps from the form. Also used by TestDataSearch() */
					function ReadDataSteps() {
						var form = $('#stepsform');
						var steps = [];
						form.find("input[name^='dd_order[']").each(function() {
							var n = $(this).attr('name').match(/\[(\d+)\]/)[1];
							var val = function(name) { var e = form.find("[name='" + name + "[" + n + "]']"); return e.length ? $.trim(e.val() || "") : ""; };
							var chk = function(name) { return form.find("[name='" + name + "[" + n + "]']").is(':checked'); };
							var protocol = val('dd_protocol');
							if (protocol == "")
								return;
							steps.push({
								num: n,
								protocol: protocol,
								enabled: chk('dd_enabled'),
								optional: chk('dd_optional'),
								primary: (form.find("input[name='dd_isprimary']:checked").val() == n),
								modality: val('dd_modality'),
								datalevel: val('dd_datalevel'),
								studyassoc: val('dd_studyassoc'),
								imagetype: val('dd_imagetype'),
								numboldreps: val('dd_numboldreps'),
								seriescriteria: val('dd_seriescriteria'),
								location: val('dd_location'),
								dataformat: val('dd_dataformat'),
								gzip: chk('dd_gzip'),
								useseries: chk('dd_useseriesdirs'),
								preserveseries: chk('dd_preserveseries'),
								usephasedir: chk('dd_usephasedir'),
								behonly: chk('dd_behonly'),
								behformat: val('dd_behformat'),
								behdir: val('dd_behdir')
							});
						});
						return steps;
					}

					/* placeholder series numbers matching the series criteria */
					function SimSeriesNums(criteria) {
						if ((criteria == "first") || (criteria == "largestsize") || (criteria == "smallestsize"))
							return [3];
						if (criteria == "last")
							return [7];
						return [3, 7];
					}

					/* join path parts, dropping empty parts (like MakePath does with '//') */
					function SimJoin() {
						var parts = [];
						for (var i = 0; i < arguments.length; i++)
							String(arguments[i]).split('/').forEach(function(p) { if (p != "") parts.push(p); });
						return parts.join('/');
					}

					/* same logic as modulePipeline::GetBehPath() */
					function SimBehPath(behformat, location, behdir, seriesnum) {
						if (behformat == "behroot") return SimJoin(location);
						if (behformat == "behrootdir") return SimJoin(location, behdir);
						if (behformat == "behseries") return SimJoin(location, seriesnum);
						if (behformat == "behseriesdir") return SimJoin(location, seriesnum, behdir);
						return null;
					}

					/* build the flat list of {path, dir, note, enabled, step, series} entries */
					function BuildExportPaths(steps, outputbids, bidsoutputdir) {
						var entries = [];
						var warnings = [];
						var add = function(path, dir, note, step, series, beh) {
							entries.push({ path: path, dir: dir, note: note || "", enabled: step.enabled, step: step.num, series: series, beh: !!beh });
						};

						steps.forEach(function(step) {
							if (step.modality == "")
								warnings.push("Step " + step.num + " has no modality and will be skipped");
						});

						if (outputbids) {
							/* archiveIO::WriteBIDS() - per-step output options are ignored */
							var root = SimJoin(bidsoutputdir);
							var sub = "sub-" + simUID;
							var ses = "ses-" + simStudyNum;
							var any = false;
							steps.forEach(function(step) {
								if (step.modality == "")
									return;
								if (step.enabled) any = true;
								SimSeriesNums(step.seriescriteria).forEach(function(s) {
									var dir = SimJoin(root, sub, ses, "{entity}");
									var label = "{" + step.protocol.replace(/[^a-zA-Z0-9]/g, "") + " suffix}";
									add(dir, true, "entity/suffix/run come from the project BIDS mapping", step, s);
									add(SimJoin(dir, sub + "_" + ses + "_" + label + ".nii.gz"), false, "series " + s, step, s);
									add(SimJoin(dir, sub + "_" + ses + "_" + label + ".json"), false, "", step, s);
									add(SimJoin(root, "sourcedata", "beh", simUID, simStudyNum, s, "*"), false, "behavioral files, if present", step, s, true);
								});
							});
							if (any) {
								var enabledStep = { enabled: true, num: 0 };
								add(SimJoin(root, "dataset_description.json"), false, "", enabledStep, 0);
								add(SimJoin(root, "participants.tsv"), false, "", enabledStep, 0);
								add(SimJoin(root, "README"), false, "", enabledStep, 0);
							}
							else
								warnings.push("No enabled steps. BIDS export will fail (no series found)");
							return { entries: entries, warnings: warnings };
						}

						steps.forEach(function(step) {
							if (step.modality == "")
								return;

							var datatype = (simDicomModalities.indexOf(step.modality.toUpperCase()) >= 0) ? "dicom" : step.modality.toLowerCase();
							var newseriesnum = 1;

							SimSeriesNums(step.seriescriteria).forEach(function(seriesnum) {
								var imgdir = SimJoin(step.location);
								var behoutdir;

								if (step.useseries) {
									if (step.preserveseries) {
										imgdir = SimJoin(imgdir, seriesnum);
										behoutdir = SimBehPath(step.behformat, step.location, step.behdir, seriesnum);
									}
									else {
										imgdir = SimJoin(imgdir, newseriesnum);
										behoutdir = SimBehPath(step.behformat, step.location, step.behdir, newseriesnum);
										newseriesnum++;
									}
								}
								else
									behoutdir = SimBehPath(step.behformat, step.location, step.behdir, seriesnum);

								if (step.usephasedir)
									imgdir = SimJoin(imgdir, "AP");

								if (imgdir != "")
									add(imgdir, true, step.usephasedir ? "phase dir from data: AP|PA|RL|LR|COL|ROW|unknownPE" : "", step, seriesnum);

								if (!step.behonly) {
									var base = simUID + "_" + simStudyNum + "_" + seriesnum + "_";
									var gz = step.gzip ? ".gz" : "";
									if ((step.dataformat == "dicom") || ((datatype != "dicom") && (datatype != "parrec"))) {
										if (datatype == "dicom")
											add(SimJoin(imgdir, "*.dcm"), false, "archived DICOM files, series " + seriesnum, step, seriesnum);
										else
											add(SimJoin(imgdir, "*"), false, "archived " + datatype + " files, series " + seriesnum, step, seriesnum);
									}
									else if (step.dataformat == "nifti4d") {
										add(SimJoin(imgdir, base + "00001.nii" + gz), false, "", step, seriesnum);
									}
									else if (step.dataformat == "nifti3d") {
										add(SimJoin(imgdir, base + "00001.nii" + gz), false, "", step, seriesnum);
										add(SimJoin(imgdir, base + "00002.nii" + gz), false, "", step, seriesnum);
										add(SimJoin(imgdir, base + "NNNNN.nii" + gz), false, "one per volume", step, seriesnum);
									}
									else if (step.dataformat == "bids") {
										/* per-step BIDS: ConvertDicom() gets an empty subject/session/mapping */
										add(SimJoin(imgdir, "__.nii.gz"), false, "", step, seriesnum);
										add(SimJoin(imgdir, "__.json"), false, "", step, seriesnum);
									}
									else if (imgdir != "") {
										/* native/analyze3d/analyze4d: ConvertDicom() rejects the format, nothing is copied */
										add(imgdir, true, "empty: " + step.dataformat + " conversion not supported", step, seriesnum);
									}
								}

								if ((step.behformat != "behnone") && (behoutdir !== null)) {
									if (behoutdir != "")
										add(behoutdir, true, "", step, seriesnum, true);
									add(SimJoin(behoutdir, "*"), false, "behavioral files, if present", step, seriesnum, true);
								}
							});
						});

						/* warn when image files from different series land in the same directory */
						var dirsources = {};
						entries.forEach(function(e) {
							if (e.dir || !e.enabled || e.beh) return;
							var d = e.path.indexOf('/') >= 0 ? e.path.substring(0, e.path.lastIndexOf('/')) : "(analysis root)";
							var key = "step " + e.step + " series " + e.series;
							dirsources[d] = dirsources[d] || [];
							if (dirsources[d].indexOf(key) < 0) dirsources[d].push(key);
						});
						for (var d in dirsources) {
							if (dirsources[d].length > 1)
								warnings.push("<tt>" + d + "</tt> receives files from multiple series (" + dirsources[d].join(", ") + ")");
						}

						return { entries: entries, warnings: warnings };
					}

					function SimEscape(s) {
						return String(s).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
					}

					/* natural sort, like SortQStringListNaturally() */
					function SimCompare(a, b) {
						return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
					}

					/* render the entries like the Linux 'tree' command */
					function RenderTree(entries) {
						var root = { children: {} };
						var numdirs = 0, numfiles = 0;

						entries.forEach(function(e) {
							var parts = e.path.split('/');
							var node = root;
							parts.forEach(function(p, i) {
								var last = (i == parts.length - 1);
								if (!node.children[p]) {
									node.children[p] = { children: {}, dir: !last || e.dir, enabled: false, notes: [], steps: {} };
									if (node.children[p].dir) numdirs++; else numfiles++;
								}
								node = node.children[p];
								node.steps[e.step] = true;
								if (e.enabled) node.enabled = true;
								if (!last && !node.dir) { node.dir = true; numfiles--; numdirs++; }
							});
							if ((e.note != "") && (node.notes.indexOf(e.note) < 0))
								node.notes.push(e.note);
						});

						var lines = ['<span class="treedir">.</span>'];
						var walk = function(node, prefix) {
							var names = Object.keys(node.children).sort(SimCompare);
							names.forEach(function(name, i) {
								var child = node.children[name];
								var last = (i == names.length - 1);
								var cls = (child.dir ? "treedir" : "") + (child.enabled ? "" : " treedisabled");
								var line = SimEscape(prefix + (last ? "└── " : "├── ")) + '<span class="' + cls + '">' + SimEscape(name + (child.dir ? "/" : "")) + '</span>';
								if (!child.enabled)
									line += ' <span class="treenote">(disabled)</span>';
								if (child.notes.length > 0)
									line += ' <span class="treenote"># ' + SimEscape(child.notes.join("; ")) + '</span>';
								/* tag the line with the data step(s) that create it, for highlighting */
								lines.push('<span class="treeline" data-steps=" ' + Object.keys(child.steps).join(" ") + ' ">' + line + '</span>');
								walk(child, prefix + (last ? "    " : "│   "));
							});
						};
						walk(root, "");

						if (entries.length == 0)
							lines.push('<span class="treenote">(no data will be exported)</span>');
						lines.push("");
						lines.push(numdirs + " director" + (numdirs == 1 ? "y" : "ies") + ", " + numfiles + " file" + (numfiles == 1 ? "" : "s"));
						return lines.join("\n");
					}

					/* highlight the tree lines for the data step being edited, or else the hovered step */
					var hoverStep = null;
					var focusStep = null;
					function HighlightExportTree() {
						var step = (focusStep !== null) ? focusStep : hoverStep;
						$('#exportTree .treeline').removeClass('treehighlight');
						$('#stepsform tr.stephighlight').removeClass('stephighlight');
						$('#stepsform tr.stepoptionhighlight').removeClass('stepoptionhighlight');
						if (step !== null) {
							$('#exportTree .treeline[data-steps*=" ' + step + ' "]').addClass('treehighlight');
							$('#stepsform tr.row' + step).addClass('stephighlight');
							$('#stepsform tr.optionRow' + step).addClass('stepoptionhighlight');
						}
					}

					/* data step number of a table row (rowN or optionRowN), or null */
					function StepOfRow(tr) {
						var m = ($(tr).attr('class') || "").match(/(?:^|\s)(?:row|optionRow)(\d+)(?:\s|$)/);
						return m ? m[1] : null;
					}

					function UpdateExportTree() {
						var outputbids = $("#stepsform input[name='outputbids']").is(':checked');
						var bidsoutputdir = $.trim($("#stepsform input[name='bidsoutputdir']").val() || "");
						var result = BuildExportPaths(ReadDataSteps(), outputbids, bidsoutputdir);
						$('#exportTree').html(RenderTree(result.entries));
						HighlightExportTree();
						if (result.warnings.length > 0)
							$('#exportTreeWarnings').html('<div class="ui small warning message"><ul class="list"><li>' + result.warnings.join('</li><li>') + '</li></ul></div>');
						else
							$('#exportTreeWarnings').html('');
					}

					$(document).ready(function() {
						var timer = null;
						/* dropdowns update their hidden inputs asynchronously, so debounce and re-read the whole form */
						$('#stepsform').on('input change click keyup', function() {
							clearTimeout(timer);
							timer = setTimeout(UpdateExportTree, 150);
						});
						UpdateExportTree();

						$('#stepsform').on('mouseenter', 'tr', function() {
							var step = StepOfRow(this);
							if (step !== null) { hoverStep = step; HighlightExportTree(); }
						});
						$('#stepsform').on('mouseleave', 'tr', function() {
							if (StepOfRow(this) !== null) { hoverStep = null; HighlightExportTree(); }
						});
						$('#stepsform').on('focusin', 'tr', function(e) {
							var step = StepOfRow(this);
							if (step !== null) { focusStep = step; HighlightExportTree(); e.stopPropagation(); }
						});
						$('#stepsform').on('focusout', function() {
							/* wait for focus to settle on the next element before clearing */
							setTimeout(function() {
								if (!$(document.activeElement).closest('tr').filter(function() { return StepOfRow(this) !== null; }).length) {
									focusStep = null;
									HighlightExportTree();
								}
							}, 0);
						});
					});
				</script>
				<?
				//} /* end of the check to display the data specs */
				?>
			</div>
			
			<?
				if ($hasprimary == false) {
					$checks['Primary data item']['level'] = 'error';
					$checks['Primary data item']['message'] = "Primary data item not specified";
					$checks['Primary data item']['description'] = "A primary data item must be specified. This determines the root imaging study from which other data can be associated and downloaded.";
				}
				else {
					$checks['Primary data item']['level'] = 'ok';
					$checks['Primary data item']['description'] = "A primary data item must be specified. This determines the root imaging study from which other data can be associated and downloaded.";
				}
				
				if ($blankmodality == true) {
					$checks['Blank modality']['level'] = 'error';
					$checks['Blank modality']['message'] = "A data item is missing a modality";
					$checks['Blank modality']['description'] = "Modality must be specified for all data items.";
				}
				else {
					$checks['Blank modality']['level'] = 'ok';
					$checks['Blank modality']['description'] = "Modality must be specified for all data items";
				}
				
			?>

			<div class="ui blue secondary attached segment">
				<div class="ui two column grid">
					<div class="ui column">
						<h3 class="ui header">Main Script Commands &nbsp; <span class="tiny" style="font-weight:normal">Ctrl+S to save</span></h3>
					</div>
					<div class="ui right aligned column">
						<span id="syntaxstatus-main" style="font-weight:normal"></span> &nbsp;
						<div class="ui tiny basic button" title="Check the script for bash syntax errors and common mistakes. Command names are not checked, so a misspelled command is not reported" onClick="CheckAceSyntax(editor, 'main'); return;"><i class="check circle outline icon"></i>Check syntax</div>
						<div class="ui tiny basic button" onClick="toggleWrap(); return;">Toggle text wrap</div>
					</div>
				</div>
			</div>
			<div class="ui attached segment">
				<div class="ui right close rail">
					<div class="ui blue segment">
						<h3 class="ui header">Pipeline variables</h3>
						<span class="tiny">Click variable to insert at current editor location</span>
						<br><br>
						<table>
							<tr><td class="pipelinevariable" onclick="insertText('{analysisrootdir}');" title="Full path to the root directory of the analysis">{analysisrootdir}</td></tr>
							<tr><td class="pipelinevariable" onclick="insertText('{subjectuid}');" title="Example: S1234ABC">{subjectuid}</td></tr>
							<tr><td class="pipelinevariable" onclick="insertText('{studynum}');" title="Example: 1">{studynum}</td></tr>
							<tr><td class="pipelinevariable" onclick="insertText('{uidstudynum}');" title="Example: S1234ABC1">{uidstudynum}</td></tr>
							<tr><td class="pipelinevariable" onclick="insertText('{pipelinename}');" title="<?=$title?>">{pipelinename}</td></tr>
							<tr><td class="pipelinevariable" onclick="insertText('{studydatetime}');" title="YYYYMMDDHHMMSS">{studydatetime}</td></tr>
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
							<tr><td style="border-top: solid 1px #666" title="Expands to first file found with extenstion. Replace ext with the extension"><tt>{first_ext_file}</tt> (deprecated)</td></tr>
							<tr><td title="Finds first file with extension"><tt>{first_n_ext_files}</tt> (deprecated)</td></tr>
							<tr><td title="Finds last file (alphabetically) with extension"><tt>{last_ext_file}</tt> (deprecated)</td></tr>
							<tr><td title="Finds all files matching the extension"><tt>{all_ext_files}</tt> (deprecated)</td></tr>
						</table>
					</div>
				</div>
			
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
					<?
					$sqlstring = "select * from pipeline_steps where pipeline_id = $id and pipeline_version = $version and ps_supplement <> 1 order by ps_order + 0";
					$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
					?>
					<textarea name="commandlist" style="font-weight:normal"><?
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$line = RenderStepLine($row['ps_command'], $row['ps_description'], ($row['ps_enabled'] == 1), ($row['ps_logged'] == 1));
							echo htmlspecialchars("$line\n", ENT_QUOTES | ENT_SUBSTITUTE);
						}
					?></textarea>
					<div id="commandlist" style="border: 1px solid #666; font-weight: normal"></div>
			</div>
			<div class="ui blue secondary attached segment">
				<div class="ui two column grid">
					<div class="ui column">
						<h3 class="ui header">Supplement script &nbsp; <span class="tiny" style="font-weight:normal">Ctrl+S to save</span></h3>
					</div>
					<div class="ui right aligned column">
						<span id="syntaxstatus-supplement" style="font-weight:normal"></span> &nbsp;
						<div class="ui tiny basic button" title="Check the script for bash syntax errors and common mistakes. Command names are not checked, so a misspelled command is not reported" onClick="CheckAceSyntax(editor2, 'supplement'); return;"><i class="check circle outline icon"></i>Check syntax</div>
						<div class="ui tiny basic button" onClick="toggleWrap2(); return;">Toggle text wrap</div>
					</div>
				</div>
			</div>
			<div class="ui attached segment">
				<?
					$sqlstring2 = "select * from pipeline_steps where pipeline_id = $id and pipeline_version = $version and ps_supplement = 1 order by ps_order + 0";
					$result2 = MySQLiQuery($sqlstring2,__FILE__,__LINE__);
					if (mysqli_num_rows($result2) > 0) {
						$open = "active";
					}
					else {
						$open = "";
					}
				?>
				<div id="supplementcommandlist" style="border: 1px solid #666; font-weight: normal"></div>
				<textarea name="supplementcommandlist" hidden><?
					while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
						$line = RenderStepLine($row2['ps_command'], $row2['ps_description'], ($row2['ps_enabled'] == 1), ($row2['ps_logged'] == 1));
						echo htmlspecialchars("$line\n", ENT_QUOTES | ENT_SUBSTITUTE);
					}
				?></textarea>
			</div>
			<div class="ui bottom attached segment">
				<input class="ui primary button" type="submit" <?=$disabled?> value="Save Pipeline Details">
			</div>
			</form>
			<!--<script src="scripts/aceeditor/ace.js" type="text/javascript" charset="utf-8"></script>-->
			<script src="https://cdn.jsdelivr.net/npm/ace-builds@1.44.0/src/ace.js"></script>
			<? PrintAceSearchHighlight(); ?>

			<script>
				var editor = ace.edit("commandlist");
				var textarea = $('textarea[name="commandlist"]').hide();
				editor.setFontSize(12);
				editor.getSession().setMode("ace/mode/sh");
				editor.getSession().setUseWrapMode(false);
				editor.getSession().setValue(textarea.val());
				<?if ($readonly) { ?>
				editor.setReadOnly(true);
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
				<?if (!$readonly) { ?>
				$(window).bind('keydown', function(event) {
					if (event.ctrlKey || event.metaKey) {
						switch (String.fromCharCode(event.which).toLowerCase()) {
							case 's':
								/* only save when the Data & Scripts tab is showing, so Ctrl+S on another tab doesn't discard its edits */
								if (!$('#stepsform').is(':visible')) {
									break;
								}
								event.preventDefault();
								document.getElementById('stepsform').submit();
								break;
						}
					}
				});
				<? } ?>
				
				var editor2 = ace.edit("supplementcommandlist");
				var textarea2 = $('textarea[name="supplementcommandlist"]').hide();
				editor2.setFontSize(12);
				editor2.getSession().setMode("ace/mode/sh");
				editor2.getSession().setUseWrapMode(false);
				editor2.getSession().setValue(textarea2.val());
				<?if ($readonly) { ?>
				editor2.setReadOnly(true);
				<? } ?>
				editor2.getSession().on('change', function(){
				  textarea2.val(editor2.getSession().getValue());
				});
				editor2.setTheme("ace/theme/xcode");
				
				keepAceSearchHighlight(editor);
				keepAceSearchHighlight(editor2);
				
				/* check the script with shellcheck (server side) and show the results as Ace gutter annotations */
				function CheckAceSyntax(whicheditor, key) {
					var status = $('#syntaxstatus-' + key);
					status.css('color', '#888').text('Checking...');
					$.post("ajaxapi.php", { action: "checkbashsyntax", script: whicheditor.getSession().getValue() }, null, "json")
					.done(function(r) {
						whicheditor.getSession().setAnnotations(r.annotations);
						var errors = 0;
						var warnings = 0;
						var suggestions = 0;
						r.annotations.forEach(function(a) {
							if (a.type == 'error') { errors++; }
							else if (a.type == 'warning') { warnings++; }
							else { suggestions++; }
						});
						if (r.annotations.length == 0) {
							status.css('color', '#21ba45').html('<i class="check icon"></i>No problems found (' + r.checker + ')').attr('title', 'Command names are not checked');
						}
						else {
							var parts = [];
							if (errors > 0) { parts.push(errors + (errors == 1 ? ' error' : ' errors')); }
							if (warnings > 0) { parts.push(warnings + (warnings == 1 ? ' warning' : ' warnings')); }
							if (suggestions > 0) { parts.push(suggestions + (suggestions == 1 ? ' suggestion' : ' suggestions')); }
							status.css('color', (errors > 0 ? '#db2828' : '#f2711c')).text(parts.join(', ') + ' - hover the gutter icons for details');
							/* jump to the first problem */
							whicheditor.gotoLine(r.annotations[0].row + 1, r.annotations[0].column, true);
						}
					})
					.fail(function(xhr) {
						status.css('color', '#db2828').text("Unable to check the script: " + ((xhr.responseJSON && xhr.responseJSON.error) || xhr.statusText));
					});
				}
				
				/* editing invalidates the annotations, so clear them once the script changes */
				[[editor, 'main'], [editor2, 'supplement']].forEach(function(pair) {
					pair[0].getSession().on('change', function() {
						if (pair[0].getSession().getAnnotations().length > 0) { pair[0].getSession().clearAnnotations(); }
						$('#syntaxstatus-' + pair[1]).text('');
					});
				});
				
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
		</div>
		
		<!-- ---------- operations tab ---------- -->
		
		<div class="ui bottom attached <?=$tab_fouractive?> tab centered very padded raised segment" data-tab="fourth">
		
		<div class="ui centered cards">
		
			<div class="raised card">
				<div class="content">
					<div class="header">
						<i class="redo alternate icon"></i>
						Reset ignored studies
					</div>
					<div class="description">
						<p>All studies in NiDB are 'checked' if they match criteria for this pipeline, otherwise they are flagged so they are not checked again. This will reset those flags so previously ignored studies will be checked again.</p>
					</div>
				</div>
				<a class="ui primary button" href="pipelines.php?action=resetanalyses&id=<?=$id?>&returntab=operations" onclick="return confirm('Are you sure you want to reset the analyses for this pipeline?')">Reset</a>
			</div>
			
			<div class="raised card">
				<div class="content">
					<div class="header">
						<i class="copy icon"></i>
						Copy to new pipeline
					</div>
					<div class="description">
						Create a new pipeline using this pipeline as a template
					</div>
				</div>
				<div class="ui primary button" onClick="GetNewPipelineName();">
					Copy...
				</div>
			</div>
			
			<? if (!$readonly) { ?>
			<div class="raised card">
				<div class="content">
					<div class="header">
						<i class="exchange alternate icon"></i>
						Change pipeline owner
					</div>
					<div class="description">
						Change the owner of this pipeline
						<p>
							<form action="pipelines.php" method="post" name="changeOwnerForm">
							<input type="hidden" name="action" value="changeowner">
							<input type="hidden" name="id" value="<?=$id?>">
							<input type="hidden" name="returntab" value="operations">
							<select class="ui selection dropdown" name="newuserid" id="newuserid" required>
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
						</p>
					</div>
				</div>
				<div class="ui primary button" onClick="document.changeOwnerForm.submit()">
					Change...
				</div>
				</form>
			</div>
			<? } ?>

			<div class="raised card">
				<div class="content">
					<div class="header">
						<i class="file export icon"></i>
						Export pipeline
					</div>
					<div class="description">
						Export this pipeline in squirrel format as a web download
					</div>
				</div>
				<a href="pipelines.php?action=exportpipeline&id=<?=$id?>&returntab=operations" class="ui primary button">Export pipeline</a>
			</div>

			<div class="raised card">
				<div class="content">
					<div class="header">
						<i class="file export icon"></i>
						Export analysis results
					</div>
					<div class="description">
						<p>Export all analysis results to a csv file.</p>
						
						<p>Previous exports</p>
						<table class="ui very compact small head stuck short scrolling table">
							<thead>
								<th>Date</th>
								<th>Status</th>
								<th>Size</th>
								<th>Get</th>
							</thead>
						<?
							$sqlstring = "select * from export_nonimaging where export_status in ('submitted','pending','processing','complete','error') and (export_deletedate is null or export_deletedate > now()) and pipeline_id = $id";
							$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$exportRowID = $row['exportnonimaging_id'];
								$exportStatus = $row['export_status'];
								$exportStatusMessage = $row['export_statusmessage'];
								$startDate = date('M m, Y', strtotime($row['export_startdate']));
								$endDate = $row['export_enddate'];
								$numCols = $row['export_numcols'];
								$numRows = $row['export_numrows'];
								$exportSize = HumanReadableFilesize($row['export_size']);
								$exportFilePath = $row['export_filepath'];
								
								if (($exportStatus == "submitted") || ($exportStatus == "pending") || ($exportStatus == "processing")) { $exportStatus = "<i class='spinner loading icon'></i>"; }
								if ($exportStatus == "complete") { $exportStatus = "<i class='green check icon'></i>"; }
								if ($exportStatus == "error") { $exportStatus = "<i class='red exclamation triangle icon'></i>"; }
								
								?>
								<tr>
									<td><?=$startDate?></td>
									<td title="<?=$exportStatusMessage?>"><?=$exportStatus?></td>
									<td><?=$exportSize?></td>
									<td>
										<? if ($exportFilePath != "") { ?>
										<a href="getfile.php?action=download&file=<?=$exportFilePath?>"><i class="arrow alternate circle down icon"></i></a>
										<? } ?>
									</td>
								</tr>
								<?
							}
						?>
						</table>
					</div>
				</div>
				<a href="pipelines.php?action=exportanalysisresults&id=<?=$id?>&returntab=operations" class="ui primary button">Export analysis</a>
			</div>

			<div class="raised card">
				<div class="content">
					<div class="header">
						<em data-emoji=":chipmunk:"></em>
						Add to squirrel package
					</div>
					<div class="description">
						Add this pipeline to an existing squirrel package
					</div>
				</div>
				<a href="packages.php?action=addobject&objecttype=pipeline&objectids[]=<?=$id?>" class="ui primary brown button">Add to Package</a>
			</div>

			<!--<div class="raised disabled card">
				<div class="content">
					<i class="right floated unlock icon"></i>
					<div class="header">Detach pipeline</div>
					<div class="description">
						This will completely inactivate the pipeline and remove all analyses from the pipeline control. Since the data will no longer be under pipeline control, all analysis results will be deleted. All analysis data will be moved to the directory you specify
					</div>
				</div>
				<a href="pipelines.php?action=detach&id=<?=$id?>&returntab=operations" class="ui disabled red button" onclick="return confirm('Are you sure you want to completely detach this pipeline?')">Detach</a>
			</div>-->
		</div>
		
		<div class="ui centered cards">
		
			<div class="red raised card">
				<div class="content">
					<div class="red header">
						<i class="red alternate trash icon"></i>
						Delete pipeline
					</div>
					<div class="description">
						Delete this entire pipeline and all data
					</div>
				</div>
				<a href="pipelines.php?action=delete&id=<?=$id?>&returntab=operations" class="ui red button" onclick="return confirm('Are you sure you want to delete this pipeline?')">Delete</a>
			</div>
			
		</div>
		</div>
		
		<!-- ---------- checks tab ---------- -->
		
		<div class="ui bottom attached <?=$tab_fiveactive?> tab raised segment" data-tab="fifth">
			<table class="ui table">
				<thead>
					<th width="20%">Check</th>
					<th>Message</th>
					<th>Description</th>
				</thead>
				<?
					$pipelineErrors = 0;
					ksort($checks);
					foreach ($checks as $check => $value) {
						if ($value['level'] == 'ok') {
							$color = "green";
							$icon = "check circle";
						}
						elseif ($value['level'] == 'warning') {
							$color = "orange";
							$icon = "info circle";
						}
						else {
							$color = "red";
							$icon = "exclamation circle";
							$pipelineErrors++;
						}
						
						?>
						<tr>
							<td>
								<div class="ui large fluid <?=$color?> label">
									<i class="<?=$icon?> icon"></i>
									<?=$check?>
								</div>
							</td>
							<td>
								<?=$value['message']?>
							</td>
							<td>
								<?=$value['description']?>
							</td>
						<?
					}
				?>
			</table>
			<?
				if ($pipelineErrors > 0) {
					?>
					<script type="text/javascript">
						const checksTab = document.getElementById('checkTabTitle');

						checksTab.style.backgroundColor = '#F00';
						checksTab.style.color = '#FFF';
						checksTab.style.borderRadius = '0.28571429rem 0.28571429rem 0 0';
						checksTab.innerHTML = '<i class="exclamation triangle icon"></i> <?=$pipelineErrors?> error(s)';
					</script>
					<?
				}
			?>
		</div>
		
		<? } ?>
		
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
		<? } ?>

		<? if ($type == "edit") { ?>
		<script>
			function addParam(value,id){
				var TheTextBox = document.getElementById(id);
				TheTextBox.value = TheTextBox.value + ' ' + value;
			}
		</script>
		</div>
		<?
		}
		MarkTime("DisplayPipelineForm() end");
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayVersion --------------------- */
	/* -------------------------------------------- */
	function DisplayVersion($id, $version) {
		/* check the parameters */
		if (!ValidID($id,'Pipeline ID - N')) { return; }
		$id = (int)$id;
		$version = (int)$version;

		$pipeline = VersionQueryRows("select * from pipelines where pipeline_id = ?", 'i', [$id], __LINE__);
		if (count($pipeline) == 0) {
			Error("Pipeline [$id] not found");
			return;
		}
		$pipeline = $pipeline[0];
		$currentversion = (int)$pipeline['pipeline_version'];
		if ($version < 1) { $version = $currentversion; }

		$versions = VersionQueryRows("select * from pipeline_version where pipeline_id = ? order by version desc", 'i', [$id], __LINE__);
		$versioninfo = null;
		foreach ($versions as $v) {
			if ((int)$v['version'] == $version) { $versioninfo = $v; }
		}

		$options = VersionQueryRows("select * from pipeline_options where pipeline_id = ? and pipeline_version = ?", 'ii', [$id, $version], __LINE__);
		$options = $options[0] ?? null;
		$datasteps = VersionQueryRows("select * from pipeline_data_def where pipeline_id = ? and pipeline_version = ? order by pdd_order + 0", 'ii', [$id, $version], __LINE__);
		$steps = VersionQueryRows("select * from pipeline_steps where pipeline_id = ? and pipeline_version = ? order by ps_order + 0", 'ii', [$id, $version], __LINE__);

		$mainlines = array();
		$supplementlines = array();
		foreach ($steps as $s) {
			$line = RenderStepLine($s['ps_command'], $s['ps_description'], ($s['ps_enabled'] == 1), ($s['ps_logged'] == 1));
			if ($s['ps_supplement'] == 1) { $supplementlines[] = $line; }
			else { $mainlines[] = $line; }
		}

		DisplayPipelineStatus($pipeline['pipeline_name'], $pipeline['pipeline_desc'], $pipeline['pipeline_enabled'], $pipeline['pipeline_debug'], $id, "pipelines", $pipeline['pipeline_status'], $pipeline['pipeline_statusmessage'], $pipeline['pipeline_laststart'], $pipeline['pipeline_lastfinish'], $pipeline['pipeline_lastcheck']);
		?>
		<div class="ui container">
			<br>
			<div class="ui segment">
				<div class="ui two column middle aligned grid">
					<div class="column">
						<h2 class="ui header">
							<i class="code branch icon"></i>
							<div class="content">
								Version <?=$version?>
								<? if ($version == $currentversion) { ?><div class="ui green label">Current</div><? } else { ?><div class="ui orange label">Previous version</div><? } ?>
								<div class="sub header">
									<? if ($versioninfo) { ?>
										Saved <?=date("M j, Y H:i:s", strtotime($versioninfo['version_datetime']))?>
										<? if (trim($versioninfo['version_notes'] ?? '') != '') { ?><br><?=htmlspecialchars($versioninfo['version_notes'] ?? '')?><? } ?>
									<? } ?>
								</div>
							</div>
						</h2>
					</div>
					<div class="right aligned column">
						<form method="get" action="pipelines.php" name="versionform" class="ui form" style="display:inline-block">
							<input type="hidden" name="action" value="viewversion">
							<input type="hidden" name="id" value="<?=$id?>">
							<select class="ui search dropdown" name="version" onchange="this.form.submit()">
								<? foreach ($versions as $v) { ?>
								<option value="<?=(int)$v['version']?>" <?=((int)$v['version'] == $version ? "selected" : "")?>>Version <?=(int)$v['version']?> &nbsp; <?=date("M j, Y H:i", strtotime($v['version_datetime']))?></option>
								<? } ?>
							</select>
						</form>
						<a href="pipelines.php?action=editpipeline&id=<?=$id?>" class="ui basic button"><i class="arrow left icon"></i> Back to pipeline</a>
					</div>
				</div>
			</div>

			<? if (!$versioninfo && !$options && (count($datasteps) == 0) && (count($steps) == 0)) { ?>
			<div class="ui warning message">Version <?=$version?> of this pipeline was not found.</div>
			<? } ?>

			<!-- ---------- options ---------- -->
			<div class="ui blue secondary top attached segment">
				<h3 class="ui header">Options</h3>
			</div>
			<div class="ui bottom attached segment">
				<? if ($options) { ?>
				<table class="ui very basic compact collapsing table">
					<tr>
						<td><b>Dependency</b><br><span class="tiny">parent pipeline</span></td>
						<td><?=VersionNameList("select pipeline_id 'id', pipeline_name 'name' from pipelines where pipeline_id = ?", $options['pipeline_dependency'], "pipelines.php?action=editpipeline&id=")?></td>
					</tr>
					<tr>
						<td><b>Dependency matching criteria</b></td>
						<td><?=htmlspecialchars($options['pipeline_dependencylevel'] ?? '')?></td>
					</tr>
					<tr>
						<td><b>Dependency directory</b></td>
						<td><?=htmlspecialchars($options['pipeline_dependencydir'] ?? '')?></td>
					</tr>
					<tr>
						<td><b>Dependency copy method</b></td>
						<td><?=htmlspecialchars($options['pipeline_deplinktype'] ?? '')?></td>
					</tr>
					<tr>
						<td><b>Group</b></td>
						<td><?=VersionNameList("select group_id 'id', group_name 'name' from `groups` where group_id = ?", $options['pipeline_groupid'], "")?></td>
					</tr>
					<tr>
						<td><b>Project</b></td>
						<td><?=VersionNameList("select project_id 'id', project_name 'name' from projects where project_id = ?", $options['pipeline_projectid'], "projects.php?id=")?></td>
					</tr>
					<tr>
						<td><b>Group by subject</b></td>
						<td><?=VersionYesNo($options['pipeline_groupbysubject'])?></td>
					</tr>
					<tr>
						<td><b>Output BIDS</b></td>
						<td><?=VersionYesNo($options['pipeline_outputbids'])?></td>
					</tr>
					<tr>
						<td><b>BIDS output directory</b></td>
						<td><tt><?=htmlspecialchars($options['pipeline_bidsoutputdir'] ?? '')?></tt></td>
					</tr>
					<tr>
						<td><b>Completed files</b></td>
						<td><tt><?=htmlspecialchars($options['pipeline_completefiles'] ?? '')?></tt></td>
					</tr>
					<tr>
						<td><b>Results script</b></td>
						<td><tt><?=htmlspecialchars($options['pipeline_resultsscript'] ?? '')?></tt></td>
					</tr>
				</table>
				<? } else { ?>
				<span style="color:#999">No options were saved for this version</span>
				<? } ?>
			</div>

			<!-- ---------- data ---------- -->
			<div class="ui blue secondary top attached segment">
				<h3 class="ui header">Data</h3>
			</div>
			<div class="ui bottom attached segment" style="overflow-x: auto">
				<? if (count($datasteps) > 0) { ?>
				<table class="ui very compact small celled table">
					<thead>
						<tr>
							<th>Order</th>
							<th>Protocol</th>
							<th>Modality</th>
							<th>Image type</th>
							<th>Data format</th>
							<th>Series criteria</th>
							<th>Level</th>
							<th>Association</th>
							<th>Directory</th>
							<th>Options</th>
						</tr>
					</thead>
					<tbody>
					<? foreach ($datasteps as $d) { ?>
						<tr class="<?=($d['pdd_enabled'] ? '' : 'disabled')?>" valign="top">
							<td><?=htmlspecialchars($d['pdd_order'] ?? '')?></td>
							<td>
								<b><?=htmlspecialchars($d['pdd_protocol'] ?? '')?></b>
								<? if ($d['pdd_isprimaryprotocol']) { ?><div class="ui tiny blue label">Primary</div><? } ?>
								<? if ($d['pdd_optional']) { ?><div class="ui tiny label">Optional</div><? } ?>
								<? if (!$d['pdd_enabled']) { ?><div class="ui tiny grey label">Disabled</div><? } ?>
							</td>
							<td><?=htmlspecialchars($d['pdd_modality'] ?? '')?></td>
							<td><tt><?=htmlspecialchars($d['pdd_imagetype'] ?? '')?></tt></td>
							<td><?=htmlspecialchars($d['pdd_dataformat'] ?? '')?></td>
							<td>
								<?=htmlspecialchars($d['pdd_seriescriteria'] ?? '')?>
								<? if ($d['pdd_numboldreps'] != '') { ?><br><span class="tiny">BOLD reps <?=htmlspecialchars($d['pdd_numboldreps'] ?? '')?></span><? } ?>
							</td>
							<td><?=htmlspecialchars($d['pdd_level'] ?? '')?></td>
							<td><?=htmlspecialchars($d['pdd_assoctype'] ?? '')?></td>
							<td>
								<tt><?=htmlspecialchars($d['pdd_location'] ?? '')?></tt>
								<? if ($d['pdd_behformat'] != '') { ?><br><span class="tiny">Beh format <?=htmlspecialchars($d['pdd_behformat'] ?? '')?></span><? } ?>
								<? if ($d['pdd_behdir'] != '') { ?><br><span class="tiny">Beh dir <tt><?=htmlspecialchars($d['pdd_behdir'] ?? '')?></tt></span><? } ?>
							</td>
							<td>
								<? if ($d['pdd_gzip']) { ?><div class="ui tiny basic label">gzip</div><? } ?>
								<? if ($d['pdd_useseries']) { ?><div class="ui tiny basic label">Use series dirs</div><? } ?>
								<? if ($d['pdd_preserveseries']) { ?><div class="ui tiny basic label">Preserve series</div><? } ?>
								<? if ($d['pdd_usephasedir']) { ?><div class="ui tiny basic label">Phase dir</div><? } ?>
								<? if ($d['pdd_behonly']) { ?><div class="ui tiny basic label">Beh only</div><? } ?>
							</td>
						</tr>
					<? } ?>
					</tbody>
				</table>
				<? } else { ?>
				<span style="color:#999">No data steps in this version</span>
				<? } ?>
			</div>

			<!-- ---------- scripts ---------- -->
			<div class="ui blue secondary top attached segment">
				<h3 class="ui header">Main Script Commands</h3>
			</div>
			<div class="ui bottom attached segment">
				<? if (count($mainlines) > 0) { ?>
				<div id="versionmainscript" style="border: 1px solid #666"><?=htmlspecialchars(implode("\n", $mainlines), ENT_QUOTES | ENT_SUBSTITUTE)?></div>
				<? } else { ?>
				<span style="color:#999">No commands in this version</span>
				<? } ?>
			</div>

			<div class="ui blue secondary top attached segment">
				<h3 class="ui header">Supplement script</h3>
			</div>
			<div class="ui bottom attached segment">
				<? if (count($supplementlines) > 0) { ?>
				<div id="versionsupplementscript" style="border: 1px solid #666"><?=htmlspecialchars(implode("\n", $supplementlines), ENT_QUOTES | ENT_SUBSTITUTE)?></div>
				<? } else { ?>
				<span style="color:#999">No supplement commands in this version</span>
				<? } ?>
			</div>
		</div>

		<script src="https://cdn.jsdelivr.net/npm/ace-builds@1.44.0/src/ace.js"></script>
		<? PrintAceSearchHighlight(); ?>
		<script>
			/* read-only Ace editors, styled like the Data & Scripts tab, sized to fit the script */
			['versionmainscript', 'versionsupplementscript'].forEach(function(elid) {
				if (!document.getElementById(elid)) { return; }
				var editor = ace.edit(elid);
				editor.setTheme("ace/theme/xcode");
				editor.session.setMode("ace/mode/sh");
				editor.setOptions({ readOnly: true, fontSize: 12, maxLines: Infinity, minLines: 3, highlightActiveLine: false });
				keepAceSearchHighlight(editor);
			});
		</script>
		<br><br>
	<?
	}


	/* -------------------------------------------- */
	/* ------- VersionQueryRows ------------------- */
	/* -------------------------------------------- */
	/* run a bound select and return all rows. Used by DisplayVersion() */
	function VersionQueryRows($sqlstring, $types, $params, $line) {
		$rows = array();
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, $types, ...$params);
		$result = MySQLiBoundQuery($stmt, __FILE__, $line, $sqlstring, $params);
		if ($result) {
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$rows[] = $row;
			}
		}
		mysqli_stmt_close($stmt);
		return $rows;
	}


	/* -------------------------------------------- */
	/* ------- VersionNameList -------------------- */
	/* -------------------------------------------- */
	/* turn a comma separated list of IDs (as stored in pipeline_options) into a list of names. $sqlstring
	   selects 'id' and 'name' for one bound ID. If $link is given, each name links to $link . id */
	function VersionNameList($sqlstring, $idlist, $link) {
		$names = array();
		foreach (explode(",", $idlist ?? '') as $objid) {
			$objid = (int)trim($objid);
			if ($objid < 1) { continue; }
			$rows = VersionQueryRows($sqlstring, 'i', [$objid], __LINE__);
			if (count($rows) == 0) {
				$names[] = "<span style='color:#999'>(deleted, ID $objid)</span>";
			}
			elseif ($link != "") {
				$names[] = "<a href='$link$objid'>" . htmlspecialchars($rows[0]['name']) . "</a>";
			}
			else {
				$names[] = htmlspecialchars($rows[0]['name']);
			}
		}
		return implode("<br>", $names);
	}


	/* -------------------------------------------- */
	/* ------- VersionYesNo ----------------------- */
	/* -------------------------------------------- */
	function VersionYesNo($value) {
		return $value ? "<i class='green check icon'></i> Yes" : "No";
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayPipelineUsage --------------- */
	/* -------------------------------------------- */
	function DisplayPipelineUsage() {
	
		MarkTime("DisplayPipelineUsage()");
	
		$username = $GLOBALS['username'];
		
		global $imgdata;
		/* create the graphs for each pipeline group */
		$sqlstring = "select distinct(pipeline_group) 'pipeline_group' from pipelines where pipeline_group <> ''";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$group = $row['pipeline_group'];
			//$imgdata[$group] = CreatePipelineGraph($group);
		}
		list($myusage,$maxsize) = GetPipelineInfo(true);
		
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
			$pipelinetree = GetPipelineTree($viewall, $viewhidden, 0);
			//PrintVariable($pipelinetree);
		?>
		<br><br>
		<b>All usage</b>
		<table class="ui very compact small celled collapsing table">
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
	function DisplayPipelineTree($viewname, $viewlevel, $viewowner, $viewstatus, $viewenabled, $viewall, $viewhidden, $viewuserid) {
		MarkTime("DisplayPipelineTree()");
		ShowFlashMessage(); /* show any message from a mutating action that redirected here (PRG) */

		$myuserid = (int)$GLOBALS['userid'];

		global $imgdata;
		/* create the graphs for each pipeline group */
		$sqlstring = "select distinct(pipeline_group) 'pipeline_group' from pipelines where pipeline_group <> ''";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$group = $row['pipeline_group'];
			//$imgdata[$group] = CreatePipelineGraph($group);
		}
		list($myusage,$maxsize) = GetPipelineInfo(false);
		$allinfo = $GLOBALS['info'] ?? array();

		/* the trees for both tabs. The 'mine' tree can include parent pipelines owned by other users, to show where the user's pipelines attach */
		$minetree = GetPipelineTree(false, $viewhidden, $myuserid);
		$alltree = GetPipelineTree(false, $viewhidden, 0);

		/* counts are distinct pipelines, since a pipeline with several parents appears in the tree more than once */
		$countMine = 0;
		$countAll = 0;
		foreach ($allinfo as $id => $p) {
			if ($p['ishidden'] && !$viewhidden) { continue; }
			$countAll++;
			if ($p['adminid'] == $myuserid) { $countMine++; }
		}

		/* favorite pipelines, including hidden ones */
		$favorites = array();
		foreach ($allinfo as $id => $p) {
			if ($p['favorite']) { $favorites[] = $p; }
		}
		usort($favorites, function($a, $b) { return strcasecmp($a['title'], $b['title']); });
	?>
	<div class="ui container">
		<div class="ui two column grid">
			<div class="column">
				<h1 class="ui header">Pipelines</h1>
			</div>
			<div class="right aligned column">
				<a href="pipelines.php?action=addform" class="ui primary large button"><i class="plus square outline icon"></i>New Pipeline</a>
			</div>
		</div>
		<a href="pipelines.php?action=viewusage" class="ui button"><i class="hdd icon"></i> Disk Usage (slow)</a>
		<a href="visualization.php?action=visualize&type=ica" class="ui button"><i class="images icon"></i> Visualization</a>
		<? if ($viewhidden) { ?>
		<a href="pipelines.php" class="ui button"><i class="eye slash icon"></i> Hide hidden pipelines</a>
		<? } else { ?>
		<a href="pipelines.php?viewhidden=1" class="ui button"><i class="eye icon"></i> Show hidden pipelines</a>
		<? } ?>
		<br><br>

		<script>
			$(document).ready(function() {
				/* remember the selected tab, so enabling/disabling a pipeline (which reloads the page) returns to the same tab */
				$('#pipelinetabs .item').tab({
					onVisible: function(tab) { try { sessionStorage.setItem('pipelinelisttab', tab); } catch (e) {} }
				});
				var lasttab = null;
				try { lasttab = sessionStorage.getItem('pipelinelisttab'); } catch (e) {}
				if (lasttab) { $('#pipelinetabs .item').tab('change tab', lasttab); }
			});

			/* filter both pipeline trees. A matching row is shown along with its ancestor rows (dimmed) so the tree
			   structure is kept. The badge counts distinct matching pipelines */
			function filterPipelines() {
				var term = document.getElementById('pipelineSearch').value.toLowerCase().trim();
				['mine', 'all'].forEach(function(tab) {
					var rows = document.querySelectorAll('#table-' + tab + ' tbody tr[data-rowkey]');
					var badge = document.getElementById('badge-' + tab);
					if (term === '') {
						rows.forEach(function(row) { row.style.display = ''; row.classList.remove('treecontext'); });
						badge.style.display = 'none';
						return;
					}
					var show = {};
					var matchrows = {};
					var matchpipelines = {};
					rows.forEach(function(row) {
						if ((row.dataset.search || '').indexOf(term) !== -1) {
							matchrows[row.dataset.rowkey] = true;
							matchpipelines[row.dataset.pipelineid] = true;
							show[row.dataset.rowkey] = true;
							(row.dataset.ancestors || '').split(' ').forEach(function(k) { if (k) show[k] = true; });
						}
					});
					rows.forEach(function(row) {
						var key = row.dataset.rowkey;
						row.style.display = show[key] ? '' : 'none';
						row.classList.toggle('treecontext', show[key] && !matchrows[key]);
					});
					var count = Object.keys(matchpipelines).length;
					badge.className = count > 0 ? 'ui red label' : 'ui grey label';
					badge.textContent = count;
					badge.style.display = '';
				});
			}
		</script>
		<style>
			tr.treecontext { opacity: 0.45; }
		</style>

		<? if (count($favorites) > 0) { ?>
		<div class="ui segment" style="margin-bottom:16px">
			<h4 class="ui header" style="margin-bottom:10px"><i class="yellow star icon"></i>Favorite pipelines</h4>
			<table class="ui very basic compact collapsing table">
				<thead>
					<th>Pipeline</th>
					<th>Owner</th>
					<th>Status</th>
				</thead>
				<tbody>
					<? foreach ($favorites as $p) { ?>
					<tr>
						<td><a href="pipelines.php?action=editpipeline&id=<?=$p['id']?>"><?=$p['title']?></a> <span class="tiny">v<?=$p['version']?></span></td>
						<td><?=$p['creatorusername']?></td>
						<td><?=PipelineDisplayStatus($p)?></td>
					</tr>
					<? } ?>
				</tbody>
			</table>
		</div>
		<? } ?>

		<div class="ui top attached tabular menu large" id="pipelinetabs">
			<a class="item active" data-tab="mine">My Pipelines &nbsp;<span class="ui label"><?=$countMine?></span> <span id="badge-mine" class="ui label" style="display:none"></span></a>
			<a class="item" data-tab="all">All Pipelines &nbsp;<span class="ui label"><?=$countAll?></span> <span id="badge-all" class="ui label" style="display:none"></span></a>
			<div class="right menu">
				<div class="item">
					<div class="ui icon input" style="width:360px">
						<input type="text" id="pipelineSearch" oninput="filterPipelines()" placeholder="Search name, group, owner, description"/>
						<i class="search icon"></i>
					</div>
				</div>
			</div>
		</div>

		<? foreach (array('mine' => $minetree, 'all' => $alltree) as $tab => $tree) { ?>
		<div class="ui bottom attached tab segment <?=($tab == 'mine' ? 'active' : '')?>" data-tab="<?=$tab?>">
			<table class="ui single line selectable table" id="table-<?=$tab?>" width="100%">
				<thead>
					<tr style="vertical-align: top;text-align:left">
						<th style="font-size:12pt">Pipeline Group</th>
						<th style="font-size:12pt">Name</th>
						<th style="font-size:12pt" align="right">Level</th>
						<th style="font-size:12pt">Owner</th>
						<th style="font-size:12pt">Status</th>
					</tr>
				</thead>
				<tbody>
					<?
						if (is_array($tree)) {
							PrintTree($tree, 0);
						}
						else {
							?><tr><td colspan="5" style="color:#999; text-align:center; padding:20px"><?=($tab == 'mine' ? "You do not own any pipelines." : "No pipelines found.")?></td></tr><?
						}
					?>
				</tbody>
			</table>
		</div>
		<? } ?>
	</div>
	<br><br><br><br><br>
	<?
	}

	
	/* -------------------------------------------- */
	/* ------- PipelineDisplayStatus -------------- */
	/* -------------------------------------------- */
	/* the status shown in the pipeline lists: Disabled, Idle (enabled but stopped), or the pipeline's status */
	function PipelineDisplayStatus($info) {
		if (!$info['isenabled']) { return "Disabled"; }
		if ($info['status'] == "stopped") { return "Idle"; }
		return $info['status'];
	}

	
	/* -------------------------------------------- */
	/* ------- GetPipelineTree -------------------- */
	/* -------------------------------------------- */
	function GetPipelineTree($viewall, $viewhidden, $userid) {
		MarkTime("GetPipelineTree($viewall, $userid)");
		
		//PrintVariable($viewhidden);
		$arr = array();
		$userid = (int)$userid;
		$whereclause = "";
		
		/* get list of pipelines owned by this username */
		if ($viewall) {
			if ($userid != 0) {
				$whereclause = "where b.pipeline_admin = $userid";
			}
		}
		else {
			if ($userid == 0) {
				if ($viewhidden == 1) {
				}
				else {
					$whereclause = "where b.pipeline_ishidden <> 1";
				}
			}
			else {
				if ($viewhidden == 1) {
					$whereclause = "where b.pipeline_admin = $userid";
				}
				else {
					$whereclause = "where b.pipeline_ishidden <> 1 and b.pipeline_admin = $userid";
				}
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
	function PrintTree($tree, $level, $ancestors = array()) {
		MarkTime("PrintTree()");

		if (!is_array($tree))
			return $level;
		
		$level++;
		foreach($tree as $node) {
			/* a dependency on a pipeline that no longer exists has no row, but its children are still printed */
			if (!isset($GLOBALS['info'][$node['pipeline_id']])) {
				$level--;
				$level = PrintTree($node['child_id'], $level, $ancestors);
				$level++;
				continue;
			}
			$rowkey = PrintPipelineRow($GLOBALS['info'][$node['pipeline_id']], $level, $ancestors);
			$level = PrintTree($node['child_id'], $level, array_merge($ancestors, array($rowkey)));
		}
		$level--;

		return $level;
	}	

	/* -------------------------------------------- */
	/* ------- PrintUsageTree --------------------- */
	/* -------------------------------------------- */
	function PrintUsageTree($tree, $level, $parentusage, $maxsize) {
		MarkTime("PrintUsageTree()");
		if (is_null($tree) || $tree < 1)
			return $level;

		$level++;
		foreach($tree as $node) {
			$usage = PrintUsageRow($GLOBALS['info'][$node['pipeline_id']], $level, $parentusage, $maxsize);
			$level = PrintUsageTree($node['child_id'], $level, $usage, $maxsize);
		}
		$level--;

		return $level;
	}	
	
	/* -------------------------------------------- */
	/* ------- PrintPipelineRow ------------------- */
	/* -------------------------------------------- */
	/* returns a key unique to this row on the page. $ancestors are the keys of the rows above it in the tree, used by the search filter */
	function PrintPipelineRow($info, $level, $ancestors = array()) {
		static $rownum = 0;
		$rownum++;
		$rowkey = "pr$rownum";
		
		//PrintVariable($info);
		
		MarkTime("PrintPipelineRow()");
		$class = '';
		$bgcolor = '';
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
		
		$dispstatus = PipelineDisplayStatus($info);
		$search = htmlspecialchars(strtolower(trim("{$info['title']} {$info['pipelinegroup']} {$info['creatorusername']} {$info['desc']}")));
		
		$imgdata = $GLOBALS['imgdata'];
		?>
		<tr style="color: <?=$fontcolor?>" data-rowkey="<?=$rowkey?>" data-pipelineid="<?=(int)$info['id']?>" data-ancestors="<?=implode(' ', $ancestors)?>" data-search="<?=$search?>">
			<? if (($info['pipelinegroup'] == '') || ($level > 1)) { ?>
			<td valign="top" align="left" class="<?=$class?>">&nbsp;</td>
			<? } else { ?>
			<td valign="top" align="left" class="<?=$class?>" title="<img border=1 src='data:image/png;base64,<?=$imgdata[$info['pipelinegroup']] ?? ''?>'>"><?=$info['pipelinegroup']?></td>
			<? } ?>
			<td valign="top" style="padding-left: <?=($level-1)*10?>;" class="<?=$class?>" title="<b><?=$info['title']?></b> &nbsp; <?=$info['desc']?>"><? if ($level > 1) { echo "<i class='clockwise rotated grey level up alternate icon'></i>"; } ?><a href="pipelines.php?action=editpipeline&id=<?=$info['id']?>" style="font-size:11pt"><?=$info['title']?></a> <? PipelineFavoriteStar($info['id'], $info['favorite'] ?? false); ?> &nbsp; <span class="tiny">v<?=$info['version']?></span></td>
			<td valign="top" align="right"><?=$info['level']?></td>
			<td valign="top"><?=$info['creatorusername']?></td>
			<td valign="top" align="left" style="background-color: <?=$bgcolor?>; <? if (!$info['isenabled']) echo "color: gray"; ?>">
				<?
					if ($info['isenabled']) {
						?><a href="pipelines.php?action=disable&returnpage=home&id=<?=$info['id']?>"><i class="green toggle on icon" title="Enabled. Click to disable"></i></a><?
					}
					else {
						?><a href="pipelines.php?action=enable&returnpage=home&id=<?=$info['id']?>"><i class="red toggle off icon" title="Disabled. Click to enable"></i></a><?
					}
				?>
				<span title="<b>Last message:</b> <?=$info['message']?><br><b>Last check:</b> <?=$info['lastcheck']?>">
				<? if ($info['status'] == 'running') { ?><b>Running</b> &nbsp; <a href="pipelines.php?action=reset&id=<?=$info['id']?>" class="ui orange basic small button">reset</a><? } else { echo $dispstatus; }  ?>
				</span>
			</td>
			<!--<td valign="top" align="right" style="font-size: 8pt; white-space:nowrap;" title="processing / complete">
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
			<td valign="top"><?=$info['queue']?></td>-->
		</tr>
		<?
		return $rowkey;
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
	function GetPipelineInfo($showusage) {
		MarkTime("GetPipelineInfo() first call");

		/* yes, this variable is supposed to be global...
		   the reason being: there is a good chance the pipeline info will be needed many times,
		   with the info being needed at different locations in the code. Rather than loading
		   everything at once, this loads what is needed to display and keeps it for later,
		   kind of like caching ... */
		global $info;
		
		$maxsize = 0;
		$favorites = GetUserFavorites('pipeline');
		$sqlstring = "select a.*,timediff(pipeline_lastfinish, pipeline_laststart) 'run_time', b.username 'creatorusername', b.user_fullname 'creatorfullname' from pipelines a left join users b on a.pipeline_admin = b.user_id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$id = (int)$row['pipeline_id'];

			MarkTime("GetPipelineInfo($id)");
			
			if ($row['pipeline_name'] == "") { $row['pipeline_name'] = "(blank pipeline name)"; }
			
			$info[$id]['id'] = $row['pipeline_id'];
			$info[$id]['title'] = $row['pipeline_name'];
			$info[$id]['favorite'] = isset($favorites[(int)$id]);
			$info[$id]['desc'] = $row['pipeline_desc'];
			$info[$id]['creatorusername'] = $row['creatorusername'];
			$info[$id]['adminid'] = (int)$row['pipeline_admin'];
			$info[$id]['creatorfullname'] = $row['creatorfullname'];
			$info[$id]['createdate'] = date("M n,Y", strtotime($row['pipeline_createdate']));
			$info[$id]['isenabled'] = $row['pipeline_enabled'];
			$info[$id]['ishidden'] = $row['pipeline_ishidden'];
			$info[$id]['istesting'] = $row['pipeline_testing'];
			$info[$id]['pipelinegroup'] = $row['pipeline_group'];
			$info[$id]['dependency'] = $row['pipeline_dependency'];
			$info[$id]['groupid'] = $row['pipeline_groupid'];
			$info[$id]['projectid'] = $row['pipeline_projectid'];
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
			
			if ($showusage) {
				$sqlstringD = "select sum(analysis_disksize) 'disksize' from analysis where pipeline_id = $id";
				$resultD = MySQLiQuery($sqlstringD,__FILE__,__LINE__);
				$rowD = mysqli_fetch_array($resultD, MYSQLI_ASSOC);
				$info[$id]['disksize'] = $rowD['disksize'];
			}
			else
				$info[$id]['disksize'] = 0.0;

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
			
			if ($showusage) {
				$sqlstring3 = "select count(*) 'numprocessing' from analysis where analysis_status = 'processing' and pipeline_id = $id";
				$result3 = MySQLiQuery($sqlstring3,__FILE__,__LINE__);
				$row3 = mysqli_fetch_array($result3, MYSQLI_ASSOC);
				$info[$id]['numprocessing'] = $row3['numprocessing'];
				
				$sqlstring3 = "select count(*) 'numcomplete' from analysis where analysis_status = 'complete' and pipeline_id = $id";
				$result3 = MySQLiQuery($sqlstring3,__FILE__,__LINE__);
				$row3 = mysqli_fetch_array($result3, MYSQLI_ASSOC);
				$info[$id]['numcomplete'] = $row3['numcomplete'];
			}
			else {
				$info[$id]['numprocessing'] = 0;
				$info[$id]['numcomplete'] = 0;
			}
			
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
		
		$d = array();
		
		$d[] = "digraph G {";
		$sqlstring = "select * from pipelines where pipeline_group = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 's', $g);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$g]);
		mysqli_stmt_close($stmt);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$pipelinename = $row['pipeline_name'];
			/* ints (intval-sanitized) - safe to inline */
			$deps = implode(",", array_filter(array_map('intval', explode(",", (string)$row['pipeline_dependency']))));
			$groupids = implode(",", array_filter(array_map('intval', explode(",", (string)$row['pipeline_groupid']))));
			$projectids = $row['pipeline_projectid'];
			
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
		
		//PrintVariable($statsoutput);

		$hostname = $queue = "";
		$hostnames = $queues = array();

		/* PHP 8: sort() on null is fatal - ensure these are always arrays */
		$hostnames = array();
		$queues = array();
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


	/* -------------------------------------------- */
	/* ------- GetDataGraph ----------------------- */
	/* -------------------------------------------- */
	function GetDataGraph($pipelineid, $version, $dependencies) {

		/* PHP 8: count()/foreach() on null is fatal - ensure these are always arrays */
		if (!is_array($dependencies)) $dependencies = array();
		$pipelineid = (int)$pipelineid;
		$version = (int)$version;
		$dd = array();
		$subject = array();
		$study = array();

		$sqlstring = "select * from pipeline_data_def where pipeline_id = $pipelineid and pipeline_version = $version order by pdd_order + 0";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$dd_order = $row['pdd_order'];
			
			$dd[$dd_order]['isprimaryprotocol'] = $row['pdd_isprimaryprotocol'];
			$dd[$dd_order]['seriescriteria'] = $row['pdd_seriescriteria'];
			$dd[$dd_order]['protocol'] = $row['pdd_protocol'];
			$dd[$dd_order]['modality'] = $row['pdd_modality'];
			$dd[$dd_order]['dataformat'] = $row['pdd_dataformat'];
			$dd[$dd_order]['imagetype'] = $row['pdd_imagetype'];
			$dd[$dd_order]['gzip'] = $row['pdd_gzip'];
			$dd[$dd_order]['location'] = $row['pdd_location'];
			$dd[$dd_order]['useseries'] = $row['pdd_useseries'];
			$dd[$dd_order]['preserveseries'] = $row['pdd_preserveseries'];
			$dd[$dd_order]['usephasedir'] = $row['pdd_usephasedir'];
			$dd[$dd_order]['behonly'] = $row['pdd_behonly'];
			$dd[$dd_order]['behformat'] = $row['pdd_behformat'];
			$dd[$dd_order]['behdir'] = $row['pdd_behdir'];
			$dd[$dd_order]['numboldreps'] = $row['pdd_numboldreps'];
			$dd[$dd_order]['enabled'] = $row['pdd_enabled'];
			$dd[$dd_order]['assoctype'] = $row['pdd_assoctype'];
			$dd[$dd_order]['optional'] = $row['pdd_optional'];
			$dd[$dd_order]['datalevel'] = $row['pdd_level'];
			$dd[$dd_order]['numimagescriteria'] = $row['pdd_numimagescriteria'];
		}

		$primarymodality = $dd[1]['modality'];
		foreach ($dd as $step => $data) {
			if ($data['isprimaryprotocol']) {
				$primarymodality = $data['modality'];
				break;
			}
		}

		$i = 0;
		$j = 0;
		foreach ($dd as $step => $data) {
			$datalevel = $data['datalevel'];
			
			if (($datalevel == "subject") || ($data['modality'] != $primarymodality)) {
				$subject[$i]['protocol'] = $data['protocol'];
				$subject[$i]['assoctype'] = $data['assoctype'];
				$subject[$i]['modality'] = $data['modality'];
				$i++;
			}
			else {
				$study[$j]['protocol'] = $data['protocol'];
				$study[$j]['assoctype'] = $data['assoctype'];
				$study[$j]['modality'] = $data['modality'];
				$j++;
			}
		}
		
		if (count($subject) > 0) {
			/* start drawing an outer box */
			?>
			<table style="background-color: #dee2ea; border: 2px solid #aaa" cellpadding="15">
				<tr>
					<td colspan="2" align="center"><b><span style="color: #777">Subject</span></b></td>
				</tr>
				<td valign="bottom" align="center">
			<?
		}

		if (count($dependencies) > 0) {
		?>
		<table width="100%" style="background-color: #fff; box-shadow: 5px 5px 10px gray" class="end" cellpadding="8" cellspacing="0" title="This is the study that will be the primary focus of the analysis">
			<tr>
				<td align="center" style="background-color: #aaa; color: #fff"><b>Parent pipeline(s)</b></td>
			</tr>
		<?
		foreach ($dependencies as $depname) {
			?>
			<tr>
				<td><?=$depname?></td>
			</tr>
			<?
		}
		?>
		</table>
		<span style="font-size: 20pt; font-weight: bold">&darr;</span>
		<?
		}
		/* draw the study level data */
		?>
		<table width="50%" style="background-color: #93a9d6; border: 4px solid orange; box-shadow: 5px 5px 10px orange" cellpadding="8" cellspacing="0" title="This is the study that will be the primary focus of the analysis">
			<tr>
				<td align="center" style="background-color: #3b5998; color: #fff"><b>This study - <?=$data['modality']?></b></td>
			</tr>
			<?
			foreach ($study as $step => $data) {
				if ($data['modality'] == "EEG") { $color = "#000"; $bgcolor="#edc7b7"; }
				elseif ($data['modality'] == "MR") { $color = "#000"; $bgcolor="#eee2dc"; }
				elseif ($data['modality'] == "ET") { $color = "#000"; $bgcolor="#bab2b5"; }
				elseif ($data['modality'] == "VIDEO") { $color = "#fff"; $bgcolor="#123c69"; }
				elseif ($data['modality'] == "TASK") { $color = "#fff"; $bgcolor="#ac3b61"; }
				else { $color=""; $bgcolor=""; }
				
				?>
				<tr>
					<td style="font-size: smaller; color: <?=$color?>; background-color: <?=$bgcolor?>"><?=$data['protocol']?></td>
				</tr>
				<?
			}
			?>
		</table>
		<?
		
		if (count($subject) > 0) {
			/* draw the subject level data and close the outer box */
			?>
					</td>
					<td valign="bottom" align="center">
						<?
						foreach ($subject as $step => $data) {
							?>
							<table cellspacing="0" cellpadding="5" class="start" style="background-color: #9bc193; box-shadow: 5px 5px 10px #333" width="100%" >
								<tr>
									<td style="color: #fff"><b><?=$data['modality']?></b> study</td>
								</tr>
								<tr>
									<td style="background-color: #fff"><?=$data['protocol']?></td>
								</tr>
							</table>
							<span style="font-size: 20pt; font-weight: bold">&darr;</span>
							<?
						}
						?>
						<table width="100%">
							<tr>
								<td><span style="font-size: 20pt; font-weight: bold">&larr;</span>
								</td>
								<td width="100%">
									<table cellspacing="0" cellpadding="5" class="start" style="background-color: #9bc193; box-shadow: 5px 5px 10px #333" width="100%" >
									<tr>
										<td style="color: #fff">Same subject, other studies</td>
									</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
			<?
		}
	}
	
	
	/* -------------------------------------------- */
	/* ------- MarkTime --------------------------- */
	/* -------------------------------------------- */
	function MarkTime($msg) {
		$time = number_format((microtime(true) - $GLOBALS['timestart']), 3);
		$GLOBALS['t'][][$msg] = $time;
	}


	/* -------------------------------------------- */
	/* ------- ExportPipeline --------------------- */
	/* -------------------------------------------- */
	function ExportPipeline($id) {
		/* web, squirrel, pipeline only */

		$ip = getenv('REMOTE_ADDR');
		$username = $_SESSION['username'];
		
		/* collect the download flags */
		$downloadflags = array();
		$downloadflags[] = "DOWNLOAD_PIPELINES";
		if (count($downloadflags) > 0)
			$downloadflagstr = "('" . implode2(",",$downloadflags) . "')";
		else
			$downloadflagstr = "null";
		
		/* collect the squirrel flags */
		$squirrelflags = array();
		if (count($squirrelflags) > 0)
			$squirrelflagstr = "('" . implode2(",",$squirrelflags) . "')";
		else
			$squirrelflagstr = "null";
		
		/* get pipeline details */
		$id = (int)$id;
		$sqlstring = "select * from pipelines where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$pipelinename = $row['pipeline_name'] ?? '';
		$pipelinedesc = $row['pipeline_desc'] ?? '';
		
		/* $downloadflagstr and $squirrelflagstr are built above from constant flag names - safe to inline */
		$sqlstring = "insert into exports (username, ip, download_flags, destinationtype, filetype, squirrel_flags, squirrel_title, squirrel_desc, submitdate, status) values (?, ?, $downloadflagstr, 'web', 'squirrel', $squirrelflagstr, ?, ?, now(), 'submitted')";
		$params = [$username, $ip, $pipelinename, $pipelinedesc];
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ssss', ...$params);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		$exportid = (int)mysqli_insert_id($GLOBALS['linki']);
		mysqli_stmt_close($stmt);
		
		$sqlstring = "insert into exportseries (export_id, pipeline_id, status) values ($exportid, $id, 'submitted')";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		Notice("Pipeline queued for export. Download squirrel file from the <a href='requeststatus.php'>Exports</a> page");
	}


	/* -------------------------------------------- */
	/* ------- ExportAnalysisResults -------------- */
	/* -------------------------------------------- */
	function ExportAnalysisResults($id) {
		/* web, squirrel, pipeline only */

		$ip = getenv('REMOTE_ADDR');
		$username = $_SESSION['username'];
		
		/* get pipeline details */
		$id = (int)$id;
		$sqlstring = "select * from pipelines where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$pipelinename = $row['pipeline_name'] ?? '';
		
		$statusmessage = "Submitted analysis results export for $pipelinename";
		$sqlstring = "insert into export_nonimaging (username, ip, export_type, pipeline_id, export_destinationtype, export_status, export_statusmessage, export_startdate) values (?, ?, 'analysisresults', ?, 'web', 'submitted', ?, now())";
		$params = [$username, $ip, $id, $statusmessage];
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ssis', ...$params);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		$exportid = mysqli_insert_id($GLOBALS['linki']);
		mysqli_stmt_close($stmt);
		
		Notice("Analysis results queued for export. Download from this page.");
	}
	
?>

<? include("footer.php") ?>
