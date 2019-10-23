<?
 // ------------------------------------------------------------------------------
 // NiDB longqc.php
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
		<title>NiDB - Longitudinal QC</title>
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
	$groupid = GetVariable("groupid");
	$protocol = GetVariable("protocol");
	
	/* determine action */
	switch ($action) {
		case 'viewlongqc':
			DisplayLonitudinalQC($groupid, $protocol);
			break;
		case 'viewprotocols':
			DisplayProtocolList($groupid);
			break;
		case 'viewgroups':
			DisplayGroupList();
			break;
		default: DisplayGroupList(); break;
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- DisplayGroupList ------------------- */
	/* -------------------------------------------- */
	function DisplayGroupList() {
	
		$urllist['groups'] = "longqc.php";
		NavigationBar("Longitudinal QC", $urllist);
		
	?>

	<table class="graydisplaytable">
		<thead>
			<tr>
				<th>Name</th>
				<th>Type</th>
				<th>Owner</th>
				<th>Group size</th>
			</tr>
		</thead>
		<tbody>
			<?
				$sqlstring = "select a.*, b.username 'ownerusername', b.user_fullname 'ownerfullname' from groups a left join users b on a.group_owner = b.user_id order by a.group_name";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$id = $row['group_id'];
					$name = $row['group_name'];
					$ownerusername = $row['ownerusername'];
					$grouptype = $row['group_type'];
					
					$sqlstring2 = "select count(*) 'count' from group_data where group_id = $id";
					$result2 = MySQLiQuery($sqlstring2, __FILE__, __LINE__);
					$row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC);
					$count = $row2['count'];
			?>
			<tr>
				<td><a href="longqc.php?action=viewprotocols&groupid=<?=$id?>"><?=$name?></a></td>
				<td><?=$grouptype?></td>
				<td><?=$ownerusername?></td>
				<td><?=$count?></td>
			</tr>
			<? 
				}
			?>
		</tbody>
	</table>
	<?
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayProtocolList ---------------- */
	/* -------------------------------------------- */
	function DisplayProtocolList($groupid) {
	
		$urllist['Groups'] = "longqc.php";
		NavigationBar("Longitudinal QC", $urllist);
		
		$sqlstring = "select a.*, b.* from groups a left join group_data b on a.group_id = b.group_id where a.group_id = $groupid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$name = $row['group_name'];
			$ownerusername = $row['ownerusername'];
			$grouptype = $row['group_type'];
			$itemids[] = "'".$row['data_id']."'";
			$modalities[$row['modality']] = $row['modality'];
		}
		?>
		<table class="smallgraydisplaytable">
			<thead>
			<tr>
				<th>Desc</th>
				<th>Alt Desc</th>
				<th>Protocol</th>
				<th>Sequence name</th>
			</tr>
			</thead>
		<?
		//PrintVariable($itemids);
		//PrintVariable($modalities);
		$studyids = implode(",",$itemids);
		if ($grouptype == "study") {
			foreach ($modalities as $key => $value) {
				$sqlstring = "select distinct series_desc, series_altdesc, series_protocol, series_sequencename from $key" . "_series where study_id in ($studyids)";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$desc = $row['series_desc'];
					$altdesc = $row['series_altdesc'];
					$protocol = $row['series_protocol'];
					$sequencename = $row['series_sequencename'];
					?>
					<tr>
						<td><a href="longqc.php?action=viewlongqc&groupid=<?=$groupid?>&protocol=<?=$desc?>"><?=$desc?></a></td>
						<td><?=$altdesc?></td>
						<td><?=$protocol?></td>
						<td><?=$sequencename?></td>
					</tr>
					<?
				}
			}
		}
		?>
		</table>
		<?
	}

	/* -------------------------------------------- */
	/* ------- DisplayLonitudinalQC --------------- */
	/* -------------------------------------------- */
	function DisplayLonitudinalQC($groupid, $protocol) {
	
		$urllist['Groups'] = "longqc.php";
		NavigationBar("Longitudinal QC", $urllist);
		
		# this only works for study groups
		$sqlstring = "select a.*, b.* from groups a left join group_data b on a.group_id = b.group_id where a.group_id = $groupid";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$name = $row['group_name'];
			$ownerusername = $row['ownerusername'];
			$grouptype = $row['group_type'];
			$studyid = $row['data_id'];
			$modality = $row['modality'];
			$seriesid = $row[$modality . "series_id"];
			
			$sqlstringA = "select *, unix_timestamp(DATE(series_datetime)) 'seriesdate' from $modality"."_series where study_id = $studyid and series_desc = '$protocol' order by seriesdate asc";
			//PrintSQL($sqlstringA);
			$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
			while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
				$seriesid = $rowA[$modality."series_id"];
				$seriesdate = $rowA['seriesdate'];
				list($path, $qadir, $uid, $studynum, $studyid, $subjectid) = GetDataPathFromSeriesID($seriesid, $modality);
				
				//$qadir = "$path/qa";
				//echo "$qadir: ";
				
				$sqlstringB = "select * from mr_qa where mrseries_id = $seriesid";
				$resultB = MySQLiQuery($sqlstringB, __FILE__, __LINE__);
				$rowB = mysqli_fetch_array($resultB, MYSQLI_ASSOC);
				$iosnr = $rowB["io_snr"];
				$pvsnr = $rowB["pv_snr"];
				$motion_rsq = $rowB["motion_rsq"];
				$moveminx = $rowB["move_minx"];
				$moveminy = $rowB["move_miny"];
				$moveminz = $rowB["move_minz"];
				$movemaxx = $rowB["move_maxx"];
				$movemaxy = $rowB["move_maxy"];
				$movemaxz = $rowB["move_maxz"];

				$iosnrs[$seriesdate]['studyid'] = $studyid;
				$iosnrs[$seriesdate]['uid'] = $uid;
				$iosnrs[$seriesdate]['studynum'] = $studynum;
				$iosnrs[$seriesdate]['subjectid'] = $subjectid;
				$iosnrs[$seriesdate]['value']= $iosnr;
				
				$pvsnrs[$seriesdate]['studyid'] = $studyid;
				$pvsnrs[$seriesdate]['uid'] = $uid;
				$pvsnrs[$seriesdate]['studynum'] = $studynum;
				$pvsnrs[$seriesdate]['subjectid'] = $subjectid;
				$pvsnrs[$seriesdate]['value'] = $pvsnr;
				
				$motionrsqs[$seriesdate] = $motion_rsq;
				$moves[$seriesdate]['minx'] = $moveminx;
				$moves[$seriesdate]['miny'] = $moveminy;
				$moves[$seriesdate]['minz'] = $moveminz;
				$moves[$seriesdate]['maxx'] = $movemaxx;
				$moves[$seriesdate]['maxy'] = $movemaxy;
				$moves[$seriesdate]['maxz'] = $movemaxz;
			}
		}
		ksort($iosnrs);
		ksort($pvsnrs);
		ksort($motionrsqs);
		ksort($moves);

		DisplayChart($iosnrs, "IO SNR", 250, 1);
		DisplayChart($pvsnrs, "PV SNR", 250, 2);
		DisplayChart($motionrsqs, "Motion r^2", 250, 3);
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayChart ----------------------- */
	/* -------------------------------------------- */
	function DisplayChart($data, $label, $height, $id) {
		$colors = GenerateColorGradient();
		?>
			<table class="grayrounded" width="100%">
				<tr>
					<td class="title"><?=$label?></td>
				</tr>
				<tr>
					<td class="body">
						<script>
							$(function() {
									var data = [
										{
										label: "<?=$label?>",
										hoverable: true,
										clickable: true,
										data: [<?
										foreach ($data as $date => $item) {
											$value = $data[$date]['value'];
											$date = $date*1000;
											if (($date > 0) && ($value > 0)) {
												$jsonstrings[] .= "['$date', $value]";
												#$jsonstrings[] .= "['$date', " . number_format($value,1,'.','') . "]";
											}
										}
									?><?=implode2(',',$jsonstrings)?>]
										}
								];
							
								var options = {
									series: {
										lines: { show: true, fill: false },
										points: { show: true }
									},
									legend: { noColumns: 6 },
									xaxis: { mode: "time", timeformat: "%Y-%m-%d" },
									yaxis: { min: 0, tickDecimals: 1 },
									selection: { mode: "x" },
								};
								var placeholder = $("#placeholder<?=$id?>");
								var plot = $.plot(placeholder, data, options);									
							});
						</script>
						<div id="placeholder<?=$id?>" style="height:<?=$height?>px;" align="center"></div>
					</td>
				</tr>
				<tr>
					<td class="body">
						<table class="tinytable">
							<thead>
								<th>Date</th>
								<th>Subject</th>
								<th>Study</th>
								<th>Value</th>
							</thead>
							<tbody>
						<?
							// get min, max
							$min = $data[0];
							$max = $data[0];
							foreach ($data as $date => $value) {
								$value = $data[$date]['value'];
								if ($value > $max) { $max = $value; }
								if ($value < $min) { $min = $value; }
							}
							$range = $max - $min;
							
							foreach ($data as $date => $value) {
								$value = $data[$date]['value'];
								$uid = $data[$date]['uid'];
								$studynum = $data[$date]['studynum'];
								$subjectid = $data[$date]['subjectid'];
								$studyid = $data[$date]['studyid'];
								if (($value > 0) && ($range > 0)) {
									$cindex = round((($value - $min)/$range)*100);
									if ($cindex > 100) { $cindex = 100; }
								}
								$date = $date;
								$date = date("D, d M Y", $date);
								?>
								<tr>
									<td><?=$date?></td>
									<td><a href="subjects.php?id=<?=$subjectid?>"><?=$uid?></a></td>
									<td><a href="subjects.php?id=<?=$studyid?>"><?=$uid?><?=$studynum?></a></td>
									<td align="right" bgcolor="<?=$colors[$cindex];?>"><tt><?=$value?><tt></td>
								</tr>
								<?
							}
						?>
							</tbody>
						</table>
					</td>
				</tr>
			</table>
		<?
	}
	
?>
<? include("footer.php") ?>