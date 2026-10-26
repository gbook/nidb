<?
 // ------------------------------------------------------------------------------
 // NiDB getfile.php
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

require "functions.php";
require "includes_php.php";

$action = GetVariable("action");
$file   = GetVariable("file");
$fileid = (int)GetVariable("fileid");
$exportid = (int)GetVariable("exportid");

/* ── serve the .sqrl file created by a package export ── */
if ($exportid > 0) {
	/* the user must own (or be an admin for) the package that was exported. Matches the
	   UserOwnsPackage() check in packages.php */
	$sqlstring = "select p.package_name from exportseries b join packages p on b.package_id = p.package_id join exports a on a.export_id = b.export_id where b.export_id = ? and a.filetype = 'package' and (p.user_id = ? or ? = 1) limit 1";
	$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
	$params = array($exportid, (int)$_SESSION['userid'], ($_SESSION['isadmin'] ? 1 : 0));
	mysqli_stmt_bind_param($stmt, 'iii', ...$params);
	$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
	$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
	mysqli_stmt_close($stmt);

	if (!$row) {
		http_response_code(404);
		exit('Export not found, or you do not have permission to access it');
	}

	/* the path is built here from the export id, never from user input. moduleExport writes
	   package exports to [exportdir]/NiDB-Squirrel-<exportid>.sqrl */
	$sqrlfile = $GLOBALS['cfg']['exportdir'] . "/NiDB-Squirrel-$exportid.sqrl";
	if (!is_file($sqrlfile)) {
		http_response_code(404);
		exit('The export file no longer exists');
	}

	/* name the download after the package, keeping only filename-safe characters */
	$basename = trim(preg_replace('/[^A-Za-z0-9._-]+/', '_', (string)$row['package_name']), '_.');
	if ($basename == '') $basename = "NiDB-Squirrel";
	$filename = "$basename-$exportid.sqrl";

	set_time_limit(0);
	header("Content-Description: File Transfer");
	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"$filename\"");
	header("Content-Transfer-Encoding: binary");
	header("Cache-Control: private, must-revalidate");
	header("Content-Length: " . filesize($sqrlfile));

	/* discard any buffered output so it isn't prepended to the binary file */
	while (ob_get_level() > 0) { ob_end_clean(); }
	readfile($sqrlfile);
	exit;
}

/* ── serve a file blob stored in the 'files' table ── */
if ($fileid > 0) {
	$download = (GetVariable("download") === '1');

	/* only serve the blob to someone allowed to see the object it is attached to. Answer 404 rather
	   than 403 so file ids can't be probed for existence */
	if (!UserCanViewFileBlob($fileid)) {
		http_response_code(404);
		exit('File not found');
	}

	$stmt = mysqli_prepare($GLOBALS['linki'], "select file_name, file_contenttype, file_blob, file_size from files where file_id = ?");
	mysqli_stmt_bind_param($stmt, 'i', $fileid);
	$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
	$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
	mysqli_stmt_close($stmt);

	if (!$row) {
		http_response_code(404);
		exit('File not found');
	}

	$contentType = $row['file_contenttype'] ?: 'application/octet-stream';
	$fileName    = $row['file_name']        ?: 'file';
	$blob        = $row['file_blob'];

	/* the content type was supplied by whoever uploaded the file. Only display types that can't run
	   script inline; anything else (html, svg, ...) is forced to download */
	$inlinetypes = array('image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/bmp', 'application/pdf', 'text/plain', 'text/csv');
	if (!in_array(strtolower(trim($contentType)), $inlinetypes, true))
		$download = true;

	header('Content-Type: '   . $contentType);
	header('Content-Length: ' . strlen($blob));
	header('Cache-Control: private, max-age=3600');
	header('X-Content-Type-Options: nosniff');
	header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . addslashes($fileName) . '"');
	echo $blob;
	exit;
}

if ($file != "") {

	/* file must live within the archive, mount, or an analysis directory.
	   realpath() resolves symlinks and ../ so traversal like archivedir/../../etc/passwd is rejected */
	$realFile = realpath($file);
	if (IsViewablePath($realFile)) {
		$file = $realFile;
		if (is_file($file)) {
			if ($action == "download") {
				$filename = basename($file);
				
				header("Pragma: public");
				header("Expires: 0");
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Cache-Control: public");
				header("Content-Description: File Transfer");
				header("Content-type: application/x-gzip");
				header("Content-Disposition: attachment; filename=\"$filename\"");
				header("Content-Transfer-Encoding: binary");
				header("Content-Length: " . filesize($file));
				
				//header("Content-Description: File Transfer");
				//header("Content-Disposition: attachment; filename=$filename");
				//header("Content-Type: application/octet-stream");
				////header("Content-type: ".mime_content_type($file)); 
				//header("Content-length: " . filesize($file) . "\n\n");
				//header("Content-Transfer-Encoding: binary");
				
				/* discard any buffered output so it isn't prepended to the binary file */
				while (ob_get_level() > 0) { ob_end_clean(); }
				readfile($file);
			}
			else {
				$pathparts = pathinfo($file);
				$ext = strtolower($pathparts['extension']);
				
				switch ($ext) {
					case "png":
						$im = imagecreatefrompng($file);
						header('Content-type: image/png');
						imagepng($im);
						imagedestroy($im);
						break;
					case "gif":
						$im = imagecreatefromgif($file);
						header('Content-type: image/gif');
						imagegif($im);
						echo file_get_contents($image);
						imagedestroy($im);
						break;
					case "jpg":
						$im = imagecreatefromjpg($file);
						header('Content-type: image/jpg');
						imagejpg($im);
						imagedestroy($im);
						break;
					case "wmv":
						header('Content-type: video/x-ms-wmv');
						readfile($file);
						break;
					case "ogv":
						header('Content-type: video/ogg');
						readfile($file);
						break;
					case "ogg":
						header('Content-type: video/ogg');
						readfile($file);
						break;
					case "mp4":
						header('Content-type: video/mp4');
						readfile($file);
						break;
					case "flv":
						header('Content-type: video/x-flv');
						readfile($file);
						break;
					default:
						echo "no match for [$ext] extension";
				}
			}
		}
		else { http_response_code(404); echo "file [" . htmlspecialchars($file) . "] does not exist"; }
	}
	else {
		http_response_code(403);
		echo htmlspecialchars($file) . " is not within an archive, mount, or analysis directory";
	}
}
else { echo "filename was blank"; }


	/* -------------------------------------------- */
	/* ------- UserCanViewFileBlob ---------------- */
	/* -------------------------------------------- */
	/* a row in 'files' has no owner of its own; it is attached to an observation (image/csv items)
	   or to a dataset request (the DUA). Allow it if the current user may see that object */
	function UserCanViewFileBlob($fileid) {
		$fileid = (int)$fileid;
		if ($fileid < 1)
			return false;

		if (IsAdminUser())
			return true;

		/* observation attachment: raw data, so View Data on a project the observation is enrolled in */
		$sqlstring = "select distinct b.project_id from observations a join enrollment b on a.enrollment_id = b.enrollment_id where a.observation_fileid = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $fileid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($fileid));
		$projectids = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
			$projectids[] = (int)$row['project_id'];
		mysqli_stmt_close($stmt);

		if (count($projectids) > 0) {
			$perms = GetCurrentUserProjectPermissions($projectids);
			foreach ($projectids as $projectid) {
				if (GetPerm($perms, 'viewdata', $projectid))
					return true;
			}
		}

		/* dataset request DUA: the user who made the request, or the admin it is assigned to */
		$username = (string)($_SESSION['username'] ?? '');
		if ($username == '')
			return false;
		$sqlstring = "select count(*) 'num' from dataset_requests where dua_fileid = ? and (username = ? or admin_username = ?)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'iss', $fileid, $username, $username);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($fileid, $username, $username));
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		return ((int)($row['num'] ?? 0) > 0);
	}
?>