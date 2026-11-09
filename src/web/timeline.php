<?
 // ------------------------------------------------------------------------------
 // NiDB timeline.php
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

	declare(strict_types = 1);
	define("LEGIT_REQUEST", true);
	session_start();
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Timeline</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	/* ----- setup variables ----- */
	/* read-only page: the filter form is a GET, so refresh/Back/bookmarks just re-run the view */
	$action = GetVariable("action");
	$enrollmentid = (int)GetVariable("enrollmentid");
	$id = (int)GetVariable("id");
	$startdatetime = GetVariable("startdatetime");
	$enddatetime = GetVariable("enddatetime");
	$selectedprotocols = GetVariable("selectedprotocols");
	$allobservations = (GetVariable("allobservations") == "1");

	if ($enrollmentid < 1)
		$enrollmentid = $id;

	/* datetime-local values ("YYYY-MM-DDTHH:MM") become "YYYY-MM-DD HH:MM"; blank means no limit */
	$startdatetime = (is_string($startdatetime) && (trim($startdatetime) != "")) ? str_replace("T", " ", trim($startdatetime)) : null;
	$enddatetime = (is_string($enddatetime) && (trim($enddatetime) != "")) ? str_replace("T", " ", trim($enddatetime)) : null;

	/* selected protocols are "modality:protocol" strings; null means show everything */
	if (is_array($selectedprotocols))
		$selectedprotocols = array_values(array_filter($selectedprotocols, 'is_string'));
	else
		$selectedprotocols = null;

	DisplayTimeline($enrollmentid, $startdatetime, $enddatetime, $selectedprotocols, $allobservations);


	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- DisplayTimeline -------------------- */
	/* -------------------------------------------- */
	/* Imaging series and observations are project data: View Data on the enrollment's project */
	function DisplayTimeline($enrollmentid, $startdatetime, $enddatetime, $selectedprotocols, $allobservations) {

		$enrollment = ($enrollmentid > 0) ? GetEnrollment($enrollmentid) : null;
		if ($enrollment == null) {
			Error("Enrollment not found");
			return;
		}
		$projectid = (int)$enrollment['project_id'];
		$perms = GetCurrentUserProjectPermissions(array($projectid));
		DisplayPermissions($perms);
		if (!GetPerm($perms, 'viewdata', $projectid)) {
			Error("You do not have permission to view data in this project");
			return;
		}

		$series = array();
		$protocols = array();
		$modalities = array();
		$selprot = array();
		$selectedModalities = array();
		$mindate = null;
		$maxdate = null;

		$all = is_null($selectedprotocols);
		if (!$all) {
			/* associative array of selected protocols: modality => [protocols] */
			foreach ($selectedprotocols as $sp) {
				$parts = explode(":", $sp, 2);
				if (count($parts) != 2) continue;
				$selprot[$parts[0]][] = $parts[1];
				$selectedModalities[] = $parts[0];
			}
			$selectedModalities = array_unique($selectedModalities);
		}

		/* get list of studies for this enrollment */
		$sqlstring = "select a.*, datediff(a.study_datetime, c.birthdate) 'ageatscan' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.enrollment_id = ? order by a.study_num asc";
		$stmt2 = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt2, 'i', $enrollmentid);
		$result2 = MySQLiBoundQuery($stmt2, __FILE__, __LINE__, $sqlstring, [$enrollmentid]);
		if (mysqli_num_rows($result2) > 0) {
			$i = 0;

			while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
				$study_id = (int)$row2['study_id'];
				$study_num = $row2['study_num'];
				$modality = (string)$row2['study_modality'];
				$study_datetime = $row2['study_datetime'];
				$study_ageatscan = $row2['study_ageatscan'];
				$calcage = (float)$row2['ageatscan'] / 365.25;
				$study_site = $row2['study_site'];
				$study_type = $row2['study_type'];

				/* get the age at the time of the study */
				if ((!is_null($study_ageatscan)) && ((float)$study_ageatscan != 0))
					$age = (float)$study_ageatscan;
				else
					$age = $calcage;

				if ($modality == "")
					continue;

				/* the series table name comes from the study's modality: validate it, it can't be bound */
				$seriestable = GetSeriesTableName($modality);
				if (($seriestable == "") || !DatabaseTableExists($seriestable)) {
					echo "<span style='color:red'>Invalid modality [" . htmlspecialchars($modality) . "]</span><br>";
					continue;
				}

				$sqlstring3 = "select * from `$seriestable` where study_id = ? order by series_num asc";
				$stmt3 = mysqli_prepare($GLOBALS['linki'], $sqlstring3);
				mysqli_stmt_bind_param($stmt3, 'i', $study_id);
				$result3 = MySQLiBoundQuery($stmt3, __FILE__, __LINE__, $sqlstring3, [$study_id]);
				while ($row3 = mysqli_fetch_array($result3, MYSQLI_ASSOC)) {
					$protocol = (string)((($row3['series_desc'] ?? '') == "") ? ($row3['series_protocol'] ?? '') : $row3['series_desc']);
					$seriesdatetime = (string)($row3['series_datetime'] ?? '');
					$seriesnum = $row3['series_num'] ?? '';
					$seriestr = (float)($row3['series_tr'] ?? 0);
					$boldreps = (int)($row3['bold_reps'] ?? 0);
					$dimT = (int)($row3['dimT'] ?? 0);
					$numfiles = (int)($row3['numfiles'] ?? 0);
					$seriesduration = $row3['series_duration'] ?? '';

					if (is_null($mindate) || ($seriesdatetime < $mindate))
						$mindate = $seriesdatetime;
					if (is_null($maxdate) || ($seriesdatetime > $maxdate))
						$maxdate = $seriesdatetime;

					/* calculate scan/experiment duration (seconds) */
					if ((string)$seriesduration == "") {
						if (($boldreps > 1) || ($dimT > 1))
							$duration = ($seriestr * max($boldreps, $dimT)) / 1000.0;
						else
							$duration = ($seriestr * $numfiles) / 1000.0;
					}
					else {
						$duration = (float)$seriesduration;
					}

					/* show all modalities/protocols, or only the selected ones, within the date range */
					if ($all || (in_array($modality, $selectedModalities) && in_array($protocol, $selprot[$modality] ?? array()))) {
						if (is_null($startdatetime) || is_null($enddatetime) || (($seriesdatetime > $startdatetime) && ($seriesdatetime < $enddatetime))) {
							$series[$i] = array(
								'studynum' => $study_num,
								'seriesnum' => $seriesnum,
								'modality' => $modality,
								'studydate' => $study_datetime,
								'seriesdate' => $seriesdatetime,
								'protocol' => $protocol,
								'age' => $age,
								'studysite' => $study_site,
								'studytype' => $study_type,
								'duration' => $duration,
							);
						}
					}

					if (!isset($protocols[$modality]) || !in_array($protocol, $protocols[$modality]))
						$protocols[$modality][] = $protocol;

					$modalities[] = $modality;

					$i++;
				}
				mysqli_stmt_close($stmt3);
			}

			/* get observations */
			if ($allobservations) {
				$sqlstringA = "select observation_name, observation_instrument, observation_startdate, observation_duration from observations where enrollment_id = ? order by observation_name";
				$stmtA = mysqli_prepare($GLOBALS['linki'], $sqlstringA);
				mysqli_stmt_bind_param($stmtA, 'i', $enrollmentid);
				$resultA = MySQLiBoundQuery($stmtA, __FILE__, __LINE__, $sqlstringA, [$enrollmentid]);
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					$series[$i] = array(
						'studynum' => '',
						'seriesnum' => '',
						'modality' => 'Measure',
						'studydate' => $rowA['observation_startdate'],
						'seriesdate' => (string)$rowA['observation_startdate'],
						'protocol' => (string)$rowA['observation_name'],
						'age' => 0.0,
						'studysite' => '',
						'studytype' => $rowA['observation_instrument'],
						'duration' => (float)$rowA['observation_duration'],
					);
					$i++;
				}
				mysqli_stmt_close($stmtA);
			}

			$modalities = array_unique($modalities);

			DisplayOptionsTable($protocols, $modalities, $enrollmentid, $selprot, $allobservations, $startdatetime, $enddatetime, $mindate, $maxdate);

			if (count($series) > 0)
				$series = array_msort($series, array('seriesdate'=>SORT_ASC, 'seriesnum'=>SORT_ASC, 'modality'=>SORT_DESC));

			/* chart rows, built server-side and passed to JS as JSON (protocol names come from DICOM headers) */
			$datarows = array();
			foreach ($series as $v) {
				$sdate = str_replace(" ", "T", $v['seriesdate']);
				$stime = strtotime($v['seriesdate']);
				if ($stime === false) continue;
				$edate = date('Y-m-d\TH:i:s', (int)($stime + $v['duration']));
				$datarows[] = array((string)$v['protocol'], $sdate, $edate);
			}
			$datajson = json_encode($datarows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_INVALID_UTF8_SUBSTITUTE);
			?>

			<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
			<script type="text/javascript">
				google.charts.load('current', {'packages':['timeline']});
				google.charts.setOnLoadCallback(drawChart);
				function drawChart() {
					var container = document.getElementById('timeline');
					var chart = new google.visualization.Timeline(container);
					var dataTable = new google.visualization.DataTable();

					dataTable.addColumn({ type: 'string', id: 'Protocol' });
					dataTable.addColumn({ type: 'date', id: 'Start' });
					dataTable.addColumn({ type: 'date', id: 'End' });
					var rows = <?=$datajson?>;
					dataTable.addRows(rows.map(function(r) { return [r[0], new Date(r[1]), new Date(r[2])]; }));
					var options = { avoidOverlappingGridLines: false, height: 800, timeline: { barLabelStyle: {fontName: "arial", fontSize: "8" }} };
					chart.draw(dataTable, options);
				}
			</script>

			<div id="timeline" style="height: 800px;"></div>

			<table class="smalldisplaytable">
				<thead>
					<th>Study</th>
					<th>Series</th>
					<th>Modality</th>
					<th>Study Date</th>
					<th>Series Date</th>
					<th>Protocol</th>
					<th>Age<span class="tiny">&nbsp;y</span></th>
					<th>Site</th>
					<th>Visit</th>
					<th>Duration</th>
				</thead>
				<tbody>
				<?
					foreach ($series as $tr) {
						?>
						<tr>
							<td><?=htmlspecialchars((string)$tr['studynum'])?></td>
							<td><?=htmlspecialchars((string)$tr['seriesnum'])?></td>
							<td><?
							 if ($tr['modality'] == "") { ?><span style="color: white; background-color: red">&nbsp;blank&nbsp;</span><? }
							 else { echo htmlspecialchars((string)$tr['modality']); }
							?></td>
							<td><?=htmlspecialchars((string)$tr['studydate'])?></td>
							<td><?=htmlspecialchars((string)$tr['seriesdate'])?></td>
							<td><?=htmlspecialchars((string)$tr['protocol'])?></td>
							<td><?=number_format((float)$tr['age'], 1)?></td>
							<td><?=htmlspecialchars((string)$tr['studysite'])?></td>
							<td><?=htmlspecialchars((string)$tr['studytype'])?></td>
							<td><?=htmlspecialchars((string)$tr['duration'])?></td>
						</tr>
						<?
					}
				?>
				</tbody>
			</table>
			<?
		}
		else {
			echo "No imaging studies";
		}
		mysqli_stmt_close($stmt2);
	}


	/* -------------------------------------------- */
	/* ------- DisplayOptionsTable ---------------- */
	/* -------------------------------------------- */
	/* the filter form. It changes nothing, so it's a GET */
	function DisplayOptionsTable($protocols, $modalities, $enrollmentid, $selprot, $allobservations, $startdatetime, $enddatetime, $mindate, $maxdate) {

		natsort($modalities);

		?>
		<div style="background-color: #eee; border: 2px solid #888; border-radius: 8px; padding: 10px;">

		<form method="get" id="form1" action="timeline.php">
		<input type="hidden" name="action" value="displaytimeline">
		<input type="hidden" name="enrollmentid" value="<?=(int)$enrollmentid?>">
		<b>Protocols</b>
		<table style="font-size: smaller;">
			<tr>
			<?
			foreach ($modalities as $modality) {
				$p = $protocols[$modality] ?? array();
				natsort($p);
				/* modality passed GetSeriesTableName(), so it is alphanumeric; safe in ids/classes */
				$parentCheckID = "parent-" . preg_replace('/[^A-Za-z0-9]/', '', $modality);
				$childCheckClass = "child-" . preg_replace('/[^A-Za-z0-9]/', '', $modality);
				?>
				<script>
					$(document).ready(function() {
						$("#<?=$parentCheckID?>").click(function() {
							$(".<?=$childCheckClass?>").prop("checked", this.checked);
						});

						$('.<?=$childCheckClass?>').click(function() {
						if ($('.<?=$childCheckClass?>:checked').length == $('.<?=$childCheckClass?>').length) {
							$('#<?=$parentCheckID?>').prop('checked', true);
							} else {
								$('#<?=$parentCheckID?>').prop('checked', false);
							}
						});
					});
				</script>

				<td valign="top" style="border-right: 1px solid #aaa; padding: 8px">
				<b style="font-size: larger;"><?=htmlspecialchars($modality)?></b> &nbsp; <label style="color: #666;"><input type="checkbox" id="<?=$parentCheckID?>"> Select all</label>
				<br><br>
				<div style="column-count: 2; column-fill: auto;">
				<?
				foreach ($p as $protocol) {
					$checked = (isset($selprot[$modality]) && in_array($protocol, $selprot[$modality])) ? "checked" : "";
					?>
					<label><input type="checkbox" class="all <?=$childCheckClass?>" name="selectedprotocols[]" value="<?=htmlspecialchars($modality . ":" . $protocol)?>" <?=$checked?>> <?=htmlspecialchars($protocol)?></label><br>
					<?
				}
				?>
				</div>
				</td>
				<?
			}
			?>
			</tr>
		</table>
		<hr>
		<b>Measures</b>
		<label><input type="checkbox" name="allobservations" value="1" <? if ($allobservations) { echo "checked"; } ?>> Include all observations</label>
		<hr>
		<b>Series Date</b><br>
		Data range <?=htmlspecialchars((string)$mindate)?> to <?=htmlspecialchars((string)$maxdate)?><br>
		<input type="datetime-local" name="startdatetime" value="<?=htmlspecialchars(str_replace(" ", "T", (string)$startdatetime))?>"> to <input type="datetime-local" name="enddatetime" value="<?=htmlspecialchars(str_replace(" ", "T", (string)$enddatetime))?>">

		<br><br><br>
		<input type="submit" value="Update" class="ui primary button">

		</form>
		</div>
		<?
	}

?>


<? include("footer.php") ?>
