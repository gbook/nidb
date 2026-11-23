<?
 // ------------------------------------------------------------------------------
 // NiDB download.php
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

	$seriesid = (int)GetVariable("seriesid");
	$type = GetVariable("type");
	$modality = GetVariable("modality");
	$filename = GetVariable("filename");

	$modality = strtolower($modality);
	
	if ($type == "file") {
		/* only the query/group CSV exports that search.php and groups.php write to the top level of
		   /tmp may be fetched here, and the file is deleted after it is sent. realpath() resolves ../
		   and symlinks so the check can't be walked out of /tmp */
		$realfile = realpath((string)$filename);
		$basename = basename((string)$filename);
		if (($realfile === false) || (dirname($realfile) !== '/tmp') || (!is_file($realfile)) || (!preg_match('/^[A-Za-z0-9_-]+\.csv$/', $basename)) || (basename($realfile) !== $basename)) {
			Error("The file you are attempting to download does not exist, or is not in a downloadable location");
		}
		elseif (filesize($realfile) == 0) {
			Error("The file you are attempting to download [" . htmlspecialchars($basename) . "] exists but is empty");
		}
		else {
			header("Content-Description: File Transfer");
			header("Content-Disposition: attachment; filename=\"$basename\"");
			header("Content-Type: text/csv");
			header("Content-Length: " . filesize($realfile));
			header("Content-Transfer-Encoding: binary");
			// output data to the browser
			while (ob_get_level() > 0) { ob_end_clean(); }
			readfile($realfile);
			unlink($realfile);
		}
	}
	else {
		/* the modality is used in a table name, so it must be a valid NiDB modality with a series table */
		$tablename = GetSeriesTableName($modality);
		if (($tablename != '') && IsNiDBModality($modality)) {
			/* get the path to the series data, and the project it belongs to */
			$sqlstring = "select a.*, b.study_num, c.project_id, d.uid from $tablename a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.$modality" . "series_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $seriesid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$seriesid]);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);

			if (!$row) {
				Error("Series not found");
				exit(0);
			}

			/* raw imaging (and behavioral) series data requires View Data on the series' project */
			$projectid = (int)$row['project_id'];
			$perms = GetCurrentUserProjectPermissions(array($projectid));
			if (!GetPerm($perms, 'viewdata', $projectid)) {
				Error("You do not have permissions to download this data");
				exit(0);
			}

			$series_num = trim($row['series_num'] ?? '');
			$study_num = trim($row['study_num'] ?? '');
			$uid = trim($row['uid'] ?? '');
			$datatype = $row['data_type'] ?? '';

			/* a blank UID, study, or series number would point the zip at a parent directory */
			if (($uid == '') || ($study_num == '') || ($series_num == '')) {
				Error("The archive path for this series is incomplete");
				exit(0);
			}

			if ($datatype == "") {
				$datatype = "$modality";
			}

			if ($type == "beh") {
				$datapath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/beh";
				$zipfilename = "$uid-$study_num-$series_num-beh.zip";
			}
			else {
				$datapath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/$datatype";
				$zipfilename = "$uid-$study_num-$series_num-$datatype.zip";
			}
			
			if (!file_exists($datapath)) {
				Error("The archive path [" . htmlspecialchars($datapath) . "] for this data does not exist");
				exit(0);
			}

			/* build the zip under a random name in [tmpdir] (not in a web-reachable or shared directory, and
			   no collision when two people download the same series at once). The shutdown function
			   deletes it however the request ends, including a client disconnect mid-download */
			$tmpdir = rtrim(trim($GLOBALS['cfg']['tmpdir'] ?? ''), "/");
			if (($tmpdir == '') || !is_dir($tmpdir) || !is_writable($tmpdir)) {
				Error("The temporary directory [tmpdir] is not set or is not writable");
				exit(0);
			}
			$zipfilepath = "$tmpdir/nidbdownload-" . bin2hex(random_bytes(16)) . ".zip";
			ignore_user_abort(true);
			register_shutdown_function(function() use ($zipfilepath) { if (is_file($zipfilepath)) @unlink($zipfilepath); });

			set_time_limit(0);
			$systemstring = "zip -j -q " . escapeshellarg($zipfilepath) . " " . escapeshellarg($datapath) . "/*";
			$junk = exec($systemstring);

			if (!file_exists($zipfilepath)) {
				Error("The archive path [" . htmlspecialchars($datapath) . "] exists, but the server was unable to create a zip file");
			}
			elseif (filesize($zipfilepath) == 0) {
				Error("The archive path [" . htmlspecialchars($datapath) . "] exists and a zip file was created but the file is empty");
			}
			else {
				/* streams the file (with Range support) and exits; the shutdown function then deletes it */
				StreamFile($zipfilepath, $zipfilename, "application/zip");
			}
		}
		else {
			Error("Invalid modality [$modality]");
		}
	}
?>