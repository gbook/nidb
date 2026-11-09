<?
 // ------------------------------------------------------------------------------
 // NiDB datadictionary.php
 // Copyright (C) 2004 - 2026
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
	ob_start(); /* buffer output so POST/Redirect/GET (a header('Location') redirect) works despite the HTML rendered below */
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Data Dictionary</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	/* ----- setup variables ----- */
	/* mutating actions require POST + CSRF token (this replaces the old HTTP_REFERER self-call check) */
	$mutating = array("addvariable", "updatevariables", "addvariables", "addcsv");
	$action = GetSafeAction($mutating);
	$projectid = (int)GetVariable("projectid");
	$varname = GetVariable("varname");
	$desc = GetVariable("desc");
	$type = GetVariable("type");
	$itemids = GetVariable("itemids");
	$deleteids = GetVariable("deleteids");
	$csv = GetVariable("csv");

	/* mutating actions use POST/Redirect/GET: run the handler, stash its message, then redirect to a
	   GET so a refresh/Back doesn't re-submit the form */
	if (in_array($action, $mutating, true)) {
		ob_start();
		list($ismember, $canedit) = GetDataDictPerms($projectid);
		if (!$canedit) {
			Error("You do not have permission to edit this project's data dictionary");
		}
		else {
			switch ($action) {
				case 'addvariable': AddVariable($projectid, $varname, $desc, $type); break;
				case 'updatevariables': UpdateVariables($projectid, $desc, $type, $deleteids); break;
				case 'addvariables': AddVariables($projectid, $varname, $desc, $type, $itemids); break;
				case 'addcsv': AddCSV($projectid, $csv); break;
			}
		}
		$_SESSION['flash'] = ob_get_clean();
		RedirectTo("datadictionary.php?projectid=$projectid");
	}

	DisplayVariables($projectid);


	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- GetDataDictPerms ------------------- */
	/* -------------------------------------------- */
	/* The data dictionary is a project definition: any member of the project (any permission on it) may
	   view it; Edit Data is needed to change it. Returns [ismember, canedit] */
	function GetDataDictPerms($projectid) {
		$projectid = (int)$projectid;
		if ($projectid < 1)
			return array(false, false);
		$perms = GetCurrentUserProjectPermissions(array($projectid));
		return array(IsProjectMember($perms, $projectid), (GetPerm($perms, 'modifydata', $projectid) == 1));
	}


	/* -------------------------------------------- */
	/* ------- Data dictionary types -------------- */
	/* -------------------------------------------- */
	/* data_dictionary.datadict_type is enum('drug','vital','measure','other'), the old names for
	   interventions and observations. The page uses the current names and maps them when saving and
	   reading. Returns '' for an unknown type */
	function DataDictTypeToDB($type) {
		$map = array('observation' => 'measure', 'intervention' => 'drug', 'vital' => 'vital', 'other' => 'other');
		return $map[strtolower(trim((string)$type))] ?? '';
	}
	function DataDictTypeFromDB($dbtype) {
		$map = array('measure' => 'observation', 'drug' => 'intervention', 'vital' => 'vital', 'other' => 'other');
		return $map[(string)$dbtype] ?? '';
	}


	/* -------------------------------------------- */
	/* ------- SaveVariable ----------------------- */
	/* -------------------------------------------- */
	/* adds a variable to the project's data dictionary, or updates it if the project already has a
	   variable with that name (there is no unique key on project/name, so this checks first). The value
	   key is only changed when $setvaluekey is true. Returns false if the name or type is invalid */
	function SaveVariable($projectid, $varname, $type, $desc, $valuekey = null, $setvaluekey = false) {
		$projectid = (int)$projectid;
		$varname = trim((string)$varname);
		$desc = trim((string)$desc);
		$dbtype = DataDictTypeToDB($type);
		if (($varname == "") || ($dbtype == ""))
			return false;

		$sqlstring = "select datadict_id from data_dictionary where project_id = ? and datadict_varname = ? order by datadict_id limit 1";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'is', $projectid, $varname);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid, $varname]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		if ($row) {
			$datadictid = (int)$row['datadict_id'];
			if ($setvaluekey) {
				$sqlstring = "update data_dictionary set datadict_type = ?, datadict_desc = ?, datadict_valuekey = ? where datadict_id = ?";
				$params = [$dbtype, $desc, $valuekey, $datadictid];
				$types = 'sssi';
			}
			else {
				$sqlstring = "update data_dictionary set datadict_type = ?, datadict_desc = ? where datadict_id = ?";
				$params = [$dbtype, $desc, $datadictid];
				$types = 'ssi';
			}
		}
		else {
			$sqlstring = "insert into data_dictionary (datadict_type, project_id, datadict_varname, datadict_desc, datadict_valuekey) values (?, ?, ?, ?, ?)";
			$params = [$dbtype, $projectid, $varname, $desc, ($setvaluekey ? $valuekey : null)];
			$types = 'sisss';
		}
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, $types, ...$params);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);
		return true;
	}


	/* -------------------------------------------- */
	/* ------- AddVariable ------------------------ */
	/* -------------------------------------------- */
	function AddVariable($projectid, $varname, $desc, $type) {
		if (trim((string)$varname) == "") {
			Error("Blank variable name");
			return;
		}
		if (SaveVariable($projectid, $varname, $type, $desc))
			Notice(htmlspecialchars(trim($varname)) . " added");
		else
			Error("Invalid variable type");
	}


	/* -------------------------------------------- */
	/* ------- UpdateVariables -------------------- */
	/* -------------------------------------------- */
	/* $descs and $types are keyed by datadict_id. Only this project's rows are changed */
	function UpdateVariables($projectid, $descs, $types, $deleteids) {
		$projectid = (int)$projectid;
		$descs = is_array($descs) ? $descs : array();
		$types = is_array($types) ? $types : array();
		$deleteids = is_array($deleteids) ? array_map('intval', $deleteids) : array();

		$updated = 0;
		$deleted = 0;
		foreach ($types as $id => $type) {
			$id = (int)$id;
			if ($id < 1) continue;

			if (in_array($id, $deleteids, true)) {
				$sqlstring = "delete from data_dictionary where datadict_id = ? and project_id = ?";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'ii', $id, $projectid);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id, $projectid]);
				$deleted += mysqli_stmt_affected_rows($stmt);
				mysqli_stmt_close($stmt);
				continue;
			}

			$dbtype = DataDictTypeToDB($type);
			if ($dbtype == "") continue;
			$desc = trim((string)($descs[$id] ?? ''));
			$sqlstring = "update data_dictionary set datadict_desc = ?, datadict_type = ? where datadict_id = ? and project_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'ssii', $desc, $dbtype, $id, $projectid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$desc, $dbtype, $id, $projectid]);
			mysqli_stmt_close($stmt);
			$updated++;
		}
		Notice("Data dictionary updated" . (($deleted > 0) ? ", $deleted variable(s) deleted" : ""));
	}


	/* -------------------------------------------- */
	/* ------- AddVariables ----------------------- */
	/* -------------------------------------------- */
	/* adds the selected uncategorized variables. $varnames/$descs/$types are keyed by row number, and
	   $ids is the list of selected row numbers */
	function AddVariables($projectid, $varnames, $descs, $types, $ids) {
		$varnames = is_array($varnames) ? $varnames : array();
		$descs = is_array($descs) ? $descs : array();
		$types = is_array($types) ? $types : array();
		$ids = is_array($ids) ? $ids : array();

		$added = array();
		foreach ($ids as $id) {
			$id = (int)$id;
			$varname = (string)($varnames[$id] ?? '');
			if (SaveVariable($projectid, $varname, $types[$id] ?? '', $descs[$id] ?? ''))
				$added[] = htmlspecialchars(trim($varname));
		}
		Notice(count($added) . " variable(s) added" . ((count($added) > 0) ? ": " . implode(", ", $added) : ""));
	}


	/* -------------------------------------------- */
	/* ------- AddCSV ----------------------------- */
	/* -------------------------------------------- */
	function AddCSV($projectid, $csv) {
		$data = ParseCSV((string)$csv);

		$added = 0;
		$problems = array();
		foreach ($data as $i => $row) {
			if (count($row) == 0) continue;   /* blank line */
			$line = $i + 2;   /* +1 for the header, +1 for 1-based numbering */
			$varname = trim((string)($row['variablename'] ?? ''));
			$vartype = trim((string)($row['type'] ?? ''));
			$vardesc = trim((string)($row['description'] ?? ''));
			$varkey = trim((string)($row['valuekey'] ?? ''));

			if ($varname == "") { $problems[] = "Line $line: variablename was blank"; continue; }
			if (DataDictTypeToDB($vartype) == "") { $problems[] = "Line $line: type must be observation or intervention"; continue; }
			if ($vardesc == "") $problems[] = "Line $line: description was blank";

			if (SaveVariable($projectid, $varname, $vartype, $vardesc, ($varkey == "") ? null : $varkey, true))
				$added++;
		}
		$msg = "$added variable(s) added or updated";
		if (count($problems) > 0)
			$msg .= "<br>" . implode("<br>", array_map('htmlspecialchars', $problems));
		Notice($msg);
	}


	/* -------------------------------------------- */
	/* ------- GetNameCounts ---------------------- */
	/* -------------------------------------------- */
	/* runs a "select name, count" query bound to $projectid; returns [name => count] */
	function GetNameCounts($sqlstring, $projectid) {
		$counts = array();
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			if ((string)$row['name'] != "")
				$counts[(string)$row['name']] = (int)$row['cnt'];
		}
		mysqli_stmt_close($stmt);
		return $counts;
	}


	/* -------------------------------------------- */
	/* ------- DisplayVariables ------------------- */
	/* -------------------------------------------- */
	/* this functon displays all variables found in
	   the project. Also displays any associated
	   information about variables, from the
	   data_dictionary table */
	function DisplayVariables($projectid) {
		$projectid = (int)$projectid;
		if ($projectid < 1) { Error("Invalid project ID"); return; }

		ShowFlashMessage();

		list($ismember, $canedit) = GetDataDictPerms($projectid);
		if (!$ismember) {
			Error("You do not have permission to view this project's data dictionary");
			return;
		}

		$datadictitems = array();
		/* get all items from data_dictionary table */
		$sqlstring = "select * from data_dictionary where project_id = ? order by datadict_varname";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$name = (string)$row['datadict_varname'];
			if (isset($datadictitems[$name])) continue;   /* older duplicates of the same name: show the first */
			$datadictitems[$name] = array('id' => (int)$row['datadict_id'], 'type' => DataDictTypeFromDB($row['datadict_type']), 'desc' => (string)$row['datadict_desc']);
		}
		mysqli_stmt_close($stmt);

		/* how many records of each variable name the project has, by kind */
		$obscounts = GetNameCounts("select a.observation_name name, count(*) cnt from observations a join enrollment b on a.enrollment_id = b.enrollment_id where b.project_id = ? group by a.observation_name", $projectid);
		$intcounts = GetNameCounts("select a.intervention_name name, count(*) cnt from interventions a join enrollment b on a.enrollment_id = b.enrollment_id where b.project_id = ? group by a.intervention_name", $projectid);
		$vitalcounts = DatabaseTableExists("vitals") ? GetNameCounts("select c.vital_name name, count(*) cnt from vitals a join enrollment b on a.enrollment_id = b.enrollment_id join vitalnames c on a.vitalname_id = c.vitalname_id where b.project_id = ? group by c.vital_name", $projectid) : array();
		$kinds = array('observation' => $obscounts, 'intervention' => $intcounts, 'vital' => $vitalcounts);

		$typelabels = array('observation' => 'Observation', 'vital' => 'Vital', 'intervention' => 'Intervention', 'other' => 'Other');

		/* start the table */
		StartHTMLTable(array("Variable", "Type", "Description", "Count", "Issues", ($canedit ? "Delete" : ""), ""), "ui table", "datadictionarytable");
		?>
		<tbody>
		<tr>
			<td colspan="8" style="background-color: #888; color: #fff; font-size: larger; padding:10px; font-weight: bold">Data Dictionary</td>
		</tr>
		<? if ($canedit) { ?>
		<form method="post" action="datadictionary.php">
		<input type="hidden" name="action" value="addvariable">
		<input type="hidden" name="projectid" value="<?=$projectid?>">
		<?=CSRFTokenField()?>
		<tr>
			<td style="border-bottom: solid #888 2px; padding: 6px;"><input type="text" name="varname" required></td>
			<td style="border-bottom: solid #888 2px; padding: 6px;">
				<select name="type" required>
					<option value="">(Select type)
					<option value="observation">Observation
					<option value="intervention">Intervention
					<option value="other">Other
				</select>
			</td>
			<td style="border-bottom: solid #888 2px; padding: 6px;"><input type="text" name="desc" required></td>
			<td style="border-bottom: solid #888 2px; padding: 6px;"></td>
			<td style="border-bottom: solid #888 2px; padding: 6px;"></td>
			<td style="border-bottom: solid #888 2px; padding: 6px;"></td>
			<td style="border-bottom: solid #888 2px; padding: 6px;"><input type="submit" value="Add Single Variable" class="ui primary button"></td>
		</tr>
		</form>

		<form method="post" action="datadictionary.php">
		<input type="hidden" name="action" value="addcsv">
		<input type="hidden" name="projectid" value="<?=$projectid?>">
		<?=CSRFTokenField()?>
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
			<?=CSRFTokenField()?>
		<? } ?>
		<?
		/* display the table rows */
		foreach ($datadictitems as $varname => $details) {
			$id = $details['id'];
			$type = $details['type'];
			$desc = $details['desc'];

			/* the count is the variable's records of its own kind; records of another kind are reported as issues */
			$count = ($type != "" && isset($kinds[$type])) ? ($kinds[$type][$varname] ?? 0) : '';
			$errors = array();
			if ($type != "other") {
				foreach ($kinds as $kind => $counts) {
					if (($kind != $type) && (($counts[$varname] ?? 0) > 0))
						$errors[] = "Data dictionary variable has " . $counts[$varname] . " entries mis-classified as $kind";
				}
			}
			?>
			<tr>
				<td><?=htmlspecialchars($varname)?></td>
				<td>
					<? if ($canedit) { ?>
					<select name="type[<?=$id?>]">
						<option value="" <? if (!isset($typelabels[$type])) { echo "selected"; } ?> >(Select type)
						<? foreach ($typelabels as $tkey => $tlabel) { ?>
						<option value="<?=$tkey?>" <? if ($type == $tkey) { echo "selected"; } ?> ><?=$tlabel?>
						<? } ?>
					</select>
					<? } else { echo htmlspecialchars($typelabels[$type] ?? ''); } ?>
				</td>
				<td><? if ($canedit) { ?><input type="text" name="desc[<?=$id?>]" value="<?=htmlspecialchars($desc)?>"><? } else { echo htmlspecialchars($desc); } ?></td>
				<td><?=$count?></td>
				<td>
				<? if (count($errors) > 0) { ?>
					<details style="font-size: smaller">
					<summary><?=count($errors)?> issue(s)</summary>
						<ul>
						<? foreach ($errors as $err) { echo "<li>" . htmlspecialchars($err) . "\n"; } ?>
						</ul>
					</details>
				<? } ?>
				</td>
				<td><? if ($canedit) { ?><input name="deleteids[]" type="checkbox" value="<?=$id?>"><? } ?></td>
				<td></td>
			</tr>
			<?
		}
		?>
		<? if ($canedit) { ?>
		<tr>
			<td colspan="8" align="right"><input type="submit" value="Update Data Dictionary" class="ui primary button"></td>
		</tr>
		</form>
		<? } ?>
		</tbody>
		<?
		/* end the data dictionary table */
		EndHTMLTable();
		?>
		<br>
		<?

		/* variables recorded in the project that aren't in the data dictionary */
		$dbitems = array();
		foreach ($kinds as $kind => $counts) {
			foreach ($counts as $name => $cnt) {
				if (!isset($datadictitems[$name]) && !isset($dbitems[$name]))
					$dbitems[$name] = array('type' => $kind, 'count' => $cnt);
			}
		}
		ksort($dbitems);

		StartHTMLTable(array("Variable", "Type", "Description", "Count", ($canedit ? "Add" : "")), "ui table", "datadictionarytable");
		?>
		<tr>
			<td colspan="7" style="background-color: #888; color: #fff; font-size: larger; padding:10px; font-weight: bold">Uncategorized Variables</td>
		</tr>
		<? if ($canedit) { ?>
		<form method="post" action="datadictionary.php">
		<input type="hidden" name="action" value="addvariables">
		<input type="hidden" name="projectid" value="<?=$projectid?>">
		<?=CSRFTokenField()?>
		<? } ?>
		<?
		$rownum = 0;
		foreach ($dbitems as $varname => $details) {
			$type = $details['type'];
			$count = $details['count'];
			?>
			<tr>
				<td><? if ($canedit) { ?><input type="hidden" name="varname[<?=$rownum?>]" value="<?=htmlspecialchars($varname)?>"><? } ?><?=htmlspecialchars($varname)?></td>
				<td><? if ($canedit) { ?><input type="hidden" name="type[<?=$rownum?>]" value="<?=$type?>"><? } ?><?=htmlspecialchars($typelabels[$type] ?? $type)?></td>
				<td><? if ($canedit) { ?><input type="text" name="desc[<?=$rownum?>]" value=""><? } ?></td>
				<td><?=(int)$count?></td>
				<td><? if ($canedit) { ?><input name="itemids[]" type="checkbox" value="<?=$rownum?>"><? } ?></td>
			</tr>
			<?
			$rownum++;
		}
		?>
		<? if ($canedit) { ?>
		<tr>
			<td colspan="7" align="right">
				<b>With Selected Variables:</b><br>
				<input type="submit" value="Add to Data Dictionary" class="ui primary button">
				<br>
			</td>
		</tr>
		</form>
		<? } ?>
		</tbody>
		<?
		/* end the table */
		EndHTMLTable();
	}
?>
<? include("footer.php") ?>
