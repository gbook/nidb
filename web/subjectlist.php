<?
 // ------------------------------------------------------------------------------
 // NiDB subjectlist.php
 // Copyright (C) 2004 - 2016
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

	include("globalfunctions.php");

	$term = GetVariable("term");

	$returnarray = array();
	
	$sqlstring = "select uid from subjects where uid like '%$term%'";
	$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
		$uid = $row['uid'];
		$arr['id'] = $uid;
		$arr['label'] = $uid;
		$arr['value'] = $uid;
		
		array_push($returnarray, $arr);
	}
	echo json_encode($returnarray);
?>