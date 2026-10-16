<?
 // ------------------------------------------------------------------------------
 // NiDB pipelinecontainers.php
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
		<title>NiDB - Pipeline containers</title>
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
	$id = (int)GetVariable("id"); /* pipeline_id. Named 'id' so menu.php shows the pipeline sub-menu */
	$referenceanalysisid = (int)GetVariable("referenceanalysisid");
	$runvalidation = GetMySQLTinyInt(GetVariable("runvalidation"));
	$containerid = (int)GetVariable("containerid");

	/* determine action */
	switch ($action) {
		case 'create':
			ob_start();
			CreateContainer($id, $referenceanalysisid, $runvalidation);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("pipelinecontainers.php?id=$id");
			break;
		case 'viewfiles':
			DisplayContainerFiles($id, $containerid);
			break;
		case 'viewcontainer':
			DisplayContainerDetails($id, $containerid);
			break;
		case 'cancel':
			ob_start();
			CancelContainer($id, $containerid);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("pipelinecontainers.php?action=viewcontainer&id=$id&containerid=$containerid");
			break;
		default:
			DisplayContainers($id);
	}
	//PrintVariable($GLOBALS['t']);

	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- ContainerQueryRows ----------------- */
	/* -------------------------------------------- */
	/* run a bound query and return all rows as an array */
	function ContainerQueryRows($sqlstring, $types, $params, $line) {
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
	/* ------- GetContainerPipeline --------------- */
	/* -------------------------------------------- */
	/* returns the pipelines row (current version) or null */
	function GetContainerPipeline($id) {
		$rows = ContainerQueryRows("select * from pipelines where pipeline_id = ?", 'i', [$id], __LINE__);
		if (count($rows) < 1) { return null; }
		return $rows[0];
	}


	/* -------------------------------------------- */
	/* ------- AddPitfall ------------------------- */
	/* -------------------------------------------- */
	function AddPitfall(&$pitfalls, $level, $title, $description) {
		$pitfalls[] = array('level' => $level, 'title' => $title, 'description' => $description);
	}


	/* -------------------------------------------- */
	/* ------- GetContainerVariables -------------- */
	/* -------------------------------------------- */
	/* scan the enabled main script steps (and their working directories) for {variables}. Returns
	   [lowercase variable] => list of step numbers. Bash ${var} is not a NiDB variable and is skipped.
	   Disabled steps don't run in the container, so they're skipped, as in modulePipelineContainer::CheckIfExportable() */
	function GetContainerVariables($steps) {
		$vars = array();
		foreach ($steps as $step) {
			if (!$step['ps_enabled']) { continue; }
			$text = ($step['ps_command'] ?? '') . "\n" . ($step['ps_workingdir'] ?? '');
			if (preg_match_all('/(?<!\$)\{([A-Za-z0-9_]+)\}/', $text, $matches)) {
				foreach ($matches[1] as $var) {
					$var = strtolower($var);
					$stepnum = (int)$step['ps_order'];
					if (!isset($vars[$var])) { $vars[$var] = array(); }
					if (!in_array($stepnum, $vars[$var])) { $vars[$var][] = $stepnum; }
				}
			}
		}
		return $vars;
	}


	/* -------------------------------------------- */
	/* ------- GetRequiredVariables --------------- */
	/* -------------------------------------------- */
	/* the runtime arguments the container will require (doc/pipeline-apptainer.md section 3) */
	function GetRequiredVariables($vars) {
		$required = array();
		if (isset($vars['subjectuid']) || isset($vars['uidstudynum'])) { $required[] = 'subjectuid'; }
		if (isset($vars['studynum']) || isset($vars['uidstudynum'])) { $required[] = 'studynum'; }
		if (isset($vars['studydatetime'])) { $required[] = 'studydatetime'; }
		return $required;
	}


	/* -------------------------------------------- */
	/* ------- StepList --------------------------- */
	/* -------------------------------------------- */
	function StepList($stepnums) {
		sort($stepnums);
		return ((count($stepnums) == 1) ? "step " : "steps ") . implode(", ", $stepnums);
	}


	/* -------------------------------------------- */
	/* ------- LauncherStepMatch ------------------ */
	/* -------------------------------------------- */
	/* the apptainer/singularity exec, run or test in a step command, or "" if there isn't one. Such a step makes the
	   export a launcher with referenced images (doc/pipeline-apptainer.md section 11). A preview of
	   modulePipelineContainer::CheckContainers(), which parses each command properly */
	function LauncherStepMatch($command) {
		if (preg_match('/(^|[^A-Za-z0-9_.-])(apptainer|singularity)\s+(exec|run|test)\b/', (string)$command, $m)) { return trim($m[0]); }
		return "";
	}


	/* -------------------------------------------- */
	/* ------- IsLauncherExport ------------------- */
	/* -------------------------------------------- */
	/* true if any enabled main step runs a container, so the export is a launcher, not a single .sif */
	function IsLauncherExport($steps) {
		foreach ($steps as $step) {
			if ($step['ps_enabled'] && (LauncherStepMatch($step['ps_command'] ?? '') != "")) { return true; }
		}
		return false;
	}


	/* -------------------------------------------- */
	/* ------- GetContainerPitfalls --------------- */
	/* -------------------------------------------- */
	/* things specific to this pipeline that will stop the export (error), make the container behave
	   differently than the pipeline does in NiDB (warning), or that the user should know (info).
	   Mirrors the export checks described in doc/pipeline-apptainer.md */
	function GetContainerPitfalls($id, $p, $steps) {
		$pitfalls = array();
		$version = (int)$p['pipeline_version'];

		/* ---------- export blockers ---------- */
		if ((int)$p['pipeline_level'] != 1) {
			AddPitfall($pitfalls, 'error', "Not a first level pipeline", "Only first level (subject/study) pipelines can be exported as a container. This is a level " . (int)$p['pipeline_level'] . " pipeline.");
		}

		$numenabled = 0;
		foreach ($steps as $step) {
			if ($step['ps_enabled']) { $numenabled++; }
		}
		if ($numenabled == 0) {
			AddPitfall($pitfalls, 'error', "No enabled main script steps", "The container runs the main script steps, and this version has none enabled.");
		}

		$rows = ContainerQueryRows("select count(*) 'num', avg(timestampdiff(second, analysis_clusterstartdate, analysis_clusterenddate)) 'avgsec' from analysis where pipeline_id = ? and pipeline_version = ? and analysis_status = 'complete' and (analysis_isbad is null or analysis_isbad <> 1)", 'ii', [$id, $version], __LINE__);
		$numcomplete = (int)($rows[0]['num'] ?? 0);
		$avgsec = (float)($rows[0]['avgsec'] ?? 0);
		if ($numcomplete == 0) {
			AddPitfall($pitfalls, 'error', "No completed analyses for version $version", "The build re-runs one completed, not-bad analysis of this version (the reference analysis) on a compute node to find the software the pipeline uses. Run the pipeline on at least one study first.");
		}

		/* ---------- variables ---------- */
		$vars = GetContainerVariables($steps);
		$known = array('analysisrootdir', 'pipelinename', 'workingdir', 'description', 'subjectuid', 'studynum', 'uidstudynum', 'studydatetime', 'analysisid', 'nolog', 'nocheckin', 'profile', 'command');
		$group = array(); $deprecated = array(); $unknown = array();
		foreach ($vars as $var => $stepnums) {
			if (in_array($var, $known)) { continue; }
			if (in_array($var, array('groups', 'uidstudynums', 'numsubjects')) || (strpos($var, 'uidstudynums_') === 0) || (strpos($var, 'numsubjects_') === 0)) {
				$group[] = "<tt>{" . htmlspecialchars($var) . "}</tt> (" . StepList($stepnums) . ")";
			}
			elseif (strpos($var, 'first_') === 0) {
				$deprecated[] = "<tt>{" . htmlspecialchars($var) . "}</tt> (" . StepList($stepnums) . ")";
			}
			else {
				$unknown[] = "<tt>{" . htmlspecialchars($var) . "}</tt> (" . StepList($stepnums) . ")";
			}
		}
		if (count($group) > 0) {
			AddPitfall($pitfalls, 'error', "Group-level variables", "Group variables only have meaning in level 2 pipelines and can't be exported: " . implode(", ", $group));
		}
		if (count($deprecated) > 0) {
			AddPitfall($pitfalls, 'error', "Deprecated variables", "These variables are no longer expanded by NiDB, and the export rejects them: " . implode(", ", $deprecated));
		}
		if (count($unknown) > 0) {
			AddPitfall($pitfalls, 'error', "Unrecognized variables", "These look like NiDB variables but aren't. NiDB leaves them in the command as-is, but the export rejects them. Fix the typo, or rewrite the text (e.g. an awk program) so it isn't a single word in braces: " . implode(", ", $unknown));
		}
		if (isset($vars['analysisid'])) {
			AddPitfall($pitfalls, 'warning', "<tt>{analysisid}</tt> is used", "There is no analysis ID outside NiDB. It will be replaced with <tt>0</tt> in the container (" . StepList($vars['analysisid']) . ").");
		}
		if (isset($vars['command'])) {
			AddPitfall($pitfalls, 'warning', "<tt>{command}</tt> is used", "The deprecated <tt>{command}</tt> variable is removed from the command (" . StepList($vars['command']) . ").");
		}
		if (isset($vars['profile'])) {
			AddPitfall($pitfalls, 'info', "<tt>{PROFILE}</tt> is not honored", "Steps are not profiled inside the container (" . StepList($vars['profile']) . ").");
		}

		/* ---------- absolute NiDB paths ---------- */
		$nidbpaths = array();
		foreach (array('archivedir', 'archivedir1', 'archivedir2', 'archivedir3', 'archivedir4', 'analysisdir', 'analysisdirb', 'clusteranalysisdir', 'clusteranalysisdirb', 'groupanalysisdir') as $key) {
			$path = rtrim(trim($GLOBALS['cfg'][$key] ?? ''), '/');
			if ($path != "") { $nidbpaths[] = $path; }
		}
		$result = MySQLiQuery("select nidbpath, clusterpath from analysisdirs", __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			foreach (array('nidbpath', 'clusterpath') as $col) {
				$path = rtrim(trim($row[$col] ?? ''), '/');
				if ($path != "") { $nidbpaths[] = $path; }
			}
		}
		$nidbpaths = array_unique($nidbpaths);
		$pathsteps = array();
		foreach ($steps as $step) {
			if (!$step['ps_enabled']) { continue; }
			$text = ($step['ps_command'] ?? '') . "\n" . ($step['ps_workingdir'] ?? '');
			foreach ($nidbpaths as $path) {
				if (preg_match('/' . preg_quote($path, '/') . '(\/|\s|$|[\'";])/', $text)) {
					$pathsteps[$path][] = (int)$step['ps_order'];
				}
			}
		}
		if (count($pathsteps) > 0) {
			$list = array();
			foreach ($pathsteps as $path => $stepnums) {
				$list[] = "<tt>" . htmlspecialchars($path) . "</tt> (" . StepList(array_unique($stepnums)) . ")";
			}
			AddPitfall($pitfalls, 'error', "NiDB data paths in the script", "The container can't see NiDB's storage. Use <tt>{analysisrootdir}</tt> for this analysis' own directory, and bring other data in through <tt>/input</tt>: " . implode(", ", $list));
		}

		/* ---------- running containers ---------- */
		/* a preview of modulePipelineContainer::CheckContainers() (doc/pipeline-apptainer.md section 11). Steps that run
		   apptainer or singularity exec, run or test are exported as a launcher; other ways of running a container are
		   errors. The export checks each command properly, and the build also checks the reference run's trace, which
		   catches containers started from a script a step calls */
		$launchersteps = array();
		$containersteps = array();
		foreach ($steps as $step) {
			if (!$step['ps_enabled']) { continue; }
			$command = (string)($step['ps_command'] ?? '');
			$match = LauncherStepMatch($command);
			if ($match != "") {
				$launchersteps[] = "<tt>" . htmlspecialchars($match) . "</tt> (step " . (int)$step['ps_order'] . ")";
				continue;
			}
			foreach (explode("\n", $command) as $line) {
				if (substr(ltrim($line), 0, 1) == "#") { continue; }
				if (preg_match('/(^|[^A-Za-z0-9_.-])(apptainer|singularity|docker|podman)\s+(exec|run|shell|instance|test|start|create|compose|container)\b|\bdocker-compose\b|\brun-singularity\b|[A-Za-z0-9_}-]\.(?i:sif|simg)\b/', $line, $m)) {
					$containersteps[] = "<tt>" . htmlspecialchars(trim($m[0])) . "</tt> (step " . (int)$step['ps_order'] . ")";
					break;
				}
			}
		}
		if (count($containersteps) > 0) {
			AddPitfall($pitfalls, 'error', "Runs a container that can't be exported", "Only steps that run <tt>apptainer</tt> or <tt>singularity</tt> <tt>exec</tt>, <tt>run</tt> or <tt>test</tt> directly can be exported. Docker and Podman aren't supported yet; run the image with <tt>apptainer exec docker://&lt;image&gt;</tt> instead: " . implode(", ", $containersteps));
		}
		if (count($launchersteps) > 0) {
			AddPitfall($pitfalls, 'info', "Exported as a launcher", "Some steps run their own containers, so instead of a single <tt>.sif</tt>, the export is a directory with <tt>run-pipeline.sh</tt>, which runs the other steps in <tt>pipeline.sif</tt> and each of these containers directly, downloading them from a pinned reference. The build node must be able to reach the images' registry: " . implode(", ", $launchersteps));
		}

		/* ---------- things that behave differently ---------- */
		$rows = ContainerQueryRows("select count(*) 'num' from pipeline_steps where pipeline_id = ? and pipeline_version = ? and ps_supplement = 1", 'ii', [$id, $version], __LINE__);
		$numsupplement = (int)($rows[0]['num'] ?? 0);
		if ($numsupplement > 0) {
			AddPitfall($pitfalls, 'info', "Supplement steps are not exported", "This version has $numsupplement supplement step(s). Only the main script runs in the container.");
		}
		if (trim($p['pipeline_resultsscript'] ?? '') != "") {
			AddPitfall($pitfalls, 'info', "Results script is not exported", "The results script inserts results into NiDB, so it is left out of the container.");
		}
		if ($p['pipeline_usetmpdir'] ?? 0) {
			AddPitfall($pitfalls, 'info', "Temporary directory is not used", "The container runs in <tt>/output</tt>. To use local disk, bind a scratch directory as <tt>/output</tt>.");
		}
		$dependencyids = array_values(array_filter(array_map('intval', explode(",", (string)($p['pipeline_dependency'] ?? '')))));
		if (count($dependencyids) > 0) {
			$names = array();
			foreach ($dependencyids as $depid) {
				$rows = ContainerQueryRows("select pipeline_name from pipelines where pipeline_id = ?", 'i', [$depid], __LINE__);
				$names[] = (count($rows) > 0) ? htmlspecialchars($rows[0]['pipeline_name']) : "[$depid]";
			}
			AddPitfall($pitfalls, 'warning', "Depends on a parent pipeline", "This pipeline uses the output of <b>" . implode(", ", $names) . "</b>. Anyone running the container must supply that output in <tt>/input</tt> themselves (see the input layout below).");
		}

		/* ---------- software ---------- */
		$allcommands = "";
		foreach ($steps as $step) {
			if ($step['ps_enabled']) { $allcommands .= ($step['ps_command'] ?? '') . "\n"; }
		}
		if (preg_match('/freesurfer|recon-all|FREESURFER_HOME|SUBJECTS_DIR/i', $allcommands)) {
			AddPitfall($pitfalls, 'warning', "FreeSurfer license", "FreeSurfer's <tt>license.txt</tt> is never copied into the image. Users must supply their own license, e.g. <tt>--bind license.txt:/opt/freesurfer/license.txt</tt>. FreeSurfer also adds ~10 GB to the image.");
		}
		if (preg_match('/\bmatlab\b/i', $allcommands)) {
			AddPitfall($pitfalls, 'warning', "MATLAB", "Commercial software cannot be included in an image meant to be shared, and MATLAB license files are never copied. Compiled MATLAB code with the MATLAB Runtime (MCR) is ok.");
		}
		if (preg_match('/\/opt\/fsl|FSLDIR|\b(bet|flirt|fnirt|feat|melodic|fslmaths)\b/', $allcommands)) {
			AddPitfall($pitfalls, 'info', "FSL", "FSL adds ~5 GB to the image.");
		}

		/* ---------- build ---------- */
		if ($avgsec > 4*3600) {
			AddPitfall($pitfalls, 'warning', "Long build", "The build runs the whole pipeline once on a compute node to find the software it uses. Completed analyses of this version averaged " . round($avgsec/3600, 1) . " hours on the cluster.");
		}
		AddPitfall($pitfalls, 'info', "Skipped code in script", "Required applications/libraries are found by tracing calls from <i>one</i> reference analysis. A bash <tt>if</tt> statement may cause sections of code to not run, preventing capture of all needed software.");
		AddPitfall($pitfalls, 'info', "Compute node OS", "The image is built on a compute node, using that node's OS and software.");

		return $pitfalls;
	}


	/* -------------------------------------------- */
	/* ------- CountBlockingPitfalls -------------- */
	/* -------------------------------------------- */
	function CountBlockingPitfalls($pitfalls) {
		$num = 0;
		foreach ($pitfalls as $pitfall) {
			if ($pitfall['level'] == 'error') { $num++; }
		}
		return $num;
	}


	/* -------------------------------------------- */
	/* ------- GetReferenceAnalyses --------------- */
	/* -------------------------------------------- */
	/* completed, not-bad analyses of this pipeline version, newest first. Any of them can be the
	   reference analysis, which the build re-runs on a compute node to find the software the pipeline uses */
	function GetReferenceAnalyses($id, $version, $analysisid = 0) {
		$sqlstring = "select a.analysis_id, a.analysis_enddate, timestampdiff(second, a.analysis_clusterstartdate, a.analysis_clusterenddate) 'clustersec', d.uid, b.study_num from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = ? and a.pipeline_version = ? and a.analysis_status = 'complete' and (a.analysis_isbad is null or a.analysis_isbad <> 1)";
		if ($analysisid > 0) {
			return ContainerQueryRows("$sqlstring and a.analysis_id = ?", 'iii', [$id, $version, $analysisid], __LINE__);
		}
		return ContainerQueryRows("$sqlstring order by a.analysis_enddate desc, a.analysis_id desc limit 500", 'ii', [$id, $version], __LINE__);
	}


	/* -------------------------------------------- */
	/* ------- ReferenceAnalysisLabel ------------- */
	/* -------------------------------------------- */
	function ReferenceAnalysisLabel($uid, $studynum, $analysisid) {
		if ((int)$analysisid == 0) { return ""; }
		if (($uid ?? '') == "") { return "Analysis " . (int)$analysisid . " (deleted)"; }
		$str = "<a href='viewanalysis.php?action=viewgraph&analysisid=$analysisid'>" . htmlspecialchars($uid) . (int)$studynum . "</a>";
		return $str;
	}


	/* -------------------------------------------- */
	/* ------- GetMainSteps ----------------------- */
	/* -------------------------------------------- */
	function GetMainSteps($id, $version) {
		return ContainerQueryRows("select ps_order, ps_command, ps_workingdir, ps_description, ps_enabled from pipeline_steps where pipeline_id = ? and pipeline_version = ? and ps_supplement <> 1 order by ps_order", 'ii', [$id, $version], __LINE__);
	}


	/* -------------------------------------------- */
	/* ------- CreateContainer -------------------- */
	/* -------------------------------------------- */
	/* queue a container build for the current version of the pipeline. The pipeline module picks up
	   'submitted' rows and does the build */
	function CreateContainer($id, $referenceanalysisid, $runvalidation) {
		if (!ValidID($id,'Pipeline ID')) { return; }

		if (!CanEditPipeline($id)) {
			Error("Only the pipeline owner or a site admin can create a container");
			return;
		}

		$p = GetContainerPipeline($id);
		if ($p == null) {
			Error("Pipeline [$id] not found");
			return;
		}
		$version = (int)$p['pipeline_version'];
		$pipelinename = htmlspecialchars($p['pipeline_name']);

		$pitfalls = GetContainerPitfalls($id, $p, GetMainSteps($id, $version));
		$numblocking = CountBlockingPitfalls($pitfalls);
		if ($numblocking > 0) {
			Error("Container not created. <b>$pipelinename</b> version $version has $numblocking problem(s) that must be fixed first");
			return;
		}

		$rows = ContainerQueryRows("select pipelinecontainer_id from pipeline_containers where pipeline_id = ? and pipeline_version = ? and build_status in ('submitted','started','building')", 'ii', [$id, $version], __LINE__);
		if (count($rows) > 0) {
			Warning("A container for <b>$pipelinename</b> version $version is already being built");
			return;
		}

		/* the reference analysis must be a completed, not-bad analysis of this version */
		$referenceanalysisid = (int)$referenceanalysisid;
		$runvalidation = (int)$runvalidation;
		$refs = GetReferenceAnalyses($id, $version, $referenceanalysisid);
		if (($referenceanalysisid < 1) || (count($refs) < 1)) {
			Error("Container not created. Select a completed analysis of version $version as the reference analysis");
			return;
		}

		$sqlstring = "insert into pipeline_containers (pipeline_id, pipeline_version, build_status, build_createdate, reference_analysisid, run_validation) values (?, ?, 'submitted', now(), ?, ?)";
		$params = [$id, $version, $referenceanalysisid, $runvalidation];
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'iiii', ...$params);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);

		Notice("Container build submitted for <b>$pipelinename</b> version $version, using reference analysis " . ReferenceAnalysisLabel($refs[0]['uid'], $refs[0]['study_num'], $referenceanalysisid) . ($runvalidation ? ", with a validation run" : ", without a validation run"));
	}


	/* -------------------------------------------- */
	/* ------- PendingCancelRequest --------------- */
	/* -------------------------------------------- */
	/* the pending fileio_requests cancel for this container, or null */
	function PendingCancelRequest($containerid) {
		$rows = ContainerQueryRows("select fileiorequest_id, username, requestdate from fileio_requests where fileio_operation = 'cancel' and data_type = 'pipelinecontainer' and data_id = ? and request_status = 'pending' order by fileiorequest_id limit 1", 'i', [$containerid], __LINE__);
		return (count($rows) > 0) ? $rows[0] : null;
	}


	/* -------------------------------------------- */
	/* ------- CancelContainer -------------------- */
	/* -------------------------------------------- */
	/* queue a cancel of an unfinished build for the fileio module (moduleFileIO::CancelPipelineContainer()), which
	   stops the cluster job and sets the row to 'canceled'. The build directory is kept */
	function CancelContainer($id, $containerid) {
		if (!ValidID($id,'Pipeline ID')) { return; }
		if (!ValidID($containerid,'Container ID')) { return; }

		if (!CanEditPipeline($id)) {
			Error("Only the pipeline owner or a site admin can cancel a container build");
			return;
		}

		$rows = ContainerQueryRows("select build_status from pipeline_containers where pipelinecontainer_id = ? and pipeline_id = ?", 'ii', [$containerid, $id], __LINE__);
		if (count($rows) < 1) {
			Error("Container [$containerid] not found for pipeline [$id]");
			return;
		}
		if (!in_array($rows[0]['build_status'], array('submitted','started','building'))) {
			Warning("Container $containerid is " . htmlspecialchars($rows[0]['build_status']) . ", so there's nothing to cancel");
			return;
		}
		if (PendingCancelRequest($containerid) != null) {
			Notice("Container $containerid is already queued to be canceled");
			return;
		}

		$sqlstring = "insert into fileio_requests (fileio_operation, data_type, data_id, username, requestdate) values ('cancel', 'pipelinecontainer', ?, ?, now())";
		$params = [$containerid, $GLOBALS['username']];
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'is', ...$params);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);

		Notice("Container $containerid is queued to be canceled. Its cluster job, if it has one, is stopped within a few minutes. The build directory is kept");
	}


	/* -------------------------------------------- */
	/* ------- ContainerStatusLabel --------------- */
	/* -------------------------------------------- */
	function ContainerStatusLabel($status) {
		switch ($status) {
			case 'submitted': return "<span class='ui grey label'><i class='clock outline icon'></i> Submitted</span>";
			case 'started': return "<span class='ui blue label'><i class='spinner loading icon'></i> Started</span>";
			case 'building': return "<span class='ui blue label'><i class='spinner loading icon'></i> Building</span>";
			case 'complete': return "<span class='ui green label'><i class='check icon'></i> Complete</span>";
			case 'error': return "<span class='ui red label'><i class='exclamation triangle icon'></i> Error</span>";
			case 'canceled': return "<span class='ui orange label'><i class='ban icon'></i> Canceled</span>";
		}
		return "<span class='ui label'>" . htmlspecialchars($status) . "</span>";
	}


	/* -------------------------------------------- */
	/* ------- ContainerStepNames ----------------- */
	/* -------------------------------------------- */
	/* build_step values, in build order, and their display names */
	function ContainerStepNames() {
		return array(
			'preReferenceAnalysisChecks' => 'Checking tools and system',
			'runningReferenceAnalysis' => 'Reference run',
			'postReferenceAnalysisChecks' => 'Capturing software',
			'buildingContainer' => 'Building image',
			'runningValidationAnalysis' => 'Validation run',
			'buildComplete' => 'Build complete');
	}


	/* -------------------------------------------- */
	/* ------- ContainerStepLabel ----------------- */
	/* -------------------------------------------- */
	/* the last step the build job checked in with (analysisapi.php), its message, and the host. For a failed build
	   this is where it stopped */
	function ContainerStepLabel($c) {
		$steps = ContainerStepNames();
		$step = (string)($c['build_step'] ?? '');
		if ($step == "") {
			return ($c['build_status'] == 'building') ? "<div class='ui small grey text'>Waiting in the cluster queue</div>" : "";
		}

		$html = "<div><b>" . htmlspecialchars($steps[$step] ?? $step) . "</b></div>";
		$message = trim((string)($c['build_step_message'] ?? ''));
		if ($message != "") { $html .= "<div class='ui small text'>" . htmlspecialchars($message) . "</div>"; }
		$hostname = trim((string)($c['build_hostname'] ?? ''));
		if ($hostname != "") { $html .= "<div class='ui small grey text'>" . htmlspecialchars($hostname) . "</div>"; }
		return $html;
	}


	/* -------------------------------------------- */
	/* ------- ContainerDatesCell ----------------- */
	/* -------------------------------------------- */
	/* the submitted date, and a clock icon whose tooltip has all four dates */
	function ContainerDatesCell($c) {
		$dates = array(
			'Submitted' => $c['build_createdate'] ?? '',
			'Build started' => $c['build_startdate'] ?? '',
			'Build finished' => $c['build_enddate'] ?? '',
			'Available' => $c['container_date'] ?? '');
		$tooltip = "<table class='ui very basic very compact collapsing table'>";
		foreach ($dates as $label => $date) {
			$date = trim((string)$date);
			$tooltip .= "<tr><td><b>$label</b></td><td style='white-space: nowrap'>" . (($date == "") ? "<span class='ui grey text'>&mdash;</span>" : htmlspecialchars($date)) . "</td></tr>";
		}
		$tooltip .= "</table>";
		$submitted = trim((string)$dates['Submitted']);
		return "<span style='white-space: nowrap'>" . htmlspecialchars($submitted) . " <i class='grey clock outline icon containerDates' data-html=\"" . htmlspecialchars($tooltip, ENT_QUOTES) . "\"></i></span>";
	}


	/* -------------------------------------------- */
	/* ------- ContainerTypeLabel ----------------- */
	/* -------------------------------------------- */
	/* a label for a launcher export (container_type 'referenced'). Nothing for a single .sif, or before the export sets it */
	function ContainerTypeLabel($c) {
		if (($c['container_type'] ?? '') != 'referenced') { return ""; }
		return " <span class='ui tiny basic blue label' title='Exported as run-pipeline.sh plus referenced images, because some steps run their own containers'>launcher</span>";
	}


	/* -------------------------------------------- */
	/* ------- ContainerImages -------------------- */
	/* -------------------------------------------- */
	/* a launcher export's images (pipeline_container_images), in the order the export found them */
	function ContainerImages($containerid) {
		return ContainerQueryRows("select * from pipeline_container_images where pipelinecontainer_id = ? order by pipelinecontainerimage_id", 'i', [$containerid], __LINE__);
	}


	/* -------------------------------------------- */
	/* ------- ReadTabFile ------------------------ */
	/* -------------------------------------------- */
	/* a tab-separated file from the build directory (segments.txt, externalpaths.txt), as arrays of at least $numfields
	   fields. Empty if the file doesn't exist */
	function ReadTabFile($file, $numfields) {
		$rows = array();
		if (!is_file($file)) { return $rows; }
		$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach (($lines === false) ? array() : $lines as $line) {
			$rows[] = array_pad(explode("\t", $line), $numfields, "");
		}
		return $rows;
	}


	/* -------------------------------------------- */
	/* ------- DisplayContainerTable -------------- */
	/* -------------------------------------------- */
	function DisplayContainerTable($containers, $currentversion, $showsize) {
		?>
		<table class="ui very compact celled table">
			<thead>
				<tr>
					<!--<th>ID</th>-->
					<th>Version</th>
					<th>Status</th>
					<th>Dates</th>
					<? if ($showsize) { ?>
					<th>Size</th>
					<? } ?>
					<th>Warnings &amp; log</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
			<?
			foreach ($containers as $c) {
				$containerid = (int)$c['pipelinecontainer_id'];
				$cversion = (int)$c['pipeline_version'];
				$size = ((int)($c['container_size'] ?? 0) > 0) ? HumanReadableFilesize((int)$c['container_size']) : "";
				$warnings = trim((string)($c['build_warnings'] ?? ''));
				$log = trim((string)($c['build_log'] ?? ''));
				?>
				<tr>
					<!--<td><?=$containerid?></td>-->
					<td><?=$cversion?><?=ContainerTypeLabel($c)?></td>
					<td><?=ContainerStatusLabel($c['build_status'])?><?=ContainerStepLabel($c)?></td>
					<td><?=ContainerDatesCell($c)?></td>
					<? if ($showsize) { ?>
					<td><?=$size?></td>
					<? } ?>
					<td>
						<? if ($warnings != "") { ?>
						<div class="ui accordion">
							<div class="title"><i class="dropdown icon"></i> <i class="orange exclamation triangle icon"></i> View warnings</div>
							<div class="content"><pre style="max-height: 400px; overflow: auto; font-size: smaller; white-space: pre-wrap"><?=htmlspecialchars($warnings)?></pre></div>
						</div>
						<? } ?>
						<? if ($log != "") { ?>
						<div class="ui accordion">
							<div class="title"><i class="dropdown icon"></i> View log</div>
							<div class="content"><pre style="max-height: 400px; overflow: auto; font-size: smaller; white-space: pre-wrap"><?=htmlspecialchars($log)?></pre></div>
						</div>
						<? } ?>
					</td>
					<td style="white-space: nowrap"><a href="pipelinecontainers.php?action=viewcontainer&id=<?=(int)$c['pipeline_id']?>&containerid=<?=$containerid?>"><i class="info circle icon"></i>Details</a></td>
				</tr>
				<?
			}
			?>
			</tbody>
		</table>
		<script>
			$(document).ready(function() { $('.containerDates').popup({ variation: 'small' }); });
		</script>
		<?
	}


	/* -------------------------------------------- */
	/* ------- ContainerBuildDir ------------------ */
	/* -------------------------------------------- */
	/* the build directory as NiDB sees it, the same path as modulePipelineContainer::GetBuildDir(). Empty if
	   cfg analysisdir isn't set. container_path is the cluster's path */
	function ContainerBuildDir($containerid) {
		$analysisdir = rtrim(trim($GLOBALS['cfg']['analysisdir'] ?? ''), '/');
		return ($analysisdir == "") ? "" : $analysisdir . "/_apptainer/" . (int)$containerid;
	}


	/* -------------------------------------------- */
	/* ------- ContainerFileLink ------------------ */
	/* -------------------------------------------- */
	/* a link to a file in the build directory, through viewfile.php if it would allow it. Empty if there's no file */
	function ContainerFileLink($file, $label) {
		if (!is_file($file)) { return ""; }
		$real = realpath($file);
		if (($real === false) || !IsViewablePath($real)) { return "<tt>" . htmlspecialchars($label) . "</tt>"; }
		return "<a href='viewfile.php?file=" . urlencode($file) . "' target='_blank'><tt>" . htmlspecialchars($label) . "</tt></a>";
	}


	/* -------------------------------------------- */
	/* ------- DisplayContainerDetails ------------ */
	/* -------------------------------------------- */
	/* everything about one container build: the build, the reference analysis and validation, the image, the build
	   directory and its reports, and the full warnings and log */
	function DisplayContainerDetails($id, $containerid) {
		ShowFlashMessage(); /* show any message from the cancel action that redirected here (PRG) */

		if (!ValidID($id,'Pipeline ID')) { return; }
		if (!ValidID($containerid,'Container ID')) { return; }

		$rows = ContainerQueryRows("select a.*, p.pipeline_name, p.pipeline_version 'current_version', d.uid, b.study_num from pipeline_containers a left join pipelines p on a.pipeline_id = p.pipeline_id left join analysis r on a.reference_analysisid = r.analysis_id left join studies b on r.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipelinecontainer_id = ? and a.pipeline_id = ?", 'ii', [$containerid, $id], __LINE__);
		if (count($rows) < 1) {
			Error("Container [$containerid] not found for pipeline [$id]");
			return;
		}
		$c = $rows[0];
		$cversion = (int)$c['pipeline_version'];
		$path = trim((string)($c['container_path'] ?? ''));
		$sha256 = trim((string)($c['container_sha256'] ?? ''));
		$containersize = (int)($c['container_size'] ?? 0);
		$buildsize = (int)($c['build_size'] ?? 0);
		$buildfilecount = (int)($c['build_filecount'] ?? 0);
		$warnings = trim((string)($c['build_warnings'] ?? ''));
		$log = trim((string)($c['build_log'] ?? ''));
		$hostname = trim((string)($c['build_hostname'] ?? ''));

		$builddir = ContainerBuildDir($containerid);
		$hasdir = ($builddir != "") && is_dir($builddir);

		/* a launcher export (doc section 11): its images are in the database; its segments and external paths are only
		   in the build directory */
		$launcher = (($c['container_type'] ?? '') == 'referenced');
		$images = $launcher ? ContainerImages($containerid) : array();
		$segments = ($launcher && $hasdir) ? ReadTabFile("$builddir/segments.txt", 4) : array();
		$extpaths = ($launcher && $hasdir) ? ReadTabFile("$builddir/externalpaths.txt", 3) : array();

		/* an unfinished build can be canceled by the pipeline owner or a site admin */
		$cancancel = in_array($c['build_status'], array('submitted','started','building')) && CanEditPipeline($id);
		$pendingcancel = $cancancel ? PendingCancelRequest($containerid) : null;

		/* the cluster job ID, written by the module when it submitted the job */
		$jobid = "";
		if ($hasdir && is_file("$builddir/build.jobid")) { $jobid = trim((string)file_get_contents("$builddir/build.jobid")); }

		/* how long the build ran */
		$duration = "";
		$start = strtotime((string)($c['build_startdate'] ?? ''));
		$end = strtotime((string)($c['build_enddate'] ?? ''));
		if (($start !== false) && ($end !== false) && ($end >= $start)) {
			$secs = $end - $start;
			$duration = sprintf("%d:%02d:%02d", intdiv($secs, 3600), intdiv($secs % 3600, 60), $secs % 60);
		}

		/* validation.txt: key=value lines, then the [missing] and [extra] file lists */
		$validation = array();
		if ($hasdir && is_file("$builddir/validation.txt")) {
			$lines = file("$builddir/validation.txt", FILE_IGNORE_NEW_LINES);
			foreach (($lines === false) ? array() : $lines as $line) {
				if (trim($line) == "") { break; }
				$parts = explode("=", $line, 2);
				if (count($parts) == 2) { $validation[$parts[0]] = $parts[1]; }
			}
		}

		$dates = array(
			'Submitted' => $c['build_createdate'] ?? '',
			'Build started' => $c['build_startdate'] ?? '',
			'Build finished' => $c['build_enddate'] ?? '',
			'Available' => $c['container_date'] ?? '');

		/* the reports in the build directory, in the order they're first written, and the steps that can write each one
		   (first and last column, inclusive): modulePipelineContainer's export on the NiDB server, then build-container.sh
		   on the compute node. build.log and build-warnings.txt are created at the job start and added to by the steps
		   after it; build-error.txt is written by whichever step fails */
		$stepcolumns = array(
			'export' => array('Export (NiDB)', 'Export, on the NiDB server', 'nidb'),
			'start' => array('Build job start', 'Build job start, before the first check-in', 'cluster'),
			'preReferenceAnalysisChecks' => array('Pre-checks', 'Checking tools and system', 'cluster'),
			'runningReferenceAnalysis' => array('Reference run', 'Reference run', 'cluster'),
			'postReferenceAnalysisChecks' => array('Capturing software', 'Capturing software', 'cluster'),
			'buildingContainer' => array('Building image', 'Building image', 'cluster'),
			'runningValidationAnalysis' => array('Validation run', 'Validation run', 'cluster'));
		$stepnames = ContainerStepNames();
		$failedstep = (($c['build_status'] == 'error') && isset($stepnames[(string)($c['build_step'] ?? '')])) ? $stepnames[$c['build_step']] : '';
		$reports = array(
			'README.txt' => array('Usage, also the image\'s help', 'export', 'export'),
			'export.log' => array('Export checks', 'export', 'export'),
			'run-pipeline.sh' => array('The launcher', 'export', 'export'),
			'segments.txt' => array('Launcher segments: native steps run in pipeline.sif, container steps from the host', 'export', 'export'),
			'images-export.txt' => array('Images the container steps run, as written in the steps', 'export', 'export'),
			'externalpaths.txt' => array('Host paths the user gives with --path', 'export', 'export'),
			'input.log' => array('Staging the reference input', 'export', 'export'),
			'build.job' => array('The cluster job', 'export', 'export'),
			'build.log' => array('Build log, from the compute node', 'start', 'runningValidationAnalysis'),
			'build-warnings.txt' => array('Warnings', 'start', 'runningValidationAnalysis'),
			'env.txt' => array('Login environment on the build node', 'start', 'start'),
			'sysinfo.txt' => array('Build node: OS, base image, tools, resources', 'preReferenceAnalysisChecks', 'preReferenceAnalysisChecks'),
			'software.txt' => array('Interpreters and versions on the build node', 'preReferenceAnalysisChecks', 'preReferenceAnalysisChecks'),
			'images-info.txt' => array('Images, pinned by digest', 'preReferenceAnalysisChecks', 'preReferenceAnalysisChecks'),
			'build-error.txt' => array('Why the build stopped' . (($failedstep == "") ? "" : ", during $failedstep"), 'preReferenceAnalysisChecks', 'runningValidationAnalysis'),
			'reference-run.log' => array('Output of the reference run', 'runningReferenceAnalysis', 'runningReferenceAnalysis'),
			'reference-segments.txt' => array('Wall time and peak memory of each segment in the reference run', 'runningReferenceAnalysis', 'runningReferenceAnalysis'),
			'captured-packages.txt' => array('Packages copied into the image', 'postReferenceAnalysisChecks', 'postReferenceAnalysisChecks'),
			'captured-opt.txt' => array('/opt directories copied into the image', 'postReferenceAnalysisChecks', 'postReferenceAnalysisChecks'),
			'captured-paths.txt' => array('Other paths copied into the image', 'postReferenceAnalysisChecks', 'postReferenceAnalysisChecks'),
			'capture-excluded.txt' => array('Deny-listed files that weren\'t copied', 'postReferenceAnalysisChecks', 'postReferenceAnalysisChecks'),
			'environment.txt' => array('The image\'s environment', 'postReferenceAnalysisChecks', 'postReferenceAnalysisChecks'),
			'manifest.json' => array('Pipeline, input layout and captured software', 'buildingContainer', 'buildingContainer'),
			'labels.txt' => array('The image\'s labels', 'buildingContainer', 'buildingContainer'),
			'validation-run.log' => array('Output of the validation run', 'runningValidationAnalysis', 'runningValidationAnalysis'),
			'validation.txt' => array('Validation summary and file lists', 'runningValidationAnalysis', 'runningValidationAnalysis'));
		$none = "<span class='ui grey text'>&mdash;</span>";
		?>
		<div class="ui container">
			<div class="ui two column middle aligned grid">
				<div class="column">
					<h2 class="ui header">
						<i class="box icon"></i>
						<div class="content">
							Container <?=$containerid?>
							<div class="sub header"><a href="pipelinecontainers.php?id=<?=$id?>"><?=htmlspecialchars($c['pipeline_name'] ?? '')?></a> &nbsp; version <?=$cversion?> <? if ($cversion == (int)$c['current_version']) { ?><span class="ui tiny basic green label">current</span><? } ?></div>
						</div>
					</h2>
				</div>
				<div class="right aligned column">
					<a href="pipelinecontainers.php?id=<?=$id?>" class="ui basic button"><i class="arrow left icon"></i> Back to containers</a>
					<a href="pipelinecontainers.php?action=viewfiles&id=<?=$id?>&containerid=<?=$containerid?>" class="ui basic button" target="_viewfiles"><i class="folder open outline icon"></i> View files</a>
					<? if ($cancancel) { ?>
						<? if ($pendingcancel != null) { ?>
						<button class="ui disabled orange button" title="Requested by <?=htmlspecialchars($pendingcancel['username'] ?? '')?> at <?=htmlspecialchars($pendingcancel['requestdate'] ?? '')?>"><i class="hourglass half icon"></i> Cancel requested</button>
						<? } else { ?>
						<form action="pipelinecontainers.php" method="post" style="display: inline" onsubmit="return confirm('Cancel container build <?=$containerid?>? Its cluster job is stopped, and the build directory is kept');">
							<input type="hidden" name="action" value="cancel">
							<input type="hidden" name="id" value="<?=$id?>">
							<input type="hidden" name="containerid" value="<?=$containerid?>">
							<button type="submit" class="ui orange button"><i class="ban icon"></i> Cancel build</button>
						</form>
						<? } ?>
					<? } ?>
				</div>
			</div>

			<div class="ui two column stackable grid">
				<div class="column">
					<h3 class="ui dividing header">Build</h3>
					<table class="ui very basic compact table">
						<tr><td class="four wide"><b>Status</b></td><td><?=ContainerStatusLabel($c['build_status'])?><?=ContainerStepLabel($c)?></td></tr>
						<tr><td><b>Build node</b></td><td><?=($hostname == "") ? $none : htmlspecialchars($hostname)?></td></tr>
						<tr><td><b>Cluster job</b></td><td><?=($jobid == "") ? $none : htmlspecialchars($jobid)?></td></tr>
						<? foreach ($dates as $label => $date) { $date = trim((string)$date); ?>
						<tr><td><b><?=$label?></b></td><td><?=($date == "") ? $none : htmlspecialchars($date)?></td></tr>
						<? } ?>
						<tr><td><b>Duration</b></td><td><?=($duration == "") ? $none : $duration?></td></tr>
					</table>
				</div>
				<div class="column">
					<h3 class="ui dividing header">Reference analysis &amp; validation</h3>
					<table class="ui very basic compact table">
						<tr><td class="four wide"><b>Reference analysis</b></td><td><?=((int)($c['reference_analysisid'] ?? 0) == 0) ? $none : ReferenceAnalysisLabel($c['uid'], $c['study_num'], $c['reference_analysisid'])?></td></tr>
						<tr><td><b>Validation run</b></td><td><?=($c['run_validation'] ?? 0) ? "Yes" : "No"?></td></tr>
						<? if (count($validation) > 0) {
							$missing = (int)($validation['missing_files'] ?? 0);
							$extra = (int)($validation['extra_files'] ?? 0);
							$exitcode = (string)($validation['exitcode'] ?? '');
							?>
						<tr><td><b>Exit code</b></td><td><?=htmlspecialchars($exitcode)?> <? if (($exitcode != "") && ($exitcode != "0")) { ?><i class="orange exclamation triangle icon"></i><? } ?></td></tr>
						<tr><td><b>Output files</b></td><td><?=number_format((int)($validation['container_files'] ?? 0))?> from the container, <?=number_format((int)($validation['reference_files'] ?? 0))?> in the reference analysis</td></tr>
						<tr><td><b>Missing</b></td><td><span class="<?=($missing > 0) ? 'ui orange text' : ''?>"><?=number_format($missing)?></span> <span class="ui small grey text">in the reference analysis, not created by the container</span></td></tr>
						<tr><td><b>Extra</b></td><td><?=number_format($extra)?> <span class="ui small grey text">created by the container, not in the reference analysis</span></td></tr>
						<tr><td><b>Details</b></td><td><?=ContainerFileLink("$builddir/validation.txt", "validation.txt")?></td></tr>
						<? } elseif ($c['run_validation'] ?? 0) { ?>
						<tr><td><b>Results</b></td><td><span class="ui grey text">No validation results yet</span></td></tr>
						<? } ?>
					</table>
				</div>
			</div>

			<h3 class="ui dividing header">Container</h3>
			<table class="ui very basic compact table">
				<tr><td class="three wide"><b>Type</b></td><td><?
					switch ($c['container_type'] ?? '') {
						case 'referenced': echo "Launcher: <tt>run-pipeline.sh</tt>, which runs the native steps in <tt>pipeline.sif</tt> and each container step's image, downloaded from its pinned reference"; break;
						case 'single': echo "Single image (<tt>.sif</tt>)"; break;
						default: echo $none;
					}
				?></td></tr>
				<tr><td><b><?=$launcher ? "Directory" : "Path"?></b> <span class="ui small grey text">(on the cluster)</span></td><td><?=($path == "") ? $none : "<tt>" . htmlspecialchars($path) . "</tt>"?></td></tr>
				<tr><td><b>Size</b></td><td><?=($containersize > 0) ? HumanReadableFilesize($containersize) . " <span class='ui small grey text'>(" . number_format($containersize) . " bytes" . ($launcher ? ", not counting the images" : "") . ")</span>" : $none?></td></tr>
				<tr><td><b>SHA256</b><? if ($launcher) { ?> <span class="ui small grey text">(of checksums.sha256)</span><? } ?></td><td><?=($sha256 == "") ? $none : "<tt style='word-break: break-all'>" . htmlspecialchars($sha256) . "</tt>"?></td></tr>
			</table>

			<? if ($launcher) { ?>
			<h4 class="ui header">Images <div class="sub header">Not in the export. <tt>run-pipeline.sh</tt> downloads each one from its pinned reference, the first time it runs</div></h4>
			<? if (count($images) == 0) { ?>
			<div class="ui message">No images recorded</div>
			<? } else { ?>
			<table class="ui very compact celled table">
				<thead>
					<tr>
						<th>Name</th>
						<th>Steps</th>
						<th>As written in the step</th>
						<th>Pinned reference</th>
						<th>Local image</th>
						<th>Validated</th>
					</tr>
				</thead>
				<tbody>
				<? foreach ($images as $img) {
					$reference = trim((string)($img['image_reference'] ?? ''));
					$tag = trim((string)($img['image_tag'] ?? ''));
					$localsha = trim((string)($img['image_localsha256'] ?? ''));
					$localsize = (int)($img['image_localsize'] ?? 0);
					?>
					<tr>
						<td><tt><?=htmlspecialchars($img['image_name'])?></tt></td>
						<td><?=htmlspecialchars(str_replace(",", ", ", (string)$img['image_steps']))?></td>
						<td><tt style="word-break: break-all"><?=htmlspecialchars($img['image_source'])?></tt></td>
						<td><? if ($reference == "") { ?><span class="ui grey text">Not pinned yet. The build pins it</span><? } else { ?><tt style="word-break: break-all"><?=htmlspecialchars($reference)?></tt><? if (($tag != "") && ($tag != $img['image_source'])) { ?><br><span class="ui small grey text">from <tt><?=htmlspecialchars($tag)?></tt></span><? } ?><? } ?></td>
						<td><? if ($localsha == "") { echo $none; } else { ?><?=($localsize > 0) ? HumanReadableFilesize($localsize) . "<br>" : ""?><span class="ui small grey text" title="sha256 of the .sif on the build node, for the record. A user's download won't match it, since converting a Docker image isn't byte-identical"><tt><?=htmlspecialchars(substr($localsha, 0, 12))?>&hellip;</tt></span><? } ?></td>
						<td><?=$img['image_validated'] ? "<i class='green check icon'></i> Yes" : (($reference == "") ? $none : "<i class='orange exclamation triangle icon'></i> No")?></td>
					</tr>
				<? } ?>
				</tbody>
			</table>
			<? } ?>

			<? if (count($segments) > 0) { ?>
			<h4 class="ui header">Segments <div class="sub header">The order <tt>run-pipeline.sh</tt> runs the steps. Shell variables don't carry from one segment to the next</div></h4>
			<table class="ui very compact celled collapsing table">
				<thead><tr><th>Segment</th><th>Steps</th><th>Runs in</th></tr></thead>
				<tbody>
				<? foreach ($segments as list($num, $kind, $segimages, $segsteps)) { ?>
					<tr>
						<td><?=(int)$num?></td>
						<td><?=htmlspecialchars(str_replace(",", ", ", $segsteps))?></td>
						<td><?=($kind == 'native') ? "<tt>pipeline.sif</tt>" : "<tt>" . htmlspecialchars(str_replace(",", ", ", $segimages)) . "</tt>, from the host"?></td>
					</tr>
				<? } ?>
				</tbody>
			</table>
			<? } ?>

			<h4 class="ui header">External paths <div class="sub header">Host files or directories the user gives with <tt>--path NAME=PATH</tt>. <tt>run-pipeline.sh</tt> won't start without them</div></h4>
			<? if (!$hasdir) { ?>
			<div class="ui message">Unknown. They're listed in the build directory, which wasn't found</div>
			<? } elseif (count($extpaths) == 0) { ?>
			<div class="ui message">None</div>
			<? } else { ?>
			<table class="ui very compact celled collapsing table">
				<thead><tr><th>Name</th><th>Steps</th><th>Path on the build node</th></tr></thead>
				<tbody>
				<? foreach ($extpaths as list($pname, $psource, $psteps)) { ?>
					<tr>
						<td><tt><?=htmlspecialchars($pname)?></tt></td>
						<td><?=htmlspecialchars(str_replace(",", ", ", $psteps))?></td>
						<td><tt><?=htmlspecialchars($psource)?></tt></td>
					</tr>
				<? } ?>
				</tbody>
			</table>
			<? } ?>
			<? } ?>

			<h3 class="ui dividing header">Build directory</h3>
			<table class="ui very basic compact table">
				<tr><td class="three wide"><b>Path</b> <span class="ui small grey text">(as NiDB sees it)</span></td><td><?=($builddir == "") ? $none : "<tt>" . htmlspecialchars($builddir) . "</tt>"?> <? if (($builddir != "") && !$hasdir) { ?><span class="ui orange text">Not found. It's created when the build starts. It may have been moved or deleted</span><? } ?></td></tr>
				<tr><td><b>Left on disk</b></td><td><?=(($buildsize > 0) || ($buildfilecount > 0)) ? number_format($buildfilecount) . " files, " . HumanReadableFilesize($buildsize) . " <span class='ui small grey text'>when the build finished" . (($c['build_status'] == 'error') ? ". A failed build keeps everything, for diagnosis" : "") . "</span>" : $none?></td></tr>
			</table>
			<? if ($hasdir) { ?>
		</div>
		<!-- outside the ui container, so the table can use the page width. The container reopens after it -->
		<div style="margin: 0 1em">
			<h4 class="ui header">
				Build reports
				<div class="sub header">In the order they're first written. Each report spans the build steps that can write to it</div>
			</h4>
			<table class="ui very compact celled table">
				<thead>
					<?
					/* where each step runs, over its step columns: consecutive steps in the same place share one heading */
					$locations = array();
					foreach ($stepcolumns as list($header, $title, $location)) {
						$n = count($locations);
						if (($n > 0) && ($locations[$n - 1][0] == $location)) { $locations[$n - 1][1]++; }
						else { $locations[] = array($location, 1); }
					}
					?>
					<tr>
						<th rowspan="2">Report description</th>
						<? foreach ($locations as list($location, $span)) { ?>
						<th colspan="<?=$span?>" class="center aligned"><?=($location == 'nidb') ? "NiDB" : (($hostname == "") ? "Cluster" : "Cluster (" . htmlspecialchars($hostname) . ")")?></th>
						<? } ?>
						<th rowspan="2">File date</th>
						<th rowspan="2" class="right aligned">Size</th>
					</tr>
					<tr>
						<? foreach ($stepcolumns as list($header, $title, $location)) { ?>
						<th title="<?=htmlspecialchars($title)?>"><?=htmlspecialchars($header)?></th>
						<? } ?>
					</tr>
				</thead>
				<tbody>
				<?
				$stepkeys = array_keys($stepcolumns);
				foreach ($reports as $file => list($desc, $firststep, $laststep)) {
					$link = ContainerFileLink("$builddir/$file", $file);
					if ($link == "") { continue; }
					$first = array_search($firststep, $stepkeys, true);
					$last = array_search($laststep, $stepkeys, true);
					?>
					<tr>
						<td><?=htmlspecialchars($desc)?></td>
						<? for ($i = 0; $i < $first; $i++) { ?><td></td><? } ?>
						<td colspan="<?=$last - $first + 1?>" style="background-color: #f3f4f5; border-left: 3px solid #a0a0a0; white-space: nowrap"><?=$link?></td>
						<? for ($i = $last + 1; $i < count($stepkeys); $i++) { ?><td></td><? } ?>
						<td style="white-space: nowrap"><?=date("Y-m-d H:i:s", filemtime("$builddir/$file"))?></td>
						<td class="right aligned" style="white-space: nowrap"><?=HumanReadableFilesize(filesize("$builddir/$file"))?></td>
					</tr>
				<? } ?>
				</tbody>
			</table>
		</div>
		<div class="ui container">
			<br>
			<? } ?>

			<h3 class="ui dividing header"><i class="orange exclamation triangle icon"></i> Warnings</h3>
			<? if ($warnings == "") { ?>
			<div class="ui message">No warnings</div>
			<? } else { ?>
			<pre class="ui segment" style="max-height: 400px; overflow: auto; font-size: smaller; white-space: pre-wrap"><?=htmlspecialchars($warnings)?></pre>
			<? } ?>

			<h3 class="ui dividing header">Log (NiDB module log)</h3>
			<? if ($log == "") { ?>
			<div class="ui message">No log entries</div>
			<? } else { ?>
			<pre class="ui segment" style="max-height: 600px; overflow: auto; font-size: smaller; white-space: pre-wrap"><?=htmlspecialchars($log)?></pre>
			<? } ?>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayContainerFiles -------------- */
	/* -------------------------------------------- */
	/* the files on disk for one container: the build directory (cfg analysisdir/_apptainer/<id>), where the finished
	   .sif stays, next to the logs and reports. Like viewanalysis.php?action=viewfiles */
	function DisplayContainerFiles($id, $containerid) {
		if (!ValidID($id,'Pipeline ID')) { return; }
		if (!ValidID($containerid,'Container ID')) { return; }

		$rows = ContainerQueryRows("select a.*, p.pipeline_name from pipeline_containers a left join pipelines p on a.pipeline_id = p.pipeline_id where a.pipelinecontainer_id = ? and a.pipeline_id = ?", 'ii', [$containerid, $id], __LINE__);
		if (count($rows) < 1) {
			Error("Container [$containerid] not found for pipeline [$id]");
			return;
		}
		$c = $rows[0];

		$dirs = array();
		$builddir = ContainerBuildDir($containerid);
		if ($builddir != "") {
			$dirs['Build directory'] = array($builddir, "Not found. It's created when the build starts. The container may have been moved or deleted");
		}
		$path = trim((string)($c['container_path'] ?? ''));
		?>
		<div class="ui container">
			<h2 class="ui header">
				<i class="folder open outline icon"></i>
				<div class="content">
					Container <?=$containerid?> files
					<div class="sub header"><a href="pipelinecontainers.php?id=<?=$id?>"><?=htmlspecialchars($c['pipeline_name'] ?? '')?></a> &nbsp; version <?=(int)$c['pipeline_version']?> &nbsp; <?=ContainerStatusLabel($c['build_status'])?></div>
				</div>
			</h2>
			<? if ($path != "") { ?>
			<div class="ui message">Container, as the cluster sees it: <tt><?=htmlspecialchars($path)?></tt></div>
			<? } ?>
			<?
			foreach ($dirs as $title => list($dir, $missing)) {
				?>
				<h3 class="ui dividing header"><?=$title?> <div class="sub header"><tt><?=htmlspecialchars($dir)?></tt></div></h3>
				<?
				if (!is_dir($dir)) {
					?><div class="ui message"><?=$missing?></div><?
					continue;
				}
				DisplayContainerFileList($dir);
			}
			?>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayContainerFileList ----------- */
	/* -------------------------------------------- */
	/* a recursive listing of $dir. Symlinks aren't followed. Files are linked to the viewers when viewfile.php
	   would allow them (IsViewablePath()) */
	function DisplayContainerFileList($dir) {
		$maxfiles = 5000;
		$entries = array();
		$truncated = false;
		try {
			$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD);
			foreach ($it as $file => $info) {
				if (count($entries) >= $maxfiles) { $truncated = true; break; }
				$entries[] = $file;
			}
		}
		catch (Exception $e) {
			Error("Unable to read [" . htmlspecialchars($dir) . "]: " . htmlspecialchars($e->getMessage()));
			return;
		}
		sort($entries);

		$totalsize = 0;
		foreach ($entries as $file) {
			if (is_file($file) && !is_link($file)) { $totalsize += filesize($file); }
		}
		?>
		<p><?=number_format(count($entries))?> files and directories, <?=HumanReadableFilesize($totalsize)?><? if ($truncated) { ?>. <b>Only the first <?=number_format($maxfiles)?> are shown</b><? } ?></p>
		<? if (count($entries) == 0) { return; } ?>
		<table class="ui very compact celled sortable table containerfiles">
			<thead>
				<tr>
					<th>File</th>
					<th>Timestamp</th>
					<th>Permissions</th>
					<th class="right aligned">Size <span class="tiny">bytes</span></th>
				</tr>
			</thead>
			<tbody>
			<?
			foreach ($entries as $file) {
				$stat = lstat($file);
				$islink = is_link($file);
				$isdir = !$islink && is_dir($file);
				$relative = substr($file, strlen($dir) + 1);
				$lastslash = strrpos($relative, '/');
				$display = ($lastslash === false) ? "<b>" . htmlspecialchars($relative) . "</b>" : htmlspecialchars(substr($relative, 0, $lastslash + 1)) . "<b>" . htmlspecialchars(substr($relative, $lastslash + 1)) . "</b>";

				if ($isdir) {
					$display = "<span style='color: darkblue'>$display/</span>";
				}
				elseif ($islink) {
					$display = "<span style='color: red'>$display</span> &rarr; " . htmlspecialchars((string)readlink($file));
				}
				elseif (IsViewablePath(realpath($file))) {
					$lower = strtolower($file);
					if (preg_match('/\.nii(\.gz)?$/', $lower)) {
						$display = "<a href='niiview.php?type=nifti&filename=" . urlencode($file) . "'>$display</a>";
					}
					elseif (preg_match('/\.(png|jpe?g|gif|ppm)$/', $lower)) {
						$display = "<a href='viewimagefile.php?file=" . urlencode($file) . "'>$display</a>";
					}
					else {
						$display = "<a href='viewfile.php?file=" . urlencode($file) . "'>$display</a>";
					}
				}
				?>
				<tr>
					<td data-sort-value="<?=htmlspecialchars($relative)?>"><?=$display?></td>
					<td data-sort-value="<?=($stat !== false) ? (int)$stat['mtime'] : 0?>"><?=($stat !== false) ? date("M j, Y H:i:s", $stat['mtime']) : ''?></td>
					<td><tt><?=($stat !== false) ? FilePermissionString($stat['mode']) : ''?></tt></td>
					<td class="right aligned" data-sort-value="<?=(($stat !== false) && !$isdir) ? (int)$stat['size'] : -1?>"><?=(($stat !== false) && !$isdir) ? number_format($stat['size']) : ''?></td>
				</tr>
				<?
			}
			?>
			</tbody>
		</table>
		<script>
			/* click a column header to sort. Cells sort by data-sort-value: the relative path, mtime, and size in bytes (directories are -1) */
			$(document).ready(function() { $('table.containerfiles').tablesort(); });
		</script>
		<?
	}


	/* -------------------------------------------- */
	/* ------- FilePermissionString --------------- */
	/* -------------------------------------------- */
	/* rwxr-xr-x from a stat() mode */
	function FilePermissionString($mode) {
		$s = "";
		foreach (array(0400, 0200, 0100, 040, 020, 010, 04, 02, 01) as $i => $bit) {
			$s .= ($mode & $bit) ? substr("rwx", $i % 3, 1) : "-";
		}
		return $s;
	}


	/* -------------------------------------------- */
	/* ------- DisplayContainers ------------------ */
	/* -------------------------------------------- */
	function DisplayContainers($id) {
		ShowFlashMessage(); /* show any message from a mutating action that redirected here (PRG) */

		if (!ValidID($id,'Pipeline ID')) { return; }
		$p = GetContainerPipeline($id);
		if ($p == null) {
			Error("Pipeline [$id] not found");
			return;
		}
		$version = (int)$p['pipeline_version'];
		$pipelinename = $p['pipeline_name'];
		$canedit = CanEditPipeline($id);

		$steps = GetMainSteps($id, $version);
		$pitfalls = GetContainerPitfalls($id, $p, $steps);
		$numblocking = CountBlockingPitfalls($pitfalls);
		$required = GetRequiredVariables(GetContainerVariables($steps));
		$refs = GetReferenceAnalyses($id, $version);

		/* existing containers, split into in-progress and finished */
		$inprogress = array();
		$finished = array();
		$latestcomplete = null;
		$rows = ContainerQueryRows("select * from pipeline_containers where pipeline_id = ? order by pipelinecontainer_id desc", 'i', [$id], __LINE__);
		foreach ($rows as $row) {
			if (in_array($row['build_status'], array('submitted','started','building'))) {
				$inprogress[] = $row;
			}
			else {
				$finished[] = $row;
				if (($latestcomplete == null) && ($row['build_status'] == 'complete')) { $latestcomplete = $row; }
			}
		}
		$versioninprogress = false;
		foreach ($inprogress as $row) {
			if ((int)$row['pipeline_version'] == $version) { $versioninprogress = true; }
		}

		/* example usage. Use the latest complete container's filename if there is one */
		$siffile = preg_replace('/[^A-Za-z0-9_.-]/', '_', $pipelinename) . "-v$version.sif";
		if (($latestcomplete != null) && (trim($latestcomplete['container_path'] ?? '') != "")) {
			$siffile = basename($latestcomplete['container_path']);
		}
		$examplevalues = array('subjectuid' => 'S1234ABC', 'studynum' => '1', 'studydatetime' => '"2024-03-01 10:15:00"');
		$args = array();
		foreach ($required as $var) { $args[] = "--$var " . $examplevalues[$var]; }
		$runcmd = "apptainer run \\\n    --bind /data/sub01:/input \\\n    --bind /results/sub01:/output \\\n    $siffile";
		if (count($args) > 0) { $runcmd .= " \\\n    " . implode(" ", $args); }

		/* steps that run their own containers make the export a launcher (doc section 11). Its external paths are only
		   known after an export, so they come from the latest complete launcher export of any version, if there is one */
		$launcher = IsLauncherExport($steps);
		$exportdir = preg_replace('/[^A-Za-z0-9_.-]/', '_', $pipelinename) . "-v$version";
		$extpaths = array();
		if ($launcher && ($latestcomplete != null) && (($latestcomplete['container_type'] ?? '') == 'referenced')) {
			if (trim($latestcomplete['container_path'] ?? '') != "") { $exportdir = basename($latestcomplete['container_path']); }
			$latestdir = ContainerBuildDir($latestcomplete['pipelinecontainer_id']);
			if ($latestdir != "") { $extpaths = ReadTabFile("$latestdir/externalpaths.txt", 3); }
		}
		$launchcmd = "./run-pipeline.sh \\\n    --input /data/sub01 \\\n    --output /results/sub01";
		if (count($args) > 0) { $launchcmd .= " \\\n    " . implode(" ", $args); }
		foreach ($extpaths as list($pname, $psource, $psteps)) { $launchcmd .= " \\\n    --path $pname=/path/to/" . basename($psource); }
		$launchcmd .= " \\\n    --image-cache /project/nidb-images";

		$datadefs = ContainerQueryRows("select * from pipeline_data_def where pipeline_id = ? and pipeline_version = ? and pdd_enabled = 1 order by pdd_order", 'ii', [$id, $version], __LINE__);
		$dependencyids = array_values(array_filter(array_map('intval', explode(",", (string)($p['pipeline_dependency'] ?? '')))));
		?>
		<div class="ui container">
			<div class="ui two column middle aligned grid">
				<div class="column">
					<h1 class="ui header">
						<i class="box icon"></i>
						<div class="content">
							Containers
							<div class="sub header"><a href="pipelines.php?action=editpipeline&id=<?=$id?>"><?=htmlspecialchars($pipelinename)?></a> &nbsp; version <?=$version?></div>
						</div>
					</h1>
				</div>
				<div class="right aligned column">
					<a href="pipelines.php?action=editpipeline&id=<?=$id?>&returntab=operations" class="ui basic button"><i class="arrow left icon"></i> Back to pipeline</a>
				</div>
			</div>

			<p>A container is a standalone <a href="https://apptainer.org/">Apptainer</a> (<tt>.sif</tt>) image of this pipeline. The container holds this pipeline's main script plus the software it uses, allowing anyone to run this pipeline without NiDB. The container would run on a <b>single</b> imaging study, like a single analysis. Input data must be formatted according to the specifications before running the container. Pipelines with steps that run their own containers are exported as a launcher instead, which runs those containers next to the pipeline's image (see usage below).</p>

			<!-- ---------- in progress ---------- -->
			<br>
			<? if (count($inprogress) > 0) { ?>
			<h2 class="ui dividing header">Exports in progress</h2>
			<? DisplayContainerTable($inprogress, $version, false); ?>
			<? } ?>

			<!-- ---------- existing ---------- -->
			<br>
			<h2 class="ui dividing header">Completed containers</h2>
			<? if (count($finished) == 0) { ?>
				<div class="ui message">No containers have been built for this pipeline yet</div>
			<? } else { ?>
				<? DisplayContainerTable($finished, $version, true); ?>
			<? } ?>

			<!-- ---------- usage ---------- -->
			<br>
			<h2 class="ui dividing header"><i class="small terminal icon"></i> Container usage</h2>
			<? if ($launcher) { ?>
			<p>Some steps of this pipeline run their own containers, so it's exported as a <b>launcher</b>: a directory (<tt><?=htmlspecialchars($exportdir)?>/</tt>) with <tt>run-pipeline.sh</tt>, which runs the other steps in the pipeline's own image (<tt>pipeline.sif</tt>) and starts each container step's image directly, so no container runs inside another. The images aren't in the export. <tt>run-pipeline.sh</tt> downloads them from their pinned references into an image cache. It needs only <tt>apptainer</tt> or <tt>singularity</tt> on the <tt>PATH</tt>.</p>
			<p>Download the images once, on a machine with internet access. Images can be 5&ndash;15&nbsp;GB each, so put the cache on project or scratch storage that every node can see:</p>
			<div class="ui segment" style="background-color: #f8f8f8"><pre style="margin: 0">./run-pipeline.sh --fetch-images --image-cache /project/nidb-images</pre></div>
			<p>Then run it once per subject/study. The input is copied into <tt>--output</tt>, and the pipeline runs there, so <tt>--output</tt> ends up looking like a NiDB analysis directory.</p>
			<div class="ui segment" style="background-color: #f8f8f8"><pre style="margin: 0"><?=htmlspecialchars($launchcmd)?></pre></div>
			<table class="ui very basic compact collapsing table">
				<tbody>
					<tr><td><tt>--input</tt>, <tt>--output</tt></td><td>The subject's input data, laid out as below, and a writable output directory</td></tr>
				<? if (in_array('subjectuid', $required)) { ?><tr><td><tt>--subjectuid</tt></td><td>Subject ID. Letters, numbers, <tt>_</tt> and <tt>-</tt> only. Replaces <tt>{subjectuid}</tt></td></tr><? } ?>
				<? if (in_array('studynum', $required)) { ?><tr><td><tt>--studynum</tt></td><td>Study number (integer). Replaces <tt>{studynum}</tt></td></tr><? } ?>
				<? if (in_array('studydatetime', $required)) { ?><tr><td><tt>--studydatetime</tt></td><td>Study date/time, <tt>YYYY-MM-DD hh:mm:ss</tt>. Replaces <tt>{studydatetime}</tt></td></tr><? } ?>
					<tr><td><tt>--path NAME=PATH</tt></td><td>A host file or directory a container step needs, such as a FreeSurfer license. <?=(count($extpaths) > 0) ? "This pipeline's are shown above, from its latest export" : "The export's <tt>README.txt</tt> lists them"?></td></tr>
					<tr><td><tt>--image-cache DIR</tt></td><td>Optional. Where the images are. Default <tt>$NIDB_IMAGE_CACHE</tt>, else <tt>~/.cache/nidb-images</tt></td></tr>
					<tr><td><tt>--allow-pull</tt></td><td>Optional. Download missing images inside a batch job. By default it stops instead, since compute nodes often have no internet access</td></tr>
					<tr><td><tt>--stop-on-error</tt></td><td>Optional. Stop at the first failing step. By default, like NiDB, later steps still run</td></tr>
				</tbody>
			</table>
			<p>On a cluster, run it from the batch job, one subject per job or array task, after <tt>--fetch-images</tt>. The export has no scheduler settings; the export's <tt>README.txt</tt> lists the wall time and peak memory of each segment in the reference run, and has an example SLURM array job. Steps that use <tt>--nv</tt> need a GPU node. <tt>./run-pipeline.sh --help</tt> prints the <tt>README.txt</tt>.</p>
			<? } else { ?>
			<p>Run the container once per subject/study. Bind the subject's input data to <tt>/input</tt> and a writable directory to <tt>/output</tt>. The input is copied into <tt>/output</tt>, and the pipeline runs there, so <tt>/output</tt> ends up looking like a NiDB analysis directory.</p>
			<div class="ui segment" style="background-color: #f8f8f8"><pre style="margin: 0"><?=htmlspecialchars($runcmd)?></pre></div>
			<table class="ui very basic compact collapsing table">
				<tbody>
				<? if (count($required) == 0) { ?>
					<tr><td colspan="2">This pipeline doesn't use any per-study variables, so no arguments are required</td></tr>
				<? } ?>
				<? if (in_array('subjectuid', $required)) { ?><tr><td><tt>--subjectuid</tt></td><td>Subject ID. Letters, numbers, <tt>_</tt> and <tt>-</tt> only. Replaces <tt>{subjectuid}</tt></td></tr><? } ?>
				<? if (in_array('studynum', $required)) { ?><tr><td><tt>--studynum</tt></td><td>Study number (integer). Replaces <tt>{studynum}</tt></td></tr><? } ?>
				<? if (in_array('studydatetime', $required)) { ?><tr><td><tt>--studydatetime</tt></td><td>Study date/time, <tt>YYYY-MM-DD hh:mm:ss</tt>. Replaces <tt>{studydatetime}</tt></td></tr><? } ?>
					<tr><td><tt>--stop-on-error</tt></td><td>Optional. Stop at the first failing step. By default, like NiDB, later steps still run</td></tr>
				</tbody>
			</table>
			<p>Other useful commands:</p>
			<div class="ui segment" style="background-color: #f8f8f8"><pre style="margin: 0">apptainer run-help <?=htmlspecialchars($siffile)?>    # pipeline description, variables and input layout
apptainer inspect <?=htmlspecialchars($siffile)?>     # labels: pipeline name, version, base OS</pre></div>
			<? } ?>

			<h4 class="ui header">Expected input layout (<tt>/input</tt>)</h4>
			<? if ($launcher) { ?>
			<p>Lay out <tt>--input</tt> (shown here as <tt>/input</tt>) as NiDB would have staged the data for this pipeline. The launcher doesn't check the layout. This is also in the export's <tt>README.txt</tt>.</p>
			<? } else { ?>
			<p>Lay out <tt>/input</tt> as NiDB would have staged the data for this pipeline. The container doesn't check the layout. This is also in the container's <tt>README.txt</tt> and <tt>apptainer run-help</tt>.</p>
			<? } ?>
			<? if ($p['pipeline_outputbids'] ?? 0) { ?>
				<p>A BIDS dataset in <tt><?=htmlspecialchars(rtrim("/input/" . ltrim((string)($p['pipeline_bidsoutputdir'] ?? ''), '/'), '/'))?></tt>. Subject directories are <tt>sub-&lt;subjectuid&gt;</tt> and session directories are <tt>ses-&lt;studynum&gt;</tt>.</p>
			<? } elseif (count($datadefs) == 0) { ?>
				<p>This pipeline has no enabled data items.</p>
			<? } else { ?>
			<table class="ui very compact celled collapsing table">
				<thead>
					<tr>
						<th>Location</th>
						<th>Modality</th>
						<th>Protocol</th>
						<th>Format</th>
						<th>Notes</th>
					</tr>
				</thead>
				<tbody>
				<?
				/* the same layout as GetData(), and the README.txt in the container (modulePipelineContainer::GetInputDirStructure()).
				   Steps that write to the same directory, with the same modality, format and notes, are one input; their protocols are combined */
				$layout = array();
				foreach ($datadefs as $dd) {
					$root = rtrim("/input/" . ltrim((string)$dd['pdd_location'], '/'), '/');
					$location = htmlspecialchars($root);
					if ($dd['pdd_useseries']) { $location .= "/&lt;seriesnum&gt;"; }
					if ($dd['pdd_usephasedir']) { $location .= "/&lt;phasedir&gt;"; }
					$modality = htmlspecialchars(strtoupper((string)$dd['pdd_modality']));
					$format = $dd['pdd_behonly'] ? "behavioral data only, no imaging data" : htmlspecialchars((string)$dd['pdd_dataformat']) . ($dd['pdd_gzip'] ? " (gzipped)" : "");
					$behdir = trim($dd['pdd_behdir'] ?? '');
					switch (trim($dd['pdd_behformat'] ?? '')) {
						case 'behroot': $behlocation = htmlspecialchars($root); break;
						case 'behrootdir': $behlocation = htmlspecialchars("$root/$behdir"); break;
						case 'behseries': $behlocation = htmlspecialchars($root) . "/&lt;seriesnum&gt;"; break;
						case 'behseriesdir': $behlocation = htmlspecialchars($root) . "/&lt;seriesnum&gt;/" . htmlspecialchars($behdir); break;
						default: $behlocation = "";
					}
					$notes = array();
					if ($dd['pdd_useseries']) { $notes[] = $dd['pdd_preserveseries'] ? "&lt;seriesnum&gt; is the original series number" : "&lt;seriesnum&gt; is 1, 2, 3, ... for each matching series"; }
					if ($dd['pdd_usephasedir']) { $notes[] = "&lt;phasedir&gt; is AP, PA, RL, LR, COL, ROW, or unknownPE"; }
					if ($behlocation != "") { $notes[] = "behavioral data in <tt>$behlocation</tt>"; }
					if ($dd['pdd_optional']) { $notes[] = "optional"; }
					$notes = implode("; ", $notes);

					$key = "$location\t$modality\t$format\t$notes";
					if (!isset($layout[$key])) {
						$layout[$key] = array('location' => $location, 'modality' => $modality, 'format' => $format, 'notes' => $notes, 'protocols' => array());
					}
					$protocol = htmlspecialchars((string)$dd['pdd_protocol']);
					if (!in_array($protocol, $layout[$key]['protocols'])) {
						$layout[$key]['protocols'][] = $protocol;
					}
				}
				foreach ($layout as $item) {
					?>
					<tr>
						<td><tt><?=$item['location']?></tt></td>
						<td><?=$item['modality']?></td>
						<td><?=implode("<br>", $item['protocols'])?></td>
						<td><?=$item['format']?></td>
						<td><?=$item['notes']?></td>
					</tr>
					<?
				}
				?>
				</tbody>
			</table>
			<? } ?>
			<? if (count($dependencyids) > 0) { ?>
				<p>Plus the parent pipeline's output, <?=(($p['pipeline_dependencydir'] ?? '') == 'subdir') ? "in a subdirectory named after the parent pipeline (<tt>/input/&lt;parentpipeline&gt;/</tt>)" : "in the root of <tt>/input</tt>"?>.</p>
			<? } ?>

			<!-- ---------- create ---------- -->
			<br>
			<h2 class="ui dividing header">Create container</h2>
			<p>Builds a container from the current version (<b>version <?=$version?></b>) of this pipeline. The build runs as a job on a compute node, in this pipeline's queue, and may take as long as the pipeline itself.</p>

			<h4 class="ui header">Potential pitfalls for this pipeline</h4>
			<table class="ui very compact table">
				<tbody>
				<?
				foreach ($pitfalls as $pitfall) {
					switch ($pitfall['level']) {
						case 'error': $icon = "red times circle"; $rowclass = "negative"; break;
						case 'warning': $icon = "orange exclamation triangle"; $rowclass = "warning"; break;
						default: $icon = "blue info circle"; $rowclass = "";
					}
					?>
					<tr class="<?=$rowclass?>">
						<td class="collapsing"><i class="<?=$icon?> icon"></i></td>
						<td class="collapsing"><b><?=$pitfall['title']?></b></td>
						<td><?=$pitfall['description']?></td>
					</tr>
					<?
				}
				?>
				</tbody>
			</table>

			<? if (!$canedit) { ?>
				<div class="ui message">Only the pipeline owner or a site admin can create a container</div>
			<? } elseif ($numblocking > 0) { ?>
				<div class="ui negative message">Fix the <?=$numblocking?> problem(s) marked <i class="red times circle icon"></i> before creating a container</div>
				<button class="ui disabled primary button"><i class="box icon"></i> Create</button>
			<? } elseif ($versioninprogress) { ?>
				<div class="ui info message">A container for version <?=$version?> is already being built - wait until the build is complete before trying to build again</div>
				<button class="ui disabled primary button"><i class="box icon"></i> Create</button>
			<? } else { ?>
				<form action="pipelinecontainers.php" method="post" class="ui form">
					<input type="hidden" name="action" value="create">
					<input type="hidden" name="id" value="<?=$id?>">
					<div class="inline field">
						<label>Reference analysis</label>
						<select class="ui search selection dropdown" name="referenceanalysisid" required>
						<? foreach ($refs as $i => $ref) { ?>
							<option value="<?=(int)$ref['analysis_id']?>" <?=($i == 0) ? "selected" : ""?>><?=htmlspecialchars(($ref['uid'] ?? '') . ($ref['study_num'] ?? ''))?> &nbsp; analysis <?=(int)$ref['analysis_id']?>, finished <?=htmlspecialchars($ref['analysis_enddate'] ?? '')?><?=((int)($ref['clustersec'] ?? 0) > 0) ? ", ran " . round($ref['clustersec']/3600, 1) . " h" : ""?></option>
						<? } ?>
						</select>
						<div class="ui pointing label">The build re-runs this analysis' study on a compute node to find the software the pipeline uses. Default is the most recent</div>
					</div>
					<div class="inline field">
						<div class="ui checkbox">
							<input type="checkbox" name="runvalidation" value="1" checked>
							<label>Run validation &mdash; run the finished container on the same input and compare its output with the reference analysis. Doubles the build time; turn off for long pipelines</label>
						</div>
					</div>
					<button type="submit" class="ui primary button" onclick="return confirm('Build a container from version <?=$version?> of this pipeline?')"><i class="box icon"></i> Create</button>
				</form>
			<? } ?>
		</div>

		<script>
			$(document).ready(function() {
				$('.ui.accordion').accordion();
				$('.ui.checkbox').checkbox();
				$('.ui.dropdown').dropdown({ fullTextSearch: true });
			});
		</script>
		<br><br>
		<?
	}


	/* -------------------------------------------- */
	/* ------- MarkTime --------------------------- */
	/* -------------------------------------------- */
	function MarkTime($msg) {
		$time = number_format((microtime(true) - $GLOBALS['timestart']), 3);
		$GLOBALS['t'][][$msg] = $time;
	}

?>

<? include("footer.php") ?>
