<?
	session_start();

if ($_POST["image"] == "") { $image = $_GET["image"]; } else { $image = $_POST["image"]; }
if ($_POST["movement"] == "") { $movement = $_GET["movement"]; } else { $movement = $_POST["movement"]; }

if ($image != "") {
	if (file_exists($image)) {
		$pathparts = pathinfo($image);
		$ext = strtolower($pathparts['extension']);
		
		switch ($ext) {
			case "png":
				$im = imagecreatefrompng($image);
				header('Content-type: image/png');
				imagepng($im);
				break;
			case "gif":
				$im = imagecreatefromgif($image);
				header('Content-type: image/gif');
				//imagegif($im);
				echo file_get_contents($image);
				break;
			case "jpg":
				$im = imagecreatefromjpg($image);
				header('Content-type: image/jpg');
				imagejpg($im);
				break;
		}
		
		imagedestroy($im);
	}
}

if ($movement != "") {
	if (file_exists($movement)) {
		$filecontents = file($movement);
		
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
	}
	?>
	<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>Realignment chart for <?=$movement?></title>
		<script type="text/javascript" src="scripts/jquery-1.3.2.min.js"></script>
		<script type="text/javascript" src="scripts/jquery.flot.js"></script>
	</head>
	<body style="font-family: arial, helvetica, sans serif">

	<table>
		<tr>
			<td align="center">
				<h2>Translation</h2>
				<div id="movementgraph" style="width:800px;height:300px;"></div>
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
				<h2>Rotation</h2>
				<div id="rotationgraph" style="width:800px;height:300px;"></div>
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
