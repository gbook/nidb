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
	ob_start(); /* buffer output so POST/Redirect/GET (a header('Location') redirect) works despite the HTML rendered below */
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
	/* mutating actions require POST + CSRF token */
	$action = GetSafeAction(array("addintervention", "deleteintervention"));
	$interventionid = (int)GetVariable("interventionid");
	$enrollmentid = (int)GetVariable("enrollmentid");
	$interventionname = GetVariable('intervention_name');
	$dose = GetVariable('dose');
	$freq = GetVariable('freq');
	$route = GetVariable('administration_route');
	$startdate = GetVariable('startdate');
	$enddate = GetVariable('enddate');
	$interventiontype = GetVariable('intervention_type');

	/* determine action */
	switch ($action) {
		/* mutating actions use POST/Redirect/GET: run the handler, stash its message,
		   then redirect to a GET so a refresh/Back doesn't re-submit the form */
		case 'addintervention':
			ob_start();
			AddIntervention($enrollmentid, $interventionname, $dose, $freq, $route, $startdate, $enddate, $interventiontype);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("interventions.php?enrollmentid=$enrollmentid");
			break;
		case 'deleteintervention':
			ob_start();
			DeleteIntervention($interventionid, $enrollmentid);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("interventions.php?enrollmentid=$enrollmentid");
			break;
		default:
			DisplayInterventionList($enrollmentid);
	}


	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- GetEnrollmentDataPerms ------------- */
	/* -------------------------------------------- */
	/* Interventions are project data: View Data on the enrollment's project to view them, Edit Data to
	   add or delete. Returns [enrollment row or null, viewdata, modifydata] */
	function GetEnrollmentDataPerms($enrollmentid) {
		$enrollment = ((int)$enrollmentid > 0) ? GetEnrollment($enrollmentid) : null;
		if ($enrollment == null)
			return array(null, 0, 0);

		$projectid = (int)$enrollment['project_id'];
		$perms = GetCurrentUserProjectPermissions(array($projectid));
		return array($enrollment, GetPerm($perms, 'viewdata', $projectid), GetPerm($perms, 'modifydata', $projectid));
	}


	/* -------------------------------------------- */
	/* ------- AddIntervention -------------------- */
	/* -------------------------------------------- */
	function AddIntervention($enrollmentid, $interventionname, $dose, $freq, $route, $startdate, $enddate, $interventiontype) {
		$enrollmentid = (int)$enrollmentid;

		list($enrollment, $viewdata, $modifydata) = GetEnrollmentDataPerms($enrollmentid);
		if ($enrollment == null) {
			Error("Invalid enrollment");
			return;
		}
		if (!$modifydata) {
			Error("You do not have permission to edit data in this project");
			return;
		}

		/* a blank end date is stored as NULL rather than an invalid empty datetime */
		$enddate = (trim((string)$enddate) === '') ? null : $enddate;

		$sqlstring = "insert into interventions (enrollment_id, startdate, enddate, doseamount, dosefrequency, administration_route, intervention_name, intervention_type) values (?, ?, ?, ?, ?, ?, ?, ?)";
		$params = [$enrollmentid, $startdate, $enddate, $dose, $freq, $route, $interventionname, $interventiontype];
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'isssssss', ...$params);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);
	}


	/* -------------------------------------------- */
	/* ------- DeleteIntervention ----------------- */
	/* -------------------------------------------- */
	function DeleteIntervention($interventionid, $enrollmentid) {
		$interventionid = (int)$interventionid;
		$enrollmentid = (int)$enrollmentid;

		/* the intervention must belong to the enrollment in the request */
		$sqlstring = "select enrollment_id from interventions where intervention_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $interventionid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$interventionid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$row || ((int)$row['enrollment_id'] != $enrollmentid)) {
			Error("Invalid intervention");
			return;
		}

		list($enrollment, $viewdata, $modifydata) = GetEnrollmentDataPerms($enrollmentid);
		if (($enrollment == null) || !$modifydata) {
			Error("You do not have permission to edit data in this project");
			return;
		}

		$sqlstring = "delete from interventions where intervention_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $interventionid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$interventionid]);
		mysqli_stmt_close($stmt);

		Notice("Intervention deleted");
	}


	/* -------------------------------------------- */
	/* ------- DisplayInterventionList ------------ */
	/* -------------------------------------------- */
	function DisplayInterventionList($enrollmentid) {
		$enrollmentid = (int)$enrollmentid;

		ShowFlashMessage();

		list($enrollment, $viewdata, $modifydata) = GetEnrollmentDataPerms($enrollmentid);
		if ($enrollment == null) {
			Error("Enrollment not found");
			return;
		}
		if (!$viewdata) {
			Error("You do not have permission to view data in this project");
			return;
		}
		?>
		<script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.noStyle.js"></script>

		<? if ($modifydata) { ?>
		<div class="ui segment">
			<form action="interventions.php" method="post" class="ui form">
				<input type="hidden" name="action" value="addintervention">
				<input type="hidden" name="enrollmentid" value="<?=$enrollmentid?>">
				<?=CSRFTokenField()?>

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
		<? } ?>

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
			const CAN_EDIT = <?=($modifydata ? 'true' : 'false')?>;
			const ENROLLMENT_ID = <?=$enrollmentid?>;
			const CSRF_TOKEN = <?=json_encode($_SESSION['csrftoken'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)?>;

			/* delete column (Edit Data only): an inline POST form with the CSRF token, so it can't be triggered by a link */
			const deleteColDef = {
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
					const form = document.createElement('form');
					form.method = 'post';
					form.action = 'interventions.php';
					form.style.cssText = 'display:inline; margin:0';
					[['action', 'deleteintervention'], ['interventionid', params.value], ['enrollmentid', ENROLLMENT_ID], ['csrftoken', CSRF_TOKEN]].forEach(function(f) {
						const input = document.createElement('input');
						input.type = 'hidden';
						input.name = f[0];
						input.value = f[1];
						form.appendChild(input);
					});
					form.onsubmit = function() { return confirm('Are you sure you want to delete this record?'); };
					const btn = document.createElement('button');
					btn.type = 'submit';
					btn.title = 'Delete this intervention';
					btn.style.cssText = 'background:none; border:none; padding:0; cursor:pointer';
					btn.innerHTML = '<i class="red alternate trash icon"></i>';
					form.appendChild(btn);
					return form;
				}
			};
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
					{ headerName: 'Date range', field: 'dates', flex: 1.3, minWidth: 260 }
				].concat(CAN_EDIT ? [deleteColDef] : []),
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




