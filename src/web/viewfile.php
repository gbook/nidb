<?
 // ------------------------------------------------------------------------------
 // NiDB viewfile.php
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
$file = GetVariable("file");

if ($file != "") {

	/* resolve symlinks and ../ so the prefix check below can't be bypassed */
	$realfile = realpath($file);

	if (($realfile === false) || (!is_file($realfile))) {
		echo "file [" . htmlspecialchars($file) . "] does not exist";
	}
	elseif (!IsViewablePath($realfile)) {
		echo htmlspecialchars($file) . " is not within an archive, mount, or analysis directory";
	}
	else {
		?>
		<body bgcolor="#DDD">
		<div style="border: 1px solid #BBB; margin:10px; padding:10px; background-color: white; font-family: monospace; white-space: pre;">
		<div style="padding:5px; background-color: #393939; color:white; font-size:11pt"><?=htmlspecialchars($file)?></div>
		<?
		if (IsBinaryFile($realfile)) {
			?>
			<i>Binary file (<?=number_format(filesize($realfile))?> bytes) - contents not displayed</i>
			<?
		}
		else {
			/* stream line by line and escape, so HTML in the file is shown as text */
			$fh = fopen($realfile, 'r');
			if ($fh !== false) {
				while (($line = fgets($fh)) !== false) {
					echo htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE);
				}
				fclose($fh);
			}
		}
		?>
		</div>
		<?
	}
}
else { echo "filename was blank"; }


/* -------------------------------------------- */
/* ------- IsViewablePath --------------------- */
/* -------------------------------------------- */
/* file must live within the archive, mount, or an analysis directory */
function IsViewablePath($realfile) {
	$dirs = array();
	foreach (array('archivedir', 'mountdir', 'analysisdir', 'analysisdirb', 'groupanalysisdir') as $key) {
		$dirs[] = $GLOBALS['cfg'][$key] ?? '';
	}

	/* level-0 pipelines write to their own pipeline_directory */
	$result = MySQLiQuery("select distinct pipeline_directory from pipelines where pipeline_directory <> ''", __FILE__, __LINE__);
	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
		$dirs[] = $row['pipeline_directory'];
	}

	foreach ($dirs as $dir) {
		$realdir = realpath(trim($dir));
		/* skip unset/missing dirs, and never allow the filesystem root */
		if (($realdir === false) || ($realdir == '/')) { continue; }

		if (substr($realfile, 0, strlen($realdir) + 1) === $realdir . '/') {
			return true;
		}
	}
	return false;
}


/* -------------------------------------------- */
/* ------- IsBinaryFile ----------------------- */
/* -------------------------------------------- */
/* treat the file as binary if the first 8KB contains a NUL byte */
function IsBinaryFile($realfile) {
	$fh = fopen($realfile, 'r');
	if ($fh === false) { return true; }
	$chunk = fread($fh, 8192);
	fclose($fh);
	return (strpos((string)$chunk, "\0") !== false);
}

?>