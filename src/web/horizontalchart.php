<?
 // ------------------------------------------------------------------------------
 // NiDB horizontalchart.php
 // Copyright (C) 2004 - 2020
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

	$w = $_REQUEST['w'];
	$h = $_REQUEST['h'];
	$v = $_REQUEST['v'];
	$c = $_REQUEST['c'];
	$b = $_REQUEST['b'];

	/* create the canvas */
	$im = imagecreatetruecolor($w,$h);
	
	/* set background to white */
	$bg = imagecolorallocate($im, 255, 255, 255);
	imagefilledrectangle($im, 0,0,$w,$h,$bg);
	
	/* get the pixel sizes of the blocks */
	$values = explode(',',$v);
	$colors = explode(',',$c);
	$sum = array_sum($values);

	if ($sum > 0) {
		$x1 = $x2 = 0;
		$y1 = 0;
		$y2 = $h;
		$i = 0;
		foreach ($values as $val) {
			//echo "$colors[$i]<br>";
			list($red,$green,$blue) = rgb2array($colors[$i]);
			//echo "$red, $green, $blue<br>";
			$x1 = $x2;
			$x2 = $x1 + $w*($val/$sum);
			$color = imagecolorallocate($im, $red, $green, $blue);
			imagefilledrectangle($im, $x1,$y1,$x2,$y2,$color);
			$i++;
			//break;
		}
	}
	
	if ($b == "yes") {
		/* draw a gray border */
		$gray = imagecolorallocate($im, 120,120,120);
		imagepolygon($im, array(0,0, 0,$h-1, $w-1,$h-1, $w-1,0), 4, $gray);
	}
	
	/* send the image to the browser */
	header('Content-type: image/png');
	imagepng($im);
	imagedestroy($im);

	/* ---------------------------------------------------------- */
	/* -------- rgb2array --------------------------------------- */
	/* ---------------------------------------------------------- */
	function rgb2array($rgb) {
		/* convert 6 digit HEX string to 3 decimals */
		$rgb = str_replace("#","",$rgb);
		return array(
			base_convert(substr($rgb, 0, 2), 16, 10),
			base_convert(substr($rgb, 2, 2), 16, 10),
			base_convert(substr($rgb, 4, 2), 16, 10),
		);
	}	
?>