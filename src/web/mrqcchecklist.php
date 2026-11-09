<?
 // ------------------------------------------------------------------------------
 // NiDB mrqcchecklist.php
 // Copyright (C) 2004 - 2026
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
// Contribution: Muhammad Asim Mubeen (Jul 2023) 
 // ------------------------------------------------------------------------------

	define("LEGIT_REQUEST", true);
	
	session_start();
	ob_start(); /* buffer output so POST/Redirect/GET (a header('Location') redirect) works despite the HTML rendered below */
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - MR QC Checklist</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	/* ----- setup variables ----- */
	/* mutating actions require POST + CSRF token */
	$mutating = array("updateqcparams", "updatemrparams", "loadmrparams");
	$action = GetSafeAction($mutating);
	$id = (int)GetVariable("id");   /* the project */
	$protocolfilter = trim((string)GetVariable("protocolfilter"));

	$param_rowid = GetVariable("param_rowid");
	$param_protocol = GetVariable("param_protocol");
	$param_sequence = GetVariable("param_sequence");

	$existingstudy = GetVariable("existingstudy");
	$existingseries = GetVariable("existingseries");

	/* The MR scan parameters and QC criteria are project definitions: any member of the project (any
	   permission on it) may view the QC checklist; Edit Data is needed to view and change the criteria */
	$ismember = false;
	$canedit = false;
	if ($id > 0) {
		$perms = GetCurrentUserProjectPermissions(array($id));
		$ismember = IsProjectMember($perms, $id);
		$canedit = (GetPerm($perms, 'modifydata', $id) == 1);
	}

	/* mutating actions use POST/Redirect/GET: run the handler, stash its message, then redirect to the
	   edit page as a GET so a refresh/Back doesn't re-submit the form */
	if (in_array($action, $mutating, true)) {
		ob_start();
		if (!$canedit) {
			Error("You do not have permission to edit this project's MR QC criteria");
		}
		else {
			switch ($action) {
				case 'updateqcparams':
					UpdateQCParams($id, $param_rowid, $param_protocol, GetVariable("param_x_max"), GetVariable("param_y_max"), GetVariable("param_z_max"), GetVariable("param_iosnr_min"), GetVariable("param_iosnr_max"), GetVariable("param_pvsnr_min"), GetVariable("param_pvsnr_max"));
					break;
				case 'updatemrparams':
					$ranges = array();
					foreach (MRScanParamNames() as $name) {
						$ranges[$name . '_min'] = GetVariable("param_" . $name . "_min");
						$ranges[$name . '_max'] = GetVariable("param_" . $name . "_max");
					}
					UpdateMRScanParams($id, $param_rowid, $param_protocol, $param_sequence, $ranges);
					break;
				case 'loadmrparams':
					LoadMRParams($id, $existingstudy, $existingseries);
					break;
			}
		}
		$_SESSION['flash'] = ob_get_clean();
		RedirectTo("mrqcchecklist.php?action=" . (($action == "updateqcparams") ? "editqcparams" : "editmrparams") . "&id=$id");
	}

	ShowFlashMessage();

	if ($id < 1) {
		Error("Invalid project ID");
	}
	elseif (!$ismember) {
		Error("You do not have permission to view this project's MR QC checklist");
	}
	else {
		switch ($action) {
			case 'editqcparams':
				if ($canedit) EditQCParams($id);
				else Error("You do not have permission to edit this project's MR QC criteria");
				break;
			case 'editmrparams':
				if ($canedit) EditMRScanParams($id);
				else Error("You do not have permission to edit this project's MR QC criteria");
				break;
			default:
				ViewQCParams($id, $protocolfilter, $canedit);
		}
	}


	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- MRQCQuery -------------------------- */
	/* -------------------------------------------- */
	/* runs a bound query; $types/$params as for mysqli_stmt_bind_param. Returns all rows */
	function MRQCQuery($sqlstring, $types = '', $params = array()) {
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		if ($types != '')
			mysqli_stmt_bind_param($stmt, $types, ...$params);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		$rows = array();
		if ($result && ($result !== true)) {
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
				$rows[] = $row;
		}
		mysqli_stmt_close($stmt);
		return $rows;
	}


	/* -------------------------------------------- */
	/* ------- MRScanParamNames ------------------- */
	/* -------------------------------------------- */
	/* the min/max ranges in mr_scanparams, in column order (each is <name>_min and <name>_max) */
	function MRScanParamNames() {
		return array('tr', 'te', 'ti', 'flip', 'xdim', 'ydim', 'zdim', 'tdim', 'slicethickness', 'slicespacing', 'bandwidth');
	}


	/* -------------------------------------------- */
	/* ------- ParamNumber ------------------------ */
	/* -------------------------------------------- */
	/* a form value as a number. The parameter columns are NOT NULL, so blank/non-numeric values are 0 */
	function ParamNumber($v) {
		$v = trim((string)$v);
		return is_numeric($v) ? round((float)$v, 3) : 0.0;
	}


	/* -------------------------------------------- */
	/* ------- LoadMRParams ----------------------- */
	/* -------------------------------------------- */
	/* adds the parameters of an MR study (UID + study number, e.g. S1234ABC5) in this project to the
	   project's expected MR scan parameters. Optionally only one series */
	function LoadMRParams($projectid, $study, $series) {
		$projectid = (int)$projectid;
		$study = trim((string)$study);
		$series = trim((string)$series);

		$uid = substr($study, 0, 8);
		$studynum = substr($study, 8);

		/* the study must be an MR study in this project */
		$rows = MRQCQuery("select c.study_id from subjects a join enrollment b on a.subject_id = b.subject_id join studies c on b.enrollment_id = c.enrollment_id where a.uid = ? and c.study_num = ? and c.study_modality = 'MR' and b.project_id = ?", 'ssi', [$uid, $studynum, $projectid]);
		if (count($rows) == 0) {
			Error("Invalid study ID [" . htmlspecialchars($study) . "]. Incorrect UID or study number, the study is not in this project, or it is not an MR study");
			return;
		}
		$studyid = (int)$rows[0]['study_id'];

		if ($series == "")
			$seriesrows = MRQCQuery("select * from mr_series where study_id = ?", 'i', [$studyid]);
		else
			$seriesrows = MRQCQuery("select * from mr_series where study_id = ? and series_num = ?", 'is', [$studyid, $series]);
		if (count($seriesrows) == 0) {
			Error("No MR series found for [" . htmlspecialchars($study) . "]");
			return;
		}

		/* each series becomes a row with min = max = the series' value */
		$columns = array('tr' => 'series_tr', 'te' => 'series_te', 'ti' => 'series_ti', 'flip' => 'series_flip', 'xdim' => 'dimX', 'ydim' => 'dimY', 'zdim' => 'dimZ', 'tdim' => 'dimT', 'slicethickness' => 'slicethickness', 'slicespacing' => 'slicespacing', 'bandwidth' => 'bandwidth');
		$rowids = array();
		$protocols = array();
		$sequences = array();
		$ranges = array();
		$msgs = array();
		foreach ($seriesrows as $row) {
			$protocol = (($row['series_desc'] ?? '') != "") ? $row['series_desc'] : ($row['series_protocol'] ?? '');
			$rowids[] = "";
			$protocols[] = $protocol;
			$sequences[] = $row['series_sequencename'] ?? '';
			foreach ($columns as $name => $col) {
				$ranges[$name . '_min'][] = (float)($row[$col] ?? 0);
				$ranges[$name . '_max'][] = (float)($row[$col] ?? 0);
			}
			$msgs[] = "Adding row [" . htmlspecialchars($protocol) . "]";
		}

		UpdateMRScanParams($projectid, $rowids, $protocols, $sequences, $ranges);
		Notice(implode("<br>", $msgs));
	}


	/* -------------------------------------------- */
	/* ------- UpdateQCParams --------------------- */
	/* -------------------------------------------- */
	/* saves the QC criteria rows. A row with a blank protocol is deleted. Only this project's rows change */
	function UpdateQCParams($projectid, $param_rowid, $param_protocol, $param_x_max, $param_y_max, $param_z_max, $param_iosnr_min, $param_iosnr_max, $param_pvsnr_min, $param_pvsnr_max) {
		$projectid = (int)$projectid;
		foreach (array('param_rowid', 'param_protocol', 'param_x_max', 'param_y_max', 'param_z_max', 'param_iosnr_min', 'param_iosnr_max', 'param_pvsnr_min', 'param_pvsnr_max') as $var) {
			if (!is_array($$var)) $$var = array();
		}

		foreach ($param_rowid as $i => $paramid) {
			$paramid = (int)$paramid;
			$protocol = trim((string)($param_protocol[$i] ?? ''));
			$values = array(ParamNumber($param_x_max[$i] ?? ''), ParamNumber($param_y_max[$i] ?? ''), ParamNumber($param_z_max[$i] ?? ''), ParamNumber($param_iosnr_min[$i] ?? ''), ParamNumber($param_iosnr_max[$i] ?? ''), ParamNumber($param_pvsnr_min[$i] ?? ''), ParamNumber($param_pvsnr_max[$i] ?? ''));

			if ($protocol != "") {
				if ($paramid == 0)
					MRQCQuery("insert ignore into mr_qcparams (protocol_name, project_id, max_x, max_y, max_z, min_iosnr, max_iosnr, min_pvsnr, max_pvsnr) values (?, ?, ?, ?, ?, ?, ?, ?, ?)", 'siddddddd', array_merge([$protocol, $projectid], $values));
				else
					MRQCQuery("update ignore mr_qcparams set protocol_name = ?, max_x = ?, max_y = ?, max_z = ?, min_iosnr = ?, max_iosnr = ?, min_pvsnr = ?, max_pvsnr = ? where mrqcparam_id = ? and project_id = ?", 'sdddddddii', array_merge([$protocol], $values, [$paramid, $projectid]));
			}
			elseif ($paramid != 0) {
				MRQCQuery("delete from mr_qcparams where mrqcparam_id = ? and project_id = ?", 'ii', [$paramid, $projectid]);
			}
		}
		Notice("QC criteria updated");
	}


	/* -------------------------------------------- */
	/* ------- UpdateMRScanParams ----------------- */
	/* -------------------------------------------- */
	/* saves the expected MR scan parameter rows. $ranges is [<name>_min|_max => [row => value]] for the
	   names in MRScanParamNames(). A row with a blank protocol is deleted. Only this project's rows change */
	function UpdateMRScanParams($projectid, $param_rowid, $param_protocol, $param_sequence, $ranges) {
		$projectid = (int)$projectid;
		$param_rowid = is_array($param_rowid) ? $param_rowid : array();
		$param_protocol = is_array($param_protocol) ? $param_protocol : array();
		$param_sequence = is_array($param_sequence) ? $param_sequence : array();

		$cols = array();
		foreach (MRScanParamNames() as $name) {
			$cols[] = $name . '_min';
			$cols[] = $name . '_max';
		}
		$numtypes = str_repeat('d', count($cols));

		foreach ($param_rowid as $i => $paramid) {
			$paramid = (int)$paramid;
			$protocol = trim((string)($param_protocol[$i] ?? ''));
			$sequence = trim((string)($param_sequence[$i] ?? ''));
			$values = array();
			foreach ($cols as $col)
				$values[] = ParamNumber((is_array($ranges[$col] ?? null) ? ($ranges[$col][$i] ?? '') : ''));

			if ($protocol != "") {
				if ($paramid == 0) {
					$sqlstring = "insert ignore into mr_scanparams (protocol_name, sequence_name, project_id, " . implode(", ", $cols) . ") values (?, ?, ?, " . implode(", ", array_fill(0, count($cols), "?")) . ")";
					MRQCQuery($sqlstring, 'ssi' . $numtypes, array_merge([$protocol, $sequence, $projectid], $values));
				}
				else {
					$sqlstring = "update ignore mr_scanparams set protocol_name = ?, sequence_name = ?, " . implode(" = ?, ", $cols) . " = ? where mrscanparam_id = ? and project_id = ?";
					MRQCQuery($sqlstring, 'ss' . $numtypes . 'ii', array_merge([$protocol, $sequence], $values, [$paramid, $projectid]));
				}
			}
			elseif ($paramid != 0) {
				MRQCQuery("delete from mr_scanparams where mrscanparam_id = ? and project_id = ?", 'ii', [$paramid, $projectid]);
			}
		}
		Notice("MR scan parameters updated");
	}


	/* -------------------------------------------- */
	/* ------- ViewQCParams ----------------------- */
	/* -------------------------------------------- */
	function ViewQCParams($id, $protocolfilter, $canedit) {
		$id = (int)$id;
		$protocolfilter = trim((string)$protocolfilter);

		/* get all of the MR params for this project */
		$parms = array();
		$i = 0;
		foreach (MRQCQuery("select * from mr_scanparams where project_id = ? order by protocol_name", 'i', [$id]) as $row) {
			$p = $row['protocol_name'];
			$parms[$p][$i]['sequence'] = $row['sequence_name'];
			foreach (MRScanParamNames() as $name) {
				$parms[$p][$i][$name . '_min'] = number_format((float)$row[$name . '_min'], 3, '.', '');
				$parms[$p][$i][$name . '_max'] = number_format((float)$row[$name . '_max'], 3, '.', '');
			}
			$i++;
		}

		$qcparms = array();
		foreach (MRQCQuery("select * from mr_qcparams where project_id = ?", 'i', [$id]) as $row) {
			foreach (array('max_x', 'max_y', 'max_z', 'min_iosnr', 'max_iosnr', 'min_pvsnr', 'max_pvsnr') as $col)
				$qcparms[$row['protocol_name']][$col] = number_format((float)$row[$col], 3, '.', '');
		}

		/* get list of studies, and then series, associated with this project */
		$studies = MRQCQuery("select c.study_id, c.study_num, a.uid, a.subject_id, b.enrollment_id from subjects a join enrollment b on a.subject_id = b.subject_id join studies c on b.enrollment_id = c.enrollment_id where b.project_id = ? and c.study_modality = 'MR' order by a.uid, c.study_num", 'i', [$id]);
		if (count($studies) == 0) {
			echo "Found no valid MR studies for this project<br>";
			return;
		}

		DisplayProtocolDataListForProject($id, "protocollist");
		?>
		<div class="ui two column grid">
			<div class="column">
				<h2 class="ui header">MR scan quality control</h2>
			</div>
			<div class="right aligned column">
				<? if ($canedit) { ?>
				<a class="ui primary button" href="mrqcchecklist.php?action=editmrparams&id=<?=$id?>"><i class="edit icon"></i> Edit expected MR parameters</a>
				<a class="ui primary button" href="mrqcchecklist.php?action=editqcparams&id=<?=$id?>"><i class="edit icon"></i> Edit QC criteria</a>
				<? } ?>
			</div>
		</div>
		<div class="ui long scrolling fluid container">
		<table class="ui head stuck celled selectable grey compact stuck table" >
			<thead>
				<th colspan="14">
				<form action="mrqcchecklist.php" method="get">
				<input type="hidden" name="action" value="viewqcparams">
				<input type="hidden" name="id" value="<?=$id?>">
				Filter by protocol name <input type="input" name="protocolfilter" value="<?=htmlspecialchars($protocolfilter)?>" list="protocollist"> <input type="submit" value="Filter">
				</form>
				</th>
				<tr>
				   <th><b>UID</b></th>
				   <th><b>Primary alt UID</b></th>
				   <th><b>Study No.</b></th>
				   <th><b>Series No.</b></th>
				   <th><b>Series Desc.</b></th>
				   <th><b>Params good?</b></th>
				   <th><b>Files on disk?</b></th>
				   <th><b>Num Files</b></th>
				   <th><b>Avg Rating</b></th>
				   <th><b>Basic QC?</b></th>
				   <th title="Displacement (X,Y,Z), Velocity in (X,Y,Z) direction, Rotation (Pitch,Roll,Yaw)"><b>Disp(X,Y,Z), Mot(X,Y,Z), Rot(P,R,Y)</b></th>
				   <th><b>SNR</b></th>
				   <th><b>FD</b></th>
				   <th><b>DVARS</b></th>
				</tr>
			</thead>
			<tbody>
		<?
		$rangelabels = array('tr' => 'TR', 'te' => 'TE', 'ti' => 'TI', 'flip' => 'Flip angle', 'xdim' => 'dim X', 'ydim' => 'dim Y', 'zdim' => 'dim Z', 'tdim' => 'dim T', 'slicethickness' => 'Slice thickness', 'slicespacing' => 'Slice spacing', 'bandwidth' => 'Bandwidth');
		$seriescolumns = array('tr' => 'series_tr', 'te' => 'series_te', 'ti' => 'series_ti', 'flip' => 'series_flip', 'xdim' => 'dimX', 'ydim' => 'dimY', 'zdim' => 'dimZ', 'tdim' => 'dimT', 'slicethickness' => 'slicethickness', 'slicespacing' => 'slicespacing', 'bandwidth' => 'bandwidth');

		foreach ($studies as $row) {
			$studyid = (int)$row['study_id'];
			$subjectid = (int)$row['subject_id'];
			$enrollmentid = (int)$row['enrollment_id'];
			$uid = htmlspecialchars((string)$row['uid']);
			$studynum = htmlspecialchars((string)$row['study_num']);
			if ($studyid < 1) continue;

			/* get the mr_series rows */
			if ($protocolfilter == "")
				$seriesrows = MRQCQuery("select * from mr_series where study_id = ? order by series_num", 'i', [$studyid]);
			else
				$seriesrows = MRQCQuery("select * from mr_series where study_id = ? and (series_desc = ? or series_protocol = ?) order by series_num", 'iss', [$studyid, $protocolfilter, $protocolfilter]);
			if (count($seriesrows) == 0) continue;

			/* get project specific altuid */
			$altrows = MRQCQuery("select altuid from subject_altuid where subject_id = ? and enrollment_id = ? and isprimary = 1", 'ii', [$subjectid, $enrollmentid]);
			$altuid = htmlspecialchars((string)($altrows[0]['altuid'] ?? ''));

			$count = 0; /* counter to display the alternate ID once */
			foreach ($seriesrows as $rowA) {
				$seriesid = (int)$rowA['mrseries_id'];
				$seriesnum = htmlspecialchars((string)$rowA['series_num']);
				$p1 = (string)$rowA['series_desc'];
				$numfiles = (int)$rowA['numfiles'];
				$values = array();
				foreach ($seriescolumns as $name => $col)
					$values[$name] = number_format((float)$rowA[$col], 3, '.', '');
				list($datapath, $seriespath, $junk0, $junk1, $junk2, $junk3, $junk4) = GetDataPathFromSeriesID($seriesid, 'mr');

				$count++;
				$rowcolor = ($count == 1) ? "positive" : "";
				?>
				<tr class="<?=$rowcolor?>">
				<? if ($count == 1) { ?>
					<td><b> <?=$uid?></b></td>
					<td><b style="background-color: darkred; color: white; padding: 2px 8px"><?=$altuid?></b> </td>
					<td><a href="studies.php?id=<?=$studyid?>"><b><?=$uid?><?=$studynum?></b></a></td>
					<td> &nbsp;<b><?=$seriesnum?></b></td>
					<td><b><?=htmlspecialchars($p1)?></b>&nbsp;</td>
				<? } else { ?>
					<td><?=$uid?></td>
					<td><?=$altuid?></td>
					<td><a href="studies.php?id=<?=$studyid?>"><?=$uid?><?=$studynum?></a></td>
					<td> &nbsp; <?=$seriesnum?></td>
					<td> <?=htmlspecialchars($p1)?>&nbsp;</td>
				<? }

				/* ----- check for MR pulse sequence parameter matching ----- */
				$matched = 0;
				$msgs = array();
				/* check if the params in this series match any of the rows in the expected params table */
				if (array_key_exists($p1, $parms)) {
					foreach ($parms[$p1] as $i => $pm) {
						$bad = 0;
						foreach ($rangelabels as $name => $label) {
							$min = $pm[$name . '_min'];
							$max = $pm[$name . '_max'];
							$val = $values[$name];
							$b = between($val, $min, $max);
							if (!$b) { $bad = 1; $msgs[] = "[$i] $label of $val not within range ($min - $max)"; }
							elseif ($b == -1) { $bad = -1; $msgs[] = "[$i] $label param is blank ([$min], [$max])"; }
						}
						if ($bad == -1) { $matched = -1; }
						if (!$bad) { $matched = 1; break; }
					}
				}
				else {
					$matched = -2;
				}

				if ($matched == 1) { /* passed the checks */
					?><td style="padding-left: 8px; background-color: #cbf7be"> </td><?
				}
				elseif ($matched == -2) { /* missing parameter criteria */
					?><td style="padding-left: 8px; background-color: lightyellow;" title="No MR parameter criteria specified for this protocol"> <i class="exclamation icon"></i> </td><?
				}
				elseif ($matched == -1) { /* ambiguous or missing parameter criteria */
					?><td style="padding-left: 8px;" title="Parameter criteria is blank">?</td><?
				}
				else { /* failed the checks */
					?>
					<td style="padding-left: 8px; background-color: #ffddd1; font-size:8pt; color: #666">
					<details>
					<summary>out of spec</summary>
					<ul>
					<li><?=implode('<li>', array_map('htmlspecialchars', $msgs))?>
					</ul>
					</details>
					</td>
					<?
				}

				/* check for files on disk */
				$fcount = 0;
				$files = glob($datapath . "/*");
				if ($files) { $fcount = count($files); }
				$datapathattr = htmlspecialchars((string)$datapath);
				if ($fcount == $numfiles) {
					?><td style="padding-left: 8px; background-color: #cbf7be"><i class="folder icon"></i></td><?
				}
				elseif ($fcount == 0) {
					?><td style="padding-left: 8px; background-color: #ffddd1;" title="Disk location [<?=$datapathattr?>]"><i class="exclamation icon"></i>No file found</td><?
				}
				else {
					?><td style="padding-left: 8px; background-color: lightyellow;" title="Filecount in database [<?=$numfiles?>] does not match that on disk [<?=$fcount?>] from [<?=$datapathattr?>]"><i class="folder minus icon"></i>File count mismatch</td><?
				}
				?>
				<td><?=$numfiles?></td>
				<?

				/* check user ratings: 5 = bad, 6 = test series */
				$ratingarray = array();
				foreach (MRQCQuery("select rating_value from ratings where rating_type = 'series' and data_modality = 'MR' and data_id = ?", 'i', [$seriesid]) as $row3)
					$ratingarray[] = $row3['rating_value'];
				$cellcolor = '';
				$ratingavg = '';
				$ratingcount = count($ratingarray);
				if ($ratingcount > 0) {
					if (in_array(5, $ratingarray)) $cellcolor = "#ffddd1";
					if (in_array(6, $ratingarray)) $cellcolor = "lightyellow";
					$ratingavg = array_sum($ratingarray) / $ratingcount;
				}
				?><td style="padding-left: 8px; background-color:<?=$cellcolor?>"><?=$ratingavg?> <? if ($ratingavg !== "") { ?><span class="tiny">(<?=$ratingcount?>)</span><? } ?></td>
				<?

				/* check basic QC */
				$qarows = MRQCQuery("select (move_maxx-move_minx) 'movex', (move_maxy-move_miny) 'movey', (move_maxz-move_minz) 'movez', (acc_maxx-acc_minx) 'accx', (acc_maxy-acc_miny) 'accy',(acc_maxz-acc_minz) 'accz',(rot_maxp-rot_minp) 'rotp', (rot_maxr-rot_minr) 'rotr',(rot_maxy-rot_miny) 'roty', io_snr, pv_snr, fd_mean, dvars_mean from mr_qa where mrseries_id = ?", 'i', [$seriesid]);
				$rowB = $qarows[0] ?? array();
				$qa = array();
				foreach (array('movex', 'movey', 'movez', 'accx', 'accy', 'accz', 'rotp', 'rotr', 'roty', 'io_snr', 'pv_snr', 'fd_mean', 'dvars_mean') as $col)
					$qa[$col] = number_format((float)($rowB[$col] ?? 0), 1);

				$msgs = array();
				if (array_key_exists($p1, $qcparms)) {
					$qc = $qcparms[$p1];
					if ($qa['movex'] > $qc['max_x']) $msgs[] = "Motion x: " . $qa['movex'] . " > (" . $qc['max_x'] . ")";
					if ($qa['movey'] > $qc['max_y']) $msgs[] = "Motion y: " . $qa['movey'] . " > (" . $qc['max_y'] . ")";
					if ($qa['movez'] > $qc['max_z']) $msgs[] = "Motion z: " . $qa['movez'] . " > (" . $qc['max_z'] . ")";
					if (($qa['io_snr'] > $qc['max_iosnr']) || ($qa['io_snr'] < $qc['min_iosnr'])) $msgs[] = "IO SNR of " . $qa['io_snr'] . " not in range (" . $qc['min_iosnr'] . " - " . $qc['max_iosnr'] . ")";
					if (($qa['pv_snr'] > $qc['max_pvsnr']) || ($qa['pv_snr'] < $qc['min_pvsnr'])) $msgs[] = "PV SNR of " . $qa['pv_snr'] . " not in range (" . $qc['min_pvsnr'] . " - " . $qc['max_pvsnr'] . ")";

					if (count($msgs) > 0) {
						?>
						<td style="border: 1px solid #aaa; padding-left: 8px; background-color: #ffddd1; font-size:8pt; color: #666" title="<?=htmlspecialchars(implode("\n", $msgs))?>">
						<i class="exclamation icon"></i>
						Out of spec
						</td>
						<?
					}
					else {
						?><td style="border: 1px solid #aaa; padding-left: 8px; background-color: #cbf7be"> </td><?
					}
				}
				else {
					?><td style="border: 1px solid #aaa; background-color: lightyellow; padding-left: 8px;" title="QC criteria not specified"> <i class="exclamation icon"></i> </td><?
				}
				?>
				<td>(<?=$qa['movex']?>,<?=$qa['movey']?>,<?=$qa['movez']?>),&nbsp;(<?=$qa['accx']?>,<?=$qa['accy']?>,<?=$qa['accz']?>),&nbsp;(<?=$qa['rotp']?>,<?=$qa['rotr']?>,<?=$qa['roty']?>)</td>
				<td><?=$qa['io_snr']?></td>
				<td><?=$qa['fd_mean']?></td>
				<td><?=$qa['dvars_mean']?></td>
				</tr>
				<?
			}
		}
		?>
			</tbody>
		</table>

		<div align="right"><input class="ui primary button" type="submit" value="Save As CSV" onMouseDown="tabletocsv();" style="font-size: 14pt; font-weight: bold"></div>

		<script type="text/javascript">
			function tabletocsv() {
				/* the final csv data */
				var csv_data = [];

				/* get each row's data */
				var rows = document.getElementsByTagName('tr');
				for (var i = 1; i < rows.length; i++) {
					var cols = rows[i].querySelectorAll('td,th');
					var csvrow = [];
					for (var j = 0; j < cols.length; j++) {
						var txt = cols[j].innerText;

						/* remove the parentheses from the displacement, motion, and rotation data */
						var newtxt = "";
						for (var k = 0; k < txt.length; k++) {
							if ((txt[k] == "(" || txt[k] == ")") && j > 9)
								newtxt += '';
							else
								newtxt += txt[k];
						}
						/* the displacement, motion, and rotation column titles */
						if (i == 1 && txt.slice(0,4) == "Disp")
							newtxt = 'DispX,DispY,DispZ,MotX,MotY,MotZ,RotP,RotR,RotY';
						csvrow.push(newtxt);
					}
					csv_data.push(csvrow.join(","));
				}
				downloadCSVFile(csv_data.join('\n'));
			}

			function downloadCSVFile(csv_data) {
				var CSVFile = new Blob(['﻿' + csv_data], { type: "text/csv" });

				/* a temporary hidden link to start the download */
				var temp_link = document.createElement('a');
				temp_link.download = "MRQuality" + new Date().getTime() + ".csv";
				temp_link.href = window.URL.createObjectURL(CSVFile);
				temp_link.style.display = "none";
				document.body.appendChild(temp_link);
				temp_link.click();
				document.body.removeChild(temp_link);
			}
		</script>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- EditQCParams ----------------------- */
	/* -------------------------------------------- */
	function EditQCParams($id) {
		$id = (int)$id;

		DisplayQCParamHeader($id);

		/* get all of the existing QC criteria */
		foreach (MRQCQuery("select * from mr_qcparams where project_id = ? order by protocol_name", 'i', [$id]) as $row)
			DisplayQCParamLine($row['mrqcparam_id'], $row['protocol_name'], $row['max_x'], $row['max_y'], $row['max_z'], $row['min_iosnr'], $row['max_iosnr'], $row['min_pvsnr'], $row['max_pvsnr']);

		for ($i=0;$i<5;$i++) {
			DisplayQCParamLine();
		}
		?>
		</table>
		<a class="ui button" href="mrqcchecklist.php?action=viewqcparams&id=<?=$id?>">Back</a>
		<input type="submit" value="Update" class="ui primary button">
		</form>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayQCParamHeader --------------- */
	/* -------------------------------------------- */
	function DisplayQCParamHeader($projectid) {
		$projectid = (int)$projectid;

		DisplayProtocolDataListForProject($projectid, "protocollist");
		?>
		<br><br>
		<form action="mrqcchecklist.php" method="post">
		<input type="hidden" name="action" value="updateqcparams">
		<input type="hidden" name="id" value="<?=$projectid?>">
		<?=CSRFTokenField()?>
		<table class="ui small celled selectable grey very compact table">
			<thead>
				<tr>
					<th>Protocol<br><span class="tiny">Leave blank to remove the row</span></th>
					<th>Motion X</th>
					<th>Motion Y</th>
					<th>Motion Z</th>
					<th>IO SNR</th>
					<th>PV SNR</th>
				</tr>
			</thead>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayQCParamLine ----------------- */
	/* -------------------------------------------- */
	function DisplayQCParamLine($rowid="", $protocol="", $x_max="", $y_max="", $z_max="", $iosnr_min="", $iosnr_max="", $pvsnr_min="", $pvsnr_max="") {
		$h = function($v) { return htmlspecialchars((string)$v); };
		?><tr>
			<input type="hidden" name="param_rowid[]" value="<?=$h($rowid)?>">
			<td><input type="text" name="param_protocol[]" value="<?=$h($protocol)?>" list="protocollist"></td>
			<td style="padding: 2px 15px"><input type="text" style="width: 45px" maxlength="8" name="param_x_max[]" value="<?=$h($x_max)?>"></td>
			<td style="padding: 2px 15px"><input type="text" style="width: 45px" maxlength="8" name="param_y_max[]" value="<?=$h($y_max)?>"></td>
			<td style="padding: 2px 15px"><input type="text" style="width: 45px" maxlength="8" name="param_z_max[]" value="<?=$h($z_max)?>"></td>
			<td style="padding: 2px 15px"><input type="text" style="width: 45px" maxlength="8" name="param_iosnr_min[]" value="<?=$h($iosnr_min)?>">&nbsp;<input type="text" style="width: 45px" maxlength="8" name="param_iosnr_max[]" value="<?=$h($iosnr_max)?>"></td>
			<td style="padding: 2px 15px"><input type="text" style="width: 45px" maxlength="8" name="param_pvsnr_min[]" value="<?=$h($pvsnr_min)?>">&nbsp;<input type="text" style="width: 45px" maxlength="8" name="param_pvsnr_max[]" value="<?=$h($pvsnr_max)?>"></td>
		</tr><?
	}


	/* -------------------------------------------- */
	/* ------- EditMRScanParams ------------------- */
	/* -------------------------------------------- */
	function EditMRScanParams($projectid) {
		$projectid = (int)$projectid;

		DisplayMRScanParamHeader($projectid);

		/* get all of the existing scan parameters */
		foreach (MRQCQuery("select * from mr_scanparams where project_id = ? order by protocol_name, sequence_name", 'i', [$projectid]) as $row)
			DisplayMRScanParamLine($row);

		for ($i=0;$i<5;$i++) {
			DisplayMRScanParamLine();
		}
		?>
		</table>
		<a class="ui button" href="mrqcchecklist.php?action=viewqcparams&id=<?=$projectid?>">Back</a>
		<input type="submit" value="Update" class="ui primary button">
		</form>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayMRScanParamHeader ----------- */
	/* -------------------------------------------- */
	function DisplayMRScanParamHeader($projectid) {
		$projectid = (int)$projectid;
		?>
		<br><br>
		<fieldset>
			<legend>Add scan parameters from an existing study in this project</legend>
			<form method="post" action="mrqcchecklist.php">
			<input type="hidden" name="action" value="loadmrparams">
			<input type="hidden" name="id" value="<?=$projectid?>">
			<?=CSRFTokenField()?>
			<input type="text" name="existingstudy" required> &nbsp; <input type="submit" value="Load Parameters"><br>
			<span class="tiny">Enter study ID in the format <u>S1234ABC5</u></span>
			</form>
		</fieldset>
		<br><br>
		<form action="mrqcchecklist.php" method="post">
		<input type="hidden" name="action" value="updatemrparams">
		<input type="hidden" name="id" value="<?=$projectid?>">
		<?=CSRFTokenField()?>
		<table class="ui small celled selectable grey very compact table">
			<thead>
				<th>Protocol<br><span class="tiny">Leave blank to remove the row</span></th>
				<th>Sequence</th>
				<th>TR</th>
				<th>TE</th>
				<th>TI</th>
				<th>Flip &ang;</th>
				<th>X dim</th>
				<th>Y dim</th>
				<th>Z dim</th>
				<th>T dim<br><span class="tiny">#BOLD reps</span></th>
				<th>Slice thickness</th>
				<th>Spacing between<br>slice centers</th>
				<th>Bandwidth</th>
			</thead>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayMRScanParamLine ------------- */
	/* -------------------------------------------- */
	/* one editable row; $row is an mr_scanparams row, or empty for a blank row */
	function DisplayMRScanParamLine($row = array()) {
		$h = function($key) use ($row) { return htmlspecialchars((string)($row[$key] ?? '')); };
		$dims = array('xdim', 'ydim', 'zdim', 'tdim');
		?><tr>
			<input type="hidden" name="param_rowid[]" value="<?=$h('mrscanparam_id')?>">
			<td><input type="text" name="param_protocol[]" value="<?=$h('protocol_name')?>"></td>
			<td><input type="text" name="param_sequence[]" value="<?=$h('sequence_name')?>"></td>
			<? foreach (MRScanParamNames() as $name) {
				if (in_array($name, $dims, true)) { ?>
			<td style="padding: 2px 15px"><input type="number" style="width: 55px" name="param_<?=$name?>_min[]" value="<?=$h($name . '_min')?>">&nbsp;<input type="number" style="width: 55px" name="param_<?=$name?>_max[]" value="<?=$h($name . '_max')?>"></td>
				<? } else { ?>
			<td style="padding: 2px 15px"><input type="text" style="width: 45px" maxlength="8" name="param_<?=$name?>_min[]" value="<?=$h($name . '_min')?>">&nbsp;<input type="text" style="width: 45px" maxlength="8" name="param_<?=$name?>_max[]" value="<?=$h($name . '_max')?>"></td>
				<? }
			} ?>
		</tr><?
	}


	/* -------------------------------------------- */
	/* ------- DisplayProtocolDataListForProject -- */
	/* -------------------------------------------- */
	function DisplayProtocolDataListForProject($projectid, $datalistid) {
		$rows = MRQCQuery("select distinct(a.series_desc) from mr_series a join studies b on a.study_id = b.study_id join enrollment c on b.enrollment_id = c.enrollment_id where c.project_id = ? order by a.series_desc", 'i', [(int)$projectid]);
		?><datalist id="<?=htmlspecialchars($datalistid)?>"><?
		foreach ($rows as $row) {
			?><option value="<?=htmlspecialchars((string)$row['series_desc'])?>"><?
		}
		?></datalist><?
	}

?>

<? include("footer.php") ?>
