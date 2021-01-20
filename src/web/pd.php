<?
 // ------------------------------------------------------------------------------
 // NiDB pd.php
 // Copyright (C) 2004 - 2021
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
 	require "functions.php";
	require "includes_php.php";
	
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB Data Download</title>
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
				<a href="downloads.php">Back to downloads</a>
			</td>
		</tr>
	</table>
</div>
<div style="margin:20px">
<?
	/* ----- setup variables ----- */
	$k = GetVariable("k");
	$p = GetVariable("p");
	$a = GetVariable("a");

	//print_r($_SESSION);
	
	if (($a == "") && ($k == "")) {
		DisplayInvalidLink();
		exit(0);
	}
	
	/* database connection */
	//$linki = mysqli_connect($GLOBALS['cfg']['mysqlhost'], $GLOBALS['cfg']['mysqluser'], $GLOBALS['cfg']['mysqlpassword'], $GLOBALS['cfg']['mysqldatabase']) or die ("Could not connect. Error [" . mysqli_error() . "]  File [" . __FILE__ . "] Line [ " . __LINE__ . "]");

	/* validate the key and redirect as necessary */
	if ($a != "") {
		
	}
	else {
		DisplayDownload($k,$p);
	}

	/* -------------------------------------------- */
	/* ------- DisplayInvalidLink ----------------- */
	/* -------------------------------------------- */
	function DisplayInvalidLink() {
		?>
		<div align="center">
		<br><br>
		<b>Invalid Link</b><br>
		The link you are trying to access is not valid. Please contact the person who sent you the link
		</div>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayDownload -------------------- */
	/* -------------------------------------------- */
	function DisplayDownload($k, $p) {
		$k = mysqli_real_escape_string($GLOBALS['linki'], $k);

		if (trim($k) == "") {
			DisplayInvalidLink();
			return 0;
		}
		
		/* check if the key exists in the users_pending table */
		$sqlstring = "select * from public_downloads where pd_key = '$k'";
		//echo "$sqlstring<br>";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0) {
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$id = $row['pd_id'];
			$createdate = $row['pd_createdate'];
			$expiredate = $row['pd_expiredate'];
			$zipsize = $row['pd_zippedsize'];
			$unzipsize = $row['pd_unzippedsize'];
			$desc = $row['pd_desc'];
			$filename = $row['pd_filename'];
			$releasenotes = $row['pd_notes'];
			$filecontents = $row['pd_filecontents'];
			$numdownloads = $row['pd_numdownloads'];
			$registerrequired = $row['pd_registerrequired'];
		}
		else {
			DisplayInvalidLink();
			return 0;
		}
		
		/* create the download link on the filesystem */
		$newlink = sha1(time());
		$systemstring = "ln -s " . $GLOBALS['cfg']['webdownloaddir'] . "/$filename " . $GLOBALS['cfg']['webdownloaddir'] . "/$newlink.zip";
		`$systemstring`;

		?>
		<b><?=$desc?></b><br>
		<span class="tiny"><b>Created</b> <?=$createdate?><br>
		<b>Total size</b> <?=HumanReadableFilesize($unzipsize)?><br>
		<b>Downloads</b> <?=number_format($numdownloads, 0)?>
		</span>
		<br><br>
		<? if ($createdate != $expiredate) { ?>
		Download valid through <b><?=$expiredate?></b>
		<br><br>
		<? } ?>
		Release notes:
		<details>
		<summary style="font-size:9pt">View</summary>
		<div style="border: solid 1px gray; background-color: #eee; margin:10px; padding:8px">
<tt><?=$releasenotes?></tt>
		</div>
		</details>
		<br>
		Download contents:
		<details>
		<summary style="font-size:9pt">View</summary>
		<pre style="border: solid 1px gray; background-color: #eee; margin:10px; padding:8px">
<?=$filecontents?>
		</pre>
		</details>
		<br>
		<br>
		<br>
		<?
		if (($registerrequired) && ($_SESSION['validlogin'] != true)) {
			?>
			The creator of this download has requested that you register with NiDB before downloading. Click this <a href="signup.php">link</a> to register. Then you can proceed to the download page.
			<?
		}
		else {
			?>
			<div align="center"><a href="download/<?="$newlink.zip";?>" style="border: 2px orange solid; background-color: #fccd8f; padding: 15px 30px 25px 30px; border-radius:10px; color: #CA5900; font-weight: bold">Download</a><br><span style="font-size: 8pt"><?=HumanReadableFilesize($zipsize)?></span></div>
			<?
			/* increment the numdownload for this download... i know, its not really accurate, but it'll be a ballpark figure. Chances are if they're coming to this page, they're only here to click this download link */
			$sqlstring = "update public_downloads set pd_numdownloads = pd_numdownloads + 1 where pd_id = $id";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		return 1;
	}
?>
</div>