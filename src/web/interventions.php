<?
 // ------------------------------------------------------------------------------
 // NiDB interventions.php
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
		<title>NiDB - Interventions</title>
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
	$interventionid = GetVariable("interventionid");
	$enrollmentid = GetVariable("enrollmentid");
	$interventionname = GetVariable('intervention_name');
	$dose = GetVariable('dose');
	$freq = GetVariable('freq');
	$route = GetVariable('administration_route');
	$startdate = GetVariable('startdate');
	$enddate = GetVariable('enddate');
	$interventiontype = GetVariable('intervention_type');

	/* determine action */
	switch ($action) {
		case 'addintervention':
			AddIntervention($enrollmentid, $interventionname, $dose, $freq, $route, $startdate, $enddate, $interventiontype);
			DisplayInterventionList($enrollmentid);
			break;
		case 'deleteintervention':
			DeleteIntervention($interventionid);
			DisplayInterventionList($enrollmentid);
			break;
		default:
			DisplayInterventionList($enrollmentid);
	}


	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- AddIntervention -------------------- */
	/* -------------------------------------------- */
	function AddIntervention($enrollmentid, $interventionname, $dose, $freq, $route, $startdate, $enddate, $interventiontype) {
		$enrollmentid = (int)$enrollmentid;

		$stmt = mysqli_prepare($GLOBALS['linki'], "insert into interventions (enrollment_id, startdate, enddate, doseamount, dosefrequency, administration_route, intervention_name, intervention_type) values (?, ?, ?, ?, ?, ?, ?, ?)");
		mysqli_stmt_bind_param($stmt, 'isssssss', $enrollmentid, $startdate, $enddate, $dose, $freq, $route, $interventionname, $interventiontype);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);
	}


	/* -------------------------------------------- */
	/* ------- DeleteIntervention ----------------- */
	/* -------------------------------------------- */
	function DeleteIntervention($id) {
		$id = (int)$id;
		$stmt = mysqli_prepare($GLOBALS['linki'], "delete from interventions where intervention_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);

		Notice("Intervention deleted");
	}


	/* -------------------------------------------- */
	/* ------- DisplayInterventionList ------------ */
	/* -------------------------------------------- */
	function DisplayInterventionList($enrollmentid) {
		$enrollmentid = (int)$enrollmentid;
		/* get subject's info for the breadcrumb list */
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
			<form action="interventions.php" method="post" class="ui form">
				<input type="hidden" name="action" value="addintervention">
				<input type="hidden" name="enrollmentid" value="<?=$enrollmentid?>">

				<div class="seven fields">
					<div class="field">
						<label>Intervention</label>
						<input type="text" name="intervention_name" placeholder="Intervention" required>
					</div>
					<div class="field">
						<label>Type</label>
						<input type="text" name="intervention_type" list="ls_type" placeholder="Type">
						<datalist id="ls_type">
							<option>Prescription</option>
							<option>Trial</option>
							<option>OTC</option>
							<option>Recreational</option>
						</datalist>
					</div>
					<div class="field">
						<label>Dose</label>
						<input type="text" name="dose" placeholder="Dose">
					</div>
					<div class="field">
						<label>Frequency</label>
						<input type="text" name="freq" placeholder="Dose frequency">
					</div>
					<div class="field">
						<label>Route</label>
						<input type="text" name="administration_route" list="ls_route" placeholder="Route">
						<datalist id="ls_route">
							<option>Oral</option>
							<option>IV</option>
							<option>IM</option>
							<option>Subcu</option>
						</datalist>
					</div>
					<div class="field">
						<label>Start date</label>
						<input type="datetime-local" name="startdate" required>
					</div>
					<div class="field">
						<label>End date</label>
						<input type="datetime-local" name="enddate">
					</div>
				</div>

				<div style="text-align: right">
					<input type="submit" value="Add Intervention" class="ui primary button">
				</div>
			</form>
		</div>

		<?
		$rowdata = array();
		$stmt = mysqli_prepare($GLOBALS['linki'], "select *, date_format(startdate,'%Y-%m-%d %r') 'startdate', date_format(enddate,'%Y-%m-%d %r') 'enddate' from interventions where enrollment_id = ? order by intervention_name");
		mysqli_stmt_bind_param($stmt, 'i', $enrollmentid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$enddate = $row['enddate'];
			if ($enddate == '') {
				$enddate = 'null';
			}
			$rowdata[] = array(
				'interventionid' => $row['intervention_id'],
				'intervention' => $row['intervention_name'],
				'type' => $row['intervention_type'],
				'dosefreq' => $row['doseamount'] . ' / ' . $row['dosefrequency'],
				'route' => $row['administration_route'],
				'dates' => $row['startdate'] . ' to ' . $enddate
			);
		}
		mysqli_stmt_close($stmt);
		$data = json_encode($rowdata, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
		?>
		<div id="interventiongrid" class="ui attached segment" style="height: 65vh; padding: 0;"></div>
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
					{ headerName: 'Intervention', field: 'intervention', flex: 1.2, minWidth: 180 },
					{ headerName: 'Type', field: 'type', minWidth: 130 },
					{ headerName: 'Dose / Frequency', field: 'dosefreq', minWidth: 150 },
					{ headerName: 'Route', field: 'route', minWidth: 120 },
					{ headerName: 'Date range', field: 'dates', flex: 1.3, minWidth: 260 },
					{
						headerName: 'Delete',
						field: 'interventionid',
						width: 90,
						minWidth: 90,
						maxWidth: 90,
						filter: false,
						sortable: false,
						resizable: false,
						cellStyle: { 'text-align': 'center' },
						cellRenderer: function(params) {
							return '<a href="interventions.php?action=deleteintervention&interventionid=' + params.value + '&enrollmentid=<?=$enrollmentid?>" title="Delete this intervention" onclick="return confirm(\'Are you sure you want to delete this record?\')"><i class="red alternate trash icon"></i></a>';
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
				const eGridDiv = document.getElementById('interventiongrid');
				gridApi = agGrid.createGrid(eGridDiv, gridOptions);
				updateDisplayedRowCount();
			});
		</script>
		<?
	}
?>

<? include("footer.php") ?>




