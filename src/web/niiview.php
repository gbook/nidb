<?
 // ------------------------------------------------------------------------------
 // NiDB niiview.php
 // Copyright (C) 2004 - 2023
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
	$seriesid = (int)GetVariable("seriesid");
	$intype = GetVariable("type");
	$modality = GetVariable("modality");
	$filename = GetVariable("filename");
	
	if ($seriesid != 0) {
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


Viewing file <?=htmlspecialchars($filename ?? '')?>
<br>
<div id="niivue-status" style="color: darkred"></div>
<canvas id="gl" width="700" height="700"></canvas>

<!-- NiiVue 0.69.0, hosted locally (the old niivue.github.io UMD URL no longer exists) -->
<script src="scripts/niivue.umd.js"></script>

<script>
	(async function() {
		/* NiiVue picks the file format from the name's extension, so pass the real
		   filename separately from the getfile.php URL (which ends in a query string) */
		var file = {
			url: "getfile.php?action=download&file=" + encodeURIComponent(<?=json_encode($filename ?? '')?>),
			name: <?=json_encode(basename($filename ?? ''))?>
		};
		var isMesh = <?=json_encode($intype == 'mesh')?>;

		try {
			var nv = new niivue.Niivue({isResizeCanvas: false});
			await nv.attachTo("gl");
			if (isMesh) {
				await nv.loadMeshes([file]);
			}
			else {
				file.colormap = "gray";
				await nv.loadVolumes([file]);
				nv.opts.isColorbar = true;
				nv.updateGLVolume();
			}
		}
		catch (e) {
			document.getElementById("niivue-status").textContent = "Unable to load image: " + e;
			console.error(e);
		}
	})();
</script>

<? include("footer.php") ?>
