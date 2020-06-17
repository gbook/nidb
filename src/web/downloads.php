<?
 // ------------------------------------------------------------------------------
 // NiDB publicdownloads.php
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
	
	$nologin = true;
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Public Downloads</title>
	</head>

<body>
<link rel="stylesheet" type="text/css" href="style.css">
<div style="text-align: left; background-color: #eee; border-bottom: 2px solid #666; padding: 20px;">
	<table width="100%">
		<tr>
			<td>
				<span style="font-weight:bold; font-size:18pt; color: #35486D">NeuroInformatics Database public downloads</span>
				<br><br>
				<? if ($_SESSION['username'] == "") { ?>
				<a href="signup.php">Create</a> an account | <a href="login.php">Sign in</a>
				<? } else {?>
				<span style="font-size:9pt">You are logged into NiDB as <?=$_SESSION['username'];?><br>
				Go to your <a href="index.php">home</a> page
				<? } ?>
			</td>
			<td align="right">
				<img src="images/nidb_short_notext_small.png">
			</td>
		</tr>
	</table>
</div>
<div style="margin:20px">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	
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
	/* ------- DisplayDownloadList ---------------- */
	/* -------------------------------------------- */
	function DisplayDownloadList() {
	?>

	<p style="background-color: #FFFFDF; border: 1px solid yellow; padding: 8px"><b>Notes</b><br>Some downloads may require registration. Click Download link to view release notes and contents of the download file. All downloads were created from data stored on this server. More detailed search criteria and QC information is available by logging in to the server and going to the Search page.</p>
	
	<div align="center">
	<table class="graydisplaytable" width="90%">
		<thead>
			<tr>
				<th>Description</th>
				<th>Created</th>
				<th>Expire date</th>
				<th>Release notes</th>
				<th>Zip size</th>
				<th>Unzipped size</th>
				<th># downloaded</th>
				<th>&nbsp;<span style="color: red; font-size:16pt">*</span> <span class="tiny">registration required</span></th>
			</tr>
		</thead>
		<tbody>
			<?
				$sqlstring = "select * from public_downloads where pd_status = 'complete' and pd_ispublic = 1 order by pd_desc asc";
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
					$status = $row['pd_status'];
					$key = strtoupper($row['pd_key']);
					$numdownload = $row['pd_numdownloads'];
					
					if ($createdate == $expiredate) {
						$expiredate = "None";
					}
			?>
			<tr>
				<td><?=$desc?></td>
				<td style="font-size:9pt"><?=$createdate?></td>
				<td style="font-size:9pt"><?=$expiredate?></td>
				<td><img src="images/preview.gif" title="<?=$notes?>"></td>
				<td style="font-size:9pt" align="right"><?=HumanReadableFilesize($zipsize)?></td>
				<td style="font-size:9pt" align="right"><?=HumanReadableFilesize($unzipsize)?></td>
				<td style="font-size:9pt" align="right"><?=$numdownload?></td>
				<td>&nbsp;<a href="<?=$GLOBALS['cfg']['siteurl'] . "/pd.php?k=$key"?>">Download</a>&nbsp; <? if ($registerrequired) { ?><span style="color: red; font-size:16pt">*</span><?} ?></td>
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
