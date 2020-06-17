<?
 // ------------------------------------------------------------------------------
 // NiDB series_inlineupdate.php
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

	define("LEGIT_REQUEST", true);
	
	session_start();
	
	require "functions.php";
	require "includes_php.php";
	
	if (isset($_POST['element_id'])) {
		$id = $_POST['id'];
		$modality = strtolower($_POST['modality']);
		$field = $_POST['element_id'];
		$value = mysqli_real_escape_string($GLOBALS['linki'], $_POST['update_value']);
		
		if (trim($value) == "") {
			//$sqlstring = "update subjects set $field = null where subject_id = $id";
			$sqlstring = "update $modality" . "_series set $field = null where $modality" . "series_id = $id";
		}
		else {
			$sqlstring = "update $modality" . "_series set $field = '$value' where $modality" . "series_id = $id";
		}
		
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if ($_POST['update_value'] == "") { $dispvalue = " "; } else { $dispvalue = $_POST['update_value']; }
		echo str_replace('\n',"<br>",$dispvalue);
	}
?>