<?
 // ------------------------------------------------------------------------------
 // NiDB qa.php
 // Copyright (C) 2004 - 2025
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

	require "functions.php";
	require "includes_php.php";

	$id = GetVariable("id");

	/* get the path to the QA info */
	$sqlstring = "select a.*, b.study_num, d.uid from mr_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.mrseries_id = $id";
	$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
	$series_num = $row['series_num'];
	$study_num = $row['study_num'];
	$uid = $row['uid'];
	
	$thumbpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/thumb.png";
	$qapath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/qa";
	$thumbinfo = getimagesize("$qapath/thumb_lut.png");
	$thumbheight = $thumbinfo[1];
?>
	<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>QA for <?=$uid?> study <?=$study_num?> series <?=$series_num?></title>
		<script type="text/javascript" src="scripts/jquery-1.3.2.min.js"></script>
		<script type="text/javascript" src="scripts/jquery.flot.js"></script>
	</head>
	
	<body style="font-family: arial, helvetica, sans serif">
	<table width="100%">
		<tr>
			<td align="center">
				<img style="border: solid 1px #666666" src="data:image/png;base64,<?=base64_encode(file_get_contents("$thumbpath"))?>"><br>
				Middle slice
			</td>
			<td align="center">
				<table>
					<tr>
						<td>
							<img style="border: solid 1px #666666" src="data:image/png;base64,<?=base64_encode(file_get_contents("$qapath/thumb_lut.png"))?>">
						</td>
						<td>
							<img width="15" height="<?=$thumbheight?>" style="border: solid 1px #666666" src="data:image/png;base64,<?=base64_encode(file_get_contents("$qapath/gradient.png"))?>">
						</td>
					</tr>
				</table>
				Color-mapped
			</td>
			<td align="center">
				<img src="data:image/png;base64,<?=base64_encode(file_get_contents("$qapath/thumb_fft.png"))?>"><br>
				FFT
			</td>
		</tr>
	</table>
<?
	$motionfile = "$qapath/MotionCorrection.txt";
	if (file_exists($motionfile)) {
		$filecontents = file($motionfile);
		
		$i = 0;
		$maxx = $maxy = $maxz = $maxpitch = $maxroll = $maxyaw = 0.0;
		
		foreach ($filecontents as $line) {
			$line = trim($line);
			list($pi,$ro,$ya,$x,$y,$z) = preg_split('/\s+/', $line);
			
			$fmovex[] = "[$i, " . number_format($x,6) . "]";
			$fmovey[] = "[$i, " . number_format($y,6) . "]";
			$fmovez[] = "[$i, " . number_format($z,6) . "]";
			$fmovepi[] = "[$i, " . number_format($pi,6) . "]";
			$fmovero[] = "[$i, " . number_format($ro,6) . "]";
			$fmoveya[] = "[$i, " . number_format($ya,6) . "]";

			$movex[] = $x;
			$movey[] = $y;
			$movez[] = $z;
			$movepi[] = $pi;
			$movero[] = $ro;
			$moveya[] = $ya;
			
			/*if ($x > $maxx) { $maxx = $x; }
			if ($y > $maxy) { $maxy = $y; }
			if ($z > $maxz) { $maxz = $z; }
			if ($pi > $maxpitch) { $maxpitch = $pi; }
			if ($ro > $maxroll) { $maxroll = $ro; }
			if ($ya > $maxyaw) { $maxyaw = $ya; }
			
			if ($x < $minx) { $minx = $x; }
			if ($y < $miny) { $miny = $y; }
			if ($z < $minz) { $minz = $z; }
			if ($pi < $minpitch) { $minpitch = $pi; }
			if ($ro < $minroll) { $minroll = $ro; }
			if ($ya < $minyaw) { $minyaw = $ya; }*/
			
			$i++;
		}
		$minx = min($movex);
		$miny = min($movey);
		$minz = min($movez);
		$minpitch = min($movepi);
		$minroll = min($movero);
		$minyaw = min($moveya);
		
		$maxx = max($movex);
		$maxy = max($movey);
		$maxz = max($movez);
		$maxpitch = max($movepi);
		$maxroll = max($movero);
		$maxyaw = max($moveya);
		
		$stdx = StdDev($movex);
		$stdy = StdDev($movey);
		$stdz = StdDev($movez);
		$stdpitch = StdDev($movepi);
		$stdroll = StdDev($movero);
		$stdyaw = StdDev($moveya);
		
		$rangex = abs($minx) + abs($maxx);
		$rangey = abs($miny) + abs($maxy);
		$rangez = abs($minz) + abs($maxz);
		$rangepitch = abs($minpitch) + abs($maxpitch);
		$rangeroll = abs($minroll) + abs($maxroll);
		$rangeyaw = abs($minyaw) + abs($maxyaw);
	?>

	<table>
		<tr>
			<td align="center">
				<b>Translation</b>
				<div id="movementgraph" style="width:400px;height:200px;"></div>
			</td>
			<td>
				<table style="font-size:10pt">
					<tr style="font-weight: bold">
						<td align="center"></td>
						<td align="center">Movement <span style="font-weight: normal; font-size: 8pt; color: gray">mm</span></td>
						<td align="center">Total <span style="font-weight: normal; font-size: 8pt; color: gray">mm</span></td>
					</tr>
					<tr>
						<td><b>X</b></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><?=number_format($minx,2)?> &emsp;<?=number_format($maxx,2)?></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><b><?=number_format($rangex,2)?></b> &plusmn;<?=number_format($stdx,2)?></td>
					</tr>
					<tr>
						<td><b>Y</b></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><?=number_format($miny,2)?> &emsp;<?=number_format($maxy,2)?></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><b><?=number_format($rangey,2)?></b> &plusmn;<?=number_format($stdy,2)?></td>
					</tr>
					<tr>
						<td><b>Z</b></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><?=number_format($minz,2)?> &emsp;<?=number_format($maxz,2)?></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><b><?=number_format($rangez,2)?></b> &plusmn;<?=number_format($stdz,2)?></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>

	<br><br>
	<table>
		<tr>
			<td align="center">
				<b>Rotation</b>
				<div id="rotationgraph" style="width:400px;height:200px;"></div>
			</td>
			<td>
				<table style="font-size:10pt">
					<tr style="font-weight: bold">
						<td align="center"></td>
						<td align="center">Rotation <span style="font-weight: normal; font-size: 8pt; color: gray">mm</span></td>
						<td align="center">Total <span style="font-weight: normal; font-size: 8pt; color: gray">mm</span></td>
					</tr>
					<tr>
						<td><b>Pitch</b></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><?=number_format($minpitch,2)?>&deg; &emsp;<?=number_format($maxpitch,2)?>&deg;</td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><b><?=number_format($rangepitch,2)?></b> &plusmn;<?=number_format($stdpitch,2)?></td>
					</tr>
					<tr>
						<td><b>Roll</b></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><?=number_format($minroll,2)?>&deg; &emsp;<?=number_format($maxroll,2)?>&deg;</td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><b><?=number_format($rangeroll,2)?></b> &plusmn;<?=number_format($stdroll,2)?></td>
					</tr>
					<tr>
						<td><b>Yaw</b></td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><?=number_format($minyaw,2)?>&deg; &emsp;<?=number_format($maxyaw,2)?>&deg;</td>
						<td style="background-color:#EFEFEF; padding: 3px 10px"><b><?=number_format($rangeyaw,2)?></b> &plusmn;<?=number_format($stdyaw,2)?></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	
	<script id="source" language="javascript" type="text/javascript">
		$(function () {
			var movex = [<? echo implode(', ',$fmovex); ?>];
			var movey = [<? echo implode(', ',$fmovey); ?>];
			var movez = [<? echo implode(', ',$fmovez); ?>];
			$.plot($("#movementgraph"), [ { label: "x (mm)", color: '#F00', data: movex }, { label: "y (mm)", color: '#4B4', data: movey }, { label: "z (mm)", color: '#00F', data: movez } ]);

			var movepi = [<? echo implode(', ',$fmovepi); ?>];
			var movero = [<? echo implode(', ',$fmovero); ?>];
			var moveya = [<? echo implode(', ',$fmoveya); ?>];
			$.plot($("#rotationgraph"), [ { label: "pitch (deg)", color: '#F00', data: movepi }, { label: "roll (deg)", color: '#4B4', data: movero }, { label: "yaw (deg)", color: '#00F', data: moveya } ]);
		});
	</script>
	
	</body>
	</html>
	<?
	}

	
# ----------------------------------------------------------
# --------- StdDev -----------------------------------------
# ----------------------------------------------------------
function StdDev($a)
{
  //variable and initializations
  $the_standard_deviation = 0.0;
  $the_variance = 0.0;
  $the_mean = 0.0;
  $the_array_sum = array_sum($a); //sum the elements
  $number_elements = count($a); //count the number of elements

  //calculate the mean
  $the_mean = $the_array_sum / $number_elements;

  //calculate the variance
  for ($i = 0; $i < $number_elements; $i++)
  {
    //sum the array
    $the_variance = $the_variance + ($a[$i] - $the_mean) * ($a[$i] - $the_mean);
  }

  $the_variance = $the_variance / $number_elements;

  //calculate the standard deviation
  $the_standard_deviation = pow( $the_variance, 0.5);

  //return the variance
  return $the_standard_deviation;
}
?>
