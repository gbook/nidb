<?
 // ------------------------------------------------------------------------------
 // NiDB adminmodalities.php
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
	ob_start(); /* buffer output for POST/Redirect/GET (see functions.php RedirectTo/ShowFlashMessage) */
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Manage Modalities</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	/* admin.php shows the Modalities link to siteadmins, so let them in too. Adding a modality is siteadmin-only */
	if (!isAdmin() && !isSiteAdmin()) {
		Error("This account does not have permissions to view this page");
	}
	else {
		/* ----- setup variables ----- */
		$action = GetVariable("action");
		$id = (int)GetVariable("id");
		$modcode = GetVariable("modcode");
		$moddesc = GetVariable("moddesc");
		$customcols = array(
			'name' => GetVariable("colname"),
			'type' => GetVariable("coltype"),
			'length' => GetVariable("collength"),
			'default' => GetVariable("coldefault"),
			'defaulttype' => GetVariable("coldefaulttype"),
			'null' => GetVariable("colnull"),
			'comment' => GetVariable("colcomment")
		);

		/* determine action */
		switch ($action) {
			case 'addform':
				if (!isSiteAdmin()) { Error("This account does not have permissions to perform this action"); break; }
				DisplayAddForm();
				break;
			case 'createsql':
				if (!isSiteAdmin()) { Error("This account does not have permissions to perform this action"); break; }
				DisplayCreateTableSQL($id, $customcols);
				break;
			/* mutating actions use POST/Redirect/GET so a refresh/Back doesn't re-run them */
			case 'add':
				if (!isSiteAdmin() || ($_SERVER['REQUEST_METHOD'] != 'POST')) { Error("This account does not have permissions to perform this action"); break; }
				ob_start();
				$newid = AddModality($modcode, $moddesc);
				$_SESSION['flash'] = ob_get_clean();
				if ($newid > 0)
					RedirectTo("adminmodalities.php?action=createsql&id=$newid");
				else
					RedirectTo("adminmodalities.php?action=addform");
				break;
			case 'disable':
				ob_start();
				DisableModality($id);
				$_SESSION['flash'] = ob_get_clean();
				RedirectTo("adminmodalities.php");
				break;
			case 'enable':
				ob_start();
				EnableModality($id);
				$_SESSION['flash'] = ob_get_clean();
				RedirectTo("adminmodalities.php");
				break;
			case 'edit':
				EditModality($id);
				break;
			default:
				DisplayModalityList();
		}
	}	
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- GetModalityCode -------------------- */
	/* -------------------------------------------- */
	/* returns the lowercase mod_code for a mod_id, or '' if not found */
	function GetModalityCode($id) {
		$sqlstring = "select mod_code from modalities where mod_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		return strtolower($row['mod_code'] ?? '');
	}


	/* -------------------------------------------- */
	/* ------- IsValidModalityCode ---------------- */
	/* -------------------------------------------- */
	/* the code becomes part of identifiers (<code>_series, <code>series_id), so letters and digits only.
	   mod_code is varchar(15) */
	function IsValidModalityCode($code) {
		return (preg_match('/^[A-Za-z][A-Za-z0-9]{0,14}$/', $code ?? '') === 1);
	}


	/* -------------------------------------------- */
	/* ------- SeriesTableExists ------------------ */
	/* -------------------------------------------- */
	function SeriesTableExists($code) {
		$tablename = strtolower($code) . "_series";
		$sqlstring = "select count(*) 'count' from information_schema.tables where table_schema = database() and table_name = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 's', $tablename);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$tablename]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		return (($row['count'] ?? 0) > 0);
	}


	/* -------------------------------------------- */
	/* ------- AddModality ------------------------ */
	/* -------------------------------------------- */
	/* returns the new mod_id, or 0 on error. The <code>_series table is not created here;
	   the siteadmin creates it from the SQL shown by DisplayCreateTableSQL() */
	function AddModality($code, $desc) {
		$code = strtoupper(trim($code ?? ''));
		$desc = trim($desc ?? '');

		if (!IsValidModalityCode($code)) {
			Error("Invalid modality code <tt>" . htmlspecialchars($code) . "</tt>. Use 1 to 15 letters and digits, starting with a letter");
			return 0;
		}
		/* these *_series tables exist but are not modality data tables */
		if (in_array($code, array('UPLOAD', 'PACKAGE'))) {
			Error("<tt>" . htmlspecialchars($code) . "</tt> is reserved. Choose a different modality code");
			return 0;
		}
		if (($desc == '') || (strlen($desc) > 255)) {
			Error("Description is required and must be 255 characters or less");
			return 0;
		}

		/* mod_code is unique, so check first to give a friendly message instead of an SQL error */
		$sqlstring = "select mod_id from modalities where mod_code = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 's', $code);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$code]);
		$numrows = mysqli_num_rows($result);
		mysqli_stmt_close($stmt);
		if ($numrows > 0) {
			Error("Modality <tt>" . htmlspecialchars($code) . "</tt> already exists");
			return 0;
		}

		$sqlstring = "insert into modalities (mod_code, mod_desc, mod_enabled) values (?, ?, 1)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ss', $code, $desc);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$code, $desc]);
		mysqli_stmt_close($stmt);
		$newid = mysqli_insert_id($GLOBALS['linki']);

		Notice("Modality <b>" . htmlspecialchars($code) . "</b> added");
		return $newid;
	}


	/* -------------------------------------------- */
	/* ------- SeriesTableCoreColumns ------------- */
	/* -------------------------------------------- */
	/* columns every <code>_series table gets: the columns the generic (non modality-specific) web and
	   C++ code paths read and write. column name => definition */
	function SeriesTableCoreColumns($code) {
		$t = strtolower($code);
		return array(
			"{$t}series_id" => "int(11) NOT NULL AUTO_INCREMENT",
			"study_id" => "int(11) DEFAULT NULL",
			"series_num" => "int(11) DEFAULT NULL",
			"series_desc" => "varchar(255) DEFAULT NULL",
			"series_altdesc" => "varchar(255) DEFAULT NULL",
			"series_protocol" => "varchar(255) DEFAULT NULL",
			"series_datetime" => "datetime DEFAULT NULL",
			"series_numfiles" => "int(11) NOT NULL DEFAULT 0 COMMENT 'total number of files'",
			"series_size" => "double NOT NULL DEFAULT 0 COMMENT 'size of all the files'",
			"series_notes" => "longtext DEFAULT NULL",
			"series_duration" => "bigint(20) DEFAULT NULL",
			"series_createdby" => "varchar(50) DEFAULT NULL",
			"ishidden" => "tinyint(1) NOT NULL DEFAULT 0",
			"lastupdate" => "timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()"
		);
	}


	/* -------------------------------------------- */
	/* ------- GenerateSeriesTableSQL ------------- */
	/* -------------------------------------------- */
	/* SQL for a new <code>_series table. $customlines are column definitions from ParseCustomColumns(),
	   added after the core columns. $code must already be validated with IsValidModalityCode() */
	function GenerateSeriesTableSQL($code, $customlines = array()) {
		$t = strtolower($code);
		$lines = array();
		foreach (SeriesTableCoreColumns($code) as $name => $def) {
			$lines[] = "  `$name` $def";
		}
		foreach ($customlines as $line) {
			$lines[] = "  $line";
		}
		$lines[] = "  PRIMARY KEY (`{$t}series_id`)";
		$lines[] = "  KEY `study_id` (`study_id`, `series_num`)";
		$lines[] = "  KEY `ishidden` (`ishidden`)";
		return "CREATE TABLE `{$t}_series` (\n" . implode(",\n", $lines) . "\n) ENGINE=Aria DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";
	}


	/* -------------------------------------------- */
	/* ------- CustomColumnTypes ------------------ */
	/* -------------------------------------------- */
	/* column types allowed for custom columns. 'length': required (1..max), decimal (M or M,D), or none.
	   'kind' decides how a default value is validated and quoted */
	function CustomColumnTypes() {
		return array(
			'varchar' =>    array('length' => 'required', 'max' => 16383, 'kind' => 'string'),
			'char' =>       array('length' => 'required', 'max' => 255, 'kind' => 'string'),
			'text' =>       array('length' => 'none', 'kind' => 'text'),
			'mediumtext' => array('length' => 'none', 'kind' => 'text'),
			'longtext' =>   array('length' => 'none', 'kind' => 'text'),
			'tinyint' =>    array('length' => 'none', 'kind' => 'int'),
			'smallint' =>   array('length' => 'none', 'kind' => 'int'),
			'int' =>        array('length' => 'none', 'kind' => 'int'),
			'bigint' =>     array('length' => 'none', 'kind' => 'int'),
			'float' =>      array('length' => 'none', 'kind' => 'float'),
			'double' =>     array('length' => 'none', 'kind' => 'float'),
			'decimal' =>    array('length' => 'decimal', 'kind' => 'float'),
			'date' =>       array('length' => 'none', 'kind' => 'date'),
			'datetime' =>   array('length' => 'none', 'kind' => 'datetime'),
			'time' =>       array('length' => 'none', 'kind' => 'time')
		);
	}


	/* -------------------------------------------- */
	/* ------- SQLStringLiteral ------------------- */
	/* -------------------------------------------- */
	/* quote a value as an SQL string literal for generated (displayed, not executed) SQL */
	function SQLStringLiteral($s) {
		return "'" . str_replace(array("\\", "'"), array("\\\\", "''"), $s) . "'";
	}


	/* -------------------------------------------- */
	/* ------- PostArrayString -------------------- */
	/* -------------------------------------------- */
	/* element $i of a posted array as a trimmed string, or '' if missing or not a string */
	function PostArrayString($arr, $i) {
		if (is_array($arr) && isset($arr[$i]) && is_string($arr[$i]))
			return trim($arr[$i]);
		return '';
	}


	/* -------------------------------------------- */
	/* ------- ParseCustomColumns ----------------- */
	/* -------------------------------------------- */
	/* validate the posted custom column definitions. Returns array($rows, $lines, $errors):
	   $rows are the cleaned values (to redisplay the form), $lines the SQL column definitions,
	   $errors HTML-safe error messages. Completely blank rows are ignored */
	function ParseCustomColumns($code, $cols) {
		$rows = array();
		$lines = array();
		$errors = array();

		$names = $cols['name'] ?? null;
		if (!is_array($names)) return array($rows, $lines, $errors);
		if (count($names) > 50) {
			$errors[] = "At most 50 custom columns can be added";
			return array($rows, $lines, $errors);
		}

		$types = CustomColumnTypes();
		$reserved = array_keys(SeriesTableCoreColumns($code));
		$seen = array();

		foreach (array_keys($names) as $i) {
			$row = array(
				'name' => strtolower(PostArrayString($cols['name'], $i)),
				'type' => strtolower(PostArrayString($cols['type'] ?? null, $i)),
				'length' => PostArrayString($cols['length'] ?? null, $i),
				'default' => PostArrayString($cols['default'] ?? null, $i),
				'defaulttype' => PostArrayString($cols['defaulttype'] ?? null, $i),
				'null' => (PostArrayString($cols['null'] ?? null, $i) == 'yes') ? 'yes' : 'no',
				'comment' => PostArrayString($cols['comment'] ?? null, $i)
			);
			if (($row['name'] == '') && ($row['length'] == '') && ($row['default'] == '') && ($row['comment'] == ''))
				continue;
			/* default type is none, null, or value (a value, which can be an empty string for string types) */
			if (!in_array($row['defaulttype'], array('none', 'null', 'value')))
				$row['defaulttype'] = ($row['default'] == '') ? 'none' : 'value';
			$rows[] = $row;

			$label = "Column " . count($rows) . ($row['name'] != '' ? " <tt>" . htmlspecialchars($row['name']) . "</tt>" : "");
			$numerrors = count($errors);

			/* name */
			if (!preg_match('/^[a-z][a-z0-9_]{0,63}$/', $row['name']))
				$errors[] = "$label: name must start with a letter and contain only letters, digits, and underscores (64 characters max)";
			elseif (in_array($row['name'], $reserved))
				$errors[] = "$label: <tt>" . htmlspecialchars($row['name']) . "</tt> is a standard column and is already in the table";
			elseif (isset($seen[$row['name']]))
				$errors[] = "$label: duplicate column name";
			$seen[$row['name']] = true;

			/* type and length */
			if (!isset($types[$row['type']])) {
				$errors[] = "$label: invalid type";
				continue;
			}
			$type = $types[$row['type']];
			$typesql = $row['type'];
			if ($type['length'] == 'required') {
				if (!ctype_digit($row['length']) || ((int)$row['length'] < 1) || ((int)$row['length'] > $type['max']))
					$errors[] = "$label: <tt>{$row['type']}</tt> requires a length from 1 to {$type['max']}";
				else
					$typesql .= "(" . (int)$row['length'] . ")";
			}
			elseif ($type['length'] == 'decimal') {
				if ($row['length'] != '') {
					if (preg_match('/^(\d{1,2})(?:,(\d{1,2}))?$/', $row['length'], $m) && ((int)$m[1] >= 1) && ((int)$m[1] <= 65) && ((int)($m[2] ?? 0) <= min(30, (int)$m[1])))
						$typesql .= "(" . (int)$m[1] . (isset($m[2]) ? "," . (int)$m[2] : "") . ")";
					else
						$errors[] = "$label: decimal length must be <tt>M</tt> or <tt>M,D</tt> (M 1 to 65, D 0 to 30 and not more than M)";
				}
			}
			elseif ($row['length'] != '') {
				$errors[] = "$label: <tt>{$row['type']}</tt> does not take a length";
			}

			/* default */
			$defaultsql = "";
			$d = $row['default'];
			if ($row['defaulttype'] == 'none') {
				/* no DEFAULT clause */
			}
			elseif ($row['defaulttype'] == 'null') {
				if ($row['null'] == 'yes') $defaultsql = " DEFAULT NULL";
				else $errors[] = "$label: a NULL default requires the column to allow NULL";
			}
			elseif (($d == '') && !in_array($type['kind'], array('string', 'text'))) {
				$errors[] = "$label: enter a default value, or choose a default of None or NULL";
			}
			else {
				switch ($type['kind']) {
					case 'int':
						if (preg_match('/^-?\d+$/', $d)) $defaultsql = " DEFAULT $d";
						else $errors[] = "$label: default must be a whole number";
						break;
					case 'float':
						if (is_numeric($d)) $defaultsql = " DEFAULT $d";
						else $errors[] = "$label: default must be a number";
						break;
					case 'string':
						if (mb_strlen($d) <= (int)$row['length']) $defaultsql = " DEFAULT " . SQLStringLiteral($d);
						else $errors[] = "$label: default is longer than the column length";
						break;
					case 'text':
						$defaultsql = " DEFAULT " . SQLStringLiteral($d);
						break;
					case 'date':
						if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) $defaultsql = " DEFAULT " . SQLStringLiteral($d);
						else $errors[] = "$label: default must be a date as <tt>YYYY-MM-DD</tt>";
						break;
					case 'datetime':
						if (strtoupper($d) == 'CURRENT_TIMESTAMP') $defaultsql = " DEFAULT current_timestamp()";
						elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $d)) $defaultsql = " DEFAULT " . SQLStringLiteral($d);
						else $errors[] = "$label: default must be <tt>YYYY-MM-DD HH:MM:SS</tt> or <tt>CURRENT_TIMESTAMP</tt>";
						break;
					case 'time':
						if (preg_match('/^-?\d{1,3}:\d{2}:\d{2}$/', $d)) $defaultsql = " DEFAULT " . SQLStringLiteral($d);
						else $errors[] = "$label: default must be a time as <tt>HH:MM:SS</tt>";
						break;
				}
			}

			/* comment */
			$commentsql = "";
			if (mb_strlen($row['comment']) > 1024)
				$errors[] = "$label: comment must be 1024 characters or less";
			elseif ($row['comment'] != '')
				$commentsql = " COMMENT " . SQLStringLiteral($row['comment']);

			if (count($errors) == $numerrors)
				$lines[] = "`{$row['name']}` $typesql " . ($row['null'] == 'yes' ? "NULL" : "NOT NULL") . $defaultsql . $commentsql;
		}

		return array($rows, $lines, $errors);
	}


	/* -------------------------------------------- */
	/* ------- DisplayCustomColumnRow ------------- */
	/* -------------------------------------------- */
	function DisplayCustomColumnRow($row) {
		?>
		<tr>
			<td><input type="text" name="colname[]" value="<?=htmlspecialchars($row['name'])?>" maxlength="64" pattern="[A-Za-z][A-Za-z0-9_]{0,63}" title="Letters, digits, and underscores, starting with a letter"></td>
			<td>
				<select name="coltype[]">
					<? foreach (array_keys(CustomColumnTypes()) as $t) { ?>
					<option value="<?=$t?>" <?=($t == $row['type']) ? "selected" : ""?>><?=$t?></option>
					<? } ?>
				</select>
			</td>
			<td><input type="text" name="collength[]" value="<?=htmlspecialchars($row['length'])?>" size="6" placeholder="e.g. 255"></td>
			<td>
				<select name="coldefaulttype[]" onChange="this.nextElementSibling.readOnly = (this.value != 'value');">
					<option value="none" <?=($row['defaulttype'] == 'none') ? "selected" : ""?>>None</option>
					<option value="null" <?=($row['defaulttype'] == 'null') ? "selected" : ""?>>NULL</option>
					<option value="value" <?=($row['defaulttype'] == 'value') ? "selected" : ""?>>Value</option>
				</select><input type="text" name="coldefault[]" value="<?=htmlspecialchars($row['default'])?>" <?=($row['defaulttype'] != 'value') ? "readonly" : ""?> placeholder="value">
			</td>
			<td>
				<select name="colnull[]">
					<option value="no" <?=($row['null'] != 'yes') ? "selected" : ""?>>NOT NULL</option>
					<option value="yes" <?=($row['null'] == 'yes') ? "selected" : ""?>>NULL</option>
				</select>
			</td>
			<td><input type="text" name="colcomment[]" value="<?=htmlspecialchars($row['comment'])?>" maxlength="1024"></td>
			<td><button class="ui small basic icon button" title="Remove column" onClick="$(this).closest('tr').remove(); return false;"><i class="trash icon"></i></button></td>
		</tr>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayAddForm --------------------- */
	/* -------------------------------------------- */
	function DisplayAddForm() {

		ShowFlashMessage(); /* show any error from a failed add (PRG) */
		?>
		<div class="ui text container">
			<h2 class="ui header">Add modality</h2>
			<form method="post" action="adminmodalities.php" class="ui form raised segment">
				<input type="hidden" name="action" value="add">
				<div class="field">
					<label>Modality code</label>
					<input type="text" name="modcode" maxlength="15" pattern="[A-Za-z][A-Za-z0-9]{0,14}" required placeholder="e.g. FNIRS" title="1 to 15 letters and digits, starting with a letter">
					<span class="tiny">Letters and digits only, starting with a letter. Stored in uppercase. The data table will be named <tt>&lt;code&gt;_series</tt></span>
				</div>
				<div class="field">
					<label>Description</label>
					<input type="text" name="moddesc" maxlength="255" required placeholder="e.g. Functional near-infrared spectroscopy">
				</div>
				<div class="ui info message">
					Adding a modality does not create its data table. The next page lets you add custom columns and shows the SQL to create the table, which must be run manually by a database administrator.
				</div>
				<button class="ui button" onClick="window.location.href='adminmodalities.php'; return false;">Cancel</button>
				<input type="submit" class="ui primary button" value="Add">
			</form>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayCreateTableSQL -------------- */
	/* -------------------------------------------- */
	/* $customcols are the posted custom column arrays (name, type, length, default, null, comment).
	   This page only generates SQL, so the custom column form posts back here without a redirect */
	function DisplayCreateTableSQL($id, $customcols) {

		ShowFlashMessage(); /* show the message from AddModality() (PRG) */

		$code = GetModalityCode($id);
		if ($code == '') { Error("Unknown modality"); return; }
		if (!IsValidModalityCode($code)) { Error("Modality code <tt>" . htmlspecialchars($code) . "</tt> cannot be used as a table name"); return; }
		$tablename = $code . "_series";
		?>
		<div class="ui container">
			<div class="ui two column grid">
				<div class="column">
					<h2 class="ui header">Create table <tt><?=htmlspecialchars($tablename)?></tt></h2>
				</div>
				<div class="column" style="text-align: right">
					<button class="ui button primary" onClick="window.location.href='adminmodalities.php'; return false;">Back</button>
				</div>
			</div>
		<?
		/* columns can only be defined before the table exists */
		if (SeriesTableExists($code)) {
			Notice("Table <tt>" . htmlspecialchars($tablename) . "</tt> already exists, so columns can no longer be added here. <a href='adminmodalities.php?action=edit&id=" . (int)$id . "'>View its schema</a>", "Nothing to do");
			?></div><?
			return;
		}

		list($rows, $customlines, $errors) = ParseCustomColumns($code, $customcols);
		?>
			<div class="ui warning message">
				<div class="header">This table must be created manually</div>
				Run the SQL below against the NiDB database as a database user with the CREATE privilege, for example in phpMyAdmin or the <tt>mysql</tt> command line client.
				Until the table exists, series of this modality cannot be stored.
			</div>

			<form method="post" action="adminmodalities.php" class="ui form segment">
				<input type="hidden" name="action" value="createsql">
				<input type="hidden" name="id" value="<?=(int)$id?>">
				<h3 class="ui header">
					Custom columns
					<div class="sub header">Optional. Added after the standard series columns. These are not saved; they only become part of the SQL below. Click <i>Update SQL</i> after making changes</div>
				</h3>
				<table class="ui very compact celled table" id="customcols">
					<thead>
						<tr>
							<th>Name</th>
							<th>Type</th>
							<th>Length<br><span class="tiny">varchar, char, decimal (M,D)</span></th>
							<th>Default</th>
							<th>Null</th>
							<th>Comment</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<? foreach ($rows as $row) { DisplayCustomColumnRow($row); } ?>
					</tbody>
				</table>
				<template id="customcolrow">
					<? DisplayCustomColumnRow(array('name' => '', 'type' => 'varchar', 'length' => '255', 'default' => '', 'defaulttype' => 'none', 'null' => 'yes', 'comment' => '')); ?>
				</template>
				<button class="ui small button" onClick="document.querySelector('#customcols tbody').appendChild(document.getElementById('customcolrow').content.cloneNode(true)); return false;"><i class="plus icon"></i> Add column</button>
				<input type="submit" class="ui small primary button" value="Update SQL">
			</form>

			<?
			if (count($errors) > 0) {
				Error("Fix these custom column errors to see the SQL<ul><li>" . implode("</li><li>", $errors) . "</li></ul>", false);
			}
			else {
				?>
				<div class="ui segment">
					<button class="ui right floated small basic button" onClick="navigator.clipboard.writeText(document.getElementById('createsql').textContent); $(this).text('Copied'); return false;"><i class="copy icon"></i> Copy</button>
					<pre id="createsql" style="margin: 0"><?=htmlspecialchars(GenerateSeriesTableSQL($code, $customlines))?></pre>
				</div>
				<?
			}
			?>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- EditModality ----------------------- */
	/* -------------------------------------------- */
	function EditModality($id) {
	
		$modality = GetModalityCode($id);
		if ($modality == '') { Error("Unknown modality"); return; }
		$tablename = $modality . "_series";

		/* information_schema instead of SHOW COLUMNS: it can be bound, and a missing table returns no rows instead of an SQL error */
		$sqlstring = "select column_name, column_type, is_nullable, column_key, column_default, extra from information_schema.columns where table_schema = database() and table_name = ? order by ordinal_position";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 's', $tablename);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$tablename]);
		mysqli_stmt_close($stmt);

		?>
		<div class="ui container">
			<div class="ui two column grid">
				<div class="column">
					<h2 class="ui header"><tt><?=htmlspecialchars($tablename)?></tt> SQL Schema</h2>
				</div>
				<div class="column" style="text-align: right">
					<button class="ui button primary" onClick="window.location.href='adminmodalities.php'; return false;">Back</button>
				</div>
			</div>
		<?
		if (mysqli_num_rows($result) < 1) {
			Error("Table <tt>" . htmlspecialchars($tablename) . "</tt> does not exist", false);
			?></div><?
			return;
		}
		?>
			<table class="ui small celled selectable grey very compact table">
				<thead>
					<tr>
						<th>Field</th>
						<th>Type</th>
						<th>Null</th>
						<th>Key</th>
						<th>Default</th>
						<th>Extra</th>
					</tr>
				</thead>
				<tbody>
				<?
				while ($row = mysqli_fetch_row($result)) {
					/* gray out primary key columns */
					$style = ($row[3] == "PRI") ? ' style="color:gray"' : '';
					?><tr><?
					foreach ($row as $cell) {
						?><td<?=$style?>><?=htmlspecialchars($cell ?? '')?></td><?
					}
					?></tr>
					<?
				}
				?>
				</tbody>
			</table>
		</div>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- EnableModality --------------------- */
	/* -------------------------------------------- */
	function EnableModality($id) {
		$sqlstring = "update modalities set mod_enabled = 1 where mod_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
		mysqli_stmt_close($stmt);

		Notice(htmlspecialchars(strtoupper(GetModalityCode($id))) . " enabled");
	}


	/* -------------------------------------------- */
	/* ------- DisableModality -------------------- */
	/* -------------------------------------------- */
	function DisableModality($id) {
		$sqlstring = "update modalities set mod_enabled = 0 where mod_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
		mysqli_stmt_close($stmt);

		Notice(htmlspecialchars(strtoupper(GetModalityCode($id))) . " disabled");
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayModalityList ---------------- */
	/* -------------------------------------------- */
	function DisplayModalityList() {

		ShowFlashMessage(); /* show any message from a mutating action that redirected here (PRG) */

		$issiteadmin = isSiteAdmin(); /* adding modalities is siteadmin-only */

		/* get the size of all *_series tables in one query. information_schema instead of SHOW TABLE STATUS,
		   whose LIKE pattern treats '_' as a wildcard. A modality without a table simply has no entry */
		$tableinfo = array();
		$sqlstring = "select table_name 'tablename', table_rows 'tablerows', data_length 'datalength', index_length 'indexlength' from information_schema.tables where table_schema = database() and table_name like '%\\\\_series'";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$tableinfo[strtolower($row['tablename'])] = $row;
		}
	?>

	<div class="ui container">
		<div class="ui two column grid">
			<div class="column">
				<h2 class="ui header">Modalities</h2>
			</div>
			<div class="column" style="text-align: right">
				<? if ($issiteadmin) { ?>
				<a href="adminmodalities.php?action=addform" class="ui primary button"><i class="plus icon"></i> Add modality</a>
				<? } ?>
			</div>
		</div>
		<table class="ui small celled selectable grey very compact table">
		<thead>
			<tr>
				<th>Name<br><span class="tiny">View table schema</span></th>
				<th>Description</th>
				<th>Rows</th>
				<th>Table size<br><span class="tiny">(data + index)</span></th>
				<th>Enable/Disable</th>
			</tr>
		</thead>
		<tbody>
			<?
				$sqlstring = "select * from modalities order by mod_code";
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$id = $row['mod_id'];
					$name = $row['mod_code'];
					$desc = $row['mod_desc'];
					$enabled = $row['mod_enabled'];
					
					/* calculate the status color */
					if (!$enabled) { $color = "gray"; }
					else { $color = "black"; }
					
					/* get information about the modality table */
					$info = $tableinfo[strtolower($name) . "_series"] ?? null;
					?>
					<tr style="color: <?=$color?>">
						<td><a href="adminmodalities.php?action=edit&id=<?=$id?>"><?=htmlspecialchars($name)?></a></td>
						<td><?=htmlspecialchars($desc)?></td>
						<? if ($info === null) { ?>
						<td colspan="2" align="center" style="color: gray">No table<? if ($issiteadmin && IsValidModalityCode($name)) { ?> &nbsp; <a href="adminmodalities.php?action=createsql&id=<?=$id?>">Show create SQL</a><? } ?></td>
						<? } else { ?>
						<td align="right"><?=number_format((int)$info['tablerows'])?></td>
						<td align="right"><?=number_format((int)$info['datalength'] + (int)$info['indexlength'])?></td>
						<? } ?>
						<td>
							<?
								if ($enabled) {
									?><a href="adminmodalities.php?action=disable&id=<?=$id?>" title="<b>Enabled.</b> Click to disable"><i class="large green toggle on icon"></i></a><?
								}
								else {
									?><a href="adminmodalities.php?action=enable&id=<?=$id?>" title="<b>Disabled.</b> Click to enable"><i class="large red toggle off icon"></i></a><?
								}
							?>
						</td>
					</tr>
					<? 
				}
			?>
		</tbody>
	</table>
	</div>
	<?
	}
?>


<? include("footer.php") ?>
