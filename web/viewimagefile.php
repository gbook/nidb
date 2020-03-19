<?
 // ------------------------------------------------------------------------------
 // NiDB viewfile.php
 // Copyright (C) 2004 - 2020
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

if ($_POST["file"] == "") { $file = $_GET["file"]; } else { $file = $_POST["file"]; }

if ($file != "") {
	if (file_exists($file)) {
		?>
		<body bgcolor="#DDD">
<div style="border: 1px solid #BBB; margin:10px; padding:10px; background-color: white; font-family: monospace; white-space: pre;">
<div style="padding:5px; background-color: 393939; color:white; font-size:11pt"><?=$file?></div>
<?
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$imgdata = base64_encode(file_get_contents($file));
?>
<img border="1" src="data:image/<?=$ext?>;base64,<?=$imgdata?>">
</div>
		<?
	}
	else { echo "file [$file] does not exist"; }
}
else { echo "filename was blank"; }
?>