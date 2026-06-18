<?
 // ------------------------------------------------------------------------------
 // NiDB instruments.php
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
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Instruments</title>
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community@31/styles/ag-theme-alpine.css">
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	$action          = GetVariable("action");
	$projectid       = (int)GetVariable("projectid");
	$instrumentid    = (int)GetVariable("instrumentid");
	$itemid          = (int)GetVariable("itemid");
	$instrument_name = GetVariable("instrument_name");
	$instrument_notes = GetVariable("instrument_notes");
	$item_name       = GetVariable("item_name");
	$item_notes      = GetVariable("item_notes");
	$item_type       = GetVariable("item_type");
	$item_names      = GetVariable("item_names");
	$itemids         = GetVariable("itemids");

	switch ($action) {
		case 'addinstrument':
			AddInstrument($projectid, $instrument_name, $instrument_notes);
			DisplayPage($projectid, $instrumentid);
			break;
		case 'updateinstrument':
			UpdateInstrument($instrumentid, $instrument_name, $instrument_notes);
			DisplayPage($projectid, $instrumentid);
			break;
		case 'deleteinstrument':
			DeleteInstrument($instrumentid);
			DisplayPage($projectid, 0);
			break;
		case 'additem':
			AddItem($instrumentid, $item_name, $item_notes, $item_type);
			DisplayPage($projectid, $instrumentid);
			break;
		case 'bulkadditems':
			BulkAddItems($instrumentid, $item_names);
			DisplayPage($projectid, $instrumentid);
			break;
		case 'deleteitem':
			DeleteItem($itemid);
			DisplayPage($projectid, $instrumentid);
			break;
		case 'updateitem':
			UpdateItem($itemid, $item_name, $item_notes, $item_type);
			break;
		case 'reorderitems':
			ReorderItems($instrumentid, $itemids);
			break;
		default:
			DisplayPage($projectid, $instrumentid);
	}


	/* --------------------------------------------------- */
	/* ------- AddInstrument ----------------------------- */
	/* --------------------------------------------------- */
	function AddInstrument($projectid, $name, $notes) {
		if ($projectid < 1) { Error("Invalid project ID"); return; }
		$name = trim($name);
		if ($name == "") { Error("Instrument name cannot be blank"); return; }
		$sqlstring = "insert into instruments (project_id, instrument_name, instrument_notes) values (?, ?, ?)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'iss', $projectid, $name, $notes);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid, $name, $notes]);
		mysqli_stmt_close($stmt);
		Notice("Instrument '$name' added");
	}


	/* --------------------------------------------------- */
	/* ------- UpdateInstrument -------------------------- */
	/* --------------------------------------------------- */
	function UpdateInstrument($instrumentid, $name, $notes) {
		if ($instrumentid < 1) { Error("Invalid instrument ID"); return; }
		$name = trim($name);
		if ($name == "") { Error("Instrument name cannot be blank"); return; }
		$sqlstring = "update instruments set instrument_name = ?, instrument_notes = ? where instrument_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ssi', $name, $notes, $instrumentid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$name, $notes, $instrumentid]);
		mysqli_stmt_close($stmt);
		Notice("Instrument updated");
	}


	/* --------------------------------------------------- */
	/* ------- DeleteInstrument -------------------------- */
	/* --------------------------------------------------- */
	function DeleteInstrument($instrumentid) {
		if ($instrumentid < 1) { Error("Invalid instrument ID"); return; }
		$sqlstring = "delete from instrument_items where instrument_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $instrumentid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$instrumentid]);
		mysqli_stmt_close($stmt);
		$sqlstring = "delete from instruments where instrument_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $instrumentid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$instrumentid]);
		mysqli_stmt_close($stmt);
		Notice("Instrument deleted");
	}


	/* --------------------------------------------------- */
	/* ------- AddItem ----------------------------------- */
	/* --------------------------------------------------- */
	function AddItem($instrumentid, $name, $notes, $type) {
		if ($instrumentid < 1) { Error("Invalid instrument ID"); return; }
		$name = trim($name);
		if ($name == "") { Error("Item name cannot be blank"); return; }
		$allowed_types = ['int', 'double', 'string', 'timeseries'];
		if (!in_array($type, $allowed_types)) { $type = 'string'; }
		$sqlstring = "select coalesce(max(item_order),0)+1 as nextorder from instrument_items where instrument_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $instrumentid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$instrumentid]);
		mysqli_stmt_close($stmt);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$nextorder = (int)$row['nextorder'];
		$sqlstring = "insert into instrument_items (instrument_id, item_name, item_order, item_notes, item_type) values (?, ?, ?, ?, ?)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'isiss', $instrumentid, $name, $nextorder, $notes, $type);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$instrumentid, $name, $nextorder, $notes, $type]);
		mysqli_stmt_close($stmt);
	}


	/* --------------------------------------------------- */
	/* ------- BulkAddItems ------------------------------ */
	/* --------------------------------------------------- */
	function BulkAddItems($instrumentid, $itemnames) {
		if ($instrumentid < 1) { Error("Invalid instrument ID"); return; }
		$allowed_types = ['int', 'double', 'string', 'timeseries'];
		$lines = preg_split('/\r\n|\r|\n/', trim($itemnames));
		$sqlstring = "select coalesce(max(item_order),0) as maxorder from instrument_items where instrument_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $instrumentid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$instrumentid]);
		mysqli_stmt_close($stmt);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$order = (int)$row['maxorder'];
		$added = 0;
		foreach ($lines as $line) {
			$parts = array_map('trim', explode(',', $line, 3));
			$name  = $parts[0];
			if ($name == "") continue;
			$type  = isset($parts[1]) && in_array(strtolower($parts[1]), $allowed_types) ? strtolower($parts[1]) : 'string';
			$notes = $parts[2] ?? '';
			$order++;
			$sqlstring = "insert into instrument_items (instrument_id, item_name, item_order, item_type, item_notes) values (?, ?, ?, ?, ?)";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'isiss', $instrumentid, $name, $order, $type, $notes);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$instrumentid, $name, $order, $type, $notes]);
			mysqli_stmt_close($stmt);
			$added++;
		}
		Notice("$added item(s) added");
	}


	/* --------------------------------------------------- */
	/* ------- DeleteItem -------------------------------- */
	/* --------------------------------------------------- */
	function DeleteItem($itemid) {
		if ($itemid < 1) { Error("Invalid item ID"); return; }
		$sqlstring = "delete from instrument_items where instrumentitem_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $itemid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$itemid]);
		mysqli_stmt_close($stmt);
	}


	/* --------------------------------------------------- */
	/* ------- UpdateItem -------------------------------- */
	/* --------------------------------------------------- */
	function UpdateItem($itemid, $name, $notes, $type) {
		if ($itemid < 1) { echo json_encode(['status' => 'error', 'message' => 'Invalid item ID']); return; }
		$name = trim($name);
		if ($name == "") { echo json_encode(['status' => 'error', 'message' => 'Item name cannot be blank']); return; }
		$allowed_types = ['int', 'double', 'string', 'timeseries'];
		if (!in_array($type, $allowed_types)) { $type = 'string'; }
		$sqlstring = "update instrument_items set item_name = ?, item_notes = ?, item_type = ? where instrumentitem_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'sssi', $name, $notes, $type, $itemid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$name, $notes, $type, $itemid]);
		mysqli_stmt_close($stmt);
		echo json_encode(['status' => 'ok']);
	}


	/* --------------------------------------------------- */
	/* ------- ReorderItems ------------------------------ */
	/* --------------------------------------------------- */
	function ReorderItems($instrumentid, $itemids) {
		if ($instrumentid < 1) return;
		$ids = json_decode($itemids, true);
		if (!is_array($ids)) return;
		foreach ($ids as $order => $id) {
			$id = (int)$id;
			$order = (int)$order + 1;
			$sqlstring = "update instrument_items set item_order = ? where instrumentitem_id = ? and instrument_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'iii', $order, $id, $instrumentid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$order, $id, $instrumentid]);
			mysqli_stmt_close($stmt);
		}
		echo json_encode(['status' => 'ok']);
	}


	/* --------------------------------------------------- */
	/* ------- DisplayPage ------------------------------- */
	/* --------------------------------------------------- */
	function DisplayPage($projectid, $instrumentid) {
		if ($projectid < 1) { Error("Invalid project ID"); return; }

		/* get project name */
		$sqlstring = "select project_name from projects where project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		mysqli_stmt_close($stmt);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$projectname = $row['project_name'];

		/* get all instruments for this project */
		$sqlstring = "select a.*, count(b.instrumentitem_id) as item_count from instruments a left join instrument_items b on a.instrument_id = b.instrument_id where a.project_id = ? group by a.instrument_id order by a.instrument_name";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		mysqli_stmt_close($stmt);
		$instruments = [];
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$instruments[] = $row;
		}

		/* if an instrument is selected, get its details and expected items */
		$selInstrument = null;
		$items = [];
		if ($instrumentid > 0) {
			$sqlstring = "select * from instruments where instrument_id = ? and project_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'ii', $instrumentid, $projectid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$instrumentid, $projectid]);
			mysqli_stmt_close($stmt);
			$selInstrument = mysqli_fetch_array($result, MYSQLI_ASSOC);

			if ($selInstrument) {
				$sqlstring = "select * from instrument_items where instrument_id = ? order by item_order, item_name";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'i', $instrumentid);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$instrumentid]);
				mysqli_stmt_close($stmt);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$items[] = $row;
				}

			}
		}

		?>
		<div class="ui container">

			<div class="ui top attached secondary inverted segment">
				<div class="ui two column grid">
					<div class="column">
						<h2 class="ui header" style="color:white">Instruments &mdash; <?=htmlspecialchars($projectname)?></h2>
					</div>
					<div class="right aligned column">
						<a href="projects.php?action=displayprojectinfo&id=<?=$projectid?>" class="ui basic inverted button"><i class="arrow left icon"></i> Project</a>
					</div>
				</div>
			</div>

			<div class="ui bottom attached segment">
				<div class="ui two column grid" style="align-items: flex-start">

					<!-- ===== LEFT: instrument list ===== -->
					<div class="four wide column">
						<!-- Add instrument form -->
						<form method="post" action="instruments.php" class="ui form" style="margin-bottom:8px">
							<input type="hidden" name="action" value="addinstrument">
							<input type="hidden" name="projectid" value="<?=$projectid?>">
							<div class="ui small fluid action input">
								<input type="text" name="instrument_name" placeholder="New instrument name" required>
								<button class="ui small green button" type="submit"><i class="plus icon"></i> Add</button>
							</div>
						</form>

						<table class="ui very compact celled selectable small table" style="margin-bottom:0">
							<thead>
								<tr>
									<th>Instrument</th>
									<th class="right aligned">Items</th>
									<th></th>
								</tr>
							</thead>
							<tbody>
							<? foreach ($instruments as $inst) {
								$active = ($inst['instrument_id'] == $instrumentid) ? 'active' : '';
							?>
							<tr class="<?=$active?>">
								<td>
									<a href="instruments.php?projectid=<?=$projectid?>&instrumentid=<?=$inst['instrument_id']?>" style="font-weight:<?=$active ? 'bold' : 'normal'?>">
										<?=htmlspecialchars($inst['instrument_name'])?>
									</a>
									<? if ($inst['instrument_notes']) { ?>
									<br><span style="font-size:0.8em; color:#888"><?=htmlspecialchars($inst['instrument_notes'])?></span>
									<? } ?>
								</td>
								<td class="right aligned"><?=$inst['item_count']?></td>
								<td class="center aligned" style="white-space:nowrap">
									<a href="instruments.php?action=deleteinstrument&projectid=<?=$projectid?>&instrumentid=<?=$inst['instrument_id']?>"
									   class="ui mini red basic icon button"
									   onclick="return confirm('Delete <?=htmlspecialchars(addslashes($inst['instrument_name']))?> and all its items?')">
										<i class="trash icon"></i>
									</a>
								</td>
							</tr>
							<? } ?>
							</tbody>
						</table>
					</div>

					<!-- ===== RIGHT: expected items ===== -->
					<div class="twelve wide column">
					<? if ($selInstrument) { ?>

						<div class="ui top attached secondary segment" style="padding: 6px 10px">
							<div class="ui two column grid">
								<div class="column">
									<b><?=htmlspecialchars($selInstrument['instrument_name'])?></b>
									<? if ($selInstrument['instrument_notes']) { ?>
									&nbsp;<span style="color:#666; font-size:0.9em"><?=htmlspecialchars($selInstrument['instrument_notes'])?></span>
									<? } ?>
								</div>
								<div class="right aligned column">
									<a class="ui mini basic teal button" onclick="exportInstrumentPDF()"><i class="file pdf icon"></i> Export PDF</a>
									<a class="ui mini basic blue button" onclick="$('#edit-instrument-form').toggle()"><i class="edit icon"></i> Edit</a>
								</div>
							</div>
						</div>

						<div id="edit-instrument-form" style="display:none" class="ui attached segment" style="padding:8px">
							<form method="post" action="instruments.php" class="ui mini form">
								<input type="hidden" name="action" value="updateinstrument">
								<input type="hidden" name="projectid" value="<?=$projectid?>">
								<input type="hidden" name="instrumentid" value="<?=$instrumentid?>">
								<div class="fields">
									<div class="eight wide field">
										<input type="text" name="instrument_name" value="<?=htmlspecialchars($selInstrument['instrument_name'])?>" placeholder="Name" required>
									</div>
									<div class="six wide field">
										<input type="text" name="instrument_notes" value="<?=htmlspecialchars($selInstrument['instrument_notes'] ?? '')?>" placeholder="Notes (optional)">
									</div>
									<div class="two wide field">
										<button class="ui small primary button" type="submit">Save</button>
									</div>
								</div>
							</form>
						</div>

						<!-- add single item -->
						<div class="ui attached segment" style="padding:6px 8px">
							<form method="post" action="instruments.php" class="ui mini form">
								<input type="hidden" name="action" value="additem">
								<input type="hidden" name="projectid" value="<?=$projectid?>">
								<input type="hidden" name="instrumentid" value="<?=$instrumentid?>">
								<div class="fields" style="margin-bottom:4px">
									<div class="seven wide field">
										<input type="text" name="item_name" placeholder="Item name" id="item-name-input" required>
									</div>
									<div class="three wide field">
										<select name="item_type" class="ui dropdown">
											<option value="string">String</option>
											<option value="int">Int</option>
											<option value="double">Double</option>
											<option value="timeseries">Timeseries</option>
										</select>
									</div>
									<div class="four wide field">
										<input type="text" name="item_notes" placeholder="Notes (optional)">
									</div>
									<div class="two wide field">
										<button class="ui small green button" type="submit"><i class="plus icon"></i> Add</button>
									</div>
								</div>
							</form>

							<!-- bulk add toggle + selection toolbar -->
							<a class="ui mini basic button" onclick="$('#bulk-add-area').toggle()"><i class="list icon"></i> Bulk add</a>
							<span id="itemSelectionToolbar" style="display:none;margin-left:10px">
								<span id="itemSelectionLabel" style="font-size:0.85em;color:#555;margin-right:8px"></span>
								<button class="ui mini red basic button" onclick="deleteSelectedItems()"><i class="trash icon"></i> Delete</button>
							</span>
							<div id="bulk-add-area" style="display:none; margin-top:6px">
								<form method="post" action="instruments.php" class="ui mini form">
									<input type="hidden" name="action" value="bulkadditems">
									<input type="hidden" name="projectid" value="<?=$projectid?>">
									<input type="hidden" name="instrumentid" value="<?=$instrumentid?>">
									<div class="field">
										<textarea name="item_names" rows="6" placeholder="One item per line: ItemName, Type, Notes&#10;Type and Notes are optional. Type defaults to String.&#10;Example:&#10;HeartRate, int, beats per minute&#10;Label, string&#10;Temperature"></textarea>
									</div>
									<button class="ui small green button" type="submit"><i class="plus icon"></i> Add all</button>
								</form>
							</div>
						</div>

						<!-- AG Grid items table -->
						<div id="item-grid" class="ag-theme-alpine" style="height:55vh; border:1px solid #ddd; border-top:none"></div>

						<div style="margin-top:4px; color:#aaa; font-size:0.8em"><?=count($items)?> expected item(s)</div>


					<? } else { ?>
						<div class="ui placeholder segment" style="min-height:0; padding:2em">
							<div class="ui icon header">
								<i class="list alternate outline icon"></i>
								Select an instrument to manage its expected items
							</div>
						</div>
					<? } ?>
					</div>

				</div>
			</div>
		</div>

<script src="https://cdn.jsdelivr.net/npm/ag-grid-community@31/dist/ag-grid-community.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
		<script>
			const itemData = <?=json_encode(array_map(function($item) {
				return [
					'id'    => (int)$item['instrumentitem_id'],
					'order' => (int)$item['item_order'],
					'name'  => $item['item_name'],
					'type'  => $item['item_type'] ?? 'string',
					'notes' => $item['item_notes'] ?? '',
				];
			}, $items))?>;

			const projectid      = <?=$projectid?>;
			const instrumentid   = <?=$instrumentid?>;
			const instrumentname = <?=json_encode($selInstrument['instrument_name'] ?? '')?>;

			$(document).ready(function() {
				$('#item-name-input').autocomplete({
					minLength: 2,
					delay: 200,
					source: function(request, response) {
						$.getJSON('ajaxapi.php', {
							action: 'searchobservationnames',
							term: request.term,
							instrumentname: instrumentname
						}, response);
					},
					select: function(event, ui) {
						$('#item-name-input').val(ui.item.value);
						return false;
					}
				});
			});

			function DeleteItemBtn(params) {
				const btn = document.createElement('a');
				//btn.className = 'ui mini red basic icon button';
				//btn.style.cssText = 'padding:2px 5px; font-size:0.7em; line-height:1';
				btn.innerHTML = '<i class="red trash alternate outline icon"></i>';
				btn.onclick = () => {
					if (confirm('Remove this item?')) {
						window.location = `instruments.php?action=deleteitem&projectid=${projectid}&instrumentid=${instrumentid}&itemid=${params.data.id}`;
					}
				};
				return btn;
			}

			function saveCell(params) {
				$.post('instruments.php', {
					action:    'updateitem',
					itemid:    params.data.id,
					item_name: params.data.name,
					item_type: params.data.type,
					item_notes: params.data.notes,
				});
			}

			const typeLabels = { string: 'String', int: 'Int', double: 'Double', timeseries: 'Timeseries' };

			const colDefs = [
				{ headerName: '', checkboxSelection: true, headerCheckboxSelection: true, width: 40, minWidth: 40, maxWidth: 40, sortable: false, filter: false, resizable: false, editable: false },
				{ field: 'order', headerName: '#', width: 60, rowDrag: true, editable: false, sort: 'asc', cellStyle: {color:'#aaa'} },
				{ field: 'name',  headerName: 'Item name', flex: 2, editable: true, onCellValueChanged: saveCell },
				{ field: 'type',  headerName: 'Type', width: 130, editable: true,
					cellEditor: 'agSelectCellEditor',
					cellEditorParams: { values: ['string', 'int', 'double', 'timeseries'] },
					valueFormatter: p => typeLabels[p.value] ?? p.value,
					onCellValueChanged: saveCell },
				{ field: 'notes', headerName: 'Notes', flex: 3, editable: true, onCellValueChanged: saveCell },
				{ headerName: '', width: 50, cellStyle: { 'text-align': 'center', 'display': 'flex', 'align-items': 'center' }, cellRenderer: DeleteItemBtn, editable: false, sortable: false, filter: false },
			];

			function updateItemSelectionToolbar() {
				const rows = itemGrid.getSelectedRows();
				const n = rows.length;
				const toolbar = document.getElementById('itemSelectionToolbar');
				const label   = document.getElementById('itemSelectionLabel');
				if (n > 0) {
					toolbar.style.display = '';
					label.textContent = 'With selected ' + n + ' item' + (n !== 1 ? 's' : '') + '...';
				} else {
					toolbar.style.display = 'none';
				}
			}

			function deleteSelectedItems() {
				const rows = itemGrid.getSelectedRows();
				if (rows.length === 0) return;
				if (!confirm('Delete ' + rows.length + ' item' + (rows.length !== 1 ? 's' : '') + '? This cannot be undone.')) return;
				const ids = rows.map(r => r.id);
				fetch('ajaxapi.php?action=bulkdeleteitems&ids=' + encodeURIComponent(JSON.stringify(ids)))
					.then(r => r.json())
					.then(res => {
						if (res.ok) {
							itemGrid.applyTransaction({ remove: rows });
							updateItemSelectionToolbar();
						} else {
							alert('Delete failed: ' + (res.error || 'unknown error'));
						}
					});
			}

			function exportInstrumentPDF() {
				const { jsPDF } = window.jspdf;
				const doc = new jsPDF();
				const notes = <?=json_encode($selInstrument['instrument_notes'] ?? '')?>;
				doc.setFontSize(16);
				doc.text(instrumentname, 14, 18);
				if (notes) {
					doc.setFontSize(10);
					doc.setTextColor(120);
					doc.text(notes, 14, 26);
					doc.setTextColor(0);
				}
				const rows = [];
				itemGrid.forEachNodeAfterFilterAndSort(n => {
					rows.push([n.data.order, n.data.name, typeLabels[n.data.type] ?? n.data.type, n.data.notes]);
				});
				doc.autoTable({
					startY: notes ? 32 : 24,
					head: [['#', 'Item Name', 'Type', 'Notes']],
					body: rows,
					styles: { fontSize: 9, cellPadding: 2 },
					headStyles: { fillColor: [41, 128, 185] },
					columnStyles: { 0: { cellWidth: 12 }, 2: { cellWidth: 24 } },
				});
				doc.save(instrumentname.replace(/[^a-z0-9]/gi, '_') + '.pdf');
			}

			const itemGrid = agGrid.createGrid(document.getElementById('item-grid'), {
				columnDefs: colDefs,
				rowData: itemData,
				rowDragManaged: true,
				animateRows: true,
				rowHeight: 28,
				headerHeight: 32,
				rowSelection: 'multiple',
				defaultColDef: { sortable: true, filter: true, resizable: true },
				onSelectionChanged: updateItemSelectionToolbar,
				onRowDragEnd: (e) => {
					const ids = [];
					itemGrid.forEachNodeAfterFilterAndSort(n => ids.push(n.data.id));
					$.post('instruments.php', { action: 'reorderitems', instrumentid: instrumentid, itemids: JSON.stringify(ids) });
				},
			});

		</script>
		<?
	}

require "footer.php";
?>
