<?
 // ------------------------------------------------------------------------------
 // NiDB scatterplot.php
 // Copyright (C) 2004 - 2017
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

	$w = $_REQUEST['w']; /* width */
	$h = $_REQUEST['h']; /* height */
	$x = $_REQUEST['x']; /* list of x values */
	$y = $_REQUEST['y']; /* list of y values */
	$c = $_REQUEST['c']; /* list of colors */
	$b = $_REQUEST['b']; /* if 'yes', draw a gray border around the image */

	/* create the canvas */
	$im = imagecreatetruecolor($w,$h);
	
	/* set background to white */
	$bg = imagecolorallocate($im, 255, 255, 255);
	imagefilledrectangle($im, 0,0,$w,$h,$bg);

	
	/* determine x and y scales based on data */
	$x = explode(",", $x);
	$y = explode(",", $y);
	$c = explode(",", $c);
	$xrange = max($x);
	$yrange = max($y);
	$xscale = $w/$xrange;
	$yscale = $h/$yrange;
	
	//echo "Scales: $xscale, $yscale";
	/* draw the dots */
	for ($i=0; $i<count($x); $i++) {
		$xp = $x[$i]*$xscale;
		$yp = $y[$i]*$yscale;

		$color = imagecolorallocate($im, hexdec(substr($c[$i],0,2)), hexdec(substr($c[$i],2,2)), hexdec(substr($c[$i],2,2)));
		//echo "Plotting $i: ($xp,$yp) $color<br>\n";
		imagefilledellipse($im,$xp,$yp,2,2,$color);
	}
	/*if (($max-$min) > 0 ) {
		$meanx = $w*(($mean-$min)/($max-$min));
		imageline($im,$meanx,0,$meanx,$h,$linecolor);
	}*/
	
	/* setup text color */
	$txtcolor = imagecolorallocate($im,0,0,0);
	$linecolor = imagecolorallocate($im,0,0,0);
	$txtheight = imagefontheight(1);

	/* draw a semi-transparent white box to put text into */
	$color = imagecolorallocatealpha($im, 255,255,255,16);
	imagefilledrectangle($im,0,$h-$txtheight-2,$w,$h,$color);
	
	/* draw min text */
	$str = number_format($min,1);
	imagestring($im,1,1,$h-$txtheight-1,$str,$txtcolor);

	/* draw max text */
	$str = number_format($max,1);
	$txtwidth = imagefontwidth(1)*strlen($str);
	imagestring($im,1,$w-$txtwidth-1,$h-$txtheight-1,$str,$txtcolor);
	
	/* draw mean line and text */
	if (($max-$min) > 0 ) {
		//$meanx = $w*(($mean-$min)/($max-$min));
		//imageline($im,$meanx,0,$meanx,$h,$linecolor);
	
		$str = number_format($mean,1);
		$txtwidth = imagefontwidth(1)*strlen($str);
		imagestring($im,1,$meanx-$txtwidth/2,$h-$txtheight-1,$str,$txtcolor);
	}
	
	if ($ind != "") {
		if (($max-$min) > 0) {
			$indcolor = imagecolorallocate($im,0,0,255);
			
			$indx = $w*(($ind-$min)/($max-$min));
			imageline($im,$indx,0,$indx,$h,$indcolor);
		
			//$str = number_format($ind,1);
			//$txtwidth = imagefontwidth(1)*strlen($str);
			//imagestring($im,1,$indx-$txtwidth/2,$h-$txtheight-1,$str,$txtcolor);
		}
	}

	//if ($b == "yes") {
		/* draw a gray border */
		$gray = imagecolorallocate($im, 120,120,120);
		imagepolygon($im, array(0,0, 0,$h-1, $w-1,$h-1, $w-1,0), 4, $gray);
	//}
	
	/* send the image to the browser */
	header('Content-type: image/png');
	imagepng($im);
	imagedestroy($im);

?>