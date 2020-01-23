<?
 // ------------------------------------------------------------------------------
 // NiDB redcapimport.php
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

	define("LEGIT_REQUEST", true);
	
	session_start();
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - RedCap import</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";
	
	//PrintVariable($_POST);
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$mappingid = GetVariable("mappingid");
	$projectid = GetVariable("projectid");
	$redcapevent = GetVariable("redcapevent");
	$redcapform = GetVariable("redcapform");
	$redcapfields = GetVariable("redcapfields");
	$nidbdatatype = GetVariable("nidbdatatype");
	$nidbvariablename = GetVariable("nidbvariablename");
	$nidbinstrumentname = GetVariable("nidbinstrumentname");
						
	/* determine action */
	switch ($action) {
		case 'updatemapping':
			UpdateMapping($projectid, $redcapevent, $redcapform, $redcapfields, $nidbdatatype, $nidbvariablename, $nidbinstrumentname);
			DisplayImportSettings($projectid);
			break;
		case 'deletemapping':
			DeleteMapping($mappingid);
			DisplayImportSettings($projectid);
			break;
		default:
			DisplayImportSettings($projectid);
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- UpdateMapping ---------------------- */
	/* -------------------------------------------- */
	function UpdateMapping($projectid, $redcapevent, $redcapform, $redcapfields, $nidbdatatype, $nidbvariablename, $nidbinstrumentname) {
		$redcapevent = mysqli_real_escape_string($GLOBALS['linki'], $redcapevent);
		$redcapform = mysqli_real_escape_string($GLOBALS['linki'], $redcapform);
		$redcapfields = mysqli_real_escape_string($GLOBALS['linki'], $redcapfields);
		$nidbdatatype = mysqli_real_escape_string($GLOBALS['linki'], $nidbdatatype);
		$nidbvariablename = mysqli_real_escape_string($GLOBALS['linki'], $nidbvariablename);
		$nidbinstrumentname = mysqli_real_escape_string($GLOBALS['linki'], $nidbinstrumentname);

		$sqlstring = "start transaction";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		$sqlstring = "insert ignore into redcap_import_mapping (project_id, redcap_event, redcap_form, redcap_fields, nidb_datatype, nidb_variablename, nidb_instrumentname) values($projectid, '$redcapevent', '$redcapform', '$redcapfields', '$nidbdatatype', '$nidbvariablename', '$nidbinstrumentname')";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		$sqlstring = "commit";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
	}


	/* -------------------------------------------- */
	/* ------- DeleteMapping ---------------------- */
	/* -------------------------------------------- */
	function DeleteMapping($mappingid) {

		MySQLiQuery("start transaction", __FILE__, __LINE__);

		$sqlstring = "delete from redcap_import_mapping where formmap_id = $mappingid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		MySQLiQuery("commit", __FILE__, __LINE__);
		
		?><div align="center"><span class="message">Mapping deleted</span></div><br><br><?
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayImportSettings -------------- */
	/* -------------------------------------------- */
	function DisplayImportSettings($projectid) {
		
		if ((trim($projectid) == "") || ($projectid < 0)) {
			?>Invalid or blank project ID [<?=$projectid?>]<?
			return;
		}
		
		?>
		
		<form action="redcapimport.php" method="post">
		<input type="hidden" name="action" value="updatemapping">
		<input type="hidden" name="projectid" value="<?=$projectid?>">
		
		<span style="font-size: larger; font-weight: bold">RedCap to NiDB form/variable mapping</span><br>
		<span class="tiny">Map the variable in the RedCap system to the NiDB variable type</span>
		<br>
		<br>
		<table class="graydisplaytable">
			<thead>
				<tr>
					<th style="text-align: center; border-right: 1px solid #bdbdbd" colspan="3">RedCap</th>
					<th rowspan="2" style="text-align: center; vertical-align: middle; font-size: 20pt; border-right: 1px solid #bdbdbd; padding: 0px 30px">&#10132;</th>
					<th style="text-align: center" colspan="4">NiDB</th>
				</tr>
				<tr>
					<th>Event</th>
					<th>Form</th>
					<th style="border-right: 1px solid #bdbdbd">Field(s)<br><span class="tiny">Comma separated list</span></th>
					<th>Type</th>
					<th>Variable</th>
					<th>Instrument</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><input type="text" name="redcapevent"></td>
					<td><input type="text" name="redcapform"></td>
					<td style="border-right: 1px solid #bdbdbd"><input type="text" name="redcapfields"></td>
					<td style="border-right: 1px solid #bdbdbd"></td>
					<td>
						<select name="nidbdatatype" required>
							<option value="">(select type)
							<option value="m">Measure
							<option value="v">Vital
							<option value="d">Drug/dose
						</select>
					</td>
					<td><input type="text" name="nidbvariablename"></td>
					<td><input type="text" name="nidbinstrumentname"></td>
					<td></td>
				</tr>
				<?
					$sqlstring = "select * from redcap_import_mapping where project_id = $projectid";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$formmapid = $row['formmap_id'];
						$event = $row['redcap_event'];
						$form = $row['redcap_form'];
						$fields = $row['redcap_fields'];
						$type = $row['nidb_datatype'];
						$variable = $row['nidb_variablename'];
						$instrument = $row['nidb_instrumentname'];
						
						switch ($type) {
							case 'm': $typeStr = "Measure"; break;
							case 'v': $typeStr = "Vital"; break;
							case 'd': $typeStr = "Drug/dose"; break;
						}
						?>
						<tr>
							<td><?=$event?></td>
							<td><?=$form?></td>
							<td style="border-right: 1px solid #bdbdbd"><?=$fields?></td>
							<td style="border-right: 1px solid #bdbdbd; text-align: center">&#10132;</td>
							<td><?=$typeStr?></td>
							<td><?=$variable?></td>
							<td><?=$instrument?></td>
							<td title="Delete mapping"><a href="redcapimport.php?action=deletemapping&mappingid=<?=$formmapid?>&projectid=<?=$projectid?>" class="redlinkbutton" style="font-size: smaller">X</a></td>
						</tr>
					<?
					}
					?>
			</tbody>
		</table>
		
		<br>
		<input type="submit" value="Save">
		</form>
		<?
	}
?>


<? include("footer.php") ?>
