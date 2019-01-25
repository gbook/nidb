<?
 // ------------------------------------------------------------------------------
 // NiDB getassessment_formfields.php
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

	require 'config.php';
	require 'functions.php';
	
	$formid = $_GET['formid'];

	$returnarray = array();
	
	$sqlstring = "select * from assessment_formfields where form_id = $formid order by formfield_order+0 asc";
	$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
		$formfieldid = $row['formfield_id'];
		$formfieldorder = $row['formfield_order'];
		$formfielddesc = $row['formfield_desc'];
		$arr['optionValue'] = $formfieldid;
		$arr['optionDisplay'] = "$formfieldorder - $formfielddesc";
		
		array_push($returnarray, $arr);
	}
	echo json_encode($returnarray);
?>