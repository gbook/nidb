<?
	header('Content-Type: text/html; charset=utf-8');

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
	require "includes_html.php";
	
?>

<html>
	<head>
		<meta charset="utf-8"/>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB Data Download</title>
	</head>

<body>
<link rel="stylesheet" type="text/css" href="style.css">
<br><br>
<div class="ui container">
	<div class="ui segment">
		<div class="ui two column grid">
			<div class="column">
				<div class="ui header">
					<em data-emoji=":chipmunk:" class="medium"></em>
					<div class="content">
						<h2>NiDB Download</h2>
						<div class="sub header">
						<? if ($_SESSION['username'] == "") { ?>
							<a href="signup.php">Create</a> an account or <a href="login.php">Sign in</a>
						<? } else {?>
							You are logged into NiDB as <?=$_SESSION['username'];?><br>
							Go to your <a href="index.php">home</a> page
						<? } ?>
						</div>
					</div>
				</div>
			</div>
			<div class="right aligned column">
				<a href="downloads.php" class="ui basic button">View Public Downloads</a>
			</div>
		</div>
	</div>
<?
	/* ----- setup variables ----- */
	$k = GetVariable("k");
	$p = GetVariable("p");
	$a = GetVariable("a");

	if (($a == "") && ($k == "")) {
		DisplayInvalidLink();
		exit(0);
	}
	
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
		Error("This link is invalid or expired. Please contact the person who sent you the link.");
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
		<div class="ui segment">
			<div class="ui two column grid">
				<div class="column">
					<h1 class="ui header"><?=$desc?></h1>
					<b>Created</b> <?=$createdate?><br>
					<b>Total size</b> <?=HumanReadableFilesize($unzipsize)?><br>
					<b>Views</b> <?=number_format($numdownloads, 0)?>
					<br><br>
					<? if ($createdate != $expiredate) { ?>
					<b>Download expires</b> <?=$expiredate?>
					<? } ?>
				</div>
				<div class="middle aligned center aligned column">
					<?
					if (($registerrequired) && ($_SESSION['validlogin'] != true)) {
						?>
						The creator of this download requires users to create an account with NiDB before downloading. Click this <a href="signup.php">link</a> to register, or ask the NiDB system admin to create an account.
						<br>
						<div class="ui labeled button">
							<a href="" class="ui big grey button"><i class="cloud download alternate icon"></i>Download</a>
							<div class="ui left pointing grey basic label">
								<?=HumanReadableFilesize($zipsize)?>
							</div>
						</div>
						<?
					}
					else {
						?>
						<div class="ui labeled button">
							<a href="download/<?="$newlink.zip";?>" class="ui big orange button"><i class="cloud download alternate icon"></i>Download</a>
							<div class="ui left pointing basic label">
								<?=HumanReadableFilesize($zipsize)?>
							</div>
						</div>
						<?
						/* increment the numdownload for this download... i know, its not really accurate, but it'll be a ballpark figure. Chances are if they're coming to this page, they're only here to click this download link */
						$sqlstring = "update public_downloads set pd_numdownloads = pd_numdownloads + 1 where pd_id = $id";
						$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					}
					?>
				</div>
			</div>
			<div class="ui accordion">
				<div class="title">
					<i class="dropdown icon"></i>
					Release notes
				</div>
				<div class="content">
					<div class="ui segment" style="border: solid 1px gray; background-color: #eee; margin:10px; padding:8px">
<tt><?=$releasenotes?></tt>
					</div>
				</div>
			</div>

			<div class="ui accordion">
				<div class="title">
					<i class="dropdown icon"></i>
					Download contents
				</div>
				<div class="content">
					<div class="ui segment" style="border: solid 1px gray; background-color: #eee; margin:10px; padding:8px">
<pre><?=$filecontents?></pre>
					</div>
				</div>
			</div>
			
		</div>
		<?
		return 1;
	}
?>
</div>