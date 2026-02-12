<?
 // ------------------------------------------------------------------------------
 // NiDB adminqc.php
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
 // ------------------------------------------------------------------------------

	define("LEGIT_REQUEST", true);
	
	session_start();
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Manage QC Modules</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "nidbapi.php";
	require "menu.php";

	/* check if they have permissions to this view page */
	if (!isSiteAdmin()) {
		Warning("You do not have permissions to view this page");
		exit(0);
	}

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$id = GetVariable("id");
	$modulename = GetVariable("modulename");
	$modality = GetVariable("modality");
	$clusterid = GetVariable("clusterid");
	$datatype = GetVariable("datatype");
	$entrypoint = GetVariable("entrypoint");

	PrintVariable($_POST);
	
	/* determine action */
	switch ($action) {
		case 'addmodule':
			AddQCModule($modulename, $modality, $clusterid, $datatype, $entrypoint);
			DisplayQCModuleList();
			break;
		case 'updatemodule':
			UpdateQCModule($id, $modulename, $modality, $clusterid, $datatype, $entrypoint);
			DisplayQCModuleList();
			break;
		case 'editmodule':
			DisplayQCModuleForm('edit', $id);
			break;
		case 'disable':
			DisableQCModule($id);
			DisplayQCModuleList();
			break;
		case 'enable':
			EnableQCModule($id);
			DisplayQCModuleList();
			break;
		case 'reset':
			ResetQCModule($id);
			DisplayQCModuleList();
			break;
		default:
			DisplayQCModuleList();
	}
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- UpdateQCModule --------------------- */
	/* -------------------------------------------- */
	function UpdateQCModule($id, $modulename, $modality, $clusterid, $datatype, $entrypoint) {

		/* perform data checks */
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
		$modulename = mysqli_real_escape_string($GLOBALS['linki'], $modulename);
		$modality = mysqli_real_escape_string($GLOBALS['linki'], $modality);
		$datatype = mysqli_real_escape_string($GLOBALS['linki'], $datatype);
		$entrypoint = mysqli_real_escape_string($GLOBALS['linki'], $entrypoint);
		$datatype = mysqli_real_escape_string($GLOBALS['linki'], $datatype);
		$clusterid = mysqli_real_escape_string($GLOBALS['linki'], $clusterid);
		
		/* update the modality */
		$sqlstring = "update qc_modules set module_name = '$modulename', modality = '$modality', cluster_id = nullif('$clusterid', ''), datatype = '$datatype', entrypoint = '$entrypoint' where qcmodule_id = $id";
		PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		Notice ("$modulename updated");
	}


	/* -------------------------------------------- */
	/* ------- AddQCmodule ------------------------ */
	/* -------------------------------------------- */
	function AddQCmodule($modulename, $modality, $clusterid, $datatype, $entrypoint) {

		/* perform data checks */
		$modulename = mysqli_real_escape_string($GLOBALS['linki'], $modulename);
		$modality = mysqli_real_escape_string($GLOBALS['linki'], $modality);
		$datatype = mysqli_real_escape_string($GLOBALS['linki'], $datatype);
		$entrypoint = mysqli_real_escape_string($GLOBALS['linki'], $entrypoint);
		$datatype = mysqli_real_escape_string($GLOBALS['linki'], $datatype);
		$clusterid = mysqli_real_escape_string($GLOBALS['linki'], $clusterid);
		
		/* insert the new modality */
		$sqlstring = "insert into qc_modules (module_name, modality, cluster_id, datatype, entrypoint) values ('$modulename', '$modality', nullif('$clusterid', ''), '$datatype', '$entrypoint')";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		Notice("$modulename added");
	}

	
	/* -------------------------------------------- */
	/* ------- EnableQCModule --------------------- */
	/* -------------------------------------------- */
	function EnableQCModule($id) {
		$sqlstring = "update qc_modules set qcm_isenabled = 1 where qcmodule_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}


	/* -------------------------------------------- */
	/* ------- DisableQCModule -------------------- */
	/* -------------------------------------------- */
	function DisableQCModule($id) {
		$sqlstring = "update qc_modules set qcm_isenabled = 0 where qcmodule_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}


	/* -------------------------------------------- */
	/* ------- EditQCModuleForm ------------------- */
	/* -------------------------------------------- */
	function DisplayQCModuleForm($type, $id) {
	
		/* populate the fields if this is an edit */
		if ($type == "edit") {
			$sqlstring = "select * from qc_modules where qcmodule_id = $id";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$modality = $row['modality'];
			$name = $row['module_name'];
			$clusterid = $row['cluster_id'];
			$datatype = $row['datatype'];
			$entrypoint = $row['entrypoint'];
		
			$formaction = "updatemodule";
			$formtitle = "Updating $name";
			$submitbuttonlabel = "Update";
		}
		else {
			$formaction = "addmodule";
			$formtitle = "Add new QC module";
			$submitbuttonlabel = "Add";
		}
		
	?>
	<div class="ui text container">
		<form method="post" action="adminqc.php">
		<input type="hidden" name="action" value="<?=$formaction?>">
		<input type="hidden" name="id" value="<?=$id?>">
		<table class="ui top attached table" width="100%">
			<tr>
				<td colspan="2" align="center">
					<b><?=$formtitle?></b>
				</td>
			</tr>
			<tr>
				<td>Name</td>
				<td>
					<div class="ui fluid input">
						<input type="text" name="modulename" value="<?=$name?>">
					</div>
				</td>
			</tr>
			<tr>
				<td>Modality</td>
				<td>
					<select name="modality" class="ui fluid dropdown">
					<?
						$modalities = GetModalityList();
						foreach ($modalities as $mod) {
							?><option value="<?=$mod?>" <? if ($mod == $modality) { echo "selected"; } ?>><?=$mod?></option><?
						}
					?>
					</select>
				</td>
			</tr>
			<tr>
				<td>Cluster</td>
				<td>
					<select name="clusterid" class="ui fluid dropdown">
					<?
						list ($clusterids, $names, $descs) = GetClusterList();
						$i = 0;
						foreach ($clusterids as $cluster_id) {
							?><option value="<?=$cluster_id?>" <? if ($cluster_id == $clusterid) { echo "selected"; } ?>><?=$names[$i]?> - <?=$descs[$i]?></option><?
							$i++;
						}
					?>
					</select>
				</td>
			</tr>
			<tr>
				<td>Data format</td>
				<td>
					<select name="datatype" class="ui fluid dropdown">
						<option value="dicom">DICOM</option>
						<option value="bids">BIDS</option>
						<option value="nifti4dgz">Nifti 4D .gz</option>
					</select>
				</td>
			</tr>
			<tr>
				<td>Entry point (full script path)<br><span class="tiny">This must be an executable script that accepts input and output parameters.</span></td>
				<td>
					<div class="ui fluid input">
						<textarea name="entrypoint" cols=60><?=$entrypoint?></textarea>
					</div>
					Example: <code>./&lt;qcscript&gt; /path/to/input /path/to/output UID</code>
				</td>
			</tr>
		</table>
		<div class="ui bottom attached segment">
			<div class="ui two column grid">
				<div class="left aligned column">
					<a href="adminqc.php" class="ui button">Cancel</a>
				</div>
				<div class="right aligned column">
					<input type="submit" value="<?=$submitbuttonlabel?>" class="ui primary button">
				</div>
			</div>
		</div>
		</form>
	</div>
	<?
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayQCModuleList ---------------- */
	/* -------------------------------------------- */
	function DisplayQCModuleList() {
	
	?>

	<div class="ui text container">
		<table class="ui table">
			<thead>
				<tr>
					<th>Module name</th>
					<th>Modality</th>
					<th>Enable/Disable</th>
				</tr>
			</thead>
			<tbody>
				<form action="adminqc.php" method="post">
				<input type="hidden" name="action" value="addmodule">
				<tr>
					<td>
						<div class="ui input">
							<input type="text" name="modulename" size="40">
						</div>
					</td>
					<td>
						<select name="modality" class="ui dropdown">
						<?
							$modalities = GetModalityList();
							foreach ($modalities as $modality) {
								?><option value="<?=$modality?>"><?=$modality?></option><?
							}
						?>
						</select>
					</td>
					<td><input type="submit" value="Add" class="ui primary button"></td>
					</form>
				</tr>
				<?
					$sqlstring = "select * from qc_modules order by module_name";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$id = $row['qcmodule_id'];
						$modality = $row['modality'];
						$name = $row['module_name'];
						$enabled = $row['isenabled'];

						/* calculate the status color */
						if (!$enabled) { $color = "gray"; }
						else { $color = "darkblue"; }

						?>
						<tr style="color: <?=$color?>">
							<td><a href="adminqc.php?action=editmodule&id=<?=$id?>"><?=$name?></a></td>
							<td><?=$modality?></td>
							<td>
								<?
									if ($enabled) {
										?><a href="adminqc.php?action=disable&id=<?=$id?>" title="<b>Enabled.</b> Click to disable"><i class="big green toggle on icon"></i></a><?
									}
									else {
										?><a href="adminqc.php?action=enable&id=<?=$id?>" title="<b>Disabled.</b> Click to enable"><i class="big grey horizontally flipped toggle on icon"></i></a><?
									}
								?>
							</td>
						</tr>
						<? 
					}
				?>
			</tbody>
		</table>
	</div>
	<?
	}
?>


<? include("footer.php") ?>
