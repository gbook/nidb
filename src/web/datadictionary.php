<?
 // ------------------------------------------------------------------------------
 // NiDB datadictionary.php
 // Copyright (C) 2004 - 2025
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
		<title>NiDB - Data Dictionary</title>
	</head>

<body onload="onload()">
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	/* check if this page is being called from itself */
	$referringpage = $_SERVER['HTTP_REFERER'];
	$phpscriptname = pathinfo(__FILE__)['basename'];
	if (contains($referringpage, $phpscriptname))
		$selfcall = true;
	else
		$selfcall = false;

	//PrintVariable($_POST,'post');
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$projectid = GetVariable("projectid");
	$datadictid = GetVariable("datadictid");
	$varname = GetVariable("varname");
	$desc = GetVariable("desc");
	$type = GetVariable("type");
	$expectedcount = GetVariable("expectedcount");
	$rangelow = GetVariable("rangelow");
	$rangehigh = GetVariable("rangehigh");
	$itemids = GetVariable("itemids");
	$deleteids = GetVariable("deleteids");
	$csv = GetVariable("csv");
	
	/* determine action */
	if ($selfcall) {
		switch ($action) {
			case 'addvariable':
				AddVariable($projectid, $varname, $desc, $type, $expectedcount, $rangelow, $rangehigh);
				break;
			case 'updatevariables':
				UpdateVariables($projectid, $datadictid, $desc, $type, $expectedcount, $rangelow, $rangehigh, $deleteids);
				break;
			case 'addvariables':
				AddVariables($projectid, $varname, $desc, $type, $expectedcount, $rangelow, $rangehigh, $itemids);
				break;
			case 'addcsv':
				AddCSV($projectid, $csv);
				break;
			case 'deletevariable':
				DeleteVariable($datadictid);
				break;
		}
		DisplayVariables($projectid);
	}
	else {
		DisplayVariables($projectid);
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- AddVariable ------------------------ */
	/* -------------------------------------------- */
	function AddVariable($projectid, $varname, $desc, $type, $expectedcount, $rangelow, $rangehigh) {
		/* perform data checks */
		if (!ValidID($projectid)) { return; }
		$varname = mysqli_real_escape_string($GLOBALS['linki'], $varname);
		$desc = mysqli_real_escape_string($GLOBALS['linki'], $desc);
		$type = mysqli_real_escape_string($GLOBALS['linki'], $type);
		$expectedcount = mysqli_real_escape_string($GLOBALS['linki'], $expectedcount);
		$rangelow = mysqli_real_escape_string($GLOBALS['linki'], $rangelow);
		$rangehigh = mysqli_real_escape_string($GLOBALS['linki'], $rangehigh);
		
		if (trim($expectedcount) == "")
			$expectedcount = "null";
		if (trim($rangelow) == "")
			$rangelow = "null";
		if (trim($rangehigh) == "")
			$rangehigh = "null";
		
		if (trim($varname) == "") {
			Error("Blank variable name");
			return;
		}
		
		/* insert the variable */
		$sqlstring = "insert ignore into data_dictionary (datadict_type, project_id, datadict_varname, datadict_desc, datadict_expectedtimepoints, datadict_rangelow, datadict_rangehigh) values ('$type', $projectid, '$varname', '$desc', $expectedcount, $rangelow, $rangehigh) on duplicate key update datadict_desc = '$desc', datadict_type = '$type', datadict_expectedtimepoints = $expectedcount, datadict_rangelow = $rangelow, datadict_rangehigh = $rangehigh";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

		?><div align="center"><span class="message"><?=$varname?> added</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- UpdateVariables -------------------- */
	/* -------------------------------------------- */
	function UpdateVariables($projectid, $datadictid, $descs, $types, $expectedcounts, $rangelows, $rangehighs, $deleteids) {
		/* perform data checks */
		if (!ValidID($projectid)) { return; }
		$descs = mysqli_real_escape_array($GLOBALS['linki'], $descs);
		$types = mysqli_real_escape_array($GLOBALS['linki'], $types);
		//$expectedcounts = mysqli_real_escape_array($expectedcounts);
		$rangelows = mysqli_real_escape_array($GLOBALS['linki'], $rangelows);
		$rangehighs = mysqli_real_escape_array($GLOBALS['linki'], $rangehighs);
		$deleteids = mysqli_real_escape_array($GLOBALS['linki'], $deleteids);
		
		$ids = array_keys($types);
		foreach ($ids as $id) {
			if (!ValidID($id)) { continue; }
			
			/* check if we need to delete the variable, or update it */
			if (in_array($id, $deleteids)) {
				$sqlstring = "delete from data_dictionary where datadict_id = $id";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
			else {
				$desc = $descs[$id];
				$type = $types[$id];
				$expectedcount = $expectedcounts[$id];
				$rangelow = $rangelows[$id];
				$rangehigh = $rangehighs[$id];
				
				if (trim($expectedcount) == "")
					$expectedcount = "null";
				if (trim($rangelow) == "")
					$rangelow = "null";
				if (trim($rangehigh) == "")
					$rangehigh = "null";
				
				/* update the variable */
				$sqlstring = "update ignore data_dictionary set datadict_desc = '$desc', datadict_type = '$type', datadict_expectedtimepoints = $expectedcount, datadict_rangelow = $rangelow, datadict_rangehigh = $rangehigh where datadict_id = $id";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
		}
	}


	/* -------------------------------------------- */
	/* ------- AddVariables ----------------------- */
	/* -------------------------------------------- */
	function AddVariables($projectid, $varnames, $descs, $types, $expectedcounts, $rangelows, $rangehighs, $ids) {
		/* perform data checks */
		if (!ValidID($projectid)) { return; }
		$varnames = mysqli_real_escape_array($GLOBALS['linki'], $varnames);
		$descs = mysqli_real_escape_array($GLOBALS['linki'], $descs);
		$types = mysqli_real_escape_array($GLOBALS['linki'], $types);
		$expectedcounts = mysqli_real_escape_array($GLOBALS['linki'], $expectedcounts);
		$rangelows = mysqli_real_escape_array($GLOBALS['linki'], $rangelows);
		$rangehighs = mysqli_real_escape_array($GLOBALS['linki'], $rangehighs);
		
		foreach ($ids as $id) {
			$varname = $varnames[$id];
			$desc = $descs[$id];
			$type = $types[$id];
			$expectedcount = $expectedcounts[$id];
			$rangelow = $rangelows[$id];
			$rangehigh = $rangehighs[$id];
			
			if (trim($expectedcount) == "")
				$expectedcount = "null";
			if (trim($rangelow) == "")
				$rangelow = "null";
			if (trim($rangehigh) == "")
				$rangehigh = "null";
			
			/* insert the variable */
			$sqlstring = "insert ignore into data_dictionary (datadict_type, project_id, datadict_varname, datadict_desc, datadict_expectedtimepoints, datadict_rangelow, datadict_rangehigh) values ('$type', $projectid, '$varname', '$desc', $expectedcount, $rangelow, $rangehigh) on duplicate key update datadict_desc = '$desc', datadict_type = '$type', datadict_expectedtimepoints = $expectedcount, datadict_rangelow = $rangelow, datadict_rangehigh = $rangehigh";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

			?><div align="center"><span class="message"><?=$varname?> added</span></div><br><br><?
		}
	}


	/* -------------------------------------------- */
	/* ------- AddCSV ----------------------------- */
	/* -------------------------------------------- */
	function AddCSV($projectid, $csv) {
		/* perform data checks */
		if (!ValidID($projectid)) { return; }
		$data = ParseCSV($csv);
		//PrintVariable($data);
		
		foreach ($data as $i => $row) {
			$varname = mysqli_real_escape_string($GLOBALS['linki'], trim($row['variablename']));
			$vartype = mysqli_real_escape_string($GLOBALS['linki'], trim($row['type']));
			$vardesc = mysqli_real_escape_string($GLOBALS['linki'], trim($row['description']));
			$varkey = mysqli_real_escape_string($GLOBALS['linki'], trim($row['valuekey']));
			
			if ($varname == "")
				echo "Line $i: variablename was blank<br>";
			if ($vartype == "")
				echo "Line $i: type was blank<br>";
			if ($vardesc == "")
				echo "Line $i: description was blank<br>";
			
			if (trim($varkey) == "")
				$varkey = "null";
			else
				$varkey = "'$varkey'";
			
			/* insert the variable */
			$sqlstring = "insert ignore into data_dictionary (datadict_type, project_id, datadict_varname, datadict_desc, datadict_valuekey, datadict_expectedtimepoints, datadict_rangelow, datadict_rangehigh) values ('$vartype', $projectid, '$varname', '$vardesc', $varkey, NULL, NULL, NULL) on duplicate key update datadict_desc = '$vardesc', datadict_valuekey = $varkey, datadict_type = '$vartype', datadict_expectedtimepoints = NULL, datadict_rangelow = NULL, datadict_rangehigh = NULL";
			//PrintSQL($sqlstring);
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
	}

	/* -------------------------------------------- */
	/* ------- DeleteVariable --------------------- */
	/* -------------------------------------------- */
	function DeleteVariable($datadictid) {
		if (!ValidID($datadictid)) { return; }
		$sqlstring = "delete from data_dictionary where datadict_id = $datadictid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}	

	/* -------------------------------------------- */
	/* ------- DisplayVariables ------------------- */
	/* -------------------------------------------- */
	/* this functon displays all variables found in
	   the project. Also displays any associated
	   information about variables, from the
	   data_dictionary table */
	function DisplayVariables($projectid) {
		if (!ValidID($projectid)) { return; }
		
		$datadictitems = array();
		/* get all items from data_dictionary table */
		$sqlstring = "select * from data_dictionary where project_id = $projectid order by datadict_varname";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$datadictitems[$row['datadict_varname']]['id'] = $row['datadict_id'];
			$datadictitems[$row['datadict_varname']]['type'] = $row['datadict_type'];
			$datadictitems[$row['datadict_varname']]['desc'] = $row['datadict_desc'];
			$datadictitems[$row['datadict_varname']]['count'] = $row['datadict_expectedtimepoints'];
			$datadictitems[$row['datadict_varname']]['rangelow'] = $row['datadict_rangelow'];
			$datadictitems[$row['datadict_varname']]['rangehigh'] = $row['datadict_rangehigh'];
		}
		
		/* start the table */
		StartHTMLTable(array("Variable", "Type", "Description", "Count", "Issues", "Delete", ""), "graydisplaytable", "datadictionarytable");
		?>
		<tbody>
		<tr>
			<td colspan="8" style="background-color: #888; color: #fff; font-size: larger; padding:10px; font-weight: bold">Data Dictionary</td>
		</tr>
		<form method="post" action="datadictionary.php">
		<input type="hidden" name="action" value="addvariable">
		<input type="hidden" name="projectid" value="<?=$projectid?>">
		<tr>
			<td style="border-bottom: solid #888 2px; padding: 6px;"><input type="text" name="varname" required></td>
			<td style="border-bottom: solid #888 2px; padding: 6px;">
				<select name="type" required>
					<option value="">(Select type)
					<option value="observation">Observation
					<!--<option value="vital">Vital-->
					<option value="intervention">Intervention
					<option value="other">Other
				</select>
			</td>
			<td style="border-bottom: solid #888 2px; padding: 6px;"><input type="text" name="desc" required></td>
			<!--<td style="border-bottom: solid #888 2px; padding: 6px;"><input type="text" name="rangelow" style="width:80px"> - <input type="text" name="rangehigh" style="width:80px"></td>-->
			<td style="border-bottom: solid #888 2px; padding: 6px;"></td>
			<td style="border-bottom: solid #888 2px; padding: 6px;"></td>
			<td style="border-bottom: solid #888 2px; padding: 6px;"></td>
			<td style="border-bottom: solid #888 2px; padding: 6px;"><input type="submit" value="Add Single Variable" class="ui primary button"></td>
		</tr>
		</form>
		
		<form method="post" action="datadictionary.php">
		<input type="hidden" name="action" value="addcsv">
		<input type="hidden" name="projectid" value="<?=$projectid?>">
		<tr>
			<td colspan="8" style="border-bottom: solid #888 2px; padding: 6px;">
				<details>
					<summary>Example variable listing format</summary>
					<ul>
						<li>This text must contain a header with 4 columns: <code>variablename, type, description, valuekey</code>
						<li><code>variablename</code> - Can only contain letters, numbers, underscore. No spaces or other punctuation. (required)
						<li><code>type</code> - possible values <code>observation</code>, <code>intervention</code>. (required)
						<li><code>description</code> - Open text field. Must be enclosed in double quotes <code>"</code>, but string cannot contain double quotes. (required)
						<li><code>valuekey</code> - list of possible keys and their meaning (value). Pairs should be separated with the pipe character <code>|</code>. (optional)
					</ul>
					<br>
					<div class="code" style="background-color: #fff"><b>variablename, type, description, valuekey</b><br>
variable_1, observation, "Important Variable 1 - w/value keys", 1=abc|2=xyz|3=mno<br>
variable_2, intervention, "Important Variable 1 - no keys", </div>
				</details>
				<br><br>
				<span style="color: #444">Paste .csv (comma separated values) here</span><br>
				<textarea name="csv" style="width: 100%; height: 100px"></textarea>
				<input type="submit" value="Add Group Variables" class="ui primary button">
			</td>
		</tr>
		</form>
		
		<form method="post" action="datadictionary.php">
			<input type="hidden" name="action" value="updatevariables">
			<input type="hidden" name="projectid" value="<?=$projectid?>">
		<?
		/* display the table rows */
		foreach($datadictitems as $varname => $details) {
			$id = $details['id'];
			$type = $details['type'];
			$desc = $details['desc'];
			//$count = $details['count'];
			$rangelow = $details['rangelow'];
			$rangehigh = $details['rangehigh'];
			
			$sqlstring = "select count(b.observation_name) 'count' from observations a left join enrollment b on a.enrollment_id = b.enrollment_id where b.project_id = $projectid and b.observation_name = '$varname'";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$observationcount = (int)$row['count'];

			//$sqlstring = "select count(c.vitalname_id) 'count' from vitals a left join enrollment b on a.enrollment_id = b.enrollment_id left join vitalnames c on a.vitalname_id = c.vitalname_id where b.project_id = $projectid and c.vital_name = '$varname'";
			//$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			//$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			//$vitalcount = (int)$row['count'];

			$sqlstring = "select count(a.intervention_name) 'count' from interventions a left join enrollment b on a.enrollment_id = b.enrollment_id where b.project_id = $projectid and c.intervention_name = '$varname'";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$interventioncount = (int)$row['count'];

			$errors = array();
			switch ($type) {
				case "observation":
					if ($vitalcount > 0)
						$errors[] = "Data dictionary variable has $vitalcount entries mis-classified as vital";
					if ($interventioncount > 0)
						$errors[] = "Data dictionary variable has $interventioncount entries mis-classified as intervention";
					
					$count = $observationcount;
					break;
				case "vital":
					if ($observationcount > 0)
						$errors[] = "Data dictionary variable has $observationcount entries mis-classified as observation";
					if ($interventioncount > 0)
						$errors[] = "Data dictionary variable has $interventioncount entries mis-classified as intervention";
					
					$count = $vitalcount;
					break;
				case "intervention":
					if ($observationcount > 0)
						$errors[] = "Data dictionary variable has $observationcount entries mis-classified as observation";
					if ($vitalcount > 0)
						$errors[] = "Data dictionary variable has $vitalcount entries mis-classified as vital";
					
					$count = $interventioncount;
					break;
			}
			
			?>
			<tr>
				<td><?=$varname?></td>
				<td>
					<select name="type[<?=$id?>]">
						<option value="" <? if (!in_array($type, array("observation", "vital", "intervention", "other"))) { echo "selected"; } ?> >(Select type)
						<option value="observation" <? if ($type == "observation") { echo "selected"; } ?> >Observation
						<option value="vital" <? if ($type == "vital") { echo "selected"; } ?> >Vital
						<option value="intervention" <? if ($type == "intervention") { echo "selected"; } ?> >Intervention
						<option value="other" <? if ($type == "other") { echo "selected"; } ?> >Other
					</select>
				</td>
				<td><input type="text" name="desc[<?=$id?>]" value="<?=$desc?>"></td>
				<!--<td><input type="text" name="rangelow" style="width:80px" value="<?=$rangelow?>"> - <input type="text" name="rangehigh" style="width:80px" value="<?=$rangehigh?>"></td>-->
				<td><?=$count?></td>
				<td>
				<?
					if (count($errors) > 0) {
						?>
						<details style="font-size: smaller">
						<summary><?=count($errors)?> issue(s)</summary>
							<ul>
							<?
								foreach ($errors as $err) {
									echo "<li>$err\n";
								}
							?>
							</ul>
						</details>
						<?
					}
					else {
					}
				?>
				</td>
				<td><input name="deleteids[]" type="checkbox" value="<?=$id?>"></td>
				<td></td>
			</tr>
			<?
		}
		?>
		<tr>
			<td colspan="8" align="right"><input type="submit" value="Update Data Dictionary" class="ui primary button"></td>
		</tr>
		</form>
		</tbody>
		<?
		/* end the data dictionary table */
		EndHTMLTable();
		?>
		<br>
		<?
		
		/* get all observation variables */
		$sqlstring = "select a.observation_name, count(a.observationname_id) 'count' from observations a left join enrollment b on a.enrollment_id = b.enrollment_id where b.project_id = $projectid group by a.observation_name order by a.observation_name";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$observationname = $row['observation_name'];
			$dbitems[$observationname]['type'] = 'observation';
			$dbitems[$observationname]['count'] = $row['count'];
		}

		
		/* get all vital variables */
		$sqlstring = "select c.vital_name, count(c.vitalname_id) 'count' from vitals a left join enrollment b on a.enrollment_id = b.enrollment_id left join vitalnames c on a.vitalname_id = c.vitalname_id where b.project_id = $projectid group by c.vital_name order by c.vital_name";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$vitalname = $row['vital_name'];
			$dbitems[$vitalname]['type'] = 'vital';
			$dbitems[$vitalname]['count'] = $row['count'];
		}

		/* get all intervention variables */
		$sqlstring = "select a.intervention_name, count(a.intervention_name) 'count' from interventions a left join enrollment b on a.enrollment_id = b.enrollment_id where b.project_id = $projectid group by a.intervention_name order by a.intervention_name";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$interventionname = $row['intervention_name'];
			$dbitems[$interventionname]['type'] = 'intervention';
			$dbitems[$interventionname]['count'] = $row['count'];
		}
		
		ksort($dbitems);
		
		StartHTMLTable(array("Variable", "Type", "Description", "Count", "Action"), "graydisplaytable", "datadictionarytable");
		?>
		<tr>
			<td colspan="7" style="background-color: #888; color: #fff; font-size: larger; padding:10px; font-weight: bold">Uncategorized Variables</td>
		</tr>
		<form method="post" action="datadictionary.php">
		<input type="hidden" name="action" value="addvariables">
		<input type="hidden" name="datadictid" value="<?=$id?>">
		<input type="hidden" name="projectid" value="<?=$projectid?>">
		<?
		$rownum = 0;
		foreach($dbitems as $varname => $details) {
			$type = $details['type'];
			$desc = $details['desc'];
			$count = $details['count'];
			$rangelow = $details['rangelow'];
			$rangehigh = $details['rangehigh'];
			
			if (!in_array($varname, array_keys($datadictitems))) {
			?>
			<tr>
				<td><input type="hidden" name="varname[<?=$rownum?>]" value="<?=$varname?>"><?=$varname?></td>
				<td><input type="hidden" name="type[<?=$rownum?>]" value="<?=$type?>"><?=$type?></td>
				<td><input type="text" name="desc[<?=$rownum?>]" value="<?=$desc?>"></td>
				<!--<td><input type="text" name="rangelow[<?=$rownum?>]" style="width:80px" value="<?=$rangelow?>"> - <input type="text" name="rangehigh[<?=$rownum?>]" style="width:80px" value="<?=$rangehigh?>"></td>-->
				<td><?=$count?></td>
				<!--<td></td>-->
				<td><input name="itemids[]" type="checkbox" value="<?=$rownum?>"></td>
			</tr>
			<?
			$rownum++;
			}
		}
		?>
		<tr>
			<td colspan="7" align="right">
				<b>With Selected Variables:</b><br>
				<input type="submit" value="Add to Data Dictionary" class="ui primary button">
				<br>
			</td>
		</tr>
		</form>
		</tbody>
		<?
		/* end the table */
		EndHTMLTable();
	}
?>
<? include("footer.php") ?>
