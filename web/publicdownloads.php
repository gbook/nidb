<?
 // ------------------------------------------------------------------------------
 // NiDB publicdownloads.php
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
	session_start();
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Manage Public Downloads</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes.php";
	require "menu.php";
	require "nidbapi.php";
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$id = GetVariable("id");
	
	/* determine action */
	if ($action == "changepassword") {
		ChangePassword($id);
	}
	elseif ($action == "delete") {
		DeleteDownload($id);
	}
	else {
		DisplayDownloadList();
	}

	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- ChangePassword --------------------- */
	/* -------------------------------------------- */
	function ChangePassword($id, $password) {
		/* perform data checks */
		$pwd = sha1($password);
		
		/* update the site */
		$sqlstring = "update public_downloads set pd_password = '$pwd' where pd_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		?><div align="center"><span class="message"><?=$id?> updated</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- DeleteDownload --------------------- */
	/* -------------------------------------------- */
	function DeleteDownload($id) {
		//$sqlstring = "delete from nidb_sites where site_id = $id";
		//$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}	
	

	/* -------------------------------------------- */
	/* ------- DisplayDownloadList ---------------- */
	/* -------------------------------------------- */
	function DisplayDownloadList() {
	
		$urllist['Public Downloads'] = "publicdownloads.php";
		NavigationBar("Export", $urllist);
		
	?>

	<table class="graydisplaytable">
		<thead>
			<tr>
				<th>Description</th>
				<th>Status</th>
				<th>Created</th>
				<th>Expires</th>
				<th>Release notes</th>
				<th>Zip size<br><span class="tiny">bytes</span></th>
				<th>Unzipped size<br><span class="tiny">bytes</span></th>
				<th>Creator</th>
				<th>Password</th>
				<th>Download link<br><span class="tiny">Copy link to use</span></th>
			</tr>
		</thead>
		<tbody>
			<?
				$sqlstring = "select * from public_downloads where pd_createdby = '" . $_SESSION['username'] . "' or pd_shareinternal = 1 order by pd_createdate desc";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$id = $row['pd_id'];
					$createdate = $row['pd_createdate'];
					$expiredate = $row['pd_expiredate'];
					$expiredays = $row['pd_expiredays'];
					$createdby = $row['pd_createdby'];
					$zipsize = $row['pd_zippedsize'];
					$unzipsize = $row['pd_unzippedsize'];
					$filename = $row['pd_filename'];
					$desc = $row['pd_desc'];
					$notes = $row['pd_notes'];
					$filecontents = $row['pd_filecontents'];
					$shareinternal = $row['pd_shareinternal'];
					$registerrequired = $row['pd_registerrequired'];
					$password = strtoupper($row['pd_password']);
					$status = $row['pd_status'];
					$key = strtoupper($row['pd_key']);
			?>
			<tr>
				<td><?=$desc?></td>
				<td><?=$status?></td>
				<td style="font-size:9pt"><?=$createdate?></td>
				<td style="font-size:9pt"><?=$expiredate?></td>
				<td><img src="images/preview.gif" title="<?=$notes?>"></td>
				<td style="font-size:9pt" align="right"><?=HumanReadableFilesize($zipsize)?></td>
				<td style="font-size:9pt" align="right"><?=HumanReadableFilesize($unzipsize)?></td>
				<td><?=$createdby?></td>
				<td style="font-size:8pt"><?=$password?></td>
				<td><input type="text" value="<?=$GLOBALS['cfg']['siteurl'] . "/pd.php?k=$key"?>"></td>
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
