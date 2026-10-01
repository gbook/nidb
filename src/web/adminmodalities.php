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

	if (!isAdmin()) {
		Error("This account does not have permissions to view this page");
	}
	else {
		/* ----- setup variables ----- */
		$action = GetVariable("action");
		$id = (int)GetVariable("id");
		
		/* determine action */
		switch ($action) {
			/* mutating actions use POST/Redirect/GET so a refresh/Back doesn't re-run them */
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
		<h2 class="ui header">Modalities</h2>
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
						<td colspan="2" align="center" style="color: gray">No table</td>
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
