<?
 // ------------------------------------------------------------------------------
 // NiDB measures.php
 // Copyright (C) 2004 - 2019
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
		<title>NiDB - Phenotypic measures</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";
	
	//PrintVariable($_POST,'POST');
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$measureid = GetVariable("measureid");
	$enrollmentid = GetVariable("enrollmentid");
	$measurename = GetVariable('measure_name');
	$measureinstrument = GetVariable('measure_instrument');
	$measurevalue = GetVariable('measure_value');
	$measuredatecompleted = GetVariable('measure_datecompleted');
	$measurerater = GetVariable('measure_rater');
						
	/* determine action */
	switch ($action) {
		case 'addmeasure':
			AddMeasure($enrollmentid, $measurename, $measurevalue, $measuredatecompleted, $measurerater, $measureinstrument);
			DisplayMeasureList($enrollmentid);
			break;
		case 'deletemeasure':
			DeleteMeasure($measureid);
			DisplayMeasureList($enrollmentid);
			break;
		default:
			DisplayMeasureList($enrollmentid);
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- AddMeasure ------------------------- */
	/* -------------------------------------------- */
	function AddMeasure($enrollmentid, $measurename, $measurevalue, $measuredatecompleted, $measurerater, $measureinstrument) {
		$measurename = mysqli_real_escape_string($GLOBALS['linki'], $measurename);
		$measurevalue = mysqli_real_escape_string($GLOBALS['linki'], $measurevalue);
		$measuredatecompleted = mysqli_real_escape_string($GLOBALS['linki'], $measuredatecompleted);
		$measurerater = mysqli_real_escape_string($GLOBALS['linki'], $measurerater);
		$measureinstrument = mysqli_real_escape_string($GLOBALS['linki'], $measureinstrument);

		if (is_numeric($measurevalue)) {
			$measuretype = 'n';
			$valuestring = '';
			$valuenum = $measurevalue;
		}
		else {
			$measuretype = 's';
			$valuestring = $measurevalue;
			$valuenum = '';
		}
		
		$sqlstringA = "select measurename_id from measurenames where measure_name = '$measurename'";
		$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
		if (mysqli_num_rows($resultA) > 0) {
			$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
			$measurenameid = $rowA['measurename_id'];
		}
		else {
			$sqlstringA = "insert into measurenames (measure_name) values ('$measurename')";
			$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
			$measurenameid = mysqli_insert_id($GLOBALS['linki']);
		}
		if (trim($measurerater) == "") $measurerater = "null";
		else $measurerater = "'" . trim($measurerater) . "'";

		if (trim($measureinstrument) == "") $measureinstrument = "null";
		else $measureinstrument = "'" . trim($measureinstrument) . "'";
		
		if (trim($measuredatecompleted) == "") $measuredatecompleted = "null";
		else $measuredatecompleted = "'" . trim($measuredatecompleted) . "'";
		
		$sqlstring = "insert ignore into measures (enrollment_id, measure_dateentered, measurename_id, measure_type, measure_valuestring, measure_valuenum, measure_rater, measure_instrument, measure_datecomplete) values ($enrollmentid, now(), $measurenameid, '$measuretype', '$valuestring', '$valuenum', $measurerater, $measureinstrument, $measuredatecompleted)";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);	
	}


	/* -------------------------------------------- */
	/* ------- DeleteMeasure ---------------------- */
	/* -------------------------------------------- */
	function DeleteMeasure($id) {
		$sqlstring = "delete from measures where measure_id = $id";
		//echo "[$sqlstring]";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		?><div align="center"><span class="message">Measure deleted</span></div><br><br><?
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayMeasureList ----------------- */
	/* -------------------------------------------- */
	function DisplayMeasureList($enrollmentid) {
		
		if ((trim($enrollmentid) == "") || ($enrollment_id < 0)) {
			?>Invalid or blank enrollment ID [<?=$enrollment_id?>]<?
			return;
		}
		
		/* get subject's info for the breadcrumb list */
		$sqlstring = "select * from enrollment a left join subjects b on a.subject_id = b.subject_id left join projects c on a.project_id = c.project_id where a.enrollment_id = $enrollmentid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$uid = $row['uid'];
		$subjectid = $row['subject_id'];
		$projectname = $row['project_name'];
		
		$urllist['Subject List'] = "subjects.php";
		$urllist[$uid] = "subjects.php?action=display&id=$subjectid";
		$urllist["$projectname Measures"] = "measures.php?enrollmentid=$enrollmentid";
		NavigationBar("Phenotypic measures", $urllist);
		
		?>
		
		<table class="smalldisplaytable">
			<thead>
				<tr>
					<th>Name</th>
					<th>Instrument</th>
					<th>Value</th>
					<th>Date<br>Completed</th>
					<th>Date<br>Entered</th>
					<th>Rater(s)<br><span class="tiny">NiDB username(s)</span></th>
					<th>Delete</th>
				</tr>
			</thead>
			<tbody>
				<form action="measures.php" method="post">
				<input type="hidden" name="action" value="addmeasure">
				<input type="hidden" name="enrollmentid" value="<?=$enrollmentid?>">
				<tr>
					<td><input type="text" name="measure_name" size="15" required></td>
					<td><input type="text" name="measure_instrument"></td>
					<td><input type="text" name="measure_value" required></td>
					<td><input type="text" name="measure_datecompleted" class="completedatetime"></td>
					<td></td>
					<td><input type="text" name="measure_rater" value="<?=$GLOBALS['username']?>"></td>
					<td><input type="submit" value="Add"></td>
				</tr>
				</form>
				<?
					$sqlstring = "select * from measures a left join measurenames b on a.measurename_id = b.measurename_id where enrollment_id = $enrollmentid order by measure_name";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$measureid = $row['measure_id'];
						$measure_dateentered = $row['measure_dateentered'];
						$measure_name = $row['measure_name'];
						$measure_type = $row['measure_type'];
						$measure_valuestring = $row['measure_valuestring'];
						$measure_valuenum = $row['measure_valuenum'];
						$measure_instrument = $row['measure_instrument'];
						$measure_rater = $row['measure_rater'];
						$measure_datecomplete = $row['measure_datecomplete'];
						
						switch ($measure_type) {
							case 's': $value = $measure_valuestring; $type = 'string'; break;
							case 'n': $value = $measure_valuenum; $type = 'number'; break;
						}
						
						?>
						<script type="text/javascript">
							$(document).ready(function(){
								$(".edit_inline<? echo $measureid; ?>").editInPlace({
									url: "measure_inlineupdate.php",
									params: "action=editinplace&id=<? echo $series_id; ?>",
									default_text: "<i style='color:#AAAAAA'>Edit here...</i>",
									bg_over: "white",
									bg_out: "lightyellow",
								});
							});
						</script>
						<tr>
							<td><?=$measure_name?></td>
							<td><?=$measure_instrument?></td>
							<td><?=$value?></td>
							<td bgcolor="<?=$color?>"><?=$measure_datecomplete?></td>
							<td><?=$measure_dateentered?><br><?=$measure_dateentered2?></td>
							<td><?=$measure_rater?><br><?=$measure_rater2?></td>
							<td align="right" class="delete">
								<a href="javascript:decision('Are you sure you want to delete this measure?', 'measures.php?action=deletemeasure&measureid=<?=$measureid?>&enrollmentid=<?=$enrollmentid?>')" class="delete">X</a>
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
