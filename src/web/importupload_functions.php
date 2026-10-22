<?
 // ------------------------------------------------------------------------------
 // NiDB importupload_functions.php
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

 /* Upload and import for the import wizard (importwizard.php), doc/universal-import.md §5 steps 5-6.

	The browser sends a manifest: the mapping it previewed, resolved to subject -> study -> series ->
	file paths (scripts/importmap.js Manifest()). Creating the upload saves the manifest to
	[uploaddir]/universal/<upload_id>.json, an unnamed import_recipes version (D4), the inspection
	result, and the decision log (D8). Local files then arrive through tusupload.php with their
	relative paths; NFS data is already in place. moduleUpload (C++) copies the files to staging,
	loads upload_subjects/studies/series from the manifest, and archives them, either straight away
	(upload_autoarchive) or after the user reviews them here.

	Needs importinspect_functions.php (IIResolveNfsPath, IIBoundQuery) */

	if (!defined("LEGIT_REQUEST")) die ("This page cannot be called directly.");

	define("IU_MAXMANIFESTBYTES", 536870912); /* decompressed manifest */
	define("IU_MAXPATHS", 2000000);
	define("IU_MAXSUBJECTS", 100000);
	define("IU_MAXDECISIONS", 5000);
	define("IU_MAXRECIPEBYTES", 1048576);
	define("IU_MAXPROFILEBYTES", 33554432);


	/* -------------------------------------------- */
	/* ------- IIValidRelPath --------------------- */
	/* -------------------------------------------- */
	/* a relative path the import accepts: no empty, '.', '..', or hidden parts, no control characters or
	   backslashes. Same rule as TusSanitizeRelativePath() and UploadablePath() in scripts/importmap.js */
	function IIValidRelPath($path) {
		return is_string($path) && (TusSanitizeRelativePath($path) !== "");
	}


	/* -------------------------------------------- */
	/* ------- IUReadUploadedJson ----------------- */
	/* -------------------------------------------- */
	/* a JSON file field from the request, gzipped when $_POST[<field>encoding] is 'gzip'. Returns the decoded
	   value, or null and sets $err */
	function IUReadUploadedJson($field, $maxbytes, &$err) {
		$err = '';
		$f = $_FILES[$field] ?? null;
		if (!is_array($f) || ($f['error'] != UPLOAD_ERR_OK)) {
			$toobig = (is_array($f) && in_array($f['error'], array(UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE)));
			$err = "The $field didn't reach the server" . ($toobig ? ", because it is larger than the server's upload size limit" : "");
			return null;
		}
		$raw = file_get_contents($f['tmp_name']);
		if (($raw !== false) && (($_POST[$field . 'encoding'] ?? '') == 'gzip'))
			$raw = @gzdecode($raw, $maxbytes);
		if (($raw === false) || (strlen($raw) > $maxbytes)) {
			$err = "The $field could not be read, or is too large";
			return null;
		}
		$v = json_decode($raw, true);
		if (!is_array($v)) {
			$err = "The $field was not understood";
			return null;
		}
		return $v;
	}


	/* -------------------------------------------- */
	/* ------- IUValidateManifest ----------------- */
	/* -------------------------------------------- */
	/* check the manifest from the browser and return it normalized, with 'counts' and 'modalities' added.
	   Returns null and sets $err if anything is wrong, since the server imports exactly what it describes */
	function IUValidateManifest($m, &$err) {
		$err = '';
		if (!is_array($m) || (($m['manifest_version'] ?? 0) != 1) || !isset($m['subjects']) || !is_array($m['subjects'])) { $err = "The import list was not understood"; return null; }
		if (count($m['subjects']) == 0) { $err = "There is nothing to import"; return null; }
		if (count($m['subjects']) > IU_MAXSUBJECTS) { $err = "Too many subjects in one import (the limit is " . number_format(IU_MAXSUBJECTS) . ")"; return null; }

		$modlist = array();
		foreach (GetModalities() as $mod) $modlist[strtoupper($mod['code'])] = $mod['code'];

		$seen = array();
		$subjectids = array();
		$counts = array('subjects' => 0, 'studies' => 0, 'series' => 0, 'files' => 0, 'paths' => 0, 'size' => 0);
		$mods = array();
		$out = array('manifest_version' => 1, 'subjects' => array());
		foreach ($m['subjects'] as $s) {
			$id = trim((string)($s['id'] ?? ''));
			if (($id == '') || (strlen($id) > 250)) { $err = "A subject ID is empty or longer than 250 characters"; return null; }
			if (isset($subjectids[strtolower($id)])) { $err = "Subject [$id] is listed twice"; return null; }
			$subjectids[strtolower($id)] = true;
			if (!isset($s['studies']) || !is_array($s['studies']) || (count($s['studies']) == 0)) { $err = "Subject [$id] has no studies"; return null; }

			$studies = array();
			foreach ($s['studies'] as $st) {
				$modality = $modlist[strtoupper((string)($st['modality'] ?? ''))] ?? '';
				if ($modality == '') { $err = "A study for subject [$id] has a modality NiDB doesn't have [" . substr((string)($st['modality'] ?? ''), 0, 30) . "]"; return null; }
				$visit = trim((string)($st['visit'] ?? ''));
				if (strlen($visit) > 255) { $err = "A visit label for subject [$id] is longer than 255 characters"; return null; }
				$datesource = (string)($st['datesource'] ?? '');
				if (!in_array($datesource, array('path', 'mtime', 'user', 'header'), true)) { $err = "A study for subject [$id] has no date. Enter one on the Details step"; return null; }
				$date = (string)($st['date'] ?? '');
				if ($datesource == 'header') $date = '';
				elseif (!preg_match('/^(\d{4})-(\d\d)-(\d\d)$/', $date, $d) || !checkdate((int)$d[2], (int)$d[3], (int)$d[1])) { $err = "A study for subject [$id] has an invalid date [" . substr($date, 0, 20) . "]"; return null; }
				if (!isset($st['series']) || !is_array($st['series']) || (count($st['series']) == 0)) { $err = "A study for subject [$id] has no series"; return null; }

				$series = array();
				foreach ($st['series'] as $se) {
					$num = $se['num'] ?? null;
					if (!is_int($num) || ($num < 1) || ($num > 2147483647)) { $err = "A series for subject [$id] has an invalid series number"; return null; }
					$numsource = (($se['numsource'] ?? '') == 'path') ? 'path' : 'order';
					$desc = trim((string)($se['desc'] ?? ''));
					if (strlen($desc) > 250) $desc = substr($desc, 0, 250);
					$semod = $modlist[strtoupper((string)($se['modality'] ?? ''))] ?? '';
					if ($semod != $modality) { $err = "A series for subject [$id] has a different modality than its study"; return null; }
					$filetype = substr(trim((string)($se['filetype'] ?? '')), 0, 255);
					if (!isset($se['paths']) || !is_array($se['paths']) || (count($se['paths']) == 0)) { $err = "A series for subject [$id] has no files"; return null; }

					$paths = array();
					$nfiles = 0;
					$size = 0;
					foreach ($se['paths'] as $p) {
						if (!is_array($p) || (count($p) < 4)) { $err = "The file list was not understood"; return null; }
						$path = $p[0];
						if (!IIValidRelPath($path)) { $err = "The path [" . substr((string)$path, 0, 200) . "] can't be imported. Paths must be inside the chosen folder, and can't include hidden folders or files (names starting with '.')"; return null; }
						if (isset($seen[$path])) { $err = "The file [" . substr($path, 0, 200) . "] is in more than one series"; return null; }
						$seen[$path] = true;
						$n = max(1, (int)$p[1]);
						$b = max(0, (int)$p[2]);
						$bundle = ((int)$p[3] == 1) ? 1 : 0;
						$paths[] = array($path, $n, $b, $bundle);
						$nfiles += $n;
						$size += $b;
						if (++$counts['paths'] > IU_MAXPATHS) { $err = "Too many files in one import (the limit is " . number_format(IU_MAXPATHS) . ")"; return null; }
					}
					$series[] = array('num' => $num, 'numsource' => $numsource, 'desc' => $desc, 'modality' => $modality, 'filetype' => $filetype, 'files' => $nfiles, 'size' => $size, 'paths' => $paths);
					$counts['series']++;
					$counts['files'] += $nfiles;
					$counts['size'] += $size;
				}
				$studies[] = array('modality' => $modality, 'visit' => $visit, 'date' => $date, 'datesource' => $datesource, 'series' => $series);
				$counts['studies']++;
				$mods[$modality] = true;
			}
			$out['subjects'][] = array('id' => $id, 'studies' => $studies);
			$counts['subjects']++;
		}
		$out['counts'] = $counts;
		$out['modalities'] = array_keys($mods);
		return $out;
	}


	/* -------------------------------------------- */
	/* ------- IUValidateDecisions ---------------- */
	/* -------------------------------------------- */
	/* the decision log rows from the browser (D8), with unknown steps/sources dropped and values trimmed to fit */
	function IUValidateDecisions($list) {
		$steps = array('inspect','detect','match','axes','mapping','details','phiwarning','upload','parse','review','archive','recipe','outcome');
		$out = array();
		if (!is_array($list)) return $out;
		foreach ($list as $d) {
			if (count($out) >= IU_MAXDECISIONS) break;
			if (!is_array($d) || !in_array($d['step'] ?? '', $steps, true) || !in_array($d['source'] ?? '', array('nidb', 'user'), true)) continue;
			$str = function($v, $max) { if ($v === null) return null; if (!is_string($v)) $v = json_encode($v); return substr($v, 0, $max); };
			$out[] = array($d['step'], $d['source'], $str($d['target'] ?? null, 255), $str($d['old'] ?? null, 65535), $str($d['new'] ?? null, 65535), isset($d['detail']) ? json_encode($d['detail']) : null);
		}
		return $out;
	}


	/* -------------------------------------------- */
	/* ------- IUAddDecisions --------------------- */
	/* -------------------------------------------- */
	/* append rows ([step, source, target, old, new, detailjson]) to an upload's decision log */
	function IUAddDecisions($uploadid, $recipeid, $userid, $rows) {
		if (count($rows) == 0) return;
		$sqlstring = "insert into import_decisions (upload_id, recipe_id, user_id, decision_date, decision_step, decision_source, decision_target, decision_old, decision_new, decision_detail) values (?, ?, ?, now(), ?, ?, ?, ?, ?, ?)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		foreach ($rows as $r) {
			list($step, $source, $target, $old, $new, $detail) = $r;
			$uid = ($source == 'user') ? $userid : null;
			mysqli_stmt_bind_param($stmt, 'iiissssss', $uploadid, $recipeid, $uid, $step, $source, $target, $old, $new, $detail);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($uploadid, $recipeid, $uid, $step, $source, $target));
		}
		mysqli_stmt_close($stmt);
	}


	/* -------------------------------------------- */
	/* ------- IUAppendLog ------------------------ */
	/* -------------------------------------------- */
	function IUAppendLog($uploadid, $msg) {
		$msg = "importwizard.php  $msg";
		$sqlstring = "insert into upload_logs (upload_id, log_date, log_msg) values (?, now(), ?)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'is', $uploadid, $msg);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($uploadid, $msg));
		mysqli_stmt_close($stmt);
	}


	/* -------------------------------------------- */
	/* ------- IUManifestDir ---------------------- */
	/* -------------------------------------------- */
	/* where manifests are kept until moduleUpload has loaded them, or '' if [uploaddir] isn't set */
	function IUManifestDir() {
		$dir = rtrim(trim($GLOBALS['cfg']['uploaddir'] ?? ''), '/');
		return ($dir == '') ? '' : "$dir/universal";
	}


	/* -------------------------------------------- */
	/* ------- IUCreateUpload --------------------- */
	/* -------------------------------------------- */
	/* the 'createupload' action: save the recipe version, the upload, its manifest, and the decision log.
	   Local data then goes to tusupload.php (context = upload_id); NFS data is queued for moduleUpload now */
	function IUCreateUpload() {
		if (!VerifyCSRFToken(false))
			InspectRespond(false, "This request could not be verified. Your session may have expired; reload the page and try again", 403);
		session_write_close();
		$userid = (int)($_SESSION['userid'] ?? 0);

		$projectid = (int)($_POST['projectid'] ?? 0);
		$perms = GetCurrentUserProjectPermissions(array($projectid));
		if (($projectid < 1) || !GetPerm($perms, 'modifydata', $projectid))
			InspectRespond(false, "You need Edit Data permission on the destination project to import into it", 403);

		$manifestdir = IUManifestDir();
		if ($manifestdir == '')
			InspectRespond(false, "NiDB configuration error: [uploaddir] is not set. Contact the NiDB administrator", 500);

		$source = (string)($_POST['source'] ?? '');
		$root = '';
		$err = '';
		if ($source == 'nfs') {
			$root = IIResolveNfsPath((string)($_POST['nfspath'] ?? ''), $err);
			if ($root == '') InspectRespond(false, $err, 400);
		}
		elseif (($source != 'localfiles') && ($source != 'localfolder'))
			InspectRespond(false, "Unknown data location", 400);

		$manifest = IUValidateManifest(IUReadUploadedJson('manifest', IU_MAXMANIFESTBYTES, $err), $err);
		if ($manifest === null) InspectRespond(false, $err, 400);
		$meta = IUReadUploadedJson('meta', IU_MAXPROFILEBYTES + IU_MAXRECIPEBYTES, $err);
		if ($meta === null) InspectRespond(false, $err, 400);
		$recipe = $meta['recipe'] ?? null;
		$recipejson = is_array($recipe) ? json_encode($recipe, JSON_UNESCAPED_SLASHES) : '';
		if (($recipejson == '') || (strlen($recipejson) > IU_MAXRECIPEBYTES))
			InspectRespond(false, "The mapping recipe was missing or too large", 400);
		$profilejson = isset($meta['profile']) ? json_encode($meta['profile'], JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) : null;
		if (($profilejson !== null) && (strlen($profilejson) > IU_MAXPROFILEBYTES)) $profilejson = null;
		$decisions = IUValidateDecisions($meta['decisions'] ?? array());
		$autoarchive = (($_POST['autoarchive'] ?? '') == '1') ? 1 : 0;

		/* the recipe version this upload uses (D4). Unnamed, and the first of its own family */
		$axes = is_array($recipe['axes'] ?? null) ? $recipe['axes'] : array();
		$rmod = substr(implode(',', $manifest['modalities']), 0, 255);
		$rtype = substr(implode(',', array_map('strval', is_array($axes['filetype'] ?? null) ? $axes['filetype'] : array())), 0, 255);
		$rsub = substr(implode(',', array_map('strval', is_array($axes['subtypes'] ?? null) ? $axes['subtypes'] : array())), 0, 255);
		$rlayout = substr((string)($axes['layout'] ?? ''), 0, 50);
		$hash = hash('sha256', $recipejson);
		$sqlstring = "insert into import_recipes (recipe_scope, user_id, project_id, recipe_modality, recipe_filetype, recipe_filesubtypes, recipe_layout, recipe_json, recipe_version, recipe_iscurrent, recipe_hash, recipe_createdby, recipe_createdate, recipe_lastused, recipe_usecount, recipe_enabled) values ('user', ?, ?, ?, ?, ?, ?, ?, 1, 0, ?, ?, now(), now(), 1, 1)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'iissssssi', $userid, $projectid, $rmod, $rtype, $rsub, $rlayout, $recipejson, $hash, $userid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($userid, $projectid, $rmod, $rtype, $rsub, $rlayout, '(recipe json)', $hash, $userid));
		$recipeid = (int)mysqli_insert_id($GLOBALS['linki']);
		mysqli_stmt_close($stmt);
		if ($recipeid < 1) InspectRespond(false, "Unable to save the mapping recipe", 500);
		IIBoundQuery("update import_recipes set recipe_familyid = ? where recipe_id = ?", array($recipeid, $recipeid), __LINE__);

		/* the upload. It starts as 'uploading' so moduleUpload can't pick it up before the manifest is
		   written; NFS data is then ready straight away, and local data waits for the browser */
		$status = 'uploading';
		$uploadsource = ($source == 'nfs') ? 'nfs' : 'web';
		$sqlstring = "insert into uploads (upload_startdate, upload_enddate, upload_status, upload_source, upload_type, upload_datapath, upload_destprojectid, upload_modality, upload_subjectcriteria, upload_studycriteria, upload_seriescriteria, upload_patientid, upload_bidsflags, upload_recipeid, upload_profile, upload_autoarchive) values (now(), if(? = 'uploadcomplete', now(), null), ?, ?, 'universal', ?, ?, ?, '', '', '', '', '', ?, ?, ?)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ssssisisi', $status, $status, $uploadsource, $root, $projectid, $rmod, $recipeid, $profilejson, $autoarchive);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($status, $status, $uploadsource, $root, $projectid, $rmod, $recipeid, '(profile json)', $autoarchive));
		$uploadid = (int)mysqli_insert_id($GLOBALS['linki']);
		mysqli_stmt_close($stmt);
		if ($uploadid < 1) InspectRespond(false, "Unable to create the import", 500);

		/* the manifest, for moduleUpload. Written before an NFS upload can be picked up, so the
		   upload is created as 'uploadcomplete' only after this succeeds (see below) */
		$counts = $manifest['counts'];
		unset($manifest['counts'], $manifest['modalities']);
		$manifest['upload_id'] = $uploadid;
		$manifestpath = "$manifestdir/$uploadid.json";
		if ((!is_dir($manifestdir) && !@mkdir($manifestdir, 0770, true)) || (@file_put_contents($manifestpath, json_encode($manifest, JSON_UNESCAPED_SLASHES)) === false)) {
			IIBoundQuery("update uploads set upload_status = 'uploaderror' where upload_id = ?", array($uploadid), __LINE__);
			IUAppendLog($uploadid, "Unable to write the manifest [$manifestpath]");
			InspectRespond(false, "Unable to save the import list on the server. Contact the NiDB administrator", 500);
		}
		$datapath = $root;
		if ($source != 'nfs') {
			$datapath = rtrim($GLOBALS['cfg']['uploaddir'], '/') . "/" . date("YmdHisv") . "_$uploadid";
			if (!TusInitDir($datapath, $userid)) {
				IIBoundQuery("update uploads set upload_status = 'uploaderror' where upload_id = ?", array($uploadid), __LINE__);
				IUAppendLog($uploadid, "Unable to create the upload directory [$datapath]");
				InspectRespond(false, "Unable to create the upload directory on the server. Contact the NiDB administrator", 500);
			}
		}
		IIBoundQuery("update uploads set upload_manifestpath = ?, upload_datapath = ? where upload_id = ?", array($manifestpath, $datapath, $uploadid), __LINE__);
		if ($source == 'nfs')
			IIBoundQuery("update uploads set upload_status = 'uploadcomplete', upload_enddate = now() where upload_id = ?", array($uploadid), __LINE__);

		IUAppendLog($uploadid, "Import created by the import wizard from IP address [" . ($_SERVER['REMOTE_ADDR'] ?? '') . "]. Recipe version [$recipeid]. " . number_format($counts['subjects']) . " subjects, " . number_format($counts['studies']) . " studies, " . number_format($counts['series']) . " series, " . number_format($counts['files']) . " files, " . number_format($counts['size']) . " bytes");
		IUAppendLog($uploadid, ($source == 'nfs') ? "Importing from NFS path [$root]" : "Waiting for files from the browser. Upload directory is [$datapath]");

		$decisions[] = array('upload', 'nidb', 'upload', null, $uploadsource, json_encode(array('counts' => $counts, 'autoarchive' => $autoarchive, 'recipeid' => $recipeid)));
		IUAddDecisions($uploadid, $recipeid, $userid, $decisions);

		InspectRespond(true, "", 200, array('uploadid' => $uploadid, 'recipeid' => $recipeid, 'counts' => $counts));
	}


	/* -------------------------------------------- */
	/* ------- IUGetUpload ------------------------ */
	/* -------------------------------------------- */
	/* a wizard upload the current user may work on (Edit Data on its project), or null */
	function IUGetUpload($uploadid) {
		$result = IIBoundQuery("select * from uploads where upload_id = ? and upload_type = 'universal'", array((int)$uploadid), __LINE__);
		$row = ($result) ? mysqli_fetch_array($result, MYSQLI_ASSOC) : null;
		if (!$row) return null;
		$projectid = (int)$row['upload_destprojectid'];
		$perms = GetCurrentUserProjectPermissions(array($projectid));
		if (!GetPerm($perms, 'modifydata', $projectid)) return null;
		return $row;
	}


	/* -------------------------------------------- */
	/* ------- IUCompleteUpload ------------------- */
	/* -------------------------------------------- */
	/* the 'completeupload' action: every file in the manifest has arrived, so hand the upload to moduleUpload */
	function IUCompleteUpload() {
		if (!VerifyCSRFToken(false))
			InspectRespond(false, "This request could not be verified. Your session may have expired; reload the page and try again", 403);
		session_write_close();
		$userid = (int)($_SESSION['userid'] ?? 0);

		$uploadid = (int)($_POST['uploadid'] ?? 0);
		$up = IUGetUpload($uploadid);
		if (!$up) InspectRespond(false, "Import [$uploadid] was not found, or you don't have permission to change it", 404);
		if (($up['upload_status'] != 'uploading') || ($up['upload_source'] != 'web'))
			InspectRespond(false, "Import [$uploadid] is not receiving files. Its status is [" . $up['upload_status'] . "]", 409);
		$datapath = $up['upload_datapath'];
		if (TusReadOwner($datapath) !== $userid)
			InspectRespond(false, "Only the user who started this import can finish it", 403);

		list($complete, $incomplete) = TusGetFileStates($datapath, "tusupload.php", $uploadid);
		if (count($incomplete) > 0)
			InspectRespond(false, number_format(count($incomplete)) . " files have not finished uploading", 409);

		/* everything the manifest lists must be here */
		$manifest = json_decode((string)@file_get_contents($up['upload_manifestpath']), true);
		if (!is_array($manifest)) InspectRespond(false, "The import list for this upload is missing on the server", 500);
		$missing = array();
		$nmissing = 0;
		$total = 0;
		foreach ($manifest['subjects'] as $s)
			foreach ($s['studies'] as $st)
				foreach ($st['series'] as $se)
					foreach ($se['paths'] as $p) {
						$total++;
						$ok = $p[3] ? is_dir("$datapath/" . $p[0]) : is_file("$datapath/" . $p[0]);
						if (!$ok) { $nmissing++; if (count($missing) < 5) $missing[] = $p[0]; }
					}
		if ($nmissing > 0)
			InspectRespond(false, number_format($nmissing) . " of " . number_format($total) . " files haven't arrived on the server, for example: " . implode(", ", $missing), 409);

		TusRemoveState($datapath);
		IIBoundQuery("update uploads set upload_status = 'uploadcomplete', upload_enddate = now() where upload_id = ? and upload_status = 'uploading'", array($uploadid), __LINE__);
		IUAppendLog($uploadid, "Upload complete. Received all " . number_format($total) . " files and folders in the import list");
		IUAddDecisions($uploadid, (int)$up['upload_recipeid'], $userid, array(array('upload', 'nidb', 'upload', 'uploading', 'uploadcomplete', json_encode(array('paths' => $total)))));
		InspectRespond(true, "", 200, array('uploadid' => $uploadid));
	}


	/* -------------------------------------------- */
	/* ------- IUUploadStatus --------------------- */
	/* -------------------------------------------- */
	/* the 'uploadstatus' action: where an upload is, for the wizard's last step to follow it */
	function IUUploadStatus() {
		if (!VerifyCSRFToken(false))
			InspectRespond(false, "This request could not be verified. Your session may have expired; reload the page and try again", 403);
		session_write_close();
		$uploadid = (int)($_POST['uploadid'] ?? 0);
		$up = IUGetUpload($uploadid);
		if (!$up) InspectRespond(false, "Import [$uploadid] was not found", 404);

		$out = array('status' => $up['upload_status'], 'percent' => ($up['upload_statuspercent'] === null) ? null : (float)$up['upload_statuspercent'], 'series' => array(), 'log' => array());
		$result = IIBoundQuery("select a.uploadseries_status 'status', (a.uploadseries_problem is not null and a.uploadseries_problem <> '') 'problem', count(*) 'n' from upload_series a join upload_studies b on a.uploadstudy_id = b.uploadstudy_id join upload_subjects c on b.uploadsubject_id = c.uploadsubject_id where c.upload_id = ? group by 1, 2", array($uploadid), __LINE__);
		while ($result && ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)))
			$out['series'][] = array('status' => $row['status'], 'problem' => (int)$row['problem'], 'count' => (int)$row['n']);
		$result = IIBoundQuery("select log_date, log_msg from upload_logs where upload_id = ? order by uploadlog_id desc limit 5", array($uploadid), __LINE__);
		while ($result && ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)))
			$out['log'][] = array($row['log_date'], substr($row['log_msg'], 0, 500));
		InspectRespond(true, "", 200, $out);
	}


	/* ------------------------------------ review ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- IUArchiveSelected ------------------ */
	/* -------------------------------------------- */
	/* POST from the review page: mark the chosen series for import, the rest ignored, and queue the upload
	   for archiving. Returns a message for the flash */
	function IUArchiveSelected($uploadid, $seriesids) {
		$up = IUGetUpload($uploadid);
		if (!$up) return array('error', "Import [$uploadid] was not found, or you don't have permission to change it");
		if ($up['upload_status'] != 'parsingcomplete') return array('error', "Import [$uploadid] can't be archived now. Its status is [" . $up['upload_status'] . "]");

		$ids = array();
		foreach ((is_array($seriesids) ? $seriesids : array()) as $id) if ((int)$id > 0) $ids[(int)$id] = true;
		if (count($ids) == 0) return array('error', "Choose at least one series to archive");

		/* only this upload's series, and none with a problem the archive step can't handle */
		$valid = array();
		$result = IIBoundQuery("select a.uploadseries_id, a.uploadseries_problem from upload_series a join upload_studies b on a.uploadstudy_id = b.uploadstudy_id join upload_subjects c on b.uploadsubject_id = c.uploadsubject_id where c.upload_id = ?", array($uploadid), __LINE__);
		while ($result && ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)))
			$valid[(int)$row['uploadseries_id']] = (string)$row['uploadseries_problem'];
		$chosen = 0;
		foreach ($valid as $sid => $problem) {
			$import = isset($ids[$sid]) && ($problem == '');
			if ($import) $chosen++;
			IIBoundQuery("update upload_series set uploadseries_status = ? where uploadseries_id = ?", array($import ? 'import' : 'ignore', $sid), __LINE__);
		}
		if ($chosen == 0) return array('error', "None of the chosen series can be archived");

		IIBoundQuery("update uploads set upload_status = 'queueforarchive' where upload_id = ? and upload_status = 'parsingcomplete'", array($uploadid), __LINE__);
		IUAppendLog($uploadid, "Queued for archiving after review: " . number_format($chosen) . " of " . number_format(count($valid)) . " series");
		IUAddDecisions($uploadid, (int)$up['upload_recipeid'], (int)($_SESSION['userid'] ?? 0), array(array('review', 'user', 'series', (string)count($valid), (string)$chosen, null)));
		return array('success', number_format($chosen) . " series queued for archiving");
	}


	/* -------------------------------------------- */
	/* ------- IUDisplayReview -------------------- */
	/* -------------------------------------------- */
	/* what moduleUpload found for a wizard upload: subjects, studies, and series with what they match in
	   NiDB and anything that needs attention. While the upload is 'parsingcomplete' the user chooses the
	   series to archive */
	function IUDisplayReview($uploadid) {
		$up = IUGetUpload($uploadid);
		if (!$up) {
			Error("Import [" . (int)$uploadid . "] was not found, or you don't have permission to view it");
			return;
		}
		$status = $up['upload_status'];
		$projectid = (int)$up['upload_destprojectid'];
		$canarchive = ($status == 'parsingcomplete');

		$subjects = array();
		$result = IIBoundQuery("select a.uploadsubject_id, a.uploadsubject_patientid, a.matchingsubjectid, s.uid, e.enrollment_id from upload_subjects a left join subjects s on a.matchingsubjectid = s.subject_id left join enrollment e on e.subject_id = a.matchingsubjectid and e.project_id = ? where a.upload_id = ? order by a.uploadsubject_patientid", array($projectid, $uploadid), __LINE__);
		while ($result && ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)))
			$subjects[(int)$row['uploadsubject_id']] = array('row' => $row, 'studies' => array());
		$studies = array();
		$result = IIBoundQuery("select b.*, st.study_num from upload_studies b join upload_subjects a on b.uploadsubject_id = a.uploadsubject_id left join studies st on b.matchingstudyid = st.study_id where a.upload_id = ? order by b.uploadstudy_date, b.uploadstudy_visit", array($uploadid), __LINE__);
		while ($result && ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))) {
			$studies[(int)$row['uploadstudy_id']] = array('row' => $row, 'series' => array());
		}
		$result = IIBoundQuery("select c.uploadseries_id, c.uploadstudy_id, c.uploadseries_status, c.uploadseries_desc, c.uploadseries_num, c.uploadseries_numfiles, c.uploadseries_size, c.uploadseries_modality, c.uploadseries_filetype, c.uploadseries_problem from upload_series c join upload_studies b on c.uploadstudy_id = b.uploadstudy_id join upload_subjects a on b.uploadsubject_id = a.uploadsubject_id where a.upload_id = ? order by c.uploadseries_num", array($uploadid), __LINE__);
		$nseries = 0;
		$nproblem = 0;
		while ($result && ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))) {
			$sid = (int)$row['uploadstudy_id'];
			if (!isset($studies[$sid])) continue;
			$studies[$sid]['series'][] = $row;
			$nseries++;
			if ((string)$row['uploadseries_problem'] != '') $nproblem++;
		}
		foreach ($studies as $sid => $st) {
			$subid = (int)$st['row']['uploadsubject_id'];
			if (isset($subjects[$subid])) $subjects[$subid]['studies'][] = $st;
		}
		?>
		<div class="ui container">
			<h2 class="ui header">
				Import <?=(int)$uploadid?>
				<div class="sub header">From the import wizard &middot; status <b><?=htmlspecialchars($status)?></b><?=($up['upload_statuspercent'] !== null) ? " (" . number_format((float)$up['upload_statuspercent'], 0) . "%)" : ""?> &middot; <a href="importimaging.php">All imports</a></div>
			</h2>
			<? if (in_array($status, array('uploading', 'uploadcomplete', 'parsing'), true)) { ?>
				<div class="ui info message">The server is still receiving or checking the files. Reload this page to see the result.</div>
			<? } elseif ($status == 'parsingerror') { ?>
				<div class="ui error message">The server couldn't read this import. See the log on the <a href="importimaging.php">imports page</a>.</div>
			<? } elseif ($canarchive && ($nproblem > 0)) { ?>
				<div class="ui warning message"><?=number_format($nproblem)?> of <?=number_format($nseries)?> series need attention and can't be archived. The others can be archived now; fix the data and import the rest again.</div>
			<? } ?>

			<form method="post" action="importwizard.php">
				<input type="hidden" name="action" value="archive">
				<input type="hidden" name="uploadid" value="<?=(int)$uploadid?>">
				<?=CSRFTokenField()?>
				<table class="ui very compact small table">
					<thead><tr><th>In NiDB</th><th>Modality</th><th class="right aligned">Files</th><th class="right aligned">Size</th><th>Status</th></tr></thead>
					<tbody>
					<? foreach ($subjects as $subj) {
						$r = $subj['row'];
						if ($r['matchingsubjectid'] === null) $label = '<span class="ui tiny orange basic label">New subject</span>';
						elseif ($r['enrollment_id'] !== null) $label = '<span class="ui tiny green basic label">In this project</span> <span class="ui grey text">' . htmlspecialchars($r['uid']) . '</span>';
						else $label = '<span class="ui tiny blue basic label">In NiDB, will be enrolled</span>';
						?>
						<tr style="background: #f8f8f9; font-weight: bold"><td colspan="4"><?=htmlspecialchars($r['uploadsubject_patientid'])?></td><td><?=$label?></td></tr>
						<? foreach ($subj['studies'] as $st) {
							$b = $st['row'];
							$date = ($b['uploadstudy_date'] !== null) ? substr($b['uploadstudy_date'], 0, 10) : "date from headers";
							?>
							<tr><td style="padding-left: 1.5em"><b><?=htmlspecialchars($b['uploadstudy_modality'])?></b> study <?=htmlspecialchars($date)?><?=($b['uploadstudy_visit'] != '') ? ' &ldquo;' . htmlspecialchars($b['uploadstudy_visit']) . '&rdquo;' : ''?></td><td></td><td></td><td></td>
								<td><?=($b['matchingstudyid'] !== null) ? '<span class="ui tiny green basic label">Existing study ' . (int)$b['study_num'] . '</span>' : '<span class="ui tiny orange basic label">New study</span>'?></td></tr>
							<? foreach ($st['series'] as $se) {
								$problem = (string)$se['uploadseries_problem'];
								?>
								<tr>
									<td style="padding-left: 3em">
										<? if ($canarchive) { ?><input type="checkbox" name="uploadseriesid[]" value="<?=(int)$se['uploadseries_id']?>" <?=($problem == '') ? 'checked' : 'disabled'?>><? } ?>
										<?=(int)$se['uploadseries_num']?> <?=htmlspecialchars($se['uploadseries_desc'])?>
									</td>
									<td><?=htmlspecialchars($se['uploadseries_modality'])?> <span class="ui grey text"><?=htmlspecialchars($se['uploadseries_filetype'])?></span></td>
									<td class="right aligned"><?=number_format((int)$se['uploadseries_numfiles'])?></td>
									<td class="right aligned"><?=number_format((float)$se['uploadseries_size'] / 1048576, 1)?> MB</td>
									<td><?=($problem != '') ? '<span class="ui tiny red basic label">' . htmlspecialchars($problem) . '</span>' : htmlspecialchars($se['uploadseries_status'])?></td>
								</tr>
							<? } ?>
						<? } ?>
					<? } ?>
					</tbody>
				</table>
				<? if ($canarchive) { ?>
					<button class="ui primary button" type="submit"><i class="archive icon"></i> Archive the checked series</button>
				<? } ?>
			</form>
		</div>
		<?
	}
?>
