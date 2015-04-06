<?
 // ------------------------------------------------------------------------------
 // NiDB system.php
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
	require_once "Mail.php";
	require_once "Mail/mime.php";

	session_start();
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - System</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes.php";
	require "menu.php";
?>

<?
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	
	/* determine action */
	if ($action == "") {
		DisplaySystem();
	}
	else {
		DisplaySystem();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- DisplaySystem ---------------------- */
	/* -------------------------------------------- */
	function DisplaySystem() {
	
		$urllist['System'] = "system.php";
		NavigationBar("System", $urllist);

		$dbconnect = true;
		$devdbconnect = true;
		$L = mysqli_connect($GLOBALS['cfg']['mysqlhost'],$GLOBALS['cfg']['mysqluser'],$GLOBALS['cfg']['mysqlpassword'],$GLOBALS['cfg']['mysqldatabase']) or $dbconnect = false;
		$Ldev = mysqli_connect($GLOBALS['cfg']['mysqldevhost'],$GLOBALS['cfg']['mysqldevuser'],$GLOBALS['cfg']['mysqldevpassword'],$GLOBALS['cfg']['mysqldevdatabase']) or $devdbconnect = false;
		
		?>
		<table class="entrytable">
			<tr>
				<td colspan="3" class="section">Database</td>
			</tr>
			<tr>
				<td class="label">Database</td>
				<td><?=$GLOBALS['cfg']['mysqluser']?>@<?=$GLOBALS['cfg']['mysqlhost']?>, <?=$GLOBALS['cfg']['mysqldatabase']?></td>
				<td><? if ($dbconnect) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
			</tr>
			<tr>
				<td class="label">Dev database</td>
				<td><?=$GLOBALS['cfg']['mysqldevuser']?>@<?=$GLOBALS['cfg']['mysqldevhost']?>, <?=$GLOBALS['cfg']['mysqldevdatabase']?></td>
				<td><? if ($devdbconnect) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
			</tr>
			<tr>
				<td colspan="3" class="section">Email</td>
			</tr>
			<tr>
				<td class="label">Email</td>
				<td><?=$GLOBALS['cfg']['emailusername']?>@<?=$GLOBALS['cfg']['emailserver']?>:<?=$GLOBALS['cfg']['emailport']?></td>
				<td><? if ($emailconnect) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
			</tr>
			<tr>
				<td colspan="3" class="section">Directories</td>
			</tr>
			<tr>
				<td class="label">Analysis</td>
				<td class="code"><?=$GLOBALS['cfg']['analysisdir']?></td>
				<td><? if (file_exists($GLOBALS['cfg']['analysisdir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
			</tr>
			<tr>
				<td class="label">Group analysis</td>
				<td class="code"><?=$GLOBALS['cfg']['groupanalysisdir']?></td>
				<td><? if (file_exists($GLOBALS['cfg']['groupanalysisdir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
			</tr>
			<tr>
				<td class="label">Archive</td>
				<td class="code"><?=$GLOBALS['cfg']['archivedir']?></td>
				<td><? if (file_exists($GLOBALS['cfg']['archivedir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
			</tr>
			<tr>
				<td class="label">Backup</td>
				<td class="code"><?=$GLOBALS['cfg']['backupdir']?></td>
				<td><? if (file_exists($GLOBALS['cfg']['backupdir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
			</tr>
			<tr>
				<td class="label">FTP</td>
				<td class="code"><?=$GLOBALS['cfg']['ftpdir']?></td>
				<td><? if (file_exists($GLOBALS['cfg']['ftpdir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
			</tr>
			<tr>
				<td class="label">Import</td>
				<td class="code"><?=$GLOBALS['cfg']['importdir']?></td>
				<td><? if (file_exists($GLOBALS['cfg']['importdir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
			</tr>
			<tr>
				<td class="label">Incoming</td>
				<td class="code"><?=$GLOBALS['cfg']['incomingdir']?></td>
				<td><? if (file_exists($GLOBALS['cfg']['incomingdir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
			</tr>
			<tr>
				<td class="label">Lock</td>
				<td class="code"><?=$GLOBALS['cfg']['lockdir']?></td>
				<td><? if (file_exists($GLOBALS['cfg']['lockdir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
			</tr>
			<tr>
				<td class="label">Log</td>
				<td class="code"><?=$GLOBALS['cfg']['logdir']?></td>
				<td><? if (file_exists($GLOBALS['cfg']['logdir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
			</tr>
			<tr>
				<td class="label">QC module</td>
				<td class="code"><?=$GLOBALS['cfg']['qcmoduledir']?></td>
				<td><? if (file_exists($GLOBALS['cfg']['qcmoduledir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
			</tr>
			<tr>
				<td class="label">Problem</td>
				<td class="code"><?=$GLOBALS['cfg']['problemdir']?></td>
				<td><? if (file_exists($GLOBALS['cfg']['problemdir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
			</tr>
			<tr>
				<td class="label">Scripts</td>
				<td class="code"><?=$GLOBALS['cfg']['scriptdir']?></td>
				<td><? if (file_exists($GLOBALS['cfg']['scriptdir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
			</tr>
			<tr>
				<td class="label">Web</td>
				<td class="code"><?=$GLOBALS['cfg']['webdir']?></td>
				<td><? if (file_exists($GLOBALS['cfg']['webdir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
			</tr>
			<tr>
				<td class="label">Download (for web-based downloads)</td>
				<td class="code"><?=$GLOBALS['cfg']['downloadpath']?></td>
				<td><? if (file_exists($GLOBALS['cfg']['downloadpath'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
			</tr>
			<tr>
				<td class="label">Uploaded</td>
				<td class="code"><?=$GLOBALS['cfg']['uploadpath']?></td>
				<td><? if (file_exists($GLOBALS['cfg']['uploadpath'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
			</tr>
			<tr>
				<td class="label">Temp</td>
				<td class="code"><?=$GLOBALS['cfg']['tmpdir']?></td>
				<td><? if (file_exists($GLOBALS['cfg']['tmpdir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
			</tr>
			<tr>
				<td class="label">Deleted</td>
				<td class="code"><?=$GLOBALS['cfg']['deletedpath']?></td>
				<td><? if (file_exists($GLOBALS['cfg']['deletedpath'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
			</tr>
			<tr>
				<td colspan="3" class="section">Directories</td>
			</tr>
			<tr>
				<td colspan="3">
					<pre><? echo system("crontab -l"); ?></pre>
				</td>
			</tr>
		</table>
		<?
	}
?>


<? include("footer.php") ?>
