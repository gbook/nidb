<?
 // ------------------------------------------------------------------------------
 // NiDB filesio.php
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
		<title>NiDB - File I/O  status</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	/* get variables */
	$action = GetVariable("action");
	$fileioid = GetVariable("fileioid");

	switch ($action) {
		case 'cancelfileio':
			CancelFileIO($fileioid);
			ShowList();
			break;
		case 'deletefileio':
			DeleteFileIO($fileioid);
			ShowList();
			break;
		default:
			ShowList();
	}


	/* --------------------------------------------------- */
	/* ------- CancelFileIO ------------------------------ */
	/* --------------------------------------------------- */
	function CancelFileIO($fileioid) {
		$sqlstring = "update fileio_requests set request_status = 'cancelled' where fileiorequest_id = $fileioid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		Notice("File I/O $fileioid has been cancelled");
	}

	/* --------------------------------------------------- */
	/* ------- DeleteFileIO ------------------------------ */
	/* --------------------------------------------------- */
	function DeleteFileIO($fileioid) {
		$sqlstring = "delete from fileio_requests where fileiorequest_id = $fileioid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		Notice("File I/O $fileioid has been deleted");
	}


	/* --------------------------------------------------- */
	/* ------- ShowList ---------------------------------- */
	/* --------------------------------------------------- */
	function ShowList() {
		if ($GLOBALS['issiteadmin']) {
			$sqlstring = "SELECT `fileiorequest_id`, `fileio_operation`, `data_type`, `request_status`, `request_message`, `username`, `requestdate`, `startdate`, `enddate` FROM `fileio_requests` order by fileiorequest_id desc limit 1000";
		} else {
			$sqlstring = "SELECT `fileiorequest_id`, `fileio_operation`, `data_type`, `request_status`, `request_message`, `username`, `requestdate`, `startdate`, `enddate` FROM `fileio_requests` where username = '" . $GLOBALS['username'] . "' order by fileiorequest_id desc limit 1000";
		}

		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$rows = [];
		$pendingCount = 0;
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			if ($row['request_status'] === 'pending') $pendingCount++;
			$enddate = ($row['enddate'] === '0000-00-00 00:00:00' || $row['enddate'] === '') ? '' : $row['enddate'];

			$duration = '';
			$validStart = ($row['startdate'] !== '0000-00-00 00:00:00' && $row['startdate'] !== '');
			$validEnd   = ($row['enddate']   !== '0000-00-00 00:00:00' && $row['enddate']   !== '');
			if ($validStart && $validEnd) {
				$diff = strtotime($row['enddate']) - strtotime($row['startdate']);
				if ($diff > 0) {
					$h = (int)floor($diff / 3600);
					$m = (int)floor(($diff % 3600) / 60);
					$s = (int)($diff % 60);
					if ($h > 0)      $duration = "{$h}h {$m}m {$s}s";
					elseif ($m > 0)  $duration = "{$m}m {$s}s";
					else             $duration = "{$s}s";
				}
			}

			$rows[] = [
				'fileiorequest_id' => (int)$row['fileiorequest_id'],
				'username'         => $row['username'],
				'requestdate'      => $row['requestdate'],
				'fileio_operation' => $row['fileio_operation'],
				'operation'        => ucfirst($row['fileio_operation']) . ' ' . $row['data_type'],
				'request_status'   => $row['request_status'],
				'request_message'  => $row['request_message'],
				'enddate'          => $enddate,
				'duration'         => $duration,
			];
		}
		?>
		<link rel="stylesheet" href="//cdn.jsdelivr.net/npm/ag-grid-community@31/styles/ag-grid.css">
		<link rel="stylesheet" href="//cdn.jsdelivr.net/npm/ag-grid-community@31/styles/ag-theme-alpine.css">

		<div style="margin-bottom:8px;display:flex;align-items:center;gap:10px">
			<input type="text" id="filterInput" placeholder="Search..." oninput="gridApi.setQuickFilter(this.value)" style="padding:5px 8px;width:250px;border:1px solid #ccc;border-radius:4px">
			<span id="pendingLabel" class="ui <?= $pendingCount > 0 ? 'yellow' : 'green' ?> label" style="font-size:1em;min-width:160px"><?= $pendingCount > 0 ? '<i class="spinner loading icon"></i>' : '' ?><?= $pendingCount ?> pending operations</span>
		</div>
		<div id="fileioGrid" class="ag-theme-alpine" style="height:600px;width:100%"></div>

		<script src="//cdn.jsdelivr.net/npm/ag-grid-community@31/dist/ag-grid-community.min.js"></script>
		<script>
		const rowData = <?= json_encode($rows) ?>;

		const opIcons = {
			'copy':                   'copy',
			'delete':                 'trash',
			'move':                   'exchange',
			'detach':                 'unlink',
			'anonymize':              'user secret',
			'createlinks':            'linkify',
			'rearchive':              'archive',
			'rearchivesubject':       'archive',
			'rearchiveidonly':        'archive',
			'rearchivesubjectidonly': 'archive',
			'rechecksuccess':         'check circle',
			'merge':                  'compress',
		};

		const columnDefs = [
			{ field: 'username',        headerName: 'Requested By',  sortable: true, filter: true, width: 140 },
			{ field: 'requestdate',     headerName: 'Request Date',  sortable: true, filter: true, width: 170 },
			{
				field: 'operation', headerName: 'Operation', sortable: true, filter: true, width: 200,
				cellRenderer: params => {
					const icon = opIcons[params.data.fileio_operation] || 'file';
					const span = document.createElement('span');
					span.innerHTML = `<i class="${icon} icon"></i>${params.value}`;
					return span;
				}
			},
			{
				field: 'request_status', headerName: 'Status', sortable: true, filter: true, width: 180,
				cellStyle: params => {
					if (params.value === 'complete') return { background: '#fcfff5', color: '#2c662d' };
					if (params.value === 'error')    return { background: '#fff6f6', color: '#9f3a38' };
					return {};
				},
				cellRenderer: params => {
					const icons = { complete: 'check circle', error: 'exclamation circle' };
					const icon = icons[params.value] || null;
					const duration = params.data.duration ? ` <span style="opacity:0.7;font-size:0.9em">(${params.data.duration})</span>` : '';
					const span = document.createElement('span');
					span.innerHTML = (icon ? `<i class="${icon} icon"></i>` : '') + params.value + duration;
					return span;
				}
			},
			{ field: 'request_message', headerName: 'Message',       sortable: true, filter: true, flex: 2 },
			{ field: 'enddate',         headerName: 'Complete Date',  sortable: true, filter: true, width: 170 },
			{
				headerName: 'Action', sortable: false, filter: false, width: 140,
				cellRenderer: params => {
					const { fileiorequest_id, request_status } = params.data;
					const div = document.createElement('div');
					div.style.paddingTop = '3px';
					if (request_status === 'pending') {
						const btn = document.createElement('a');
						btn.href = `filesio.php?action=cancelfileio&fileioid=${fileiorequest_id}`;
						btn.className = 'ui small compact red button';
						btn.textContent = 'Cancel';
						btn.onclick = e => { if (!confirm('Are you sure?')) e.preventDefault(); };
						div.appendChild(btn);
					} else if (request_status === 'error' || request_status === 'cancelled') {
						const btn = document.createElement('a');
						btn.href = `filesio.php?action=deletefileio&fileioid=${fileiorequest_id}`;
						btn.className = 'ui small compact red button';
						btn.textContent = 'Remove';
						btn.onclick = e => { if (!confirm('Are you sure?')) e.preventDefault(); };
						div.appendChild(btn);
					}
					return div;
				}
			}
		];

		const gridOptions = {
			columnDefs,
			rowData,
			defaultColDef: { resizable: true },
		};

		const gridDiv = document.getElementById('fileioGrid');
		const gridApi = agGrid.createGrid(gridDiv, gridOptions);

		// Poll pending rows every second; stop when none remain
		const pendingIds = new Set(
			rowData.filter(r => r.request_status === 'pending').map(r => r.fileiorequest_id)
		);

		function updatePendingLabel() {
			const label = document.getElementById('pendingLabel');
			const n = pendingIds.size;
			label.className = 'ui ' + (n > 0 ? 'yellow' : 'green') + ' label';
			label.innerHTML = (n > 0 ? '<i class="spinner loading icon"></i>' : '') + n + ' pending operations';
		}

		function pollPendingStatus() {
			if (pendingIds.size === 0) return;
			fetch('ajaxapi.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: 'action=getfileiostatus&ids=' + encodeURIComponent(JSON.stringify([...pendingIds]))
			})
				.then(r => {
					if (!r.ok) throw new Error('HTTP ' + r.status);
					return r.json();
				})
				.then(data => {
					if (!Array.isArray(data)) return;
					data.forEach(item => {
						if (item.request_status !== 'pending') {
							gridApi.forEachNode(node => {
								if (node.data && node.data.fileiorequest_id === item.fileiorequest_id) {
									const enddate = (item.enddate === '0000-00-00 00:00:00' || item.enddate === '') ? '' : item.enddate;
									node.setData({ ...node.data, request_status: item.request_status, request_message: item.request_message, enddate, duration: item.duration || '' });
								}
							});
							pendingIds.delete(item.fileiorequest_id);
						}
					});
					updatePendingLabel();
					if (pendingIds.size === 0) clearInterval(pollTimer);
				})
				.catch(e => console.error('FileIO poll error:', e));
		}

		const pollTimer = pendingIds.size > 0 ? setInterval(pollPendingStatus, 1000) : null;
		</script>
		<?
	}

require "footer.php";
?>
