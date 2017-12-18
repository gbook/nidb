<?
 // ------------------------------------------------------------------------------
 // NiDB series_inlineupdate.php
 // Copyright (C) 2004 - 2017
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

	session_start();

	require "functions.php";
	
	if (isset($_POST['element_id'])) {
		/* database connection */
		$linki = mysqli_connect($GLOBALS['cfg']['mysqlhost'], $GLOBALS['cfg']['mysqluser'], $GLOBALS['cfg']['mysqlpassword'], $GLOBALS['cfg']['mysqldatabase']) or die ("Could not connect. Error [" . mysqli_error() . "]  File [" . __FILE__ . "] Line [ " . __LINE__ . "]");

		$id = $_POST['id'];
		$modality = strtolower($_POST['modality']);
		$field = $_POST['element_id'];
		$value = mysqli_real_escape_string($GLOBALS['linki'], $_POST['update_value']);
		$sqlstring = "update enrollment set $field = '$value' where enrollment_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if ($_POST['update_value'] == "") { $dispvalue = " "; } else { $dispvalue = $_POST['update_value']; }
		echo str_replace('\n',"<br>",$dispvalue);
	}
?>