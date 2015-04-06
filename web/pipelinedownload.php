<?
 // ------------------------------------------------------------------------------
 // NiDB pipelinedownload.php
 // Copyright (C) 2004 - 2015
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
		<title>NiDB - Manage Pipelines</title>
	</head>

<body>
	<div id="wrapper">
<?
	//require "config.php";
	require "functions.php";
	require "includes.php";
	require "menu.php";
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$id = GetVariable("id");
	
	$pipeline_id = GetVariable("pipeline_id");
	$protocol = GetVariable("protocol");
	$dirformat = GetVariable("dirformat");
	$nfsdir = GetVariable("nfsdir");
	$anonymize = GetVariable("anonymize");
	$gzip = GetVariable("gzip");
	$preserveseries = GetVariable("preserveseries");
	$groupbyprotocol = GetVariable("groupbyprotocol");
	$onlynew = GetVariable("onlynew");
	$admin = GetVariable("admin");
	$filetype = GetVariable("filetype");
	$modality = GetVariable("modality");
	$behformat = GetVariable("behformat");
	$behdirrootname = GetVariable("behdirrootname");
	
	/* determine action */
	if ($action == "editform") {
		DisplayPipelineDownloadForm("edit", $id);
	}
	elseif ($action == "addform") {
		DisplayPipelineDownloadForm("add", "");
	}
	elseif ($action == "update") {
		UpdatePipelineDownload($id, $pipeline_id, $protocol, $dirformat, $nfsdir, $anonymize, $gzip, $preserveseries, $groupbyprotocol, $onlynew, $admin, $filetype, $modality, $behformat, $behdirrootname);
		DisplayPipelineDownloadList();
	}
	elseif ($action == "add") {
		AddPipelineDownload($pipeline_id, $protocol, $dirformat, $nfsdir, $anonymize, $gzip, $preserveseries, $groupbyprotocol, $onlynew, $admin, $filetype, $modality, $behformat, $behdirrootname);
		DisplayPipelineDownloadList();
	}
	elseif ($action == "delete") {
		DeletePipelineDownload($id);
	}
	else {
		DisplayPipelineDownloadList();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- UpdatePipelineDownload ------------- */
	/* -------------------------------------------- */
	function UpdatePipelineDownload($id, $pipeline_id, $protocol, $dirformat, $nfsdir, $anonymize, $gzip, $preserveseries, $groupbyprotocol, $onlynew, $admin, $filetype, $modality, $behformat, $behdirrootname) {
		/* perform data checks */
		$protocol = mysql_real_escape_string($protocol);
		$nfsdir = mysql_real_escape_string($nfsdir);
		$behdirrootname = mysql_real_escape_string($behdirrootname);
		
		/* update the pipeline */
		$sqlstring = "update pipeline_download set pipeline_id = '$pipeline_id', pd_admin = '$admin', pd_protocol = '$protocol', pd_dirformat = '$dirformat', pd_nfsdir = '$nfsdir', pd_anonymize = '$anonymize', pd_gzip = '$gzip', pd_preserveseries = '$preserveseries', pd_groupbyprotocol = '$groupbyprotocol', pd_onlynew = '$onlynew', pd_filetype = '$filetype', pd_modality = '$modality', pd_behformat = '$behformat', pd_behdirrootname = '$behdirrootname' where pipelinedownload_id = $id";
		//echo $sqlstring;
		$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		
		?><div align="center"><span class="message"><?=$id?> updated</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- AddPipelineDownload ---------------- */
	/* -------------------------------------------- */
	function AddPipelineDownload($pipeline_id, $protocol, $dirformat, $nfsdir, $anonymize, $gzip, $preserveseries, $groupbyprotocol, $onlynew, $admin, $filetype, $modality, $behformat, $behdirrootname) {
		/* perform data checks */
		$protocol = mysql_real_escape_string($protocol);
		$nfsdir = mysql_real_escape_string($nfsdir);
		$behdirrootname = mysql_real_escape_string($behdirrootname);
		
		/* insert the new pipeline */
		$sqlstring = "insert into pipeline_download (pipeline_id, pd_admin, pd_protocol, pd_dirformat, pd_nfsdir, pd_anonymize, pd_gzip, pd_preserveseries, pd_groupbyprotocol, pd_onlynew, pd_filetype, pd_modality, pd_behformat, pd_behdirrootname, pd_createdate, pd_status) values ($pipeline_id, $admin, '$protocol', '$dirformat', '$nfsdir', '$anonymize', '$gzip', '$preserveseries', '$groupbyprotocol', '$onlynew', '$filetype', '$modality', '$behformat', '$behdirrootname', now(), 'active')";
		$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		
		?><div align="center"><span class="message">Pipeline for <?=$protocol?> added</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- DeletePipelineDownload ------------- */
	/* -------------------------------------------- */
	function DeletePipelineDownload($id) {
		$sqlstring = "delete from pipeline_download where pipelinedownload_id = $id";
		$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
	}	
	
	
	/* -------------------------------------------- */
	/* ------- DisplayPipelineDownloadForm -------- */
	/* -------------------------------------------- */
	function DisplayPipelineDownloadForm($type, $id) {
	
		/* populate the fields if this is an edit */
		if ($type == "edit") {
			$sqlstring = "select * from pipeline_download where pipelinedownload_id = $id";
			$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
			//$id = $row['pipelinedownload_id'];
			$pipeline_id = $row['pipeline_id'];
			$admin_id = $row['pd_admin'];
			//$adminfullname = $row['adminfullname'];
			$protocol = $row['pd_protocol'];
			$dirformat = $row['pd_dirformat'];
			$nfsdir = $row['pd_nfsdir'];
			$anonymize = $row['pd_anonymize'];
			$gzip = $row['pd_gzip'];
			$preserveseries = $row['pd_preserveseries'];
			$groupbyprotocol = $row['pd_groupbyprotocol'];
			$onlynew = $row['pd_onlynew'];
			$filetype = $row['pd_filetype'];
			$modality = $row['pd_modality'];
			$behformat = $row['pd_behformat'];
			$behdirrootname = $row['pd_behdirrootname'];
			$createdate = $row['pd_createdate'];
			$status = $row['pd_status'];
		
			$formaction = "update";
			$formtitle = "Updating pipeline download $id";
			$submitbuttonlabel = "Update";
		}
		else {
			$behdirrootname = "beh";
			$onlynew = "1";
			$filetype = "nifti3d";
			$modality = "mr";
			
			$formaction = "add";
			$formtitle = "Add new pipeline";
			$submitbuttonlabel = "Add";
		}
		
		$urllist['Pipelines'] = "adminpipelines.php";
		$urllist["Download ($protocol)"] = "pipelinedownload.php?action=editform&id=$id";
		NavigationBar("Pipeline Download", $urllist);
		
	?>
		<div align="center">
		<table class="entrytable">
			<form method="post" action="pipelinedownload.php">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="id" value="<?=$id?>">
			<tr>
				<td colspan="2" align="center">
					<b><?=$formtitle?></b>
				</td>
			</tr>
			<tr>
				<td>Pipeline</td>
				<td>
					<select name="pipeline_id">
						<?
							$sqlstring = "select * from pipelines where pipeline_status = 'active' order by pipeline_name";
							$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
							while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
								$pipe_id = $row['pipeline_id'];
								$pipeline_name = $row['pipeline_name'];

								if ($pipe_id == $pipeline_id) { $selected = "selected"; } else { $selected = ""; }
								?>
								<option value="<?=$pipe_id?>" <?=$selected?>><?=$pipeline_name?></option>
								<?
							}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td>Protocol</td>
				<td><input type="text" name="protocol" value="<?=$protocol?>"></td>
			</tr>
			<tr>
				<td>Directory format</td>
				<td>
					<select name="dirformat">
						<option value="datetime" <? if ($dirformat == "datetime") {echo "selected"; } ?>>20110704_123456</option>
						<option value="datetimeshortid" <? if (($dirformat == "datetimeshortid") || ($dirformat == "")) {echo "selected"; } ?>>20110704_123456_S123ABC1</option>
						<option value="datetimelongid" <? if ($dirformat == "datetimelongid") {echo "selected"; } ?>>20110704_123456_S123ABC_999999_1</option>
						<option value="shortid" <? if ($dirformat == "shortid") {echo "selected"; } ?>>S123ABC1</option>
						<option value="longid" <? if ($dirformat == "longid") {echo "selected"; } ?>>S123ABC_999999_1</option>
					</select>
				</td>
			</tr>
			<tr>
				<td>NFS directory</td>
				<td><input type="text" name="nfsdir" value="<?=$nfsdir?>" size="70"></td>
			</tr>
			<tr>
				<td>Anonymize?</td>
				<td><input type="checkbox" name="anonymize" value="1" <? if ($anonymize) echo "checked"; ?>></td>
			</tr>
			<tr>
				<td>gzip?</td>
				<td><input type="checkbox" name="gzip" value="1" <? if ($gzip) echo "checked"; ?>></td>
			</tr>
			<tr>
				<td>Preserve series?</td>
				<td><input type="checkbox" name="preserveseries" value="1" <? if ($preserveseries) echo "checked"; ?>></td>
			</tr>
			<tr>
				<td>Group by protocol?</td>
				<td><input type="checkbox" name="groupbyprotocol" value="1" <? if ($groupbyprotocol) echo "checked"; ?>></td>
			</tr>
			<tr>
				<td>Only new data?</td>
				<td><input type="checkbox" name="onlynew" value="1" <? if ($onlynew) echo "checked"; ?>></td>
			</tr>
			<tr>
				<td>Administrator</td>
				<td>
					<select name="admin">
						<?
							$sqlstring = "select * from users where user_enabled = true order by user_fullname";
							$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
							while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
								$userid = $row['user_id'];
								$username = $row['username'];
								$fullname = $row['user_fullname'];
								//echo "[$userid:$admin]";
								if ($userid == $admin_id) { $selected = "selected"; } else { $selected = ""; }
								?>
								<option value="<?=$userid?>" <?=$selected?>><?=$fullname?></option>
								<?
							}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td>File type</td>
				<td>
					<select name="filetype">
						<option value="dicom">DICOM</option>
						<option value="nifti3d" checked>Nifti 3D</option>
						<option value="nifti4d">Nifti 4D</option>
						<option value="analyze3d">Analyze 3D</option>
						<option value="analyze4d">Analyze 4D</option>
					</select>
				</td>
			</tr>
			<tr>
				<td>Modality</td>
				<td>
					<!--<input type="text" name="modality" value="<?=$study_modality?>">-->
					<select name="modality">
					<?
						$sqlstring = "select * from modalities order by mod_desc";
						$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
						while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
							$mod_code = $row['mod_code'];
							$mod_desc = $row['mod_desc'];
							if ($mod_code == $modality) { $selected = "selected"; } else { $selected = ""; }
							?>
							<option value="<?=$mod_code?>" <?=$selected?>><?=$mod_desc?></option>
							<?
						}
					?>
					</select>
				</td>
			</tr>
			<tr>
				<td>Behavioral data format</td>
				<td>
					<table>
						<tr>
							<td><input type="radio" name="behformat" value="behnone" <? if ($behformat == "behnone") {echo "checked"; } ?>>Don't download behavioral data</td>
							<td style="color:#333333"></td>
						</tr>
						<tr>
							<td><input type="radio" name="behformat" value="behroot" <? if ($behformat == "behroot") {echo "checked"; } ?>>Place in in root</td>
							<td style="color:#333333"><tt>subjectdir/file.log</tt></td>
						</tr>
						<tr>
							<td><input type="radio" name="behformat" value="behrootdir"  <? if (($behformat == "behrootdir") || ($behformat == "")) {echo "checked"; } ?>>Place in <input type="text" name="behdirrootname" value="<?=$behdirrootname?>" size="6"> directory in root</td>
							<td style="color:#333333"><tt>subjectdir/beh/file.log</tt></td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td colspan="2" align="center">
					<input type="submit" value="<?=$submitbuttonlabel?>">
				</td>
			</tr>
			</form>
		</table>
		</div>
	<?
	}

	/* -------------------------------------------- */
	/* ------- DisplayPipelineDownloadList -------- */
	/* -------------------------------------------- */
	function DisplayPipelineDownloadList() {
	
		$urllist['Pipeline download list'] = "pipelinedownload.php";
		$urllist['Add pipeline download'] = "pipelinedownload.php?action=addform";
		NavigationBar("Pipeline Downloads", $urllist);
		
	?>

	<table class="displaytable" width="100%">
		<thead>
			<tr>
				<th>Pipeline</th>
				<th>Admin</th>
				<th>Protocol</th>
				<th>Dir format</th>
				<th>NFS dir</th>
				<th>Anonymize?</th>
				<th>gzip?</th>
				<th>Preserve series?</th>
				<th>Group?</th>
				<th>Only new?</th>
				<th>File type</th>
				<th>Modality</th>
				<th>Beh format</th>
				<th>Create date</th>
			</tr>
		</thead>
		<tbody>
			<?
				$sqlstring = "select a.*, b.username 'adminusername', b.user_fullname 'adminfullname', c.pipeline_name from pipeline_download a left join users b on a.pd_admin = b.user_id left join pipelines c on a.pipeline_id = c.pipeline_id where a.pd_status = 'active' order by a.pd_protocol";
				$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
					$id = $row['pipelinedownload_id'];
					$pipeline_name = $row['pipeline_name'];
					$adminusername = $row['adminusername'];
					$adminfullname = $row['adminfullname'];
					$protocol = $row['pd_protocol'];
					$dirformat = $row['pd_dirformat'];
					$nfsdir = $row['pd_nfsdir'];
					$anonymize = $row['pd_anonymize'];
					$gzip = $row['pd_gzip'];
					$preserveseries = $row['pd_preserveseries'];
					$groupbyprotocol = $row['pd_groupbyprotocol'];
					$onlynew = $row['pd_onlynew'];
					$filetype = $row['pd_filetype'];
					$modality = $row['pd_modality'];
					$behformat = $row['pd_behformat'];
					$behdirrootname = $row['pd_behdirrootname'];
					$createdate = $row['pd_createdate'];
					$status = $row['pd_status'];
			?>
			<tr>
				<td><a href="pipelinedownload.php?action=editform&id=<?=$id?>"><?=$pipeline_name?></a></td>
				<td><?=$adminfullname?></td>
				<td><?=$protocol?></td>
				<td><?=$dirformat?></td>
				<td><?=$nfsdir?></td>
				<td><?if ($anonymize) echo "&#10004;";?></td>
				<td><?if ($gzip) echo "&#10004;";?></td>
				<td><?if ($preserveseries) echo "&#10004;";?></td>
				<td><?if ($groupbyprotocol) echo "&#10004;";?></td>
				<td><?if ($onlynew) echo "&#10004;";?></td>
				<td><?=$filetype?></td>
				<td><?=$modality?></td>
				<td><?=$behformat?> [<?=$behdirrootname?>]</td>
				<td><?=$createdate?></td>
			</tr>
			<? 
				}
			?>
		</tbody>
	</table>
	<?
	}
?>


<? include("footer.php") ?>
