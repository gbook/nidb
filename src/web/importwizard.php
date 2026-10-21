<?
 // ------------------------------------------------------------------------------
 // NiDB importwizard.php
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

 /* Universal import wizard. MOCKUP / SCAFFOLD: see doc/universal-import.md

	Step 1 asks what kind of data the user has (imaging, non-imaging, not sure).
	Step 2 starts the imaging import, hands off to the non-imaging import, or walks
	the user through a decision tree to identify the data.

	The flow is driven by the WIZARD_PATHS and DECISION_TREE objects in the script
	below, so steps and questions can be changed without touching the markup.

	Inspect (imaging): the browser builds a profile of the chosen files
	(scripts/importinspect.js), or the server builds one for an NFS path, and the
	'inspect' action runs the detectors in importinspect_functions.php.

	Map (imaging): the browser applies the user's path mapping to every file
	(scripts/importmap.js), and the 'match' action checks the resulting subjects and
	studies against NiDB. Nothing is uploaded or saved yet. */

	define("LEGIT_REQUEST", true);

	session_start();
	ob_start(); /* the mutating steps (upload, recipe save) will use POST/Redirect/GET */

	/* the inspect and match actions return JSON, so they run before any page HTML */
	if (in_array($_POST['action'] ?? '', array('inspect', 'match'), true)) {
		require "functions.php";
		require "includes_php.php";
		require "importinspect_functions.php";
		if ($_POST['action'] == 'inspect') InspectData();
		else MatchData();
		exit(0);
	}
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Import Wizard</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "importinspect_functions.php";
	require "menu.php";

	$username = $_SESSION['username'];
	$instanceid = (int)$_SESSION['instanceid'];

	/* ----- setup variables ----- */
	$projectid = (int)GetVariable("projectid");

	ShowFlashMessage();

	DisplayWizard($username, $instanceid, $projectid);


	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- InspectData ------------------------ */
	/* -------------------------------------------- */
	/* the 'inspect' action: analyze a profile sent by the browser, or build one for an NFS path.
	   Read-only, but it is the start of an import, so it needs Edit Data on the destination project */
	function InspectData() {
		if (!VerifyCSRFToken(false))
			InspectRespond(false, "This request could not be verified. Your session may have expired; reload the page and try again", 403);

		/* nothing below writes to the session; release it so a long NFS listing doesn't block the user's other pages */
		session_write_close();

		$projectid = (int)($_POST['projectid'] ?? 0);
		$perms = GetCurrentUserProjectPermissions(array($projectid));
		if (($projectid < 1) || !GetPerm($perms, 'modifydata', $projectid))
			InspectRespond(false, "You need Edit Data permission on the destination project to import into it", 403);

		$source = (string)($_POST['source'] ?? '');
		$err = '';
		if ($source == 'nfs') {
			$root = IIResolveNfsPath((string)($_POST['nfspath'] ?? ''), $err);
			if ($root == '')
				InspectRespond(false, $err, 400);
			$profile = IIBuildNfsProfile($root);
		}
		elseif (($source == 'localfiles') || ($source == 'localfolder')) {
			$f = $_FILES['profile'] ?? null;
			if (!is_array($f) || ($f['error'] != UPLOAD_ERR_OK)) {
				$toobig = (is_array($f) && in_array($f['error'], array(UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE)));
				InspectRespond(false, "The inspection data didn't reach the server" . ($toobig ? ", because it is larger than the server's upload size limit" : ""), 400);
			}
			$raw = file_get_contents($f['tmp_name']);
			if (($raw !== false) && (($_POST['encoding'] ?? '') == 'gzip'))
				$raw = @gzdecode($raw, 1073741824);
			$profile = IIValidateProfile(($raw === false) ? null : json_decode($raw, true), $err);
			if ($profile === null)
				InspectRespond(false, $err, 400);
			$profile['source'] = $source;
		}
		else {
			InspectRespond(false, "Unknown data location", 400);
		}

		if (count($profile['files']) == 0)
			InspectRespond(false, "No files were found", 400);

		$result = IIAnalyzeProfile($profile);
		/* the browser maps every file in the next step; for local data it already has the list */
		if ($source == 'nfs') $result['files'] = $profile['files'];
		InspectRespond(true, "", 200, $result);
	}


	/* -------------------------------------------- */
	/* ------- MatchData -------------------------- */
	/* -------------------------------------------- */
	/* the 'match' action: which of the mapped subjects and studies already exist. Read-only, and needs
	   Edit Data on the destination project like the inspection */
	function MatchData() {
		if (!VerifyCSRFToken(false))
			InspectRespond(false, "This request could not be verified. Your session may have expired; reload the page and try again", 403);
		session_write_close();

		$projectid = (int)($_POST['projectid'] ?? 0);
		$perms = GetCurrentUserProjectPermissions(array($projectid));
		if (($projectid < 1) || !GetPerm($perms, 'modifydata', $projectid))
			InspectRespond(false, "You need Edit Data permission on the destination project to import into it", 403);

		$f = $_FILES['data'] ?? null;
		if (!is_array($f) || ($f['error'] != UPLOAD_ERR_OK))
			InspectRespond(false, "The subject list didn't reach the server", 400);
		$raw = file_get_contents($f['tmp_name']);
		if (($raw !== false) && (($_POST['encoding'] ?? '') == 'gzip'))
			$raw = @gzdecode($raw, 268435456);
		$err = '';
		$req = IIValidateMatchRequest(($raw === false) ? null : json_decode($raw, true), $err);
		if ($req === null)
			InspectRespond(false, $err, 400);

		InspectRespond(true, "", 200, IIMatchIdentities($projectid, $req[0], $req[1]));
	}


	/* -------------------------------------------- */
	/* ------- InspectRespond --------------------- */
	/* -------------------------------------------- */
	/* emit the JSON response (inspect and match actions) and stop. Buffered output (e.g. a PHP warning) is discarded so the body is only the JSON */
	function InspectRespond($success, $message, $httpcode, $result = null) {
		while (ob_get_level() > 0) { ob_end_clean(); }
		http_response_code($httpcode);
		header("Content-Type: application/json; charset=UTF-8");
		header("Cache-Control: no-store");
		echo json_encode(array('success' => (bool)$success, 'message' => $message, 'result' => $result), JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
		exit(0);
	}


	/* -------------------------------------------- */
	/* ------- GetModalities ---------------------- */
	/* -------------------------------------------- */
	/* enabled modalities, for the modality dropdowns */
	function GetModalities() {
		$mods = array();
		$sqlstring = "select mod_code, mod_desc from modalities where mod_enabled = 1 order by mod_code";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring);
		mysqli_stmt_close($stmt);
		while ($result && ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)))
			$mods[] = array('code' => $row['mod_code'], 'desc' => $row['mod_desc']);
		return $mods;
	}


	/* -------------------------------------------- */
	/* ------- GetUserProjects -------------------- */
	/* -------------------------------------------- */
	/* projects in the current instance that the user belongs to */
	function GetUserProjects($username, $instanceid) {
		$sqlstring = "select a.project_id, a.project_name, a.project_costcenter from projects a join user_project b on a.project_id = b.project_id join users c on b.user_id = c.user_id where c.username = ? and a.instance_id = ? order by a.project_name";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'si', $username, $instanceid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$username, $instanceid]);
		mysqli_stmt_close($stmt);

		$projects = array();
		while ($result && ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)))
			$projects[] = $row;
		return $projects;
	}


	/* -------------------------------------------- */
	/* ------- DisplayProjectOptions -------------- */
	/* -------------------------------------------- */
	function DisplayProjectOptions($projects, $projectid) {
		?><option value="">Select project...</option><?
		foreach ($projects as $p) {
			$selected = ((int)$p['project_id'] == $projectid) ? "selected" : "";
			?><option value="<?=(int)$p['project_id']?>" <?=$selected?>><?=htmlspecialchars($p['project_name'] ?? '')?> (<?=htmlspecialchars($p['project_costcenter'] ?? '')?>)</option><?
		}
	}


	/* -------------------------------------------- */
	/* ------- DisplayWizard ---------------------- */
	/* -------------------------------------------- */
	function DisplayWizard($username, $instanceid, $projectid) {
		$projects = GetUserProjects($username, $instanceid);
		$filetypes = array();
		foreach (IIRegistry() as $t)
			$filetypes[] = array('code' => $t['code'], 'name' => $t['name']);
		?>
		<style>
			.wizpanel { display: none; }
			.wizpanel.active { display: block; }
			.wizchoice.ui.card { cursor: pointer; }
			.wizchoice.ui.card:hover { box-shadow: 0 0 0 2px #2185d0, 0 2px 8px rgba(0,0,0,0.15); }
			.wizchoice .content > .header { margin-top: 0.5em !important; }
			.wiztrail .item { color: #666; }
			.wizanswer.ui.button { text-align: left; margin-bottom: 0.5em; }
			.insdots { color: #2185d0; letter-spacing: 1px; white-space: nowrap; cursor: help; }
			.insevidence { color: #777; font-size: 0.9em; }
			#ins-groups select { padding: 2px 4px; }
			#ins-groups tr.insunknown { background-color: #fffaf3; }
			#ins-groups tr.insexcluded td { color: #aaa; }
			/* mapping: one row per folder and file name (and per part, when a name holds several things) */
			.mappath { font-family: monospace; font-size: 1.1em; word-break: break-all; }
			#map-parts td { vertical-align: middle; }
			#map-parts .mapname { font-family: monospace; }
			#map-parts tr.mappart .mapname { padding-left: 2em !important; }
			#map-parts select { padding: 3px 6px; min-width: 16em; }
			#map-parts tr.mappart td { border-top: none !important; padding-top: 2px !important; padding-bottom: 2px !important; }
			.mapdelims { margin-top: 3px; color: #555; }
			.mapdelims label { margin-left: 0.6em; cursor: pointer; white-space: nowrap; }
			.mapdelims input { vertical-align: middle; margin-right: 2px; }
			#map-tree td { vertical-align: top; }
			#map-tree tr.mapsubject td { background: #f8f8f9; font-weight: bold; }
			.mapindent1 { padding-left: 1.5em !important; }
			.mapindent2 { padding-left: 3em !important; }
		</style>

		<div class="ui container">
			<div class="ui top attached grey segment">
				<h2 class="ui header">
					<i class="magic icon"></i>
					<div class="content">Import Wizard
						<div class="sub header">Guided import for any kind of data</div>
					</div>
				</h2>
			</div>
			<div class="ui attached warning message">
				<i class="flask icon"></i> <b>Mockup.</b> This page is a scaffold for the universal import design (<code>doc/universal-import.md</code>). Nothing is uploaded or saved.
			</div>

			<!-- step bar, filled in by RenderSteps() from WIZARD_PATHS -->
			<div class="ui attached fluid small steps" id="wizsteps"></div>

			<div class="ui attached segment" style="min-height: 300px">

				<!-- ======== Step 1: what kind of data ======== -->
				<div class="wizpanel" id="panel-start">
					<h3 class="ui header">What kind of data are you importing?</h3>
					<div class="ui four stackable cards">
						<div class="wizchoice ui card" onClick="Choose('imaging')">
							<div class="center aligned content">
								<i class="big blue file image outline icon"></i>
								<div class="header">Imaging</div>
								<div class="description">Data files from a device or program: MRI, CT, PET, EEG, MEG, eye tracking, task logs, audio, video, genetics, documents...</div>
							</div>
							<div class="extra content">Any data that is kept as <b>files</b></div>
						</div>
						<div class="wizchoice ui card" onClick="Choose('nonimaging')">
							<div class="center aligned content">
								<i class="big green table icon"></i>
								<div class="header">Non-imaging</div>
								<div class="description">Values about subjects: questionnaire scores, vitals, ratings, lab values, medications, doses, treatments...</div>
							</div>
							<div class="extra content">Observations and interventions, usually a <b>spreadsheet</b></div>
						</div>
						<div class="wizchoice ui card" onClick="Choose('squirrel')">
							<div class="center aligned content">
								<img src="images/squirrel-icon-64.png" alt="" style="height: 32px">
								<div class="header">squirrel package</div>
								<div class="description">A .sqrl package exported from NiDB or another squirrel tool. It can hold imaging and non-imaging data together</div>
							</div>
							<div class="extra content">One <b>.sqrl</b> or <b>.zip</b> file</div>
						</div>
						<div class="wizchoice ui card" onClick="Choose('unsure')">
							<div class="center aligned content">
								<i class="big grey question circle outline icon"></i>
								<div class="header">Not sure</div>
								<div class="description">Answer a few questions and NiDB will suggest how to import it</div>
							</div>
							<div class="extra content">Takes less than a minute</div>
						</div>
					</div>
				</div>

				<!-- ======== Step 2a: imaging import ======== -->
				<div class="wizpanel" id="panel-imaging">
					<div id="imaging-identified" class="ui info message" style="display:none"></div>
					<h3 class="ui header">Imaging import
						<div class="sub header">Choose where the data goes and where it is. NiDB looks at the files before anything is uploaded</div>
					</h3>
					<div class="ui form">
						<div class="field">
							<label>Destination project</label>
							<select class="ui dropdown" id="imaging-project">
								<? DisplayProjectOptions($projects, $projectid); ?>
							</select>
						</div>
						<div class="grouped fields">
							<label>Where is the data?</label>
							<div class="field">
								<div class="ui radio checkbox">
									<input type="radio" name="imaging-location" value="localfiles" checked onChange="UpdateImagingLocation()">
									<label>Files on this computer <span class="ui grey text">(choose one or more files)</span></label>
								</div>
							</div>
							<div class="field">
								<div class="ui radio checkbox">
									<input type="radio" name="imaging-location" value="localfolder" onChange="UpdateImagingLocation()">
									<label>A folder on this computer <span class="ui grey text">(choose a folder; everything in it and its subfolders)</span></label>
								</div>
							</div>
							<div class="field">
								<div class="ui radio checkbox">
									<input type="radio" name="imaging-location" value="nfs" onChange="UpdateImagingLocation()">
									<label>A folder on the server <span class="ui grey text">(NFS path)</span></label>
								</div>
							</div>
						</div>
						<!-- the browser only gets access to the chosen files. Nothing is uploaded until the user confirms the import -->
						<div class="field" id="imaging-localfiles">
							<label>Files</label>
							<input type="file" id="imaging-files" multiple onChange="QuickLook(this.files)">
						</div>
						<div class="field" id="imaging-localfolder" style="display:none">
							<label>Folder</label>
							<input type="file" id="imaging-folder" webkitdirectory directory multiple onChange="QuickLook(this.files)">
						</div>
						<div class="field" id="imaging-nfs" style="display:none">
							<label>Path</label>
							<input type="text" id="imaging-nfspath" placeholder="/path/accessible/to/nidb/server">
						</div>
						<p class="ui grey text" id="imaging-localnote"><i class="lock icon"></i>Your files stay on this computer while NiDB inspects them. Only the files you keep are uploaded, after you confirm the import</p>
					</div>

					<!-- quick look at the chosen folder: file listing only, no file contents are read -->
					<div id="imaging-quicklook" class="ui segment" style="display:none">
						<h4 class="ui header">Quick look <div class="sub header">From the file list only. Nothing has been uploaded</div></h4>
						<div class="ui tiny three statistics" id="ql-stats"></div>
						<table class="ui very compact small collapsing table" id="ql-ext">
							<thead><tr><th>Extension</th><th class="right aligned">Files</th><th class="right aligned">Size</th></tr></thead>
							<tbody></tbody>
						</table>
					</div>

					<div id="inspect-error" class="ui negative message" style="display:none"></div>
					<div id="inspect-progress" class="ui small blue progress" style="display:none">
						<div class="bar"></div>
						<div class="label"></div>
					</div>

					<div class="ui divider"></div>
					<button class="ui button" onClick="GoBack()"><i class="left arrow icon"></i> Back</button>
					<button class="ui primary right floated button" id="inspect-button" onClick="StartInspect()">Inspect data <i class="right arrow icon"></i></button>
				</div>

				<!-- ======== Step 3 (imaging): here's what we think you have ======== -->
				<div class="wizpanel" id="panel-inspect">
					<h3 class="ui header">Here's what we think you have
						<div class="sub header" id="ins-summary"></div>
					</h3>
					<div id="ins-hints"></div>
					<div id="ins-phi" class="ui warning message" style="display:none">
						<div class="header">Possible patient information</div>
						<p>NiDB archives files exactly as they are, so this will be stored with the data.</p>
						<ul class="list"></ul>
					</div>

					<table class="ui very basic definition table" id="ins-axes"><tbody></tbody></table>

					<h4 class="ui header">File groups <div class="sub header">Choose which groups to import. Groups that travel together (such as .json sidecars) are kept with their files</div></h4>
					<table class="ui compact small table" id="ins-groups">
						<thead><tr><th class="collapsing">Import</th><th>Files</th><th class="right aligned">Count</th><th class="right aligned">Size</th><th>File type</th><th>Modality</th><th>Why</th></tr></thead>
						<tbody></tbody>
					</table>

					<div class="ui styled fluid accordion" id="ins-details">
						<div class="title"><i class="dropdown icon"></i> Folder structure details</div>
						<div class="content">
							<table class="ui very compact small table" id="ins-levels">
								<thead><tr><th>Folder level</th><th class="right aligned">Different names</th><th>Most common format</th><th>Examples</th></tr></thead>
								<tbody></tbody>
							</table>
							<h5 class="ui header">Most common paths <div class="sub header"># is a digit; dates and UIDs are shown as &lt;date&gt; and &lt;uid&gt;</div></h5>
							<table class="ui very compact small table" id="ins-shapes">
								<tbody></tbody>
							</table>
						</div>
					</div>
					<p class="ui grey text" id="ins-notes" style="margin-top: 1em"></p>

					<div class="ui divider"></div>
					<button class="ui button" onClick="GoBack()"><i class="left arrow icon"></i> Back</button>
					<button class="ui primary right floated button" onClick="GoMap()">Map to subjects, studies &amp; series <i class="right arrow icon"></i></button>
				</div>

				<!-- ======== Step 4 (imaging): map to subject / study / series ======== -->
				<div class="wizpanel" id="panel-map">
					<h3 class="ui header">Map to subjects, studies &amp; series
						<div class="sub header">NiDB reads the subject, visit, and series from the folder and file names. Check what each part of an example file means. The preview below covers every file, and nothing has been uploaded</div>
					</h3>

					<div class="ui segment">
						<div class="ui form">
							<div class="inline field">
								<label>Example file</label>
								<select id="map-example" onChange="RenderMapPath()"></select>
							</div>
						</div>
						<p class="mappath" id="map-path"></p>
						<table class="ui very basic compact table" id="map-parts">
							<thead><tr><th>Folder or file name</th><th></th><th>What is it?</th></tr></thead>
							<tbody></tbody>
						</table>
						<div class="ui grey text">Every file is read with the pattern <code id="map-template"></code></div>
					</div>

					<div class="ui form">
						<div class="inline field" id="map-subjectconst-field">
							<label>The path has no subject ID. All files belong to subject</label>
							<input type="text" id="map-subjectconst" placeholder="Subject ID" onInput="SetSubjectConst(this.value)">
						</div>
					</div>

					<div class="ui fluid accordion" id="map-options" style="margin-top: 0.8em">
						<div class="title"><i class="dropdown icon"></i> More options</div>
						<div class="content">
							<div class="ui form">
								<div class="inline field">
									<label>Study date, if the path doesn't have one</label>
									<select id="map-datesource" onChange="SetMapOption('datesource', this.value)">
										<option value="mtime">File modification date (copying a file can change it)</option>
										<option value="header">From the file headers, after upload</option>
										<option value="none">Enter it in the next step</option>
									</select>
									<span class="ui grey text" id="map-datesource-note"></span>
								</div>
								<div class="inline field">
									<label>Series, if the path doesn't name them</label>
									<select id="map-grouping" onChange="SetMapOption('grouping', this.value)">
										<option value="basename">One series per file name (files with the same name stay together)</option>
										<option value="folder">One series per folder</option>
									</select>
									<span class="ui grey text" id="map-grouping-note"></span>
								</div>
							</div>
						</div>
					</div>

					<div id="map-updating" class="ui active inline mini loader" style="display:none"></div>
					<div id="map-checks"></div>
					<div class="ui tiny five statistics" id="map-stats" style="margin: 1em 0"></div>
					<div id="map-matchnote" class="ui grey text"></div>

					<table class="ui very compact small table" id="map-tree">
						<thead><tr><th>In NiDB</th><th>From</th><th class="right aligned">Files</th><th class="right aligned">Size</th><th>Status</th></tr></thead>
						<tbody></tbody>
					</table>
					<button class="ui small basic button" id="map-more" style="display:none" onClick="mapShown += 100; RenderMapTree()">Show more subjects</button>

					<div class="ui styled fluid accordion" id="map-details" style="margin-top: 1em">
						<div class="title"><i class="dropdown icon"></i> Files that don't fit <span id="map-misfit-count"></span></div>
						<div class="content">
							<p class="ui grey text">These files aren't imported unless the mapping changes.</p>
							<table class="ui very compact small table" id="map-misfits">
								<thead><tr><th>Why</th><th class="right aligned">Files</th><th>Examples</th></tr></thead>
								<tbody></tbody>
							</table>
						</div>
						<div class="title"><i class="dropdown icon"></i> Recipe (preview)</div>
						<div class="content">
							<p class="ui grey text">How this mapping would be saved (doc/universal-import.md §6.4). It holds patterns, not names or IDs from your files. Recipes aren't saved yet.</p>
							<pre id="map-recipe" style="max-height: 400px; overflow: auto; font-size: 0.85em"></pre>
						</div>
					</div>

					<div class="ui divider"></div>
					<button class="ui button" onClick="GoBack()"><i class="left arrow icon"></i> Back</button>
					<button class="ui primary right floated disabled button" title="Not built yet: details, upload, and import (doc/universal-import.md §5 steps 5 and 6)">Details &amp; upload <i class="right arrow icon"></i></button>
				</div>

				<!-- ======== Step 2b: non-imaging import ======== -->
				<div class="wizpanel" id="panel-nonimaging">
					<div id="nonimaging-identified" class="ui info message" style="display:none"></div>
					<h3 class="ui header">Non-imaging import
						<div class="sub header">Observations and interventions are imported from a .csv file</div>
					</h3>
					<div class="ui list">
						<div class="item"><i class="eye icon"></i><div class="content"><b>Observations</b>: things measured or observed about a subject (scores, vitals, ratings, lab values)</div></div>
						<div class="item"><i class="syringe icon"></i><div class="content"><b>Interventions</b>: things given to or done to a subject (medications, doses, treatments)</div></div>
					</div>
					<p>One file can contain both, using the <code>type</code> column. The next page describes the .csv format.</p>
					<div class="ui form">
						<div class="field">
							<label>Destination project</label>
							<select class="ui dropdown" id="nonimaging-project">
								<? DisplayProjectOptions($projects, $projectid); ?>
							</select>
						</div>
					</div>

					<div class="ui divider"></div>
					<button class="ui button" onClick="GoBack()"><i class="left arrow icon"></i> Back</button>
					<button class="ui primary right floated button" onClick="StartNonImaging()">Continue to non-imaging import <i class="right arrow icon"></i></button>
				</div>

				<!-- ======== Step 2d: squirrel package ======== -->
				<div class="wizpanel" id="panel-squirrel">
					<div id="squirrel-identified" class="ui info message" style="display:none"></div>
					<h3 class="ui header">squirrel package import
						<div class="sub header">Choose the package, and NiDB shows what's in it before anything is uploaded</div>
					</h3>
					<div class="ui form">
						<div class="field">
							<label>Destination project</label>
							<select class="ui dropdown" id="squirrel-project">
								<? DisplayProjectOptions($projects, $projectid); ?>
							</select>
						</div>
						<div class="field">
							<label>Package <span class="ui grey text">(.sqrl, or a .zip export)</span></label>
							<input type="file" id="squirrel-file" accept=".sqrl,.zip" onChange="ReadSquirrel()">
						</div>
					</div>
					<p class="ui grey text"><i class="lock icon"></i>The package stays on this computer while NiDB reads its contents list. Package on the server instead? Use the <a href="importimaging.php?action=newimportform&filetype=squirrel">import form</a> with an NFS path</p>

					<div id="squirrel-reading" class="ui active inline small loader" style="display:none"></div>
					<div id="squirrel-error" class="ui negative message" style="display:none"></div>

					<!-- what's in the package, from its squirrel.json -->
					<div id="squirrel-summary" style="display:none">
						<h4 class="ui header"><span id="sq-name"></span><div class="sub header" id="sq-details"></div></h4>
						<p id="sq-description"></p>
						<div id="sq-notyet" class="ui warning message" style="display:none"></div>
						<table class="ui very basic collapsing compact table" id="sq-contents">
							<thead><tr><th>Package contents</th><th class="right aligned">Count</th><th>Imported</th></tr></thead>
							<tbody></tbody>
						</table>
					</div>

					<div class="ui divider"></div>
					<button class="ui button" onClick="GoBack()"><i class="left arrow icon"></i> Back</button>
					<button class="ui primary right floated disabled button" id="squirrel-continue" onClick="StartSquirrel()">Upload this package <i class="right arrow icon"></i></button>
				</div>

				<!-- ======== Step 2c: decision tree for unknown data ======== -->
				<div class="wizpanel" id="panel-unsure">
					<div class="ui small breadcrumb wiztrail" id="tree-trail"></div>
					<h3 class="ui header" id="tree-question"></h3>
					<p class="ui grey text" id="tree-help"></p>
					<div id="tree-answers" style="max-width: 700px"></div>

					<!-- shown when the tree reaches a result -->
					<div id="tree-result" style="display:none">
						<div class="ui icon message" id="tree-result-msg">
							<i class="icon" id="tree-result-icon"></i>
							<div class="content">
								<div class="header" id="tree-result-title"></div>
								<p id="tree-result-text"></p>
							</div>
						</div>
						<button class="ui primary button" id="tree-result-go"></button>
					</div>

					<div class="ui divider"></div>
					<button class="ui button" onClick="GoBack()"><i class="left arrow icon"></i> Back</button>
					<span class="ui grey text" style="margin-left: 1em">Already know? Go to
						<a href="#" onClick="Choose('imaging'); return false;">imaging</a> or
						<a href="#" onClick="Choose('nonimaging'); return false;">non-imaging</a> import</span>
				</div>

			</div>
		</div>

		<!-- versioned so browsers reload the scripts when they change -->
		<script src="scripts/importinspect.js?v=<?=(int)@filemtime(__DIR__ . "/scripts/importinspect.js")?>"></script>
		<script src="scripts/importmap.js?v=<?=(int)@filemtime(__DIR__ . "/scripts/importmap.js")?>"></script>
		<script src="scripts/sqrlreader.js?v=<?=(int)@filemtime(__DIR__ . "/scripts/sqrlreader.js")?>"></script>
		<script>
		/* from the server: inspection settings and file types, the enabled modalities, and the CSRF token for the inspect request */
		var II_CONFIG = <?=json_encode(IIBrowserConfig(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES)?>;
		var FILETYPES = <?=json_encode($filetypes, JSON_HEX_TAG | JSON_HEX_AMP)?>;
		var MODALITIES = <?=json_encode(GetModalities(), JSON_HEX_TAG | JSON_HEX_AMP)?>;
		var CSRF_TOKEN = <?=json_encode($_SESSION['csrftoken'] ?? '', JSON_HEX_TAG | JSON_HEX_AMP)?>;
		var II_MATCH = { maxsubjects: <?=II_MAXMATCHSUBJECTS?>, maxstudies: <?=II_MAXMATCHSTUDIES?> };

		/* ======================================================================
		   Wizard configuration. Edit these to change the flow.
		   ====================================================================== */

		/* the steps shown in the step bar for each path. 'panel' is the panel shown
		   for a step; steps with no panel are placeholders for later phases */
		var WIZARD_PATHS = {
			start: [
				{ title: "Type of data", desc: "Imaging or non-imaging", panel: "start" },
				{ title: "Start import", desc: "" }
			],
			imaging: [
				{ title: "Type of data", desc: "Imaging", panel: "start" },
				{ title: "Choose data", desc: "Project and folder", panel: "imaging" },
				{ title: "Inspect", desc: "What we think you have", panel: "inspect" },
				{ title: "Map", desc: "Subject / study / series", panel: "map" },
				{ title: "Upload & import", desc: "Only the files you keep" }
			],
			nonimaging: [
				{ title: "Type of data", desc: "Non-imaging", panel: "start" },
				{ title: "Non-imaging import", desc: "Observations & interventions", panel: "nonimaging" }
			],
			squirrel: [
				{ title: "Type of data", desc: "squirrel package", panel: "start" },
				{ title: "Choose package", desc: "What's in it", panel: "squirrel" },
				{ title: "Upload & import", desc: "Subjects, studies & series" }
			],
			unsure: [
				{ title: "Type of data", desc: "Not sure", panel: "start" },
				{ title: "Identify", desc: "A few questions", panel: "unsure" },
				{ title: "Start import", desc: "" }
			]
		};

		/* decision tree for "Not sure". Each node is a question; each answer goes to
		   another node ('next') or ends with a result ('result', a key in TREE_RESULTS) */
		var DECISION_TREE = {
			start: {
				question: "Is your data made up of files that came from a device, instrument, or program?",
				help: "For example: scanner images (DICOM, NIfTI), EEG or eye-tracking recordings, task or experiment output files, audio or video, genetic data files.",
				answers: [
					{ label: "Yes, it's files from a device or program", result: "imaging" },
					{ label: "It's a squirrel package (.sqrl) exported from NiDB or another squirrel tool", result: "squirrel" },
					{ label: "No, it's a table or spreadsheet of values", next: "table" },
					{ label: "I'm not sure", next: "keepfiles" }
				]
			},
			table: {
				question: "What does each row of the table describe?",
				help: "Non-imaging data is one value per subject (or per subject at a visit), such as a questionnaire score or a medication dose.",
				answers: [
					{ label: "A subject, or a subject at a visit, with values like scores or doses", next: "obsint" },
					{ label: "Events or samples within one session, made by a device or task program (e.g. one file per session)", result: "imaging_task" },
					{ label: "Something else", result: "inspect" }
				]
			},
			keepfiles: {
				question: "Do you need to keep the original files, so you can download them exactly as they are later?",
				help: "Imaging data is archived as the original files. Non-imaging data is stored as individual values, and the original spreadsheet is not kept.",
				answers: [
					{ label: "Yes, keep the files", result: "imaging" },
					{ label: "No, I just have values to record for each subject", next: "obsint" },
					{ label: "Still not sure", result: "inspect" }
				]
			},
			obsint: {
				question: "Are the values measured about the subject, or given to the subject?",
				help: "",
				answers: [
					{ label: "Measured or observed: scores, vitals, ratings, lab values", result: "nonimaging_obs" },
					{ label: "Given or done: medications, doses, treatments", result: "nonimaging_int" },
					{ label: "Both", result: "nonimaging_both" }
				]
			}
		};

		/* where each tree result goes. 'path' is a key in WIZARD_PATHS */
		var TREE_RESULTS = {
			imaging:         { path: "imaging",    title: "This is imaging data", text: "In NiDB, imaging data is any data kept as files. NiDB will inspect the folder to work out the modality and file format." },
			imaging_task:    { path: "imaging",    title: "This is imaging data (task or device output)", text: "Per-session output files are archived as files, for example as a TASK series. NiDB will inspect the folder to confirm." },
			inspect:         { path: "imaging",    title: "Let NiDB take a look", text: "Choose the folder and NiDB will inspect the files before anything is uploaded. If it turns out to be a table of values, you can switch to non-imaging import." },
			nonimaging_obs:  { path: "nonimaging", title: "This is non-imaging data: observations", text: "Measured or observed values are imported as observations." },
			nonimaging_int:  { path: "nonimaging", title: "This is non-imaging data: interventions", text: "Things given to or done to a subject are imported as interventions." },
			nonimaging_both: { path: "nonimaging", title: "This is non-imaging data: observations and interventions", text: "One .csv file can hold both, using the type column." },
			squirrel:        { path: "squirrel",   title: "This is a squirrel package", text: "A squirrel package can hold imaging and non-imaging data together, so it has its own import." }
		};

		/* how each import path is shown in a tree result */
		var PATH_STYLE = {
			imaging:    { color: "blue",   icon: "file image outline", button: "Start imaging import" },
			nonimaging: { color: "green",  icon: "table",              button: "Start non-imaging import" },
			squirrel:   { color: "brown",  icon: "archive",            button: "Start squirrel import" }
		};

		/* ======================================================================
		   Wizard state and rendering
		   ====================================================================== */

		/* path: key in WIZARD_PATHS. panel: the panel shown (a step's 'panel'). node: current tree node.
		   trail: tree answers so far. result: tree result key, if the path was reached through the tree */
		var state = { path: "start", panel: "start", node: "start", trail: [], result: null };

		/* the latest inspection: the server's result plus the user's changes. Kept in memory only (a FileList
		   can't survive a page reload), so after a reload the wizard goes back to choosing the data */
		var inspection = null;

		/* the first panel of a path after "Type of data" */
		function DefaultPanel(path) {
			var steps = WIZARD_PATHS[path] || [];
			for (var i = 1; i < steps.length; i++)
				if (steps[i].panel) return steps[i].panel;
			return "start";
		}

		/* push the state into the browser history, so the browser Back button steps back through the wizard */
		function SetState(newstate) {
			state = newstate;
			history.pushState(state, "", "#" + state.path);
			Render();
		}

		window.addEventListener("popstate", function(e) {
			state = e.state || { path: "start", panel: "start", node: "start", trail: [], result: null };
			Render();
		});

		function Choose(path) {
			SetState({ path: path, panel: DefaultPanel(path), node: "start", trail: [], result: null });
		}

		function GoBack() {
			history.back();
		}

		function Answer(index) {
			var node = DECISION_TREE[state.node];
			var a = node.answers[index];
			var trail = state.trail.concat([{ node: state.node, label: a.label }]);
			if (a.next)
				SetState({ path: "unsure", panel: "unsure", node: a.next, trail: trail, result: null });
			else
				SetState({ path: "unsure", panel: "unsure", node: state.node, trail: trail, result: a.result });
		}

		/* index of the current panel's step in the path */
		function CurrentStepIndex() {
			var steps = WIZARD_PATHS[state.path];
			for (var i = 0; i < steps.length; i++)
				if (steps[i].panel == state.panel) return i;
			return 0;
		}

		function RenderSteps() {
			var steps = WIZARD_PATHS[state.path];
			var current = CurrentStepIndex();
			var bar = document.getElementById("wizsteps");
			bar.className = "ui attached fluid small steps";
			bar.innerHTML = "";
			steps.forEach(function(s, i) {
				var div = document.createElement("div");
				div.className = (i < current) ? "completed step" : (i == current) ? "active step" : "disabled step";
				var content = document.createElement("div");
				content.className = "content";
				var title = document.createElement("div");
				title.className = "title";
				title.textContent = s.title;
				var desc = document.createElement("div");
				desc.className = "description";
				desc.textContent = s.desc;
				content.appendChild(title);
				content.appendChild(desc);
				div.appendChild(content);
				bar.appendChild(div);
			});
		}

		function RenderTree() {
			var node = DECISION_TREE[state.node];

			/* trail of answers so far */
			var trail = document.getElementById("tree-trail");
			trail.innerHTML = "";
			state.trail.forEach(function(t, i) {
				if (i > 0) {
					var d = document.createElement("i");
					d.className = "right chevron icon divider";
					trail.appendChild(d);
				}
				var s = document.createElement("span");
				s.className = "section";
				s.textContent = t.label;
				trail.appendChild(s);
			});

			var answers = document.getElementById("tree-answers");
			answers.innerHTML = "";

			if (state.result) {
				var r = TREE_RESULTS[state.result];
				document.getElementById("tree-question").textContent = "";
				document.getElementById("tree-help").textContent = "";
				document.getElementById("tree-result").style.display = "";
				document.getElementById("tree-result-msg").className = "ui icon message " + PATH_STYLE[r.path].color;
				document.getElementById("tree-result-icon").className = PATH_STYLE[r.path].icon + " icon";
				document.getElementById("tree-result-title").textContent = r.title;
				document.getElementById("tree-result-text").textContent = r.text;
				var go = document.getElementById("tree-result-go");
				go.innerHTML = "";
				go.appendChild(document.createTextNode(PATH_STYLE[r.path].button + " "));
				var arrow = document.createElement("i");
				arrow.className = "right arrow icon";
				go.appendChild(arrow);
				go.onclick = function() { SetState({ path: r.path, panel: DefaultPanel(r.path), node: state.node, trail: state.trail, result: state.result }); };
				return;
			}

			document.getElementById("tree-result").style.display = "none";
			document.getElementById("tree-question").textContent = node.question;
			document.getElementById("tree-help").textContent = node.help;
			node.answers.forEach(function(a, i) {
				var b = document.createElement("button");
				b.className = "wizanswer ui fluid basic button";
				b.textContent = a.label;
				b.onclick = function() { Answer(i); };
				answers.appendChild(b);
			});
		}

		/* when the tree picked the path, say so at the top of the import panel */
		function RenderIdentified(panel) {
			var msg = document.getElementById(panel + "-identified");
			if (state.result && TREE_RESULTS[state.result] && (TREE_RESULTS[state.result].path == panel)) {
				msg.textContent = TREE_RESULTS[state.result].title + ". " + TREE_RESULTS[state.result].text;
				msg.style.display = "";
			}
			else {
				msg.style.display = "none";
			}
		}

		function Render() {
			if (!WIZARD_PATHS[state.path])
				state = { path: "start", panel: "start", node: "start", trail: [], result: null };
			/* history entries from before the panel was kept in the state, or a panel this path doesn't have */
			if (!state.panel || !WIZARD_PATHS[state.path].some(function(s) { return s.panel == state.panel; }))
				state.panel = (state.path == "start") ? "start" : DefaultPanel(state.path);
			/* the inspection isn't kept across a reload */
			if (((state.panel == "inspect") || (state.panel == "map")) && !inspection)
				state.panel = "imaging";
			if ((state.panel == "map") && !inspection.map)
				state.panel = "inspect";

			RenderSteps();
			var panel = state.panel;
			document.querySelectorAll(".wizpanel").forEach(function(p) {
				p.classList.toggle("active", p.id == "panel-" + panel);
			});

			if (panel == "unsure") RenderTree();
			if (panel == "imaging") RenderIdentified("imaging");
			if (panel == "nonimaging") RenderIdentified("nonimaging");
			if (panel == "squirrel") RenderIdentified("squirrel");
			if (panel == "map") ScheduleMap(0);
		}

		/* ======================================================================
		   Step 2 panels
		   ====================================================================== */

		function UpdateImagingLocation() {
			var loc = document.querySelector('input[name="imaging-location"]:checked').value;
			document.getElementById("imaging-localfiles").style.display = (loc == "localfiles") ? "" : "none";
			document.getElementById("imaging-localfolder").style.display = (loc == "localfolder") ? "" : "none";
			document.getElementById("imaging-nfs").style.display = (loc == "nfs") ? "" : "none";
			document.getElementById("imaging-localnote").style.display = (loc == "nfs") ? "none" : "";

			/* the quick look follows the selected local input */
			if (loc == "localfiles") QuickLook(document.getElementById("imaging-files").files);
			else if (loc == "localfolder") QuickLook(document.getElementById("imaging-folder").files);
			else QuickLook(null);
		}

		function HumanSize(bytes) {
			var units = ["B", "KB", "MB", "GB", "TB"];
			var i = 0;
			while ((bytes >= 1024) && (i < units.length - 1)) { bytes /= 1024; i++; }
			return bytes.toFixed((i == 0) ? 0 : 1) + " " + units[i];
		}

		/* summary of the chosen folder from the file list only (names, sizes). A placeholder for the inspection */
		function QuickLook(files) {
			var box = document.getElementById("imaging-quicklook");
			if (!files || (files.length == 0)) { box.style.display = "none"; return; }

			var total = 0, maxdepth = 0, exts = {};
			for (var i = 0; i < files.length; i++) {
				var f = files[i];
				total += f.size;
				var path = f.webkitRelativePath || f.name;
				maxdepth = Math.max(maxdepth, path.split("/").length - 1);
				var name = f.name.toLowerCase();
				var ext = name.endsWith(".nii.gz") ? ".nii.gz" : ((name.lastIndexOf(".") > 0) ? name.substring(name.lastIndexOf(".")) : "(none)");
				if (!exts[ext]) exts[ext] = { count: 0, size: 0 };
				exts[ext].count++;
				exts[ext].size += f.size;
			}

			var stats = document.getElementById("ql-stats");
			stats.innerHTML = "";
			[[files.length.toLocaleString(), "Files"], [HumanSize(total), "Total size"], [maxdepth, "Folder depth"]].forEach(function(s) {
				var st = document.createElement("div");
				st.className = "statistic";
				var v = document.createElement("div");
				v.className = "value";
				v.textContent = s[0];
				var l = document.createElement("div");
				l.className = "label";
				l.textContent = s[1];
				st.appendChild(v);
				st.appendChild(l);
				stats.appendChild(st);
			});

			var tbody = document.querySelector("#ql-ext tbody");
			tbody.innerHTML = "";
			Object.keys(exts).sort(function(a, b) { return exts[b].count - exts[a].count; }).slice(0, 10).forEach(function(e) {
				var tr = document.createElement("tr");
				[e, exts[e].count.toLocaleString(), HumanSize(exts[e].size)].forEach(function(v, i) {
					var td = document.createElement("td");
					if (i > 0) td.className = "right aligned";
					td.textContent = v;
					tr.appendChild(td);
				});
				tbody.appendChild(tr);
			});

			box.style.display = "";
		}

		/* ======================================================================
		   squirrel package
		   ====================================================================== */

		/* read the chosen package's squirrel.json in the browser (scripts/sqrlreader.js) and show what's in it */
		function ReadSquirrel() {
			var file = document.getElementById("squirrel-file").files[0];
			var err = document.getElementById("squirrel-error");
			var cont = document.getElementById("squirrel-continue");
			err.style.display = "none";
			document.getElementById("squirrel-summary").style.display = "none";
			cont.classList.add("disabled");
			if (!file) return;

			var reading = document.getElementById("squirrel-reading");
			reading.style.display = "";
			SqrlReader.ReadPackage(file).then(function(pkg) {
				RenderSquirrel(SqrlReader.Summarize(pkg.json), pkg.format, file);
				cont.classList.remove("disabled");
			}).catch(function(e) {
				err.textContent = (e && e.message) ? e.message : String(e);
				err.style.display = "";
			}).then(function() {
				reading.style.display = "none";
			});
		}

		/* which parts of a package are imported today: moduleUpload::ParseUploadedSquirrel() stages subjects, studies, and series */
		function RenderSquirrel(s, format, file) {
			document.getElementById("sq-name").textContent = s.name || file.name;
			var details = [file.name, HumanSize(file.size), (format == "7z") ? ".sqrl (7z)" : ".zip"];
			if (s.datetime) details.push("created " + s.datetime);
			if (s.squirrelversion) details.push("squirrel " + s.squirrelversion);
			if (s.dataformat) details.push("data format " + s.dataformat);
			document.getElementById("sq-details").textContent = details.join(" \u00B7 ");
			document.getElementById("sq-description").textContent = s.description;

			var mods = Object.keys(s.modalities).sort().map(function(m) { return m + " " + s.modalities[m].studies; }).join(", ");
			var rows = [
				["Subjects", s.subjects, true, ""],
				["Studies", s.studies, true, mods],
				["Imaging series", s.series, true, (s.files > 0) ? s.files.toLocaleString() + " files, " + HumanSize(s.size) : ""],
				["Observations", s.observations, false, ""],
				["Interventions", s.interventions, false, ""],
				["Study analyses", s.analyses, false, ""],
				["Group analyses", s.groupanalyses, false, ""],
				["Pipelines", s.pipelines, false, ""],
				["Experiments", s.experiments, false, ""],
				["Data dictionaries", s.datadictionaries, false, ""]
			];
			var tbody = document.querySelector("#sq-contents tbody");
			tbody.innerHTML = "";
			var notyet = [];
			rows.forEach(function(row) {
				if (!row[2] && (row[1] == 0)) return;
				if (!row[2]) notyet.push(row[1].toLocaleString() + " " + row[0].toLowerCase());
				var tr = El("tr");
				var name = El("td", "", row[0]);
				if (row[3]) { name.appendChild(El("br")); name.appendChild(El("span", "insevidence", row[3])); }
				tr.appendChild(name);
				tr.appendChild(El("td", "right aligned", row[1].toLocaleString()));
				var imp = El("td");
				imp.appendChild(El("i", row[2] ? "green check icon" : "grey minus icon"));
				imp.appendChild(document.createTextNode(row[2] ? "Yes" : "Not yet"));
				tr.appendChild(imp);
				tbody.appendChild(tr);
			});

			var warn = document.getElementById("sq-notyet");
			if (notyet.length > 0) {
				warn.textContent = "This package also has " + notyet.join(", ") + ". NiDB doesn't import those from squirrel packages yet; only the subjects, studies, and imaging series are imported.";
				warn.style.display = "";
			}
			else {
				warn.style.display = "none";
			}
			document.getElementById("squirrel-summary").style.display = "";
		}

		/* create the upload with the existing squirrel import (importimaging.php 'newimport'), which then shows its
		   upload page. The browser can't hand the chosen file to another page, so it is chosen once more there */
		function StartSquirrel() {
			var projectid = document.getElementById("squirrel-project").value;
			if (projectid == "") {
				var err = document.getElementById("squirrel-error");
				err.textContent = "Choose the destination project";
				err.style.display = "";
				return;
			}
			var fields = { action: "newimport", csrftoken: CSRF_TOKEN, datalocation: "web", projectid: projectid, filetype: "squirrel", modality: "", subjectcriteria: "patientid", studycriteria: "modalitystudydate", seriescriteria: "seriesnum" };
			var form = El("form");
			form.method = "post";
			form.action = "importimaging.php";
			Object.keys(fields).forEach(function(k) {
				var input = El("input");
				input.type = "hidden";
				input.name = k;
				input.value = fields[k];
				form.appendChild(input);
			});
			document.body.appendChild(form);
			form.submit();
		}

		function StartNonImaging() {
			var projectid = document.getElementById("nonimaging-project").value;
			window.location = "importnonimaging.php?action=newimportform" + ((projectid != "") ? "&projectid=" + encodeURIComponent(projectid) : "");
		}

		/* ======================================================================
		   Inspect
		   ====================================================================== */

		function ShowInspectError(msg) {
			var e = document.getElementById("inspect-error");
			e.textContent = msg;
			e.style.display = msg ? "" : "none";
		}

		/* fraction 0-1, or null for an indeterminate wait (the server is working) */
		function SetProgress(text, fraction) {
			var p = document.getElementById("inspect-progress");
			if (text === null) { p.style.display = "none"; return; }
			p.style.display = "";
			p.classList.toggle("active", fraction === null);
			p.querySelector(".bar").style.width = ((fraction === null) ? 100 : Math.round(fraction * 100)) + "%";
			p.querySelector(".label").textContent = text;
		}

		function StartInspect() {
			ShowInspectError("");
			var projectid = document.getElementById("imaging-project").value;
			if (projectid == "") { ShowInspectError("Choose the destination project"); return; }

			var loc = document.querySelector('input[name="imaging-location"]:checked').value;
			var fd = new FormData();
			fd.append("action", "inspect");
			fd.append("csrftoken", CSRF_TOKEN);
			fd.append("projectid", projectid);
			fd.append("source", loc);

			var ready;
			var mapfiles = null; /* every file's [path, size, mtime], for the mapping step */
			if (loc == "nfs") {
				var nfspath = document.getElementById("imaging-nfspath").value.trim();
				if (nfspath == "") { ShowInspectError("Enter the path of the folder on the server"); return; }
				fd.append("nfspath", nfspath);
				SetProgress("Inspecting the folder on the server. Large folders can take a few minutes", null);
				ready = Promise.resolve();
			}
			else {
				var files = document.getElementById((loc == "localfiles") ? "imaging-files" : "imaging-folder").files;
				if (!files || (files.length == 0)) { ShowInspectError((loc == "localfiles") ? "Choose the files to import" : "Choose the folder to import"); return; }
				SetProgress("Reading file headers on this computer", 0);
				ready = ImportInspect.BuildProfile(files, loc, II_CONFIG, function(stage, done, total) {
					SetProgress("Reading file headers on this computer (" + done + " of " + total + " sampled files)", (total > 0) ? done / total : 1);
				}).then(function(profile) {
					mapfiles = profile.files;
					return ImportInspect.PackProfile(profile);
				}).then(function(packed) {
					fd.append("encoding", packed.encoding);
					fd.append("profile", packed.blob, "profile");
					SetProgress("Identifying the data", null);
				});
			}

			var button = document.getElementById("inspect-button");
			button.classList.add("loading", "disabled");

			ready.then(function() {
				return fetch("importwizard.php", { method: "POST", body: fd, credentials: "same-origin" });
			}).then(function(r) {
				return r.text().then(function(t) {
					var j;
					try { j = JSON.parse(t); }
					catch (e) { throw new Error(/login\.php/.test(r.url) ? "Your session has expired. Reload the page and log in again" : "The server sent an unexpected response"); }
					if (!j.success) throw new Error(j.message || "The inspection failed");
					return j.result;
				});
			}).then(function(result) {
				/* for NFS the server sends the file list; for local data it's the list the profile was built from */
				inspection = { result: result, source: loc, projectid: projectid, layout: (result.layouts[0] || {}).code, groups: {}, files: (loc == "nfs") ? (result.files || []) : mapfiles, map: null };
				delete result.files;
				result.groups.forEach(function(g) { inspection.groups[g.key] = { include: g.include, type: g.type, modality: g.modality }; });
				SetProgress(null);
				RenderInspection();
				SetState({ path: state.path, panel: "inspect", node: state.node, trail: state.trail, result: state.result });
				window.scrollTo(0, 0);
			}).catch(function(e) {
				SetProgress(null);
				ShowInspectError(e.message || String(e));
			}).then(function() {
				button.classList.remove("loading", "disabled");
			});
		}

		/* ---------- rendering helpers ---------- */

		function El(tag, className, text) {
			var e = document.createElement(tag);
			if (className) e.className = className;
			if ((text !== undefined) && (text !== null)) e.textContent = text;
			return e;
		}

		/* confidence as five dots, e.g. ●●●●○ */
		function Dots(conf) {
			var n = Math.max(0, Math.min(5, Math.round((conf || 0) * 5)));
			var d = El("span", "insdots", "\u25CF".repeat(n) + "\u25CB".repeat(5 - n));
			d.title = Math.round((conf || 0) * 100) + "% confident";
			return d;
		}

		function ModalityName(code) {
			for (var i = 0; i < MODALITIES.length; i++)
				if (MODALITIES[i].code == code) return MODALITIES[i].desc;
			return code;
		}

		function FiletypeName(code) {
			for (var i = 0; i < FILETYPES.length; i++)
				if (FILETYPES[i].code == code) return FILETYPES[i].name;
			return (code == "IGNORE") ? "System files" : "Unknown";
		}

		/* a <select> of modalities; '' is "Choose..." */
		function ModalitySelect(value, onchange) {
			var sel = El("select");
			sel.appendChild(new Option("Choose...", ""));
			MODALITIES.forEach(function(m) { sel.appendChild(new Option(m.code + " (" + m.desc + ")", m.code)); });
			sel.value = value || "";
			sel.onchange = function() { onchange(sel.value); };
			return sel;
		}

		function AxisRow(tbody, label, valueNode, conf, evidence) {
			var tr = El("tr");
			tr.appendChild(El("td", "collapsing", label));
			var td = El("td");
			td.appendChild(valueNode);
			tr.appendChild(td);
			var tdc = El("td", "collapsing");
			if (conf !== null) tdc.appendChild(Dots(conf));
			tr.appendChild(tdc);
			tr.appendChild(El("td", "insevidence", (evidence || []).join(". ")));
			tbody.appendChild(tr);
		}

		/* ---------- the result ---------- */

		function RenderInspection() {
			var r = inspection.result;
			var sm = r.summary;
			document.getElementById("ins-summary").textContent = (sm.root ? sm.root + " \u00B7 " : "") + sm.numfiles.toLocaleString() + " files \u00B7 " + HumanSize(sm.totalsize) + ((sm.mtimemin != "") ? " \u00B7 modified " + sm.mtimemin + ((sm.mtimemax != sm.mtimemin) ? " to " + sm.mtimemax : "") : "");

			/* hints, e.g. this looks like non-imaging data */
			var hints = document.getElementById("ins-hints");
			hints.innerHTML = "";
			r.hints.forEach(function(h) {
				var m = El("div", "ui info message");
				m.appendChild(El("p", "", h.text));
				if (h.kind == "nonimaging") {
					var b = El("button", "ui small green button", "Switch to non-imaging import");
					b.onclick = function() { Choose("nonimaging"); };
					m.appendChild(b);
				}
				if (h.kind == "squirrel") {
					var bs = El("button", "ui small brown button", "Switch to squirrel import");
					bs.onclick = function() { Choose("squirrel"); };
					m.appendChild(bs);
				}
				if (h.kind == "dicom") {
					var bd = El("a", "ui small blue button", "Go to the DICOM import");
					bd.href = "importimaging.php?action=newimportform&filetype=auto" + (inspection.projectid ? "&projectid=" + encodeURIComponent(inspection.projectid) : "");
					m.appendChild(bd);
				}
				hints.appendChild(m);
			});

			/* possible patient information (D6: factual, never blocking) */
			var phi = document.getElementById("ins-phi");
			var ul = phi.querySelector("ul");
			ul.innerHTML = "";
			r.phiwarnings.forEach(function(w) { ul.appendChild(El("li", "", w)); });
			phi.style.display = (r.phiwarnings.length > 0) ? "" : "none";

			RenderAxes();
			RenderGroups();

			/* folder levels and common paths */
			var lt = document.querySelector("#ins-levels tbody");
			lt.innerHTML = "";
			r.levels.forEach(function(l) {
				var tr = El("tr");
				tr.appendChild(El("td", "", "Level " + l.level));
				tr.appendChild(El("td", "right aligned", l.distinct.toLocaleString()));
				tr.appendChild(El("td", "", l.shapes.map(function(s) { return s.shape; }).join(", ")));
				tr.appendChild(El("td", "insevidence", l.examples.join(", ")));
				lt.appendChild(tr);
			});
			if (r.levels.length == 0) {
				var tr0 = El("tr");
				var td0 = El("td", "insevidence", "All files are in one folder");
				td0.colSpan = 4;
				tr0.appendChild(td0);
				lt.appendChild(tr0);
			}
			var st = document.querySelector("#ins-shapes tbody");
			st.innerHTML = "";
			r.shapes.slice(0, 8).forEach(function(s) {
				var tr = El("tr");
				tr.appendChild(El("td", "", s.shape));
				tr.appendChild(El("td", "right aligned collapsing", s.count.toLocaleString() + " files"));
				st.appendChild(tr);
			});

			document.getElementById("ins-notes").textContent = r.notes.join(". ") + ".";
			$("#ins-details").accordion();
		}

		/* modality, file type, and structure, then the subject/study/series identity from the chosen structure */
		function RenderAxes() {
			var r = inspection.result;
			var tbody = document.querySelector("#ins-axes tbody");
			tbody.innerHTML = "";

			/* modality: one dropdown when every group agrees, otherwise "mixed", set per group below */
			var mods = {};
			var modconf = 1;
			r.groups.forEach(function(g) {
				var e = inspection.groups[g.key];
				if (e.include && (g.role == "primary")) { if (e.modality) mods[e.modality] = true; modconf = Math.min(modconf, (e.modality == g.modality) ? g.modconf : 1); }
			});
			var modlist = Object.keys(mods);
			var modnode;
			if (modlist.length > 1) {
				modnode = El("span", "", "Mixed: " + modlist.join(", ") + " (set per group below)");
			}
			else {
				modnode = ModalitySelect(modlist[0] || "", function(v) {
					r.groups.forEach(function(g) { if (g.role == "primary" || g.role == "companion") inspection.groups[g.key].modality = v; });
					RenderAxes();
					RenderGroups();
				});
			}
			AxisRow(tbody, "Modality", modnode, (modlist.length > 0) ? modconf : 0, r.axes.modality.evidence);

			/* file type */
			var ft = r.axes.filetype;
			var ftnode = El("span", "", (ft.value == "Mixed") ? "Mixed: " + ft.values.join(", ") : ft.value);
			if (ft.subtypes.length > 0) ftnode.appendChild(El("span", "insevidence", "  + " + ft.subtypes.join(", ")));
			AxisRow(tbody, "File type", ftnode, ft.confidence, ft.evidence);

			/* structure: the detected layouts, best first */
			var layout = null;
			r.layouts.forEach(function(l) { if (l.code == inspection.layout) layout = l; });
			if (!layout) layout = r.layouts[0];
			var sel = El("select");
			r.layouts.forEach(function(l) { sel.appendChild(new Option(l.name + " (" + Math.round(l.confidence * 100) + "%)", l.code)); });
			sel.value = layout.code;
			sel.onchange = function() { inspection.layout = sel.value; RenderAxes(); };
			AxisRow(tbody, "Structure", sel, layout.confidence, layout.evidence);

			AxisRow(tbody, "Subject", El("span", "", layout.identity.subject), null, []);
			AxisRow(tbody, "Study", El("span", "", layout.identity.study), null, []);
			AxisRow(tbody, "Series", El("span", "", layout.identity.series), null, []);
		}

		function RenderGroups() {
			var r = inspection.result;
			var tbody = document.querySelector("#ins-groups tbody");
			tbody.innerHTML = "";
			r.groups.forEach(function(g) {
				var e = inspection.groups[g.key];
				var tr = El("tr", ((g.role == "unknown") ? "insunknown " : "") + (e.include ? "" : "insexcluded"));

				var cb = El("input");
				cb.type = "checkbox";
				cb.checked = e.include;
				cb.onchange = function() { e.include = cb.checked; RenderAxes(); RenderGroups(); };
				var tdcb = El("td", "center aligned");
				tdcb.appendChild(cb);
				tr.appendChild(tdcb);

				var name = El("td");
				name.appendChild(El("b", "", g.label));
				if (g.examples.length > 0) { name.appendChild(El("br")); name.appendChild(El("span", "insevidence", g.examples[0])); }
				tr.appendChild(name);
				tr.appendChild(El("td", "right aligned", g.count.toLocaleString() + ((g.units > 0) ? " (" + g.units.toLocaleString() + " folders)" : "")));
				tr.appendChild(El("td", "right aligned", HumanSize(g.size)));

				/* file type: companions and system files are fixed; others can be changed */
				var tdt = El("td");
				if (g.role == "companion") {
					tdt.appendChild(El("span", "", "With the " + g.companionlabel + " files"));
				}
				else if (g.role == "ignore") {
					tdt.appendChild(El("span", "", g.typename));
				}
				else {
					var ts = El("select");
					ts.appendChild(new Option("Unknown", ""));
					FILETYPES.forEach(function(t) { ts.appendChild(new Option(t.name, t.code)); });
					ts.value = e.type || "";
					ts.onchange = function() { e.type = ts.value; };
					tdt.appendChild(ts);
					tdt.appendChild(document.createTextNode(" "));
					tdt.appendChild(Dots(g.typeconf));
				}
				tr.appendChild(tdt);

				/* modality */
				var tdm = El("td");
				if (g.role == "companion") {
					tdm.appendChild(El("span", "insevidence", "Same as " + g.companionlabel));
				}
				else if ((g.role != "ignore") && (g.role != "metadata")) {
					tdm.appendChild(ModalitySelect(e.modality, function(v) { e.modality = v; RenderAxes(); }));
					if (g.modality != "") { tdm.appendChild(document.createTextNode(" ")); tdm.appendChild(Dots(g.modconf)); }
				}
				tr.appendChild(tdm);

				var why = g.evidence.slice();
				if (g.role == "unknown") why.unshift("Not recognized. Include it if it belongs with this data");
				tr.appendChild(El("td", "insevidence", why.join(". ")));
				tbody.appendChild(tr);
			});
		}

		/* ======================================================================
		   Map to subjects, studies, and series (scripts/importmap.js)
		   ====================================================================== */

		var MAP_ROLES = [
			{ role: "subject",    name: "Subject ID" },
			{ role: "studylabel", name: "Visit / study label" },
			{ role: "studydate",  name: "Study date" },
			{ role: "seriesdesc", name: "Series name" },
			{ role: "seriesnum",  name: "Series number" }
		];
		var mapTree = null;
		var mapShown = 25;
		var mapTimer = null;
		var matchTimer = null;
		var matchSeq = 0;
		var matchResult = null; /* {key, subjects: {id: status}, studies: {key: status}} for the current tree */

		/* from the inspection step to the mapping. The units are rebuilt each time, since the included groups and
		   modalities may have changed; the mapping is kept unless the structure was changed */
		function GoMap() {
			var r = inspection.result;
			var prepared = ImportMap.Prepare(inspection.files || [], r, inspection.groups, II_CONFIG.bundles);
			var layout = null;
			r.layouts.forEach(function(l) { if (l.code == inspection.layout) layout = l; });
			var map = inspection.map;
			if (!map || (map.layout != inspection.layout))
				map = { layout: inspection.layout, mapping: ImportMap.DefaultMapping(layout, prepared, r) };
			map.prepared = prepared;
			map.examples = ImportMap.ShapeExamples(prepared.units, 10);
			inspection.map = map;
			matchResult = null;
			mapShown = 25;

			var sel = document.getElementById("map-example");
			sel.innerHTML = "";
			map.examples.forEach(function(e, i) { sel.appendChild(new Option(e.unit.path + ((map.examples.length > 1) ? "  (" + Plural(e.count, "file", "files") + " like this)" : ""), i)); });

			SetState({ path: state.path, panel: "map", node: state.node, trail: state.trail, result: state.result });
			window.scrollTo(0, 0);
		}

		function RenderMapControls() {
			var m = inspection.map.mapping;
			var rk = ImportMap.RoleKeys(m);

			document.getElementById("map-subjectconst-field").style.display = (rk.subject.length == 0) ? "" : "none";
			document.getElementById("map-subjectconst").value = m.subjectconst;

			/* the date and series options only matter when the path doesn't give them */
			var ds = document.getElementById("map-datesource");
			var dicom = inspection.map.prepared.units.some(function(u) { return u.primary && (u.type == "DICOM"); });
			ds.querySelector("option[value=header]").hidden = !dicom;
			if ((m.datesource == "role") || ((m.datesource == "header") && !dicom)) m.datesource = "mtime";
			ds.value = m.datesource;
			ds.disabled = (rk.studydate.length > 0);
			document.getElementById("map-datesource-note").textContent = ds.disabled ? "The path has the study date" : "";
			var gr = document.getElementById("map-grouping");
			gr.value = m.grouping;
			gr.disabled = (rk.seriesdesc.length > 0) || (rk.seriesnum.length > 0);
			document.getElementById("map-grouping-note").textContent = gr.disabled ? "The path has the series name or number" : "";
			RenderMapPath();
		}

		/* a dropdown of what a name or part is. A name with several parts can be used as a whole or by its parts */
		function RoleSelect(value, parts) {
			var sel = El("select");
			sel.appendChild(new Option(parts ? "Use the parts below" : "Not used", ""));
			MAP_ROLES.forEach(function(r) { sel.appendChild(new Option(r.name, r.role)); });
			sel.value = value;
			return sel;
		}

		var DELIM_NAMES = { "_": "_", "-": "-", ".": ".", " ": "space" };

		/* checkboxes for the delimiters that appear in a name */
		function DelimControls(seg, text, delims) {
			var box = El("div", "mapdelims");
			var present = ImportMap.DELIMS.split("").filter(function(c) { return text.indexOf(c) >= 0; });
			if (present.length == 0) return null;
			box.appendChild(document.createTextNode("Split at "));
			present.forEach(function(c) {
				var lab = El("label");
				var cb = El("input");
				cb.type = "checkbox";
				cb.checked = delims.indexOf(c) >= 0;
				cb.onchange = function() { SetDelim(seg, c, this.checked); };
				lab.appendChild(cb);
				lab.appendChild(El("code", "", DELIM_NAMES[c]));
				box.appendChild(lab);
			});
			return box;
		}

		/* the example path: one row per folder and the file name, and a row per part when a name holds several things */
		function RenderMapPath() {
			var map = inspection.map;
			var m = map.mapping;
			var ex = map.examples[parseInt(document.getElementById("map-example").value || "0", 10)] || map.examples[0];
			var tbody = document.querySelector("#map-parts tbody");
			tbody.innerHTML = "";
			if (!ex) { document.getElementById("map-path").textContent = "No files to map"; document.getElementById("map-template").textContent = ""; return; }
			var u = ex.unit;
			document.getElementById("map-path").textContent = u.folders.concat([u.base + u.ext]).join(" / ");

			var segs = u.folders.map(function(f, i) { return { seg: "d" + i, text: f, what: "Folder " + (i + 1) }; });
			segs.push({ seg: "f", text: u.base, what: "File name" + (u.ext ? " (without " + u.ext + ")" : "") });
			segs.forEach(function(sg) {
				var delims = ImportMap.SegDelims(m, sg.seg);
				var toks = ImportMap.Tokenize(sg.text, delims);
				var parts = toks.length > 1;
				var tr = El("tr", "mapwholerow");
				tr.appendChild(El("td", "mapname", sg.text));
				var info = El("td", "insevidence", sg.what);
				var dc = m.roles[sg.seg] ? null : DelimControls(sg.seg, sg.text, delims);
				if (dc) info.appendChild(dc);
				tr.appendChild(info);
				var sel = RoleSelect(m.roles[sg.seg] || "", parts);
				sel.onchange = function() { SetRole(sg.seg, sg.seg, this.value); };
				var td = El("td");
				td.appendChild(sel);
				tr.appendChild(td);
				tbody.appendChild(tr);
				if (!parts || m.roles[sg.seg]) return;
				toks.forEach(function(t, j) {
					var key = sg.seg + ".t" + j;
					var trp = El("tr", "mappart");
					trp.appendChild(El("td", "mapname", t.text));
					trp.appendChild(El("td", "insevidence", "Part " + (j + 1)));
					var sp = RoleSelect(m.roles[key] || "", false);
					sp.onchange = function() { SetRole(sg.seg, key, this.value); };
					var tdp = El("td");
					tdp.appendChild(sp);
					trp.appendChild(tdp);
					tbody.appendChild(trp);
				});
			});
			var friendly = { subject: "subject", studylabel: "visit", studydate: "date", seriesdesc: "series name", seriesnum: "series number" };
			document.getElementById("map-template").textContent = ImportMap.Template(u, m).replace(/\{(\w+)\}/g, function(x, r) { return "{" + (friendly[r] || r) + "}"; });
		}

		/* set what a whole name (key == seg) or one of its parts is. A whole name and its parts can't both be used */
		function SetRole(seg, key, role) {
			var roles = inspection.map.mapping.roles;
			if (key == seg) {
				Object.keys(roles).forEach(function(k) { if ((k == seg) || (k.indexOf(seg + ".t") == 0)) delete roles[k]; });
				if (role) roles[seg] = role;
			}
			else if (role) roles[key] = role;
			else delete roles[key];
			ScheduleMap(0);
		}

		/* split a name at a delimiter, or stop splitting at it. The parts move, so their roles are cleared */
		function SetDelim(seg, c, on) {
			var m = inspection.map.mapping;
			if (!m.delims) m.delims = {};
			var cur = ImportMap.SegDelims(m, seg);
			var d = ImportMap.DELIMS.split("").filter(function(x) { return (x == c) ? on : (cur.indexOf(x) >= 0); }).join("");
			if (d == ImportMap.DELIMS) delete m.delims[seg];
			else m.delims[seg] = d;
			Object.keys(m.roles).forEach(function(k) { if (k.indexOf(seg + ".t") == 0) delete m.roles[k]; });
			ScheduleMap(0);
		}

		function SetSubjectConst(v) {
			inspection.map.mapping.subjectconst = v;
			ScheduleMap(300);
		}

		function SetMapOption(name, v) {
			inspection.map.mapping[name] = v;
			ScheduleMap(0);
		}

		/* mapping 100,000s of files takes a moment, so updates are batched */
		function ScheduleMap(delay) {
			if (!inspection || !inspection.map) return;
			RenderMapControls();
			clearTimeout(mapTimer);
			var big = inspection.map.prepared.units.length > 20000;
			if (big) document.getElementById("map-updating").style.display = "";
			mapTimer = setTimeout(function() {
				mapTree = ImportMap.Apply(inspection.map.prepared, inspection.map.mapping);
				document.getElementById("map-updating").style.display = "none";
				RenderMapResult();
				ScheduleMatch();
			}, Math.max(delay, big ? 30 : 0));
		}

		/* ---------- the result ---------- */

		/* "1 file doesn't", "3 files don't" */
		function Plural(n, one, many) { return n.toLocaleString() + " " + ((n == 1) ? one : many); }

		function StudyKey(id, st) { return [id, st.modality, st.date, st.label].join("\u0001"); }

		function RenderMapResult() {
			var t = mapTree;
			var m = inspection.map.mapping;
			var prep = inspection.map.prepared;

			/* things to check */
			var checks = document.getElementById("map-checks");
			checks.innerHTML = "";
			var warn = [];
			var c = t.checks;
			var rolename = {};
			MAP_ROLES.forEach(function(r) { rolename[r.role] = r.name; });
			if (t.counts.misfits > 0) warn.push(Plural(t.counts.misfits, "file doesn't", "files don't") + " fit the mapping and won't be imported (see \"Files that don't fit\" below)");
			if (c.nomodality > 0) warn.push(Plural(c.nomodality, "series has", "series have") + " no modality. Set it for their file group on the previous step");
			if (c.mtimestudies > 0) warn.push(Plural(c.mtimestudies, "study is", "studies are") + " dated by file modification time, which changes when files are copied. If part of the path is the date, set it to \"Study date\" above");
			if (c.nodatestudies > 0) warn.push(Plural(c.nodatestudies, "study has", "studies have") + " no date yet; you'll be asked for it in the next step");
			if (c.duplicatenumbers > 0) warn.push(Plural(c.duplicatenumbers, "series number is", "series numbers are") + " used more than once in the same study");
			c.odd.forEach(function(o) { warn.push(o.count.toLocaleString() + " " + rolename[o.role].toLowerCase() + " values don't look like the rest (most are " + o.shape + "; others: " + o.shapes.join(", ") + ")"); });
			if (warn.length > 0) {
				var msg = El("div", "ui warning message");
				var ul = El("ul", "list");
				warn.forEach(function(w) { ul.appendChild(El("li", "", w)); });
				msg.appendChild(ul);
				checks.appendChild(msg);
			}

			RenderMapStats();
			RenderMapTree();

			/* files that don't fit */
			document.getElementById("map-misfit-count").textContent = "(" + t.counts.misfits.toLocaleString() + ")";
			var mt = document.querySelector("#map-misfits tbody");
			mt.innerHTML = "";
			t.misfits.forEach(function(mf) {
				var tr = El("tr");
				tr.appendChild(El("td", "", mf.reason));
				tr.appendChild(El("td", "right aligned", mf.count.toLocaleString()));
				tr.appendChild(El("td", "insevidence", mf.examples.join(", ")));
				mt.appendChild(tr);
			});
			[[prep.dataset, "Dataset files (such as dataset_description.json and README) are kept with the import, outside any series"], [prep.archives, "Archives are unpacked after upload, and their contents are mapped then"], [prep.excluded, "In file groups that aren't imported (previous step)"]].forEach(function(x) {
				if (x[0].count == 0) return;
				var tr = El("tr");
				tr.appendChild(El("td", "insevidence", x[1]));
				tr.appendChild(El("td", "right aligned", x[0].count.toLocaleString()));
				tr.appendChild(El("td", "", ""));
				mt.appendChild(tr);
			});

			/* the recipe this would save */
			var r = inspection.result;
			var mods = {};
			r.groups.forEach(function(g) { var e = inspection.groups[g.key]; if (e.include && e.modality && (g.role == "primary")) mods[e.modality] = true; });
			var axes = { modality: Object.keys(mods), filetype: r.axes.filetype.values, subtypes: r.axes.filetype.subtypes, layout: inspection.layout };
			var groups = r.groups.map(function(g) { return { key: g.key, include: inspection.groups[g.key].include }; });
			var ex = inspection.map.examples[0];
			document.getElementById("map-recipe").textContent = JSON.stringify(ImportMap.Recipe(m, axes, groups, ex ? ex.unit : null, t), null, 2);
			$("#map-details").accordion();
		}

		function RenderMapStats() {
			var t = mapTree;
			var sub = "", stu = "";
			if (matchResult) {
				var sc = { enrolled: 0, other: 0, "new": 0, ambiguous: 0 }, tc = { existing: 0, "new": 0 };
				t.subjects.forEach(function(s) {
					var ms = matchResult.subjects[s.id];
					if (ms) sc[ms.status] = (sc[ms.status] || 0) + 1;
					s.studylist.forEach(function(st) { var x = matchResult.studies[StudyKey(s.id, st)]; if (x && (tc[x.status] !== undefined)) tc[x.status]++; });
				});
				sub = sc["new"] + " new, " + (sc.enrolled + sc.other) + " existing";
				stu = tc["new"] + " new, " + tc.existing + " existing";
			}
			var stats = [
				[t.counts.subjects.toLocaleString(), "Subjects", sub],
				[t.counts.studies.toLocaleString(), "Studies", stu],
				[t.counts.series.toLocaleString(), "Series", ""],
				[t.counts.files.toLocaleString(), "Files", ""],
				[t.counts.misfits.toLocaleString(), "Don't fit", ""]
			];
			var box = document.getElementById("map-stats");
			box.innerHTML = "";
			stats.forEach(function(x, i) {
				var st = El("div", "statistic" + ((i == 4) && (t.counts.misfits > 0) ? " orange" : ""));
				st.appendChild(El("div", "value", x[0]));
				var l = El("div", "label", x[1]);
				if (x[2]) { l.appendChild(El("br")); l.appendChild(El("span", "insevidence", x[2])); }
				st.appendChild(l);
				box.appendChild(st);
			});
		}

		function StatusLabel(color, text, title) {
			var l = El("span", "ui tiny " + color + " basic label", text);
			if (title) l.title = title;
			return l;
		}

		/* subjects, then their studies and series. Long lists are shown a page at a time */
		function RenderMapTree() {
			var t = mapTree;
			var hdr = inspection.map.mapping.header;
			var tbody = document.querySelector("#map-tree tbody");
			tbody.innerHTML = "";
			var Row = function(cls, cells) {
				var tr = El("tr", cls);
				cells.forEach(function(c, i) {
					var td = El("td", c.cls || "");
					if (c.node) td.appendChild(c.node); else td.textContent = c.text || "";
					tr.appendChild(td);
				});
				tbody.appendChild(tr);
			};
			t.subjects.slice(0, mapShown).forEach(function(s) {
				var ms = matchResult ? matchResult.subjects[s.id] : null;
				var status = El("span", "");
				if (hdr.subject) status.appendChild(StatusLabel("grey", "Matched after upload"));
				else if (!ms) status.appendChild(StatusLabel("grey", matchResult === false ? "Not checked" : "Checking..."));
				else if (ms.status == "enrolled") status.appendChild(StatusLabel("green", "In this project", "NiDB subject " + ms.uid));
				else if (ms.status == "other") status.appendChild(StatusLabel("blue", "In NiDB, will be enrolled", "This ID belongs to a NiDB subject who isn't enrolled in this project yet"));
				else if (ms.status == "ambiguous") status.appendChild(StatusLabel("red", "Matches " + ms.count + " subjects", "More than one NiDB subject has this ID. This must be resolved before import"));
				else status.appendChild(StatusLabel("orange", "New subject"));
				if (ms && (ms.status == "enrolled")) status.appendChild(El("span", "insevidence", " " + ms.uid));
				Row("mapsubject", [{ text: hdr.subject ? "Subjects from DICOM PatientID" : s.id }, { text: "" }, { text: s.files.toLocaleString(), cls: "right aligned" }, { text: HumanSize(s.size), cls: "right aligned" }, { node: status }]);

				s.studylist.forEach(function(st) {
					var name = El("span", "");
					name.appendChild(El("b", "", st.modality || "?"));
					name.appendChild(document.createTextNode(" study "));
					if (hdr.study) name.appendChild(El("span", "insevidence", "from the DICOM headers"));
					else {
						if (st.date) name.appendChild(document.createTextNode(st.date + " "));
						if (st.datesource == "mtime") name.appendChild(El("span", "insevidence", "(file date) "));
						if (st.datesource == "header") name.appendChild(El("span", "insevidence", "(date from headers) "));
						if (st.datesource == "none") name.appendChild(El("span", "insevidence", "(no date yet) "));
						if (st.label) name.appendChild(document.createTextNode("“" + st.label + "”"));
					}
					var x = (matchResult && !hdr.subject) ? matchResult.studies[StudyKey(s.id, st)] : null;
					var sstat = El("span", "");
					if (x && (x.status == "existing")) sstat.appendChild(StatusLabel("green", "Existing study " + x.studynum, "Series are added to this study"));
					else if (x && (x.status == "ambiguous")) sstat.appendChild(StatusLabel("red", "Matches studies " + x.studynums.join(", ")));
					else if (x && (x.status == "new")) sstat.appendChild(StatusLabel("orange", "New study"));
					else if (x && (x.status == "unknown")) sstat.appendChild(StatusLabel("grey", "Matched after upload"));
					Row("", [{ node: name, cls: "mapindent1" }, { text: st.series.length.toLocaleString() + " series", cls: "insevidence" }, { text: st.files.toLocaleString(), cls: "right aligned" }, { text: HumanSize(st.size), cls: "right aligned" }, { node: sstat }]);

					var MAXSERIES = 12;
					st.series.slice(0, MAXSERIES).forEach(function(se) {
						var sn = El("span", "");
						if (hdr.series) sn.appendChild(El("span", "insevidence", "Series from the DICOM headers"));
						else {
							sn.appendChild(El("b", "", se.num + " "));
							sn.appendChild(document.createTextNode(se.desc));
							if (se.modality && (se.modality != st.modality)) sn.appendChild(El("span", "insevidence", " " + se.modality));
						}
						var from = se.first + ((se.units > 1) ? " (+" + (se.units - 1).toLocaleString() + ")" : "");
						Row("", [{ node: sn, cls: "mapindent2" }, { text: from, cls: "insevidence" }, { text: se.files.toLocaleString(), cls: "right aligned" }, { text: HumanSize(se.size), cls: "right aligned" }, { text: "" }]);
					});
					if (st.series.length > MAXSERIES)
						Row("", [{ text: "+ " + (st.series.length - MAXSERIES).toLocaleString() + " more series", cls: "mapindent2 insevidence" }, {}, {}, {}, {}]);
				});
			});
			if (t.subjects.length == 0)
				Row("", [{ text: "No files are mapped yet. Choose which part of the path is the subject", cls: "insevidence" }, {}, {}, {}, {}]);
			var more = document.getElementById("map-more");
			more.style.display = (t.subjects.length > mapShown) ? "" : "none";
			more.textContent = "Show more subjects (" + (t.subjects.length - mapShown).toLocaleString() + " not shown)";
		}

		/* ---------- matching against NiDB ---------- */

		/* after the mapping settles, ask the server which subjects and studies already exist. Responses for an
		   older mapping are ignored */
		function ScheduleMatch() {
			clearTimeout(matchTimer);
			var t = mapTree;
			var hdr = inspection.map.mapping.header;
			var note = document.getElementById("map-matchnote");
			note.textContent = "";
			if (hdr.subject || (t.subjects.length == 0)) { matchResult = null; return; }

			var subjects = t.subjects.map(function(s) { return s.id; });
			var studies = [];
			t.subjects.forEach(function(s) {
				s.studylist.forEach(function(st) { studies.push([s.id, st.modality, (st.datesource == "path") ? st.date : "", st.label]); });
			});
			if ((subjects.length > II_MATCH.maxsubjects) || (studies.length > II_MATCH.maxstudies)) {
				matchResult = false;
				note.textContent = "Too many subjects to check against NiDB here; they're matched during import";
				RenderMapTree();
				return;
			}
			var key = JSON.stringify([subjects, studies]);
			if (matchResult && (matchResult.key == key)) { RenderMapStats(); RenderMapTree(); return; }
			matchResult = null;

			var seq = ++matchSeq;
			matchTimer = setTimeout(function() {
				var fd = new FormData();
				fd.append("action", "match");
				fd.append("csrftoken", CSRF_TOKEN);
				fd.append("projectid", inspection.projectid);
				ImportInspect.PackProfile({ subjects: subjects, studies: studies }).then(function(packed) {
					fd.append("encoding", packed.encoding);
					fd.append("data", packed.blob, "data");
					return fetch("importwizard.php", { method: "POST", body: fd, credentials: "same-origin" });
				}).then(function(r) {
					return r.json().catch(function() { throw new Error("The server sent an unexpected response"); });
				}).then(function(j) {
					if (seq != matchSeq) return;
					if (!j.success) throw new Error(j.message || "Checking against NiDB failed");
					var res = { key: key, subjects: {}, studies: {} };
					subjects.forEach(function(id, i) { res.subjects[id] = j.result.subjects[i]; });
					/* the studies were sent in tree order; key them by what the tree shows (including file dates) */
					var i = 0;
					mapTree.subjects.forEach(function(s) { s.studylist.forEach(function(st) { res.studies[StudyKey(s.id, st)] = j.result.studies[i++]; }); });
					matchResult = res;
					RenderMapStats();
					RenderMapTree();
				}).catch(function(e) {
					if (seq != matchSeq) return;
					matchResult = false;
					note.textContent = "Couldn't check against NiDB: " + (e.message || String(e));
					RenderMapTree();
				});
			}, 600);
		}

		/* initial state: from the history (page reload) or the URL hash */
		$(document).ready(function() {
			$('.ui.radio.checkbox').checkbox();
			$("#map-options").accordion();
			if (history.state && history.state.path) {
				state = history.state;
			}
			else {
				var path = window.location.hash.replace("#", "");
				if (WIZARD_PATHS[path]) { state.path = path; state.panel = (path == "start") ? "start" : DefaultPanel(path); }
				history.replaceState(state, "", "#" + state.path);
			}
			Render();
		});
		</script>
		<?
	}
?>
<? include("footer.php") ?>
