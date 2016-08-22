<?
	/* ----- setup variables ----- */
	if ($_POST["action"] == "") { $action = $_GET["action"]; } else { $action = $_POST["action"]; }
	if ($_POST["id"] == "") { $id = $_GET["id"]; } else { $id = $_POST["id"]; }
	if ($_POST["oldprefid"] == "") { $oldprefid = $_GET["oldprefid"]; } else { $oldprefid = $_POST["oldprefid"]; }
	if ($_POST["taskid"] == "") { $taskid = $_GET["taskid"]; } else { $taskid = $_POST["taskid"]; }
	if ($_POST["autocsver"] == "") { $autocsver = $_GET["autocsver"]; } else { $autocsver = $_POST["autocsver"]; }

	if ($_POST["viewtype"] == "") { $viewtype = $_GET["viewtype"]; } else { $viewtype = $_POST["viewtype"]; }

	/* edit variables */
	if ($_POST["edit_description"] == "") { $edit_description = $_GET["edit_description"]; } else { $edit_description = $_POST["edit_description"]; }
	if ($_POST["edit_shortname"] == "") { $edit_shortname = $_GET["edit_shortname"]; } else { $edit_shortname = $_POST["edit_shortname"]; }
	if ($_POST["edit_extralines"] == "") { $edit_extralines = $_GET["edit_extralines"]; } else { $edit_extralines = $_POST["edit_extralines"]; }
	
	if ($_POST["edit_do_behmatchup"] == "") { $edit_do_behmatchup = $_GET["edit_do_behmatchup"]; } else { $edit_do_behmatchup = $_POST["edit_do_behmatchup"]; }
	if ($_POST["edit_do_stats"] == "") { $edit_do_stats = $_GET["edit_do_stats"]; } else { $edit_do_stats = $_POST["edit_do_stats"]; }
	if ($_POST["edit_do_censor"] == "") { $edit_do_censor = $_GET["edit_do_censor"]; } else { $edit_do_censor = $_POST["edit_do_censor"]; }
	if ($_POST["edit_do_autoslice"] == "") { $edit_do_autoslice = $_GET["edit_do_autoslice"]; } else { $edit_do_autoslice = $_POST["edit_do_autoslice"]; }
	if ($_POST["edit_do_db"] == "") { $edit_do_db = $_GET["edit_do_db"]; } else { $edit_do_db = $_POST["edit_do_db"]; }
	
	if ($_POST["edit_beh_queue"] == "") { $edit_beh_queue = $_GET["edit_beh_queue"]; } else { $edit_beh_queue = $_POST["edit_beh_queue"]; }
	if ($_POST["edit_beh_digits"] == "") { $edit_beh_digits = $_GET["edit_beh_digits"]; } else { $edit_beh_digits = $_POST["edit_beh_digits"]; }
	if ($_POST["edit_stat_makeasciis"] == "") { $edit_stat_makeasciis = $_GET["edit_stat_makeasciis"]; } else { $edit_stat_makeasciis = $_POST["edit_stat_makeasciis"]; }
	if ($_POST["edit_stat_asciiscript"] == "") { $edit_stat_asciiscript = $_GET["edit_stat_asciiscript"]; } else { $edit_stat_asciiscript = $_POST["edit_stat_asciiscript"]; }

	if ($_POST["edit_stat_behdirname"] == "") { $edit_stat_behdirname = $_GET["edit_stat_behdirname"]; } else { $edit_stat_behdirname = $_POST["edit_stat_behdirname"]; }
	if ($_POST["edit_stat_relativepath"] == "") { $edit_stat_relativepath = $_GET["edit_stat_relativepath"]; } else { $edit_stat_relativepath = $_POST["edit_stat_relativepath"]; }

	if ($_POST["edit_stat_dirname"] == "") { $edit_stat_dirname = $_GET["edit_stat_dirname"]; } else { $edit_stat_dirname = $_POST["edit_stat_dirname"]; }
	if ($_POST["edit_stat_pattern"] == "") { $edit_stat_pattern = $_GET["edit_stat_pattern"]; } else { $edit_stat_pattern = $_POST["edit_stat_pattern"]; }
	if ($_POST["edit_stat_behunits"] == "") { $edit_stat_behunits = $_GET["edit_stat_behunits"]; } else { $edit_stat_behunits = $_POST["edit_stat_behunits"]; }
	if ($_POST["edit_stats_basisfunction"] == "") { $edit_stats_basisfunction = $_GET["edit_stats_basisfunction"]; } else { $edit_stats_basisfunction = $_POST["edit_stats_basisfunction"]; }
	if ($_POST["edit_stat_onsetfiles"] == "") { $edit_stat_onsetfiles = $_GET["edit_stat_onsetfiles"]; } else { $edit_stat_onsetfiles = $_POST["edit_stat_onsetfiles"]; }
	if ($_POST["edit_stat_durationfiles"] == "") { $edit_stat_durationfiles = $_GET["edit_stat_durationfiles"]; } else { $edit_stat_durationfiles = $_POST["edit_stat_durationfiles"]; }
	if ($_POST["edit_stat_regressorfiles"] == "") { $edit_stat_regressorfiles = $_GET["edit_stat_regressorfiles"]; } else { $edit_stat_regressorfiles = $_POST["edit_stat_regressorfiles"]; }
	if ($_POST["edit_stat_regressornames"] == "") { $edit_stat_regressornames = $_GET["edit_stat_regressornames"]; } else { $edit_stat_regressornames = $_POST["edit_stat_regressornames"]; }
	if ($_POST["edit_stat_parameternames"] == "") { $edit_stat_parameternames = $_GET["edit_stat_parameternames"]; } else { $edit_stat_parameternames = $_POST["edit_stat_parameternames"]; }
	if ($_POST["edit_stat_parameterorders"] == "") { $edit_stat_parameterorders = $_GET["edit_stat_parameterorders"]; } else { $edit_stat_parameterorders = $_POST["edit_stat_parameterorders"]; }
	if ($_POST["edit_stat_parameterfiles"] == "") { $edit_stat_parameterfiles = $_GET["edit_stat_parameterfiles"]; } else { $edit_stat_parameterfiles = $_POST["edit_stat_parameterfiles"]; }
	if ($_POST["edit_stat_censorfiles"] == "") { $edit_stat_censorfiles = $_GET["edit_stat_censorfiles"]; } else { $edit_stat_censorfiles = $_POST["edit_stat_censorfiles"]; }
	if ($_POST["edit_stat_contrastmatrix"] == "") { $edit_stat_contrastmatrix = $_GET["edit_stat_contrastmatrix"]; } else { $edit_stat_contrastmatrix = $_POST["edit_stat_contrastmatrix"]; }
	if ($_POST["edit_stat_highpasscutoff"] == "") { $edit_stat_highpasscutoff = $_GET["edit_stat_highpasscutoff"]; } else { $edit_stat_highpasscutoff = $_POST["edit_stat_highpasscutoff"]; }
	if ($_POST["edit_stat_serialcorr"] == "") { $edit_stat_serialcorr = $_GET["edit_stat_serialcorr"]; } else { $edit_stat_serialcorr = $_POST["edit_stat_serialcorr"]; }

	if ($_POST["edit_stat_xbflength"] == "") { $edit_stat_xbflength = $_GET["edit_stat_xbflength"]; } else { $edit_stat_xbflength = $_POST["edit_stat_xbflength"]; }
	if ($_POST["edit_stat_xbforder"] == "") { $edit_stat_xbforder = $_GET["edit_stat_xbforder"]; } else { $edit_stat_xbforder = $_POST["edit_stat_xbforder"]; }
	if ($_POST["edit_stat_timemodulation"] == "") { $edit_stat_timemodulation = $_GET["edit_stat_timemodulation"]; } else { $edit_stat_timemodulation = $_POST["edit_stat_timemodulation"]; }
	if ($_POST["edit_stat_parametricmodulation"] == "") { $edit_stat_parametricmodulation = $_GET["edit_stat_parametricmodulation"]; } else { $edit_stat_parametricmodulation = $_POST["edit_stat_parametricmodulation"]; }
	if ($_POST["edit_stat_globalfx"] == "") { $edit_stat_globalfx = $_GET["edit_stat_globalfx"]; } else { $edit_stat_globalfx = $_POST["edit_stat_globalfx"]; }
	if ($_POST["edit_stat_autoslicecons"] == "") { $edit_stat_autoslicecons = $_GET["edit_stat_autoslicecons"]; } else { $edit_stat_autoslicecons = $_POST["edit_stat_autoslicecons"]; }
	if ($_POST["edit_stat_autoslicep"] == "") { $edit_stat_autoslicep = $_GET["edit_stat_autoslicep"]; } else { $edit_stat_autoslicep = $_POST["edit_stat_autoslicep"]; }
	if ($_POST["edit_stat_autoslicebackground"] == "") { $edit_stat_autoslicebackground = $_GET["edit_stat_autoslicebackground"]; } else { $edit_stat_autoslicebackground = $_POST["edit_stat_autoslicebackground"]; }
	if ($_POST["edit_stat_autosliceslices"] == "") { $edit_stat_autosliceslices = $_GET["edit_stat_autosliceslices"]; } else { $edit_stat_autosliceslices = $_POST["edit_stat_autosliceslices"]; }
	if ($_POST["edit_stat_autosliceemailcons"] == "") { $edit_stat_autosliceemailcons = $_GET["edit_stat_autosliceemailcons"]; } else { $edit_stat_autosliceemailcons = $_POST["edit_stat_autosliceemailcons"]; }

	if ($_POST["edit_db_fileprefix"] == "") { $edit_db_fileprefix = $_GET["edit_db_fileprefix"]; } else { $edit_db_fileprefix = $_POST["edit_db_fileprefix"]; }
	if ($_POST["edit_db_betanums"] == "") { $edit_db_betanums = $_GET["edit_db_betanums"]; } else { $edit_db_betanums = $_POST["edit_db_betanums"]; }
	if ($_POST["edit_db_threshold"] == "") { $edit_db_threshold = $_GET["edit_db_threshold"]; } else { $edit_db_threshold = $_POST["edit_db_threshold"]; }
	if ($_POST["edit_db_smoothkernel"] == "") { $edit_db_smoothkernel = $_GET["edit_db_smoothkernel"]; } else { $edit_db_smoothkernel = $_POST["edit_db_smoothkernel"]; }
	if ($_POST["edit_db_imcalcs"] == "") { $edit_db_imcalcs = $_GET["edit_db_imcalcs"]; } else { $edit_db_imcalcs = $_POST["edit_db_imcalcs"]; }
	if ($_POST["edit_db_imnames"] == "") { $edit_db_imnames = $_GET["edit_db_imnames"]; } else { $edit_db_imnames = $_POST["edit_db_imnames"]; }

	//echo "<pre>";
	//print_r($_POST);
	//echo "</pre>";
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>AutoCS <? echo $autocsver; ?> - Stats</title>
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
		$id = AddPrefs($taskid, $edit_description, $edit_shortname, $edit_extralines, $edit_do_behmatchup, $edit_do_stats, $edit_do_censor, $edit_do_autoslice, $edit_do_db, $edit_beh_queue, $edit_beh_digits, $edit_stat_makeasciis, $edit_stat_asciiscript, $edit_stat_behdirname, $edit_stat_relativepath, $edit_stat_dirname, $edit_stat_pattern, $edit_stat_behunits, $edit_stat_volterra, $edit_stats_basisfunction, $edit_stat_onsetfiles, $edit_stat_durationfiles, $edit_stat_regressorfiles, $edit_stat_regressornames, $edit_stat_parameternames, $edit_stat_parameterorders, $edit_stat_parameterfiles, $edit_stat_censorfiles, $edit_stat_contrastmatrix, $edit_stat_xbflength, $edit_stat_xbforder, $edit_stat_timemodulation, $edit_stat_parametricmodulation, $edit_stat_globalfx, $edit_stat_highpasscutoff, $edit_stat_serialcorr, $edit_stat_autoslicecons, $edit_stat_autoslicep, $edit_stat_autoslicebackground, $edit_stat_autosliceslices, $edit_stat_autosliceemailcons, $edit_db_overwritebeta, $edit_db_fileprefix, $edit_db_betanums, $edit_db_threshold, $edit_db_smoothkernel, $edit_db_imcalcs, $edit_db_imnames);
		DisplayPrefs($id, "");
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
		DisplaySummary($taskid);
	}
	elseif ($action == "display") {
		DisplayPrefs($id, $viewtype);
	}
	elseif ($action == "check") {
		CheckPrefs($id);
	}
	else {
		DisplaySummary($taskid);
	}


	/* -------------------------------------------- */
	/* ------- AddPrefs --------------------------- */
	/* -------------------------------------------- */
	function AddPrefs($taskid, $edit_description, $edit_shortname, $edit_extralines, $edit_do_behmatchup, $edit_do_stats, $edit_do_censor, $edit_do_autoslice, $edit_do_db, $edit_beh_queue, $edit_beh_digits, $edit_stat_makeasciis, $edit_stat_asciiscript, $edit_stat_behdirname, $edit_stat_relativepath, $edit_stat_dirname, $edit_stat_pattern, $edit_stat_behunits, $edit_stat_volterra, $edit_stats_basisfunction, $edit_stat_onsetfiles, $edit_stat_durationfiles, $edit_stat_regressorfiles, $edit_stat_regressornames, $edit_stat_parameternames, $edit_stat_parameterorders, $edit_stat_parameterfiles, $edit_stat_censorfiles, $edit_stat_contrastmatrix, $edit_stat_xbflength, $edit_stat_xbforder, $edit_stat_timemodulation, $edit_stat_parametricmodulation, $edit_stat_globalfx, $edit_stat_highpasscutoff, $edit_stat_serialcorr, $edit_stat_autoslicecons, $edit_stat_autoslicep, $edit_stat_autoslicebackground, $edit_stat_autosliceslices, $edit_stat_autosliceemailcons, $edit_db_overwritebeta, $edit_db_fileprefix, $edit_db_betanums, $edit_db_threshold, $edit_db_smoothkernel, $edit_db_imcalcs, $edit_db_imnames) {

		$edit_description = mysqli_real_escape_string($edit_description);
		$edit_shortname = mysqli_real_escape_string($edit_shortname);
		$edit_extralines = mysqli_real_escape_string($edit_extralines);
		if ($edit_do_behmatchup == "yes") { $edit_do_behmatchup = "1"; } else { $edit_do_behmatchup = "0"; }
		if ($edit_do_stats == "yes") { $edit_do_stats = "1"; } else { $edit_do_stats = "0"; }
		if ($edit_do_censor == "yes") { $edit_do_censor = "1"; } else { $edit_do_censor = "0"; }
		if ($edit_do_autoslice == "yes") { $edit_do_autoslice = "1"; } else { $edit_do_autoslice = "0"; }
		if ($edit_do_db == "yes") { $edit_do_db = "1"; } else { $edit_do_db = "0"; }
		$edit_beh_queue = mysqli_real_escape_string($edit_beh_queue);
		$edit_beh_digits = mysqli_real_escape_string($edit_beh_digits);
		$edit_stat_behdirname = mysqli_real_escape_string($edit_stat_behdirname);
		if ($edit_stat_relativepath == "yes") { $edit_stat_relativepath = "1"; } else { $edit_stat_relativepath = "0"; }
		if ($edit_stat_makeasciis == "yes") { $edit_stat_makeasciis = "1"; } else { $edit_stat_makeasciis = "0"; }
		$edit_stat_asciiscript = mysqli_real_escape_string($edit_stat_asciiscript);
		$edit_beh_digits = mysqli_real_escape_string($edit_beh_digits);
		$edit_stat_dirname = mysqli_real_escape_string($edit_stat_dirname);
		$edit_stat_pattern = mysqli_real_escape_string($edit_stat_pattern);
		$edit_stat_behunits = mysqli_real_escape_string($edit_stat_behunits);
		if ($edit_stat_volterra == "yes") { $edit_stat_volterra = "1"; } else { $edit_stat_volterra = "0"; }
		$edit_stats_basisfunction = mysqli_real_escape_string($edit_stats_basisfunction);
		$edit_stat_onsetfiles = mysqli_real_escape_string(FormatMatrix($edit_stat_onsetfiles));
		$edit_stat_durationfiles = mysqli_real_escape_string(FormatMatrix($edit_stat_durationfiles));
		$edit_stat_regressorfiles = mysqli_real_escape_string(FormatMatrix($edit_stat_regressorfiles));
		$edit_stat_regressornames = mysqli_real_escape_string(FormatMatrix($edit_stat_regressornames));
		$edit_stat_parameternames = mysqli_real_escape_string(FormatMatrix($edit_stat_parameternames));
		$edit_stat_parameterorders = mysqli_real_escape_string(FormatMatrix($edit_stat_parameterorders));
		$edit_stat_parameterfiles = mysqli_real_escape_string(FormatMatrix($edit_stat_parameterfiles));
		$edit_stat_censorfiles = mysqli_real_escape_string(FormatMatrix($edit_stat_censorfiles));
		$edit_stat_timemodulation = mysqli_real_escape_string($edit_stat_timemodulation);
		$edit_stat_parametricmodulation = mysqli_real_escape_string($edit_stat_parametricmodulation);

		$cons = FormatContrastMatrix($edit_stat_contrastmatrix);
		$edit_stat_tcontrasts = $cons[0];
		//$edit_stat_tcon_columnlabels = $cons[1];
		$edit_stat_tcontrastnames = mysqli_real_escape_string($cons[2]);
		//$edit_stat_tcontrasts = mysqli_real_escape_string($edit_stat_tcontrasts);
		//$edit_stat_tcontrastnames = mysqli_real_escape_string($edit_stat_tcontrastnames);

		//echo "<pre>";
		//print_r($edit_stat_tcontrasts);
		//echo "blah blah";
		//print_r($edit_stat_tcontrastnames);
		//echo "</pre>";

		if ($edit_stat_globalfx == "yes") { $edit_stat_globalfx = "1"; } else { $edit_stat_globalfx = "0"; }
		if ($edit_stat_serialcorr == "yes") { $edit_stat_serialcorr = "1"; } else { $edit_stat_serialcorr = "0"; }
		$edit_stat_autoslicecons = mysqli_real_escape_string($edit_stat_autoslicecons);
		$edit_stat_autoslicebackground = mysqli_real_escape_string($edit_stat_autoslicebackground);
		$edit_stat_autosliceslices = mysqli_real_escape_string($edit_stat_autosliceslices);
		$edit_stat_autosliceemailcons = mysqli_real_escape_string($edit_stat_autosliceemailcons);
		if ($edit_db_overwritebeta == "yes") { $edit_db_overwritebeta = "1"; } else { $edit_db_overwritebeta = "0"; }
		$edit_db_fileprefix = mysqli_real_escape_string($edit_db_fileprefix);
		$edit_db_betanums = mysqli_real_escape_string($edit_db_betanums);
		$edit_db_smoothkernel = mysqli_real_escape_string($edit_db_smoothkernel);
		$edit_db_imcalcs = mysqli_real_escape_string(FormatMatrix($edit_db_imcalcs));
		$edit_db_imnames = mysqli_real_escape_string(FormatMatrix($edit_db_imnames));


		$sqlstring  = "insert into task_stats_prefs ( taskid, description, shortname, extralines, startdate, enddate, do_behmatchup, do_stats, do_censor, do_autoslice, do_db, beh_queue, beh_digits, stats_makeasciis, stats_asciiscriptpath, stats_behdirname, stats_relativepath, stats_dirname, stats_pattern, stats_behunits, stats_volterra, stats_basisfunction, stats_onsetfiles, stats_durationfiles, stats_regressorfiles, stats_regressornames, stats_paramnames, stats_paramorders, stats_paramfiles, stats_censorfiles, stats_fit_xbflength, stats_fit_xbforder, stats_timemodulation, stats_parametricmodulation, stats_globalfx, stats_highpasscutoff, stats_serialcorr, stats_tcontrasts, stats_tcontrastnames, autoslice_cons, autoslice_p, autoslice_background, autoslice_slices, autoslice_emailcons, db_overwritebeta, db_fileprefix, db_betanums, db_threshold, db_smoothkernel, db_imcalcs, db_imnames ) values ($taskid, '$edit_description', '$edit_shortname', '$edit_extralines', now(), '3000-01-01 00:00:00', $edit_do_behmatchup, $edit_do_stats, $edit_do_censor, $edit_do_autoslice, $edit_do_db, '$edit_beh_queue', '$edit_beh_digits', $edit_stat_makeasciis, '$edit_stat_asciiscript', '$edit_stat_behdirname', '$edit_stat_relativepath', '$edit_stat_dirname', '$edit_stat_pattern', '$edit_stat_behunits', $edit_stat_volterra, $edit_stats_basisfunction, '$edit_stat_onsetfiles', '$edit_stat_durationfiles', '$edit_stat_regressorfiles', '$edit_stat_regressornames', '$edit_stat_parameternames', '$edit_stat_parameterorders', '$edit_stat_parameterfiles', '$edit_stat_censorfiles', '$edit_stat_xbflength', '$edit_stat_xbforder', '$edit_stat_timemodulation', '$edit_stat_parametricmodulation', $edit_stat_globalfx, $edit_stat_highpasscutoff, $edit_stat_serialcorr, '$edit_stat_tcontrasts', '$edit_stat_tcontrastnames', '$edit_stat_autoslicecons', '$edit_stat_autoslicep', '$edit_stat_autoslicebackground', '$edit_stat_autosliceslices', '$edit_stat_autosliceemailcons', $edit_db_overwritebeta, '$edit_db_fileprefix', '$edit_db_betanums', '$edit_db_threshold', '$edit_db_smoothkernel', '$edit_db_imcalcs', '$edit_db_imnames')";
		echo "$sqlstring<br>";
		MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$prefsid = mysql_insert_id();

		?><div class="message">Stats Pref file added</div><br><?
		
		return $prefsid;
	}

	
	/* -------------------------------------------- */
	/* ------- CopyToNew -------------------------- */
	/* -------------------------------------------- */
	function CopyToNew($taskid, $oldid) {
		$sqlstring = "select * from task_stats_prefs where id = $oldid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		//$id = $row['id'];
		//$taskid = $row['taskid'];
		$description = mysqli_real_escape_string($row['description']);
		$shortname = "copy_of_" . mysqli_real_escape_string($row['shortname']);
		$extralines = mysqli_real_escape_string($row['extralines']);
		//$startdate = $row['startdate'];
		$do_behmatchup = $row['do_behmatchup'];
		$do_stats = $row['do_stats'];
		$do_censor = $row['do_censor'];
		$do_autoslice = $row['do_autoslice'];
		$do_db = $row['do_db'];
		$beh_queue = mysqli_real_escape_string($row['beh_queue']);
		$beh_digits = mysqli_real_escape_string($row['beh_digits']);
		$stats_makeasciis = $row['stats_makeasciis'];
		$stats_asciiscriptpath = mysqli_real_escape_string($row['stats_asciiscriptpath']);
		$stats_behdirname = mysqli_real_escape_string($row['stats_behdirname']);
		$stats_relativepath = mysqli_real_escape_string($row['stats_relativepath']);
		$stats_dirname = mysqli_real_escape_string($row['stats_dirname']);
		$stats_pattern = mysqli_real_escape_string($row['stats_pattern']);
		$stats_behunits = $row['stats_behunits'];
		$stats_volterra = $row['stats_volterra'];
		$stats_basisfunction = $row['stats_basisfunction'];
		$stats_onsetfiles = mysqli_real_escape_string($row['stats_onsetfiles']);
		$stats_durationfiles = mysqli_real_escape_string($row['stats_durationfiles']);
		$stats_regressorfiles = mysqli_real_escape_string($row['stats_regressorfiles']);
		$stats_regressornames = mysqli_real_escape_string($row['stats_regressornames']);
		$stats_paramnames = mysqli_real_escape_string($row['stats_paramnames']);
		$stats_paramorders = mysqli_real_escape_string($row['stats_paramorders']);
		$stats_paramfiles = mysqli_real_escape_string($row['stats_paramfiles']);
		$stats_censorfiles = mysqli_real_escape_string($row['stats_censorfiles']);
		//$stats_censorfiles_format = $row['stats_censorfiles'];
		$stats_fit_xbflength = $row['stats_fit_xbflength'];
		$stats_fit_xbforder = $row['stats_fit_xbforder'];
		$stats_timemodulation = $row['stats_timemodulation'];
		$stats_parametricmodulation = $row['stats_parametricmodulation'];
		$stats_globalfx = $row['stats_globalfx'];
		$stats_highpasscutoff = $row['stats_highpasscutoff'];
		$stats_serialcorr = $row['stats_serialcorr'];
		$stats_tcontrasts = mysqli_real_escape_string($row['stats_tcontrasts']);
		$stats_tcontrastnames = mysqli_real_escape_string($row['stats_tcontrastnames']);
		$autoslice_cons = mysqli_real_escape_string($row['autoslice_cons']);
		$autoslice_p = mysqli_real_escape_string($row['autoslice_p']);
		$autoslice_background = $row['autoslice_background'];
		$autoslice_slices = $row['autoslice_slices'];
		$autoslice_emailcons = $row['autoslice_emailcons'];
		$db_overwritebeta = $row['db_overwritebeta'];
		$db_fileprefix = mysqli_real_escape_string($row['db_fileprefix']);
		$db_betanums = $row['db_betanums'];
		$db_threshold = $row['db_threshold'];
		$db_smoothkernel = $row['db_smoothkernel'];
		$db_imcalcs = mysqli_real_escape_string($row['db_imcalcs']);
		$db_imnames = mysqli_real_escape_string($row['db_imnames']);
		
		$sqlstring  = "insert into task_stats_prefs ( taskid, description, shortname, extralines, startdate, enddate, do_behmatchup, do_stats, do_censor, do_autoslice, do_db, beh_queue, beh_digits, stats_makeasciis, stats_asciiscriptpath, stats_behdirname, stats_relativepath, stats_dirname, stats_pattern, stats_behunits, stats_volterra, stats_basisfunction, stats_onsetfiles, stats_durationfiles, stats_regressorfiles, stats_regressornames, stats_paramnames, stats_paramorders, stats_paramfiles, stats_censorfiles, stats_fit_xbflength, stats_fit_xbforder, stats_timemodulation, stats_parametricmodulation, stats_globalfx, stats_highpasscutoff, stats_serialcorr, stats_tcontrasts, stats_tcontrastnames, autoslice_cons, autoslice_p, autoslice_background, autoslice_slices, autoslice_emailcons, db_overwritebeta, db_fileprefix, db_betanums, db_threshold, db_smoothkernel, db_imcalcs, db_imnames ) values ($taskid, '$description', '$shortname', '$extralines', now(), '3000-01-01 00:00:00', '$do_behmatchup', '$do_stats', '$do_censor', '$do_autoslice', '$do_db', '$beh_queue', '$beh_digits', '$stats_makeasciis', '$stats_asciiscript', '$stats_behdirname', '$stats_relativepath', '$stats_dirname', '$stats_pattern', '$stats_behunits', '$stats_volterra', '$stats_basisfunction', '$stats_onsetfiles', '$stats_durationfiles', '$stats_regressorfiles', '$stats_regressornames', '$stats_parameternames', '$stats_parameterorders', '$stats_parameterfiles', '$stats_censorfiles', '$stats_xbflength', '$stats_xbforder', '$stats_timemodulation', '$stats_parametricmodulation', '$stats_globalfx', '$stats_highpasscutoff', '$stats_serialcorr', '$stats_tcontrasts', '$stats_tcontrastnames', '$autoslice_cons', '$autoslice_p', '$autoslice_background', '$autoslice_slices', '$autoslice_emailcons', '$db_overwritebeta', '$db_fileprefix', '$db_betanums', '$db_threshold', '$db_smoothkernel', '$db_imcalcs', '$db_imnames')";
		//echo "$sqlstring<br>";
		//exit(0);
		MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$prefsid = mysql_insert_id();

		?><div class="message">Stats Pref file added</div><br><?
		
		return $prefsid;
	}	


	/* -------------------------------------------- */
	/* ------- FormatMatrix ----------------------- */
	/* -------------------------------------------- */
	function FormatMatrix($str) {

		if (trim($str) == "") {
			return "";
		}
		else {
			//echo "<pre>";
			$ret = "";
			$lines = explode("\r\n",$str);
			foreach ($lines as $line) {
				/* remove quotes, semi-colons, etc */
				$line = str_replace(array("\"","'",";"),"",$line);
				
				/* separate the line based on the commas or spaces */
				$parts = preg_split("/[\s,]+/", $line);
				//print_r($parts);
				/* go through each element on the line and surround with apostrophes */
				for ($i=0; $i<sizeof($parts); $i++) {
					$parts[$i] = "'" . $parts[$i] . "'";
				}


				/* put the parts back together with commas and append with a semi-colon */
				$line = implode(",", $parts) . ";";
				$ret .= $line;
			}
			echo "</pre>";
			return $ret;
		}
	}


	/* -------------------------------------------- */
	/* ------- FormatContrastMatrix --------------- */
	/* -------------------------------------------- */
	function FormatContrastMatrix($str) {
		echo "<pre>";
		$lines = explode("\n",$str);
		array_pop($lines);
		$i = 0;
		foreach ($lines as $line) {
			/* remove spaces, quotes, etc */
			$line = str_replace(array("\"","'"," "),"",$line);
			
			//echo $line;
			/* separate the line based on the commas */
			$parts = explode(",", $line);

			//print_r($parts);
			//$connames[$i] = $parts[0] . "; ";
			
			/* put the parts back together with commas and append with a semi-colon */
			array_shift($parts); /* remove 0th element */
			$connames[] = "'" . array_shift($parts) . "'"; /* remove 1st element */
			array_pop($parts); /* remove the last element, which should be blank */
			$line = implode(",", $parts) . ";";
			if ($i == 0) {
				$labels = $line;
			}
			else {
				//print_r($parts);
				//echo $line;
				$cons .= $line;
			}
			//echo "Cons: $cons\n";
			$i++;
		}
		array_shift($connames); /* remove 0th element which is always blank */
		array_shift($connames); /* remove 1st element which is always 'Labels' */
		$names = implode(";", $connames);

		//print_r($cons);
		//echo "blah blah";
		//print_r($names);
		//echo "</pre>";
		
		$ret = array($cons, $labels, $names);
		return $ret;
	}


	/* -------------------------------------------- */
	/* ------- DeletePrefs ------------------------ */
	/* -------------------------------------------- */
	function DeletePrefs($id) {
		$sqlstring = "update task_stats_prefs set enddate = now() where id = $id";
		MySQLiQuery($sqlstring, __FILE__, __LINE__);

		?><div class="message">File '<? echo $id ?>' deleted</div><br>
		<a href="tasks.php">Back to list of tasks</a>
		<?
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
		
		//echo "Autocs [$autocsver]<br>";
	?>
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

		<form action="autocs_statsprefs.php" method="post" id="form1">
		<input type="hidden" name="action" value="add">
		<input type="hidden" name="taskid" value="<? echo $taskid; ?>">

		<table><tr><td><img src="images/back16.png"></td><td><a href="autocs_statsprefs.php?taskid=<? echo $taskid; ?>" class="link">Cancel</a></td></tr></table><br>
		
		<table cellspacing="0" cellpadding="4" id="tableone" class="editor">
			<tr>
				<td colspan="3" style="border-bottom: 3px double #222222; text-align: center; font-weight: bold">Add New Stats Preferences File for <? echo $taskname; ?> <span class="spm<?=$autocsver?>">spm<?=$autocsver?></span></td>
			</tr>
			<tr>
				<td colspan="3">&nbsp;</td>
			</tr>
			<tr>
				<td class="label">Prefs Description</td>
				<td colspan="2" class="value"><input type="text" name="edit_description" class="csprefsinput required" size="70"></td>
			</tr>
			<tr>
				<td class="label">Short name<br><span class="sublabel">letters and numbers only, no spaces</span></td>
				<td colspan="2" class="value"><input type="text" name="edit_shortname" class="csprefsinput required"></td>
			</tr>
			<tr>
				<td class="label">Extra lines to include<br><span class="sublabel">path changes, etc</span></td>
				<td colspan="2" class="value" valign="top">
				<textarea name="edit_extralines" class="csprefsinput" cols="70" rows="5" wrap="off"></textarea>
				<!--rmpath('/opt/spm99');
				rmpath('/opt/spmd99b');
				rmpath('/denali/home/kiehl/display');
				addpath('/opt/spm2');
				addpath('/opt/spm2/toolbox/INRIAlign');
				addpath('/opt/center_scripts');
				addpath('/opt/center_scripts/slice_overlay');-->
				</td>
			</tr>
			<tr>
				<td class="label" style="background-color: white">Steps to perform</td>
				<td colspan="2" class="value" style="background-color: white">
					<input type="checkbox" name="edit_do_behmatchup" value="yes" class="csprefsinput" checked>Behavioral Matchup<br>
					<input type="checkbox" name="edit_do_stats" value="yes" class="csprefsinput" checked>Stats<br>
					<input type="checkbox" name="edit_do_censor" value="yes" class="csprefsinput" checked>Time censoring <span class="sublabel">Using this option will disable regressors</span><br>
					<input type="checkbox" name="edit_do_autoslice" value="yes" class="csprefsinput" checked>Autoslice<br>
					<input type="checkbox" name="edit_do_db" value="yes" class="csprefsinput" checked>Derivative Boost<br>
				</td>
			</tr>
			<tr><td colspan="2" style="font-size: 8pt">&nbsp;</td></tr>

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
			<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
			<tr>
				<td class="label">Behavioral directory name</td>
				<td class="value"><input type="text" name="edit_stat_behdirname" value="beh" class="csprefsinput" checked size="40"> <tt>csprefs.stats_beh_dir_name</tt> <img src="images/help.gif" onMouseOver="Tip('Behavioral directory name. This will be used when make ascii script is used', TITLE, 'csprefs.stats_beh_dir_name', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<tr>
				<td class="label">Use relative path?</td>
				<td class="value"><input type="checkbox" name="edit_stat_relativepath" value="yes" class="csprefsinput" checked> <tt>csprefs.stats_files_relative_path_sub</tt> <img src="images/help.gif" onMouseOver="Tip('Checked means onset and duration files will be relative to <b>subject</b> directory and unchecked means onset and duration files will be relative to <b>run</b> directory.', TITLE, 'csprefs.stats_files_relative_path_sub', FOLLOWMOUSE, false, FADEIN, 0, FADEOUT, 200, BGCOLOR, 'lightyellow', WIDTH, -500, DURATION, -2000)" onMouseOut="UnTip()"></td>
			</tr>
			<? } ?>
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

			<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
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
			<? } ?>
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
	/* ------- DisplayPrefs ----------------------- */
	/* -------------------------------------------- */
	function DisplayPrefs($id, $viewtype) {
		/* get the preprocessing information */
		$sqlstring = "select * from task_stats_prefs where id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$id = $row['id'];
		$taskid = $row['taskid'];
		$description = $row['description'];
		$shortname = $row['shortname'];
		$extralines = $row['extralines'];
		$startdate = $row['startdate'];
		$do_behmatchup = $row['do_behmatchup'];
		$do_stats = $row['do_stats'];
		$do_censor = $row['do_censor'];
		$do_autoslice = $row['do_autoslice'];
		$do_db = $row['do_db'];
		$beh_queue = $row['beh_queue'];
		$beh_digits = $row['beh_digits'];
		$stats_makeasciis = $row['stats_makeasciis'];
		$stats_asciiscriptpath = $row['stats_asciiscriptpath'];
		$stats_behdirname = $row['stats_behdirname'];
		$stats_relativepath = $row['stats_relativepath'];
		$stats_dirname = $row['stats_dirname'];
		$stats_pattern = $row['stats_pattern'];
		$stats_behunits = $row['stats_behunits'];
		$stats_volterra = $row['stats_volterra'];
		$stats_basisfunction = $row['stats_basisfunction'];
		$stats_onsetfiles = FormatDisplayMatrix($row['stats_onsetfiles']);
		$stats_durationfiles = FormatDisplayMatrix($row['stats_durationfiles']);
		$stats_regressorfiles = FormatDisplayMatrix($row['stats_regressorfiles']);
		$stats_regressornames = FormatDisplayMatrix($row['stats_regressornames']);
		$stats_paramnames = FormatDisplayMatrix($row['stats_paramnames']);
		$stats_paramorders = FormatDisplayMatrix($row['stats_paramorders']);
		$stats_paramfiles = FormatDisplayMatrix($row['stats_paramfiles']);
		$stats_censorfiles = FormatDisplayMatrix($row['stats_censorfiles']);
		$stats_censorfiles_format = FormatCensorMatrix($row['stats_censorfiles']);
		$stats_fit_xbflength = $row['stats_fit_xbflength'];
		$stats_fit_xbforder = $row['stats_fit_xbforder'];
		$stats_timemodulation = $row['stats_timemodulation'];
		$stats_parametricmodulation = $row['stats_parametricmodulation'];
		$stats_globalfx = $row['stats_globalfx'];
		$stats_highpasscutoff = $row['stats_highpasscutoff'];
		$stats_serialcorr = $row['stats_serialcorr'];
		$stats_tcontrasts = FormatConDisplayMatrix($row['stats_tcon_columnlabels'], $row['stats_tcontrasts']);
		$stats_tcontrastnames = FormatDisplayMatrix($row['stats_tcontrastnames']);
		$autoslice_cons = $row['autoslice_cons'];
		$autoslice_p = $row['autoslice_p'];
		$autoslice_background = $row['autoslice_background'];
		$autoslice_slices = $row['autoslice_slices'];
		$autoslice_emailcons = $row['autoslice_emailcons'];
		$db_overwritebeta = $row['db_overwritebeta'];
		$db_fileprefix = $row['db_fileprefix'];
		$db_betanums = $row['db_betanums'];
		$db_threshold = $row['db_threshold'];
		$db_smoothkernel = $row['db_smoothkernel'];
		$db_imcalcs = FormatDisplayMatrix($row['db_imcalcs']);
		$db_imnames = FormatDisplayMatrix($row['db_imnames']);

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
		<table><tr><td><img src="images/back16.png"></td><td><a href="autocs_statsprefs.php?taskid=<? echo $taskid; ?>" class="link">Back</a> to summary</td></tr></table><br>
		<span class="spm<?=$autocsver?>">spm<?=$autocsver?></span>
		<br><br>
		<?

		if ($viewtype == "print") {
			?>
			<div style="border: 1pt gray dashed; padding: 5pt;">
			<pre>
<span style="color: red">function</span> cs_prefs_<? echo $taskname; ?>_<span id="shortname"><? echo $shortname; ?></span>
<span style="color: green">%<? echo $description; ?></span>.

<span style="color: red">global</span> csprefs;
<span style="color: red">global</span> defaults;
warning off;

<? if ($autocsver == 5) { ?>
<span style="color: red">if</span> ~exist('<span style="color: salmon">initGraphics</span>', '<span style="color: salmon">var</span>')
    initGraphics = 1;
<span style="color: red">end</span>
<? } ?>

<span style="color: green">% PATH CHANGES, ETC</span>
<? echo $extralines; ?>


<span style="color: green">% PROCESSING STEPS TO RUN</span>
csprefs.run_beh_matchup         = <? echo $do_behmatchup; ?>;
<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
csprefs.run_dicom_convert       = 0;
csprefs.run_reorient            = 0;
<? } ?>
csprefs.run_realign             = 0;
<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
csprefs.run_coregister          = 0;
csprefs.run_slicetime           = 0;
<? } ?>
csprefs.run_normalize           = 0;
csprefs.run_smooth              = 0;
csprefs.run_filter              = 0;
csprefs.run_stats               = <? echo $do_stats; ?>;
csprefs.run_usecensor           = <? echo $do_censor; ?>;
csprefs.run_autoslice           = <? echo $do_autoslice; ?>;
csprefs.run_deriv_boost         = <? echo $do_db; ?>;
<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
csprefs.run_segment             = 0;
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

<span style="color: green">% SETTINGS PERTAINING TO CS_BEH_MATCHUP</span>
csprefs.beh_queue_dir           = '<span style="color: salmon"><? echo $beh_queue; ?></span>';
csprefs.digits                  = <? echo $beh_digits; ?>;

<span style="color: green">% SETTINGS PERTAINING TO CS_STATS</span>
csprefs.stats_make_asciis       = <? echo $stats_makeasciis; ?>;
csprefs.stats_ascii_script      = '<span style="color: salmon"><? echo $stats_asciiscriptpath; ?></span>';
<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
csprefs.stats_beh_dir_name      = '<span style="color: salmon"><?=$stats_behdirname;?></span>';
csprefs.stats_files_relative_path_sub = <?=$stats_relativepath;?>;
<? } ?>
csprefs.stats_dir_name          = '<span style="color: salmon"><? echo $stats_dirname; ?></span>';
csprefs.stats_pattern           = '<span style="color: salmon"><? echo $stats_pattern; ?></span>';
csprefs.stats_beh_units         = '<span style="color: salmon"><? echo $stats_behunits; ?></span>';
csprefs.stats_volterra          = <? echo $stats_volterra; ?>;
csprefs.stats_basis_func        = <? echo $stats_basisfunction; ?>;
csprefs.stats_onset_files       = {<div><? echo $stats_onsetfiles; ?></div>};
csprefs.stats_duration_files    = {<div><? echo $stats_durationfiles; ?></div>};
csprefs.stats_regressor_files   = {<div><? echo $stats_regressorfiles; ?></div>};
csprefs.stats_regressor_names   = {<div><? echo $stats_regressornames; ?></div>};
csprefs.stats_param_names       = {<div><? echo $stats_paramnames; ?></div>};
csprefs.stats_param_orders      = {<div><? echo $stats_paramorders; ?></div>};
csprefs.stats_param_files       = {<div><? echo $stats_paramfiles; ?></div>};
<div><? echo $stats_censorfiles_format; ?></div>
<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
csprefs.xBF.length              = <? echo $stats_fit_xbflength;?>;
csprefs.xBF.order               = <? echo $stats_fit_xbforder;?>;
csprefs.stats_time_modulation   = {<div><? echo $stats_timemodulation;?></div>};
csprefs.stats_parametric_modulation = {<div><? echo $stats_parametricmodulation;?></div>};
<? } ?>
csprefs.stats_global_fx         = <? echo $stats_globalfx; ?>;
csprefs.stats_highpass_cutoff   = <? echo $stats_highpasscutoff; ?>;
csprefs.stats_serial_corr       = <? echo $stats_serialcorr; ?>;

csprefs.stats_tcontrasts        = [<div><? echo $stats_tcontrasts; ?></div>];

csprefs.stats_tcontrast_names   = {<div><? echo $stats_tcontrastnames; ?></div>};

<span style="color: green">% SETTINGS PERTAINING TO CS_AUTOSLICE</span>
csprefs.autoslice_cons          = [<? echo $autoslice_cons; ?>];
csprefs.autoslice_p             = <? echo $autoslice_p; ?>;
csprefs.autoslice_background    = '<? echo $autoslice_background; ?>';
csprefs.autoslice_slices        = [<? echo $autoslice_slices; ?>];
csprefs.autoslice_email_cons    = [<? echo $autoslice_emailcons; ?>];

<span style="color: green">% SETTINGS PERTAINING TO CS_DERIVATIVE_BOOST</span>
csprefs.dboost_overwrite_beta   = <? echo $db_overwritebeta; ?>;
csprefs.dboost_file_prefix      = '<? echo $db_fileprefix; ?>';
csprefs.dboost_beta_nums        = [<? echo $db_betanums; ?>];
csprefs.dboost_threshold        = <? echo $db_threshold; ?>;
csprefs.dboost_smooth_kernel    = [<? echo $db_smoothkernel; ?>];
csprefs.dboost_im_calcs         = {<div><? echo $db_imcalcs; ?></div>};
csprefs.dboost_im_names         = {<div><? echo $db_imnames; ?></div>};

<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
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
					url: "autocs_statsprefs_inlineupdate.php",
					params: "action=editinplace&id=<? echo $id; ?>",
					bg_over: "lightblue",
					bg_out: "lightyellow",
				});
				$(".edit_textarea").editInPlace({
					url: "autocs_statsprefs_inlineupdate.php",
					params: "action=editinplace&id=<? echo $id; ?>",
					field_type: "textarea",
					bg_over: "lightblue",
					bg_out: "lightyellow",
					textarea_rows: "10",
					textarea_cols: "100",
				});
			});
		</script>
		<style type="text/css">
            .edit_inline { background-color: lightyellow; padding-left: 2pt; padding-right: 2pt; }
            .edit_textarea { background-color: lightyellow; }
			textarea.inplace_field { background-color: lightblue; font-family: courier new; font-size: 9pt; border: 1pt solid gray; width: 800px;  }
			input.inplace_field { background-color: lightblue; font-family: courier new; font-size: 9pt; border: 1pt solid gray; width: 400px;  }
		</style>

		<div align="center">
		<table cellpadding="10" cellspacing="0">
			<tr>
				<td style="background-color: ivory; border-left: 1pt solid gray; border-top: 1pt solid gray;">
					<span style="font-size: 14pt; font-weight: bold">Preprocessing for <? echo $taskname; ?></span> <br>
					<span style="font-size:10pt">You can edit <span style="background-color: lightyellow">highlighted</span> values in place by clicking and editing them. <span style="color: #AAAAAA">Grayed</span> values are not editable.</span>
				</td>
				<td align="right" valign="top" style="background-color: ivory; border-top: 1pt solid gray; border-right: 1pt solid gray"><a href="autocs_statsprefs.php?action=display&viewtype=print&id=<? echo $id; ?>" style="color:blue; font-size: 10pt">Print preview</a></td>
			</tr>
			<tr>
				<td style="border: 1pt dashed gray" colspan="2">
					<span style="white-space: pre; font-family: courier new; font-size: 9pt;">


<span style="color: red">function</span> cs_prefs_<? echo $taskname; ?>_<span id="shortname" class="edit_inline"><span id="shortname"><? echo $shortname; ?></span></span>
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

<span style="color: green">% PROCESSING STEPS TO RUN</span>
csprefs.run_beh_matchup         = <span id="do_behmatchup" class="edit_inline"><? echo $do_behmatchup; ?></span>;
<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
csprefs.run_dicom_convert       = 0;
csprefs.run_reorient            = 0;
<? } ?>
csprefs.run_realign             = 0;
<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
csprefs.run_coregister          = 0;
csprefs.run_slicetime           = 0;
<? } ?>
csprefs.run_normalize           = 0;
csprefs.run_smooth              = 0;
csprefs.run_filter              = 0;
csprefs.run_stats               = <span id="do_stats" class="edit_inline"><? echo $do_stats; ?></span>;
csprefs.run_usecensor           = <span id="do_censor" class="edit_inline"><? echo $do_censor; ?></span>;
csprefs.run_autoslice           = <span id="do_autoslice" class="edit_inline"><? echo $do_autoslice; ?></span>;
csprefs.run_deriv_boost         = <span id="do_db" class="edit_inline"><? echo $do_db; ?></span>;
<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
csprefs.run_segment             = 0;
<? } ?>
csprefs.sendmail                = 0;

<span style="color: green">% GENERAL SETTINGS... FOR ALL CENTERSCRIPTS FUNCTIONS</span> <span style="color:gray">
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
csprefs.tr                      = <? echo $tr; ?>; </span>

<span style="color: green">% SETTINGS PERTAINING TO CS_BEH_MATCHUP</span>
csprefs.beh_queue_dir           = '<span id="beh_queue" class="edit_inline"><span style="color: salmon"><? echo $beh_queue; ?></span></span>';
csprefs.digits                  = <span id="beh_digits" class="edit_inline"><? echo $beh_digits; ?></span>;

<span style="color: green">% SETTINGS PERTAINING TO CS_STATS</span>
csprefs.stats_make_asciis       = <span id="stats_makeasciis" class="edit_inline"><? echo $stats_makeasciis; ?></span>;
csprefs.stats_ascii_script      = '<span id="stats_asciiscriptpath" class="edit_inline"><span style="color: salmon"><? echo $stats_asciiscriptpath; ?></span></span>';
<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
csprefs.stats_beh_dir_name      = '<span id="stats_behdirname" class="edit_inline"><span style="color: salmon"><?=$stats_behdirname;?></span></span>';
csprefs.stats_files_relative_path_sub = <span id="stats_relativepath" class="edit_inline"><?=$stats_relativepath;?></span>;
<? } ?>
csprefs.stats_dir_name          = '<span id="stats_dirname" class="edit_inline"><span style="color: salmon"><? echo $stats_dirname; ?></span></span>';
csprefs.stats_pattern           = '<span id="stats_pattern" class="edit_inline"><span style="color: salmon"><? echo $stats_pattern; ?></span></span>';
csprefs.stats_beh_units         = '<span id="stats_behunits" class="edit_inline"><span style="color: salmon"><? echo $stats_behunits; ?></span></span>';
csprefs.stats_volterra          = <span id="stats_volterra" class="edit_inline"><? echo $stats_volterra; ?></span>;
csprefs.stats_basis_func        = <span id="stats_basisfunction" class="edit_inline"><? echo $stats_basisfunction; ?></span>;
csprefs.stats_onset_files       = {<div id="stats_onsetfiles" class="edit_textarea"><? echo $stats_onsetfiles; ?></div>};
csprefs.stats_duration_files    = {<div id="stats_durationfiles" class="edit_textarea"><? echo $stats_durationfiles; ?></div>};
csprefs.stats_regressor_files   = {<div id="stats_regressorfiles" class="edit_textarea"><? echo $stats_regressorfiles; ?></div>};
csprefs.stats_regressor_names   = {<div id="stats_regressornames" class="edit_textarea"><? echo $stats_regressornames; ?></div>};
csprefs.stats_param_names       = {<div id="stats_paramnames" class="edit_textarea"><? echo $stats_paramnames; ?></div>};
csprefs.stats_param_orders      = {<div id="stats_paramorders" class="edit_textarea"><? echo $stats_paramorders; ?></div>};
csprefs.stats_param_files       = {<div id="stats_paramfiles" class="edit_textarea"><? echo $stats_paramfiles; ?></div>};
<span style="color: green">% censor file parameters will be properly formatted when CenterScripts runs</span>
csprefs.stats_regressor_censor  = {<div id="stats_censorfiles" class="edit_textarea"><? echo $stats_censorfiles; ?></div>};
<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
csprefs.xBF.length              = <span id="stats_fit_xbflength" class="edit_inline"><? echo $stats_fit_xbflength;?></span>;
csprefs.xBF.order               = <span id="stats_fit_xbforder" class="edit_inline"><? echo $stats_fit_xbforder;?></span>;
csprefs.stats_time_modulation   = {<div><div id="stats_timemodulation" class="edit_textarea"><? echo $stats_timemodulation;?></div></div>};
csprefs.stats_parametric_modulation = {<div><div id="stats_parametricmodulation" class="edit_textarea"><? echo $stats_parametricmodulation;?></div></div>};
<? } ?>csprefs.stats_global_fx         = <span id="stats_globalfx" class="edit_inline"><? echo $stats_globalfx; ?></span>;
csprefs.stats_highpass_cutoff   = <span id="stats_highpasscutoff" class="edit_inline"><? echo $stats_highpasscutoff; ?></span>;
csprefs.stats_serial_corr       = <span id="stats_serialcorr" class="edit_inline"><? echo $stats_serialcorr; ?></span>;

csprefs.stats_tcontrasts        = [
<div id="stats_tcontrasts" class="edit_textarea"><? echo $stats_tcontrasts; ?></div>];

csprefs.stats_tcontrast_names   = {<div id="stats_tcontrastnames" class="edit_textarea"><? echo $stats_tcontrastnames; ?></div>};

<span style="color: green">% SETTINGS PERTAINING TO CS_AUTOSLICE</span>
csprefs.autoslice_cons          = [<span id="autoslice_cons" class="edit_inline"><? echo $autoslice_cons; ?></span>];
csprefs.autoslice_p             = <span id="autoslice_p" class="edit_inline"><? echo $autoslice_p; ?></span>;
csprefs.autoslice_background    = '<span id="autoslice_background" class="edit_inline"><span style="color: salmon"><? echo $autoslice_background; ?></span></span>';
csprefs.autoslice_slices        = [<span id="autoslice_slices" class="edit_inline"><? echo $autoslice_slices; ?></span>];
csprefs.autoslice_email_cons    = [<span id="autoslice_emailcons" class="edit_inline"><? echo $autoslice_emailcons; ?></span>];

<span style="color: green">% SETTINGS PERTAINING TO CS_DERIVATIVE_BOOST</span>
csprefs.dboost_overwrite_beta   = <span id="db_overwritebeta" class="edit_inline"><? echo $db_overwritebeta; ?></span>;
csprefs.dboost_file_prefix      = '<span id="db_fileprefix" class="edit_inline"><span style="color: salmon"><? echo $db_fileprefix; ?></span></span>';
csprefs.dboost_beta_nums        = [<span id="db_betanums" class="edit_inline"><? echo $db_betanums; ?></span>];
csprefs.dboost_threshold        = <span id="db_threshold" class="edit_inline"><? echo $db_threshold; ?></span>;
csprefs.dboost_smooth_kernel    = [<span id="db_smoothkernel" class="edit_inline"><? echo $db_smoothkernel; ?></span>];
csprefs.dboost_im_calcs         = {<div id="db_imcalcs" class="edit_textarea"><? echo $db_imcalcs; ?></div>};
csprefs.dboost_im_names         = {<div id="db_imnames" class="edit_textarea"><? echo $db_imnames; ?></div>};

<? if (($autocsver == 5) || ($autocsver == 8)) { ?>
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
	/* ------- FormatDisplayMatrix ---------------- */
	/* -------------------------------------------- */
	function FormatDisplayMatrix($str) {
		if (trim($str) == "") {
			return "";
		}
		else {
			$str = str_replace(array("\r\n","\n"),"",$str);
			$str = str_replace(";",";\n",$str);
			return "$str";
		}
	}

	
	/* -------------------------------------------- */
	/* ------- FormatCensorMatrix ----------------- */
	/* -------------------------------------------- */
	function FormatCensorMatrix($str) {
		if (trim($str) == "") {
			return "";
		}
		else {
			$lines = explode(";",$str);
			if ($lines[count($lines)] == "") {
				array_pop($lines);
			}
			$i = 1;
			$outstr = "";
			foreach ($lines as $line) {
				$tmpstr = "csprefs.stats_regressor_censor($i).files = { $line };\n";
				//echo "$tmpstr<br>";
				$outstr .= $tmpstr;
				$i++;
			}
			return "$outstr";
		}
	}
	

	/* -------------------------------------------- */
	/* ------- FormatConDisplayMatrix ------------- */
	/* -------------------------------------------- */
	function FormatConDisplayMatrix($labstr, $constr) {
		if (trim($constr) == "") {
			return "";
		}
		else {
			$constr = str_replace(array("%","\r\n","\n"),"",$constr);

			/* break the string into lines based on the semicolons */
			$lines = explode(";",$constr);
			$numlines = count($lines);

			$cols = preg_split("/[\s,]+/", trim($lines[0]));
			foreach ($cols as $col) {
				$col = str_pad($col, 7, " ", STR_PAD_RIGHT);
				$newline .= $col;
			}
			$newmatrix = "% " . rtrim($newline) . ";\n";
			$newline = "";

			/* go through each line and separate into columns, then reformat into tabbed columns */
			for($i=1; $i<$numlines; $i++) {
				$line = trim($lines[$i]);
				if (trim($line) == "") {
					continue;
				}
				$cols = preg_split("/[\s,]+/", $line);
				foreach ($cols as $col) {
					$col = str_pad($col, 7, " ", STR_PAD_RIGHT);
					$newline .= $col;
				}
				$newmatrix .= "  " . rtrim($newline) . ";\n";
				$newline = "";
			}
			return "$newmatrix";
		}
	}


	/* -------------------------------------------- */
	/* ------- CheckPrefs ------------------------- */
	/* -------------------------------------------- */
	function CheckPrefs($id) {
		/* get the preprocessing information */
		$sqlstring = "select * from task_stats_prefs where id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$id = $row['id'];
		$taskid = $row['taskid'];
		$description = $row['description'];
		$shortname = $row['shortname'];
		$extralines = $row['extralines'];
		$startdate = $row['startdate'];
		$do_behmatchup = $row['do_behmatchup'];
		$do_stats = $row['do_stats'];
		$do_autoslice = $row['do_autoslice'];
		$do_db = $row['do_db'];
		$beh_queue = $row['beh_queue'];
		$beh_digits = $row['beh_digits'];
		$stats_makeasciis = $row['stats_makeasciis'];
		$stats_asciiscriptpath = $row['stats_asciiscriptpath'];
		$stats_dirname = $row['stats_dirname'];
		$stats_pattern = $row['stats_pattern'];
		$stats_behunits = $row['stats_behunits'];
		$stats_volterra = $row['stats_volterra'];
		$stats_basisfunction = $row['stats_basisfunction'];
		list($onset_rows, $onset_cols) = GetMatrixSize($row['stats_onsetfiles']);
		list($duration_rows, $duration_cols) = GetMatrixSize($row['stats_durationfiles']);
		list($regressorfiles_rows, $regressorfiles_cols) = GetMatrixSize($row['stats_regressorfiles']);
		list($regressornames_rows, $regressornames_cols) = GetMatrixSize($row['stats_regressornames']);
		list($paramnames_rows, $paramnames_cols) = GetMatrixSize($row['stats_paramnames']);
		list($paramorders_rows, $paramorders_cols) = GetMatrixSize($row['stats_paramorders']);
		list($paramfiles_rows, $paramfiles_cols) = GetMatrixSize($row['stats_paramfiles']);
		$stats_globalfx = $row['stats_globalfx'];
		$stats_highpasscutoff = $row['stats_highpasscutoff'];
		$stats_serialcorr = $row['stats_serialcorr'];
		list($tcontrasts_rows, $tcontrasts_cols) = GetConMatrixSize($row['stats_tcontrasts']);
		list($tcontrastnames_rows, $tcontrastnames_cols) = GetMatrixSize($row['stats_tcontrastnames']);
		$autoslice_cons = $row['autoslice_cons'];
		$autoslice_p = $row['autoslice_p'];
		$autoslice_background = $row['autoslice_background'];
		$autoslice_slices = $row['autoslice_slices'];
		$autoslice_emailcons = $row['autoslice_emailcons'];
		$db_overwritebeta = $row['db_overwritebeta'];
		$db_fileprefix = $row['db_fileprefix'];
		$db_betanums = $row['db_betanums'];
		$db_threshold = $row['db_threshold'];
		$db_smoothkernel = $row['db_smoothkernel'];
		list($imcalcs_rows, $imcalcs_cols) = GetMatrixSize($row['db_imcalcs']);
		list($imnames_rows, $imnames_cols) = GetMatrixSize($row['db_imnames']);

		/* get task related information */
		$sqlstring = "select a.*, b.datadirpath from tasks a, server_datadirs b where a.taskid = $taskid and a.task_datadirid = b.id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$taskpath = $row['taskpath'];
		$taskname = $row['task_shortname'];
		$scandirpattern = $row['scandirpattern'];
		$rundirpattern = $row['rundirpattern'];
		$numdummyscans = $row['numdummyscans'];
		$tr = $row['tr'];
		$datadirpath = $row['datadirpath'];


		?>
		<style>
			.header { border-bottom: 2pt solid #555555; font-weight: bold; }
			.check { border-bottom: 1pt solid #999999; font-weight: bold; }
			.description { border-bottom: 1pt solid #999999; }
			.success { border-bottom: 1pt solid #999999; font-weight: bold; color: green; }
			.error { border-bottom: 1pt solid #999999; font-weight: bold; color: red; }
			td.errormessage { border-bottom: 1pt solid #999999; }
		</style>
		<table cellspacing="0" cellpadding="2">
			<tr>
				<td class="header" width="8%">&nbsp;</td>
				<td class="header" width="15%">Description</td>
				<td class="header">Result</td>
				<td class="header">Message</td>
			</tr>
			<tr>
				<td class="check">Check 1</td>
				<td class="description">Will it do anything?</td>
				<?
					if (($do_behmatchup == "0") && ($do_stats == "0") && ($do_autoslice == "0") && ($do_db == "0")) { echo "<td class='error'>Error</td><td class='errormessage'>Nothing is actually specified to run</td>"; }
					else { echo "<td class='success'>Success</td><td class='errormessage'>This script will actually do something</td>"; }
				?>
			</tr>
			<tr>
				<td class="check">Check 2</td>
				<td class="description">Behavioral files</td>
				<?
					if ($onset_cols == -1) { echo "<td class='error'>Error</td><td class='errormessage'>Your consecutive runs have different numbers of behavioral files</td>"; }
					else { echo "<td class='success'>Success</td><td class='errormessage'>You have $onset_rows runs, each with $onset_cols behavioral files</td>"; }
				?>
			</tr>
			<tr>
				<td class="check">Check 3</td>
				<td class="description">Regressor files</td>
				<?
					if ($regressorfiles_cols == -1) { echo "<td class='error'>Error</td><td class='errormessage'>Your consecutive runs have different numbers of regressor files</td>"; }
					else { echo "<td class='success'>Success</td><td class='errormessage'>You have $regressorfiles_rows runs, each with $regressorfiles_cols regressor files</td>"; }
				?>
			</tr>
			<tr>
				<td class="check">Check 4</td>
				<td class="description">Regressor names</td>
				<?
					if ($regressornames_cols == -1) { echo "<td class='error'>Error</td><td class='errormessage'>Your consecutive runs have different numbers of regressor names</td>"; }
					else { echo "<td class='success'>Success</td><td class='errormessage'>You have $regressornames_rows runs, each with $regressornames_cols regressor names</td>"; }
				?>
			</tr>
			<tr>
				<td class="check">Check 5</td>
				<td class="description">Parameter names</td>
				<?
					if ($paramnames_cols == -1) { echo "<td class='error'>Error</td><td class='errormessage'>Your consecutive runs have different numbers of parameter names</td>"; }
					else { echo "<td class='success'>Success</td><td class='errormessage'>You have $paramnames_rows runs, each with $paramnames_cols parameter names</td>"; }
				?>
			</tr>
			<tr>
				<td class="check">Check 6</td>
				<td class="description">Parameter orders</td>
				<?
					if ($paramorders_cols == -1) { echo "<td class='error'>Error</td><td class='errormessage'>Your consecutive runs have different numbers of parameter orders</td>"; }
					else { echo "<td class='success'>Success</td><td class='errormessage'>You have $paramorders_rows runs, each with $paramorders_cols parameter orders</td>"; }
				?>
			</tr>
			<tr>
				<td class="check">Check 7</td>
				<td class="description">Parameter files</td>
				<?
					if ($paramfiles_cols == -1) { echo "<td class='error'>Error</td><td class='errormessage'>Your consecutive runs have different numbers of parameter files</td>"; }
					else { echo "<td class='success'>Success</td><td class='errormessage'>You have $paramfiles_rows runs, each with $paramfiles_cols parameter files</td>"; }
				?>
			</tr>
			<tr>
				<td class="check">Check 8</td>
				<td class="description">Contrast names</td>
				<?
					if ($tcontrasts_rows != $tcontrastnames_rows) { echo "<td class='error'>Error</td><td class='errormessage'>The number of rows in the contrast matrix ($tcontrasts_rows) does not match the number of contrast names ($tcontrastnames_rows)</td>"; }
					else { echo "<td class='success'>Success</td><td class='errormessage'>The number of rows in the contrast matrix matches the number of contrast names</td>"; }
				?>
			</tr>
			<tr>
				<td class="check">Check 9</td>
				<td class="description">Contrasts</td>
				<?
					if ($tcontrasts_cols == -1) { echo "<td class='error'>Error</td><td class='errormessage'>Consecutive rows of the design matrix have different numbers of columns</td>"; }
					else { echo "<td class='success'>Success</td><td class='errormessage'>The number of columns is consistent between rows in the design matrix</td>"; }
				?>
			</tr>
			<tr>
				<td class="check">Check 10</td>
				<td class="description">Make ASCII files (1)</td>
				<?
					if ($stats_makeasciis == 0) { echo "<td class='error'>Error</td><td class='errormessage'>This script will not generate ASCII files</td>"; }
					else { echo "<td class='success'>Success</td><td class='errormessage'>This script will generate ASCII files</td>"; }
				?>
			</tr>
			<tr>
				<td class="check">Check 11</td>
				<td class="description">Make ASCII files (2)</td>
				<?
					if ($stats_asciiscriptpath == "") { echo "<td class='error'>Error</td><td class='errormessage'>ASCII script path is blank</td>"; }
					elseif (!file_exists("/mount$stats_asciiscriptpath")) { echo "<td class='error'>Error</td><td class='errormessage'>ASCII script does not exist</td>"; }
					else { echo "<td class='success'>Success</td><td class='errormessage'>The ASCII file generator script exists and will run</td>"; }
				?>
			</tr>
			<tr>
				<td class="check">Check 12</td>
				<td class="description">Contrast matrix size</td>
				<?
					if ($stats_basisfunction == "1") { /* HRF */
						$conmatsize = ($onset_rows * $onset_cols) + ($regressornames_cols * $onset_rows) + ($paramnames_rows * $onset_rows) + $onset_rows;
						$basisfunction = "HRF";
					}
					if ($stats_basisfunction == "2") { /* HRF + time deriv */
						$conmatsize = (($onset_rows * $onset_cols)*2) + ($regressornames_cols * $onset_rows) + ($paramnames_rows * $onset_rows) + $onset_rows;
						$basisfunction = "HRF + time deriv";
					}
					if ($stats_basisfunction == "3") { /* HRF + time deriv + dispersion deriv */
						$conmatsize = (($onset_rows * $onset_cols)*3) + ($regressornames_cols * $onset_rows) + ($paramnames_rows * $onset_rows) + $onset_rows;
						$basisfunction = "HRF + time deriv + dispersion deriv";
					}
					if ($stats_basisfunction == "7") { /* FIR */
						$conmatsize = (($onset_rows * $onset_cols)*7) + ($regressornames_cols * $onset_rows) + ($paramnames_rows * $onset_rows) + $onset_rows;
						$basisfunction = "FIR";
					}
					if ($conmatsize != $tcontrasts_cols) { echo "<td class='error'>Error</td><td class='errormessage'>You should have ($conmatsize) columns in your design matrix based on your previous information and specified basis function of $basisfunction, but you actually have ($tcontrasts_cols) columns</td>"; }
					else { echo "<td class='success'>Success</td><td class='errormessage'>You are using $basisfunction and your design matrix has the correct number of columns ($conmatsize)</td>"; }
				?>
			</tr>
		</table>
		<?
	}


	/* -------------------------------------------- */
	/* ------- GetMatrixSize ---------------------- */
	/* -------------------------------------------- */
	function GetMatrixSize($str) {
		if (trim($str) == "") {
			return array(0,0);
		}
		else {
			$numrows = 0;
			$rows = explode(";", $str);
			$ncols = -1;
			foreach ($rows as $row) {
				if (trim($row) != "") {
					$numrows++;
					$numcols = count(explode(",",$row));
					if ($ncols == -1) {
						$ncols = $numcols;
					}
					else {
						if ($ncols != $numcols) {
							/* rows don't have the same number of items... not good */
							return array($numrows,-1);
						}
					}
				}
			}
			return array($numrows,$numcols);
		}
	}


	/* -------------------------------------------- */
	/* ------- GetConMatrixSize ------------------- */
	/* -------------------------------------------- */
	function GetConMatrixSize($constr) {
		if (trim($constr) == "") {
			return array(0,0);
		}
		else {
			$constr = str_replace(array("%","\r\n","\n"),"",$constr);

			/* break the string into lines based on the semicolons */
			$lines = explode(";",$constr);
			$numlines = count($lines);

			$numrows = 0;
			$ncols = -1;
			/* go through each line and separate into columns, then reformat into tabbed columns */
			for($i=1; $i<$numlines; $i++) {
				$line = trim($lines[$i]);
				if ($line == "") {
					continue;
				}
				$numrows++;
				$numcols = count(preg_split("/[\s,]+/", $line));

				if ($ncols == -1) {
					$ncols = $numcols;
				}
				else {
					if ($ncols != $numcols) {
						/* rows don't have the same number of items... not good */
						return array($numrows,-1);
					}
				}
			}
			return array($numrows, $numcols);
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
					<img src="images/add16.png"> <a href="autocs_statsprefs.php?action=addform&taskid=<? echo $taskid; ?>" class="link">Add New</a>
				</td>
			</tr>
			<tr>
				<td>
					<form method="post" action="autocs_statsprefs.php">
					<input type="hidden" name="action" value="copytonew">
					<input type="hidden" name="taskid" value="<?=$taskid?>">
						<img src="images/copy16.png">
						<select name="oldprefid">
						<?
							$sqlstring = "SELECT a.shortname, b.task_shortname, a.id, b.task_autocsver FROM task_stats_prefs a left join tasks b on a.taskid = b.taskid WHERE a.enddate > now() and b.task_enddate > now() order by b.task_shortname";
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
				<td colspan="5" style="border-top: 2pt solid darkblue; text-align: left; font-weight: bold; background-color: lightyellow">Stats preferences</td>
			</tr>
			<tr>
				<td class="columnheaderleft">Name</td>
				<td class="columnheader">Description</td>
				<td class="columnheader">Check for errors</td>
				<td class="columnheader">Create date</td>
				<td class="columnheaderright">Delete</td>
			</tr>
			<?
				$sqlstring = "select * from task_stats_prefs where taskid = $taskid and enddate > now()";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$id = $row['id'];
						$createdate = $row['startdate'];
						$shortname = $row['shortname'];
						$description = $row['description'];
						?>
						<tr>
							<td><a href="autocs_statsprefs.php?action=display&id=<? echo $id; ?>">cs_prefs_<? echo $taskname; ?>_<? echo $shortname; ?></a></td>
							<td><? echo $description; ?></td>
							<td><a href="autocs_statsprefs.php?action=check&id=<? echo $id; ?>">check</a></td>
							<td><? echo $createdate; ?></td>
							<td align="center"><a href="autocs_statsprefs.php?action=delete&taskid=<? echo $taskid; ?>&id=<? echo $id; ?>" class="link" style="color: red">X</a></td>
						</tr>
						<?
					}
				}
				else {
					?>
					<tr>
						<td colspan="3" style="text-align: center">No stats preferences available</td>
					</tr>
					<?
				}
			?>
		</table>
		<?
	}

?>

<? include("footer.php") ?>
