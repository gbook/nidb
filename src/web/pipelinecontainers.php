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

	/* determine action */
	switch ($action) {
		case 'create':
			ob_start();
			CreateContainer($id, $referenceanalysisid, $runvalidation);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("pipelinecontainers.php?id=$id");
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
	/* scan the main script steps (and their working directories) for {variables}. Returns
	   [lowercase variable] => list of step numbers. Bash ${var} is not a NiDB variable and is skipped */
	function GetContainerVariables($steps) {
		$vars = array();
		foreach ($steps as $step) {
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
			AddPitfall($pitfalls, 'warning', "Unrecognized variables", "These look like NiDB variables but aren't. NiDB leaves them in the command as-is; the export treats any leftover <tt>{...}</tt> token as an error, so fix the typo or rewrite the text (e.g. an awk program) so it isn't a single word in braces: " . implode(", ", $unknown));
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
		foreach ($steps as $step) { $allcommands .= ($step['ps_command'] ?? '') . "\n"; }
		if (preg_match('/freesurfer|recon-all|FREESURFER_HOME|SUBJECTS_DIR/i', $allcommands)) {
			AddPitfall($pitfalls, 'warning', "FreeSurfer license", "FreeSurfer's <tt>license.txt</tt> is never copied into the image. Users must supply their own license, e.g. <tt>--bind license.txt:/opt/freesurfer/license.txt</tt>. FreeSurfer also adds ~10 GB to the image.");
		}
		if (preg_match('/\bmatlab\b/i', $allcommands)) {
			AddPitfall($pitfalls, 'warning', "MATLAB", "Commercial software must not be in an image meant to be shared, and MATLAB license files are never copied. Compiled MATLAB code with the MATLAB Runtime (MCR) is fine.");
		}
		if (preg_match('/\/opt\/fsl|FSLDIR|\b(bet|flirt|fnirt|feat|melodic|fslmaths)\b/', $allcommands)) {
			AddPitfall($pitfalls, 'info', "FSL", "FSL adds ~5 GB to the image.");
		}

		/* ---------- build ---------- */
		if ($avgsec > 4*3600) {
			AddPitfall($pitfalls, 'warning', "Long build", "The build runs the whole pipeline once on a compute node to find the software it uses. Completed analyses of this version averaged " . round($avgsec/3600, 1) . " hours on the cluster.");
		}
		AddPitfall($pitfalls, 'info', "Code paths not taken", "Software is found by tracing one reference run. Software used only for some subjects (an <tt>if</tt> branch the reference study didn't take) may be missing from the image.");
		AddPitfall($pitfalls, 'info', "Compute node OS", "The image is built on a compute node, using that node's OS and software. Export pipelines that run on an old OS before those nodes are upgraded.");

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
		return htmlspecialchars($uid) . (int)$studynum . " <span style='color: #888'>(analysis " . (int)$analysisid . ")</span>";
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
	/* ------- ContainerStatusLabel --------------- */
	/* -------------------------------------------- */
	function ContainerStatusLabel($status) {
		switch ($status) {
			case 'submitted': return "<span class='ui grey label'><i class='clock outline icon'></i> Submitted</span>";
			case 'started': return "<span class='ui blue label'><i class='spinner loading icon'></i> Started</span>";
			case 'building': return "<span class='ui blue label'><i class='spinner loading icon'></i> Building</span>";
			case 'complete': return "<span class='ui green label'><i class='check icon'></i> Complete</span>";
			case 'error': return "<span class='ui red label'><i class='exclamation triangle icon'></i> Error</span>";
		}
		return "<span class='ui label'>" . htmlspecialchars($status) . "</span>";
	}


	/* -------------------------------------------- */
	/* ------- DisplayContainerTable -------------- */
	/* -------------------------------------------- */
	function DisplayContainerTable($containers, $currentversion, $showsize) {
		?>
		<table class="ui very compact celled table">
			<thead>
				<tr>
					<th>ID</th>
					<th>Version</th>
					<th>Status</th>
					<th>Reference analysis</th>
					<th>Validation</th>
					<th>Submitted</th>
					<th>Build started</th>
					<th>Build finished</th>
					<? if ($showsize) { ?>
					<th>Available</th>
					<th>Container</th>
					<th>Size</th>
					<th>SHA256</th>
					<? } ?>
					<th>Warnings &amp; log</th>
				</tr>
			</thead>
			<tbody>
			<?
			foreach ($containers as $c) {
				$containerid = (int)$c['pipelinecontainer_id'];
				$cversion = (int)$c['pipeline_version'];
				$path = (string)($c['container_path'] ?? '');
				$size = ((int)($c['container_size'] ?? 0) > 0) ? HumanReadableFilesize((int)$c['container_size']) : "";
				$sha256 = (string)($c['container_sha256'] ?? '');
				$warnings = trim((string)($c['build_warnings'] ?? ''));
				$log = trim((string)($c['build_log'] ?? ''));
				?>
				<tr>
					<td><?=$containerid?></td>
					<td><?=$cversion?> <? if ($cversion == $currentversion) { ?><span class="ui tiny basic green label">current</span><? } ?></td>
					<td><?=ContainerStatusLabel($c['build_status'])?></td>
					<td><?=ReferenceAnalysisLabel($c['uid'], $c['study_num'], $c['reference_analysisid'])?></td>
					<td><?=($c['run_validation'] ?? 0) ? "Yes" : "No"?></td>
					<td><?=htmlspecialchars($c['build_createdate'] ?? '')?></td>
					<td><?=htmlspecialchars($c['build_startdate'] ?? '')?></td>
					<td><?=htmlspecialchars($c['build_enddate'] ?? '')?></td>
					<? if ($showsize) { ?>
					<td><?=htmlspecialchars($c['container_date'] ?? '')?></td>
					<td><tt><?=htmlspecialchars($path)?></tt></td>
					<td><?=$size?></td>
					<td><? if ($sha256 != "") { ?><tt title="<?=htmlspecialchars($sha256)?>"><?=htmlspecialchars(substr($sha256, 0, 12))?>&hellip;</tt><? } ?></td>
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
				</tr>
				<?
			}
			?>
			</tbody>
		</table>
		<?
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
		$rows = ContainerQueryRows("select a.*, d.uid, b.study_num from pipeline_containers a left join analysis r on a.reference_analysisid = r.analysis_id left join studies b on r.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = ? order by a.pipelinecontainer_id desc", 'i', [$id], __LINE__);
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

			<p>A container is a standalone Apptainer (<tt>.sif</tt>) image of one version of this pipeline: its main script plus the software it uses, captured from the compute node. Anyone can run it once per subject/study without NiDB. See <tt>doc/pipeline-apptainer.md</tt> for details.</p>

			<!-- ---------- in progress ---------- -->
			<? if (count($inprogress) > 0) { ?>
			<h3 class="ui dividing header"><i class="spinner loading icon"></i> In progress</h3>
			<? DisplayContainerTable($inprogress, $version, false); ?>
			<? } ?>

			<!-- ---------- existing ---------- -->
			<h3 class="ui dividing header"><i class="boxes icon"></i> Containers</h3>
			<? if (count($finished) == 0) { ?>
				<div class="ui message">No containers have been built for this pipeline yet</div>
			<? } else { ?>
				<? DisplayContainerTable($finished, $version, true); ?>
			<? } ?>

			<!-- ---------- usage ---------- -->
			<h3 class="ui dividing header"><i class="terminal icon"></i> Usage</h3>
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

			<h4 class="ui header">Expected input layout (<tt>/input</tt>)</h4>
			<? if ($p['pipeline_outputbids'] ?? 0) { ?>
				<p>A BIDS dataset in <tt>/input/<?=htmlspecialchars($p['pipeline_bidsoutputdir'] ?? '')?></tt></p>
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
				foreach ($datadefs as $dd) {
					$location = htmlspecialchars("/input/" . ltrim((string)$dd['pdd_location'], '/'));
					if ($dd['pdd_useseries']) { $location = rtrim($location, '/') . "/&lt;seriesnum&gt;"; }
					if ($dd['pdd_usephasedir']) { $location .= "/&lt;phasedir&gt;"; }
					$format = htmlspecialchars($dd['pdd_dataformat']) . ($dd['pdd_gzip'] ? " (gzipped)" : "");
					$notes = array();
					if ($dd['pdd_optional']) { $notes[] = "optional"; }
					if ($dd['pdd_behonly']) { $notes[] = "behavioral data only"; }
					if (trim($dd['pdd_behformat'] ?? '') != "") { $notes[] = "behavioral: " . htmlspecialchars($dd['pdd_behformat']) . (trim($dd['pdd_behdir'] ?? '') != "" ? " in <tt>" . htmlspecialchars($dd['pdd_behdir']) . "</tt>" : ""); }
					?>
					<tr>
						<td><tt><?=$location?></tt></td>
						<td><?=htmlspecialchars($dd['pdd_modality'])?></td>
						<td><?=htmlspecialchars($dd['pdd_protocol'])?></td>
						<td><?=$format?></td>
						<td><?=implode("; ", $notes)?></td>
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
			<h3 class="ui dividing header"><i class="plus square outline icon"></i> Create container</h3>
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
				<div class="ui info message">A container for version <?=$version?> is already being built</div>
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
