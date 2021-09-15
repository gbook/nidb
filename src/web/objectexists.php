<?
 // ------------------------------------------------------------------------------
 // NiDB objectexists.php
 // Copyright (C) 2004 - 2021
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

	$ref = $_SERVER['HTTP_REFERER'];

	$action = GetVariable("action");
	$modality = GetVariable("modality");
	$datatype = GetVariable("datatype");
	$seriesid = GetVariable("seriesid");

	/* check if this was called from a valid page */
	if (contains($ref, $_SERVER["SERVER_NAME"] . "/studies.php") || contains($ref, $_SERVER["SERVER_ADDR"] . "/studies.php")) {
		
		if ($action == "") {
			list($path, $qapath, $uid, $studynum, $studyid, $subjectid) = GetDataPathFromSeriesID($seriesid, $modality);
			
			if ($datatype == "dicom") {
				$dicoms = glob("$path/*.dcm");
				$dcmfile = $dicoms[0];
				if (file_exists($dcmfile)) {
					?><!-- <i class="green check icon"></i>--><?
				}
				else {
					?> <i class="red exclamation circle icon" title="Files missing from disk [<?=$path?>]"></i><?
				}
			}
			elseif ($datatype == "parrec") {
				$pars = glob("$path/*.par");
				$parfile = $pars[0];
				if (file_exists($parfile)) {
					?><!-- <i class="green check icon"></i>--><?
				}
				else {
					?> <i class="red exclamation circle icon" title="Files missing from disk [<?=$path?>]"></i><?
				}
			} else {
				echo $series_desc;
			}
		}
		elseif ($action == "thumbnail") {
			list($path, $qapath, $uid, $studynum, $studyid, $subjectid) = GetDataPathFromSeriesID($seriesid, $modality);
			$thumbpath = "$path/../thumb.png";
			if (file_exists($thumbpath)) {
				?>
				<style>
					#preview{
					position:absolute;
					border:1px solid #ccc;
					background:gray;
					padding:0px;
					display:none;
					color:#fff;
					}
				</style>

				<a href="preview.php?image=<?=$thumbpath?>" class="preview"><i class="photo video icon"></i></a>
				<?
			}
			//else {
			//	echo "thumbnail path [$thumbpath]";
			//}
		}
	}
?>