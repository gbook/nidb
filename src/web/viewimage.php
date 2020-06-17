<?
 // ------------------------------------------------------------------------------
 // NiDB viewimage.php
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

	define("LEGIT_REQUEST", true);
	
	session_start();
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - View Image</title>
	</head>

<body style="height:100%">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$seriesid = GetVariable("seriesid");
	$intype = GetVariable("type");
	$modality = GetVariable("modality");
	$filename = GetVariable("filename");
	
	if ($seriesid != '') {
		/* get the path to the data */
		$sqlstring = "select a.*, b.study_num, d.uid from $modality" . "_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.$modality" . "series_id = $seriesid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$series_num = $row['series_num'];
		$study_num = $row['study_num'];
		$uid = $row['uid'];
		$datatype = $row['data_type'];

		if ($datatype == "") {
			$datatype = "$modality";
		}
		
		$datapath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/$datatype";
		
		/* get list of files */
		$files = array_diff(scandir($datapath), array('..', '.'));
		$filearray = "['$datapath/" . implode("','$datapath/",$files) . "']";
		//PrintVariable($filearray,'filearray');
		if (stristr(strtolower($filename),'.nii') !== FALSE) { $ext = 'nii'; }
		if (stristr(strtolower($filename),'.nii.gz') !== FALSE) { $ext = 'nii.gz'; }
		if (stristr(strtolower($filename),'.dcm') !== FALSE) { $ext = 'dcm'; }
	}
	else {
		$filearray = "['$filename']";
		
		if (stristr(strtolower($filename),'.nii') !== FALSE) { $ext = 'nii'; }
		if (stristr(strtolower($filename),'.nii.gz') !== FALSE) { $ext = 'nii.gz'; }
		if (stristr(strtolower($filename),'.inflated') !== FALSE) { $ext = 'inflated'; }
		if (stristr(strtolower($filename),'.smoothwm') !== FALSE) { $ext = 'smoothwm'; }
		if (stristr(strtolower($filename),'.sphere') !== FALSE) { $ext = 'sphere'; }
		if (stristr(strtolower($filename),'.pial') !== FALSE) { $ext = 'pial'; }
		if (stristr(strtolower($filename),'.fsm') !== FALSE) { $ext = 'fsm'; }
		if (stristr(strtolower($filename),'.orig') !== FALSE) { $ext = 'orig'; }
	}
?>

<script type="text/javascript" src="scripts/xtk.js"></script>
<script type="text/javascript" src="scripts/dat.gui.min.js"></script>


<? if (($intype == "dicom") || ($intype == "nifti")) { ?>
	
	<!-- the container for the renderers -->
	<div style="height:100%">
	<table style="width:100%; height:100%">
		<tr>
			<td colspan="3" style="width:100%; height:400px">
				<div id="3d" style="background-color: #000; width: 100%; height: 100%; margin-bottom: 2px;"></div>
			</td>
		</tr>
		<tr>
			<td style="width:33%; height:350px">
				<div id="sliceX" style="border-top: 5px solid yellow; background-color: #000; width:100%; height:100%"></div>
			</td>
			<td style="width:33%; height:350px">
				<div id="sliceY" style="border-top: 5px solid red;background-color: #000; width:100%; height:100%"></div>
			</td>
			<td style="width:33%; height:350px">
				<div id="sliceZ" style="border-top: 5px solid green; background-color: #000; width:100%; height:100%"></div>
			</td>
		</tr>
	</table>
	</div>
  <? //exit(0);?>
	<script>
		window.onload = function() {

			//exit(0);
			var dicomfiles = <?=$filearray?>;
			//
			// try to create the 3D renderer
			//
			_webGLFriendly = true;
			try {
				// try to create and initialize a 3D renderer
				threeD = new X.renderer3D();
				threeD.container = '3d';
				threeD.init();
			} catch (Exception) {
				// no webgl on this machine
				_webGLFriendly = false;
			}

			//
			// create the 2D renderers
			// .. for the X orientation
			sliceX = new X.renderer2D();
			sliceX.container = 'sliceX';
			sliceX.orientation = 'X';
			sliceX.init();
			// .. for Y
			var sliceY = new X.renderer2D();
			sliceY.container = 'sliceY';
			sliceY.orientation = 'Y';
			sliceY.init();
			// .. and for Z
			var sliceZ = new X.renderer2D();
			sliceZ.container = 'sliceZ';
			sliceZ.orientation = 'Z';
			sliceZ.init();
			
			
			//
			// THE VOLUME DATA
			//
			// create a X.volume
			volume = new X.volume();
			volume.file = dicomfiles.sort().map(function(f) {
				return 'http://<?=$_SERVER['HTTP_HOST']?>/getfile.php?action=download&file=' + f + '&.<?=$ext?>';
			});
			volume.color = [0.0, 0.1, 0.0];
			volume.windowHigh = volume.max/4;
  
			// add the volume in the main renderer
			// we choose the sliceX here, since this should work also on
			// non-webGL-friendly devices like Safari on iOS
			sliceX.add(volume);

			// start the loading/rendering
			sliceX.render();

			//
			// THE GUI
			//
			// the onShowtime method gets executed after all files were fully loaded and
			// just before the first rendering attempt
			sliceX.onShowtime = function() {

				//
				// add the volume to the other 3 renderers
				//
				sliceY.add(volume);
				sliceY.render();
				sliceZ.add(volume);
				sliceZ.render();

				if (_webGLFriendly) {
					threeD.add(volume);
					threeD.render();
				}

				// now the real GUI
				var gui = new dat.GUI();

				// the following configures the gui for interacting with the X.volume
				var volumegui = gui.addFolder('Volume');
				// now we can configure controllers which..
				// .. switch between slicing and volume rendering
				var vrController = volumegui.add(volume, 'volumeRendering');
				// .. configure the volume rendering opacity
				var opacityController = volumegui.add(volume, 'opacity', 0, 1);
				// .. and the threshold in the min..max range
				var lowerThresholdController = volumegui.add(volume, 'lowerThreshold', volume.min, volume.max);
				var upperThresholdController = volumegui.add(volume, 'upperThreshold', volume.min, volume.max);
				var lowerWindowController = volumegui.add(volume, 'windowLow', volume.min, volume.max);
				var upperWindowController = volumegui.add(volume, 'windowHigh', volume.min, volume.max);
				// the indexX,Y,Z are the currently displayed slice indices in the range
				// 0..dimensions-1
				var sliceXController = volumegui.add(volume, 'indexX', 0, volume.dimensions[0] - 1);
				var sliceYController = volumegui.add(volume, 'indexY', 0, volume.dimensions[1] - 1);
				var sliceZController = volumegui.add(volume, 'indexZ', 0, volume.dimensions[2] - 1);
				volumegui.open();
			};
		};
	</script>
	
<? } else { ?>

<div id='r' style='background-color:#000000; float: left; width: 1000px; height: 800px; margin:10px;'></div>
<script>
	window.onload = function() {

		// create and initialize a 3D renderer
		var r = new X.renderer3D();
		r.container = 'r';
		r.init();

		// create the left hemisphere mesh
		var lh = new X.mesh();
		// .. attach a Freesurfer .smoothwm mesh
		lh.file = 'http://<?=$_SERVER['HTTP_HOST']?>/getfile.php?action=download&file=<?=$filename?>&.<?=$ext?>';
		// change the color to a smooth red
		lh.color = [0.7, 0.2, 0.2];
		// add some transparency
		lh.opacity = 1.0;

		// add the objects
		r.add(lh);

		// .. and start the loading and rendering!
		r.camera.position = [0, 0, 200];
		r.render();

		//
		// THE GUI PANEL
		//
		// The user interface is realized using DAT.GUI (tutorial here:
		// http://workshop.chromeexperiments.com/examples/gui/#1--Basic-Usage) which
		// clicks right into XTK.
		//

		/* setup the GUI */
		var gui = new dat.GUI();
		//var gui = new dat.GUI({ autoPlace: false});
		//var guiContainer = document.getElementById('r');
		//guiContainer.appendChild(gui.domElement);

		// left hemisphere
		var lhgui = gui.addFolder('Mesh');
		lhgui.add(lh, 'visible');
		lhgui.add(lh, 'opacity', 0, 1);
		lhgui.addColor(lh, 'color');
		lhgui.open();
	};
</script>
<? } ?>

<? include("footer.php") ?>
