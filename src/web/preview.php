<?

	define("LEGIT_REQUEST", true);
	
	session_start();

	require "functions.php";
	require "includes_php.php"; /* login required; also connects to the database for IsViewablePath() */

	$image = GetVariable("image");
	$movement = GetVariable("movement");

	/* both parameters are file paths: only serve files inside the NiDB data directories (same rule as
	   getfile.php?file=), after resolving any ../ or symlinks */
	function PreviewPath($path) {
		$real = realpath((string)$path);
		if (($real === false) || (!is_file($real)) || (!IsViewablePath($real)))
			return false;
		return $real;
	}

	if ($image != "") {
		$real = PreviewPath($image);
		/* serve only real PNG/GIF/JPEG files, with the type detected from the file itself */
		$info = ($real !== false) ? @getimagesize($real) : false;
		if (($info === false) || (!in_array($info[2], array(IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_JPEG), true))) {
			http_response_code(404);
			exit;
		}
		while (ob_get_level() > 0) ob_end_clean();
		header('Content-Type: ' . $info['mime']);
		header('X-Content-Type-Options: nosniff');
		header('Content-Length: ' . filesize($real));
		readfile($real);
		exit;
	}

	if ($movement != "") {
		$real = PreviewPath($movement);
		if ($real === false) {
			http_response_code(404);
			echo "File not found";
			exit;
		}
		$movementname = basename($real);
		$filecontents = file($real);

		$i = 0;
		$fmovex = $fmovey = $fmovez = $fmovepi = $fmovero = $fmoveya = array();
		$movex = $movey = $movez = $movepi = $movero = $moveya = array();
		foreach ($filecontents as $line) {
			/* realignment parameter files have 6 numeric columns: pitch roll yaw x y z. Skip anything else */
			$cols = preg_split('/\s+/', trim($line));
			if ((count($cols) < 6) || (count(array_filter(array_slice($cols, 0, 6), 'is_numeric')) < 6))
				continue;
			list($pi,$ro,$ya,$x,$y,$z) = array_map('floatval', array_slice($cols, 0, 6));

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
			$i++;
		}
		if ($i == 0) {
			echo "No realignment values found in " . htmlspecialchars($movementname);
			exit;
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
	<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>Realignment chart for <?=htmlspecialchars($movementname)?></title>
		<script type="text/javascript" src="scripts/jquery-3.7.1.min.js"></script>
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
