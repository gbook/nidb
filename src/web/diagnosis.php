<?
 // ------------------------------------------------------------------------------
 // NiDB diagnosis.php
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
		<title>NiDB - Diagnosis</title>
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
	$diagnosisid = GetVariable("diagnosisid");
	$enrollmentid = GetVariable("enrollmentid");
	$icd10id = GetVariable("icd10_id");
	$startdate = GetVariable("startdate");
	$enddate = GetVariable("enddate");

	/* determine action */
	switch ($action) {
		case 'adddiagnosis':
			AddDiagnosis($enrollmentid, $icd10id, $startdate, $enddate);
			DisplayDiagnosisList($enrollmentid);
			break;
		case 'deletediagnosis':
			DeleteDiagnosis($diagnosisid);
			DisplayDiagnosisList($enrollmentid);
			break;
		default:
			DisplayDiagnosisList($enrollmentid);
	}


	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- AddDiagnosis ----------------------- */
	/* -------------------------------------------- */
	function AddDiagnosis($enrollmentid, $icd10id, $startdate, $enddate) {
		$enrollmentid = (int)$enrollmentid;
		$icd10id = (int)$icd10id;

		$stmt = mysqli_prepare($GLOBALS['linki'], "insert into diagnosis (enrollment_id, icd10_id, start_date, end_date) values (?, ?, ?, ?)");
		mysqli_stmt_bind_param($stmt, 'iiss', $enrollmentid, $icd10id, $startdate, $enddate);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);
	}


	/* -------------------------------------------- */
	/* ------- DeleteDiagnosis -------------------- */
	/* -------------------------------------------- */
	function DeleteDiagnosis($diagnosisid) {
		$diagnosisid = (int)$diagnosisid;
		$stmt = mysqli_prepare($GLOBALS['linki'], "delete from diagnosis where diagnosis_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $diagnosisid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);

		Notice("Diagnosis deleted");
	}


	/* -------------------------------------------- */
	/* ------- DisplayDiagnosisList --------------- */
	/* -------------------------------------------- */
	function DisplayDiagnosisList($enrollmentid) {
		$enrollmentid = (int)$enrollmentid;

		$stmt = mysqli_prepare($GLOBALS['linki'], "select * from enrollment a left join subjects b on a.subject_id = b.subject_id left join projects c on a.project_id = c.project_id where a.enrollment_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $enrollmentid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		$uid = $row['uid'];
		$subjectid = $row['subject_id'];
		$projectname = $row['project_name'];
		?>
		<script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.noStyle.js"></script>

		<div class="ui segment">
			<form action="diagnosis.php" method="post" class="ui form" id="diagnosisform">
				<input type="hidden" name="action" value="adddiagnosis">
				<input type="hidden" name="enrollmentid" value="<?=$enrollmentid?>">
				<input type="hidden" name="icd10_id" id="icd10_id" required>

				<div class="three fields">
					<div class="eight wide field">
						<label>ICD10</label>
						<input type="text" name="icd10_search" id="icd10_search" placeholder="Search ICD10 code or long description" autocomplete="off" required>
						<div class="ui tiny pointing basic label">Type at least 2 characters to search ICD10 code or long description.</div>
					</div>
					<div class="four wide field">
						<label>Start date</label>
						<input type="date" name="startdate" required>
					</div>
					<div class="four wide field">
						<label>End date</label>
						<input type="date" name="enddate">
					</div>
				</div>

				<div style="text-align: right">
					<input type="submit" value="Add Diagnosis" class="ui primary button">
				</div>
			</form>
		</div>

		<?
		$rowdata = array();
		$stmt = mysqli_prepare($GLOBALS['linki'], "select a.diagnosis_id, b.icd10_code, b.icd10_longdesc, date_format(a.start_date, '%Y-%m-%d') start_date, date_format(a.end_date, '%Y-%m-%d') end_date from diagnosis a left join icd10 b on a.icd10_id = b.icd10_id where a.enrollment_id = ? order by b.icd10_code");
		mysqli_stmt_bind_param($stmt, 'i', $enrollmentid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$enddate = $row['end_date'];
			if ($enddate == '') {
				$enddate = 'null';
			}
			$rowdata[] = array(
				'diagnosisid' => $row['diagnosis_id'],
				'code' => $row['icd10_code'],
				'description' => $row['icd10_longdesc'],
				'startdate' => $row['start_date'],
				'enddate' => $enddate
			);
		}
		mysqli_stmt_close($stmt);
		$data = json_encode($rowdata, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
		?>
		<div id="diagnosisgrid" class="ui attached segment" style="height: 50vh; padding: 0;"></div>
		<div class="ui bottom attached secondary segment">Displaying <span id="rowcount">0</span> rows</div>

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
					{ headerName: 'ICD10 Code', field: 'code', minWidth: 130, maxWidth: 130, cellStyle: { 'font-family': 'monospace', 'font-size': '12pt' } },
					{ headerName: 'Long Description', field: 'description', flex: 1.5, minWidth: 280 },
					{ headerName: 'Start Date', field: 'startdate', minWidth: 120, maxWidth: 120 },
					{ headerName: 'End Date', field: 'enddate', minWidth: 120, maxWidth: 120 },
					{
						headerName: 'Delete',
						field: 'diagnosisid',
						width: 90,
						minWidth: 90,
						maxWidth: 90,
						filter: false,
						sortable: false,
						resizable: false,
						cellStyle: { 'text-align': 'center' },
						cellRenderer: function(params) {
							return '<a href="diagnosis.php?action=deletediagnosis&diagnosisid=' + params.value + '&enrollmentid=<?=$enrollmentid?>" title="Delete this diagnosis" onclick="return confirm(\'Are you sure you want to delete this record?\')"><i class="red alternate trash icon"></i></a>';
						}
					}
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
				$('#icd10_search').autocomplete({
					minLength: 2,
					delay: 200,
					source: function(request, response) {
						$.getJSON('ajaxapi.php', { action: 'searchicd10', term: request.term }, response);
					},
					focus: function(event, ui) {
						$('#icd10_search').val(ui.item.label);
						return false;
					},
					select: function(event, ui) {
						$('#icd10_search').val(ui.item.label);
						$('#icd10_id').val(ui.item.icd10_id);
						return false;
					}
				});

				$('#icd10_search').on('input', function() {
					$('#icd10_id').val('');
				});

				$('#diagnosisform').on('submit', function() {
					if ($('#icd10_id').val() == '') {
						alert('Please select an ICD10 code from the search results.');
						$('#icd10_search').focus();
						return false;
					}
				});

				const eGridDiv = document.getElementById('diagnosisgrid');
				gridApi = agGrid.createGrid(eGridDiv, gridOptions);
				updateDisplayedRowCount();
			});
		</script>
		<?
	}
?>

<? include("footer.php") ?>

