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
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - View file</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	$action = GetVariable("action");
	$file = GetVariable("file");

	if ($file == "") {
		Error("Filename was blank");
	}
	else {
		/* resolve symlinks and ../ so the prefix check below can't be bypassed */
		$realfile = realpath($file);

		if (($realfile === false) || (!is_file($realfile))) {
			Error("File [" . htmlspecialchars($file) . "] does not exist");
		}
		elseif (!IsViewablePath($realfile)) {
			Error(htmlspecialchars($file) . " is not within an archive, mount, or analysis directory");
		}
		else {
			DisplayFile($file, $realfile);
		}
	}
?>

<? include("footer.php") ?>
<?

/* -------------------------------------------- */
/* ------- DisplayFile ------------------------ */
/* -------------------------------------------- */
function DisplayFile($file, $realfile) {
	$filesize = filesize($realfile);
	$isbinary = IsBinaryFile($realfile);
	?>
	<style>
		/* the page column is a flex item, so by default it grows to fit long lines instead of
		   letting #fileviewer scroll horizontally; allow it to shrink to the window width */
		#mainPageGrid > .column { min-width: 0; }

		#fileviewer {
			margin: 0;
			padding: 10px;
			height: calc(100vh - 220px); /* fallback; resized to fill the window by SizeFileViewer() */
			min-height: 200px;
			overflow: auto;
			font-family: 'JetBrains Mono', 'Roboto Mono', monospace;
			font-size: 9pt;
			line-height: 1.4;
			white-space: pre;
			background-color: #fff;
			tab-size: 4;
		}
	</style>

	<div class="ui top attached inverted segment" style="display: flex; align-items: center; gap: 10px; padding: 8px 12px">
		<i class="file <?=($isbinary ? '' : 'alternate')?> outline icon"></i>
		<span style="font-family: 'JetBrains Mono', 'Roboto Mono', monospace; overflow-wrap: anywhere; flex: 1"><?=htmlspecialchars($file)?></span>
		<span class="ui grey label"><?=number_format($filesize)?> bytes</span>
		<a class="ui mini primary button" href="getfile.php?action=download&file=<?=urlencode($realfile)?>"><i class="download icon"></i> Download</a>
	</div>
	<?
	if ($isbinary) {
		?>
		<div class="ui bottom attached segment">
			<i>Binary file - contents not displayed</i>
		</div>
		<?
	}
	else {
		?><div class="ui bottom attached segment" style="padding: 0"><pre id="fileviewer"><?
		/* stream line by line and escape, so HTML in the file is shown as text */
		$fh = fopen($realfile, 'r');
		if ($fh !== false) {
			while (($line = fgets($fh)) !== false) {
				echo htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE);
			}
			fclose($fh);
		}
		?></pre></div>

		<script>
			/* size the viewer to fill the window down to the footer, so long files scroll inside it instead of the page */
			function SizeFileViewer() {
				var viewer = document.getElementById('fileviewer');
				var height = window.innerHeight - (viewer.getBoundingClientRect().top + window.scrollY);
				viewer.style.height = Math.max(height, 200) + 'px';

				/* then shrink by whatever still overflows the window (segment borders, margins, footer) */
				var overflow = document.documentElement.scrollHeight - window.innerHeight;
				if (overflow > 0) {
					viewer.style.height = Math.max(height - overflow, 200) + 'px';
				}
			}
			$(document).ready(SizeFileViewer);
			$(window).on('resize', SizeFileViewer);
		</script>
		<?
	}
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