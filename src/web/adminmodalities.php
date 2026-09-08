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
		$id = GetVariable("id");
		$protocols = GetVariable("protocols");
		$thegroup = GetVariable("thegroup");
		$modality = GetVariable("modality");
		$pgitemid = GetVariable("pgitemid");
		
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
			case 'editprotocolgroups':
				EditProtocolGroups($id,$modality);
				break;
			case 'updateprotocolgroup':
				ob_start();
				UpdateProtocolGroup($protocols, $thegroup, $modality);
				$_SESSION['flash'] = ob_get_clean();
				RedirectTo("adminmodalities.php?action=editprotocolgroups&modality=" . urlencode($modality));
				break;
			case 'deleteprotocolgroupitem':
				ob_start();
				DeleteProtocolGroupItem($pgitemid);
				$_SESSION['flash'] = ob_get_clean();
				RedirectTo("adminmodalities.php?action=editprotocolgroups&modality=" . urlencode($modality));
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
	/* ------- Updatemodality --------------------- */
	/* -------------------------------------------- */
	function Updatemodality($id, $modalityname, $modalitydesc, $admin) {
		/* update the modality */
		$sqlstring = "update modalities set modality_name = ?, modality_desc = ?, modality_admin = ? where modality_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'sssi', $modalityname, $modalitydesc, $admin, $id);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$modalityname, $modalitydesc, $admin, $id]);
		mysqli_stmt_close($stmt);

		?><div align="center"><span class="message"><?=htmlspecialchars($modalityname)?> updated</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- Addmodality ------------------------ */
	/* -------------------------------------------- */
	function Addmodality($modalityname, $modalitydesc, $admin) {
		/* insert the new modality */
		$sqlstring = "insert into modalities (modality_name, modality_desc, modality_admin, modality_createdate, modality_status) values (?, ?, ?, now(), 'active')";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'sss', $modalityname, $modalitydesc, $admin);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$modalityname, $modalitydesc, $admin]);
		mysqli_stmt_close($stmt);

		?><div align="center"><span class="message"><?=htmlspecialchars($modalityname)?> added</span></div><br><br><?
	}

	
	/* -------------------------------------------- */
	/* ------- EditModality ----------------------- */
	/* -------------------------------------------- */
	function EditModality($id) {
	
		$sqlstring = "select mod_code from modalities where mod_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		$modality = strtolower($row['mod_code'] ?? '');
		if ($modality == '') { Error("Unknown modality"); return; }

		/* $modality is a DB-derived table name (an identifier), so it can't be bound */
		$sqlstring = "show columns from $modality" . "_series";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		$fields_num = mysqli_num_fields($result);

		?>
		<div class="ui container">
			<div class="ui two column grid">
				<div class="column">
					<h2 class="ui header"><tt><?=$modality?>_series</tt> SQL Schema</h2>
				</div>
				<div class="column" style="text-align: right">
					<button class="ui button primary" onClick="window.location.href='adminmodalities.php'; return false;">Back</button>
				</div>
			</div>
			<table class="ui small celled selectable grey very compact table">
			<thead>
			<tr>
		<?
		// printing table headers
		for($i=0; $i<$fields_num; $i++)
		{
			$field = mysqli_fetch_field($result);
			$fieldname = $field->name;
			?>
			<th><?=$fieldname?></th>
			<?
		}
		?>
			</thead>
		<?
		if (mysqli_num_rows($result) > 0) {
			// printing table rows
			while($row = mysqli_fetch_row($result))
			{
				echo "<tr>";

				// $row is array... foreach( .. ) puts every element
				// of $row to $cell variable
				//print_r($row);
				foreach($row as $cell)
					if ($row[3] == "PRI") {	?>
						<td style="color:gray"><?=$cell?></td>
					<? }
					else {
						echo "<td>$cell</td>";
					}
				echo "</tr>\n";
			}
			echo "</table>";
			
			/* reset the pointer so not to confuse any subsequent data access */
			mysqli_data_seek($result, 0);
		}
		else {
			echo "</table>";
		}
		
		
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
	}

	
	/* -------------------------------------------- */
	/* ------- DeleteProtocolGroupItem ------------ */
	/* -------------------------------------------- */
	function DeleteProtocolGroupItem($pgitemid) {
		$sqlstring = "delete from protocolgroup_items where pgitem_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $pgitemid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$pgitemid]);
		mysqli_stmt_close($stmt);
	}

	
	/* -------------------------------------------- */
	/* ------- UpdateProtocolGroup ---------------- */
	/* -------------------------------------------- */
	function UpdateProtocolGroup($protocols, $thegroup, $modality) {
		$modality = strtoupper($modality);

		/* find (or create) the protocol group */
		$sqlstring = "select * from protocol_group where protocolgroup_name = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 's', $thegroup);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$thegroup]);
		$numrows = mysqli_num_rows($result);
		mysqli_stmt_close($stmt);
		if ($numrows > 0) {
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$protocolgroupid = $row['protocolgroup_id'];
		}
		else {
			$sqlstring = "insert into protocol_group (protocolgroup_name, protocolgroup_modality) values (?, ?)";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'ss', $thegroup, $modality);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$thegroup, $modality]);
			mysqli_stmt_close($stmt);
			$protocolgroupid = mysqli_insert_id($GLOBALS['linki']);
		}

		/* add each selected protocol to the group */
		if (is_array($protocols)) {
			foreach ($protocols as $protocol) {
				$sqlstring = "insert ignore into protocolgroup_items (protocolgroup_id, pgitem_protocol) values (?, ?)";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'is', $protocolgroupid, $protocol);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$protocolgroupid, $protocol]);
				mysqli_stmt_close($stmt);
			}
		}
	}
	
	
	/* -------------------------------------------- */
	/* ------- EditProtocolGroups ----------------- */
	/* -------------------------------------------- */
	function EditProtocolGroups($id, $modality) {

		ShowFlashMessage(); /* show any message from a mutating action that redirected here (PRG) */

		$sqlstring = "select * from modalities where mod_id = ? or mod_code = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ss', $id, $modality);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id, $modality]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		$modality = strtolower($row['mod_code'] ?? '');
		if ($modality == '') { Error("Unknown modality"); return; }

		$rows = array();
		?>
		<div align="center" style="font-weight: bold" width="100%">Protocol Groups for <span style="color:darkblue"><?=strtoupper($modality)?></span></div>
		<br>
		<table>
			<tr>
				<td valign="top">
					<form action="adminmodalities.php" method="post">
					<input type="hidden" name="action" value="updateprotocolgroup">
					<input type="hidden" name="modality" value="<?=$modality?>">
					<table class="ui very compact celled grey table">
						<thead>
							<tr>
								<th>Series description</th>
								<th>Count</th>
								<th>Add to group</th>
							</tr>
						</thead>
						<tbody>
							<datalist id="protocolgroups">
							<?
							$sqlstring = "select distinct(`protocolgroup_name`) 'group' from protocol_group where protocolgroup_modality = ?";
							$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
							mysqli_stmt_bind_param($stmt, 's', $modality);
							$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$modality]);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$group = $row['group'];
								?><option value="<?=$group?>"><?
							}
							mysqli_stmt_close($stmt);
							?>
							</datalist>
						<?
							/* $modality is a DB-derived table name (an identifier), so these can't be bound */
						$sqlstring = "select distinct(series_desc), count(series_desc) 'count' from $modality" . "_series where trim(series_desc) <> '' group by series_desc order by series_desc";
						$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$seriesdesc = $row['series_desc'];
							$count = $row['count'];
							$rows[$seriesdesc] = ($rows[$seriesdesc] ?? 0) + $count;
						}
						$sqlstring = "select distinct(series_protocol), count(series_protocol) 'count' from $modality" . "_series where trim(series_protocol) <> '' group by series_protocol order by series_protocol";
						$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$seriesprotocol = $row['series_protocol'];
							$count = $row['count'];
							$rows[$seriesprotocol] = ($rows[$seriesprotocol] ?? 0) + $count;
						}
						
						ksort($rows);
						foreach ($rows as $series => $count) {
							?>
							<tr>
								<td><?=$series?></td>
								<td><?=$count?></td>
								<td><input type="checkbox" name="protocols[]" value="<?=$series?>"></td>
							</tr>
							<?
						}
						?>
						<tr>
							<td colspan="3" align="right">Protocol group name <input type="text" name="thegroup" list="protocolgroups"><input type="submit" value="Add" title="Add selected to group" class="ui primary button"></td>
						</tr>
						</tbody>
					</table>
					</form>
				</td>
				<td valign="top">
					<table class="ui very compact celled grey table">
						<thead>
							<tr>
								<th>Group</th>
								<th>Count</th>
							</tr>
						</thead>
						<tbody>
						<?
						$sqlstring = "select * from protocol_group where protocolgroup_modality = ?";
						$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
						mysqli_stmt_bind_param($stmt, 's', $modality);
						$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$modality]);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$groupid = $row['protocolgroup_id'];
							$groupname = $row['protocolgroup_name'];

							$sqlstringA = "select * from protocolgroup_items where protocolgroup_id = ?";
							$stmtA = mysqli_prepare($GLOBALS['linki'], $sqlstringA);
							mysqli_stmt_bind_param($stmtA, 'i', $groupid);
							$resultA = MySQLiBoundQuery($stmtA, __FILE__, __LINE__, $sqlstringA, [$groupid]);
							mysqli_stmt_close($stmtA);
							$count = mysqli_num_rows($resultA);
							?>
							<tr>
								<td><?=$groupname?></td>
								<td>
									<details>
										<summary style="font-size:9pt"><?=$count?> protocols</summary>
										<table class="ui very small very compact celled selectable grey table">
										<?
											while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
												$p = $rowA['pgitem_protocol'];
												$pgitemid = $rowA['pgitem_id'];
												?>
													<tr><td><?=$p?></td><td><a class="ui red button" href="adminmodalities.php?action=deleteprotocolgroupitem&pgitemid=<?=$pgitemid?>&modality=<?=$modality?>" style="color:darkred;" title="Remove <b><?=$p?></b> from group" onclick="return confirm('Are you sure you want to delete this?')"><i class="trash icon"></i></a></td></tr>
												<?
											}
										?>
										</table>
									</details>
								</td>
							</tr>
							<?
						}
						mysqli_stmt_close($stmt);
						?>
						</tbody>
					</table>
				</td>
			</tr>
		</table>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayModalityList ---------------- */
	/* -------------------------------------------- */
	function DisplayModalityList() {

		ShowFlashMessage(); /* show any message from a mutating action that redirected here (PRG) */
	?>

	<div class="ui container">
		<h2 class="ui header">Modalities</h2>
		<table class="ui small celled selectable grey very compact table">
		<thead>
			<tr>
				<th>Name<br><span class="tiny">View table schema</span></th>
				<th>Protocol groups</th>
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
					$sqlstringA = "show table status like '" . strtolower($name) . "_series'";
					$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
					$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
					$rows = $rowA['Rows'];
					$tablesize = $rowA['Data_length'];
					$indexsize = $rowA['Index_length'];

					/* get info about the modality protocol group */
					//$sqlstringB = "select count(*) 'count' from modality_protocolgroup where modality = '$name'";
					//$resultB = MySQLiQuery($sqlstringB, __FILE__, __LINE__);
					//$rowB = mysqli_fetch_array($resultB, MYSQLI_ASSOC);
					//$grouprowcount = $rowB['count'];
					
					?>
					<tr style="color: <?=$color?>">
						<td><a href="adminmodalities.php?action=edit&id=<?=$id?>"><?=$name?></a></td>
						<td><i class="sitemap icon"></i> <a href="adminmodalities.php?action=editprotocolgroups&id=<?=$id?>">View</a></td>
						<td><?=$desc?></td>
						<td align="right"><?=number_format($rows ?? 0,0)?></td>
						<td align="right"><?=number_format(($tablesize ?? 0)+($indexsize ?? 0))?></td>
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
	<?
	}
?>


<? include("footer.php") ?>
