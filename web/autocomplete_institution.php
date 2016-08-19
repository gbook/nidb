<?
 // ------------------------------------------------------------------------------
 // NiDB autocomplete_institution.php
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

	//require "config.php";
	require "functions.php";

	$term = GetVariable("term");

	$returnarray = array();
	
	$sqlstring = "select distinct(study_institution) 'institution' from studies where study_institution like '%$term%'";
	$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
		$institution = $row['institution'];
		$arr['id'] = $institution;
		$arr['label'] = $institution;
		$arr['value'] = $institution;
		
		array_push($returnarray, $arr);
	}
	echo json_encode($returnarray);
?>