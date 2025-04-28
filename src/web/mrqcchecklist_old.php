<?
 // ------------------------------------------------------------------------------
 // NiDB mrqcchecklist.php
 // Copyright (C) 2004 - 2022
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
	$action = GetVariable("action");
	$id = GetVariable("id");
	$projectid = GetVariable("projectid");
	$itemid = GetVariable("itemid");
	$itemorder = GetVariable("itemorder");
	$itemname = GetVariable("itemname");
	$modality = GetVariable("modality");
	$protocol = GetVariable("protocol");
	$itemcount = GetVariable("itemcount");
	$frequency = GetVariable("frequency");
	$frequencyunit = GetVariable("frequencyunit");
	$enrollmentid = GetVariable("enrollmentid");
	$projectchecklistid = GetVariable("projectchecklistid");
	$reason = GetVariable("reason");
	$missingdataid = GetVariable("missingdataid");

	$protocolfilter = GetVariable("protocolfilter");
	
	$param_rowid = GetVariable("param_rowid");
	$param_protocol = GetVariable("param_protocol");
	$param_sequence = GetVariable("param_sequence");
	$param_x_max = GetVariable("param_x_max");
	$param_y_max = GetVariable("param_y_max");
	$param_z_max = GetVariable("param_z_max");
	$param_iosnr_min = GetVariable("param_iosnr_min");
	$param_iosnr_max = GetVariable("param_iosnr_max");
	$param_pvsnr_min = GetVariable("param_pvsnr_min");
	$param_pvsnr_max = GetVariable("param_pvsnr_max");

	$param_tr_min = GetVariable("param_tr_min");
	$param_tr_max = GetVariable("param_tr_max");
	$param_te_min = GetVariable("param_te_min");
	$param_te_max = GetVariable("param_te_max");
	$param_ti_min = GetVariable("param_ti_min");
	$param_ti_max = GetVariable("param_ti_max");
	$param_flip_min = GetVariable("param_flip_min");
	$param_flip_max = GetVariable("param_flip_max");
	$param_xdim_min = GetVariable("param_xdim_min");
	$param_xdim_max = GetVariable("param_xdim_max");
	$param_ydim_min = GetVariable("param_ydim_min");
	$param_ydim_max = GetVariable("param_ydim_max");
	$param_zdim_min = GetVariable("param_zdim_min");
	$param_zdim_max = GetVariable("param_zdim_max");
	$param_tdim_min = GetVariable("param_tdim_min");
	$param_tdim_max = GetVariable("param_tdim_max");
	$param_slicethickness_min = GetVariable("param_slicethickness_min");
	$param_slicethickness_max = GetVariable("param_slicethickness_max");
	$param_slicespacing_min = GetVariable("param_slicespacing_min");
	$param_slicespacing_max = GetVariable("param_slicespacing_max");
	$param_bandwidth_min = GetVariable("param_bandwidth_min");
	$param_bandwidth_max = GetVariable("param_bandwidth_max");

	$existingstudy = GetVariable("existingstudy");
	$existingseries = GetVariable("existingseries");

	/* determine action */
	switch ($action) {
		case 'editqcparams':
			EditQCParams($id);
			break;
		case 'updateqcparams':
			UpdateQCParams($id, $param_rowid, $param_protocol, $param_x_max, $param_y_max, $param_z_max, $param_iosnr_min, $param_iosnr_max, $param_pvsnr_min, $param_pvsnr_max);
			EditQCParams($id);
			break;
		case 'loadqcparams':
			LoadQCParams($id, $existingstudy, $existingseries);
			EditQCParams($id);
			break;
		case 'viewqcparams':
			ViewQCParams($id, $protocolfilter);
			break;
		case 'editmrparams':
			EditMRScanParams($id);
			break;
		case 'updatemrparams':
			UpdateMRScanParams($id, $param_rowid, $param_protocol, $param_sequence, $param_tr_min, $param_tr_max, $param_te_min, $param_te_max, $param_ti_min, $param_ti_max, $param_flip_min, $param_flip_max, $param_xdim_min, $param_xdim_max, $param_ydim_min, $param_ydim_max, $param_zdim_min, $param_zdim_max, $param_tdim_min, $param_tdim_max, $param_slicethickness_min, $param_slicethickness_max, $param_slicespacing_min, $param_slicespacing_max, $param_bandwidth_min, $param_bandwidth_max);
			EditMRScanParams($id);
			break;
		case 'loadmrparams':
			LoadMRParams($id, $existingstudy, $existingseries);
			EditMRScanParams($id);
			break;
		case 'viewmrparams':
			ViewMRParams($id);
			break;
			break;
		default:
			ViewQCParams($id, $protocolfilter);
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	/* -------------------------------------------- */
	/* ------- LoadQCParams ----------------------- */
	/* -------------------------------------------- */
	function LoadQCParams($id, $study, $series) {
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
		if (!isInteger($id)) { echo "Invalid project ID [$id]"; return; }
		$study = mysqli_real_escape_string($GLOBALS['linki'], trim($study));
		$series = mysqli_real_escape_string($GLOBALS['linki'], trim($series));
		
		$uid = substr($study,0,8);
		$studynum = substr($study,8);
		
		/* check if its a valid subject, valid study num, and is MR modality */
		$sqlstring = "select c.study_id from subjects a left join enrollment b on a.subject_id = b.subject_id left join studies c on b.enrollment_id = c.enrollment_id where a.uid = '$uid' and c.study_num = '$studynum' and c.study_modality = 'MR'";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0){
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$studyid = $row['study_id'];
			if ($studyid > 0) {
				/* get the mr_series rows */
				if ($series == "") {
					$sqlstring = "select * from mr_series where study_id = $studyid";
				}
				else {
					$sqlstring = "select * from mr_series where study_id = $studyid and series_num = '$series'";
				}
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0){
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$series_desc = $row['series_desc'];
						$series_protocol = $row['series_protocol'];
						$sequence = $row['series_sequencename'];
						$tr = $row['series_tr'];
						$te = $row['series_te'];
						$ti = $row['series_ti'];
						$flip = $row['series_flip'];
						$slicethickness = $row['slicethickness'];
						$slicespacing = $row['slicespacing'];
						$dimx = $row['dimX'];
						$dimy = $row['dimY'];
						$dimz = $row['dimZ'];
						$dimt = $row['dimT'];
						$bandwidth = $row['bandwidth'];
						
						if (strlen($series_desc) != "") {
							$protocol = $series_desc;
						}
						else {
							$protocol = $series_protocol;
						}
						$param_rowid[] = "";
						$param_protocol[] = $protocol;
						$param_sequence[] = $sequence;
						$param_tr[] = (double)$tr;
						$param_te[] = (double)$te;
						$param_ti[] = (double)$ti;
						$param_flip[] = (double)$flip;
						$param_xdim[] = (double)$dimx;
						$param_ydim[] = (double)$dimy;
						$param_zdim[] = (double)$dimz;
						$param_tdim[] = (double)$dimt;
						$param_slicethickness[] = (double)$slicethickness;
						$param_slicespacing[] = (double)$slicespacing;
						$param_bandwidth[] = (double)$bandwidth;
						echo "Adding row [$protocol]<br>";
					}
					
					/* we have all the params, now do the inserts into the scan params table */
					UpdateQCParams($id, $param_rowid, $param_protocol, $param_sequence, $param_tr, $param_tr, $param_te, $param_te, $param_ti, $param_ti, $param_flip, $param_flip, $param_xdim, $param_xdim, $param_ydim, $param_ydim, $param_zdim, $param_zdim, $param_tdim, $param_tdim, $param_slicethickness, $param_slicethickness, $param_slicespacing, $param_slicespacing, $param_bandwidth, $param_bandwidth);
				}
				else {
					Error("No MR series found for [$study]");
				}
			}
		}
		else {
			Error("Invalid study ID [$study]. Incorrect UID, study number, or study does not contain MR series");
		}
	}

	
	/* -------------------------------------------- */
	/* ------- LoadMRParams ----------------------- */
	/* -------------------------------------------- */
	function LoadMRParams($id, $study, $series) {
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
		if (!isInteger($id)) { echo "Invalid project ID [$id]"; return; }
		$study = mysqli_real_escape_string($GLOBALS['linki'], trim($study));
		$series = mysqli_real_escape_string($GLOBALS['linki'], trim($series));
		
		$uid = substr($study,0,8);
		$studynum = substr($study,8);
		
		/* check if its a valid subject, valid study num, and is MR modality */
		$sqlstring = "select c.study_id from subjects a left join enrollment b on a.subject_id = b.subject_id left join studies c on b.enrollment_id = c.enrollment_id where a.uid = '$uid' and c.study_num = '$studynum' and c.study_modality = 'MR'";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0){
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$studyid = $row['study_id'];
			if ($studyid > 0) {
				/* get the mr_series rows */
				if ($series == "") {
					$sqlstring = "select * from mr_series where study_id = $studyid";
				}
				else {
					$sqlstring = "select * from mr_series where study_id = $studyid and series_num = '$series'";
				}
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0){
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$series_desc = $row['series_desc'];
						$series_protocol = $row['series_protocol'];
						$sequence = $row['series_sequencename'];
						$tr = $row['series_tr'];
						$te = $row['series_te'];
						$ti = $row['series_ti'];
						$flip = $row['series_flip'];
						$slicethickness = $row['slicethickness'];
						$slicespacing = $row['slicespacing'];
						$dimx = $row['dimX'];
						$dimy = $row['dimY'];
						$dimz = $row['dimZ'];
						$dimt = $row['dimT'];
						$bandwidth = $row['bandwidth'];
						
						if (strlen($series_desc) != "") {
							$protocol = $series_desc;
						}
						else {
							$protocol = $series_protocol;
						}
						$param_rowid[] = "";
						$param_protocol[] = $protocol;
						$param_sequence[] = $sequence;
						$param_tr[] = (double)$tr;
						$param_te[] = (double)$te;
						$param_ti[] = (double)$ti;
						$param_flip[] = (double)$flip;
						$param_xdim[] = (double)$dimx;
						$param_ydim[] = (double)$dimy;
						$param_zdim[] = (double)$dimz;
						$param_tdim[] = (double)$dimt;
						$param_slicethickness[] = (double)$slicethickness;
						$param_slicespacing[] = (double)$slicespacing;
						$param_bandwidth[] = (double)$bandwidth;
						echo "Adding row [$protocol]<br>";
					}
					
					/* we have all the params, now do the inserts into the scan params table */
					UpdateMRScanParams($id, $param_rowid, $param_protocol, $param_sequence, $param_tr, $param_tr, $param_te, $param_te, $param_ti, $param_ti, $param_flip, $param_flip, $param_xdim, $param_xdim, $param_ydim, $param_ydim, $param_zdim, $param_zdim, $param_tdim, $param_tdim, $param_slicethickness, $param_slicethickness, $param_slicespacing, $param_slicespacing, $param_bandwidth, $param_bandwidth);
				}
				else {
					Error("No MR series found for [$study]");
				}
			}
		}
		else {
			Error("Invalid study ID [$study]. Incorrect UID, study number, or study does not contain MR series");
		}
	}


	/* -------------------------------------------- */
	/* ------- UpdateQCParams --------------------- */
	/* -------------------------------------------- */
	function UpdateQCParams($id, $param_rowid, $param_protocol, $param_x_max, $param_y_max, $param_z_max, $param_iosnr_min, $param_iosnr_max, $param_pvsnr_min, $param_pvsnr_max) {
		
		$i=0;
		foreach ($param_rowid as $paramid) {
			$paramid = mysqli_real_escape_string($GLOBALS['linki'], $paramid);
			
			$protocol = mysqli_real_escape_string($GLOBALS['linki'], trim($param_protocol[$i]));
			$sequence = mysqli_real_escape_string($GLOBALS['linki'], trim($param_sequence[$i]));
			$x_max = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_x_max[$i])),3,'.','');
			$y_max = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_y_max[$i])),3,'.','');
			$z_max = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_z_max[$i])),3,'.','');
			$min_iosnr = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_iosnr_min[$i])),3,'.','');
			$max_iosnr = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_iosnr_max[$i])),3,'.','');
			$min_pvsnr = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_pvsnr_min[$i])),3,'.','');
			$max_pvsnr = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_pvsnr_max[$i])),3,'.','');
			
			if ($protocol != "") {
				if ($paramid == "") {
					$sqlstring = "insert ignore into mr_qcparams (protocol_name, project_id, max_x, max_y, max_z, min_iosnr, max_iosnr, min_pvsnr, max_pvsnr) values ('$protocol', '$id', '$x_max', '$y_max', '$z_max', '$min_iosnr', '$max_iosnr', '$min_pvsnr', '$max_pvsnr')";
				}
				else {
					$sqlstring = "update ignore mr_qcparams set protocol_name = '$protocol', max_x = '$x_max', max_y = '$y_max', max_z = '$z_max', min_iosnr = '$min_iosnr', max_iosnr = '$max_iosnr', min_pvsnr = '$min_pvsnr', max_pvsnr = '$max_pvsnr' where mrqcparam_id = $paramid";
				}
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
			if (($protocol == "") && ($paramid != "")) {
				$sqlstring = "delete from mr_qcparams where mrqcparam_id = $paramid";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
			$i++;
		}
	}

	
	/* -------------------------------------------- */
	/* ------- ViewQCParams ----------------------- */
	/* -------------------------------------------- */
	function ViewQCParams($id, $protocolfilter) {
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
		if (!isInteger($id)) { echo "Invalid project ID [$id]"; return; }

		$protocolfilter = mysqli_real_escape_string($GLOBALS['linki'], trim($protocolfilter));
		
		$sqlstring = "select * from projects where project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$name = $row['project_name'];
		
		/* get all of the MR params for this project */
		$sqlstring = "select * from mr_scanparams where project_id = $id order by protocol_name";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		//PrintSQLTable($result);
		if (mysqli_num_rows($result) > 0) {
			$i=0;
			//$lastprotocol = "";
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$p = $row['protocol_name'];
				$parms[$p][$i]['sequence'] = $row['sequence_name'];
				$parms[$p][$i]['tr_min'] = number_format($row['tr_min'],3,'.','');
				$parms[$p][$i]['tr_max'] = number_format($row['tr_max'],3,'.','');
				$parms[$p][$i]['te_min'] = number_format($row['te_min'],3,'.','');
				$parms[$p][$i]['te_max'] = number_format($row['te_max'],3,'.','');
				$parms[$p][$i]['ti_min'] = number_format($row['ti_min'],3,'.','');
				$parms[$p][$i]['ti_max'] = number_format($row['ti_max'],3,'.','');
				$parms[$p][$i]['flip_min'] = number_format($row['flip_min'],3,'.','');
				$parms[$p][$i]['flip_max'] = number_format($row['flip_max'],3,'.','');
				$parms[$p][$i]['xdim_min'] = number_format($row['xdim_min'],3,'.','');
				$parms[$p][$i]['xdim_max'] = number_format($row['xdim_max'],3,'.','');
				$parms[$p][$i]['ydim_min'] = number_format($row['ydim_min'],3,'.','');
				$parms[$p][$i]['ydim_max'] = number_format($row['ydim_max'],3,'.','');
				$parms[$p][$i]['zdim_min'] = number_format($row['zdim_min'],3,'.','');
				$parms[$p][$i]['zdim_max'] = number_format($row['zdim_max'],3,'.','');
				$parms[$p][$i]['tdim_min'] = number_format($row['tdim_min'],3,'.','');
				$parms[$p][$i]['tdim_max'] = number_format($row['tdim_max'],3,'.','');
				$parms[$p][$i]['slicethickness_min'] = number_format($row['slicethickness_min'],3,'.','');
				$parms[$p][$i]['slicethickness_max'] = number_format($row['slicethickness_max'],3,'.','');
				$parms[$p][$i]['slicespacing_min'] = number_format($row['slicespacing_min'],3,'.','');
				$parms[$p][$i]['slicespacing_max'] = number_format($row['slicespacing_max'],3,'.','');
				$parms[$p][$i]['bandwidth_min'] = number_format($row['bandwidth_min'],3,'.','');
				$parms[$p][$i]['bandwidth_max'] = number_format($row['bandwidth_max'],3,'.','');
				//if ($p != $lastprotocol) {
				//	$i=0;
				//	$lastprotocol = $p;
				//}
				//else {
					$i++;
				//}
			}
		}
		//PrintVariable($parms);
		
		$sqlstring = "select * from mr_qcparams where project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0) {
			$i=0;
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$qcparms[$row['protocol_name']]['max_x'] = number_format($row['max_x'],3,'.','');
				$qcparms[$row['protocol_name']]['max_y'] = number_format($row['max_y'],3,'.','');
				$qcparms[$row['protocol_name']]['max_z'] = number_format($row['max_z'],3,'.','');
				$qcparms[$row['protocol_name']]['min_iosnr'] = number_format($row['min_iosnr'],3,'.','');
				$qcparms[$row['protocol_name']]['max_iosnr'] = number_format($row['max_iosnr'],3,'.','');
				$qcparms[$row['protocol_name']]['min_pvsnr'] = number_format($row['min_pvsnr'],3,'.','');
				$qcparms[$row['protocol_name']]['max_pvsnr'] = number_format($row['max_pvsnr'],3,'.','');
			}
		}
		//PrintVariable($qcparms);

		/* get list of studies, and then series, associated with this project */
		$sqlstring = "select c.study_id, c.study_num, a.uid, a.subject_id, b.enrollment_id from subjects a left join enrollment b on a.subject_id = b.subject_id left join studies c on b.enrollment_id = c.enrollment_id where b.project_id = '$id' and c.study_modality = 'MR' order by a.uid, c.study_num";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0){
			
			DisplayProtocolDataListForProject($id, "protocollist");
			
			?>
			<div class="ui two column grid">
				<div class="column">
					<h2 class="ui header">MR scan quality control</h2>
				</div>
				<div class="right aligned column">
					<a class="ui primary button" href="mrqcchecklist.php?action=editmrparams&id=<? =$id?>"><i class="edit icon"></i> Edit expected MR parameters</a>
					<a class="ui primary button" href="mrqcchecklist.php?action=editqcparams&id=<? =$id?>"><i class="edit icon"></i> Edit QC criteria</a>
				</div>
			</div>
			<table class="ui small celled selectable grey very compact table">
				<thead>
					<th colspan="5">
					<form action="mrqcchecklist.php" action="post">
					<input type="hidden" name="action" value="viewqcparams">
					<input type="hidden" name="id" value="<? =$id?>">
					Filter by protocol name <input type="input" name="protocolfilter" value="<? =$protocolfilter?>" list="protocollist"> <input type="submit" value="Filter">
					</form>
					</th>
				</thead>
				<tbody>
			<?
			
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$studyid = $row['study_id'];
				$subjectid = $row['subject_id'];
				$enrollmentid = $row['enrollment_id'];
				$uid = $row['uid'];
				$studynum = $row['study_num'];
				if ($studyid > 0) {
					/* get the mr_series rows */
					if ($protocolfilter == "") {
						$sqlstringA = "select * from mr_series where study_id = $studyid order by series_num";
					}
					else {
						$sqlstringA = "select * from mr_series where study_id = $studyid and (series_desc = '$protocolfilter' or series_protocol = '$protocolfilter') order by series_num";
					}
					$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
					if (mysqli_num_rows($resultA) > 0){
						
						/* get project specific altuid */
						$sqlstringB = "select altuid from subject_altuid where subject_id = $subjectid and enrollment_id = $enrollmentid and isprimary = 1";
						$resultB = MySQLiQuery($sqlstringB, __FILE__, __LINE__);
						$rowB = mysqli_fetch_array($resultB, MYSQLI_ASSOC);
						$altuid = $rowB['altuid'];
						
						?>
							<tr>
								<td><a href="studies.php?id=<? =$studyid?>"><b><? =$uid?><? =$studynum?></b></a> &nbsp;Primary alt UID: &nbsp; <b style="background-color: darkred; color: white; padding: 2px 8px"><? =$altuid?></b></td>
								<td><b>Params</b></td>
								<td><b>Files on disk</b></td>
								<td><b>Avg Rating</b></td>
								<td><b>Basic QC</b></td>
								<!--<td style="border: 1px solid black">Advanced QC</td>-->
							</tr>
						<?
						while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
							$seriesid = $rowA['mrseries_id'];
							$seriesnum = $rowA['series_num'];
							$series_desc = $rowA['series_desc'];
							$series_protocol = $rowA['series_protocol'];
							$sequence = $rowA['series_sequencename'];
							$tr = number_format($rowA['series_tr'],3,'.','');
							$te = number_format($rowA['series_te'],3,'.','');
							$ti = number_format($rowA['series_ti'],3,'.','');
							$flip = number_format($rowA['series_flip'],3,'.','');
							$slicethickness = number_format($rowA['slicethickness'],3,'.','');
							$slicespacing = number_format($rowA['slicespacing'],3,'.','');
							$dimx = number_format($rowA['dimX'],3,'.','');
							$dimy = number_format($rowA['dimY'],3,'.','');
							$dimz = number_format($rowA['dimZ'],3,'.','');
							$dimt = number_format($rowA['dimT'],3,'.','');
							$bandwidth = number_format($rowA['bandwidth'],3,'.','');
							$numfiles = $rowA['numfiles'];
							list($datapath, $seriespath, $junk0, $junk1, $junk2, $junk3, $junk4) = GetDataPathFromSeriesID($seriesid, 'mr');
							
							$p1 = $series_desc;
							$protocol2 = $series_protocol;

							$thumbpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$studynum/$seriesnum/thumb.png";
							
							?>
							<tr>
								<td>
									 &nbsp; <? =$seriesnum?> - <? =$p1?>
									<? if (file_exists($thumbpath)) { ?>
									<a href="preview.php?image=<? =$thumbpath?>" class="preview"><img src="images/preview.gif" border="0"></a>
									&nbsp;
									<? } ?>
								</td>
							<?
							
							
							/* ----- check for MR pulse sequence parameter matching ----- */
							$matched = 0;
							$msgs = array();
							/* check if the params in this study match with any of the rows in the QA params table */
							if (array_key_exists($p1, $parms)) {
								$numparms = count($parms[$p1]);
								foreach ($parms[$p1] as $i => $pm) {
									$bad = 0;

									$min = $parms[$p1][$i]['tr_min'];
									$max = $parms[$p1][$i]['tr_max'];
									$b = between($tr, $min, $max);
									if (!$b) { $bad = 1; $msgs[] = "[$i] TR of $tr not within range ($min - $max)"; }
									elseif ($b == -1) { $bad = -1; $msgs[] = "[$i] TR param is blank ([$min], [$max])"; }
									//else { $msgs[] = "[$i] TR within range ($min - <b>$tr</b> - $max)"; }
									
									$min = $parms[$p1][$i]['te_min'];
									$max = $parms[$p1][$i]['te_max'];
									$b = between($te, $min, $max);
									if (!$b) { $bad = 1; $msgs[] = "[$i] TE of $te not within range ($min - $max)"; }
									elseif ($b == -1) { $bad = -1; $msgs[] = "[$i] TE param is blank ([$min], [$max])"; }
									//else { $msgs[] = "[$i] TE within range ($min - $te - $max)"; }
									
									$min = $parms[$p1][$i]['ti_min'];
									$max = $parms[$p1][$i]['ti_max'];
									$b = between($ti, $min, $max);
									if (!$b) { $bad = 1; $msgs[] = "[$i] TI of $ti not within range ($min - $max)"; }
									elseif ($b == -1) { $bad = -1; $msgs[] = "[$i] TI param is blank ([$min], [$max])"; }
									//else { $msgs[] = "[$i] TI within range ($min - $ti - $max)"; }
									
									$min = $parms[$p1][$i]['flip_min'];
									$max = $parms[$p1][$i]['flip_max'];
									$b = between($flip, $min, $max);
									if (!$b) { $bad = 1; $msgs[] = "[$i] Flip angle of $flip not within range ($min - $max)"; }
									elseif ($b == -1) { $bad = -1; $msgs[] = "[$i] Flip angle param is blank ([$min], [$max])"; }
									//else { $msgs[] = "[$i] Flip angle within range ($min - $flip - $max)"; }
									
									$min = $parms[$p1][$i]['xdim_min'];
									$max = $parms[$p1][$i]['xdim_max'];
									$b = between($dimx, $min, $max);
									if (!$b) { $bad = 1; $msgs[] = "[$i] dim X of $dimx not within range ($min - $max)"; }
									elseif ($b == -1) { $bad = -1; $msgs[] = "[$i] dim X param is blank ([$min], [$max])"; }
									//else { $msgs[] = "[$i] dim X within range ($min - $dimx - $max)"; }
									
									$min = $parms[$p1][$i]['ydim_min'];
									$max = $parms[$p1][$i]['ydim_max'];
									$b = between($dimy, $min, $max);
									if (!$b) { $bad = 1; $msgs[] = "[$i] dim Y of $dimy not within range ($min - $max)"; }
									elseif ($b == -1) { $bad = -1; $msgs[] = "[$i] dim Y param is blank ([$min], [$max])"; }
									//else { $msgs[] = "[$i] dim Y within range ($min - $dimy - $max)"; }
									
									$min = $parms[$p1][$i]['zdim_min'];
									$max = $parms[$p1][$i]['zdim_max'];
									$b = between($dimz, $min, $max);
									if (!$b) { $bad = 1; $msgs[] = "[$i] dim Z of $dimz not within range ($min - $max)"; }
									elseif ($b == -1) { $bad = -1; $msgs[] = "[$i] dim Z param is blank ([$min], [$max])"; }
									//else { $msgs[] = "[$i] dim Z within range ($min - $dimz - $max)"; }
									
									$min = $parms[$p1][$i]['tdim_min'];
									$max = $parms[$p1][$i]['tdim_max'];
									$b = between($dimt, $min, $max);
									if (!$b) { $bad = 1; $msgs[] = "[$i] dim T of $dimt not within range ($min - $max)"; }
									elseif ($b == -1) { $bad = -1; $msgs[] = "[$i] dim T param is blank ([$min], [$max])"; }
									//else { $msgs[] = "[$i] dim T within range ($min - $dimt - $max)"; }
									
									$min = $parms[$p1][$i]['slicethickness_min'];
									$max = $parms[$p1][$i]['slicethickness_max'];
									$b = between($slicethickness, $min, $max);
									if (!$b) { $bad = 1; $msgs[] = "[$i] Slice thickness of $slicethickness not within range ($min - $max)"; }
									elseif ($b == -1) { $bad = -1; $msgs[] = "[$i] Slice thickness param is blank ([$min], [$max])"; }
									//else { $msgs[] = "[$i] Slice thickness within range ($min - $slicethickness - $max)"; }
									
									$min = $parms[$p1][$i]['slicespacing_min'];
									$max = $parms[$p1][$i]['slicespacing_max'];
									$b = between($slicespacing, $min, $max);
									if (!$b) { $bad = 1; $msgs[] = "[$i] Slice spacing of $slicespacing not within range ($min - $max)"; }
									elseif ($b == -1) { $bad = -1; $msgs[] = "[$i] Slice Spacing param is blank ([$min], [$max])"; }
									//else { $msgs[] = "[$i] Slice spacing within range ($min - $slicespacing - $max)"; }
									
									$min = $parms[$p1][$i]['bandwidth_min'];
									$max = $parms[$p1][$i]['bandwidth_max'];
									$b = between($bandwidth, $min, $max);
									if (!$b) { $bad = 1; $msgs[] = "[$i] Bandwidth of $bandwidth not within range ($min - $max)"; }
									elseif ($b == -1) { $bad = -1; $msgs[] = "[$i] Bandwidth param is blank ([$min], [$max])"; }
									//else { $msgs[] = "[$i] Bandwidth within range ($min - $bandwidth - $max)"; }
									
									//$msgs[] = "<br>";
									
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
								?><td style="padding-left: 8px;" title="No MR parameter criteria specified for this protocol">&mdash;</td><?
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
								<li><? =implode2('<li>',$msgs)?>
								</ul>
								</details>
								</td>
								<?
							}
							
							/* check for files on disk */
							$fcount = 0;
							$files = glob($datapath . "/*");
							if ($files){ $fcount = count($files); }
							if ($fcount == $numfiles) {
								?><td style="padding-left: 8px; background-color: #cbf7be"> </td><?
							}
							elseif ($fcount == 0) {
								?><td style="padding-left: 8px; background-color: #ffddd1;" title="No files on disk in [<? =$datapath?>]"> </td><?
							}
							else {
								?><td style="padding-left: 8px; background-color: lightyellow;" title="Filecount in database [<? =$numfiles?>] does not match that on disk [<? =$fcount?>] from [<? =$datapath?>]">&#9898;</td><?
							}

							/* check user ratings */
							$sqlstring3 = "select * from ratings where rating_type = 'series' and data_modality = 'MR' and data_id = '$seriesid'";
							
							$result3 = MySQLiQuery($sqlstring3, __FILE__, __LINE__);
							while ($row3 = mysqli_fetch_array($result3, MYSQLI_ASSOC)) {
								$ratingseriesid = $row3['data_id'];
								$ratings[$ratingseriesid][] = $row3['rating_value'];
							}
							//print_r($ratings);
							/* check if this is real data, or unusable data based on the ratings, and get rating counts */
							$isbadseries = false;
							$istestseries = false;
							$ratingcount2 = '';
							$hasratings = false;
							$cellcolor = '';
							$ratingavg = '';
							
							if (isset($ratings)) {
								foreach ($ratings as $key => $ratingarray) {
									if ($key == $seriesid) {
										$hasratings = true;
										if (in_array(5,$ratingarray)) {
											$isbadseries = true;
										}
										if (in_array(6,$ratingarray)) {
											$istestseries = true;
										}
										$ratingcount2 = count($ratingarray);
										$ratingavg = array_sum($ratingarray) / count($ratingarray);
										break;
									}
								}
							}
							
							if ($isbadseries) { $cellcolor = "red"; }
							if ($istestseries) { $cellcolor = "#aaa"; }
							
							?><td style="padding-left: 8px;"><span style="color: <? =$cellcolor?>"><? =$ratingavg?></span> <? if ($ratingavg != "") { ?><span class="tiny">(<? =$ratingcount2?>)</span><? } ?></td><?
							
							/* check basic QC */
							$sqlstringB = "select (move_maxx-move_minx) 'movex', (move_maxy-move_miny) 'movey', (move_maxz-move_minz) 'movez', io_snr, pv_snr from mr_qa where mrseries_id = '$seriesid'";
							//PrintSQL($sqlstringB);
							$resultB = MySQLiQuery($sqlstringB, __FILE__, __LINE__);
							$rowB = mysqli_fetch_array($resultB, MYSQLI_ASSOC);
							$movex = $rowB['movex'] + 0.0;
							$movey = $rowB['movey'] + 0.0;
							$movez = $rowB['movez'] + 0.0;
							$iosnr = $rowB['io_snr'] + 0.0;
							$pvsnr = $rowB['pv_snr'] + 0.0;
							
							$msgs = array();
							if (array_key_exists($p1, $qcparms)) {
								$bad = 0;
								if ($movex > $qcparms[$p1]['max_x']) {
									$msgs[] = "Motion x: $movex &gt; (" . $qcparms[$p1]['max_x'] . ")";
									$bad = 1;
								}
								if ($movey > $qcparms[$p1]['max_y']) {
									$msgs[] = "Motion y: $movey &gt; (" . $qcparms[$p1]['max_y'] . ")";
									$bad = 1;
								}
								if ($movez > $qcparms[$p1]['max_z']) {
									$msgs[] = "Motion z: $movez &gt; (" . $qcparms[$p1]['max_z'] . ")";
									$bad = 1;
								}
								if (($iosnr > $qcparms[$p1]['max_iosnr']) || ($iosnr < $qcparms[$p1]['min_iosnr'])) {
									$msgs[] = "IO SNR of $iosnr not in range (" . $qcparms[$p1]['min_iosnr'] . " - " . $qcparms[$p1]['max_iosnr'] . ")";
									$bad = 1;
								}
								if (($pvsnr > $qcparms[$p1]['max_pvsnr']) || ($pvsnr < $qcparms[$p1]['min_pvsnr'])) {
									$msgs[] = "PV SNR of $pvsnr not in range (" . $qcparms[$p1]['min_pvsnr'] . " - " . $qcparms[$p1]['max_pvsnr'] . ")";
									$bad = 1;
								}
								
								if ($bad) {
									?>
									<td style="border: 1px solid #aaa; padding-left: 8px; background-color: #ffddd1; font-size:8pt; color: #666">
									<details>
									<summary>out of spec</summary>
									<ul>
									<li><? =implode2('<li>',$msgs)?>
									</ul>
									</details>
									</td>
									<?
								}
								else {
									?><td style="border: 1px solid #aaa; padding-left: 8px; background-color: #cbf7be"> </td><?
								}
							}
							else {
								?><td style="border: 1px solid #aaa; padding-left: 8px;" title="QC crtieria not specified">&mdash;</td><?
							}
							
							/* check advanced QC */
							?><!--<td>?</td>--><?
							
							?></tr><?
						}
					}
					else {
						//echo "Found no MR series for this study [$uid$studynum]";
					}
				}
				else {
					//echo "Invalid study ID [$study_id]<br>";
				}
				//break;
			}
			?>
				</tbody>
			</table>
			<?
		}
		else {
			echo "Found no valid MR studies for this project<br>";
		}
	}


	/* -------------------------------------------- */
	/* ------- EditQCParams ----------------------- */
	/* -------------------------------------------- */
	function EditQCParams($id) {
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
		if (!isInteger($id)) { echo "Invalid project ID [$id]"; return; }

		DisplayQCParamHeader($id);
		
		/* get all of the existing scan parameters */
		$sqlstring = "select * from mr_qcparams where project_id = '$id' order by protocol_name";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$paramid = $row['mrqcparam_id'];
			$protocol = $row['protocol_name'];
			$x_max = $row['max_x'];
			$y_max = $row['max_y'];
			$z_max = $row['max_z'];
			$iosnr_min = $row['min_iosnr'];
			$iosnr_max = $row['max_iosnr'];
			$pvsnr_min = $row['min_pvsnr'];
			$pvsnr_max = $row['max_pvsnr'];

			DisplayQCParamLine($paramid, $protocol, $x_max, $y_max, $z_max, $iosnr_min, $iosnr_max, $pvsnr_min, $pvsnr_max);
		}
		
		for ($i=0;$i<5;$i++) {
			DisplayQCParamLine();
		}
		?>
		</table>
		<input type="submit" value="Update">
		</form>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayQCParamHeader --------------- */
	/* -------------------------------------------- */
	function DisplayQCParamHeader($projectid) {
		
		$sqlstring = "select * from projects where project_id = $projectid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$name = $row['project_name'];
		
		DisplayProtocolDataListForProject($projectid, "protocollist");
		
		?>
		<br><br>
		<form action="mrqcchecklist.php" method="post">
		<input type="hidden" name="action" value="updateqcparams">
		<input type="hidden" name="id" value="<? =$projectid?>">
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
		?><tr>
			<input type="hidden" name="param_rowid[]" value="<? =$rowid?>">
			<td><input type="text" name="param_protocol[]" value="<? =$protocol?>" list="protocollist"></td>
			<td style="padding: 2px 15px"><input type="text" style="width: 45px" maxlength="8" name="param_x_max[]" value="<? =$x_max?>"></td>
			<td style="padding: 2px 15px"><input type="text" style="width: 45px" maxlength="8" name="param_y_max[]" value="<? =$y_max?>"></td>
			<td style="padding: 2px 15px"><input type="text" style="width: 45px" maxlength="8" name="param_z_max[]" value="<? =$z_max?>"></td>
			<td style="padding: 2px 15px"><input type="text" style="width: 45px" maxlength="8" name="param_iosnr_min[]" value="<? =$iosnr_min?>">&nbsp;<input type="text" style="width: 45px" maxlength="8" name="param_iosnr_max[]" value="<? =$iosnr_max?>"></td>
			<td style="padding: 2px 15px"><input type="text" style="width: 45px" maxlength="8" name="param_pvsnr_min[]" value="<? =$pvsnr_min?>">&nbsp;<input type="text" style="width: 45px" maxlength="8" name="param_pvsnr_max[]" value="<? =$pvsnr_max?>"></td>
		</tr><?
	}
	
	
	/* -------------------------------------------- */
	/* ------- ViewMRParams ----------------------- */
	/* -------------------------------------------- */
	function ViewMRParams($id) {
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
		if (!isInteger($id)) { echo "Invalid project ID [$id]"; return; }
		
		$sqlstring = "select * from projects where project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$name = $row['project_name'];
		
		/* get all of the MR params for this project */
		$sqlstring = "select * from mr_scanparams where project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) < 1){
			?>No MR parameters specified for this project. Add them <a href="projects.php?action=editmrparams&id=<? =$id?>">here</a>.<?
			return;
		}
		else {
			$i=0;
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$parms['protocol'][$i] = $row['protocol_name'];
				$parms['sequence'][$i] = $row['sequence_name'];
				$parms['tr_min'][$i] = number_format($row['tr_min'],3,'.','');
				$parms['tr_max'][$i] = number_format($row['tr_max'],3,'.','');
				$parms['te_min'][$i] = number_format($row['te_min'],3,'.','');
				$parms['te_max'][$i] = number_format($row['te_max'],3,'.','');
				$parms['ti_min'][$i] = number_format($row['ti_min'],3,'.','');
				$parms['ti_max'][$i] = number_format($row['ti_max'],3,'.','');
				$parms['flip_min'][$i] = number_format($row['flip_min'],3,'.','');
				$parms['flip_max'][$i] = number_format($row['flip_max'],3,'.','');
				$parms['xdim_min'][$i] = number_format($row['xdim_min'],3,'.','');
				$parms['xdim_max'][$i] = number_format($row['xdim_max'],3,'.','');
				$parms['ydim_min'][$i] = number_format($row['ydim_min'],3,'.','');
				$parms['ydim_max'][$i] = number_format($row['ydim_max'],3,'.','');
				$parms['zdim_min'][$i] = number_format($row['zdim_min'],3,'.','');
				$parms['zdim_max'][$i] = number_format($row['zdim_max'],3,'.','');
				$parms['tdim_min'][$i] = number_format($row['tdim_min'],3,'.','');
				$parms['tdim_max'][$i] = number_format($row['tdim_max'],3,'.','');
				$parms['slicethickness_min'][$i] = number_format($row['slicethickness_min'],3,'.','');
				$parms['slicethickness_max'][$i] = number_format($row['slicethickness_max'],3,'.','');
				$parms['slicespacing_min'][$i] = number_format($row['slicespacing_min'],3,'.','');
				$parms['slicespacing_max'][$i] = number_format($row['slicespacing_max'],3,'.','');
				$parms['bandwidth_min'][$i] = number_format($row['bandwidth_min'],3,'.','');
				$parms['bandwidth_max'][$i] = number_format($row['bandwidth_max'],3,'.','');
				$i++;
			}
			$numparms = $i;
		}
		
		/* get list of studies associated with this project */
		$sqlstring = "select c.study_id, c.study_num, a.uid from subjects a left join enrollment b on a.subject_id = b.subject_id left join studies c on b.enrollment_id = c.enrollment_id where b.project_id = '$id' and c.study_modality = 'MR'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0){
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$studyid = $row['study_id'];
				$uid = $row['uid'];
				$studynum = $row['study_num'];
				if ($studyid > 0) {
					/* get the mr_series rows */
					$sqlstringA = "select * from mr_series where study_id = $studyid order by series_num";
					$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
					if (mysqli_num_rows($resultA) > 0){
						?>
						<table width="100%">
							<tr>
								<td colspan="2" style="background-color: #444; color: white; padding: 3px 6px; border-radius:4px; margin-top: 10px; margin-bottom:5px"><b>Checking <? =$uid?><? =$studynum?>...</td>
							</tr>
						<?
						while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
							$seriesnum = $rowA['series_num'];
							$series_desc = $rowA['series_desc'];
							$series_protocol = $rowA['series_protocol'];
							$sequence = $rowA['series_sequencename'];
							$tr = number_format($rowA['series_tr'],3,'.','');
							$te = number_format($rowA['series_te'],3,'.','');
							$ti = number_format($rowA['series_ti'],3,'.','');
							$flip = number_format($rowA['series_flip'],3,'.','');
							$slicethickness = number_format($rowA['slicethickness'],3,'.','');
							$slicespacing = number_format($rowA['slicespacing'],3,'.','');
							$dimx = number_format($rowA['dimX'],3,'.','');
							$dimy = number_format($rowA['dimY'],3,'.','');
							$dimz = number_format($rowA['dimZ'],3,'.','');
							$dimt = number_format($rowA['dimT'],3,'.','');
							$bandwidth = number_format($rowA['bandwidth'],3,'.','');
							
							$protocol1 = $series_desc;
							$protocol2 = $series_protocol;
							
							$matched = false;
							$mismatch = "";
							/* check if the params in this study match with any of the rows in the QA params table */
							for ($i=0;$i<$numparms;$i++) {
								$rowmatch = true;
								$nummismatch[$i] = 0;
								if (($protocol1 != $parms['protocol'][$i]) && ($protocol2 != $parms['protocol'][$i])) { $rowmatch = false; $nummismatch[$i]++; }
								if ($sequence != $parms['sequence'][$i]) { $rowmatch = false; $nummismatch[$i]++; }

								$localdebug = 0;
								if (!between($tr, $parms['tr_min'][$i], $parms['tr_max'][$i])) { if ($localdebug) { echo "TR not within range (" . $parms['tr_min'][$i] . " - $tr - " . $parms['tr_max'][$i] . " )<br>"; } $rowmatch = false; $nummismatch[$i]++; }
								if (!between($te, $parms['te_min'][$i], $parms['te_max'][$i])) { if ($localdebug) { echo "TE not within range (" . $parms['te_min'][$i] . " - $te - " . $parms['te_max'][$i] . " )<br>"; } $rowmatch = false; $nummismatch[$i]++; }
								if (!between($ti, $parms['ti_min'][$i], $parms['ti_max'][$i])) { if ($localdebug) { echo "TI not within range (" . $parms['ti_min'][$i] . " - $ti - " . $parms['ti_max'][$i] . " )<br>"; } $rowmatch = false; $nummismatch[$i]++; }
								if (!between($flip, $parms['flip_min'][$i], $parms['flip_max'][$i])) { if ($localdebug) { echo "Flip not within range (" . $parms['flip_min'][$i] . " - $flip - " . $parms['flip_max'][$i] . " )<br>"; } $rowmatch = false; $nummismatch[$i]++; }
								if (!between($dimx, $parms['dimx_min'][$i], $parms['dimx_max'][$i])) { if ($localdebug) { echo "dimx not within range (" . $parms['dimx_min'][$i] . " - $dimx - " . $parms['dimx_max'][$i] . " )<br>"; } $rowmatch = false; $nummismatch[$i]++; }
								if (!between($dimy, $parms['dimy_min'][$i], $parms['dimy_max'][$i])) { if ($localdebug) { echo "dimy not within range (" . $parms['dimy_min'][$i] . " - $dimy - " . $parms['dimy_max'][$i] . " )<br>"; } $rowmatch = false; $nummismatch[$i]++; }
								if (!between($dimz, $parms['dimz_min'][$i], $parms['dimz_max'][$i])) { if ($localdebug) { echo "dimz not within range (" . $parms['dimz_min'][$i] . " - $dimz - " . $parms['dimz_max'][$i] . " )<br>"; } $rowmatch = false; $nummismatch[$i]++; }
								if (!between($dimt, $parms['dimt_min'][$i], $parms['dimt_max'][$i])) { if ($localdebug) { echo "dimt not within range (" . $parms['dimt_min'][$i] . " - $dimt - " . $parms['dimt_max'][$i] . " )<br>"; } $rowmatch = false; $nummismatch[$i]++; }
								if (!between($slicethickness, $parms['slicethickness_min'][$i], $parms['slicethickness_max'][$i])) { if ($localdebug) { echo "slicethickness not within range (" . $parms['slicethickness_min'][$i] . " - $slicethickness - " . $parms['slicethickness_max'][$i] . " )<br>"; } $rowmatch = false; $nummismatch[$i]++; }
								if (!between($slicespacing, $parms['slicespacing_min'][$i], $parms['slicespacing_max'][$i])) { if ($localdebug) { echo "slicespacing not within range (" . $parms['slicespacing_min'][$i] . " - $slicespacing - " . $parms['slicespacing_max'][$i] . " )<br>"; } $rowmatch = false; $nummismatch[$i]++; }
								if (!between($bandwidth, $parms['bandwidth_min'][$i], $parms['bandwidth_max'][$i])) { if ($localdebug) { echo "bandwidth not within range (" . $parms['bandwidth_min'][$i] . " - $bandwidth - " . $parms['bandwidth_max'][$i] . " )<br>"; } $rowmatch = false; $nummismatch[$i]++; }

								if ($rowmatch) { $matched = true; break; }
								
							}
							//PrintVariable($nummismatch);
							
							if ($matched) {
								?>
								<tr style="font-size: 9pt">
									<td style="width: 30px"></td>
									<td style="color: green">Series <? =$seriesnum?> [<? =$protocol1?>] <b>OK</b></td>
								</tr><?
							}
							else {
								?><tr>
									<td style="width: 30px"></td>
									<td style="padding-left: 30px"><span style="color: red; font-size:9pt">Series <? =$seriesnum?> [<? =$protocol1?>] did NOT match. Nearest matches:
								<?
								$min = min($nummismatch);
								$idx = array_keys($nummismatch, $min);
								?>
								<table class="ui very compact small celled table">
									<thead>
										<tr>
										<th>Protocol</th>
										<th>Sequence</th>
										<th>TR</th>
										<th>TE</th>
										<th>TI</th>
										<th>Flip &ang;</th>
										<th>X dim</th>
										<th>Y dim</th>
										<th>Z dim</th>
										<th>T dim</th>
										<th>Slice thick</th>
										<th>Slice spacing</th>
										<th>Bandwidth</th>
										<th></th>
										</tr>
									</thead>
									<tr style="font-weight: bold">
										<td><? =$protocol1?></td>
										<td><? =$sequence?></td>
										<td><? =$tr?></td>
										<td><? =$te?></td>
										<td><? =$ti?></td>
										<td><? =$flip?></td>
										<td><? =$dimx?></td>
										<td><? =$dimy?></td>
										<td><? =$dimz?></td>
										<td><? =$dimt?></td>
										<td><? =$slicethickness?></td>
										<td><? =$slicespacing?></td>
										<td><? =$bandwidth?></td>
										<td><a href="projects.php?id=<? =$id?>&action=loadmrparams&existingstudy=<? ="$uid$studynum"?>&existingseries=<? =$seriesnum?>">Add to QA list</a></td>
									</tr>
								<?
								?></table>
								</td>
								</tr>
							<?
							}
						}
						?></table><?
					}
					else {
						echo "Found no MR series for this study [$uid$studynum]";
					}
				}
				else {
					echo "Invalid study ID [$study_id]<br>";
				}
			}
		}
		else {
			echo "Found no valid MR studies for this project<br>";
		}
	}
	

	/* -------------------------------------------- */
	/* ------- EditMRScanParams ------------------- */
	/* -------------------------------------------- */
	function EditMRScanParams($projectid) {
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], $projectid);
		if (!isInteger($projectid)) { echo "Invalid project ID [$projectid]"; return; }

		DisplayMRScanParamHeader($projectid);
		
		/* get all of the existing scan parameters */
		$sqlstring = "select * from mr_scanparams where project_id = '$projectid' order by protocol_name, sequence_name";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$paramid = $row['mrscanparam_id'];
			$protocol = $row['protocol_name'];
			$sequence = $row['sequence_name'];
			$tr_min = $row['tr_min'];
			$tr_max = $row['tr_max'];
			$te_min = $row['te_min'];
			$te_max = $row['te_max'];
			$ti_min = $row['ti_min'];
			$ti_max = $row['ti_max'];
			$flip_min = $row['flip_min'];
			$flip_max = $row['flip_max'];
			$xdim_min = $row['xdim_min'];
			$xdim_max = $row['xdim_max'];
			$ydim_min = $row['ydim_min'];
			$ydim_max = $row['ydim_max'];
			$zdim_min = $row['zdim_min'];
			$zdim_max = $row['zdim_max'];
			$tdim_min = $row['tdim_min'];
			$tdim_max = $row['tdim_max'];
			$slicethickness_min = $row['slicethickness_min'];
			$slicethickness_max = $row['slicethickness_max'];
			$slicespacing_min = $row['slicespacing_min'];
			$slicespacing_max = $row['slicespacing_max'];
			$bandwidth_min = $row['bandwidth_min'];
			$bandwidth_max = $row['bandwidth_max'];

			DisplayMRScanParamLine($paramid, $protocol, $sequence, $tr_min, $tr_max, $te_min, $te_max, $ti_min, $ti_max, $flip_min, $flip_max, $xdim_min, $xdim_max, $ydim_min, $ydim_max, $zdim_min, $zdim_max, $tdim_min, $tdim_max, $slicethickness_min, $slicethickness_max, $slicespacing_min, $slicespacing_max, $bandwidth_min, $bandwidth_max);
		}
		
		for ($i=0;$i<5;$i++) {
			DisplayMRScanParamLine();
		}
		?>
		</table>
		<input type="submit" value="Update">
		</form>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayMRScanParamHeader ----------- */
	/* -------------------------------------------- */
	function DisplayMRScanParamHeader($projectid) {
		
		$sqlstring = "select * from projects where project_id = $projectid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$name = $row['project_name'];
		
		?>
		<br><br>
		<fieldset>
			<legend>Add scan parameters from existing study</legend>
			<form>
			<input type="hidden" name="action" value="loadmrparams">
			<input type="hidden" name="id" value="<? =$projectid?>">
			<input type="text" name="existingstudy"> &nbsp; <input type="submit" value="Load Parameters"><br>
			<span class="tiny">Enter study ID in the format <u>S1234ABC5</u></span>
			</form>
		</fieldset>
		<br><br>
		<form action="mrqcchecklist.php" method="post">
		<input type="hidden" name="action" value="updatemrparams">
		<input type="hidden" name="id" value="<? =$projectid?>">
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
	function DisplayMRScanParamLine($rowid="", $protocol="", $sequence="", $tr_min="", $tr_max="", $te_min="", $te_max="", $ti_min="", $ti_max="", $flip_min="", $flip_max="", $xdim_min="", $xdim_max="", $ydim_min="", $ydim_max="", $zdim_min="", $zdim_max="", $tdim_min="", $tdim_max="", $slicethickness_min="", $slicethickness_max="", $slicespacing_min="", $slicespacing_max="", $bandwidth_min="", $bandwidth_max="") {
		?><tr>
			<input type="hidden" name="param_rowid[]" value="<? =$rowid?>">
			<td><input type="text" name="param_protocol[]" value="<? =$protocol?>"></td>
			<td><input type="text" name="param_sequence[]" value="<? =$sequence?>"></td>
			<td style="padding: 2px 15px"><input type="text" style="width: 45px" maxlength="8" name="param_tr_min[]" value="<? =$tr_min?>">&nbsp;<input type="text" style="width: 45px" maxlength="8" name="param_tr_max[]" value="<? =$tr_max?>"></td>
			<td style="padding: 2px 15px"><input type="text" style="width: 45px" maxlength="8" name="param_te_min[]" value="<? =$te_min?>">&nbsp;<input type="text" style="width: 45px" maxlength="8" name="param_te_max[]" value="<? =$te_max?>"></td>
			<td style="padding: 2px 15px"><input type="text" style="width: 45px" maxlength="8" name="param_ti_min[]" value="<? =$ti_min?>">&nbsp;<input type="text" style="width: 45px" maxlength="8" name="param_ti_max[]" value="<? =$ti_max?>"></td>
			<td style="padding: 2px 15px"><input type="text" style="width: 45px" maxlength="8" name="param_flip_min[]" value="<? =$flip_min?>">&nbsp;<input type="text" style="width: 45px" maxlength="8" name="param_flip_max[]" value="<? =$flip_max?>"></td>
			<td style="padding: 2px 15px"><input type="number" style="width: 55px" name="param_xdim_min[]" value="<? =$xdim_min?>">&nbsp;<input type="number" style="width: 55px" name="param_xdim_max[]" value="<? =$xdim_max?>"></td>
			<td style="padding: 2px 15px"><input type="number" style="width: 55px" name="param_ydim_min[]" value="<? =$ydim_min?>">&nbsp;<input type="number" style="width: 55px" name="param_ydim_max[]" value="<? =$ydim_max?>"></td>
			<td style="padding: 2px 15px"><input type="number" style="width: 55px" name="param_zdim_min[]" value="<? =$zdim_min?>">&nbsp;<input type="number" style="width: 55px" name="param_zdim_max[]" value="<? =$zdim_max?>"></td>
			<td style="padding: 2px 15px"><input type="number" style="width: 55px" name="param_tdim_min[]" value="<? =$tdim_min?>">&nbsp;<input type="number" style="width: 55px" name="param_tdim_max[]" value="<? =$tdim_max?>"></td>
			<td style="padding: 2px 15px"><input type="text" style="width: 45px" maxlength="8" name="param_slicethickness_min[]" value="<? =$slicethickness_min?>">&nbsp;<input type="text" style="width: 45px" maxlength="8" name="param_slicethickness_max[]" value="<? =$slicethickness_max?>"></td>
			<td style="padding: 2px 15px"><input type="text" style="width: 45px" maxlength="8" name="param_slicespacing_min[]" value="<? =$slicespacing_min?>">&nbsp;<input type="text" style="width: 45px" maxlength="8" name="param_slicespacing_max[]" value="<? =$slicespacing_max?>"></td>
			<td style="padding: 2px 15px"><input type="text" style="width: 45px" maxlength="8" name="param_bandwidth_min[]" value="<? =$bandwidth_min?>">&nbsp;<input type="text" style="width: 45px" maxlength="8" name="param_bandwidth_max[]" value="<? =$bandwidth_max?>"></td>
		</tr><?
	}

	
	/* -------------------------------------------- */
	/* ------- UpdateMRScanParams ----------------- */
	/* -------------------------------------------- */
	function UpdateMRScanParams($id, $param_rowid, $param_protocol, $param_sequence, $param_tr_min, $param_tr_max, $param_te_min, $param_te_max, $param_ti_min, $param_ti_max, $param_flip_min, $param_flip_max, $param_xdim_min, $param_xdim_max, $param_ydim_min, $param_ydim_max, $param_zdim_min, $param_zdim_max, $param_tdim_min, $param_tdim_max, $param_slicethickness_min, $param_slicethickness_max, $param_slicespacing_min, $param_slicespacing_max, $param_bandwidth_min, $param_bandwidth_max) {
		
		$i=0;
		foreach ($param_rowid as $paramid) {
			$paramid = mysqli_real_escape_string($GLOBALS['linki'], $paramid);
			
			$protocol = mysqli_real_escape_string($GLOBALS['linki'], trim($param_protocol[$i]));
			$sequence = mysqli_real_escape_string($GLOBALS['linki'], trim($param_sequence[$i]));
			$tr_min = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_tr_min[$i])),3,'.','');
			$tr_max = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_tr_max[$i])),3,'.','');
			$te_min = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_te_min[$i])),3,'.','');
			$te_max = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_te_max[$i])),3,'.','');
			$ti_min = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_ti_min[$i])),3,'.','');
			$ti_max = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_ti_max[$i])),3,'.','');
			$flip_min = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_flip_min[$i])),3,'.','');
			$flip_max = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_flip_max[$i])),3,'.','');
			$xdim_min = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_xdim_min[$i])),3,'.','');
			$xdim_max = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_xdim_max[$i])),3,'.','');
			$ydim_min = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_ydim_min[$i])),3,'.','');
			$ydim_max = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_ydim_max[$i])),3,'.','');
			$zdim_min = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_zdim_min[$i])),3,'.','');
			$zdim_max = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_zdim_max[$i])),3,'.','');
			$tdim_min = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_tdim_min[$i])),3,'.','');
			$tdim_max = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_tdim_max[$i])),3,'.','');
			$slicethickness_min = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_slicethickness_min[$i])),3,'.','');
			$slicethickness_max = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_slicethickness_max[$i])),3,'.','');
			$slicespacing_min = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_slicespacing_min[$i])),3,'.','');
			$slicespacing_max = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_slicespacing_max[$i])),3,'.','');
			$bandwidth_min = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_bandwidth_min[$i])),3,'.','');
			$bandwidth_max = number_format(mysqli_real_escape_string($GLOBALS['linki'], trim($param_bandwidth_max[$i])),3,'.','');
			
			if ($protocol != "") {
				if ($paramid == "") {
					$sqlstring = "insert ignore into mr_scanparams (protocol_name, sequence_name, project_id, tr_min, tr_max, te_min, te_max, ti_min, ti_max, flip_min, flip_max, xdim_min, xdim_max, ydim_min, ydim_max, zdim_min, zdim_max, tdim_min, tdim_max, slicethickness_min, slicethickness_max, slicespacing_min, slicespacing_max, bandwidth_min, bandwidth_max) values ('$protocol', '$sequence', '$id', '$tr_min', '$tr_max', '$te_min', '$te_max', '$ti_min', '$ti_max', '$flip_min', '$flip_max', '$xdim_min', '$xdim_max', '$ydim_min', '$ydim_max', '$zdim_min', '$zdim_max', '$tdim_min', '$tdim_max', '$slicethickness_min', '$slicethickness_max', '$slicespacing_min', '$slicespacing_max', '$bandwidth_min', '$bandwidth_max')";
				}
				else {
					$sqlstring = "update ignore mr_scanparams set protocol_name = '$protocol', sequence_name = '$sequence', tr_min = '$tr_min', tr_max = '$tr_max', te_min = '$te_min', te_max = '$te_max', ti_min = '$ti_min', ti_max = '$ti_max', flip_min = '$flip_min', flip_max = '$flip_max', xdim_min = '$xdim_min', xdim_max = '$xdim_max', ydim_min = '$ydim_min', ydim_max = '$ydim_max', zdim_min = '$zdim_min', zdim_max = '$zdim_max', tdim_min = '$tdim_min', tdim_max = '$tdim_max', slicethickness_min = '$slicethickness_min', slicethickness_max = '$slicethickness_max', slicespacing_min = '$slicespacing_min', slicespacing_max = '$slicespacing_max', bandwidth_min = '$bandwidth_min', bandwidth_max = '$bandwidth_max' where mrscanparam_id = $paramid";
				}
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
			if (($protocol == "") && ($paramid != "")) {
				$sqlstring = "delete from mr_scanparams where mrscanparam_id = $paramid";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
			$i++;
		}
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayProtocolDataListForProject -- */
	/* -------------------------------------------- */
	function DisplayProtocolDataListForProject($projectid, $datalistid) {
		$sqlstring = "select distinct(a.series_desc) from mr_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id where c.project_id = $projectid order by a.series_desc";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		?><datalist id="<? =$datalistid?>"><?
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			?><option value="<? =$row['series_desc']?>"><?
		}
		?></datalist><?
	}
	
	
?>

<? include("footer.php") ?>
