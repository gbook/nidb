<?
 // ------------------------------------------------------------------------------
 // NiDB tusupload.php
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

 /* tus resumable upload endpoint for importimaging.php. The context is the upload_id: files go into
    the upload's upload_datapath, and only while it is a web upload with status 'uploading'. The
    protocol itself is in tus_functions.php. See doc/tus.md */

	define("LEGIT_REQUEST", true);

	session_start();
	ob_start(); /* discard any output from the includes; this page only speaks the tus protocol */

	$nologin = true; /* TusServe() returns an HTTP status instead of redirecting to login.php */
	require "functions.php";
	require "includes_php.php";
	require "tus_functions.php";

	TusServe(array(
		'endpoint' => 'tusupload.php',
		'contextpattern' => '/^[1-9][0-9]*$/',
		'resolve' => 'ImportTusResolve',
		'log' => 'ImportTusLog'
	));


	/* -------------------------------------------- */
	/* ------- ImportTusResolve ------------------- */
	/* -------------------------------------------- */
	/* upload directory for $uploadid, if it is still receiving files from the browser */
	function ImportTusResolve($uploadid, $userid, $failcode) {
		$uploadid = (int)$uploadid;
		$sqlstring = "select upload_status, upload_source, upload_datapath from uploads where upload_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $uploadid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($uploadid));
		$row = ($result) ? mysqli_fetch_array($result, MYSQLI_ASSOC) : null;
		mysqli_stmt_close($stmt);

		if (!$row) {
			TusRespond($failcode, array(), "Upload [$uploadid] not found");
		}
		if (($row['upload_status'] != "uploading") || ($row['upload_source'] != "web")) {
			TusRespond($failcode, array(), "Upload [$uploadid] is no longer accepting files. Its status is [" . $row['upload_status'] . "]");
		}

		return $row['upload_datapath'];
	}


	/* -------------------------------------------- */
	/* ------- ImportTusLog ----------------------- */
	/* -------------------------------------------- */
	function ImportTusLog($uploadid, $message) {
		$uploadid = (int)$uploadid;
		$str = "tusupload.php  $message";
		$sqlstring = "insert ignore into upload_logs (upload_id, log_date, log_msg) values (?, now(), ?)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'is', $uploadid, $str);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($uploadid, $str));
		mysqli_stmt_close($stmt);
	}
?>
