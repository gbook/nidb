<?
 // ------------------------------------------------------------------------------
 // NiDB icd10.php
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
		<title>NiDB - ICD-10 Codes</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");

	/* determine action */
	if (($action == "") || ($action == "list")) {
		DisplayICD10List();
	}
	else {
		DisplayICD10List();
	}


	/* -------------------------------------------- */
	/* ------- DisplayICD10List ------------------- */
	/* -------------------------------------------- */
	function DisplayICD10List() {
		$stmt = mysqli_prepare($GLOBALS['linki'], "select icd10_code, icd10_longdesc from icd10 order by icd10_code");
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$rowdata = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$rowdata[] = array(
				'code' => $row['icd10_code'],
				'longdesc' => $row['icd10_longdesc']
			);
		}
		mysqli_stmt_close($stmt);
		$data = json_encode($rowdata, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
		$numcodes = count($rowdata);
		?>
		<script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.noStyle.js"></script>

		<div class="ui container">
			<div class="ui top attached yellow segment">
				<div class="ui grid">
					<div class="eight wide column">
						<h1 class="ui header">
							ICD-10 Codes
							<div class="sub header">Displaying <?=number_format($numcodes)?> codes</div>
						</h1>
					</div>
					<div class="right aligned eight wide column">
						<div class="ui basic label">Reference list</div>
					</div>
				</div>
			</div>
			<div id="myGrid" class="ui attached segment" style="height: 65vh; padding: 0;"></div>
			<div class="ui bottom attached secondary segment">Displaying <span id="rowcount">0</span> rows</div>
		</div>

		<script>
			const rowData = <?=$data?>;
			let gridApi;

			function updateDisplayedRowCount() {
				if (!gridApi) {
					return;
				}
				document.getElementById('rowcount').textContent = gridApi.getDisplayedRowCount();
			}

			const gridOptions = {
				theme: agGrid.themeBalham,
				columnDefs: [
					{
						headerName: 'ICD-10 Code',
						field: 'code',
						minWidth: 180,
						maxWidth: 180,
						pinned: 'left',
						cellStyle: { 'font-family': 'monospace', 'font-size': '12pt' }
					},
					{ headerName: 'Long Description', field: 'longdesc', flex: 1, wrapText: false, autoHeight: true }
				],
				rowData: rowData,
				defaultColDef: { sortable: true, filter: true, resizable: true },
				animateRows: false,
				suppressMovableColumns: true,
				onFirstDataRendered: updateDisplayedRowCount,
				onFilterChanged: updateDisplayedRowCount,
				onModelUpdated: updateDisplayedRowCount
			};

			$(document).ready(function() {
				const eGridDiv = document.getElementById('myGrid');
				gridApi = agGrid.createGrid(eGridDiv, gridOptions);
				updateDisplayedRowCount();
			});
		</script>
		<?
	}
?>

<? include("footer.php") ?>





