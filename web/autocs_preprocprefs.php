<?
	/* ----- setup variables ----- */
	if ($_POST["action"] == "") { $action = $_GET["action"]; } else { $action = $_POST["action"]; }
	if ($_POST["id"] == "") { $id = $_GET["id"]; } else { $id = $_POST["id"]; }
	if ($_POST["oldprefid"] == "") { $oldprefid = $_GET["oldprefid"]; } else { $oldprefid = $_POST["oldprefid"]; }
	if ($_POST["taskid"] == "") { $taskid = $_GET["taskid"]; } else { $taskid = $_POST["taskid"]; }

	if ($_POST["viewtype"] == "") { $viewtype = $_GET["viewtype"]; } else { $viewtype = $_POST["viewtype"]; }

	/* edit variables */
	if ($_POST["edit_description"] == "") { $edit_description = $_GET["edit_description"]; } else { $edit_description = $_POST["edit_description"]; }
	if ($_POST["edit_shortname"] == "") { $edit_shortname = $_GET["edit_shortname"]; } else { $edit_shortname = $_POST["edit_shortname"]; }
	if ($_POST["edit_extralines"] == "") { $edit_extralines = $_GET["edit_extralines"]; } else { $edit_extralines = $_POST["edit_extralines"]; }
	/* steps to do */
	if ($_POST["edit_do_dicomconvert"] == "") { $edit_do_dicomconvert = $_GET["edit_do_dicomconvert"]; } else { $edit_do_dicomconvert = $_POST["edit_do_dicomconvert"]; }
	if ($_POST["edit_do_reorient"] == "") { $edit_do_reorient = $_GET["edit_do_reorient"]; } else { $edit_do_reorient = $_POST["edit_do_reorient"]; }
	if ($_POST["edit_do_realign"] == "") { $edit_do_realign = $_GET["edit_do_realign"]; } else { $edit_do_realign = $_POST["edit_do_realign"]; }
	if ($_POST["edit_do_msdcalc"] == "") { $edit_do_msdcalc = $_GET["edit_do_msdcalc"]; } else { $edit_do_msdcalc = $_POST["edit_do_msdcalc"]; }
	if ($_POST["edit_do_coregister"] == "") { $edit_do_coregister = $_GET["edit_do_coregister"]; } else { $edit_do_coregister = $_POST["edit_do_coregister"]; }
	if ($_POST["edit_do_slicetime"] == "") { $edit_do_slicetime = $_GET["edit_do_slicetime"]; } else { $edit_do_slicetime = $_POST["edit_do_slicetime"]; }
	if ($_POST["edit_do_normalize"] == "") { $edit_do_normalize = $_GET["edit_do_normalize"]; } else { $edit_do_normalize = $_POST["edit_do_normalize"]; }
	if ($_POST["edit_do_smooth"] == "") { $edit_do_smooth = $_GET["edit_do_smooth"]; } else { $edit_do_smooth = $_POST["edit_do_smooth"]; }
	if ($_POST["edit_do_artrepair"] == "") { $edit_do_artrepair = $_GET["edit_do_artrepair"]; } else { $edit_do_artrepair = $_POST["edit_do_artrepair"]; }
	if ($_POST["edit_do_filter"] == "") { $edit_do_filter = $_GET["edit_do_filter"]; } else { $edit_do_filter = $_POST["edit_do_filter"]; }
	if ($_POST["edit_do_segment"] == "") { $edit_do_segment = $_GET["edit_do_segment"]; } else { $edit_do_segment = $_POST["edit_do_segment"]; }

	/* dicom convert */
	if ($_POST["edit_di_filepattern"] == "") { $edit_di_filepattern = $_GET["edit_di_filepattern"]; } else { $edit_di_filepattern = $_POST["edit_di_filepattern"]; }
	if ($_POST["edit_di_format"] == "") { $edit_di_format = $_GET["edit_di_format"]; } else { $edit_di_format = $_POST["edit_di_format"]; }
	if ($_POST["edit_di_writefileprefix"] == "") { $edit_di_writefileprefix = $_GET["edit_di_writefileprefix"]; } else { $edit_di_writefileprefix = $_POST["edit_di_writefileprefix"]; }
	if ($_POST["edit_di_outputdir"] == "") { $edit_di_outputdir = $_GET["edit_di_outputdir"]; } else { $edit_di_outputdir = $_POST["edit_di_outputdir"]; }

	/* reorient */
	if ($_POST["edit_ro_pattern"] == "") { $edit_ro_pattern = $_GET["edit_ro_pattern"]; } else { $edit_ro_pattern = $_POST["edit_ro_pattern"]; }
	if ($_POST["edit_ro_vector"] == "") { $edit_ro_vector = $_GET["edit_ro_vector"]; } else { $edit_ro_vector = $_POST["edit_ro_vector"]; }
	if ($_POST["edit_ro_write"] == "") { $edit_ro_write = $_GET["edit_ro_write"]; } else { $edit_ro_write = $_POST["edit_ro_write"]; }

	/* realign */
	if ($_POST["edit_re_coregister"] == "") { $edit_re_coregister = $_GET["edit_re_coregister"]; } else { $edit_re_coregister = $_POST["edit_re_coregister"]; }
	if ($_POST["edit_re_reslice"] == "") { $edit_re_reslice = $_GET["edit_re_reslice"]; } else { $edit_re_reslice = $_POST["edit_re_reslice"]; }
	if ($_POST["edit_re_useinrialign"] == "") { $edit_re_useinrialign = $_GET["edit_re_useinrialign"]; } else { $edit_re_useinrialign = $_POST["edit_re_useinrialign"]; }
	if ($_POST["edit_re_realignpattern"] == "") { $edit_re_realignpattern = $_GET["edit_re_realignpattern"]; } else { $edit_re_realignpattern = $_POST["edit_re_realignpattern"]; }
	if ($_POST["edit_re_inrialignrho"] == "") { $edit_re_inrialignrho = $_GET["edit_re_inrialignrho"]; } else { $edit_re_inrialignrho = $_POST["edit_re_inrialignrho"]; }
	if ($_POST["edit_re_inrialigncutoff"] == "") { $edit_re_inrialigncutoff = $_GET["edit_re_inrialigncutoff"]; } else { $edit_re_inrialigncutoff = $_POST["edit_re_inrialigncutoff"]; }
	if ($_POST["edit_re_inrialignquality"] == "") { $edit_re_inrialignquality = $_GET["edit_re_inrialignquality"]; } else { $edit_re_inrialignquality = $_POST["edit_re_inrialignquality"]; }
	if ($_POST["edit_re_fwhm"] == "") { $edit_re_fwhm = $_GET["edit_re_fwhm"]; } else { $edit_re_fwhm = $_POST["edit_re_fwhm"]; }
	if ($_POST["edit_re_rtm"] == "") { $edit_re_rtm = $_GET["edit_re_rtm"]; } else { $edit_re_rtm = $_POST["edit_re_rtm"]; }
	if ($_POST["edit_re_pw"] == "") { $edit_re_pw = $_GET["edit_re_pw"]; } else { $edit_re_pw = $_POST["edit_re_pw"]; }
	if ($_POST["edit_re_writeimages"] == "") { $edit_re_writeimages = $_GET["edit_re_writeimages"]; } else { $edit_re_writeimages = $_POST["edit_re_writeimages"]; }
	if ($_POST["edit_re_writemean"] == "") { $edit_re_writemean = $_GET["edit_re_writemean"]; } else { $edit_re_writemean = $_POST["edit_re_writemean"]; }

	/* coregister */
	if ($_POST["edit_co_run"] == "") { $edit_co_run = $_GET["edit_co_run"]; } else { $edit_co_run = $_POST["edit_co_run"]; }
	if ($_POST["edit_co_runreslice"] == "") { $edit_co_runreslice = $_GET["edit_co_runreslice"]; } else { $edit_co_runreslice = $_POST["edit_co_runreslice"]; }
	if ($_POST["edit_co_ref"] == "") { $edit_co_ref = $_GET["edit_co_ref"]; } else { $edit_co_ref = $_POST["edit_co_ref"]; }
	if ($_POST["edit_co_source"] == "") { $edit_co_source = $_GET["edit_co_source"]; } else { $edit_co_source = $_POST["edit_co_source"]; }
	if ($_POST["edit_co_otherpattern"] == "") { $edit_co_otherpattern = $_GET["edit_co_otherpattern"]; } else { $edit_co_otherpattern = $_POST["edit_co_otherpattern"]; }
	if ($_POST["edit_co_writeref"] == "") { $edit_co_writeref = $_GET["edit_co_writeref"]; } else { $edit_co_writeref = $_POST["edit_co_writeref"]; }

	/* slicetime correction */
	if ($_POST["edit_st_pattern"] == "") { $edit_st_pattern = $_GET["edit_st_pattern"]; } else { $edit_st_pattern = $_POST["edit_st_pattern"]; }
	if ($_POST["edit_st_sliceorder"] == "") { $edit_st_sliceorder = $_GET["edit_st_sliceorder"]; } else { $edit_st_sliceorder = $_POST["edit_st_sliceorder"]; }
	if ($_POST["edit_st_refslice"] == "") { $edit_st_refslice = $_GET["edit_st_refslice"]; } else { $edit_st_refslice = $_POST["edit_st_refslice"]; }
	if ($_POST["edit_st_ta"] == "") { $edit_st_ta = $_GET["edit_st_ta"]; } else { $edit_st_ta = $_POST["edit_st_ta"]; }

	/* normalize */
	if ($_POST["edit_no_determineparams"] == "") { $edit_no_determineparams = $_GET["edit_no_determineparams"]; } else { $edit_no_determineparams = $_POST["edit_no_determineparams"]; }
	if ($_POST["edit_no_writenormalized"] == "") { $edit_no_writenormalized = $_GET["edit_no_writenormalized"]; } else { $edit_no_writenormalized = $_POST["edit_no_writenormalized"]; }
	if ($_POST["edit_no_paramtemplate"] == "") { $edit_no_paramtemplate = $_GET["edit_no_paramtemplate"]; } else { $edit_no_paramtemplate = $_POST["edit_no_paramtemplate"]; }
	if ($_POST["edit_no_parampattern"] == "") { $edit_no_parampattern = $_GET["edit_no_parampattern"]; } else { $edit_no_parampattern = $_POST["edit_no_parampattern"]; }
	if ($_POST["edit_no_paramsourceweight"] == "") { $edit_no_paramsourceweight = $_GET["edit_no_paramsourceweight"]; } else { $edit_no_paramsourceweight = $_POST["edit_no_paramsourceweight"]; }
	if ($_POST["edit_no_matname"] == "") { $edit_no_matname = $_GET["edit_no_matname"]; } else { $edit_no_matname = $_POST["edit_no_matname"]; }
	if ($_POST["edit_no_writenormpattern"] == "") { $edit_no_writenormpattern = $_GET["edit_no_writenormpattern"]; } else { $edit_no_writenormpattern = $_POST["edit_no_writenormpattern"]; }
	if ($_POST["edit_no_writenormmatname"] == "") { $edit_no_writenormmatname = $_GET["edit_no_writenormmatname"]; } else { $edit_no_writenormmatname = $_POST["edit_no_writenormmatname"]; }

	/* smoothing */
	if ($_POST["edit_sm_kernel"] == "") { $edit_sm_kernel = $_GET["edit_sm_kernel"]; } else { $edit_sm_kernel = $_POST["edit_sm_kernel"]; }
	if ($_POST["edit_sm_pattern"] == "") { $edit_sm_pattern = $_GET["edit_sm_pattern"]; } else { $edit_sm_pattern = $_POST["edit_sm_pattern"]; }

	/* art repair */
	if ($_POST["edit_ar_pattern"] == "") { $edit_ar_pattern = $_GET["edit_ar_pattern"]; } else { $edit_ar_pattern = $_POST["edit_ar_pattern"]; }

	/* filtering */
	if ($_POST["edit_fi_pattern"] == "") { $edit_fi_pattern = $_GET["edit_fi_pattern"]; } else { $edit_fi_pattern = $_POST["edit_fi_pattern"]; }
	if ($_POST["edit_fi_cutofffreq"] == "") { $edit_fi_cutofffreq = $_GET["edit_fi_cutofffreq"]; } else { $edit_fi_cutofffreq = $_POST["edit_fi_cutofffreq"]; }

	/* segmentation */
	if ($_POST["edit_se_pattern"] == "") { $edit_se_pattern = $_GET["edit_se_pattern"]; } else { $edit_se_pattern = $_POST["edit_se_pattern"]; }
	if ($_POST["edit_se_gmoutput"] == "") { $edit_se_gmoutput = $_GET["edit_se_gmoutput"]; } else { $edit_se_gmoutput = $_POST["edit_se_gmoutput"]; }
	if ($_POST["edit_se_wmoutput"] == "") { $edit_se_wmoutput = $_GET["edit_se_wmoutput"]; } else { $edit_se_wmoutput = $_POST["edit_se_wmoutput"]; }
	if ($_POST["edit_se_csfoutput"] == "") { $edit_se_csfoutput = $_GET["edit_se_csfoutput"]; } else { $edit_se_csfoutput = $_POST["edit_se_csfoutput"]; }
	if ($_POST["edit_se_biascor"] == "") { $edit_se_biascor = $_GET["edit_se_biascor"]; } else { $edit_se_biascor = $_POST["edit_se_biascor"]; }
	if ($_POST["edit_se_cleanup"] == "") { $edit_se_cleanup = $_GET["edit_se_cleanup"]; } else { $edit_se_cleanup = $_POST["edit_se_cleanup"]; }

?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>AutoCS - Pre-processing</title>
	</head>

<body>
<? $system = "grandcentral"; ?>
<? $section = "autocs"; ?>
<? include("header.php") ?>
<? include("menu.php") ?>
<? include("menu_gc.php") ?>
<? include("menu_gc_autocs.php") ?>
<br><br>

<?
	/* ----- determine which action to take ----- */
	if ($action == "add") {
		$id = AddPrefs($taskid, $edit_description, $edit_shortname, $edit_extralines, $edit_do_dicomconvert, $edit_do_reorient, $edit_do_realign, $edit_do_msdcalc, $edit_do_coregister, $edit_do_slicetime, $edit_do_normalize, $edit_do_smooth, $edit_do_artrepair, $edit_do_filter, $edit_do_segment, $edit_di_filepattern, $edit_di_format, $edit_di_writefileprefix, $edit_di_outputdir, $edit_ro_pattern, $edit_ro_vector, $edit_ro_write, $edit_re_coregister, $edit_re_reslice, $edit_re_useinrialign, $edit_re_realignpattern, $edit_re_inrialignrho, $edit_re_inrialigncutoff, $edit_re_inrialignquality, $edit_re_fwhm, $edit_re_rtm, $edit_re_pw, $edit_re_writeimages, $edit_re_writemean, $edit_co_run, $edit_co_runreslice, $edit_co_ref, $edit_co_source, $edit_co_otherpattern, $edit_co_writeref, $edit_st_pattern, $edit_st_sliceorder, $edit_st_refslice, $edit_st_ta, $edit_no_determineparams, $edit_no_writenormalized, $edit_no_paramtemplate, $edit_no_parampattern, $edit_no_paramsourceweight, $edit_no_matname, $edit_no_writenormpattern, $edit_no_writenormmatname, $edit_sm_kernel, $edit_sm_pattern, $edit_ar_pattern, $edit_fi_pattern, $edit_fi_cutofffreq, $edit_se_pattern, $edit_se_gmoutput, $edit_se_wmoutput, $edit_se_csfoutput, $edit_se_biascor, $edit_se_cleanup);
		DisplayPrefs($id, $viewtype);
	}
	elseif ($action == "addform") {
		AddPrefsForm($taskid);
	}
	elseif ($action == "copytonew") {
		$id = CopyToNew($taskid, $oldprefid);
		DisplayPrefs($id, "");
	}
	elseif ($action == "delete") {
		DeletePrefs($id);
	}
	elseif ($action == "display") {
		DisplayPrefs($id, $viewtype);
	}
	else {
		DisplaySummary($taskid);
	}


	/* -------------------------------------------- */
	/* ------- AddPrefs --------------------------- */
	/* -------------------------------------------- */
	function AddPrefs($taskid,
		$edit_description,
		$edit_shortname,
		$edit_extralines,
		$edit_do_dicomconvert, $edit_do_reorient, $edit_do_realign, $edit_do_msdcalc, $edit_do_coregister, $edit_do_slicetime, $edit_do_normalize, $edit_do_smooth, $edit_do_artrepair, $edit_do_filter, $edit_do_segment,
		$edit_di_filepattern, $edit_di_format, $edit_di_writefileprefix, $edit_di_outputdir,
		$edit_ro_pattern, $edit_ro_vector, $edit_ro_write,
		$edit_re_coregister, $edit_re_reslice, $edit_re_useinrialign, $edit_re_realignpattern, $edit_re_inrialignrho, $edit_re_inrialigncutoff, $edit_re_inrialignquality, $edit_re_fwhm, $edit_re_rtm, $edit_re_pw, $edit_re_writeimages, $edit_re_writemean,
		$edit_co_run, $edit_co_runreslice, $edit_co_ref, $edit_co_source, $edit_co_otherpattern, $edit_co_writeref,
		$edit_st_pattern, $edit_st_sliceorder, $edit_st_refslice, $edit_st_ta,
		$edit_no_determineparams, $edit_no_writenormalized, $edit_no_paramtemplate, $edit_no_parampattern, $edit_no_paramsourceweight, $edit_no_matname, $edit_no_writenormpattern, $edit_no_writenormmatname,
		$edit_sm_kernel, $edit_sm_pattern,
		$edit_ar_pattern,
		$edit_fi_pattern, $edit_fi_cutofffreq,
		$edit_se_pattern, $edit_se_gmoutput, $edit_se_wmoutput, $edit_se_csfoutput, $edit_se_biascor, $edit_se_cleanup) {

		/* fix the variables before putting them into the database */
		$edit_description = mysqli_real_escape_string($GLOBALS['linki'], $edit_description);
		$edit_shortname = mysqli_real_escape_string($GLOBALS['linki'], $edit_shortname);
		$edit_extralines = mysqli_real_escape_string($GLOBALS['linki'], $edit_extralines);
		if ($edit_do_dicomconvert == "yes") { $edit_do_dicomconvert = "1"; } else { $edit_do_dicomconvert = "0"; }
		if ($edit_do_reorient == "yes") { $edit_do_reorient = "1"; } else { $edit_do_reorient = "0"; }
		if ($edit_do_realign == "yes") { $edit_do_realign = "1"; } else { $edit_do_realign = "0"; }
		if ($edit_do_msdcalc == "yes") { $edit_do_msdcalc = "1"; } else { $edit_do_msdcalc = "0"; }
		if ($edit_do_coregister == "yes") { $edit_do_coregister = "1"; } else { $edit_do_coregister = "0"; }
		if ($edit_do_slicetime == "yes") { $edit_do_slicetime = "1"; } else { $edit_do_slicetime = "0"; }
		if ($edit_do_normalize == "yes") { $edit_do_normalize = "1"; } else { $edit_do_normalize = "0"; }
		if ($edit_do_smooth == "yes") { $edit_do_smooth = "1"; } else { $edit_do_smooth = "0"; }
		if ($edit_do_artrepair == "yes") { $edit_do_artrepair = "1"; } else { $edit_do_artrepair = "0"; }
		if ($edit_do_filter == "yes") { $edit_do_filter = "1"; } else { $edit_do_filter = "0"; }
		if ($edit_do_segment == "yes") { $edit_do_segment = "1"; } else { $edit_do_segment = "0"; }
		$edit_di_filepattern = mysqli_real_escape_string($GLOBALS['linki'], $edit_di_filepattern);
		$edit_di_format = mysqli_real_escape_string($GLOBALS['linki'], $edit_di_format);
		$edit_di_writefileprefix = mysqli_real_escape_string($GLOBALS['linki'], $edit_di_writefileprefix);
		$edit_di_outputdir = mysqli_real_escape_string($GLOBALS['linki'], $edit_di_outputdir);
		$edit_ro_pattern = mysqli_real_escape_string($GLOBALS['linki'], $edit_ro_pattern);
		$edit_ro_vector = mysqli_real_escape_string($GLOBALS['linki'], $edit_ro_vector);
		if ($edit_ro_write == "yes") { $edit_ro_write = 1; } else { $edit_ro_write = "0"; }
		if ($edit_re_coregister == "yes") { $edit_re_coregister = 1; } else { $edit_re_coregister = "0"; }
		if ($edit_re_reslice == "yes") { $edit_re_reslice = "1"; } else { $edit_re_reslice = "0"; }
		if ($edit_re_useinrialign == "yes") { $edit_re_useinrialign = "1"; } else { $edit_re_useinrialign = "0"; }
		$edit_re_realignpattern = mysqli_real_escape_string($GLOBALS['linki'], $edit_re_realignpattern);
		$edit_re_inrialignrho = mysqli_real_escape_string($GLOBALS['linki'], $edit_re_inrialignrho);
		if ($edit_re_rtm == "yes") { $edit_re_rtm = "1"; } else { $edit_re_rtm = "0"; }
		$edit_re_pw = mysqli_real_escape_string($GLOBALS['linki'], $edit_re_pw);
		if ($edit_re_writemean == "yes") { $edit_re_writemean = "1"; } else { $edit_re_writemean = "0"; }
		if ($edit_co_run == "yes") { $edit_co_run = "1"; } else { $edit_co_run = "0"; }
		if ($edit_co_runreslice == "yes") { $edit_co_runreslice = "1"; } else { $edit_co_runreslice = "0"; }
		$edit_co_ref = mysqli_real_escape_string($GLOBALS['linki'], $edit_co_ref);
		$edit_co_source = mysqli_real_escape_string($GLOBALS['linki'], $edit_co_source);
		$edit_co_otherpattern = mysqli_real_escape_string($GLOBALS['linki'], $edit_co_otherpattern);
		$edit_co_writeref = mysqli_real_escape_string($GLOBALS['linki'], $edit_co_writeref);
		$edit_st_pattern = mysqli_real_escape_string($GLOBALS['linki'], $edit_st_pattern);
		$edit_st_sliceorder = mysqli_real_escape_string($GLOBALS['linki'], $edit_st_sliceorder);
		$edit_st_refslice = mysqli_real_escape_string($GLOBALS['linki'], $edit_st_refslice);
		$edit_st_ta = mysqli_real_escape_string($GLOBALS['linki'], $edit_st_ta);
		if ($edit_no_determineparams == "yes") { $edit_no_determineparams = "1"; } else { $edit_no_determineparams = "0"; }
		if ($edit_no_writenormalized == "yes") { $edit_no_writenormalized = "1"; } else { $edit_no_writenormalized = "0"; }
		$edit_no_paramtemplate = mysqli_real_escape_string($GLOBALS['linki'], $edit_no_paramtemplate);
		$edit_no_parampattern = mysqli_real_escape_string($GLOBALS['linki'], $edit_no_parampattern);
		$edit_no_paramsourceweight = mysqli_real_escape_string($GLOBALS['linki'], $edit_no_paramsourceweight);
		$edit_no_matname = mysqli_real_escape_string($GLOBALS['linki'], $edit_no_matname);
		$edit_no_writenormpattern = mysqli_real_escape_string($GLOBALS['linki'], $edit_no_writenormpattern);
		$edit_no_writenormmatname = mysqli_real_escape_string($GLOBALS['linki'], $edit_no_writenormmatname);
		$edit_sm_kernel = mysqli_real_escape_string($GLOBALS['linki'], $edit_sm_kernel);
		$edit_sm_pattern = mysqli_real_escape_string($GLOBALS['linki'], $edit_sm_pattern);
		$edit_ar_pattern = mysqli_real_escape_string($GLOBALS['linki'], $edit_ar_pattern);
		$edit_fi_pattern = mysqli_real_escape_string($GLOBALS['linki'], $edit_fi_pattern);
		$edit_se_pattern = mysqli_real_escape_string($GLOBALS['linki'], $edit_se_pattern);
		$edit_se_gmoutput = mysqli_real_escape_string($GLOBALS['linki'], $edit_se_gmoutput);
		$edit_se_wmoutput = mysqli_real_escape_string($GLOBALS['linki'], $edit_se_wmoutput);
		$edit_se_csfoutput = mysqli_real_escape_string($GLOBALS['linki'], $edit_se_csfoutput);

		$sqlstring  = "insert into task_preprocess_prefs (
		taskid, description, shortname, extralines, startdate, enddate, 
		do_dicomconvert, do_reorient, do_realign, do_msdcalc, do_coregister, do_slicetime, do_normalize, do_smooth, do_artrepair, do_filter, do_segment,
		dicom_filepattern, dicom_format, dicom_writefileprefix, dicom_outputdir,
		reorient_pattern, reorient_vector, reorient_write,
		realign_coregister, realign_reslice, realign_useinrialign, realign_pattern, realign_inri_rho, realign_inri_cutoff, realign_inri_quality, realign_fwhm, realign_tomean, realign_pathtoweight, realign_writeresliceimg, realign_writemean,
		coreg_run, coreg_runreslice, coreg_ref, coreg_source, coreg_otherpattern, coreg_writeref,
		slicetime_pattern, slicetime_sliceorder, slicetime_refslice, slicetime_ta, 
		norm_determineparams, norm_writeimages, norm_paramstemplate, norm_paramspattern, norm_paramssourceweight, norm_paramsmatname, norm_writepattern, norm_writematname,
		smooth_kernel, smooth_pattern,
		art_pattern,
		filter_pattern, filter_cuttofffreq,
		segment_pattern, segment_outputgm, segment_outputwm, segment_outputcsf, segment_outputbiascor, segment_outputcleanup
		)
		values (
		$taskid, '$edit_description', '$edit_shortname', '$edit_extralines', now(), '3000-01-01 00:00:00',
		$edit_do_dicomconvert, $edit_do_reorient, $edit_do_realign, $edit_do_msdcalc, $edit_do_coregister, $edit_do_slicetime, $edit_do_normalize, $edit_do_smooth, $edit_do_artrepair, $edit_do_filter, $edit_do_segment,
		'$edit_di_filepattern', '$edit_di_format', '$edit_di_writefileprefix', '$edit_di_outputdir',
		'$edit_ro_pattern', '$edit_ro_vector', $edit_ro_write,
		$edit_re_coregister, $edit_re_reslice, $edit_re_useinrialign, '$edit_re_realignpattern', '$edit_re_inrialignrho', '$edit_re_inrialigncutoff', '$edit_re_inrialignquality', '$edit_re_fwhm', '$edit_re_rtm', '$edit_re_pw', '$edit_re_writeimages', '$edit_re_writemean',
		$edit_co_run, $edit_co_runreslice, '$edit_co_ref', '$edit_co_source', '$edit_co_otherpattern', '$edit_co_writeref',
		'$edit_st_pattern', '$edit_st_sliceorder', '$edit_st_refslice', '$edit_st_ta',
		'$edit_no_determineparams', '$edit_no_writenormalized', '$edit_no_paramtemplate', '$edit_no_parampattern', '$edit_no_paramsourceweight', '$edit_no_matname', '$edit_no_writenormpattern', '$edit_no_writenormmatname',
		'$edit_sm_kernel', '$edit_sm_pattern', '$edit_ar_pattern',
		'$edit_fi_pattern', '$edit_fi_cutofffreq',
		'$edit_se_pattern', '$edit_se_gmoutput', '$edit_se_wmoutput', '$edit_se_csfoutput', $edit_se_biascor, $edit_se_cleanup)";
		//echo "$sqlstring<br>";
		MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$prefsid = mysqli_insert_id($GLOBALS['linki']);

		?><div class="message">Preprocessing Pref file added</div><br><?
		
		return $prefsid;
	}


	/* -------------------------------------------- */
	/* ------- DeletePrefs ------------------------ */
	/* -------------------------------------------- */
	function DeletePrefs($id) {
		$sqlstring = "update task_preprocess_prefs set enddate = now() where taskid = $id";
		MySQLiQuery($sqlstring, __FILE__, __LINE__);

		?><div class="message">File '<? echo $id ?>' deleted</div><br>
		<a href="tasks.php">Back to list of tasks</a>
		<?
	}


	/* -------------------------------------------- */
	/* ------- CopyToNew -------------------------- */
	/* -------------------------------------------- */
	function CopyToNew($taskid, $oldid) {
		/* get the preprocessing information */
		$sqlstring = "select * from task_preprocess_prefs where id = $oldid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		//$id = $row['id'];
		//$taskid = $row['taskid'];
		$description = mysqli_real_escape_string($GLOBALS['linki'], $row['description']);
		$shortname = "copy_of_" . mysqli_real_escape_string($GLOBALS['linki'], $row['shortname']);
		$extralines = mysqli_real_escape_string($GLOBALS['linki'], $row['extralines']);
		//$startdate = $row['startdate'];
		$do_dicomconvert = $row['do_dicomconvert'];
		$do_reorient = $row['do_reorient'];
		$do_realign = $row['do_realign'];
		$do_msdcalc = $row['do_msdcalc'];
		$do_coregister = $row['do_coregister'];
		$do_slicetime = $row['do_slicetime'];
		$do_normalize = $row['do_normalize'];
		$do_smooth = $row['do_smooth'];
		$do_artrepair = $row['do_artrepair'];
		$do_filter = $row['do_filter'];
		$do_segment = $row['do_segment'];
		$dicom_filepattern = $row['dicom_filepattern'];
		$dicom_format = mysqli_real_escape_string($GLOBALS['linki'], $row['dicom_format']);
		$dicom_writefileprefix = mysqli_real_escape_string($GLOBALS['linki'], $row['dicom_writefileprefix']);
		$dicom_outputdir = mysqli_real_escape_string($GLOBALS['linki'], $row['dicom_outputdir']);
		$reorient_pattern = mysqli_real_escape_string($GLOBALS['linki'], $row['reorient_pattern']);
		$reorient_vector = $row['reorient_vector'];
		$reorient_write = $row['reorient_write'];
		$realign_coregister = $row['realign_coregister'];
		$realign_reslice = $row['realign_reslice'];
		$realign_useinrialign = $row['realign_useinrialign'];
		$realign_pattern = mysqli_real_escape_string($GLOBALS['linki'], $row['realign_pattern']);
		$realign_inri_rho = $row['realign_inri_rho'];
		$realign_inri_cutoff = $row['realign_inri_cutoff'];
		$realign_inri_quality = $row['realign_inri_quality'];
		$realign_fwhm = $row['realign_fwhm'];
		$realign_tomean = $row['realign_tomean'];
		$realign_pathtoweight = mysqli_real_escape_string($GLOBALS['linki'], $row['realign_pathtoweight']);
		$realign_writeresliceimg = $row['realign_writeresliceimg'];
		$realign_writemean = $row['realign_writemean'];
		$coreg_run = $row['coreg_run'];
		$coreg_runreslice = $row['coreg_runreslice'];
		$coreg_ref = $row['coreg_ref'];
		$coreg_source = mysqli_real_escape_string($GLOBALS['linki'], $row['coreg_source']);
		$coreg_otherpattern = mysqli_real_escape_string($GLOBALS['linki'], $row['coreg_otherpattern']);
		$coreg_writeref = $row['coreg_writeref'];
		$slicetime_pattern = mysqli_real_escape_string($GLOBALS['linki'], $row['slicetime_pattern']);
		$slicetime_sliceorder = $row['slicetime_sliceorder'];
		$slicetime_refslice = $row['slicetime_refslice'];
		$slicetime_ta = $row['slicetime_ta'];
		$norm_determineparams = $row['norm_determineparams'];
		$norm_writeimages = $row['norm_writeimages'];
		$norm_paramstemplate = mysqli_real_escape_string($GLOBALS['linki'], $row['norm_paramstemplate']);
		$norm_paramspattern = mysqli_real_escape_string($GLOBALS['linki'], $row['norm_paramspattern']);
		$norm_paramssourceweight = $row['norm_paramssourceweight'];
		$norm_paramsmatname = mysqli_real_escape_string($GLOBALS['linki'], $row['norm_paramsmatname']);
		$norm_writepattern = $row['norm_writepattern'];
		$norm_writematname = $row['norm_writematname'];
		$smooth_kernel = $row['smooth_kernel'];
		$smooth_pattern = mysqli_real_escape_string($GLOBALS['linki'], $row['smooth_pattern']);
		$art_pattern = mysqli_real_escape_string($GLOBALS['linki'], $row['art_pattern']);
		$filter_pattern = mysqli_real_escape_string($GLOBALS['linki'], $row['filter_pattern']);
		$filter_cuttofffreq = $row['filter_cuttofffreq'];
		$segment_pattern = mysqli_real_escape_string($GLOBALS['linki'], $row['segment_pattern']);
		$segment_outputgm = mysqli_real_escape_string($GLOBALS['linki'], $row['segment_outputgm']);
		$segment_outputwm = mysqli_real_escape_string($GLOBALS['linki'], $row['segment_outputwm']);
		$segment_outputcsf = mysqli_real_escape_string($GLOBALS['linki'], $row['segment_outputcsf']);
		$segment_outputbiascor = mysqli_real_escape_string($GLOBALS['linki'], $row['segment_outputbiascor']);
		$segment_outputcleanup = mysqli_real_escape_string($GLOBALS['linki'], $row['segment_outputcleanup']);

		$sqlstring  = "insert into task_preprocess_prefs (
		taskid, description, shortname, extralines, startdate, enddate, 
		do_dicomconvert, do_reorient, do_realign, do_msdcalc, do_coregister, do_slicetime, do_normalize, do_smooth, do_artrepair, do_filter, do_segment,
		dicom_filepattern, dicom_format, dicom_writefileprefix, dicom_outputdir,
		reorient_pattern, reorient_vector, reorient_write,
		realign_coregister, realign_reslice, realign_useinrialign, realign_pattern, realign_inri_rho, realign_inri_cutoff, realign_inri_quality, realign_fwhm, realign_tomean, realign_pathtoweight, realign_writeresliceimg, realign_writemean,
		coreg_run, coreg_runreslice, coreg_ref, coreg_source, coreg_otherpattern, coreg_writeref,
		slicetime_pattern, slicetime_sliceorder, slicetime_refslice, slicetime_ta, 
		norm_determineparams, norm_writeimages, norm_paramstemplate, norm_paramspattern, norm_paramssourceweight, norm_paramsmatname, norm_writepattern, norm_writematname,
		smooth_kernel, smooth_pattern, art_pattern,
		filter_pattern, filter_cuttofffreq,
		segment_pattern, segment_outputgm, segment_outputwm, segment_outputcsf, segment_outputbiascor, segment_outputcleanup
		)
		values (
		$taskid, '$description', '$shortname', '$extralines', now(), '3000-01-01 00:00:00',
		'$do_dicomconvert', '$do_reorient', '$do_realign', '$do_msdcalc', '$do_coregister', '$do_slicetime', '$do_normalize', '$do_smooth', '$do_artrepair', '$do_filter', '$do_segment',
		'$dicom_filepattern', '$dicom_format', '$dicom_writefileprefix', '$dicom_outputdir',
		'$reorient_pattern', '$reorient_vector', $reorient_write,
		'$realign_coregister', '$realign_reslice', '$realign_useinrialign', '$realign_pattern', '$realign_inri_rho', '$realign_inri_cutoff', '$realign_inri_quality', '$realign_fwhm', '$realign_tomean', '$realign_pathtoweight', '$realign_writeresliceimg', '$realign_writemean',
		'$coreg_run', '$coreg_runreslice', '$coreg_ref', '$coreg_source', '$coreg_otherpattern', '$coreg_writeref',
		'$slicetime_pattern', '$slicetime_sliceorder', '$slicetime_refslice', '$slicetime_ta',
		'$norm_determineparams', '$norm_writeimages', '$norm_paramstemplate', '$norm_paramspattern', '$norm_paramssourceweight', '$norm_paramsmatname', '$norm_writepattern', '$norm_writematname',
		'$smooth_kernel', '$smooth_pattern', '$art_pattern',
		'$filter_pattern', '$filter_cuttofffreq',
		'$segment_pattern', '$segment_outputgm', '$segment_outputwm', '$segment_outputcsf', '$segment_outputbiascor', '$segment_outputcleanup')";
		
		//echo "$sqlstring<br>";
		MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$prefsid = mysqli_insert_id($GLOBALS['linki']);

		?><div class="message">Pre-processing Pref file added</div><br><?
		
		return $prefsid;
	}
	
	
	/* -------------------------------------------- */
	/* ------- AddPrefsForm ----------------------- */
	/* -------------------------------------------- */
	function AddPrefsForm($taskid) {
		$sqlstring = "select task_shortname, task_autocsver from tasks where taskid = $taskid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$taskname = $row['task_shortname'];
		$autocsver = $row['task_autocsver'];

		/* autocomplete tags */
		$dicom_filepattern_tags = GetDistinctDBField('dicom_filepattern', 'task_preprocess_prefs');
		$dicom_format_tags = GetDistinctDBField('dicom_format', 'task_preprocess_prefs');
		$dicom_writefileprefix_tags = GetDistinctDBField('dicom_writefileprefix', 'task_preprocess_prefs');
		$dicom_outputdir_tags = GetDistinctDBField('dicom_outputdir', 'task_preprocess_prefs');
		$reorient_pattern_tags = GetDistinctDBField('reorient_pattern', 'task_preprocess_prefs');
		$reorient_vector_tags = GetDistinctDBField('reorient_vector', 'task_preprocess_prefs');
		$realign_pattern_tags = GetDistinctDBField('realign_pattern', 'task_preprocess_prefs');
		$realign_inri_rho_tags = GetDistinctDBField('realign_inri_rho', 'task_preprocess_prefs');
		$realign_inri_cutoff_tags = GetDistinctDBField('realign_inri_cutoff', 'task_preprocess_prefs');
		$realign_inri_quality_tags = GetDistinctDBField('realign_inri_quality', 'task_preprocess_prefs');
		$realign_fwhm_tags = GetDistinctDBField('realign_fwhm', 'task_preprocess_prefs');
		$realign_pathtoweight_tags = GetDistinctDBField('realign_pathtoweight', 'task_preprocess_prefs');
		$coreg_ref_tags = GetDistinctDBField('coreg_ref', 'task_preprocess_prefs');
		$coreg_source_tags = GetDistinctDBField('coreg_source', 'task_preprocess_prefs');
		$coreg_otherpattern_tags = GetDistinctDBField('coreg_otherpattern', 'task_preprocess_prefs');
		$coreg_writeref_tags = GetDistinctDBField('coreg_writeref', 'task_preprocess_prefs');
		$slicetime_pattern_tags = GetDistinctDBField('slicetime_pattern', 'task_preprocess_prefs');
		$slicetime_sliceorder_tags = GetDistinctDBField('slicetime_sliceorder', 'task_preprocess_prefs');
		$slicetime_refslice_tags = GetDistinctDBField('slicetime_refslice', 'task_preprocess_prefs');
		$slicetime_ta_tags = GetDistinctDBField('slicetime_ta', 'task_preprocess_prefs');
		$norm_paramstemplate_tags = GetDistinctDBField('norm_paramstemplate', 'task_preprocess_prefs');
		$norm_paramspattern_tags = GetDistinctDBField('norm_paramspattern', 'task_preprocess_prefs');
		$norm_paramssourceweight_tags = GetDistinctDBField('norm_paramssourceweight', 'task_preprocess_prefs');
		$norm_paramsmatname_tags = GetDistinctDBField('norm_paramsmatname', 'task_preprocess_prefs');
		$norm_writepattern_tags = GetDistinctDBField('norm_writepattern', 'task_preprocess_prefs');
		$norm_writematname_tags = GetDistinctDBField('norm_writematname', 'task_preprocess_prefs');
		$smooth_kernel_tags = GetDistinctDBField('smooth_kernel', 'task_preprocess_prefs');
		$smooth_pattern_tags = GetDistinctDBField('smooth_pattern', 'task_preprocess_prefs');
		$art_pattern_tags = GetDistinctDBField('art_pattern', 'task_preprocess_prefs');
		$filter_pattern_tags = GetDistinctDBField('filter_pattern', 'task_preprocess_prefs');
		$filter_cuttofffreq_tags = GetDistinctDBField('filter_cuttofffreq', 'task_preprocess_prefs');
		$segment_pattern_tags = GetDistinctDBField('segment_pattern', 'task_preprocess_prefs');
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
		</style>

		<img src="images/back16.png"> <a href="autocs_preprocprefs.php?taskid=<? echo $taskid; ?>" class="link">Back</a> to list of preprocessing prefs<br><br>
		<form action="autocs_preprocprefs.php" method="get" id="form1">
		<input type="hidden" name="action" value="add">
		<input type="hidden" name="taskid" value="<? echo $taskid; ?>">

		<table cellspacing="0" cellpadding="4" id="tableone" width="100%" class="editor">
			<tr>
				<td colspan="2" style="border-bottom: 3px double #222222; text-align: center; font-weight: bold">Add New Pre-processing Preferences File for <span style="color: darkblue"><? echo $taskname; ?></span> &nbsp; <span class="spm<?=$autocsver?>">spm<?=$autocsver?></span></td>
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
					<? if (($autocsver == 5) || ($autocsver == 8)) { ?><input type="checkbox" name="edit_do_dicomconvert" value="yes" class="csprefsinput">DICOM Convert<br><? } ?>
					<? if (($autocsver == 5) || ($autocsver == 8)) { ?><input type="checkbox" name="edit_do_reorient" value="yes" class="csprefsinput">Reorient<br><? } ?>
					<input type="checkbox" name="edit_do_realign" value="yes" class="csprefsinput" checked>Realign<br>
					<? if (($autocsver == 5) || ($autocsver == 8)) { ?><input type="checkbox" name="edit_do_msdcalc" value="yes" class="csprefsinput">MSD Calculation <span class="sublabel">"Realignment->Write resliced images" MUST be set to 2 if this option is checked</span><br><? } ?>
					<? if (($autocsver == 5) || ($autocsver == 8)) { ?><input type="checkbox" name="edit_do_coregister" value="yes" class="csprefsinput">Coregister<br><? } ?>
					<? if (($autocsver == 5) || ($autocsver == 8)) { ?><input type="checkbox" name="edit_do_slicetime" value="yes" class="csprefsinput" checked>Slicetime correction<br><? } ?>
					<input type="checkbox" name="edit_do_normalize" value="yes" class="csprefsinput" checked>Normalize<br>
					<input type="checkbox" name="edit_do_smooth" value="yes" class="csprefsinput" checked>Smooth<br>
					<? if (($autocsver == 5) || ($autocsver == 8)) { ?><input type="checkbox" name="edit_do_artrepair" value="yes" class="csprefsinput">Art Repair<br><? } ?>
					<input type="checkbox" name="edit_do_filter" value="yes" class="csprefsinput" checked>Filter<br>
					<? if (($autocsver == 5) || ($autocsver == 8)) { ?><input type="checkbox" name="edit_do_segment" value="yes" class="csprefsinput">Segment<br><? } ?>
				</td>
			</tr>
			<tr><td colspan="2" style="font-size: 8pt">&nbsp;</td></tr>

			<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
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
			<? } ?>

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

		<p><input type="submit" value="Create" name="submit"></p>
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
	/* ------- DisplayPrefs ----------------------- */
	/* -------------------------------------------- */
	function DisplayPrefs($id, $viewtype) {
		/* get the preprocessing information */
		$sqlstring = "select * from task_preprocess_prefs where id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$id = $row['id'];
		$taskid = $row['taskid'];
		$description = $row['description'];
		$shortname = $row['shortname'];
		$extralines = $row['extralines'];
		$startdate = $row['startdate'];
		$do_dicomconvert = $row['do_dicomconvert'];
		$do_reorient = $row['do_reorient'];
		$do_realign = $row['do_realign'];
		$do_msdcalc = $row['do_msdcalc'];
		$do_coregister = $row['do_coregister'];
		$do_slicetime = $row['do_slicetime'];
		$do_normalize = $row['do_normalize'];
		$do_smooth = $row['do_smooth'];
		$do_artrepair = $row['do_artrepair'];
		$do_filter = $row['do_filter'];
		$do_segment = $row['do_segment'];
		$dicom_filepattern = $row['dicom_filepattern'];
		$dicom_format = $row['dicom_format'];
		$dicom_writefileprefix = $row['dicom_writefileprefix'];
		$dicom_outputdir = $row['dicom_outputdir'];
		$reorient_pattern = $row['reorient_pattern'];
		$reorient_vector = $row['reorient_vector'];
		$reorient_write = $row['reorient_write'];
		$realign_coregister = $row['realign_coregister'];
		$realign_reslice = $row['realign_reslice'];
		$realign_useinrialign = $row['realign_useinrialign'];
		$realign_pattern = $row['realign_pattern'];
		$realign_inri_rho = $row['realign_inri_rho'];
		$realign_inri_cutoff = $row['realign_inri_cutoff'];
		$realign_inri_quality = $row['realign_inri_quality'];
		$realign_fwhm = $row['realign_fwhm'];
		$realign_tomean = $row['realign_tomean'];
		$realign_pathtoweight = $row['realign_pathtoweight'];
		$realign_writeresliceimg = $row['realign_writeresliceimg'];
		$realign_writemean = $row['realign_writemean'];
		$coreg_run = $row['coreg_run'];
		$coreg_runreslice = $row['coreg_runreslice'];
		$coreg_ref = $row['coreg_ref'];
		$coreg_source = $row['coreg_source'];
		$coreg_otherpattern = $row['coreg_otherpattern'];
		$coreg_writeref = $row['coreg_writeref'];
		$slicetime_pattern = $row['slicetime_pattern'];
		$slicetime_sliceorder = $row['slicetime_sliceorder'];
		$slicetime_refslice = $row['slicetime_refslice'];
		$slicetime_ta = $row['slicetime_ta'];
		$norm_determineparams = $row['norm_determineparams'];
		$norm_writeimages = $row['norm_writeimages'];
		$norm_paramstemplate = $row['norm_paramstemplate'];
		$norm_paramspattern = $row['norm_paramspattern'];
		$norm_paramssourceweight = $row['norm_paramssourceweight'];
		$norm_paramsmatname = $row['norm_paramsmatname'];
		$norm_writepattern = $row['norm_writepattern'];
		$norm_writematname = $row['norm_writematname'];
		$smooth_kernel = $row['smooth_kernel'];
		$smooth_pattern = $row['smooth_pattern'];
		$art_pattern = $row['art_pattern'];
		$filter_pattern = $row['filter_pattern'];
		$filter_cuttofffreq = $row['filter_cuttofffreq'];
		$segment_pattern = $row['segment_pattern'];
		$segment_outputgm = $row['segment_outputgm'];
		$segment_outputwm = $row['segment_outputwm'];
		$segment_outputcsf = $row['segment_outputcsf'];
		$segment_outputbiascor = $row['segment_outputbiascor'];
		$segment_outputcleanup = $row['segment_outputcleanup'];

		/* get task related information */
		$sqlstring = "select a.*, b.datadirpath from tasks a, server_datadirs b where a.taskid = $taskid and a.task_datadirid = b.id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$autocsver = $row['task_autocsver'];
		$taskpath = $row['task_shortpath'];
		$taskname = $row['task_shortname'];
		$spm_defaults_dir = $row['task_spmdefaultsdir'];
		$scandirpattern = $row['task_scandirpattern'];
		$rundirpattern = $row['task_rundirpattern'];
		$scandirpostpend = $row['task_scandir_postpend'];
		$rundirpostpend = $row['task_rundir_postpend'];
		$fileuseregexp = $row['task_file_useregexp'];
		$numdummyscans = $row['task_numdummyscans'];
		$tr = $row['task_tr'];
		$datadirpath = $row['datadirpath'];

		if ($spm_defaults_dir == "") {
			if ($autocsver == 5) {
				$spm_defaults_dir = "/opt/center_scripts5/";
			}
			elseif ($autocsver == 8) {
				$spm_defaults_dir = "/opt/spm8/";
			}
			else {
				$spm_defaults_dir = "$datadirpath/$taskpath";
			}
		}
		
		?>
		<table><tr><td><img src="images/back16.png"></td><td><a href="autocs_preprocprefs.php?taskid=<? echo $taskid; ?>" class="link">Back</a> to summary</td></tr></table><br>
		<?

		if ($viewtype == "print") {
			?>
			<div style="border: 1pt gray dashed; padding: 5pt;">
			<pre>
<span style="color: red">function</span> cs_prefs_<? echo $taskname; ?>_<span id="shortname" class="edit_inline"><? echo $shortname; ?></span>
<span style="color: green">%<? echo $description; ?></span>.

<span style="color: red">global</span> csprefs;
<span style="color: red">global</span> defaults;
warning off;

<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
<span style="color: red">if</span> ~exist('<span style="color: salmon">initGraphics</span>', '<span style="color: salmon">var</span>')
    initGraphics = 1;
<span style="color: red">end</span>
<? } ?>

<span style="color: green">% PATH CHANGES, ETC</span>
<? echo $extralines; ?>


<span style="color: green">% PROCESSING STEPS TO RUN</span>
csprefs.run_beh_matchup         = 0;
<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
csprefs.run_dicom_convert       = <? echo $do_dicomconvert; ?>;
csprefs.run_reorient            = <? echo $do_reorient; ?>;
<? } ?>
csprefs.run_realign             = <? echo $do_realign; ?>;
<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
csprefs.run_msdcalc             = <? echo $do_msdcalc; ?>;
<? } ?>
<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
csprefs.run_coregister          = <? echo $do_coregister; ?>;
csprefs.run_slicetime           = <? echo $do_slicetime; ?>;
<? } ?>
csprefs.run_normalize           = <? echo $do_normalize; ?>;
csprefs.run_smooth              = <? echo $do_smooth; ?>;
<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
csprefs.run_artrepair           = <? echo $do_artrepair; ?>;
<? } ?>
csprefs.run_filter              = <? echo $do_filter; ?>;
csprefs.run_stats               = 0;
csprefs.run_autoslice           = 0;
csprefs.run_deriv_boost         = 0;
<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
csprefs.run_segment             = <? echo $do_segment; ?>;
<? } ?>
csprefs.sendmail                = 0;

<span style="color: green">% GENERAL SETTINGS... FOR ALL CENTERSCRIPTS FUNCTIONS</span>
csprefs.exp_dir                 = '<span style="color: salmon"><? echo "$datadirpath/$taskpath/"; ?></span>';
csprefs.logfile                 = '<span style="color: salmon"><? echo "$datadirpath/$taskpath"; ?>/cs_log_<? echo $taskname; ?>.txt</span>';
csprefs.errorlog                = '<span style="color: salmon"><? echo "$datadirpath/$taskpath"; ?>/cs_errorlog_<? echo $taskname; ?>.txt</span>';
csprefs.spm_defaults_dir        = '<span style="color: salmon"><? echo $spm_defaults_dir; ?></span>';
csprefs.scandir_regexp          = '<span style="color: salmon"><? echo $scandirpattern; ?></span>';
csprefs.rundir_regexp           = '<span style="color: salmon"><? echo $rundirpattern; ?></span>';
<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
csprefs.scandir_postpend        = '<span style="color: salmon"><? echo $scandirpostpend; ?></span>';
csprefs.rundir_postpend         = '<span style="color: salmon"><? echo $rundirpostpend; ?></span>';
csprefs.file_useregexp          = <? echo $fileuseregexp; ?>;
<? } ?>
csprefs.dummyscans              = <? echo $numdummyscans; ?>;
csprefs.tr                      = <? echo $tr; ?>;

<span style="color: green">% SETTINGS PERTAINING TO CS_REALIGN</span>
csprefs.coregister              = <? echo $realign_coregister; ?>;
csprefs.reslice                 = <? echo $realign_reslice; ?>;
csprefs.use_inrialign           = <? echo $realign_useinrialign; ?>;
csprefs.realign_pattern         = '<span style="color: salmon"><? echo $realign_pattern; ?></span>';
csprefs.inrialign_rho           = '<span style="color: salmon"><? echo $realign_inri_rho; ?></span>';
csprefs.inrialign_cutoff        = <? echo $realign_inri_cutoff; ?>;
csprefs.inrialign_quality       = <? echo $realign_inri_quality; ?>;
csprefs.realign_fwhm            = <? echo $realign_fwhm; ?>;
csprefs.realign_rtm             = <? echo $realign_tomean; ?>;
csprefs.realign_pw              = '<? echo $realign_pathtoweight; ?>';
csprefs.reslice_write_imgs      = <? echo $realign_writeresliceimg; ?>;
csprefs.reslice_write_mean      = <? echo $realign_writemean; ?>;

<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
<span style="color: green">% SETTINGS PERTAINING TO CS_DICOM_CONVERT</span>
csprefs.dicom.file_pattern      = '<span style="color: salmon"><? echo $dicom_filepattern; ?></span>';
csprefs.dicom.format            = '<span style="color: salmon"><? echo $dicom_format; ?></span>';
csprefs.dicom.write_file_prefix = '<span style="color: salmon"><? echo $dicom_writefileprefix; ?></span>';
csprefs.dicom.outputDir         = '<span style="color: salmon"><? echo $dicom_outputdir; ?></span>';

<span style="color: green">% SETTINGS PERTAINING TO CS_REORIENT</span>
csprefs.reorient_pattern        = '<span style="color: salmon"><? echo $reorient_pattern; ?></span>';
csprefs.reorient_vector         = [<? echo $reorient_vector; ?>]; 
csprefs.write_reorient          = <? echo $reorient_write; ?>;

<span style="color: green">% SETTINGS PERTAINING TO CS_COREGISTER</span>
csprefs.run_coreg               = <?=$coreg_run?>;
csprefs.run_reslice             = <?=$coreg_runreslice?>;
csprefs.coreg.ref               = '<span style="color: salmon"><?=$coreg_ref?></span>';
csprefs.coreg.source            = '<span style="color: salmon"><?=$coreg_source?></span>'; 
csprefs.coreg.other_pattern     = '<span style="color: salmon"><?$coreg_otherpattern?></span>';
csprefs.coreg.write.ref         = '<span style="color: salmon"><?=$coreg_writeref?></span>';

<span style="color: green">% SETTINGS PERTAINING TO CS_SLICETIME</span>
csprefs.slicetime_pattern       = '<span style="color: salmon"><?=$slicetime_pattern?></span>';
csprefs.sliceorder              = [<?=$slicetime_sliceorder?>];
csprefs.refslice                = <?=$slicetime_refslice?>;
csprefs.ta                      = '<span style="color: salmon"><?=$slicetime_ta?></span>';
<? } ?>

<span style="color: green">% SETTINGS PERTAINING TO CS_NORMALIZE</span>
csprefs.determine_params        = <? echo $norm_determineparams; ?>;
csprefs.write_normalized        = <? echo $norm_writeimages; ?>;
csprefs.params_template         = '<span style="color: salmon"><? echo $norm_paramstemplate; ?></span>';
csprefs.params_pattern          = '<span style="color: salmon"><? echo $norm_paramspattern; ?></span>';
csprefs.params_source_weight    = '<span style="color: salmon"><? echo $norm_paramssourceweight; ?></span>';
csprefs.params_matname          = '<span style="color: salmon"><? echo $norm_paramsmatname; ?></span>';
csprefs.writenorm_pattern       = '<span style="color: salmon"><? echo $norm_writepattern; ?></span>';
csprefs.writenorm_matname       = '<span style="color: salmon"><? echo $norm_writematname; ?></span>';

<span style="color: green">% SETTINGS PERTAINING TO CS_SMOOTH</span>
csprefs.smooth_kernel           = [<? echo $smooth_kernel; ?>];
csprefs.smooth_pattern          = '<span style="color: salmon"><? echo $smooth_pattern; ?></span>';

<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
<span style="color: green">% SETTINGS PERTAINING TO CS_ARTREPAIR</span>
csprefs.art_pattern          = '<span style="color: salmon"><? echo $art_pattern; ?></span>';
<? } ?>

<span style="color: green">% SETTINGS PERTAINING TO CS_FILTER</span>
csprefs.filter_pattern          = '<span style="color: salmon"><? echo $filter_pattern; ?></span>';
csprefs.cutoff_freq             = <? echo $filter_cuttofffreq; ?>;

<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
<span style="color: green">% SETTINGS PERTAINING TO CS_SEGMENTATION</span>
csprefs.segment.pattern         = '<span style="color: salmon"><?=$segment_pattern?></span>';
csprefs.segment.output.GM       = [<?=$segment_outputgm?>]; 
csprefs.segment.output.WM       = [<?=$segment_outputwm?>]; 
csprefs.segment.output.CSF      = [<?=$segment_outputcsf?>]; 
csprefs.segment.output.biascor  = <?=$segment_outputbiascor?>;
csprefs.segment.output.cleanup  = <?=$segment_outputcleanup?>;

<span style="color: red">if</span> initGraphics
    handles = spm('<span style="color: salmon">CreateIntWin</span>'); <span style="color: green">%for progress bars</span>
    set(handles, '<span style="color: salmon">visible</span>', '<span style="color: salmon">on</span>');
    spm_figure('<span style="color: salmon">Create</span>','<span style="color: salmon">Graphics</span>','<span style="color: salmon">Graphics</span>','<span style="color: salmon">on</span>');
    pause(1);
<span style="color: red">end</span>
addpath(csprefs.spm_defaults_dir);
spm_defaults;

<? } else { ?>

<span style="color: green">%for progress bars</span>
spm('<span style="color: salmon">CreateIntWin</span>');
spm_figure('<span style="color: salmon">Create</span>','<span style="color: salmon">Graphics</span>','<span style="color: salmon">Graphics</span>','<span style="color: salmon">on</span>');
pause(1);
addpath(csprefs.spm_defaults_dir);
spm_defaults;
<? } ?>
			</pre>
			</div>
			<?
		}
		else {
			?>

		<script type="text/javascript">
			$(document).ready(function(){
				$(".edit_inline").editInPlace({
					url: "autocs_preprocprefs_inlineupdate.php",
					params: "action=editinplace&id=<? echo $id; ?>",
					bg_over: "lightblue",
					bg_out: "lightyellow",
					//textarea_rows: "15",
					//textarea_cols: "35",
				});
				$(".edit_textarea").editInPlace({
					url: "autocs_preprocprefs_inlineupdate.php",
					params: "action=editinplace&id=<? echo $id; ?>",
					field_type: "textarea",
					bg_over: "lightblue",
					bg_out: "lightyellow",
					textarea_rows: "10",
					textarea_cols: "50",
				});
			});
		</script>
		<style type="text/css">
            .edit_inline { background-color: lightyellow; padding-left: 2pt; padding-right: 2pt; }
            .edit_textarea { background-color: lightyellow; }
		</style>

		<div align="center">
		<table cellpadding="10" cellspacing="0">
			<tr>
				<td style="background-color: ivory; border-left: 1pt solid gray; border-top: 1pt solid gray;">
					<span style="font-size: 14pt; font-weight: bold">Preprocessing for <? echo $taskname; ?></span> <br>
					<span style="font-size:10pt">You can edit <span style="background-color: lightyellow">highlighted</span> values in place by clicking and editing them. <span style="color: #AAAAAA">Grayed</span> values are not editable.</span>
				</td>
				<td align="right" valign="top" style="background-color: ivory; border-top: 1pt solid gray; border-right: 1pt solid gray"><a href="autocs_preprocprefs.php?action=display&viewtype=print&id=<? echo $id; ?>" style="color:blue; font-size: 10pt">Print preview</a></td>
			</tr>
			<tr>
				<td style="border: 1pt dashed gray" colspan="2">
					<span style="white-space: pre; font-family: courier new; font-size: 9pt;">
<span style="color: red">function</span> cs_prefs_<? echo $taskname; ?>_<span id="shortname" class="edit_inline"><? echo $shortname; ?></span>

<span style="color: green">%<span id="description" class="edit_inline"><? echo $description; ?></span></span>.

<span style="color: red">global</span> csprefs;
<span style="color: red">global</span> defaults;
warning off;

<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
<span style="color: red">if</span> ~exist('<span style="color: salmon">initGraphics</span>', '<span style="color: salmon">var</span>')
    initGraphics = 1;
<span style="color: red">end</span>
<? } ?>

<span style="color: green">% PATH CHANGES, ETC</span>
<div id="extralines" class="edit_textarea"><? echo $extralines; ?></div>
<br>
<span style="color: green">% PROCESSING STEPS TO RUN</span>
<span style="color: #AAAAAA">csprefs.run_beh_matchup         = 0;</span>
<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
csprefs.run_dicom_convert       = <span id="do_dicomconvert" class="edit_inline"><? echo $do_dicomconvert; ?></span>;
csprefs.run_reorient            = <span id="do_reorient" class="edit_inline"><? echo $do_reorient; ?></span>;
<? } ?>
csprefs.run_realign             = <span id="do_realign" class="edit_inline"><? echo $do_realign; ?></span>;
<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
csprefs.run_msdcalc             = <span id="do_msdcalc" class="edit_inline"><? echo $do_msdcalc; ?></span>;
<? } ?>
<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
csprefs.run_coregister          = <span id="do_coregister" class="edit_inline"><? echo $do_coregister; ?></span>;
csprefs.run_slicetime           = <span id="do_slicetime" class="edit_inline"><? echo $do_slicetime; ?></span>;
<? } ?>
csprefs.run_normalize           = <span id="do_normalize" class="edit_inline"><? echo $do_normalize; ?></span>;
csprefs.run_smooth              = <span id="do_smooth" class="edit_inline"><? echo $do_smooth; ?></span>;
<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
csprefs.run_artrepair           = <span id="do_artrepair" class="edit_inline"><? echo $do_artrepair; ?></span>;
<? } ?>
csprefs.run_filter              = <span id="do_filter" class="edit_inline"><? echo $do_filter; ?></span>;
<span style="color: #AAAAAA">csprefs.run_stats               = 0;</span>
<span style="color: #AAAAAA">csprefs.run_autoslice           = 0;</span>
<span style="color: #AAAAAA">csprefs.run_deriv_boost         = 0;</span>
<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
csprefs.run_segment             = <span id="do_segment" class="edit_inline"><? echo $do_segment; ?></span>;
<? } ?>
<span style="color: #AAAAAA">csprefs.sendmail                = 0;</span>
<br>

<span style="color: green">% GENERAL SETTINGS... FOR ALL CENTERSCRIPTS FUNCTIONS</span>
<span style="color: #AAAAAA">csprefs.exp_dir                 = '<span style="color: salmon"><? echo "$datadirpath/$taskpath/"; ?></span>';
csprefs.logfile                 = '<span style="color: salmon"><? echo "$datadirpath/$taskpath"; ?>/cs_log_<? echo $taskname; ?>.txt</span>';
csprefs.errorlog                = '<span style="color: salmon"><? echo "$datadirpath/$taskpath"; ?>/cs_errorlog_<? echo $taskname; ?>.txt</span>';
csprefs.spm_defaults_dir        = '<span style="color: salmon"><? echo $spm_defaults_dir; ?></span>';
csprefs.scandir_regexp          = '<span style="color: salmon"><? echo $scandirpattern; ?></span>';
csprefs.rundir_regexp           = '<span style="color: salmon"><? echo $rundirpattern; ?></span>';
<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
csprefs.scandir_postpend        = '<span style="color: salmon"><? echo $scandirpostpend; ?></span>';
csprefs.rundir_postpend         = '<span style="color: salmon"><? echo $rundirpostpend; ?></span>';
csprefs.file_useregexp          = <? echo $fileuseregexp; ?>;
<? } ?>
csprefs.dummyscans              = <? echo $numdummyscans; ?>;
csprefs.tr                      = <? echo $tr; ?>;</span>
<br>

<span style="color: green">% SETTINGS PERTAINING TO CS_REALIGN</span>
csprefs.coregister              = <span id="realign_coregister" class="edit_inline"><? echo $realign_coregister; ?></span>;
csprefs.reslice                 = <span id="realign_reslice" class="edit_inline"><? echo $realign_reslice; ?></span>;
csprefs.use_inrialign           = <span id="realign_useinrialign" class="edit_inline"><? echo $realign_useinrialign; ?></span>;
csprefs.realign_pattern         = '<span id="realign_pattern" class="edit_inline"><span style="color: salmon"><? echo $realign_pattern; ?></span></span>';
csprefs.inrialign_rho           = '<span id="realign_inri_rho" class="edit_inline"><span style="color: salmon"><? echo $realign_inri_rho; ?></span></span>';
csprefs.inrialign_cutoff        = <span id="realign_inri_cutoff" class="edit_inline"><? echo $realign_inri_cutoff; ?></span>;
csprefs.inrialign_quality       = <span id="realign_inri_quality" class="edit_inline"><? echo $realign_inri_quality; ?></span>;
csprefs.realign_fwhm            = <span id="realign_fwhm" class="edit_inline"><? echo $realign_fwhm; ?></span>;
csprefs.realign_rtm             = <span id="realign_tomean" class="edit_inline"><? echo $realign_tomean; ?></span>;
csprefs.realign_pw              = '<span id="realign_pathtoweight" class="edit_inline"><? echo $realign_pathtoweight; ?> </span>';
csprefs.reslice_write_imgs      = <span id="realign_writeresliceimg" class="edit_inline"><? echo $realign_writeresliceimg; ?></span>;
csprefs.reslice_write_mean      = <span id="realign_writemean" class="edit_inline"><? echo $realign_writemean; ?></span>;

<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
<span style="color: green">% SETTINGS PERTAINING TO CS_DICOM_CONVERT</span>
csprefs.dicom.file_pattern      = '<span id="dicom_filepattern" class="edit_inline"><span style="color: salmon"><? echo $dicom_filepattern; ?></span></span>';
csprefs.dicom.format            = '<span id="dicom_format" class="edit_inline"><span style="color: salmon"><? echo $dicom_format; ?></span></span>';
csprefs.dicom.write_file_prefix = '<span id="dicom_writefileprefix" class="edit_inline"><span style="color: salmon"><? echo $dicom_writefileprefix; ?></span></span>';
csprefs.dicom.outputDir         = '<span id="dicom_outputdir" class="edit_inline"><span style="color: salmon"><? echo $dicom_outputdir; ?></span></span>';

<span style="color: green">% SETTINGS PERTAINING TO CS_REORIENT</span>
csprefs.reorient_pattern        = '<span id="reorient_pattern" class="edit_inline"><span style="color: salmon"><? echo $reorient_pattern; ?></span></span>';
csprefs.reorient_vector         = [<span id="reorient_vector" class="edit_inline"><? echo $reorient_vector; ?></span>]; 
csprefs.write_reorient          = <span id="reorient_write" class="edit_inline"><? echo $reorient_write; ?></span>;

<span style="color: green">% SETTINGS PERTAINING TO CS_COREGISTER</span>
csprefs.run_coreg               = <span id="coreg_run" class="edit_inline"><?=$coreg_run?></span>;
csprefs.run_reslice             = <span id="coreg_runreslice" class="edit_inline"><?=$coreg_runreslice?></span>;
csprefs.coreg.ref               = '<span id="coreg_ref" class="edit_inline"><span style="color: salmon"><?=$coreg_ref?></span></span>';
csprefs.coreg.source            = '<span id="coreg_source" class="edit_inline"><span style="color: salmon"><?=$coreg_source?></span></span>'; 
csprefs.coreg.other_pattern     = '<span id="coreg_otherpattern" class="edit_inline"><span style="color: salmon"><?$coreg_otherpattern?></span></span>';
csprefs.coreg.write.ref         = '<span id="coreg_writeref" class="edit_inline"><span style="color: salmon"><?=$coreg_writeref?></span></span>';

<span style="color: green">% SETTINGS PERTAINING TO CS_SLICETIME</span>
csprefs.slicetime_pattern       = '<span id="slicetime_pattern" class="edit_inline"><span style="color: salmon"><?=$slicetime_pattern?></span></span>';
csprefs.sliceorder              = [<span id="slicetime_sliceorder" class="edit_inline"><?=$slicetime_sliceorder?></span>];
csprefs.refslice                = <span id="slicetime_refslice" class="edit_inline"><?=$slicetime_refslice?></span>;
csprefs.ta                      = '<span id="slicetime_ta" class="edit_inline"><span style="color: salmon"><?=$slicetime_ta?></span></span>';
<? } ?>

<span style="color: green">% SETTINGS PERTAINING TO CS_NORMALIZE</span>
csprefs.determine_params        = <span id="norm_determineparams" class="edit_inline"><? echo $norm_determineparams; ?></span>;
csprefs.write_normalized        = <span id="norm_writeimages" class="edit_inline"><? echo $norm_writeimages; ?></span>;
csprefs.params_template         = '<span id="norm_paramstemplate" class="edit_inline"><span style="color: salmon"><? echo $norm_paramstemplate; ?></span></span>';
csprefs.params_pattern          = '<span id="norm_paramspattern" class="edit_inline"><span style="color: salmon"><? echo $norm_paramspattern; ?></span></span>';
csprefs.params_source_weight    = '<span id="norm_paramssourceweight" class="edit_inline"><span style="color: salmon"><? echo $norm_paramssourceweight; ?></span></span>';
csprefs.params_matname          = '<span id="norm_paramsmatname" class="edit_inline"><span style="color: salmon"><? echo $norm_paramsmatname; ?></span></span>';
csprefs.writenorm_pattern       = '<span id="norm_writepattern" class="edit_inline"><span style="color: salmon"><? echo $norm_writepattern; ?></span></span>';
csprefs.writenorm_matname       = '<span id="norm_writematname" class="edit_inline"><span style="color: salmon"><? echo $norm_writematname; ?></span></span>';

<span style="color: green">% SETTINGS PERTAINING TO CS_SMOOTH</span>
csprefs.smooth_kernel           = [<span id="smooth_kernel" class="edit_inline"><? echo $smooth_kernel; ?></span>];
csprefs.smooth_pattern          = '<span id="smooth_pattern" class="edit_inline"><span style="color: salmon"><? echo $smooth_pattern; ?></span></span>';

<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
<span style="color: green">% SETTINGS PERTAINING TO CS_ARTREPAIR</span>
csprefs.art_pattern          = '<span id="art_pattern" class="edit_inline"><span style="color: salmon"><? echo $art_pattern; ?></span></span>';
<? } ?>

<span style="color: green">% SETTINGS PERTAINING TO CS_FILTER</span>
csprefs.filter_pattern          = '<span id="filter_pattern" class="edit_inline"><span style="color: salmon"><? echo $filter_pattern; ?></span></span>';
csprefs.cutoff_freq             = <span id="filter_cuttofffreq" class="edit_inline"><? echo $filter_cuttofffreq; ?></span>;

<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
<span style="color: green">% SETTINGS PERTAINING TO CS_SEGMENTATION</span>
csprefs.segment.pattern         = '<span id="segment_pattern" class="edit_inline"><span style="color: salmon"><?=$segment_pattern?></span></span>';
csprefs.segment.output.GM       = [<span id="segment_outputgm" class="edit_inline"><?=$segment_outputgm?></span>]; 
csprefs.segment.output.WM       = [<span id="segment_outputwm" class="edit_inline"><?=$segment_outputwm?></span>]; 
csprefs.segment.output.CSF      = [<span id="segment_outputcsf" class="edit_inline"><?=$segment_outputcsf?></span>]; 
csprefs.segment.output.biascor  = <span id="segment_outputbiascor" class="edit_inline"><?=$segment_outputbiascor?></span>;
csprefs.segment.output.cleanup  = <span id="segment_outputcleanup" class="edit_inline"><?=$segment_outputcleanup?></span>;

<span style="color: red">if</span> initGraphics
    handles = spm('<span style="color: salmon">CreateIntWin</span>'); <span style="color: green">%for progress bars</span>
    set(handles, '<span style="color: salmon">visible</span>', '<span style="color: salmon">on</span>');
    spm_figure('<span style="color: salmon">Create</span>','<span style="color: salmon">Graphics</span>','<span style="color: salmon">Graphics</span>','<span style="color: salmon">on</span>');
    pause(1);
<span style="color: red">end</span>
addpath(csprefs.spm_defaults_dir);
spm_defaults;

<? } else { ?>

<span style="color: green">%for progress bars</span>
spm('<span style="color: salmon">CreateIntWin</span>');
spm_figure('<span style="color: salmon">Create</span>','<span style="color: salmon">Graphics</span>','<span style="color: salmon">Graphics</span>','<span style="color: salmon">on</span>');
pause(1);
addpath(csprefs.spm_defaults_dir);
spm_defaults;
<? } ?>

					</span>
				</td>
			</tr>
		</table>
		</div>
		<?
		}
	}


	/* -------------------------------------------- */
	/* ------- DisplaySummary --------------------- */
	/* -------------------------------------------- */
	function DisplaySummary($taskid) {
		$sqlstring = "SELECT task_shortname FROM tasks WHERE taskid = $taskid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$taskname = $row['task_shortname'];
		?>
		<table><tr><td><img src="images/back16.png"></td><td><a href="tasks.php?action=viewtask&taskid=<? echo $taskid; ?>" class="link">Back</a> to <? echo $taskname; ?></td></tr></table><br>

		<br>
		<table>
			<tr>
				<td>
					<img src="images/add16.png"> <a href="autocs_preprocprefs.php?action=addform&taskid=<? echo $taskid; ?>" class="link">Add New</a>
				</td>
			</tr>
			<tr>
				<td>
					<form method="post" action="autocs_preprocprefs.php">
					<input type="hidden" name="action" value="copytonew">
					<input type="hidden" name="taskid" value="<?=$taskid?>">
						<img src="images/copy16.png">
						<select name="oldprefid">
						<?
							$sqlstring = "SELECT a.shortname, b.task_shortname, a.id, b.task_autocsver FROM task_preprocess_prefs a left join tasks b on a.taskid = b.taskid WHERE a.enddate > now() and b.task_enddate > now() order by b.task_shortname";
							$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$taskshortname = $row['task_shortname'];
								$prefsshortname = $row['shortname'];
								$id = $row['id'];
								$autocsver = $row['task_autocsver'];
								?>
								<option value="<?=$id?>" class="spm<?=$autocsver?>light"><?=$taskshortname?> - <?=$prefsshortname?> [spm<?=$autocsver?>]</option>
								<?
							}
						?>
						</select>
						<input type="submit" value="Copy from existing">
					</form>
				</td>
			</tr>
		</table>
		<br>
		
		<!-- display existing preprocessing preferences -->
		<table cellspacing="0" cellpadding="3" width="100%">
			<tr>
				<td colspan="4" style="border-top: 2pt solid darkblue; text-align: left; font-weight: bold; background-color: lightyellow">Pre-processing preferences</td>
			</tr>
			<tr>
				<td class="columnheaderleft">Name</td>
				<td class="columnheader">Description</td>
				<td class="columnheader">Create date</td>
				<td class="columnheaderright">Delete</td>
			</tr>
			<?
				$sqlstring = "select * from task_preprocess_prefs where taskid = $taskid and enddate > now()";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$id = $row['id'];
						$createdate = $row['startdate'];
						$shortname = $row['shortname'];
						$description = $row['description'];
						?>
						<tr>
							<td><a href="autocs_preprocprefs.php?action=display&id=<? echo $id; ?>">cs_prefs_<? echo $taskname; ?>_<? echo $shortname; ?></a></td>
							<td><? echo $description; ?></td>
							<td><? echo $createdate; ?></td>
							<td align="center"><a href="autocs_preprocprefs.php?action=delete&id=<? echo $id; ?>" class="link" style="color: red">X</a></td>
						</tr>
						<?
					}
				}
				else {
					?>
					<tr>
						<td colspan="3" style="text-align: center">No pre-processing preferences available</td>
					</tr>
					<?
				}
			?>
		</table>
		<?
	}

?>

<? include("footer.php") ?>
