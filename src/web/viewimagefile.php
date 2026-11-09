<?
 // ------------------------------------------------------------------------------
 // NiDB viewimagefile.php
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

$file = GetVariable("file");

/* only images are shown here (in an <img> data URI), with the browser-supported types */
$mimetypes = array('png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'bmp' => 'image/bmp', 'webp' => 'image/webp');

if ($file == "") {
	echo "Filename was blank";
}
else {
	/* resolve symlinks and ../ first, so the directory check can't be bypassed with a path
	   like <archivedir>/../../etc/passwd. Same check as viewfile.php */
	$realfile = realpath($file);
	$ext = strtolower(pathinfo((string)$realfile, PATHINFO_EXTENSION));

	if (($realfile === false) || (!is_file($realfile))) {
		echo "File [" . htmlspecialchars($file) . "] does not exist";
	}
	elseif (!IsViewablePath($realfile)) {
		echo htmlspecialchars($file) . " is not within an archive, mount, or analysis directory";
	}
	elseif (!isset($mimetypes[$ext])) {
		echo htmlspecialchars($file) . " is not an image file";
	}
	else {
		$imgdata = base64_encode(file_get_contents($realfile));
		?>
		<body bgcolor="#DDD">
		<div style="border: 1px solid #BBB; margin:10px; padding:10px; background-color: white; font-family: monospace; white-space: pre;">
			<div style="padding:5px; background-color: #393939; color:white; font-size:11pt"><?=htmlspecialchars($file)?></div>
			<img border="1" src="data:<?=$mimetypes[$ext]?>;base64,<?=$imgdata?>">
		</div>
		<?
	}
}

?>
