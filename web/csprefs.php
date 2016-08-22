<?
 // ------------------------------------------------------------------------------
 // NiDB csprefs.php
 // Copyright (C) 2004 - 2016
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
	session_start();
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - CenterScripts</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes.php";
	require "nidbapi.php";
	require "menu.php";
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$id = GetVariable("id");
	$defaultinstanceid = GetVariable("defaultinstanceid");
	$instancename = GetVariable("instancename");
	$users = GetVariable("users");
	$userinstanceid = GetVariable("userinstanceid");
	
	//print_r($_POST);
	
	/* determine action */
	switch ($action) {
		case 'editform':
			DisplayPrefsForm("edit", $id);
			break;
		case 'addform':
			DisplayPrefsForm("add", "");
			break;
		case 'update':
			UpdatePrefs($id, $instancename, $users);
			DisplayPrefsList();
			break;
		case 'add':
			AddPrefs($prefsname);
			DisplayPrefsList();
			break;
		case 'delete':
			DeletePrefs($id);
			break;
		default:
			DisplayPrefsList();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- UpdatePrefs ------------------------ */
	/* -------------------------------------------- */
	function UpdatePrefs($id, $prefsname, $users) {
		/* perform data checks */
		$prefsname = mysqli_real_escape_string($instancename);
		
		/* update the instance */
		$sqlstring = "update instance set instance_name = '$instancename' where instance_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		/* add the users to the user_instance table */
		foreach ($users as $userid) {
			$sqlstring = "insert ignore into user_instance (instance_id, user_id) values ($id, $userid)";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		
		?><div align="center"><span class="message"><?=$instancename?> updated</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- AddPrefs --------------------------- */
	/* -------------------------------------------- */
	function AddPrefs($prefsname) {
		/* perform data checks */
		$instancename = mysqli_real_escape_string($instancename);
		
		# create a new instance uid
		do {
			$instanceuid = NIDB\CreateUID('I');
			$sqlstring = "SELECT * FROM `instance` WHERE instance_uid = '$instanceuid'";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$count = mysqli_num_rows($result);
		} while ($count > 0);
		
		$sqlstring = "select user_id from users where username = '" . $GLOBALS['username'] . "'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$ownerid = $row['user_id'];
		
		/* insert the new instance */
		$sqlstring = "insert into instance (instance_uid, instance_name, instance_ownerid) values ('$instanceuid', '$instancename', '$ownerid')";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		?><div align="center"><span class="message"><?=$instancename?> added</span></div><?
	}


	/* -------------------------------------------- */
	/* ------- DeletePrefs ------------------------ */
	/* -------------------------------------------- */
	function DeletePrefs($id) {
		$sqlstring = "delete from instance where instance_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}	

	
	/* -------------------------------------------- */
	/* ------- DisplayPrefsForm ------------------- */
	/* -------------------------------------------- */
	function DisplayPrefsForm($type, $id) {
	
		/* populate the fields if this is an edit */
		if ($type == "edit") {
			$sqlstring = "select * from cs_prefs where csprefs_id = $id";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$instanceid = $row['instance_id'];
			$uid = $row['instance_uid'];
			$name = $row['instance_name'];
		
			$formaction = "update";
			$formtitle = "Updating $instancename";
			$submitbuttonlabel = "Update";
		}
		else {
			$formaction = "add";
			$formtitle = "Create new CS preferences file";
			$submitbuttonlabel = "Create";
		}
		
		$urllist['Analysis'] = "pipelines.php";
		$urllist['CS Prefs'] = "csprefs.php";
		NavigationBar("Analysis", $urllist);
		
		
		/* autocomplete tags */
		$dicom_filepattern_tags = GetDistinctDBField('dicom_filepattern', 'cs_prefs');
		$dicom_format_tags = GetDistinctDBField('dicom_format', 'cs_prefs');
		$dicom_writefileprefix_tags = GetDistinctDBField('dicom_writefileprefix', 'cs_prefs');
		$dicom_outputdir_tags = GetDistinctDBField('dicom_outputdir', 'cs_prefs');
		$reorient_pattern_tags = GetDistinctDBField('reorient_pattern', 'cs_prefs');
		$reorient_vector_tags = GetDistinctDBField('reorient_vector', 'cs_prefs');
		$realign_pattern_tags = GetDistinctDBField('realign_pattern', 'cs_prefs');
		$realign_inri_rho_tags = GetDistinctDBField('realign_inri_rho', 'cs_prefs');
		$realign_inri_cutoff_tags = GetDistinctDBField('realign_inri_cutoff', 'cs_prefs');
		$realign_inri_quality_tags = GetDistinctDBField('realign_inri_quality', 'cs_prefs');
		$realign_fwhm_tags = GetDistinctDBField('realign_fwhm', 'cs_prefs');
		$realign_pathtoweight_tags = GetDistinctDBField('realign_pathtoweight', 'cs_prefs');
		$coreg_ref_tags = GetDistinctDBField('coreg_ref', 'cs_prefs');
		$coreg_source_tags = GetDistinctDBField('coreg_source', 'cs_prefs');
		$coreg_otherpattern_tags = GetDistinctDBField('coreg_otherpattern', 'cs_prefs');
		$coreg_writeref_tags = GetDistinctDBField('coreg_writeref', 'cs_prefs');
		$slicetime_pattern_tags = GetDistinctDBField('slicetime_pattern', 'cs_prefs');
		$slicetime_sliceorder_tags = GetDistinctDBField('slicetime_sliceorder', 'cs_prefs');
		$slicetime_refslice_tags = GetDistinctDBField('slicetime_refslice', 'cs_prefs');
		$slicetime_ta_tags = GetDistinctDBField('slicetime_ta', 'cs_prefs');
		$norm_paramstemplate_tags = GetDistinctDBField('norm_paramstemplate', 'cs_prefs');
		$norm_paramspattern_tags = GetDistinctDBField('norm_paramspattern', 'cs_prefs');
		$norm_paramssourceweight_tags = GetDistinctDBField('norm_paramssourceweight', 'cs_prefs');
		$norm_paramsmatname_tags = GetDistinctDBField('norm_paramsmatname', 'cs_prefs');
		$norm_writepattern_tags = GetDistinctDBField('norm_writepattern', 'cs_prefs');
		$norm_writematname_tags = GetDistinctDBField('norm_writematname', 'cs_prefs');
		$smooth_kernel_tags = GetDistinctDBField('smooth_kernel', 'cs_prefs');
		$smooth_pattern_tags = GetDistinctDBField('smooth_pattern', 'cs_prefs');
		$art_pattern_tags = GetDistinctDBField('art_pattern', 'cs_prefs');
		$filter_pattern_tags = GetDistinctDBField('filter_pattern', 'cs_prefs');
		$filter_cuttofffreq_tags = GetDistinctDBField('filter_cuttofffreq', 'cs_prefs');
		$segment_pattern_tags = GetDistinctDBField('segment_pattern', 'cs_prefs');
	?>
		<script type="text/javascript">
		<!--
			$(document).ready(function() {
				//$('#tableone').tableHover();
				$("#form1").validate();
			});
		-->
		</script>
		<script>
		$(function() {
			/* autocompletes */
			var dicom_filepattern_tags = [<?=$dicom_filepattern_tags?>]; $( "#edit_di_filepattern" ).autocomplete({ source: dicom_filepattern_tags, delay: 0 });
			var dicom_format_tags = [<?=$dicom_format_tags?>]; $( "#edit_di_format" ).autocomplete({ source: dicom_format_tags, delay: 0 });
			var dicom_writefileprefix_tags = [<?=$dicom_writefileprefix_tags?>]; $( "#edit_di_writefileprefix" ).autocomplete({ source: dicom_writefileprefix_tags, delay: 0 });
			var dicom_outputdir_tags = [<?=$dicom_outputdir_tags?>]; $( "#edit_di_outputdir" ).autocomplete({ source: dicom_outputdir_tags, delay: 0 });
			var reorient_pattern_tags = [<?=$reorient_pattern_tags?>]; $( "#edit_ro_pattern" ).autocomplete({ source: reorient_pattern_tags, delay: 0 });
			var reorient_vector_tags = [<?=$reorient_vector_tags?>]; $( "#edit_ro_vector" ).autocomplete({ source: reorient_vector_tags, delay: 0 });
			var realign_pattern_tags = [<?=$realign_pattern_tags?>]; $( "#edit_re_realignpattern" ).autocomplete({ source: realign_pattern_tags, delay: 0 });
			var realign_inri_rho_tags = [<?=$realign_inri_rho_tags?>]; $( "#edit_re_inrialignrho" ).autocomplete({ source: realign_inri_rho_tags, delay: 0 });
			var realign_inri_cutoff_tags = [<?=$realign_inri_cutoff_tags?>]; $( "#edit_re_inrialigncutoff" ).autocomplete({ source: realign_inri_cutoff_tags, delay: 0 });
			var realign_inri_quality_tags = [<?=$realign_inri_quality_tags?>]; $( "#edit_re_inrialignquality" ).autocomplete({ source: realign_inri_quality_tags, delay: 0 });
			var realign_fwhm_tags = [<?=$realign_fwhm_tags?>]; $( "#edit_re_fwhm" ).autocomplete({ source: realign_fwhm_tags, delay: 0 });
			var realign_pathtoweight_tags = [<?=$realign_pathtoweight_tags?>]; $( "#edit_re_pw" ).autocomplete({ source: realign_pathtoweight_tags, delay: 0 });
			var coreg_ref_tags = [<?=$coreg_ref_tags?>]; $( "#edit_co_ref" ).autocomplete({ source: coreg_ref_tags, delay: 0 });
			var coreg_source_tags = [<?=$coreg_source_tags?>]; $( "#edit_co_source" ).autocomplete({ source: coreg_source_tags, delay: 0 });
			var coreg_otherpattern_tags = [<?=$coreg_otherpattern_tags?>]; $( "#edit_co_otherpattern" ).autocomplete({ source: coreg_otherpattern_tags, delay: 0 });
			var coreg_writeref_tags = [<?=$coreg_writeref_tags?>]; $( "#edit_co_writeref" ).autocomplete({ source: coreg_writeref_tags, delay: 0 });
			var slicetime_pattern_tags = [<?=$slicetime_pattern_tags?>]; $( "#edit_st_pattern" ).autocomplete({ source: slicetime_pattern_tags, delay: 0 });
			var slicetime_sliceorder_tags = [<?=$slicetime_sliceorder_tags?>]; $( "#edit_st_sliceorder" ).autocomplete({ source: slicetime_sliceorder_tags, delay: 0 });
			var slicetime_refslice_tags = [<?=$slicetime_refslice_tags?>]; $( "#edit_st_refslice" ).autocomplete({ source: slicetime_refslice_tags, delay: 0 });
			var slicetime_ta_tags = [<?=$slicetime_ta_tags?>]; $( "#edit_st_ta" ).autocomplete({ source: slicetime_ta_tags, delay: 0 });
			var norm_paramstemplate_tags = [<?=$norm_paramstemplate_tags?>]; $( "#edit_no_paramtemplate" ).autocomplete({ source: norm_paramstemplate_tags, delay: 0 });
			var norm_paramspattern_tags = [<?=$norm_paramspattern_tags?>]; $( "#edit_no_parampattern" ).autocomplete({ source: norm_paramspattern_tags, delay: 0 });
			var norm_paramssourceweight_tags = [<?=$norm_paramssourceweight_tags?>]; $( "#edit_no_paramsourceweight" ).autocomplete({ source: norm_paramssourceweight_tags, delay: 0 });
			var norm_paramsmatname_tags = [<?=$norm_paramsmatname_tags?>]; $( "#edit_no_matname" ).autocomplete({ source: norm_paramsmatname_tags, delay: 0 });
			var norm_writepattern_tags = [<?=$norm_writepattern_tags?>]; $( "#edit_no_writenormpattern" ).autocomplete({ source: norm_writepattern_tags, delay: 0 });
			var norm_writematname_tags = [<?=$norm_writematname_tags?>]; $( "#edit_no_writenormmatname" ).autocomplete({ source: norm_writematname_tags, delay: 0 });
			var smooth_kernel_tags = [<?=$smooth_kernel_tags?>]; $( "#edit_sm_kernel" ).autocomplete({ source: smooth_kernel_tags, delay: 0 });
			var smooth_pattern_tags = [<?=$smooth_pattern_tags?>]; $( "#edit_sm_pattern" ).autocomplete({ source: smooth_pattern_tags, delay: 0 });
			var art_pattern_tags = [<?=$art_pattern_tags?>]; $( "#edit_ar_pattern" ).autocomplete({ source: art_pattern_tags, delay: 0 });
			var filter_pattern_tags = [<?=$filter_pattern_tags?>]; $( "#edit_fi_pattern" ).autocomplete({ source: filter_pattern_tags, delay: 0 });
			var filter_cuttofffreq_tags = [<?=$filter_cuttofffreq_tags?>]; $( "#edit_fi_cutofffreq" ).autocomplete({ source: filter_cuttofffreq_tags, delay: 0 });
			var segment_pattern_tags = [<?=$segment_pattern_tags?>]; $( "#edit_se_pattern" ).autocomplete({ source: segment_pattern_tags, delay: 0 });
			
			$('input[name=checkspm]').click(function() {
				//alert('hi');
				if ($('#checkspm2:checked').val() == '2') {
					$('.autocs2').show();
				}
				else {
					$('.autocs5').hide();
					$('.autocs8').hide();
				}
			});
			
		});
		function SwitchSPMVer(v) {
			if (v == 2) {
				$('.autocs2').show();
				$('.autocs58').hide();
				//alert('SPM2');
			}
			if (v == 5) {
				$('.autocs2').hide();
				$('.autocs58').show();
				//alert('SPM5');
			}
			if (v == 8) {
				$('.autocs2').hide();
				$('.autocs58').show();
				//alert('SPM8');
			}
		}
		</script>
		<style>
			input { margin-left: 5pt; }
			input.error { border: 1px solid red; }
			label.error {
				background: url('images/unchecked.gif') no-repeat;
				padding-left: 16px;
				margin-left: .3em;
			}
			label.valid {
				background: url('images/checked.gif') no-repeat;
				display: block;
				width: 16px;
				height: 16px;
			}
		</style>

		<img src="images/back16.png"> <a href="autocs_preprocprefs.php?taskid=<? echo $taskid; ?>" class="link">Back</a> to list of preprocessing prefs<br><br>
		<form action="autocs_preprocprefs.php" method="get" id="form1">
		<input type="hidden" name="action" value="add">
		<input type="hidden" name="taskid" value="<? echo $taskid; ?>">

		<b>SPM version:</b><br>
		<input type="radio" name="checkspm" id="checkspm2" onClick="SwitchSPMVer(2)" value="2">SPM2<br>
		<input type="radio" name="checkspm" id="checkspm5" onClick="SwitchSPMVer(5)" value="5">SPM5<br>
		<input type="radio" name="checkspm" id="checkspm8" onClick="SwitchSPMVer(8)" value="8">SPM8

		<table cellspacing="0" cellpadding="4" id="tableone" width="100%" class="editor">
			<tr>
				<td colspan="2" style="border-bottom: 3px solid #444; text-align: center; font-weight: bold">Add New Pre-processing Preferences File for <span style="color: darkblue"><? echo $taskname; ?></span> &nbsp; <span class="spm<?=$autocsver?>">spm<?=$autocsver?></span></td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
		
			<tr>
				<td class="label"><br>Prefs Description</td>
				<td class="value"><br><input type="text" name="edit_description" class="csprefsinput required" size="70"></td>
			</tr>
			<tr>
				<td class="label">Short name<br><span class="sublabel">letters and numbers only, no spaces</span></td>
				<td class="value"><input type="text" name="edit_shortname" class="csprefsinput required"></td>
			</tr>
			<tr>
				<td class="label">Extra lines to include<br><span class="sublabel">path changes, etc</span></td>
				<td class="value" valign="top">
				<textarea name="edit_extralines" class="csprefsinput" cols="70" rows="5"></textarea>
				</td>
			</tr>
			<tr>
				<td class="label" style="background-color: white">Steps to perform</td>
				<td colspan="2" class="value" style="background-color: white">
					<span class="autocs58"><input type="checkbox" name="edit_do_dicomconvert" value="yes" class="csprefsinput">DICOM Convert<br></span>
					<span class="autocs58"><input type="checkbox" name="edit_do_reorient" value="yes" class="csprefsinput">Reorient<br></span>
					<input type="checkbox" name="edit_do_realign" value="yes" class="csprefsinput" checked>Realign<br>
					<span class="autocs58"><input type="checkbox" name="edit_do_msdcalc" value="yes" class="csprefsinput">MSD Calculation <span class="sublabel">"Realignment->Write resliced images" MUST be set to 2 if this option is checked</span><br></span>
					<span class="autocs58"><input type="checkbox" name="edit_do_coregister" value="yes" class="csprefsinput">Coregister<br></span>
					<span class="autocs58"><input type="checkbox" name="edit_do_slicetime" value="yes" class="csprefsinput" checked>Slicetime correction<br></span>
					<input type="checkbox" name="edit_do_normalize" value="yes" class="csprefsinput" checked>Normalize<br>
					<input type="checkbox" name="edit_do_smooth" value="yes" class="csprefsinput" checked>Smooth<br>
					<span class="autocs58"><input type="checkbox" name="edit_do_artrepair" value="yes" class="csprefsinput">Art Repair<br></span>
					<input type="checkbox" name="edit_do_filter" value="yes" class="csprefsinput" checked>Filter<br>
					<span class="autocs58"><input type="checkbox" name="edit_do_segment" value="yes" class="csprefsinput">Segment<br></span>
					<input type="checkbox" name="edit_do_behmatchup" value="yes" class="csprefsinput" checked>Behavioral Matchup<br>
					<input type="checkbox" name="edit_do_stats" value="yes" class="csprefsinput" checked>Stats<br>
					<input type="checkbox" name="edit_do_censor" value="yes" class="csprefsinput" checked>Time censoring <span class="sublabel">Using this option will disable regressors</span><br>
					<input type="checkbox" name="edit_do_autoslice" value="yes" class="csprefsinput" checked>Autoslice<br>
					<input type="checkbox" name="edit_do_db" value="yes" class="csprefsinput" checked>Derivative Boost<br>
				</td>
			</tr>
			<tr><td colspan="2" style="font-size: 8pt">&nbsp;</td></tr>

			<div class="autocs58">
			<tr>
				<td colspan="2" height="30px" style="border-top: #999999 1pt solid; color: darkblue"><b>DICOM Conversion</b> <span class="spm5">spm5</span> <span class="spm8">spm8</span>
				<br><span class="sublabel">Outputs *.nii files</span>
				</td>
			</tr>
			<tr>
				<td class="label">File Pattern</td>
				<td class="value"><span class="ui-widget"><input type="text" id="edit_di_filepattern" name="edit_di_filepattern" value="Ser*.dcm" checked class="csprefsinput"></span> <tt>csprefs.dicom.file_pattern</tt> <img src="images/help.gif" onMouseOver="Tip('File pattern for dicom files', TITLE, 'csprefs.dicom.file_pattern', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Format</td>
				<td class="value"><span class="ui-widget"><input type="text" id="edit_di_format" name="edit_di_format" value="3d_analyze" checked class="csprefsinput"></span> <tt>csprefs.dicom.format</tt> <img src="images/help.gif" onMouseOver="Tip('Dicom files can be converted to 3D analyze, 3D Nifti or 4D Nifti files depending upon the format. Options are \'3d_analyze\', \'3d_nifti\' or \'4d_nifti\'', TITLE, 'csprefs.dicom.format', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Write file prefix</td>
				<td class="value"><span class="ui-widget"><input type="text" id="edit_di_writefileprefix" name="edit_di_writefileprefix" value="task" checked class="csprefsinput"></span> <tt>csprefs.dicom.write_file_prefix</tt> <img src="images/help.gif" onMouseOver="Tip('File prefix for naming the analyze or Nifti files that are written.', TITLE, 'csprefs.dicom.write_file_prefix', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Output directory</td>
				<td class="value"><span class="ui-widget"><input type="text" id="edit_di_outputdir" name="edit_di_outputdir" value="task" checked class="csprefsinput"></span> <tt>csprefs.dicom.outputDir</tt> <img src="images/help.gif" onMouseOver="Tip('Files converted from DICOM will be placed in this directory. Leave blank if you want the files to be placed in the run directory', TITLE, 'csprefs.dicom.outputDir', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr><td colspan="2" style="font-size: 8pt">&nbsp;</td></tr>

			<tr>
				<td colspan="2" height="30px" style="border-top: #999999 1pt solid; color: darkblue"><b>Reorientation</b> <span class="spm5">spm5</span> <span class="spm8">spm8</span>
				<br><span class="sublabel">Outputs Re*.nii files</span>
				</td>
			</tr>
			<tr>
				<td class="label">File pattern</td>
				<td class="value"><span class="ui-widget"><input type="text" id="edit_ro_pattern" name="edit_ro_pattern" value="<? if (($autocsver == 5) || ($autocsver == 8)) { echo "*.nii"; } else { echo "*.img";} ?>" class="csprefsinput"></span> <tt>csprefs.reorient_pattern</tt> <img src="images/help.gif" onMouseOver="Tip('Specify pattern for images to be re-oriented', TITLE, 'csprefs.reorient_pattern', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Reorient vector<br><span class="sublabel">Must contain 12 elements</span></td>
				<td class="value">[<span class="ui-widget"><input type="text" id="edit_ro_vector" name="edit_ro_vector" size="40" value="0, 0, 0, 0, 0, 0, 1, 1, 1, 0, 0, 0" checked class="csprefsinput"></span>] <tt>csprefs.reorient_vector</tt> <img src="images/help.gif" onMouseOver="Tip('Affine transformation matrix will be obtained based on this vector.<br><br>csprefs.reorient_vector(1)  - x translation<br>csprefs.reorient_vector(2)  - y translation<br>csprefs.reorient_vector(3)  - z translation<br>csprefs.reorient_vector(4)  - x rotation about - {pitch} (radians)<br>csprefs.reorient_vector(5)  - y rotation about - {roll}  (radians)<br>csprefs.reorient_vector(6)  - z rotation about - {yaw}   (radians)<br>csprefs.reorient_vector(7)  - x scaling<br>csprefs.reorient_vector(8)  - y scaling<br>csprefs.reorient_vector(9)  - z scaling<br>csprefs.reorient_vector(10) - x affine<br>csprefs.reorient_vector(11) - y affine<br>csprefs.reorient_vector(12) - z affine', TITLE, 'csprefs.reorient_vector', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Write reoriented files?</td>
				<td class="value"><input type="checkbox" name="edit_ro_write" value="yes"> <tt>csprefs.write_reorient</tt> <img src="images/help.gif" onMouseOver="Tip('Write reoriented images. If unchecked, it modifies the headers of the images whereas if its checked it will write new set of images with prefix Re_', TITLE, 'csprefs.write_reorient', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr><td colspan="2" style="font-size: 8pt">&nbsp;</td></tr>
			</div>

			<tr>
				<td colspan="2" height="30px" style="border-top: #999999 1pt solid; color: darkblue"><b>Realignment</b> <span class="spm2">spm2</span> <span class="spm5">spm5</span> <span class="spm8">spm8</span>
				<br><span class="sublabel">Outputs r*.nii files only if resliced images are written</span>
				</td>
			</tr>
			<tr>
				<td class="label">Coregister?</td>
				<td class="value"><input type="checkbox" name="edit_re_coregister" value="yes" checked class="csprefsinput"> <tt>csprefs.coregister</tt> <img src="images/help.gif" onMouseOver="Tip('whether to coregister (i.e., run inria_realign or spm_realign)', TITLE, 'csprefs.coregister', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Reslice?</td>
				<td class="value"><input type="checkbox" name="edit_re_reslice" value="yes" checked class="csprefsinput"> <tt>csprefs.reslice</tt> <img src="images/help.gif" onMouseOver="Tip('whether to reslice (i.e., run spm_reslice). Together, this and csprefs.coregister take the place of the Coregister and Reslice? type dialog box in the GUI', TITLE, 'csprefs.reslice', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Use INRIAlign?</td>
				<td class="value"><input type="checkbox" name="edit_re_useinrialign" value="yes" checked class="csprefsinput"> <tt>csprefs.use_inrialign</tt> <img src="images/help.gif" onMouseOver="Tip('whether to use INRIAlign. (if not checked, use spm_realign instead)', TITLE, 'csprefs.use_inrialign', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">File pattern to realign</td>
				<td class="value">
					<span class="ui-widget"><input type="text" id="edit_re_realignpattern" name="edit_re_realignpattern" value="<? if (($autocsver == 5) || ($autocsver == 8)) { echo "*.nii"; } else { echo "*.img";} ?>" class="csprefsinput required"></span> 
					<tt>csprefs.realign_pattern</tt> <img src="images/help.gif" onMouseOver="Tip('specifies a pattern identifying which image files should be realigned. Literals and wildcards (*) only', TITLE, 'csprefs.realign_pattern', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()">
				</td>
			</tr>
			<tr>
				<td class="label">INRIAlign rho</td>
				<td class="value">
					<span class="ui-widget"><input type="text" id="edit_re_inrialignrho" name="edit_re_inrialignrho" value="geman" class="csprefsinput required"></span> 
					<tt>csprefs.inrialign_rho</tt> <img src="images/help.gif" onMouseOver="Tip('rho function for INRIAlign. Ignore if not using INRIAlign. Default is geman; see inria_realign.m for further explanation and other choices', TITLE, 'csprefs.inrialign_rho', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">INRIAlign cutoff</td>
				<td class="value"><span class="ui-widget"><input type="text" id="edit_re_inrialigncutoff" name="edit_re_inrialigncutoff" value="2.5" class="csprefsinput required number"></span> <tt>csprefs.inrialign_cutoff</tt> <img src="images/help.gif" onMouseOver="Tip('cut-off distance for INRIAlign. Ignore if not using INRIAlign. Default is 2.5; see inria_realign.m for details', TITLE, 'csprefs.inrialign_cutoff', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">INRIAlign quality</td>
				<td class="value"><span class="ui-widget"><input type="text" id="edit_re_inrialignquality" name="edit_re_inrialignquality" value="1.0" class="csprefsinput required number"></span> <tt>csprefs.inrialign_quality</tt> <img src="images/help.gif" onMouseOver="Tip('quality value for INRIAlign. Value from 0 (fastest, low quality) to 1 (slowest, high quality). The equivalent value for spm_realign is defined in spm_defaults.m', TITLE, 'csprefs.inrialign_quality', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">FWHM</td>
				<td class="value"><span class="ui-widget"><input type="text" id="edit_re_fwhm" name="edit_re_fwhm" value="8" class="csprefsinput required digits"></span> <tt>csprefs.realign_fwhm</tt> <img src="images/help.gif" onMouseOver="Tip('size of smoothing kernel (mm) applied during realignment. Applies to both INRIAlign and spm_realign.', TITLE, 'csprefs.realign_fwhm', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Realign to mean image</td>
				<td class="value"><input type="checkbox" id="edit_re_rtm" name="edit_re_rtm" value="yes" class="csprefsinput"> <tt>csprefs.realign_rtm</tt> <img src="images/help.gif" onMouseOver="Tip('whether to realign all images to the mean image. Applies to both INRIAlign and spm_realign. NOTE: APPARENTLY DOES NOT WORK FOR INRIALIGN', TITLE, 'csprefs.realign_rtm', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Weighted image path</td>
				<td class="value"><span class="ui-widget"><input type="text" id="edit_re_pw" name="edit_re_pw" class="csprefsinput"></span> <tt>csprefs.realign_pw</tt> <img src="images/help.gif" onMouseOver="Tip('pathname to a weighting image for realignment. Leave blank if you don\'t want to weight (...for our lives to be over...). Might need some recoding to actually use this option... we\'re just going to assume it\'s blank for now', TITLE, 'csprefs.realign_pw', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Write resliced images</td>
				<td class="value">
					<select name="edit_re_writeimages" class="csprefsinput">
						<option value="0" selected>0 - Write no images
						<option value="1">1 - Write all but first image
						<option value="2">2 - Write all images
					</select> <tt>csprefs.reslice_write_imgs</tt> <img src="images/help.gif" onMouseOver="Tip('which resliced images to write. 0 = don\'t write any, 1 = write all but first image, 2 = write all', TITLE, 'csprefs.reslice_write_imgs', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()">
				</td>
			</tr>
			<tr>
				<td class="label">Write mean image</td>
				<td class="value"><input type="checkbox" name="edit_re_writemean" value="yes" class="csprefsinput" checked> <tt>csprefs.reslice_write_mean</tt> <img src="images/help.gif" onMouseOver="Tip('whether to write a mean image', TITLE, 'csprefs.reslice_write_mean', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr><td colspan="2" style="font-size: 8pt">&nbsp;</td></tr>


			<tr>
				<td colspan="2" height="30px" style="border-top: #999999 1pt solid; color: darkblue"><B>Coregister</B> <span class="spm5">spm5</span> <span class="spm8">spm8</span>
				</td>
			</tr>
			<tr>
				<td class="label">Run coregister step</td>
				<td class="value"><input type="checkbox" name="edit_co_run" value="yes" class="csprefsinput" checked> <tt>csprefs.run_coreg</tt> <img src="images/help.gif" onMouseOver="Tip('Runs coregister step', TITLE, 'csprefs.run_coreg', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Run reslice step</td>
				<td class="value"><input type="checkbox" name="edit_co_runreslice" value="yes" class="csprefsinput" checked> <tt>csprefs.run_reslice</tt> <img src="images/help.gif" onMouseOver="Tip('Runs reslice step', TITLE, 'csprefs.run_reslice', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Reference image</td>
				<td class="value"><span class="ui-widget"><input type="text" id="edit_co_ref" name="edit_co_ref" value="/opt/spm<?=$autocsver?>/templates/EPI.nii" class="csprefsinput" size="40"></span> <tt>csprefs.coreg.ref</tt> <img src="images/help.gif" onMouseOver="Tip('Reference image used for coregister step', TITLE, 'csprefs.coreg.ref', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Source image</td>
				<td class="value"><span class="ui-widget"><input type="text" id="edit_co_source" name="edit_co_source" value="" class="csprefsinput" size="40"></span> <tt>csprefs.coreg.source</tt> <img src="images/help.gif" onMouseOver="Tip('Source image used for coregister step', TITLE, 'csprefs.coreg.source', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Other pattern</td>
				<td class="value"><span class="ui-widget"><input type="text" id="edit_co_otherpattern" name="edit_co_otherpattern" value="" class="csprefsinput" size="40"></span> <tt>csprefs.coreg.other_pattern</tt> <img src="images/help.gif" onMouseOver="Tip('File pattern for other images used', TITLE, 'csprefs.coreg.other_pattern', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Reference image for reslicing</td>
				<td class="value"><span class="ui-widget"><input type="text" id="edit_co_writeref" name="edit_co_writeref" value="" class="csprefsinput" size="40"></span> <tt>csprefs.coreg.write.ref</tt> <img src="images/help.gif" onMouseOver="Tip('Reference image used for reslicing. Specify the reference file if you have checked off csprefs.run_reslice. Source image and other images will be resliced using the reference image. After reslicing the new set of images have prefix r.', TITLE, 'csprefs.coreg.source', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr><td colspan="2" style="font-size: 8pt">&nbsp;</td></tr>


			<tr>
				<td colspan="2" height="30px" style="border-top: #999999 1pt solid; color: darkblue"><B>Slicetime correction</B> <span class="spm2">spm2</span> <span class="spm5">spm5</span> <span class="spm8">spm8</span>
				<br><span class="sublabel">Outputs a*.nii files</span>
				</td>
			</tr>
			<tr>
				<td class="label">Slicetime file pattern</td>
				<td class="value"><span class="ui-widget"><input type="text" id="edit_st_pattern" name="edit_st_pattern" value="<? if (($autocsver == 5) || ($autocsver == 8)) { echo "*.nii"; } else { echo "*.img";} ?>" class="csprefsinput" size="40"></span> <tt>csprefs.slicetime_pattern</tt> <img src="images/help.gif" onMouseOver="Tip('specifies a pattern identifying which image files should be slicetimed. Literals and wildcards (*) only', TITLE, 'csprefs.sliceorder', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Slice order</td>
				<td class="value">[<span class="ui-widget"><input type="text" id="edit_st_sliceorder" name="edit_st_sliceorder" value="1:1:29" class="csprefsinput" size="40"></span>] <tt>csprefs.slicetime_sliceorder</tt> <img src="images/help.gif" onMouseOver="Tip('Matlab matrix specifying order of slices acquired (just like you input it in the SPM GUI). Just remember to enclose the matrix in square brackets, e.g. [ 1 3 5 7 9 2 4 6 8 10]', TITLE, 'csprefs.sliceorder', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Reference slice</td>
				<td class="value"><span class="ui-widget"><input type="text" id="edit_st_refslice" name="edit_st_refslice" value="15" class="csprefsinput" size="4"></span> <tt>csprefs.slicetime_refslice</tt> <img src="images/help.gif" onMouseOver="Tip('slice # to use as the \'reference slice\'; same as you input it in the SPM GUI', TITLE, 'csprefs.refslice', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Time of acquisition</td>
				<td class="value"><span class="ui-widget"><input type="text" id="edit_st_ta" name="edit_st_ta" value="default" class="csprefsinput"></span> <tt>csprefs.ta</tt> <img src="images/help.gif" onMouseOver="Tip('time of acquisition (TA). If you have a specific value in mind for this (like 1.9 or something), you can use that; if, like most people, you just accept the default value in the GUI, you can specify the text string \'default\' to use the auto-calculated value (which is the time of one TR minus the time of one slice)', TITLE, 'csprefs.ta', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr><td colspan="2" style="font-size: 8pt">&nbsp;</td></tr>


			<tr>
				<td colspan="2" height="30px" style="border-top: #999999 1pt solid; color: darkblue"><B>Normalization</B> <span class="spm2">spm2</span> <span class="spm5">spm5</span> <span class="spm8">spm8</span>
				<br><span class="sublabel">Outputs w*.nii files</span>
				</td>
			</tr>
			<tr>
				<td class="label">Determine parameters</td>
				<td class="value"><input type="checkbox" name="edit_no_determineparams" value="yes" class="csprefsinput" checked> <tt>csprefs.determine_params</tt> <img src="images/help.gif" onMouseOver="Tip('whether to determine paramters (first step of normalization)', TITLE, 'csprefs.determine_params', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Write normalized</td>
				<td class="value"><input type="checkbox" name="edit_no_writenormalized" value="yes" class="csprefsinput" checked> <tt>csprefs.write_normalized</tt> <img src="images/help.gif" onMouseOver="Tip('whether to write normalized images (second step of normalization)', TITLE, 'csprefs.write_normalized', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Parameters template</td>
				<td class="value"><span class="ui-widget"><input type="text" id="edit_no_paramtemplate" name="edit_no_paramtemplate" size="40" value="<? if (($autocsver == 5) || ($autocsver == 8)) { echo "/opt/spm$autocsver/templates/EPI.nii"; } else { echo "/opt/spm2/templates/EPI.mnc";} ?>" class="csprefsinput required"></span> <tt>csprefs.params_template</tt> <img src="images/help.gif" onMouseOver="Tip('image to use as template for paramter estimation. For fMRI, usually \'EPI.mnc\' somewhere. Although spm_normalize allows multiple templates, this option is not implemented in cs_normalize', TITLE, 'csprefs.params_template', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Parameters file pattern</td>
				<td class="value"><span class="ui-widget"><input type="text" id="edit_no_parampattern" name="edit_no_parampattern" value="<? if (($autocsver == 5) || ($autocsver == 8)) { echo "mean*nii"; } else { echo "mean*img";} ?>" class="csprefsinput required"></span> <tt>csprefs.params_pattern</tt> <img src="images/help.gif" onMouseOver="Tip('name of image, or pattern identifying an image, to use for paramter estimation. Usually this is the mean image created during realignment. If a pattern (using wildcards) is used, the pattern should only match one image in each directory, or else an error will occur. This image needs to be in the directory passed to cs_normalize', TITLE, 'csprefs.params_pattern', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Parameters source weight</td>
				<td class="value"><span class="ui-widget"><input type="text" id="edit_no_paramsourceweight" name="edit_no_paramsourceweight" class="csprefsinput"></span> <tt>csprefs.params_source_weight</tt> <img src="images/help.gif" onMouseOver="Tip('name of image, or pattern identifying an image, to weight the source image during paramter estimation. Only need to specify this if spm_defaults has \'defaults.normalise.estimate.wtsrc\' set to 1; otherwise, leave this blank. If the pattern matches more than one image, an error will occur. This image needs to be in the directory passed to cs_normalize', TITLE, 'csprefs.params_source_weight', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Parameters Matlab filename</td>
				<td class="value"><span class="ui-widget"><input type="text" id="edit_no_matname" name="edit_no_matname" class="csprefsinput"></span> <tt>csprefs.params_matname</tt> <img src="images/help.gif" onMouseOver="Tip('optional name for Matlab file (e.g., \'*.mat\' format) in which to store the transformations that result from spatial normalization. If nothing specified (in other words, if you have a blank string here), then the default is the name of your input image file with \'_sn.mat\' appended (e.g. \'mymean_sn.mat\')', TITLE, 'csprefs.params_matname', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Write normalization pattern</td>
				<td class="value"><span class="ui-widget"><input type="text" id="edit_no_writenormpattern" name="edit_no_writenormpattern" value="<? if (($autocsver == 5) || ($autocsver == 8)) { echo "a*.nii"; } else { echo "*img";} ?>" class="csprefsinput required"></span> <tt>csprefs.writenorm_pattern</tt> <img src="images/help.gif" onMouseOver="Tip('specifies a pattern identifying which image files should be normalized. Literals and wildcards (*) only', TITLE, 'csprefs.writenorm_pattern', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Write normalization<br>matlab filename</td>
				<td class="value"><span class="ui-widget"><input type="text" id="edit_no_writenormmatname" name="edit_no_writenormmatname" class="csprefsinput"></span> <tt>csprefs.writenorm_matname</tt> <img src="images/help.gif" onMouseOver="Tip('name of Matlab file, or pattern identifying a Matlab file, containing paramters to apply to images. Only need to specify this if you have csprefs.determine_params set to 0; otherwise, leave this blank. If the pattern matches more than one file, an error will occur. This file needs to be in the directory passed to cs_normalize', TITLE, 'csprefs.writenorm_matname', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr><td colspan="2" style="font-size: 8pt">&nbsp;</td></tr>

			<tr>
				<td colspan="2" height="30px" style="border-top: #999999 1pt solid; color: darkblue"><B>Smoothing</B> <span class="spm2">spm2</span> <span class="spm5">spm5</span> <span class="spm8">spm8</span>
				<br><span class="sublabel">Outputs s*.nii files</span>
				</td>
			</tr>

			<tr>
				<td class="label">Smoothing kernel<br><span class="sublabel">Must contain 3 elements</span></td>
				<td class="value"><span class="ui-widget"><input type="text" id="edit_sm_kernel" name="edit_sm_kernel" value="8 8 8" class="csprefsinput required"></span> <tt>csprefs.smooth_kernel</tt> <img src="images/help.gif" onMouseOver="Tip('size of Gaussian smoothing kernel, in mm', TITLE, 'csprefs.smooth_kernel', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Smoothing pattern</td>
				<td class="value"><span class="ui-widget"><input type="text" id="edit_sm_pattern" name="edit_sm_pattern" value="<? if (($autocsver == 5) || ($autocsver == 8)) { echo "wa*.nii"; } else { echo "w*.img";} ?>" class="csprefsinput required"></span> <tt>csprefs.smooth_pattern</tt> <img src="images/help.gif" onMouseOver="Tip('specifies a pattern identifying which image files should be smoothed. Literals and wildcards (*) only', TITLE, 'csprefs.smooth_pattern', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr><td colspan="2" style="font-size: 8pt">&nbsp;</td></tr>

			<tr>
				<td colspan="2" height="30px" style="border-top: #999999 1pt solid; color: darkblue"><B>Art Repair</B> <span class="spm8">spm8</span></td>
			</tr>

			<tr>
				<td class="label">Art repair pattern</td>
				<td class="value"><span class="ui-widget"><input type="text" id="edit_ar_pattern" name="edit_ar_pattern" value="swar*.nii" class="csprefsinput required"></span> <tt>csprefs.art_pattern</tt> <img src="images/help.gif" onMouseOver="Tip('specifies a pattern identifying which image files Art repair should use. Literals and wildcards (*) only', TITLE, 'csprefs.smooth_pattern', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr><td colspan="2" style="font-size: 8pt">&nbsp;</td></tr>
			
			<tr>
				<td colspan="2" height="30px" style="border-top: #999999 1pt solid; color: darkblue"><B>Filtering</B> <span class="spm2">spm2</span> <span class="spm5">spm5</span> <span class="spm8">spm8</span>
				<br><span class="sublabel">Outputs f*.nii files</span>
				</td>
			</tr>

			<tr>
				<td class="label">Filter pattern</td>
				<td class="value"><span class="ui-widget"><input type="text" id="edit_fi_pattern" name="edit_fi_pattern" value="<? if (($autocsver == 5) || ($autocsver == 8)) { echo "swa*.nii"; } else { echo "s*.img";} ?>" class="csprefsinput required"></span> <tt>csprefs.filter_pattern</tt> <img src="images/help.gif" onMouseOver="Tip('pattern for images to filter. Wildcards (*) and literals only. If demand warrants, we can do this with regexp instead, but I doubt it\'s necessary', TITLE, 'csprefs.filter_pattern', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Cutoff Frequency</td>
				<td class="value"><span class="ui-widget"><input type="text" id="edit_fi_cutofffreq" name="edit_fi_cutofffreq" value="0.25" class="csprefsinput required"></span> <tt>csprefs.cutoff_freq</tt> <img src="images/help.gif" onMouseOver="Tip('to be honest, I dont really know what this is', TITLE, 'csprefs.cutoff_freq', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr><td colspan="2" style="font-size: 8pt">&nbsp;</td></tr>

		
			<tr>
				<td colspan="2" height="30px" style="border-top: #999999 1pt solid; color: darkblue"><B>Segmentation</B> <span class="spm5">spm5</span> <span class="spm8">spm8</span></td>
			</tr>

			<tr>
				<td class="label">Segment file pattern</td>
				<td class="value"><span class="ui-widget"><input type="text" id="edit_se_pattern" name="edit_se_pattern" value="w*nii" class="csprefsinput required"></span> <tt>csprefs.segment.pattern</tt> <img src="images/help.gif" onMouseOver="Tip('pattern for images to segment', TITLE, 'csprefs.segment.pattern', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Gray matter output<br><span class="sublabel">Must contain 3 elements</span></td>
				<td class="value">
					[<select name="edit_se_gmoutput" class="csprefsinput">
						<option value="0,0,0">0 0 0 - None
						<option value="0,0,1">0 0 1 - Native Space
						<option value="0,1,0">0 1 0 - Unmodulated Normalised
						<option value="1,0,0">1 0 0 - Modulated Normalised
						<option value="0,1,1">0 1 1 - Native + Unmodulated Normalised
						<option value="1,0,1">1 0 1 - Native + Modulated Normalised
						<option value="1,1,1" selected>1 1 1 - Native + Modulated + Unmodulated
						<option value="1,1,0">1 1 0 - Modulated + Unmodulated Normalised
					</select>]
					<tt>csprefs.segment.output.GM</tt> 
					<img src="images/help.gif" onMouseOver="Tip('Options are as follows:<br><br> [0 0 0] means \'None\'<br> [0 0 1] means \'Native Space\'<br> [0 1 0] means \'Unmodulated Normalised\'<br> [1 0 0] means \'Modulated Normalised\'<br> [0 1 1] means \'Native + Unmodulated Normalised\'<br> [1 0 1] means \'Native + Modulated Normalised\'<br> [1 1 1] means \'Native + Modulated + Unmodulated\'<br>[1 1 0] means \'Modulated + Unmodulated Normalised\' ', TITLE, 'csprefs.segment.output.GM', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">White matter output<br><span class="sublabel">Must contain 3 elements</span></td>
				<td class="value">
					[<select name="edit_se_wmoutput" class="csprefsinput">
						<option value="0,0,0">0 0 0 - None
						<option value="0,0,1" selected>0 0 1 - Native Space
						<option value="0,1,0">0 1 0 - Unmodulated Normalised
						<option value="1,0,0">1 0 0 - Modulated Normalised
						<option value="0,1,1">0 1 1 - Native + Unmodulated Normalised
						<option value="1,0,1">1 0 1 - Native + Modulated Normalised
						<option value="1,1,1">1 1 1 - Native + Modulated + Unmodulated
						<option value="1,1,0">1 1 0 - Modulated + Unmodulated Normalised
					</select>]
					<tt>csprefs.segment.output.WM</tt> <img src="images/help.gif" onMouseOver="Tip('Options are as follows:<br><br> [0 0 0] means \'None\'<br> [0 0 1] means \'Native Space\'<br> [0 1 0] means \'Unmodulated Normalised\'<br> [1 0 0] means \'Modulated Normalised\'<br> [0 1 1] means \'Native + Unmodulated Normalised\'<br> [1 0 1] means \'Native + Modulated Normalised\'<br> [1 1 1] means \'Native + Modulated + Unmodulated\'<br>[1 1 0] means \'Modulated + Unmodulated Normalised\' ', TITLE, 'csprefs.segment.output.WM', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">CSF output<br><span class="sublabel">Must contain 3 elements</span></td>
				<td class="value">
					[<select name="edit_se_csfoutput" class="csprefsinput">
						<option value="0,0,0" selected>0 0 0 - None
						<option value="0,0,1">0 0 1 - Native Space
						<option value="0,1,0">0 1 0 - Unmodulated Normalised
						<option value="1,0,0">1 0 0 - Modulated Normalised
						<option value="0,1,1">0 1 1 - Native + Unmodulated Normalised
						<option value="1,0,1">1 0 1 - Native + Modulated Normalised
						<option value="1,1,1">1 1 1 - Native + Modulated + Unmodulated
						<option value="1,1,0">1 1 0 - Modulated + Unmodulated Normalised
					</select>]
				<tt>csprefs.segment.output.CSF</tt> <img src="images/help.gif" onMouseOver="Tip('Options are as follows:<br><br> [0 0 0] means \'None\'<br> [0 0 1] means \'Native Space\'<br> [0 1 0] means \'Unmodulated Normalised\'<br> [1 0 0] means \'Modulated Normalised\'<br> [0 1 1] means \'Native + Unmodulated Normalised\'<br> [1 0 1] means \'Native + Modulated Normalised\'<br> [1 1 1] means \'Native + Modulated + Unmodulated\'<br>[1 1 0] means \'Modulated + Unmodulated Normalised\' ', TITLE, 'csprefs.segment.output.CSF', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Bias correction</td>
				<td class="value">
					<select name="edit_se_biascor" class="csprefsinput">
						<option value="0">0 - Save bias corrected
						<option value="1" selected>1 - Don't save bias corrected
					</select>
					<tt>csprefs.segment.output.biascor</tt> <img src="images/help.gif" onMouseOver="Tip('Save bias correction or not', TITLE, 'csprefs.segment.output.biascor', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Output cleanup</td>
				<td class="value">
					<select name="edit_se_cleanup" class="csprefsinput">
						<option value="0" selected>0 - Don't do cleanup
						<option value="1">1 - Light clean
						<option value="2">2 - Thorough clean
					</select>
					<tt>csprefs.segment.output.cleanup</tt> <img src="images/help.gif" onMouseOver="Tip('Type of cleanup, if any', TITLE, 'csprefs.segment.output.cleanup', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr><td colspan="2" style="font-size: 8pt">&nbsp;</td></tr>
		</table>

		<script type="text/javascript">
			$(document).ready(function() {
				$("#form1").validate();
				//var t = $('#contrasts');
				//$.uiTableEdit( t );
				$('.editable').editable();

				$('#contrasts').tableHover({rowClass: 'hoverrow', colClass: 'hover', clickClass: 'click', headRows: true,  
                    footRows: true, headCols: true, footCols: true});
			});
		</script>

		<style>
			input { margin-left: 5pt; }
			input.error { border: 1px solid red; }
			label.error {
				background: url('images/unchecked.gif') no-repeat;
				padding-left: 16px;
				margin-left: .3em;
			}
			label.valid {
				background: url('images/checked.gif') no-repeat;
				display: block;
				width: 16px;
				height: 16px;
			}
			.collabel { border-bottom: 1pt solid #DDDDDD; border-right: 1pt solid #DDDDDD; text-align: center; color: green; }
			.conrow { border-bottom: 1pt solid #DDDDDD; border-right: 1pt solid #DDDDDD; text-align: center; }
			.conheader { border-top: 1pt solid black; border-bottom: 1pt solid black; text-align: center; border-right: 1pt solid #DDDDDD; }
			.conlabel { background-color: white; text-align: right; font-weight: bold; border-right: 1pt solid black; padding-right: 10px; }
			.remove { color: red; font-weight: bold; text-decoration: none; }
			td.hover, tr.hover { background-color: bisque; }
		</style>

		<table cellspacing="0" cellpadding="4" id="tableone" class="editor">
			<tr>
				<td colspan="2" height="30px" style="border-top: #999999 1pt solid; color: darkblue"><B>Behavioral Matchup</B></td>
			</tr>
			<tr>
				<td class="label">Behavioral data queue</td>
				<td class="value"><input type="text" name="edit_beh_queue" class="csprefsinput" value="/datadir/task_queue" size="40"> <tt>csprefs.beh_queue_dir</tt> <img src="images/help.gif" onMouseOver="Tip('directory where beh data is queued up', TITLE, 'csprefs.beh_queue_dir', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Digits</td>
				<td class="value"><input type="text" name="edit_beh_digits" value="[4]" class="csprefsinput required"> <tt>csprefs.digits</tt> <img src="images/help.gif" onMouseOver="Tip('number of digits to match in files or folders within beh_queue_dir; this allows cs_beh_matchup to align the last n digits of the longest string of digits in a filename with the last n digits of the scan directory name. Can be [3,4] or [4], etc...', TITLE, 'csprefs.digits', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr><td colspan="2" style="font-size: 8pt">&nbsp;</td></tr>

			<tr>
				<td colspan="2" height="30px" style="border-top: #999999 1pt solid; color: darkblue"><B>Statistics</B></td>
			</tr>
			<tr>
				<td class="label">Make ASCIIs</td>
				<td class="value"><input type="checkbox" name="edit_stat_makeasciis" value="yes" class="csprefsinput" checked> <tt>csprefs.stats_make_asciis</tt> <img src="images/help.gif" onMouseOver="Tip('whether to run a script generating ASCII timing files from the subject\'s behavioral data', TITLE, 'csprefs.stats_make_asciis', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">ASCII script</td>
				<td class="value"><input type="text" name="edit_stat_asciiscript" value="/path/to/script/asciis.m" class="csprefsinput" checked size="40"> <tt>csprefs.stats_ascii_script</tt> <img src="images/help.gif" onMouseOver="Tip('if csprefs.stats_ascii_script is 1, then this should specify the path to a script that computes timings from whatever sorts of files are in the subject\'s \'beh\' folder. CenterScripts promises that the script will be run such that its current directory is the \'beh\' folder; everything else is up to the file you specify here.', TITLE, 'csprefs.stats_ascii_script', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<span class="autocs58">
			<tr>
				<td class="label">Behavioral directory name</td>
				<td class="value"><input type="text" name="edit_stat_behdirname" value="beh" class="csprefsinput" checked size="40"> <tt>csprefs.stats_beh_dir_name</tt> <img src="images/help.gif" onMouseOver="Tip('Behavioral directory name. This will be used when make ascii script is used', TITLE, 'csprefs.stats_beh_dir_name', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Use relative path?</td>
				<td class="value"><input type="checkbox" name="edit_stat_relativepath" value="yes" class="csprefsinput" checked> <tt>csprefs.stats_files_relative_path_sub</tt> <img src="images/help.gif" onMouseOver="Tip('Checked means onset and duration files will be relative to <b>subject</b> directory and unchecked means onset and duration files will be relative to <b>run</b> directory.', TITLE, 'csprefs.stats_files_relative_path_sub', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			</span>
			<tr>
				<td class="label">Stats directory name</td>
				<td class="value"><input type="text" name="edit_stat_dirname" value="stats" class="csprefsinput required"> <tt>csprefs.stats_dir_name</tt> <img src="images/help.gif" onMouseOver="Tip('what to call the directory in which stats are run', TITLE, 'csprefs.stats_dir_name', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Stats image pattern</td>
				<td class="value"><input type="text" name="edit_stat_pattern" value="sw*img" class="csprefsinput required"> <tt>csprefs.stats_pattern</tt> <img src="images/help.gif" onMouseOver="Tip('pattern for images on which to run stats. Wildcards (*) and literals only. If demand warrants, we can do this with regexp instead, but I doubt it\'s necessary', TITLE, 'csprefs.stats_pattern', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Behavioral units</td>
				<td class="value"><input type="text" name="edit_stat_behunits" value="scans" class="csprefsinput required"> <tt>csprefs.stats_beh_units</tt> <img src="images/help.gif" onMouseOver="Tip('whether ascii file timings are in scans or seconds. Should be either \'scans\' or \'secs\'; in the ONRC this should usually be \'scans\'', TITLE, 'csprefs.stats_beh_units', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Volterra?</td>
				<td class="value"><input type="checkbox" name="edit_stat_volterra" value="yes"> <tt>csprefs.stats_volterra</tt> <img src="images/help.gif" onMouseOver="Tip('corresponds to SPM GUI option \'Model interactions (Volterra)\'', TITLE, 'csprefs.stats_volterra', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Basis function</td>
				<td class="value">
					<select name="edit_stats_basisfunction" id="edit_stats_basisfunction" class="csprefsinput">
						<option value="1" selected>1 - hrf
						<option value="2">2 - hrf (with time derivative)
						<option value="3">3 - hrf (with time and dispersion derivatives)
						<option value="4">4 - Fourier set
						<option value="5">5 - Fourier set (Hanning)
						<option value="6">6 - Gamma functions
						<option value="7">7 - Finite Impulse Response
					</select> <tt>csprefs.stats_basis_func</tt>
					 <img src="images/help.gif" onMouseOver="Tip('which basis function to use. Usually canonical hemodynmaic response function (HRF) or HRF with time derivative. Value should be a number. Here are the available options, but only the first two are guaranteed to work right now.', TITLE, 'csprefs.stats_basis_func', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()">
				</td>
			</tr>

			<tr>
				<td colspan="2" height="30px" style="border-top: #999999 1pt solid; color: darkblue"><B>Behavioral Files and Regressors</B></td>
			</tr>
			<tr>
				<td class="label">Onset files</td>
				<td class="value" colspan="3">
					<table cellspacing="0" cellpadding="0">
						<tr>
							<td valign="top">
								<textarea name="edit_stat_onsetfiles" id="edit_stat_onsetfiles" class="csprefsinput" cols="90" rows="4" wrap="off"></textarea>
								<br><div class="label sublabel" id="onsetfiledims"></div>
							</td>
							<td valign="top">
								<img src="images/help.gif" onMouseOver="Tip('OK, here is where you need a LITTLE bit of Matlab knowledge. This is going to be a matrix of filenames. These filenames can either be relative to each subject directory (i.e., \'beh/event1.asc\' or \'run1/beh/event1.asc\' or whatnot), OR they can be absolute (i.e., \'/shasta/data1/mi3/event_always_thesame.asc\') if the onsets don\'t change between subjects. The number of rows in this matrix is the number of runs you have. If particular subjects did not complete all runs, the script will take care of it if I) run directories are numbered (i.e. \'1/\' \'2/\' or \'run1/\' \'run2/\' or anything of the sort) or II) the run(s) they did complete are the first run(s). If your run directories are NOT numbered AND the subject did not complete the FIRST run, you may need to reconfigure a prefs file for that subject and run them manually. There should be as many filenames in each row as there are events in that run. If a particular event does not occur in a run, it is OK for the file to be empty or nonexistent. NOTE: It is OK to use wildcards (asterisks) in these filenames as long as the pattern only matches one file (this is good if your files are named s123_event1.asc or whatnot); however, asterisks cannot appear in the path, just the filename itself.', TITLE, 'csprefs.stats_onset_files', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()">&nbsp;<br>
							</td>
							<td valign="top">
								<span style="font-family: courier new; font-size:11pt; color: darkblue;">csprefs.stats_onset_files</span>
								<br><br>
								<span style="font-size: 10pt">
								<img src="images/dot.png"> Filenames are comma separated<br>
								<img src="images/dot.png"> Each <b>run</b> should be on its own line<br>
								<img src="images/dot.png"> No need for ' ' marks around filenames
								<br><br>
								</span>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class="label">Duration files</td>
				<td class="value" colspan="3">
					<table cellspacing="0" cellpadding="0">
						<tr>
							<td valign="top">
								<textarea name="edit_stat_durationfiles" id="edit_stat_durationfiles" class="csprefsinput" cols="90" rows="4" wrap="off"></textarea>
								<br><div class="label sublabel" id="durationfiledims"></div>
							</td>
							<td valign="top">
								<img src="images/help.gif" onMouseOver="Tip('same exact rules as above, with one addition: If you have short events, i.e., you want your events to all be duration 0, then just enter this option as [] (the empty matrix) or 0. Otherwise, number of files etc. should match up with the onset files.', TITLE, 'csprefs.stats_duration_files', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()">&nbsp;
							</td>
							<td valign="top">
								<span style="font-family: courier new; font-size:11pt; color: darkblue;">csprefs.stats_duration_files</span>
								<br><br>
								<span style="font-size: 10pt">
								<img src="images/dot.png"> Filenames are comma separated<br>
								<img src="images/dot.png"> Each <b>run</b> should be on its own line<br>
								<img src="images/dot.png"> Leave blank if not using duration files<br>
								<img src="images/dot.png"> No need for ' ' marks around filenames
								<br><br>
								</span>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class="label">Regressor files<br><span class="sublabel">Regressors will be ignored<br>if time censoring is used</span></td>
				<td class="value" colspan="3">
					<table cellspacing="0" cellpadding="0">
						<tr>
							<td valign="top">
								<textarea name="edit_stat_regressorfiles" id="edit_stat_regressorfiles" class="csprefsinput" cols="90" rows="4" wrap="off"></textarea>
								<br><div class="label sublabel" id="regressorfiledims"></div>
							</td>
							<td valign="top">
								<img src="images/help.gif" onMouseOver="Tip('matrix of names of files that contain additional regressors. Most commonly used for regressing out movement parameters. Any number of files can be specified, each containing one or more regressors. Regressor files for each session should be specified in a separate row, like csprefs.stats_onset_files and so forth. As in  csprefs.stats_onset_files, wildcard characters in filenames are OK with certain restrictions. If you don\'t need any additional regressors, leave this blank', TITLE, 'csprefs.stats_regressor_files', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()">&nbsp;
							</td>
							<td valign="top">
								<span style="font-family: courier new; font-size:11pt; color: darkblue;">csprefs.stats_regressor_files</span>
								<br><br>
								<span style="font-size: 10pt">
								<img src="images/dot.png"> Files are comma separated<br>
								<img src="images/dot.png"> Each <b>run</b> should be on its own line<br>
								<img src="images/dot.png"> Leave this textbox blank if not using regressor files<br>
								<img src="images/dot.png"> No need for ' ' marks around filenames
								<br><br>
								</span>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class="label">Regressor names<br><span class="sublabel">Regressors will be ignored<br>if time censoring is used</span></td>
				<td class="value" colspan="3">
					<table cellspacing="0" cellpadding="0">
						<tr>
							<td valign="top">
								<textarea name="edit_stat_regressornames" id="edit_stat_regressornames" class="csprefsinput" cols="90" rows="4" wrap="off"></textarea>
								<br><div class="label sublabel" id="regressornamedims"></div>
							</td>
							<td valign="top">
								<img src="images/help.gif" onMouseOver="Tip('names for regressors specified in csprefs.stats_regressor_files. There should be one name for each REGRESSOR, which is not necessarily the same as one name per FILE; often files contain more than one regressor (for example, the realignment parameters file contains six regressors). If there are not enough names, there will be problems; if there are too many names, only the first n names will be used, where n=your number of regressors. Just like the above, put names for each run on separate rows, and wildcards in filenames are OK with certain restrictions. If you have no regressors specified, leave this blank', TITLE, 'csprefs.stats_regressor_names', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()">&nbsp;
							</td>
							<td valign="top">
								<span style="font-family: courier new; font-size:11pt; color: darkblue;">csprefs.stats_regressor_names</span>
								<br><br>
								<span style="font-size: 10pt">
								<img src="images/dot.png"> Names are comma separated<br>
								<img src="images/dot.png"> Each <b>run</b> should be on its own line<br>
								<img src="images/dot.png"> Leave this textbox blank if not using regressor files<br>
								<img src="images/dot.png"> No need for ' ' marks around names
								<br><br>
								</span>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class="label">Parameter names</td>
				<td class="value" colspan="3">
					<table cellspacing="0" cellpadding="0">
						<tr>
							<td valign="top">
								<textarea name="edit_stat_parameternames" id="edit_stat_parameternames" class="csprefsinput" cols="90" rows="4" wrap="off"></textarea>
								<br><div class="label sublabel" id="paramnamedims"></div>
							</td>
							<td valign="top">
								<img src="images/help.gif" onMouseOver="Tip('cell matrix of parametric effect names to model. This should be the same number of runs and events per run as csprefs.stats_onset_files, unless you don\'t want to model any parametric effects at all (in this case, set csprefs.stats_param_names equal to {}, the empty matrix). For each event in your model, you can have one or more parametric effects, specified as follows. To model the effect of time, enter \'time\' as the name of the effect; to model another effect that you will specify, enter a descriptive name such as \'difficulty\' or \'brightness\' or so forth; and to model no parameters for that event, enter either \'none\' or an empty string or matrix such as \'\' or []. You can specify multiple parameters for an event as well by making a cell within a cell, e.g. {\'time\',\'difficulty\'}. This makes more sense in the examples (see below).', TITLE, 'csprefs.stats_param_names', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()">&nbsp;
							</td>
							<td valign="top">
								<span style="font-family: courier new; font-size:11pt; color: darkblue;">csprefs.stats_param_names</span>
								<br><br>
								<span style="font-size: 10pt">
								<img src="images/dot.png"> Names are comma separated<br>
								<img src="images/dot.png"> Each <b>run</b> should be on its own line<br>
								<img src="images/dot.png"> Leave this textbox blank if not using extra parameters<br>
								<img src="images/dot.png"> No need for ' ' marks around names
								<br><br>
								</span>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class="label">Parameter orders</td>
				<td class="value" colspan="3">
					<table cellspacing="0" cellpadding="0">
						<tr>
							<td valign="top">
								<textarea name="edit_stat_parameterorders" id="edit_stat_parameterorders" class="csprefsinput" cols="90" rows="4" wrap="off"></textarea>
								<br><div class="label sublabel" id="paramorderdims"></div>
							</td>
							<td valign="top">
								<img src="images/help.gif" onMouseOver="Tip('the polynomial orders (i.e. 1 for linear, 2 for quadratic, etc.) of the parameters specified in csprefs.stats_param_names. These should all be numeric values, arranged in the same structure as the strings in csprefs.stats_param_names. If you left a parameter empty, you can enter the order as 0. If you are not modeling any parametric effects, you can leave csprefs.stats_param_orders blank', TITLE, 'csprefs.stats_param_orders', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()">&nbsp;
							</td>
							<td valign="top">
								<span style="font-family: courier new; font-size:11pt; color: darkblue;">csprefs.stats_param_orders: </span>
								<br><br>
								<span style="font-size: 10pt">
								<img src="images/dot.png"> Orders are comma separated<br>
								<img src="images/dot.png"> Each <b>run</b> should be on its own line<br>
								<img src="images/dot.png"> Leave this textbox blank if not using extra parameters<br>
								<img src="images/dot.png"> No need for ' ' marks around orders
								<br><br>
								</span>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class="label">Parameter files</td>
				<td class="value" colspan="3">
					<table cellspacing="0" cellpadding="0">
						<tr>
							<td valign="top">
								<textarea name="edit_stat_parameterfiles" id="edit_stat_parameterfiles" class="csprefsinput" cols="90" rows="4" wrap="off"></textarea>
								<br><div class="label sublabel" id="paramfiledims"></div>
							</td>
							<td valign="top">
								<img src="images/help.gif" onMouseOver="Tip('if you specify any parameters in csprefs.stats_param_names besides 'time', here is where you need to provide files that contain the parameter values. These follow the same rules for filenames as in csprefs.stats_onset_files, and each file should contain the same number of parameter values as the number of events to which the parameter corresponds. Like csprefs.stats_param_orders, this should be in the same structure as csprefs.stats_param_names; if you have specified 'time' or no parameter ('none','',etc.) in csprefs.stats_param_names, you can also specify 'none','',etc. in the corresponding place here. If you are not modeling any parametric effects, you can leave csprefs.stats_param_files blank', TITLE, 'csprefs.stats_param_files', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()">&nbsp;
							</td>
							<td valign="top">
								<span style="font-family: courier new; font-size:11pt; color: darkblue;">csprefs.stats_param_files</span>
								<br><br>
								<span style="font-size: 10pt">
								<img src="images/dot.png"> Filenames are comma separated<br>
								<img src="images/dot.png"> Each <b>run</b> should be on its own line<br>
								<img src="images/dot.png"> Leave this textbox blank if not using extra parameters<br>
								<img src="images/dot.png"> No need for ' ' marks around filenames
								<br><br>
								</span>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class="label">Time censoring files<br><span class="sublabel">Regressors will be ignored<br>if time censoring is used</span></td>
				<td class="value" colspan="3">
					<table cellspacing="0" cellpadding="0">
						<tr>
							<td valign="top">
								<textarea name="edit_stat_censorfiles" id="edit_stat_censorfiles" class="csprefsinput" cols="90" rows="4" wrap="off"></textarea>
								<br><div class="label sublabel" id="censorfiledims"></div>
							</td>
							<td valign="top">
								<img src="images/help.gif" onMouseOver="Tip('Time censoring', TITLE, 'csprefs.stats_param_files', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()">&nbsp;
							</td>
							<td valign="top">
								<span style="font-family: courier new; font-size:11pt; color: darkblue;">csprefs.stats_regressor_censor</span>
								<br><br>
								<span style="font-size: 10pt">
								Should be in the following format, with semi colons at the end of each line:<br>
								<tt>'1/MSDCalc.txt';<br>
								'2/MSDCalc.txt';</tt><br>
								<img src="images/dot.png"> The fields will be properly formatted when the prefs file is generated
								<br><br>
								</span>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr><td colspan="2" style="font-size: 8pt">&nbsp;</td></tr>

			<tr>
				<td colspan="2" height="30px" style="border-top: #999999 1pt solid; color: darkblue"><B>Contrast Matrix</B><br><span style="color:#666666; font-size:8pt;">This contrast matrix can get slow when you have a lot of contrasts and behavioral files... so beware</span></td>
			</tr>
			<tr>
				<td colspan="3" style="border: 1pt dashed gray; padding:10pt;">

					<script type="text/javascript">
					/* add a column */
					function addCol() {
						/* get existing values */
						var numcols = document.getElementById("numcols").value;
						var numrows = document.getElementById("numrows").value;
						var headerexists = document.getElementById("headerexists").value;
						numcols++;
						document.getElementById("numcols").value = numcols;

						var str = "";
						if (numrows == 0) {
							/* need to add a TR tag if there are no rows yet */
							if (headerexists == 0){
								$("#contrasts thead tr").append("<td>&nbsp;</td><td class='conheader'>Contrast Name</td>\n");
							}
							$("#contrasts tbody").append("<tr id=\"row0\"><td>&nbsp;</td><td align='right'>Label</td><td class=\"collabel col" + numcols + "\" onMouseOver=\"this.style.backgroundColor='skyblue'; highlightCell(" + numcols + "," + numrows + ",'bisque')\" onMouseOut=\"this.style.backgroundColor='white'; highlightCell(" + numcols + "," + numrows + ",'white')\"><span class='editable'>n</span></td></tr>\n");
							numrows++;
							str = "<tr id=\"row1\">\n";
							str += "<td>&nbsp;<a href='' class='remove' onMouseDown='RemoveRow(\"#row" + numrows + "\"); return false;'><img src='images/delete12.png' border='0'></a></td>\n";
							str += "<td class='conlabel rowlabel" + numrows + "'>Contrast " + numrows + "</td>\n";
							str += "<td class='conrow col" + numcols + "' onMouseOver=\"this.style.backgroundColor='skyblue'; highlightCell(" + numcols + "," + numrows + ",'bisque')\" onMouseOut=\"this.style.backgroundColor='white'; highlightCell(" + numcols + "," + numrows + ",'white')\"><span class='editable'>0</span></td>\n</tr>\n";
							$("#contrasts tbody").append(str);
							document.getElementById("numrows").value = numrows;
						}
						else {
							if ((numcols == 1) && (headerexists == 0)) {
								$("#contrasts tbody tr").append("<td class=\"conlabel col" + numcols + " rowlabel" + numrows + "\">Contrast " + numrows + " <input type='button' style='border:0pt' value='X' onMouseDown='RemoveRow(\"#row" + numrows + "\"); return false;'></td><td class=\"conrow col" + numcols + "\"><span class='editable'>0</span></td>\n");
							}
							else {
								var i = 0;
								for (i=0;i<=numrows;i++ ){
									if (i == 0) {
										$("#row" + i).append("<td class='collabel col" + numcols + "' onMouseOver=\"this.style.backgroundColor='skyblue'; highlightCell(" + numcols + "," + i + ",'bisque')\" onMouseOut=\"this.style.backgroundColor='white'; highlightCell(" + numcols + "," + i + ",'white')\"><span class='editable'>n</span></td>\n");
									}
									else {
										$("#row" + i).append("<td class='conrow col" + numcols + "' onMouseOver=\"this.style.backgroundColor='skyblue'; highlightCell(" + numcols + "," + i + ",'bisque')\" onMouseOut=\"this.style.backgroundColor='white'; highlightCell(" + numcols + "," + i + ",'white')\"><span class='editable'>0</span></td>\n");
									}
								}
							}
						}

						document.getElementById("headerexists").value = 1; /* a header should always exist by this point */

						/* always need to add a TD to the THEAD, as a column header */
						str = "<td class='conheader col" + numcols + "'>&nbsp;<a href='' class='remove' onClick='RemoveCol(\".col" + numcols + "\"); return false;'><img src='images/delete12.png' border='0'></a>&nbsp;</td>\n"
						$("#contrasts thead tr").append(str);

						/* update the #col, #row display */
						$('#coldisplay').text(numcols);
						$('#rowdisplay').text(numrows);

						/* make sure the new cells are editable */
						//var t = $('#contrasts');
						//$.uiTableEdit( t );
						$('.editable').editable();
					}

					function addRow() {
						var numrows = document.getElementById("numrows").value;
						var numcols = document.getElementById("numcols").value;
						var headerexists = document.getElementById("headerexists").value;
						numrows++;
						document.getElementById("numrows").value = numrows;

						var i = 0;
						var str = "";

						if (numrows == 1) {
							str = "<tr id=\"row0\"><td>&nbsp;</td><td class='conlabel rowlabel0'>Labels</td>";
							if ((numcols == 0) && (headerexists == 0)) {
								/* if this is the first thing added, and no columns or headers exist... a header will need to be created */
								numcols++;
								document.getElementById("numcols").value = numcols;
								str += "<td class='collabel col" + numcols + "'  onMouseOver=\"this.style.backgroundColor='skyblue'; highlightCell(1,0,'bisque')\" onMouseOut=\"this.style.backgroundColor='white'; highlightCell(1,0,'white')\"><span class='editable'>n</span></td>";

								$("#contrasts thead tr").append("<td>&nbsp;</td><td class='conheader'>Contrast Name</td><td class='conheader col" + numcols + "'>&nbsp;<a href='' class='remove' onClick='RemoveCol(\".col" + numcols + "\"); return false;'><img src='images/delete12.png' border='0'></a>&nbsp;</td>\n");

								document.getElementById("headerexists").value = 1;
								headerexists = 1;
							}
							str += "<tr id=\"row" + numrows + "\"><td>&nbsp;<a href='' class='remove' onMouseDown='RemoveRow(\"#row" + numrows + "\"); return false;'><img src='images/delete12.png' border='0'></a></td><td class='conlabel rowlabel" + numrows + "'><span class='editable'>Contrast " + numrows + "</span></td>";
							document.getElementById("numrows").value = numrows;
						}
						else {
							str = "<tr id=\"row" + numrows + "\"><td>&nbsp;<a href='' class='remove' onMouseDown='RemoveRow(\"#row" + numrows + "\"); return false;'><img src='images/delete12.png' border='0'></a></td><td class='conlabel rowlabel" + numrows + "'><span class='editable'>Contrast " + numrows + "</span></td>";
						}

						if ((numcols == 0) && (headerexists == 0)) {
							/* if this is the first thing added, and no columns or headers exist... a header will need to be created */
							numcols++;
							document.getElementById("numcols").value = numcols;
							str += "<td class='conrow col" + numcols + "'  onMouseOver=\"this.style.backgroundColor='skyblue'; highlightCell(1," + numrows + ",'bisque')\" onMouseOut=\"this.style.backgroundColor='white'; highlightCell(1," + numrows + ",'white')\"><span class='editable'>0</span></td>";

							$("#contrasts thead tr").append("<td>&nbsp;</td><td class='conheader'>Contrast Name</td><td class='conheader col" + numcols + "'>&nbsp;<a href='' class='remove' onClick='RemoveCol(\".col" + numcols + "\"); return false;'><img src='images/delete12.png' border='0'></a>&nbsp;</td>\n");

							document.getElementById("headerexists").value = 1;
						}
						else {
							for (i=1;i<=numcols;i++ ){
								str += "<td class='conrow col" + i + "' onMouseOver=\"this.style.backgroundColor='skyblue'; highlightCell(" + i + "," + numrows + ",'bisque')\" onMouseOut=\"this.style.backgroundColor='white'; highlightCell(" + i + "," + numrows + ",'white')\"><span class='editable'>0</td>\n";
							}
						}
						str += "</tr>\n";
						$("#contrasts tbody").append(str);

						/* update the #col, #row display */
						$('#rowdisplay').text(numrows);
						$('#coldisplay').text(numcols);

						/* make sure the new cells are editable */
						//var t = $('#contrasts');
						//$.uiTableEdit( t );
						$('.editable').editable();
					}

					function highlightCell(col, row, color) {
						$("thead .col" + col).css("background-color",color);
						$(".rowlabel" + row).css("background-color",color);
						//$("#row" + row).css("background-color",color);
					}

					function RemoveCol(id) {
						$(id).remove();
						var numcols = document.getElementById("numcols").value;
						numcols--;
						document.getElementById("numcols").value = numcols;
						$('#coldisplay').text(numcols);
					}

					function RemoveRow(id) {
						$(id).remove();
						var numrows = document.getElementById("numrows").value;
						numrows--;
						document.getElementById("numrows").value = numrows;
						$('#rowdisplay').text(numrows);
					}

					function ConvertToCSV() {
						$("#contrasts").table2csv( {
							callback: function (csv) {
								document.getElementById("contrastmatrix").value = csv;
							}
						});
					}

					function SetupMatrix() {
						/* get all the values necessary to create the default matrix */
						var basisfunction = document.getElementById('edit_stats_basisfunction').value;

						onset_size = GetMatrixSize(document.getElementById('edit_stat_onsetfiles').value);
						onset_numrows = onset_size[0];
						onset_numcols = onset_size[1];
						$('#onsetfiledims').text(onset_numcols + ' x ' + onset_numrows);

						dur_size = GetMatrixSize(document.getElementById('edit_stat_durationfiles').value);
						dur_numrows = dur_size[0];
						dur_numcols = dur_size[1];
						$('#durationfiledims').text(dur_numcols + ' x ' + dur_numrows);

						reg_size = GetMatrixSize(document.getElementById('edit_stat_regressorfiles').value);
						reg_numrows = reg_size[0];
						reg_numcols = reg_size[1];
						$('#regressorfiledims').text(reg_numcols + ' x ' + reg_numrows);

						regname_size = GetMatrixSize(document.getElementById('edit_stat_regressornames').value);
						regname_numrows = regname_size[0];
						regname_numcols = regname_size[1];
						$('#regressornamedims').text(regname_numcols + ' x ' + regname_numrows);

						paramname_size = GetMatrixSize(document.getElementById('edit_stat_parameternames').value);
						paramname_numrows = paramname_size[0];
						paramname_numcols = paramname_size[1];
						$('#paramnamedims').text(paramname_numcols + ' x ' + paramname_numrows);

						paramorder_size = GetMatrixSize(document.getElementById('edit_stat_parameterorders').value);
						paramorder_numrows = paramorder_size[0];
						paramorder_numcols = paramorder_size[1];
						$('#paramorderdims').text(paramorder_numcols + ' x ' + paramorder_numrows);

						paramfiles_size = GetMatrixSize(document.getElementById('edit_stat_parameterfiles').value);
						paramfiles_numrows = paramfiles_size[0];
						paramfiles_numcols = paramfiles_size[1];
						$('#paramfiledims').text(paramfiles_numcols + ' x ' + paramfiles_numrows);

						censorfiles_size = GetMatrixSize(document.getElementById('edit_stat_censorfiles').value);
						censorfiles_numrows = censorfiles_size[0];
						censorfiles_numcols = censorfiles_size[1];
						$('#censorfiledims').text(censorfiles_numcols + ' x ' + censorfiles_numrows);
						
						/* create the appropriate number of columns in the contrast matrix */
						var numcols = ((onset_numrows * onset_numcols)*basisfunction) + (reg_numcols * onset_numrows) + onset_numrows;
						for (i=0; i<numcols; i++) { addCol(); }
					}

					/* ========= GetMatrixSize =========
					   returns (rows, cols)
					*/
					function GetMatrixSize(str) {
						if (str.replace(/^\s+|\s+$/g,"") == "") {
							return Array(0,0);
						}
						else {
							var numrows = 0;
							var rows = str.split(/\n/);
							var ncols = -1;
							for (i=0;i<rows.length;i++) {
								if (rows[i].replace(/^\s+|\s+$/g,"") != "") {
									numrows++;
									nrows = rows[i].split(',');
									numcols = nrows.length;
									if (ncols == -1) {
										ncols = numcols;
									}
									else {
										if (ncols != numcols) {
											/* rows don't have the same number of items... not good */
											return Array(numrows,-1);
										}
									}
								}
							}
							return Array(numrows,numcols);
						}
					}

					</script>

					<!-- contrast matrix related variables -->
					<input type="hidden" id="numrows" value="0">
					<input type="hidden" id="numcols" value="0">
					<input type="hidden" id="headerexists" value="0">
					<input type="hidden" id="contrastmatrix" name="edit_stat_contrastmatrix" value="empty">
					<table width="100%" cellspacing="0" cellpadding="0">
						<tr>
							<td>
								<b>Step 1:</b> <a href="" onClick="SetupMatrix(); return false;" class="link">Create Matrix</a>
								<br>
								<span style="font-size:10pt; color: #666666;">based on the above fields</span>
							</td>
							<td align="right">
								Matrix size:<br>
								<span style="font-size:10pt; color: darkgray"><span id="coldisplay">0</span> beh columns x <span id="rowdisplay">0</span> contrasts</span>
							</td>
						</tr>
					</table>
					<br>
					<b>Step 2:</b> <span style="font-size:11pt;">Edit matrix as needed. Use tab key to move between fields</span>
					<br><br>
					<a href="" onClick="addRow(); return false;" class="link"><img src="images/arrow-down13.png" style="border:0"> Add Contrast</a>
					&nbsp;
					<a href="" onClick="addCol(); return false;" class="link">Add Beh Column <img src="images/arrow-right13.png" style="border:0"></a>
					
					<br><br>
					<!-- the actual contrast table... empty when the page is first loaded -->
					<table id="contrasts" border="0" style="font-size: 10pt; border: 2pt solid black" cellpadding="1" cellspacing="0">
						<thead style="border-top: 1pt solid black; border-bottom: 1pt solid black">
							<tr>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
					<br>
				</td>
			</tr>

			<span class="autocs58">
			<tr>
				<td class="label">FIT length</td>
				<td class="value"><input type="text" name="edit_stat_xbflength" class="csprefsinput" value="12"> <tt>csprefs.xBF.length</tt> <img src="images/help.gif" onMouseOver="Tip('Not really sure what this does', TITLE, 'csprefs.xBF.length', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">FIT order</td>
				<td class="value"><input type="text" name="edit_stat_xbforder" class="csprefsinput" value="8"> <tt>csprefs.xBF.order</tt> <img src="images/help.gif" onMouseOver="Tip('Don\'t really know what this is either', TITLE, 'csprefs.stats_xBF.order', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Time modulation</td>
				<td class="value"><input type="text" name="edit_stat_timemodulation" class="csprefsinput" value="" size="70"> <tt>csprefs.stats_time_modulation</tt> <img src="images/help.gif" onMouseOver="Tip('Specify a cell array of size number of sessions by conditions. Where the order of time modulation is as follows<br><br>0 - No Time modulation<br>1 - 1st order<br>2 - 2nd order<br>3 - 3rd order<br>4 - 4th order<br>5 - 5th order<br>6 - 6th order<br><br>Example is {1, 1, 1; 1, 1, 1};', TITLE, 'csprefs.stats_time_modulation', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Parametric modulation</td>
				<td class="value"><input type="text" name="edit_stat_parametricmodulation" class="csprefsinput" value="" size="70"> <tt>csprefs.stats_parametric_modulation</tt> <img src="images/help.gif" onMouseOver="Tip('Specify a cell array of size number of sessions by conditions.<br><br>For each parameter each condition the values are given as follows<br>{parameter_name, parameter_vector, polynomial expansion}<br>a. Parameter_name - \'Targets\'<br>b. parameter_vector must be of the same length as the onset timings for that condition like [1:23]<br>c. polynomial expansion - Options are as follows<br>1 - 1st order<br>2 - 2nd order<br>3 - 3rd order<br>4 - 4th order<br>5 - 5th order<br>6 - 6th order<br><br><br>Example is {{\'Targets\', [1:23], 1}, {\'Novels\', [1:23], 1}, {\'Standards\', [1:184], 1}; {\'Targets\', [1:24], 1}, {\'Novels\', [1:23], 1}, {\'Standards\', [1:185], 1}};', TITLE, 'csprefs.stats_parametric_modulation', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			</span>
			<tr>
				<td class="label">High-pass cutoff</td>
				<td class="value"><input type="text" name="edit_stat_highpasscutoff" class="csprefsinput" value="128"> <tt>csprefs.stats_highpass_cutoff</tt> <img src="images/help.gif" onMouseOver="Tip('number of seconds for high-pass filter. Default is 128. Put \'Inf\' (without quotes) for no filtering.', TITLE, 'csprefs.stats_highpass_cutoff', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Correct for<br>serial correlations?</td>
				<td class="value"><input type="checkbox" name="edit_stat_serialcorr" value="yes"> <tt>csprefs.stats_serial_corr</tt> <img src="images/help.gif" onMouseOver="Tip('number of seconds for high-pass filter. Default is 128. Put \'Inf\' (without quotes) for no filtering.', TITLE, 'csprefs.stats_serial_corr', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr><td colspan="2" style="font-size: 8pt">&nbsp;</td></tr>

			<tr>
				<td colspan="2" height="30px" style="border-top: #999999 1pt solid; color: darkblue"><B>Autoslice</B></td>
			</tr>
			<tr>
				<td class="label">Con #'s to autoslice</td>
				<td class="value">[<input type="text" name="edit_stat_autoslicecons" class="csprefsinput" value="">] <tt>csprefs.autoslice_cons</tt> <img src="images/help.gif" onMouseOver="Tip('vector of contrast numbers (e.g. [4:6,9,10]) to autoslice', TITLE, 'csprefs.autoslice_cons', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">P value</td>
				<td class="value"><input type="text" name="edit_stat_autoslicep" class="csprefsinput" value="0.05"> <tt>csprefs.autoslice_p</tt> <img src="images/help.gif" onMouseOver="Tip('p value (uncorrected only, for now) at which to show contrasts', TITLE, 'csprefs.autoslice_p', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Background</td>
				<td class="value"><input type="text" name="edit_stat_autoslicebackground" class="csprefsinput" value="/opt/spm2/canonical/ch2bet.img" size="40"> <tt>csprefs.autoslice_background</tt> <img src="images/help.gif" onMouseOver="Tip('absolute pathname of image to serve as the background for the autoslices... most likely some sort of anatomical', TITLE, 'csprefs.autoslice_background', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
				<td class="csprefsvariable"></td>
			</tr>
			<tr>
				<td class="label">Slices</td>
				<td class="value">[<input type="text" name="edit_stat_autosliceslices" class="csprefsinput" value="-40:4:72">] <tt>csprefs.autoslice_slices</tt> <img src="images/help.gif" onMouseOver="Tip('vector of z coordinates (in mm) at which to show slices (i.e. for [-40:4:72], shows slices every 4 mm from z=-40 up to z=72)', TITLE, 'csprefs.autoslice_slices', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Con #'s to email</td>
				<td class="value">[<input type="text" name="edit_stat_autosliceemailcons" class="csprefsinput" value="">] <tt>csprefs.autoslice_email_cons</tt> <img src="images/help.gif" onMouseOver="Tip('vector of contrast numbers to email out. Can be any subset of csprefs.autoslice_cons, including the empty matrix []', TITLE, 'csprefs.autoslice_email_cons', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr><td colspan="2" style="font-size: 8pt">&nbsp;</td></tr>

			<tr>
				<td colspan="2" height="30px" style="border-top: #999999 1pt solid; color: darkblue"><B>Derivative Boost</B></td>
			</tr>
			<tr>
				<td class="label">Overwrite beta images?</td>
				<td class="value"><input type="checkbox" name="edit_db_overwritebeta" class="csprefsinput" value="yes"> <tt>csprefs.dboost_overwrite_beta</tt> <img src="images/help.gif" onMouseOver="Tip('whether or not to overwrite the original beta images (alternative is to create new images).', TITLE, 'csprefs.dboost_overwrite_beta', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">File prefix</td>
				<td class="value"><input type="text" name="edit_db_fileprefix" class="csprefsinput" value="db_"> <tt>csprefs.dboost_file_prefix</tt> <img src="images/help.gif" onMouseOver="Tip('if creating new files, what to prefix the new files with. If you chose to overwrite the old betas, you can leave this set to the empty string; it will be ignored anyway', TITLE, 'csprefs.dboost_file_prefix', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Beta Numbers</td>
				<td class="value">[<input type="text" name="edit_db_betanums" class="csprefsinput" value="" size="40">] <tt>csprefs.dboost_beta_nums</tt> <img src="images/help.gif" onMouseOver="Tip('A comma separated list. Which beta images to apply a derivative boost to, as numbered by SPM (e.g., [1, 3] will boost \'beta_0001.img\' and \'beta_0003.img\')', TITLE, 'csprefs.dboost_beta_nums', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Threshold</td>
				<td class="value"><input type="text" name="edit_db_threshold" class="csprefsinput" value="1"> <tt>csprefs.dboost_threshold</tt> <img src="images/help.gif" onMouseOver="Tip('minimum ratio of main effect to derivative required to perform the boost. Default is 1, meaning the boost will be applied anywhere that the main effect is at least as big as the derivative effect', TITLE, 'csprefs.dboost_threshold', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Smooth kernel<br><span class="sublabel">Must contain 3 elements</span></td>
				<td class="value">[<input type="text" name="edit_db_smoothkernel" class="csprefsinput" value="8 8 8">] <tt>csprefs.dboost_smooth_kernel</tt> <img src="images/help.gif" onMouseOver="Tip('size of smoothing kernel to apply to the derivative boost effect, in mm', TITLE, 'csprefs.dboost_smooth_kernel', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">IM Calcs</td>
				<td class="value" colspan="3">
					<table cellspacing="0" cellpadding="0">
						<tr>
							<td valign="top">
								<textarea name="edit_db_imcalcs" class="csprefsinput" cols="40" rows="6" wrap="off"></textarea>
							</td>
							<td valign="top">
								<img src="images/help.gif" onMouseOver="Tip('any additional image calculations to make on your boosted images. In effect you are calculating new contrasts manually. This uses SPM\'s ImCalc syntax, where you specify several images and use i1,i2,i3,etc. to refer to them. So, i1+i2 creates the sum of the first and second images. Here, i1,i2,etc. will refer to the boosted images in the order you specified them in csprefs.dboost_beta_nums... so if csprefs.dboost_beta_nums is [1, 3, 5], then i1 refers to the boosted form of \'beta_0001.img\', i2 refers to the boosted \'beta_0003.img\', and so on.', TITLE, 'csprefs.dboost_im_calcs', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()">&nbsp;
							</td>
							<td valign="top">
								<span style="font-family: courier new; font-size:11pt; color: darkblue;">csprefs.dboost_im_calcs</span>
								<br><br>
								<span style="font-size: 10pt">
								<img src="images/dot.png"> Each equation should be on its own line<br>
								<img src="images/dot.png"> No need for ' ' marks around equations
								</span>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class="label">IM Names</td>
				<td class="value" colspan="3">
					<table cellspacing="0" cellpadding="0">
						<tr>
							<td valign="top">
								<textarea name="edit_db_imnames" class="csprefsinput" cols="40" rows="6" wrap="off"></textarea>
							</td>
							<td valign="top">
								<img src="images/help.gif" onMouseOver="Tip('names for the output images of each of the calculations specified in csprefs.dboost_im_calcs. The number of strings in csprefs.dboost_im_calcs and csprefs.dboost_im_names should match up, one name per calculation.', TITLE, 'csprefs.dboost_im_calcs', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()">&nbsp;
							</td>
							<td valign="top">
								<span style="font-family: courier new; font-size:11pt; color: darkblue;">csprefs.dboost_im_calcs</span>
								<br><br>
								<span style="font-size: 10pt">
								<img src="images/dot.png"> Each name should be on its own line<br>
								<img src="images/dot.png"> No need for ' ' marks around equations
								</span>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr><td colspan="2" style="font-size: 8pt">&nbsp;</td></tr>
		</table>

		<p><input type="submit" value="Create" name="submit" onMouseDown="ConvertToCSV();"></p>
		</form>
		
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- GetDistinctDBField ----------------- */
	/* -------------------------------------------- */
	function GetDistinctDBField($field, $table) {
		$sqlstring = "SELECT distinct($field) 'tag' from `$table` where realign_pattern <> ''";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$tag = $row['tag'];
			$str .= "\"$tag\",";
		}
		return $str;
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayPrefsList ------------------- */
	/* -------------------------------------------- */
	function DisplayPrefsList() {
	
		$urllist['Analysis'] = "pipelines.php";
		$urllist['CS Prefs'] = "csprefs.php";
		$urllist['Create Preferences File'] = "csprefs.php?action=addform";
		NavigationBar("Analysis", $urllist);
		
	?>

	<table class="graydisplaytable">
		<thead>
			<tr>
				<th>Name</th>
				<th>Description</th>
			</tr>
		</thead>
		<form method="post" action="csprefs.php">
		<input type="hidden" name="action" value="setdefaultinstance">
		<tbody>
			<?
				$sqlstring = "select * from cs_prefs order by shortname";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$prefid = $row['csprefs_id'];
					$shortname = $row['shortname'];
					$desc = $row['description'];
			?>
			<tr>
				<td><?=$shortname?></td>
				<td><?=$description?></td>
			</tr>
			<? 
				}
			?>
		</tbody>
		</form>
	</table>
	<?
	}
?>


<? include("footer.php") ?>
