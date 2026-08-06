<?
 // ------------------------------------------------------------------------------
 // NiDB dicomimport.php
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
		<title>NiDB - DICOM Import</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	DisplayDicomImport();


	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- DisplayDicomImport ----------------- */
	/* -------------------------------------------- */
	function DisplayDicomImport() {

		/* ----- counts of files waiting to be parsed/imported ----- */
		$received = 0;
		$parsed = 0;
		$error = 0;
		$sqlstring = "select file_status, count(*) 'count' from dicom_monitor where file_status in ('Received', 'Parsed', 'Error') group by file_status";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			if ($row['file_status'] == 'Received')
				$received = (int)$row['count'];
			elseif ($row['file_status'] == 'Parsed')
				$parsed = (int)$row['count'];
			elseif ($row['file_status'] == 'Error')
				$error = (int)$row['count'];
		}
		$total = $received + $parsed + $error;
		?>

		<div class="ui container">

			<div class="ui grid">
				<div class="twelve wide column">
					<h2 class="ui header">
						<div class="content">
							DICOM receiver monitor
							<div class="sub header">DICOM files received in the last 14 days</div>
						</div>
					</h2>
				</div>
				<div class="four wide right aligned column" style="color:#888">
					Last refreshed <span id="lastrefresh"></span>
				</div>
			</div>

			<!-- Received / Parsed / Error / Total -->
			<div style="text-align: center; margin-bottom: 2.5em">
				<table class="ui small very compact celled grey table" style="display: inline-table; text-align: left; width: 320px">
					<thead>
						<tr>
							<th>Status</th>
							<th class="right aligned">Count</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>Received</td>
							<td class="right aligned" id="count_received"><?=number_format($received)?></td>
						</tr>
						<tr>
							<td>Parsed</td>
							<td class="right aligned" id="count_parsed"><?=number_format($parsed)?></td>
						</tr>
						<tr>
							<td>Error</td>
							<td class="right aligned" id="count_error"><?=number_format($error)?></td>
						</tr>
						<tr>
							<td><b>Total</b></td>
							<td class="right aligned"><b id="count_total"><?=number_format($total)?></b></td>
						</tr>
					</tbody>
				</table>
			</div>

			<script>
				/* Poll the receiver data every second and update in place, rather than reloading the
				   whole page (which would collapse the accordion). The counts are always refreshed;
				   the archived summary is a heavier grouped query, so it is only refetched while the
				   accordion is actually open. The two requests run in parallel and the next tick is
				   scheduled only after both settle, so slow responses cannot pile up. */
				(function() {
					/* the data on the page was rendered by the server as this page loaded */
					document.getElementById('lastrefresh').textContent = new Date().toLocaleTimeString();

					function refreshCounts() {
						return fetch('ajaxapi.php?action=dicomreceivercounts', { cache: 'no-store' })
							.then(function(r) { return r.ok ? r.json() : null; })
							.then(function(d) {
								if (!d) return;
								document.getElementById('count_received').textContent = Number(d.received).toLocaleString();
								document.getElementById('count_parsed').textContent   = Number(d.parsed).toLocaleString();
								document.getElementById('count_error').textContent    = Number(d.error).toLocaleString();
								document.getElementById('count_total').textContent    = Number(d.total).toLocaleString();
								/* stamp the time only on a successful update, so it reflects when the
								   displayed data was actually last refreshed */
								document.getElementById('lastrefresh').textContent = new Date().toLocaleTimeString();
							});
					}

					function archivedIsOpen() {
						/* Fomantic marks the shown accordion content with the 'active' class */
						var c = document.querySelector('.ui.accordion > .content');
						return c && c.classList.contains('active');
					}

					function refreshArchived() {
						if (!archivedIsOpen()) return Promise.resolve();
						return fetch('ajaxapi.php?action=dicomarchivedsummary', { cache: 'no-store' })
							.then(function(r) { return r.ok ? r.text() : null; })
							.then(function(html) {
								/* only replace while still open, so a slow response can't clobber a
								   panel the user just collapsed */
								if (html !== null && archivedIsOpen())
									document.getElementById('archivedContainer').innerHTML = html;
							});
					}

					function poll() {
						/* allSettled so one failing request doesn't abort the other or the loop */
						Promise.allSettled([refreshCounts(), refreshArchived()])
							.finally(function() { setTimeout(poll, 1000); });
					}
					setTimeout(poll, 1000);
				})();
			</script>

			<!-- Archived summary -->
			<div class="ui fluid styled accordion">
				<div class="title">
					<i class="dropdown icon"></i> Archived
				</div>
				<div class="content">
					<div id="archivedContainer"><?=DicomArchivedSummaryHTML()?></div>
				</div>
			</div>

		</div>
		<?
	}
?>


<? include("footer.php") ?>
