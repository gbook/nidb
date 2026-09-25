<?
 // ------------------------------------------------------------------------------
 // NiDB tus_functions.php
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

 /* Shared tus 1.0.0 resumable upload server (https://tus.io/protocols/resumable-upload), with the
    'creation' and 'termination' extensions, plus helpers for the pages that use it. See doc/tus.md

    Files are uploaded into a context: a directory chosen by the endpoint, holding a .tus
    subdirectory with the upload state
        .tus/owner          user_id of the user allowed to upload into this directory
        .tus/<hex>.json     one file's state: original filename, length, completion
        .tus/<hex>.part     partially received file data
    Completed files are moved to <directory>/<filename>. An upload URL is
    <endpoint>?id=<context>-<hex>

    An endpoint is a small page that loads the NiDB includes and calls TusServe() with its own
    rules for turning a context ID into a directory. See tusupload.php (imports, context is the
    upload_id) and tuscontext.php (general use, context is a random token under [tmpdir]/tus)

    Nothing in this file runs when it is included */

	if (!defined("LEGIT_REQUEST")) die ("This page cannot be called directly.");

	define("TUS_VERSION", "1.0.0");


	/* ============================================================================ */
	/* ------- Server ------------------------------------------------------------- */
	/* ============================================================================ */


	/* -------------------------------------------- */
	/* ------- TusServe --------------------------- */
	/* -------------------------------------------- */
	/* Handle one tus request and exit. $config keys:
	     endpoint        (required) URL of the endpoint page, relative to the upload page, e.g. "tusupload.php"
	     contextpattern  (required) anchored regex a context ID must match, e.g. '/^[0-9]+$/'. Must not allow '/'
	     resolve         (required) callable ($context, $userid, $failcode) returning the context's directory,
	                     or "" if the context does not exist or is not accepting files. May call TusRespond()
	                     itself to send a more specific error
	     log             (optional) callable ($context, $message), called when a file is created, completed, or discarded
	     checkdiskspace  (optional, default true) refuse a file larger than the free space in its directory
	     maxsize         (optional, default 0 = no limit) largest file size accepted, in bytes
	   The session must be started before calling; it is closed here, so a long request does not
	   block the user's other pages. Ownership (.tus/owner) is always checked, after resolve */
	function TusServe($config) {
		$config = array_merge(array('endpoint' => '', 'contextpattern' => '', 'resolve' => null, 'log' => null, 'checkdiskspace' => true, 'maxsize' => 0), $config);
		if (($config['endpoint'] == "") || ($config['contextpattern'] == "") || (!is_callable($config['resolve']))) {
			TusRespond(500, array(), "tus endpoint is misconfigured");
		}

		$userid = (int)($_SESSION['userid'] ?? 0);
		$validlogin = (($_SESSION['validlogin'] ?? '') == "true");
		if (session_status() === PHP_SESSION_ACTIVE)
			session_write_close();

		$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? '');
		if (($method == "POST") && (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])))
			$method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);

		if ($method == "OPTIONS") {
			$headers = array("Tus-Version" => TUS_VERSION, "Tus-Extension" => "creation,termination");
			if ($config['maxsize'] > 0)
				$headers["Tus-Max-Size"] = $config['maxsize'];
			TusRespond(204, $headers);
		}

		if (($_SERVER['HTTP_TUS_RESUMABLE'] ?? '') != TUS_VERSION) {
			TusRespond(412, array("Tus-Version" => TUS_VERSION), "Unsupported tus version");
		}

		if ((!$validlogin) || ($userid < 1)) {
			TusRespond(403, array(), "Not logged in. Your session may have expired, log in again and resume the upload");
		}

		switch ($method) {
			case 'POST': TusCreate($config, $userid); break;
			case 'HEAD': TusHead($config, $userid); break;
			case 'PATCH': TusPatch($config, $userid); break;
			case 'DELETE': TusDelete($config, $userid); break;
			default:
				TusRespond(405, array("Allow" => "OPTIONS, HEAD, POST, PATCH, DELETE"));
		}
	}


	/* -------------------------------------------- */
	/* ------- TusRespond ------------------------- */
	/* -------------------------------------------- */
	/* send the HTTP status, tus headers, and an optional plain text message, then exit */
	function TusRespond($code, $headers = array(), $body = "") {
		while (ob_get_level() > 0) ob_end_clean();

		http_response_code($code);
		header("Tus-Resumable: " . TUS_VERSION);
		header("Cache-Control: no-store");
		foreach ($headers as $name => $value)
			header("$name: $value");

		if ($body != "") {
			header("Content-Type: text/plain");
			echo $body;
		}
		exit(0);
	}


	/* -------------------------------------------- */
	/* ------- TusCreate -------------------------- */
	/* -------------------------------------------- */
	/* POST - create a new file in a context. Requires Upload-Length, and Upload-Metadata with
	   'context' and 'filename' */
	function TusCreate($config, $userid) {
		$length = $_SERVER['HTTP_UPLOAD_LENGTH'] ?? '';
		if (($length === '') || (!ctype_digit($length))) {
			TusRespond(400, array(), "Missing or invalid Upload-Length (deferred length is not supported)");
		}
		$length = (int)$length;
		if (($config['maxsize'] > 0) && ($length > $config['maxsize'])) {
			TusRespond(413, array(), "File is [" . number_format($length) . "] bytes, the largest file accepted is [" . number_format($config['maxsize']) . "] bytes");
		}

		$meta = TusParseMetadata($_SERVER['HTTP_UPLOAD_METADATA'] ?? '');
		$context = $meta['context'] ?? '';
		if (!preg_match($config['contextpattern'], $context)) {
			TusRespond(400, array(), "Missing or invalid upload context");
		}
		$filename = TusSanitizeFilename($meta['filename'] ?? '');

		$savepath = TusResolve($config, $context, $userid, 403);
		$tusdir = "$savepath/.tus";

		/* refuse up front rather than filling the disk halfway through */
		if ($config['checkdiskspace']) {
			$free = @disk_free_space($savepath);
			if (($free !== false) && ($length > $free)) {
				TusRespond(413, array(), "Not enough disk space on the server for this file. File is [" . number_format($length) . "] bytes, available space is [" . number_format($free) . "] bytes");
			}
		}

		$hex = bin2hex(random_bytes(16));
		$info = array('filename' => $filename, 'length' => $length, 'created' => date('c'), 'complete' => false, 'finalname' => '');
		if (!TusWriteInfo($tusdir, $hex, $info) || (@file_put_contents("$tusdir/$hex.part", "") === false)) {
			TusRespond(500, array(), "Unable to create upload file");
		}

		TusLog($config, $context, "Receiving file [$filename]. Size is [" . number_format($length) . "] bytes");

		/* a zero length file is complete as soon as it exists */
		if ($length == 0)
			TusCompleteFile($config, $context, $savepath, $hex, $info);

		TusRespond(201, array("Location" => TusFileURL($config['endpoint'], $context, $hex)));
	}


	/* -------------------------------------------- */
	/* ------- TusHead ---------------------------- */
	/* -------------------------------------------- */
	/* HEAD - report how many bytes of the file have been received, so the client can resume */
	function TusHead($config, $userid) {
		list($context, $hex) = TusParseKey($config);
		$savepath = TusResolve($config, $context, $userid, 410);
		$info = TusReadInfo("$savepath/.tus", $hex);

		TusRespond(200, array("Upload-Offset" => TusGetOffset($savepath, $hex, $info), "Upload-Length" => $info['length']));
	}


	/* -------------------------------------------- */
	/* ------- TusPatch --------------------------- */
	/* -------------------------------------------- */
	/* PATCH - append a chunk to the file at Upload-Offset */
	function TusPatch($config, $userid) {
		list($context, $hex) = TusParseKey($config);

		if (($_SERVER['CONTENT_TYPE'] ?? '') != "application/offset+octet-stream") {
			TusRespond(415, array(), "Content-Type must be application/offset+octet-stream");
		}
		$offset = $_SERVER['HTTP_UPLOAD_OFFSET'] ?? '';
		if (($offset === '') || (!ctype_digit($offset))) {
			TusRespond(400, array(), "Missing or invalid Upload-Offset");
		}
		$offset = (int)$offset;

		$savepath = TusResolve($config, $context, $userid, 410);
		$tusdir = "$savepath/.tus";
		$info = TusReadInfo($tusdir, $hex);
		if ($info['complete']) {
			TusRespond(409, array("Upload-Offset" => $info['length']), "File is already complete");
		}

		$partfile = "$tusdir/$hex.part";
		$fp = @fopen($partfile, "ab");
		if ($fp === false) {
			TusRespond(500, array(), "Unable to open the upload file");
		}
		/* only one PATCH per file at a time. 423 tells the client to retry later */
		if (!flock($fp, LOCK_EX | LOCK_NB)) {
			fclose($fp);
			TusRespond(423, array(), "File is locked by another request");
		}

		/* the size of the .part file is the authoritative offset */
		clearstatcache(true, $partfile);
		$current = filesize($partfile);
		if ($offset != $current) {
			flock($fp, LOCK_UN);
			fclose($fp);
			TusRespond(409, array("Upload-Offset" => $current), "Upload-Offset [$offset] does not match the received size [$current]");
		}

		/* keep whatever arrives, even if the client goes away mid-chunk. The next HEAD picks up from there */
		ignore_user_abort(true);
		set_time_limit(0);

		$in = fopen("php://input", "rb");
		$written = stream_copy_to_stream($in, $fp, $info['length'] - $current);
		fclose($in);
		fflush($fp);
		flock($fp, LOCK_UN);
		fclose($fp);

		if ($written === false) {
			TusRespond(500, array(), "Error writing the upload file");
		}

		$newoffset = $current + $written;
		if ($newoffset >= $info['length'])
			TusCompleteFile($config, $context, $savepath, $hex, $info);

		TusRespond(204, array("Upload-Offset" => $newoffset));
	}


	/* -------------------------------------------- */
	/* ------- TusDelete -------------------------- */
	/* -------------------------------------------- */
	/* DELETE - discard a file, whether it is partial or complete */
	function TusDelete($config, $userid) {
		list($context, $hex) = TusParseKey($config);
		$savepath = TusResolve($config, $context, $userid, 410);
		$tusdir = "$savepath/.tus";
		$info = TusReadInfo($tusdir, $hex);

		@unlink("$tusdir/$hex.part");
		if (($info['complete']) && ($info['finalname'] != ""))
			@unlink("$savepath/" . $info['finalname']);
		@unlink("$tusdir/$hex.json");

		TusLog($config, $context, "Discarded file [" . $info['filename'] . "]");

		TusRespond(204);
	}


	/* -------------------------------------------- */
	/* ------- TusCompleteFile -------------------- */
	/* -------------------------------------------- */
	/* move a fully received .part file into the context directory under its original name */
	function TusCompleteFile($config, $context, $savepath, $hex, $info) {
		$tusdir = "$savepath/.tus";

		/* serialize the name check and rename, so two files with the same name can't overwrite each other */
		$lock = fopen("$tusdir/finalize.lock", "c");
		flock($lock, LOCK_EX);

		$finalname = TusUniqueFilename($savepath, $info['filename']);
		$ok = rename("$tusdir/$hex.part", "$savepath/$finalname");
		if ($ok) {
			chmod("$savepath/$finalname", 0777);
			$info['complete'] = true;
			$info['finalname'] = $finalname;
			TusWriteInfo($tusdir, $hex, $info);
		}

		flock($lock, LOCK_UN);
		fclose($lock);

		if (!$ok) {
			TusLog($config, $context, "Error moving [$tusdir/$hex.part] to [$savepath/$finalname]");
			TusRespond(500, array(), "Unable to save the completed file");
		}

		if ($finalname != $info['filename'])
			TusLog($config, $context, "Received file [" . $info['filename'] . "], saved as [$finalname] because a file with that name already exists. Size is [" . number_format($info['length']) . "] bytes");
		else
			TusLog($config, $context, "Received file [$finalname]. Size is [" . number_format($info['length']) . "] bytes");
	}


	/* -------------------------------------------- */
	/* ------- TusResolve ------------------------- */
	/* -------------------------------------------- */
	/* get the context's directory from the endpoint's resolve callback, and check this user owns it.
	   Responds with $failcode (403 when creating, 410 when the client is resuming a file, which
	   tells it the upload is gone) if not */
	function TusResolve($config, $context, $userid, $failcode) {
		$savepath = call_user_func($config['resolve'], $context, $userid, $failcode);
		if ((!is_string($savepath)) || ($savepath == "")) {
			TusRespond($failcode, array(), "Upload not found, or no longer accepting files");
		}
		$savepath = rtrim($savepath, "/");

		if (TusReadOwner($savepath) !== (int)$userid) {
			TusRespond($failcode, array(), "Upload not found, or not created by this user");
		}

		return $savepath;
	}


	/* -------------------------------------------- */
	/* ------- TusParseKey ------------------------ */
	/* -------------------------------------------- */
	/* split <endpoint>?id=<context>-<hex> into array(context, hex) */
	function TusParseKey($config) {
		$id = $_GET['id'] ?? '';
		if ((!preg_match('/^(.+)-([0-9a-f]{32})$/', $id, $matches)) || (!preg_match($config['contextpattern'], $matches[1]))) {
			TusRespond(404, array(), "Invalid upload URL");
		}
		return array($matches[1], $matches[2]);
	}


	/* -------------------------------------------- */
	/* ------- TusParseMetadata ------------------- */
	/* -------------------------------------------- */
	/* Upload-Metadata is a comma separated list of '<key> <base64 value>' pairs */
	function TusParseMetadata($header) {
		$meta = array();
		foreach (explode(",", $header) as $pair) {
			$parts = explode(" ", trim($pair), 2);
			if ($parts[0] == "") continue;
			$meta[$parts[0]] = isset($parts[1]) ? (string)base64_decode($parts[1]) : "";
		}
		return $meta;
	}


	/* -------------------------------------------- */
	/* ------- TusReadInfo ------------------------ */
	/* -------------------------------------------- */
	function TusReadInfo($tusdir, $hex) {
		$json = @file_get_contents("$tusdir/$hex.json");
		$info = ($json === false) ? null : json_decode($json, true);
		if (!is_array($info)) {
			TusRespond(404, array(), "File not found");
		}
		return $info;
	}


	/* -------------------------------------------- */
	/* ------- TusWriteInfo ----------------------- */
	/* -------------------------------------------- */
	/* write to a temp file and rename, so a reader never sees a half written file */
	function TusWriteInfo($tusdir, $hex, $info) {
		$tmp = "$tusdir/$hex.json.tmp";
		if (@file_put_contents($tmp, json_encode($info)) === false)
			return false;
		return rename($tmp, "$tusdir/$hex.json");
	}


	/* -------------------------------------------- */
	/* ------- TusGetOffset ----------------------- */
	/* -------------------------------------------- */
	function TusGetOffset($savepath, $hex, $info) {
		if ($info['complete'])
			return $info['length'];

		$partfile = "$savepath/.tus/$hex.part";
		clearstatcache(true, $partfile);
		return file_exists($partfile) ? filesize($partfile) : 0;
	}


	/* -------------------------------------------- */
	/* ------- TusLog ----------------------------- */
	/* -------------------------------------------- */
	function TusLog($config, $context, $message) {
		if (is_callable($config['log']))
			call_user_func($config['log'], $context, $message);
	}


	/* -------------------------------------------- */
	/* ------- TusFileURL ------------------------- */
	/* -------------------------------------------- */
	function TusFileURL($endpoint, $context, $hex) {
		$separator = (strpos($endpoint, "?") === false) ? "?" : "&";
		return $endpoint . $separator . "id=" . urlencode("$context-$hex");
	}


	/* -------------------------------------------- */
	/* ------- TusSanitizeFilename ---------------- */
	/* -------------------------------------------- */
	/* reduce a client supplied filename to a safe name with no directory components */
	function TusSanitizeFilename($name) {
		$name = str_replace("\\", "/", $name);
		$name = basename($name);
		$name = preg_replace('/[\x00-\x1f\x7f]/', '', $name);
		$name = trim($name);
		/* no hidden files, which also keeps uploads from clobbering the .tus directory */
		$name = ltrim($name, ".");
		if (strlen($name) > 200)
			$name = substr($name, -200);
		if ($name == "")
			$name = "file";
		return $name;
	}


	/* -------------------------------------------- */
	/* ------- TusUniqueFilename ------------------ */
	/* -------------------------------------------- */
	/* returns $name, or name_1.ext, name_2.ext... if that already exists in $dir */
	function TusUniqueFilename($dir, $name) {
		if (!file_exists("$dir/$name"))
			return $name;

		$pi = pathinfo($name);
		$ext = (isset($pi['extension']) && ($pi['extension'] != "")) ? "." . $pi['extension'] : "";
		$base = $pi['filename'];
		for ($i = 1; ; $i++) {
			$candidate = $base . "_$i" . $ext;
			if (!file_exists("$dir/$candidate"))
				return $candidate;
		}
	}


	/* ============================================================================ */
	/* ------- Page helpers: any directory ---------------------------------------- */
	/* ============================================================================ */


	/* -------------------------------------------- */
	/* ------- TusInitDir ------------------------- */
	/* -------------------------------------------- */
	/* make $savepath (if needed) and its .tus state directory, owned by $userid. Returns true on success */
	function TusInitDir($savepath, $userid) {
		if ((!is_dir("$savepath/.tus")) && (!@mkdir("$savepath/.tus", 0777, true)))
			return false;
		@chmod($savepath, 0777);
		return (@file_put_contents("$savepath/.tus/owner", (int)$userid) !== false);
	}


	/* -------------------------------------------- */
	/* ------- TusReadOwner ----------------------- */
	/* -------------------------------------------- */
	/* returns the user_id that owns $savepath, or null if it has no tus state */
	function TusReadOwner($savepath) {
		$owner = @file_get_contents("$savepath/.tus/owner");
		if ($owner === false)
			return null;
		return (int)trim($owner);
	}


	/* -------------------------------------------- */
	/* ------- TusGetFileStates ------------------- */
	/* -------------------------------------------- */
	/* state of every file started in $savepath. Returns array(complete files, incomplete files), each
	   a list of array(filename, length, offset, url). url is the file's tus upload URL, which a client
	   can pass as uploadUrl to resume it, or DELETE to discard it. Complete files also have finalname
	   (the name saved in $savepath) and received (unix time the last byte was written, or null) */
	function TusGetFileStates($savepath, $endpoint, $context) {
		$complete = array();
		$incomplete = array();

		$tusdir = "$savepath/.tus";
		$entries = @scandir($tusdir);
		if ($entries === false) $entries = array();
		foreach ($entries as $entry) {
			if (!preg_match('/^([0-9a-f]{32})\.json$/', $entry, $matches)) continue;
			$hex = $matches[1];
			$info = json_decode((string)@file_get_contents("$tusdir/$entry"), true);
			if (!is_array($info)) continue;

			$file = array('filename' => $info['filename'], 'length' => (int)$info['length'], 'url' => TusFileURL($endpoint, $context, $hex));
			if ($info['complete']) {
				$file['offset'] = (int)$info['length'];
				$file['finalname'] = $info['finalname'];
				$finalfile = "$savepath/" . $info['finalname'];
				$file['received'] = (($info['finalname'] != "") && file_exists($finalfile)) ? filemtime($finalfile) : null;
				$complete[] = $file;
			}
			else {
				$partfile = "$tusdir/$hex.part";
				$file['offset'] = file_exists($partfile) ? filesize($partfile) : 0;
				$incomplete[] = $file;
			}
		}

		return array($complete, $incomplete);
	}


	/* -------------------------------------------- */
	/* ------- TusListFiles ----------------------- */
	/* -------------------------------------------- */
	/* names of the completed files in $savepath (everything except hidden entries such as .tus) */
	function TusListFiles($savepath) {
		$files = array();
		$entries = @scandir($savepath);
		if ($entries === false) $entries = array();
		foreach ($entries as $entry) {
			if (substr($entry, 0, 1) == ".") continue;
			if (is_file("$savepath/$entry"))
				$files[] = $entry;
		}
		return $files;
	}


	/* -------------------------------------------- */
	/* ------- TusRemoveState --------------------- */
	/* -------------------------------------------- */
	/* delete the .tus directory, leaving only the uploaded files. Any unfinished files are lost, and
	   the directory no longer accepts uploads */
	function TusRemoveState($savepath) {
		return TusDeleteTree("$savepath/.tus");
	}


	/* -------------------------------------------- */
	/* ------- TusDeleteTree ---------------------- */
	/* -------------------------------------------- */
	/* recursively delete $path. Symlinks are removed, not followed */
	function TusDeleteTree($path) {
		if (is_link($path) || (!is_dir($path)))
			return @unlink($path);
		foreach (scandir($path) as $entry) {
			if (($entry != ".") && ($entry != ".."))
				TusDeleteTree("$path/$entry");
		}
		return @rmdir($path);
	}


	/* ============================================================================ */
	/* ------- Page helpers: general upload contexts (tuscontext.php) ------------- */
	/* ============================================================================ */
	/* A context is a directory [tmpdir]/tus/<token>, where token is 32 random hex characters.
	   A page creates one with TusCreateContext(), points tus-js-client at tuscontext.php with
	   metadata { context: token }, then collects the files with TusGetContextFiles() and removes the
	   context with TusDeleteContext() */


	/* -------------------------------------------- */
	/* ------- TusContextPath --------------------- */
	/* -------------------------------------------- */
	/* directory of context $token, or "" if the token is not well formed. Does not check the owner */
	function TusContextPath($token) {
		if ((!is_string($token)) || (!preg_match('/^[0-9a-f]{32}$/', $token)) || ($GLOBALS['cfg']['tmpdir'] == ""))
			return "";
		return $GLOBALS['cfg']['tmpdir'] . "/tus/$token";
	}


	/* -------------------------------------------- */
	/* ------- TusCreateContext ------------------- */
	/* -------------------------------------------- */
	/* create a new context owned by $userid. Returns its token, or "" on error */
	function TusCreateContext($userid) {
		$token = bin2hex(random_bytes(16));
		$savepath = TusContextPath($token);
		if (($savepath == "") || (!TusInitDir($savepath, $userid)))
			return "";
		return $token;
	}


	/* -------------------------------------------- */
	/* ------- TusGetContextFiles ----------------- */
	/* -------------------------------------------- */
	/* full paths of the completed files in context $token. Returns null if the context does not
	   exist, is not owned by $userid, or still has unfinished files (unless $allowincomplete) */
	function TusGetContextFiles($token, $userid, $allowincomplete = false) {
		$savepath = TusContextPath($token);
		if (($savepath == "") || (TusReadOwner($savepath) !== (int)$userid))
			return null;

		if (!$allowincomplete) {
			list($complete, $incomplete) = TusGetFileStates($savepath, "tuscontext.php", $token);
			if (count($incomplete) > 0)
				return null;
		}

		$files = array();
		foreach (TusListFiles($savepath) as $name)
			$files[] = "$savepath/$name";
		return $files;
	}


	/* -------------------------------------------- */
	/* ------- TusDeleteContext ------------------- */
	/* -------------------------------------------- */
	/* delete context $token and all of its files. Returns false if it is not owned by $userid */
	function TusDeleteContext($token, $userid) {
		$savepath = TusContextPath($token);
		if (($savepath == "") || (TusReadOwner($savepath) !== (int)$userid))
			return false;
		return TusDeleteTree($savepath);
	}
?>
