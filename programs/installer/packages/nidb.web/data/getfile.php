<?
 // ------------------------------------------------------------------------------
 // NiDB getfile.php
 // Copyright (C) 2004 - 2019
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
if ($_POST["action"] == "") { $action = $_GET["action"]; } else { $action = $_POST["action"]; }

if ($file != "") {
	if (file_exists($file)) {
		if ($action == "download") {
			$filename = basename($file);
			header("Content-Description: File Transfer");
			header("Content-Disposition: attachment; filename=$filename");
			header("Content-Type: application/download");
			//header("Content-type: ".mime_content_type($file)); 
			header("Content-length: " . filesize($file) . "\n\n");
			header("Content-Transfer-Encoding: binary");
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
					//imagegif($im);
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
	else { echo "file [$file] does not exist"; }
}
else { echo "filename was blank"; }
?>