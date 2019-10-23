<?
 // ------------------------------------------------------------------------------
 // NiDB setup.php
 // Copyright (C) 2004 - 2019
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

	declare(strict_types = 1);
	define("LEGIT_REQUEST", true);
	session_start();
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Setup</title>
	</head>
	
	<style>
		.t td { vertical-align: top; padding: 7px;}
		.t td:first-child { text-align: right; color: #444; font-weight: bold; }
		.t td:last-child { text-align: left; color: darkblue; }
		.e { font-weight: bold; color: #444; }
		.good:before { content: '\2714'; font-weight: bold; color: green; display: inline-block; }
		.bad:before { content: '\2718'; font-weight: bold; color: red; display: inline-block; }
		input:invalid { background: #ffdbc9; border: 2px solid orange !important; }
		/* input:required { border: 1px solid orange !important; } */
	</style>

<body>
	<div id="wrapper">
<?
	/* check if the .cfg file exists */
	if ( (file_exists('nidb.cfg')) || (file_exists('../nidb.cfg')) || (file_exists('../programs/nidb.cfg')) || (file_exists('/home/nidb/programs/nidb.cfg')) || (file_exists('/nidb/programs/nidb.cfg')) ) {
		/* if so, load the config, but still treat the page as a setup */
	}
	
	$setup = true;
	
	require "functions.php";
	require "includes_html.php";
	
	//PrintVariable($_POST);
	
	/* ----- setup variables ----- */
	$step = GetVariable("step");

    $c['debug'] = GetVariable("debug");
    $c['hideerrors'] = GetVariable("hideerrors");
	
    $c['mysqlhost'] = GetVariable("mysqlhost");
    $c['mysqluser'] = GetVariable("mysqluser");
    $c['mysqlpassword'] = GetVariable("mysqlpassword");
    $c['mysqldatabase'] = GetVariable("mysqldatabase");
	$c['mysqldevhost'] = GetVariable("mysqldevhost");
    $c['mysqldevuser'] = GetVariable("mysqldevuser");
    $c['mysqldevpassword'] = GetVariable("mysqldevpassword");
    $c['mysqldevdatabase'] = GetVariable("mysqldevdatabase");
    $c['mysqlclusteruser'] = GetVariable("mysqlclusteruser");
    $c['mysqlclusterpassword'] = GetVariable("mysqlclusterpassword");

    $c['modulefileiothreads'] = GetVariable("modulefileiothreads");
    $c['moduleexportthreads'] = GetVariable("moduleexportthreads");
    $c['moduleimportthreads'] = GetVariable("moduleimportthreads");
    $c['modulemriqathreads'] = GetVariable("modulemriqathreads");
    $c['modulepipelinethreads'] = GetVariable("modulepipelinethreads");
    $c['moduleimportuploadedthreads'] = GetVariable("moduleimportuploadedthreads");
    $c['moduleqcthreads'] = GetVariable("moduleqcthreads");
	
    //$c['emaillib'] = GetVariable("emaillib");
    $c['emailusername'] = GetVariable("emailusername");
    $c['emailpassword'] = GetVariable("emailpassword");
    $c['emailserver'] = GetVariable("emailserver");
    $c['emailport'] = GetVariable("emailport");
    $c['emailfrom'] = GetVariable("emailfrom");
    $c['adminemail'] = GetVariable("adminemail");
	
    $c['siteurl'] = GetVariable("siteurl");
    
    $c['fslbinpath'] = GetVariable("fslbinpath");
	
	$c['usecluster'] = GetVariable("usecluster");
    $c['queuename'] = GetVariable("queuename");
    $c['queueuser'] = GetVariable("queueuser");
    $c['clustersubmithost'] = GetVariable("clustersubmithost");
    $c['qsubpath'] = GetVariable("qsubpath");
    $c['clusteruser'] = GetVariable("clusteruser");
    $c['clusternidbpath'] = GetVariable("clusternidbpath");

    $c['version'] = GetVariable("version");
    $c['sitename'] = GetVariable("sitename");
    $c['sitenamedev'] = GetVariable("sitenamedev");
    $c['sitecolor'] = GetVariable("sitecolor");
    $c['ispublic'] = GetVariable("ispublic");
    $c['sitetype'] = GetVariable("sitetype");
    $c['allowphi'] = GetVariable("allowphi");
    $c['allowrawdicomexport'] = GetVariable("allowrawdicomexport");
    $c['enableremoteconn'] = GetVariable("enableremoteconn");
    $c['enablecalendar'] = GetVariable("enablecalendar");
    $c['uploadsizelimit'] = GetVariable("uploadsizelimit");
    $c['displayrecentstudies'] = GetVariable("displayrecentstudies");
    $c['displayrecentstudydays'] = GetVariable("displayrecentstudydays");

    $c['importchunksize'] = GetVariable("importchunksize");
    $c['numretry'] = GetVariable("numretry");
    $c['enablenfs'] = GetVariable("enablenfs");
    $c['enableftp'] = GetVariable("enableftp");

    $c['enablecas'] = GetVariable("enablecas");
    $c['casserver'] = GetVariable("casserver");
    $c['casport'] = GetVariable("casport");
    $c['cascontext'] = GetVariable("cascontext");
    
	$c['localftphostname'] = GetVariable("localftphostname");
    $c['localftpusername'] = GetVariable("localftpusername");
    $c['localftppassword'] = GetVariable("localftppassword");
	
	$c['analysisdir'] = GetVariable("analysisdir");
	$c['analysisdirb'] = GetVariable("analysisdirb");
	$c['clusteranalysisdir'] = GetVariable("clusteranalysisdir");
	$c['clusteranalysisdirb'] = GetVariable("clusteranalysisdirb");
    $c['groupanalysisdir'] = GetVariable("groupanalysisdir");
    $c['archivedir'] = GetVariable("archivedir");
    $c['backupdir'] = GetVariable("backupdir");
    $c['ftpdir'] = GetVariable("ftpdir");
    $c['importdir'] = GetVariable("importdir");
    $c['incomingdir'] = GetVariable("incomingdir");
    $c['incoming2dir'] = GetVariable("incoming2dir");
    $c['lockdir'] = GetVariable("lockdir");
    $c['logdir'] = GetVariable("logdir");
    $c['mountdir'] = GetVariable("mountdir");
    $c['packageimportdir'] = GetVariable("packageimportdir");
    $c['qcmoduledir'] = GetVariable("qcmoduledir");
    $c['problemdir'] = GetVariable("problemdir");
    $c['scriptdir'] = GetVariable("scriptdir");
    $c['webdir'] = GetVariable("webdir");
    $c['webdownloaddir'] = GetVariable("webdownloaddir");
    $c['downloaddir'] = GetVariable("downloaddir");
    $c['uploadeddir'] = GetVariable("uploadeddir");
    $c['tmpdir'] = GetVariable("tmpdir");
    $c['deleteddir'] = GetVariable("deleteddir");
	$systemmessage = GetVariable("systemmessage");
	$messageid = GetVariable("messageid");

	$rootpassword = GetVariable("rootpassword");
	$userpassword = GetVariable("userpassword");
	$userpassword2 = GetVariable("userpassword2");
	
	/* determine the setup step */
	switch ($step) {
		case 'updateconfig':
			WriteConfig($c);
			break;
		case 'testemail':
			TestEmail();
			break;
		case 'welcome':
			DisplayWelcomePage();
			break;
		case 'systemcheck':
			DisplaySystemCheckPage();
			break;
		case 'database1':
			DisplayDatabase1Page();
			break;
		case 'database2':
			DisplayDatabase2Page($rootpassword, $userpassword, $userpassword2);
			break;
		case 'config':
			DisplayConfigPage();
			break;
		case 'setupcomplete':
			DisplaySetupCompletePage();
			break;
		default:
			DisplayWelcomePage();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- TestEmail -------------------------- */
	/* -------------------------------------------- */
	function TestEmail() {
		$to = $GLOBALS['cfg']['adminemail'];
		$subject = "Testing email send from " . $GLOBALS['cfg']['sitename'] . " (" . $GLOBALS['cfg']['siteurl'] . ")";
		$body = "If you receive this message, your NiDB email is working";
		
		/* send the email */
		if (!SendGmail($to,$subject,$body, 1, 0)) {
			return "System error. Unable to send email!";
		}
	}
	
	
	/* -------------------------------------------- */
	/* ------- WriteConfig ------------------------ */
	/* -------------------------------------------- */
	function WriteConfig($c) {
		
		/* escape all the variables and put them back into meaningful variable names */
		foreach ($c as $key => $value) {
			if (is_scalar($value)) {
				$$key = trim($c[$key]);
			}
			else {
				$$key = $c[$key];
			}
		}
		
		$year = date("Y");
		
		$str = "# NiDB configuration file
# ------------------------------------------------------------------------------
# NIDB nidb.cfg
# Copyright (C) 2004-$year
# Gregory A Book (gregory.book@hhchealth.org) (gregory.a.book@gmail.com)
# Olin Neuropsychiatry Research Center, Hartford Hospital
# ------------------------------------------------------------------------------
# GPLv3 License:
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see http://www.gnu.org/licenses/.
# ------------------------------------------------------------------------------

# ----- System availability -----
[offline] = 0

# ----- Debug -----
[debug] = $debug
[hideerrors] = $hideerrors

# ----- Database -----
[mysqlhost] = $mysqlhost
[mysqldatabase] = $mysqldatabase
[mysqluser] = $mysqluser
[mysqlpassword] = $mysqlpassword
[mysqldevhost] = $mysqldevhost
[mysqldevdatabase] = $mysqldevdatabase
[mysqldevuser] = $mysqldevuser
[mysqldevpassword] = $mysqldevpassword
[mysqlclusteruser] = $mysqlclusteruser
[mysqlclusterpassword] = $mysqlclusterpassword

# ----- modules -----
[modulefileiothreads] = $modulefileiothreads
[moduleexportthreads] = $moduleexportthreads
[moduleimportthreads] = $moduleimportthreads
[modulemriqathreads] = $modulemriqathreads
[modulepipelinethreads] = $modulepipelinethreads
[moduleimportuploadedthreads] = $moduleimportuploadedthreads
[moduleqcthreads] = $moduleqcthreads

# ----- E-mail -----
//# emaillib options (case-sensitive): Net-SMTP-TLS (default), Email-Send-SMTP-Gmail
//[emaillib] = $emaillib
[emailusername] = $emailusername
[emailpassword] = $emailpassword
[emailserver] = $emailserver
[emailport] = $emailport
[emailfrom] = $emailfrom
[adminemail] = $adminemail

# ----- Site/server options -----
[siteurl] = $siteurl
[version] = $version
[sitename] = $sitename
[sitenamedev] = $sitenamedev
[sitecolor] = $sitecolor
[ispublic] = $ispublic
[sitetype] = $sitetype
[allowphi] = $allowphi
[allowrawdicomexport] = $allowrawdicomexport
[enableremoteconn] = $enableremoteconn
[enablecalendar] = $enablecalendar
[uploadsizelimit] = $uploadsizelimit
[displayrecentstudies] = $displayrecentstudies
[displayrecentstudydays] = $displayrecentstudydays

# ----- import/export options -----
[importchunksize] = $importchunksize
[numretry] = $numretry
[enablenfs] = $enablenfs
[enableftp] = $enableftp

# ----- qc -----
[fslbinpath] = $fslbinpath

# ----- cluster -----
[usecluster] = $usecluster
[queuename] = $queuename
[queueuser] = $queueuser
[clustersubmithost] = $clustersubmithost
[qsubpath] = $qsubpath
[clusteruser] = $clusteruser
[clusternidbpath] = $clusternidbpath

# ----- CAS authentication -----
[enablecas] = $enablecas
[casserver] = $casserver
[casport] = $casport
[cascontext] = $cascontext

# ----- local FTP info -----
[localftphostname] = $localftphostname
[localftpusername] = $localftpusername
[localftppassword] = $localftppassword

# ----- Directories (alphabetical list) -----
[analysisdir] = $analysisdir
[analysisdirb] = $analysisdirb
[clusteranalysisdir] = $clusteranalysisdir
[clusteranalysisdirb] = $clusteranalysisdirb
[groupanalysisdir] = $groupanalysisdir
[archivedir] = $archivedir
[backupdir] = $backupdir
[deleteddir] = $deleteddir
[downloaddir] = $downloaddir
[ftpdir] = $ftpdir
[importdir] = $importdir
[incomingdir] = $incomingdir
[incoming2dir] = $incoming2dir
[lockdir] = $lockdir
[logdir] = $logdir
[mountdir] = $mountdir
[packageimportdir] = $packageimportdir
[qcmoduledir] = $qcmoduledir
[problemdir] = $problemdir
[scriptdir] = $scriptdir
[tmpdir] = $tmpdir
[uploadeddir] = $uploadeddir
[webdir] = $webdir
[webdownloaddir] = $webdownloaddir
";

		$ret = file_put_contents($GLOBALS['cfg']['cfgpath'], $str);
		if (($ret === false) || ($ret === false) || ($ret == 0)) {
			?><div class="staticmessage">Problem writing [<?=$GLOBALS['cfg']['cfgpath']?>]. Is the file writeable to the [<?=system("whoami"); ?>] account?</div><?
		}
		else {
			?><div class="staticmessage">Config file has been written to <?=$GLOBALS['cfg']['cfgpath']?></div><?
		}

		/* write a cconfig file for when NiDB is run from a cluster. this only contains basic info, separate DB login, and no paths */
		$str = "# NiDB cluster configuration file (for nidb running on the cluster)
# ------------------------------------------------------------------------------
# NIDB nidb.cfg
# Copyright (C) 2004-$year
# Gregory A Book (gregory.book@hhchealth.org) (gregory.a.book@gmail.com)
# Olin Neuropsychiatry Research Center, Hartford Hospital
# ------------------------------------------------------------------------------
# GPLv3 License:
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see http://www.gnu.org/licenses/.
# ------------------------------------------------------------------------------

# ----- Database -----
[mysqlhost] = $mysqlhost
[mysqldatabase] = $mysqldatabase
[mysqlclusteruser] = $mysqlclusteruser
[mysqlclusterpassword] = $mysqlclusterpassword

# ----- Site/server options -----
[version] = $version
[sitename] = $sitename
[clusteranalysisdir] = $clusteranalysisdir
[clusteranalysisdirb] = $clusteranalysisdirb

# ----- qc -----
[fslclusterbinpath] = $fslclusterbinpath
";
		$clustercfgfile = dirname($GLOBALS['cfg']['cfgpath']) . "/nidb-cluster.cfg";
		$ret = file_put_contents($clustercfgfile, $str);
		if (($ret === false) || ($ret === false) || ($ret == 0)) {
			?><div class="staticmessage">Problem writing [<?=$clustercfgfile?>]. Is the file writeable to the [<?=system("whoami"); ?>] account?</div><?
		}
		else {
			?><div class="staticmessage">Cluster config file has been written to <?=$clustercfgfile?></div><?
		}
		
	
	}


	/* -------------------------------------------- */
	/* ------- DisplayWelcomePage ----------------- */
	/* -------------------------------------------- */
	function DisplayWelcomePage() {
		
		?>
		<br><br>
		<div align="center" valign="middle">
		<table width="60%" height="60%" cellpadding="20" cellspacing="0" style="border: 2px solid #888; border-radius: 10px">
			<tr>
				<td width="20%" valign="top" style="border-right: 2px solid #888" rowspan="2"><?=DisplaySetupMenu("welcome")?></td>
				<td valign="top" height="90%">
					The following pages will guide you through the NiDB setup process
				</td>
			</tr>
			<tr>
				<td></td>
				<td align="right">
					<a href="setup.php?step=systemcheck">Next</a>
				</td>
			</tr>
		</table>
		<?
		
	}


	/* -------------------------------------------- */
	/* ------- DisplaySystemCheckPage ------------- */
	/* -------------------------------------------- */
	function DisplaySystemCheckPage() {
		
		?>
		<br><br>
		<div align="center" valign="middle">
		<table width="60%" height="60%" cellpadding="20" cellspacing="0" style="border: 2px solid #888; border-radius: 10px">
			<tr>
				<td width="20%" valign="top" style="border-right: 2px solid #888" rowspan="2"><?=DisplaySetupMenu("systemcheck")?></td>
				<td valign="top" height="90%">
					Required Linux packages<br><br>
					
					<?
					$memory = preg_split('/\s+/', shell_exec("free -g"))[8];
					
					$cores = (int)shell_exec("cat /proc/cpuinfo | grep processor | wc -l");

					/* check the MariaDB version */
					$mariadb = shell_exec("mysql --version");
					if (is_null($mariadb))
						$mariadbver = "<span style='color:red'>Not Installed</span>";
					else
						$mariadbver = str_replace("-MariaDB,", "", preg_split('/\s+/', $mariadb)[4]);
					
					$mvers = explode(".",$mariadbver);
					if ($mvers[0] < 10) {
						$mariadbver = "<span style='color:red'>$mariadbver (must be version 10.0+)</span>";
					}
					
					/* check the httpd version */
					$httpd = apache_get_version();
					if (is_null($httpd))
						$httpdver = "<span style='color:red'>Not Installed</span> ... how are you viewing this?";
					else
						$httpdver = str_replace("Apache/", "", preg_split('/\s+/', $httpd)[2]);
					
					/* check the image magick version */
					$imagemagick = shell_exec("convert -version");
					if (is_null($imagemagick))
						$imagemagickver = "<span style='color:red'>Not Installed</span>";
					else
						$imagemagickver = preg_split('/\s+/', $imagemagick)[2];

					/* check the PHP version */
					$phpversion = phpversion();
					$mvers = explode(".", $phpversion);
					if ($mvers[0] < 7) {
						$phpver = "<span style='color:red'>$phpversion (must be version 7.0+)</span>";
					}
					else
						$phpver = $phpversion;

					?>
					<table cellpadding="5">
						<tr>
							<td align="right">OS</td>
							<td><?=php_uname();?></td>
						</tr>
						<tr>
							<td align="right">CPU Cores</td>
							<td><?=$cores;?></td>
						</tr>
						<tr>
							<td align="right">System memory</td>
							<td><?=$memory;?> GB</td>
						</tr>
						<tr>
							<td align="right">Apache&nbsp;(httpd)</td>
							<td><b><?=$httpdver?></b> &nbsp; <span class="tiny"><?=$httpd?></span></td>
						</tr>
						<tr>
							<td align="right">MariaDB&nbsp;(mysql)</td>
							<td><b><?=$mariadbver?></b> &nbsp; <span class="tiny"><?=$mariadb?></span></td>
						</tr>
						<tr>
							<td align="right">PHP</td>
							<td><b><?=$phpver?></b></td>
						</tr>
						<tr>
							<td align="right">ImageMagick</td>
							<td><b><?=$imagemagickver?></b> &nbsp; <span class="tiny"><?=$imagemagick?></span></td>
						</tr>
					</table>
					<br><br>
					If any packages are not found or are not the right version, install them using yum or other methods. Then come back and refresh this page.
				</td>
			</tr>
			<tr>
				<td></td>
				<td align="right">
					<a href="setup.php?step=database1">Next</a>
				</td>
			</tr>
		</table>
		<?
		
	}


	/* -------------------------------------------- */
	/* ------- DisplayDatabase1Page --------------- */
	/* -------------------------------------------- */
	function DisplayDatabase1Page() {
		
		$cfg = LoadConfig();
		
		if (!is_null($cfg)) {
			$mysqlhost = $cfg['mysqlhost'];
			$mysqldatabase = $cfg['mysqldatabase'];
			$mysqluser = $cfg['mysqluser'];
			$mysqlpassword = $cfg['mysqlpassword'];
		}
		?>
		<br><br>
		<div align="center" valign="middle">
		<table width="60%" height="60%" cellpadding="20" cellspacing="0" style="border: 2px solid #888; border-radius: 10px">
			<tr>
				<td width="20%" valign="top" style="border-right: 2px solid #888" rowspan="2"><?=DisplaySetupMenu("database")?></td>
				<td valign="top" height="90%">
					<br>
					Enter the MySQL database connection parameters
					<br>
					<br>
					<form method="post" action="setup.php" name="theform">
					<input type="hidden" name="step" value="database2">
					<table class="t">
						<tr>
							<td>MySQL server</td>
							<td>
								<? if ($mysqlhost == "") { ?>
								localhost
								<? } else { ?>
								<span style="color: darkred; font-weight: bold"><?=$mysqlhost?></span><br>
								<span class="tiny">Loaded from config file</span>
								<? }?>
							</td>
						</tr>
						<tr>
							<td>Database name</td>
							<td>
								<? if ($mysqldatabase == "") { ?>
								nidb
								<? } else { ?>
								<span style="color: darkred; font-weight: bold"><?=$mysqldatabase?></span><br>
								<span class="tiny">Loaded from config file</span>
								<? }?>
							</td>
						</tr>
						<tr>
							<td>MySQL root password</td>
							<td>
								<input type="password" name="rootpassword"><br><span class="tiny">Should be <tt>password</tt> if this is the <u>first</u> NiDB installation</span>
							</td>
						</tr>
						<tr>
							<td>MySQL username</td>
							<td>
								<? if ($mysqluser == "") { ?>
								nidb
								<? } else { ?>
								<span style="color: darkred; font-weight: bold"><?=$mysqluser?></span><br>
								<span class="tiny">Loaded from config file</span>
								<? }?>
							</td>
						</tr>
						<tr>
							<td>MySQL user password</td>
							<td>
								<?
									if ($mysqlpassword != "")
										$cfgpassword = $mysqlpassword;
									else 
										$cfgpassword = "";
								?>
								<input type="password" name="nidbpassword" value="<?=$cfgpassword?>"><br>
								<input type="password" name="nidbpassword2" value="<?=$cfgpassword?>">
								<? if ($mysqlpassword != "") { ?>
								<br><span class="tiny">Password loaded from config file. Change here if needed</span>
								<? } ?>
							</td>
						</tr>
					</table>
					</form>
				</td>
			</tr>
			<tr>
				<td></td>
				<td align="right">
					<a onClick="document.theform.submit()" style="cursor: hand">Next</a>
				</td>
			</tr>
		</table>
		<?
		
	}


	/* -------------------------------------------- */
	/* ------- DisplayDatabase2Page --------------- */
	/* -------------------------------------------- */
	function DisplayDatabase2Page($rootpassword, $userpassword, $userpassword2) {
		
		?>
		<br><br>
		<div align="center" valign="middle">
		<table width="60%" height="60%" cellpadding="20" cellspacing="0" style="border: 2px solid #888; border-radius: 10px">
			<tr>
				<td width="20%" valign="top" style="border-right: 2px solid #888" rowspan="2"><?=DisplaySetupMenu("database")?></td>
				<td valign="top" height="90%" width="100%">
					<div style="width: 800px; height: 100%; overflow: auto; overflow-x:auto">
					Database setup
					<ol>
					<?
					
					$GLOBALS['linki'] = mysqli_connect('localhost', 'root', $rootpassword);
					
					if (!$GLOBALS['linki']) {
						?>
						<li><span class="bad"></span> Unable to connect to database:<br>
						Error number: <?=mysqli_connect_errno()?><br>
						Error message: <?=mysqli_connect_error()?>
						<?
					}
					else {
						?><li><span class="good"></span> Successfully connected to the database server<?
						
						/* check if the database itself exists */
						$sqlstring = "show databases like 'nidb'";
						$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
						if (mysqli_num_rows($result) > 0) {
							?><li><span class="good"></span> Database 'nidb' exists<?
							
							/* check if there are any tables */
							$sqlstring = "SELECT COUNT(DISTINCT `table_name`) FROM `information_schema`.`columns` WHERE `table_schema` = 'nidb'";
							$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
							if (mysqli_num_rows($result) > 0) {
								?><li><span class="good"></span> Existing tables found in 'nidb' database. Upgrading SQL schema<br><?
								UpgradeDatabase($GLOBALS['linki'], 'nidb', "/nidb/nidb.sql");
							}
							else {
								?><li>No tables found in 'nidb' database. Running full SQL script<?
								/* load the sql file(s) */
								if (file_exists("/nidb/nidb.sql")) {
									$systemstring = "mysql -uroot -p$rootpassword nidb < /nidb/nidb.sql";
									shell_exec($systemstring);
									
									if (file_exists("/nidb/nidb-data.sql")) {
										$systemstring = "mysql -uroot -p$rootpassword nidb < /nidb/nidb-data.sql";
										shell_exec($systemstring);
									}
									else {
										?><li><span class="bad"></span> <tt>/nidb/nidb-data.sql</tt> not found. This file should have been provided by the installer<?
									}
								}
								else {
									?><li><span class="bad"></span> <tt>/nidb/nidb.sql</tt> not found. This file should have been provided by the installer<?
								}

							}
						}
						else {
							$sqlstring = "create database 'nidb'";
							$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
							?><li>Created database 'nidb'<?
							
							/* load the sql file(s) */
							if (file_exists("/nidb/nidb.sql")) {
								$systemstring = "mysql -uroot -p$rootpassword nidb < /nidb/nidb.sql";
								shell_exec($systemstring);
								
								if (file_exists("/nidb/nidb-data.sql")) {
									$systemstring = "mysql -uroot -p$rootpassword nidb < /nidb/nidb-data.sql";
									shell_exec($systemstring);
								}
								else {
									?><li><span class="bad"></span> <tt>/nidb/nidb-data.sql</tt> not found. This file should have been provided by the installer<?
								}
							}
							else {
								?><li><span class="bad"></span> <tt>/nidb/nidb.sql</tt> not found. This file should have been provided by the installer<?
							}
						}
					}
					
					?>
					</ol>
					</div>
				</td>
			</tr>
			<tr>
				<td></td>
				<td align="right">
					<a href="setup.php?step=config">Next</a>
				</td>
			</tr>
		</table>
		<?
		
	}


	/* -------------------------------------------- */
	/* ------- DisplayConfigPage ------------------ */
	/* -------------------------------------------- */
	function DisplayConfigPage() {
		
		?>
		<br><br>
		<div align="center" valign="middle">
		<table width="80%" height="60%" cellpadding="20" cellspacing="0" style="border: 2px solid #888; border-radius: 10px">
			<tr>
				<td width="20%" valign="top" style="border-right: 2px solid #888" rowspan="2"><?=DisplaySetupMenu("config")?></td>
				<td valign="top" height="90%">
					NiDB configuration and settings
				</td>
			</tr>
			<tr>
				<td><? DisplayConfig(); ?></td>
				<td align="right" valign="bottom">
					<a href="setup.php?step=setupcomplete">Next</a>
				</td>
			</tr>
		</table>
		<?
		
	}


	/* -------------------------------------------- */
	/* ------- DisplaySetupCompletePage ----------- */
	/* -------------------------------------------- */
	function DisplaySetupCompletePage() {
		
		?>
		<br><br>
		<div align="center" valign="middle">
		<table width="60%" height="60%" cellpadding="20" cellspacing="0" style="border: 2px solid #888; border-radius: 10px">
			<tr>
				<td width="20%" valign="top" style="border-right: 2px solid #888" rowspan="2"><?=DisplaySetupMenu("setupcomplete")?></td>
				<td valign="top" height="90%">
					Setup Complete
				</td>
			</tr>
			<tr>
				<td></td>
				<td align="right">
					<a href="index.php">Done</a>
				</td>
			</tr>
		</table>
		<?
		
	}


	/* -------------------------------------------- */
	/* ------- DisplaySetupMenu ------------------- */
	/* -------------------------------------------- */
	function DisplaySetupMenu($step) {
		?>
		<style>
			.highlighted { background-color: #526FAA; font-weight: bold; color: #fff; }
			.completed { color: #526FAA; font-weight: bold; }
		</style>
		<table cellspacing="0" cellpadding="15" width="100%" style="color: gray">
			<tr><td class="<?if ($step == "welcome") { echo "highlighted"; } else if (in_array($step, array("systemcheck","database","config","setupcomplete"))) { echo "completed"; } ?>">Welcome to NiDB</td></tr>
			<tr><td class="<?if ($step == "systemcheck") { echo "highlighted"; } else if (in_array($step, array("database","config","setupcomplete"))) { echo "completed"; } ?>">System Check</td></tr>
			<tr><td class="<?if ($step == "database") { echo "highlighted"; } else if (in_array($step, array("config","setupcomplete"))) { echo "completed"; } ?>">Database</td></tr>
			<tr><td class="<?if ($step == "config") { echo "highlighted"; } else if (in_array($step, array("setupcomplete"))) { echo "completed"; } ?>">Config</td></tr>
			<tr><td class="<?if ($step == "setupcomplete") { echo "highlighted"; } ?>">Setup Complete</td></tr>
		</table>
		<?
	}


	/* -------------------------------------------- */
	/* ------- UpgradeDatabase -------------------- */
	/* -------------------------------------------- */
	function UpgradeDatabase($linki, $database, $sqlfile) {
		
		if (!file_exists($sqlfile)) {
			echo "[$sqlfile] not found<br>";
			return false;
		}
		
		if (!mysqli_select_db($linki, $database)) {
			echo "Unable to select database [$database]<br>";
			return false;
		}
		
		/* disable strict mode to prevent truncation errors */
		$sqlstring = "SET @@global.sql_mode= ''";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

		/* load the file, loop through the lines */
		$lines = file($sqlfile);
		$table = "";
		$lastcolumn = "";
		$tableexists = false;
		$indextable = "";
		$createindex = "";
		$lastline = false;
		foreach ($lines as $line) {
			
			/* ignore any blank lines, comments, or anything **after** COMMIT; */
			$line = trim($line);
			if ((substr($line,0,2) == "--") || ($line == "") || ($lastline)) {
				continue;
			}
			
			/* check if it's the last line */
			if ($line == "COMMIT;") {
				$lastline = true;
			}
			
			/* create table section */
			if (substr($line,0,12) == "CREATE TABLE") {
				$table = str_replace("`", "", preg_split('/\s+/', $line)[2]);
				echo "<br>Table <span class='e'>$table</span><br>";
				
				/* check if this table exists */
				$sqlstring = "show tables like '$table'";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					$tableexists = true;
				}
				else {
					$tableexists = false;
					/* start putting together the create table statement */
					$createtable = $line;
				}
				$createindex = "";
			}
			
			/* table doesn't exist, so build the create table statement */
			if (($createtable != "") && ($createtable != $line)) {
				$createtable .= "$line\n";

				$createindex = "";
			}
			
			/* end of a create table */
			if (substr($line,0,9) == ") ENGINE=") {
				echo "</ul>";
				//echo "Done examining [$table]<br>";
				
				if ($createtable != "") {
					echo "Table <tt class='e'>$table</tt> did not exist, creating";
					echo "<tt><pre>$createtable</pre></tt>";
					
					/* create the table */
					$sqlstring = $createtable;
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
				
				$table = "";
				$previouscol = "";
				$tableexists = false;
				$createtable = "";
				$createindex = "";
			}
			
			/* regular column to be added/updated for the current table */
			if (($table != "") && ($createtable == "") && (substr($line,0,1) == "`")) {
				$parts = preg_split('/`/', $line);
				$column = trim($parts[1]);
				$properties = trim($parts[2]);
				$properties = rtrim($properties,",");

				/* change the column if it already exists */
				$sqlstring = "alter ignore table `$table` change column if exists `$column` `$column` $properties";
				if ($previouscol != "") {
					$sqlstring .= " after `$previouscol`";
				}
				/* if there is an issue with this column, it will be an error, so no need to check warnings */
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				
				/* add the column if it does not exist */
				$sqlstring = "alter table `$table` add column if not exists `$column` $properties";
				if ($previouscol != "") {
					$sqlstring .= " after `$previouscol`";
				}
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

				echo "<tt style='font-size: smaller;'><span class='good'></span> $column</tt><br>";
				
				$previouscol = $column;
				$createindex = "";
			}
			
			/* create index section */
			if (substr($line,0,11) == "ALTER TABLE") {
				
				/* if we're here, we may be finishing an index and starting a new one, so create the previous index if there is one */
				if ($createindex != "") {
					
					$createindex = str_replace("ALTER TABLE", "ALTER IGNORE TABLE", $createindex);
					$createindex = str_replace("ADD UNIQUE KEY", "ADD UNIQUE KEY IF NOT EXISTS", $createindex);
					$createindex = str_replace("ADD PRIMARY KEY", "ADD PRIMARY KEY IF NOT EXISTS", $createindex);
					$createindex = str_replace("ADD KEY", "ADD KEY IF NOT EXISTS", $createindex);
					
					/* run the create index */
					echo "<tt span style='font-size: smaller;'><pre>$createindex</pre></tt>";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
				
				$indextable = str_replace("`", "", preg_split('/\s+/', $line)[2]);
				echo "<br>Alter Table for <span class='e'>$indextable</span><br>";
				$createindex = "$line\n";
			}
			else {
				/* build the create index statement */
				if (($createindex != "") && (!$lastline)) {
					$createindex .= "$line\n";
				}
			}
			
			/* this is the end of the file. If there are any tables to create, create them now */
			if (($lastline) && ($createtable != "")) {
				echo "Table [$table] did not exist, creating";
				echo "<tt span style='font-size: smaller;'><pre>$createtable</pre></tt>";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
			
			/* this is the end of the file. If there are any indexes/autoincrements to create, create them now */
			if (($lastline) && ($createindex != "")) {
				$createindex = str_replace("ALTER TABLE", "ALTER IGNORE TABLE", $createindex);
				$createindex = str_replace("ADD UNIQUE KEY", "ADD UNIQUE KEY IF NOT EXISTS", $createindex);
				$createindex = str_replace("ADD PRIMARY KEY", "ADD PRIMARY KEY IF NOT EXISTS", $createindex);
				$createindex = str_replace("ADD KEY", "ADD KEY IF NOT EXISTS", $createindex);
				
				echo "<tt span style='font-size: smaller;'><pre>$createindex</pre></tt>";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
		}
		
	}


	/* -------------------------------------------- */
	/* ------- DisplayConfig ---------------------- */
	/* -------------------------------------------- */
	function DisplayConfig() {

		/* load the actual .cfg file */
		$GLOBALS['cfg'] = LoadConfig(true);
	
		if (!is_null($GLOBALS['cfg'])) {
			$dbconnect = true;
			$devdbconnect = true;
			$L = mysqli_connect($GLOBALS['cfg']['mysqlhost'],$GLOBALS['cfg']['mysqluser'],$GLOBALS['cfg']['mysqlpassword'],$GLOBALS['cfg']['mysqldatabase']) or $dbconnect = false;
			$Ldev = mysqli_connect($GLOBALS['cfg']['mysqldevhost'],$GLOBALS['cfg']['mysqldevuser'],$GLOBALS['cfg']['mysqldevpassword'],$GLOBALS['cfg']['mysqldevdatabase']) or $devdbconnect = false;
		}
		
		if (!is_null($GLOBALS['cfg']) && file_exists($GLOBALS['cfg']['cfgpath'])) {
			?><div align="center">Reading from config file <code style="background-color: #ddd; padding:5px; border-radius: 4px">&nbsp;<?=$GLOBALS['cfg']['cfgpath']?>&nbsp;</code></div><?
		}
		else {
			?><div align="center">Config file not found. Will be creating a new file <code style="background-color: #ddd; padding:5px; border-radius: 4px">&nbsp;/nidb/nidb.cfg&nbsp;</code></div><?
			$GLOBALS['cfg']['mysqlhost'] = "localhost";
			$GLOBALS['cfg']['mysqluser'] = "nidb";
			$GLOBALS['cfg']['mysqldatabase'] = "nidb";
			
			$GLOBALS['cfg']['modulefileiothreads'] = 2;
			$GLOBALS['cfg']['moduleexportthreads'] = 2;
			$GLOBALS['cfg']['moduleimportthreads'] = 1;
			$GLOBALS['cfg']['modulemriqathreads'] = 4;
			$GLOBALS['cfg']['modulepipelinethreads'] = 4;
			$GLOBALS['cfg']['moduleimportuploadedthreads'] = 1;
			$GLOBALS['cfg']['moduleqcthreads'] = 2;
			
			$GLOBALS['cfg']['emailserver'] = "tls://smtp.gmail.com";
			$GLOBALS['cfg']['emailport'] = 587;
			
			$GLOBALS['cfg']['uploadsizelimit'] = 1000;
			$GLOBALS['cfg']['displayrecentstudydays'] = 5;
			$GLOBALS['cfg']['siteurl'] = $_SERVER['SERVER_NAME'];
			$GLOBALS['cfg']['sitename'] = gethostname();
			$GLOBALS['cfg']['sitetype'] = 'local';
			
			$GLOBALS['cfg']['analysisdir'] = "/nidb/data/pipeline";
			$GLOBALS['cfg']['analysisdirb'] = "/nidb/data/pipelineb";
			$GLOBALS['cfg']['clusteranalysisdir'] = "/nidb/data/pipeline";
			$GLOBALS['cfg']['clusteranalysisdirb'] = "/nidb/data/pipelineb";
			$GLOBALS['cfg']['groupanalysisdir'] = "/nidb/data/pipelinegroup";
			$GLOBALS['cfg']['archivedir'] = "/nidb/data/archive";
			$GLOBALS['cfg']['backupdir'] = "/nidb/data/backup";
			$GLOBALS['cfg']['ftpdir'] = "/nidb/data/ftp";
			$GLOBALS['cfg']['importdir'] = "/nidb/data/import";
			$GLOBALS['cfg']['incomingdir'] = "/nidb/data/dicomincoming";
			$GLOBALS['cfg']['lockdir'] = "/nidb/programs/lock";
			$GLOBALS['cfg']['logdir'] = "/nidb/programs/logs";
			$GLOBALS['cfg']['mountdir'] = "/mount";
			$GLOBALS['cfg']['qcmoduledir'] = "/nidb/programs/qcmodules";
			$GLOBALS['cfg']['problemdir'] = "/nidb/data/problem";
			$GLOBALS['cfg']['scriptdir'] = "/nidb/programs";
			$GLOBALS['cfg']['webdir'] = "/var/www/html";
			$GLOBALS['cfg']['webdownloaddir'] = "/var/www/html/download";
			$GLOBALS['cfg']['downloaddir'] = "/nidb/data/download";
			$GLOBALS['cfg']['uploadeddir'] = "/nidb/data/upload";
			$GLOBALS['cfg']['tmpdir'] = "/nidb/data/tmp";
			$GLOBALS['cfg']['deleteddir'] = "/nidb/data/deleted";
		}
		?>
		<br><br>
		<form name="configform" method="post" action="system.php">
		<input type="hidden" name="action" value="updateconfig">
		<table class="entrytable">
			<thead>
				<tr>
					<th>Variable</th>
					<th>Value</th>
					<th>Description</th>
				</tr>
			</thead>
			<tr>
				<td colspan="4" class="heading"><br>Debug</td>
			</tr>
			<tr>
				<td class="variable">debug</td>
				<td><input type="checkbox" name="debug" value="1" <? if ($GLOBALS['cfg']['debug']) { echo "checked"; } ?>></td>
				<td>Enable debugging for the PHP pages. Will display all SQL statements.</td>
			</tr>
			<tr>
				<td class="variable">hideerrors</td>
				<td><input type="checkbox" name="hideerrors" value="1" <? if ($GLOBALS['cfg']['hideerrors']) { echo "checked"; } ?>></td>
				<td>Hide a SQL error if it occurs. Emails are always sent. Always leave checked on production systems for security purposes!</td>
			</tr>
			
			<tr>
				<td colspan="4" class="heading"><br>Database</td>
			</tr>
			<tr>
				<td class="variable">mysqlhost</td>
				<td><input type="text" name="mysqlhost" required value="<?=$GLOBALS['cfg']['mysqlhost']?>"size="30"></td>
				<!-- <td><? if ($dbconnect) { ?><span class="good"></span><? } else { ?><span class="bad"></span><? } ?></td> -->
				<td>Database hostname (should be localhost or 127.0.0.1 unless the database is running on a different server than the website)</td>
			</tr>
			<tr>
				<td class="variable">mysqluser</td>
				<td><input type="text" name="mysqluser" required value="<?=$GLOBALS['cfg']['mysqluser']?>"size="30"></td>
				<!-- <td><? if ($dbconnect) { ?><span class="good"></span><? } else { ?><span class="bad"></span><? } ?></td> -->
				<td>Database username</td>
			</tr>
			<tr>
				<td class="variable">mysqlpassword</td>
				<td><input type="password" name="mysqlpassword" required value="<?=$GLOBALS['cfg']['mysqlpassword']?>"size="30"></td>
				<!-- <td><? if ($dbconnect) { ?><span class="good"></span><? } else { ?><span class="bad"></span><? } ?></td> -->
				<td>Database password</td>
			</tr>
			<tr>
				<td class="variable">mysqldatabase</td>
				<td><input type="text" name="mysqldatabase" required value="<?=$GLOBALS['cfg']['mysqldatabase']?>" size="30"></td>
				<!-- <td><? if ($dbconnect) { ?><span class="good"></span><? } else { ?><span class="bad"></span><? } ?></td> -->
				<td>Database (default is <tt>nidb</tt>)</td>
			</tr>
			<tr>
				<td class="variable">mysqlclusteruser</td>
				<td><input type="text" name="mysqlclusteruser" value="<?=$GLOBALS['cfg']['mysqlclusteruser']?>" size="30"></td>
				<!-- <td><? if ($dbconnect) { ?><span class="good"></span><? } else { ?><span class="bad"></span><? } ?></td> -->
				<td>Cluster database username -  this user has insert-only permissions for certain pipeline tables</td>
			</tr>
			<tr>
				<td class="variable">mysqlclusterpassword</td>
				<td><input type="password" name="mysqlclusterpassword" value="<?=$GLOBALS['cfg']['mysqlclusterpassword']?>" size="30"></td>
				<!-- <td><? if ($dbconnect) { ?><span class="good"></span><? } else { ?><span class="bad"></span><? } ?></td> -->
				<td>Cluster database password</td>
			</tr>

			<tr>
				<td colspan="2" class="heading"><br>Modules</td>
				<td valign="bottom"><br><br>Maximum number of threads allowed. Some modules cannot be multi-threaded</td>
			</tr>
			<tr>
				<td class="variable">modulefileiothreads</td>
				<td><input type="number" name="modulefileiothreads" value="<?=$GLOBALS['cfg']['modulefileiothreads']?>"></td>
				<td><b>fileio</b> module. Recommended is 2</td>
			</tr>
			<tr>
				<td class="variable">moduleexportthreads</td>
				<td><input type="number" name="moduleexportthreads" value="<?=$GLOBALS['cfg']['moduleexportthreads']?>"></td>
				<td><b>export</b> module. Recommended is 2</td>
			</tr>
			<tr>
				<td class="variable">moduleimportthreads</td>
				<td><input type="number" name="moduleimportthreads" value="1" disabled></td>
				<td><b>import</b> module. Not multi-threaded.</td>
			</tr>
			<tr>
				<td class="variable">modulemriqathreads</td>
				<td><input type="number" name="modulemriqathreads" value="<?=$GLOBALS['cfg']['modulemriqathreads']?>"></td>
				<td><b>mriqa</b> module. Recommended is 4</td>
			</tr>
			<tr>
				<td class="variable">modulepipelinethreads</td>
				<td><input type="number" name="modulepipelinethreads" value="<?=$GLOBALS['cfg']['modulepipelinethreads']?>"></td>
				<td><b>pipeline</b> module. Recommended is 4</td>
			</tr>
			<tr>
				<td class="variable">moduleimportuploadedthreads</td>
				<td><input type="number" name="moduleimportuploadedthreads" value="1" disabled></td>
				<td><b>importuploaded</b> module. Not multi-threaded.</td>
			</tr>
			<tr>
				<td class="variable">moduleqcthreads</td>
				<td><input type="number" name="moduleqcthreads" value="<?=$GLOBALS['cfg']['moduleqcthreads']?>"></td>
				<td><b>qc</b> module. Recommended is 2</td>
			</tr>

			<tr>
				<td class="heading"><br>Email</td>
				<td colspan="2" valign="bottom"</td>
			</tr>
			<tr>
				<td class="variable">emailusername</td>
				<td><input type="text" name="emailusername" required value="<?=$GLOBALS['cfg']['emailusername']?>" size="30"></td>
				<td>Username to login to the gmail account. Used for sending emails only</td>
			</tr>
			<tr>
				<td class="variable">emailpassword</td>
				<td><input type="password" name="emailpassword" required value="<?=$GLOBALS['cfg']['emailpassword']?>" size="30"></td>
				<td>email account password</td>
			</tr>
			<tr>
				<td class="variable">emailserver</td>
				<td><input type="text" name="emailserver" required value="<?=$GLOBALS['cfg']['emailserver']?>" size="30"></td>
				<td>Email server for sending email. For gmail, this should be <tt>tls://smtp.gmail.com</tt></td>
			</tr>
			<tr>
				<td class="variable">emailport</td>
				<td><input type="number" name="emailport" required value="<?=$GLOBALS['cfg']['emailport']?>" size="30"></td>
				<td>Email server port. For gmail, it should be <tt>587</tt></td>
			</tr>
			<tr>
				<td class="variable">emailfrom</td>
				<td><input type="email" name="emailfrom" required value="<?=$GLOBALS['cfg']['emailfrom']?>"size="30"></td>
				<td>Email return address</td>
			</tr>
			<tr>
				<td colspan="4" class="heading"><br>Site options</td>
			</tr>
			<tr>
				<td class="variable">adminemail</td>
				<td><input type="text" name="adminemail" required value="<?=$GLOBALS['cfg']['adminemail']?>"size="30"></td>
				<td>Administrator's email. Displayed for error messages and other system activities</td>
			</tr>
			<tr>
				<td class="variable">siteurl</td>
				<td><input type="text" name="siteurl" required value="<?=$GLOBALS['cfg']['siteurl']?>"size="30"></td>
				<td>Full URL of the NiDB website</td>
			</tr>
			<tr>
				<td class="variable">version</td>
				<td><input type="text" name="version" disabled value="<?=$GLOBALS['cfg']['version']?>"size="30"></td>
				<td>NiDB version. No need to change this</td>
			</tr>
			<tr>
				<td class="variable">sitename</td>
				<td><input type="text" name="sitename" required value="<?=$GLOBALS['cfg']['sitename']?>"size="30"></td>
				<td>Displayed on NiDB main page and some email notifications</td>
			</tr>
			<tr>
				<td class="variable">sitecolor</td>
				<td><input type="color" name="sitecolor" value="<?=$GLOBALS['cfg']['sitecolor']?>"size="30"></td>
				<td>Hex code for color in the upper left of the menu</td>
			</tr>
			<tr>
				<td class="variable">ispublic</td>
				<td><input type="checkbox" name="ispublic" value="1" <? if (!is_null($GLOBALS['cfg']['ispublic']) && $GLOBALS['cfg']['ispublic']) { echo "checked"; } ?>></td>
				<td>Selected if this installation is on a public server and only has port 80 open</td>
			</tr>
			<tr>
				<td class="variable">sitetype</td>
				<td><input type="text" name="sitetype" value="<?=$GLOBALS['cfg']['sitetype']?>"size="30"></td>
				<td>Options are local, public, or commercial</td>
			</tr>
			<tr>
				<td class="variable">allowphi</td>
				<td><input type="checkbox" name="allowphi" value="1" <? if (!is_null($GLOBALS['cfg']['allowphi']) && $GLOBALS['cfg']['allowphi']) { echo "checked"; } ?>></td>
				<td>Checked to allow PHI (name, DOB) on server. Unchecked to remove all PHI by default (replace name with 'Anonymous' and DOB with only year)</td>
			</tr>
			<tr>
				<td class="variable">enableremoteconn</td>
				<td><input type="checkbox" name="enableremoteconn" value="1" <? if (!is_null($GLOBALS['cfg']['enableremoteconn']) && $GLOBALS['cfg']['enableremoteconn']) { echo "checked"; } ?>></td>
				<td>Allow this server to send data to remote NiDB servers</td>
			</tr>
			<tr>
				<td class="variable">enablecalendar</td>
				<td><input type="checkbox" name="enablecalendar" value="1" <? if (!is_null($GLOBALS['cfg']['enablecalendar']) && $GLOBALS['cfg']['enablecalendar']) { echo "checked"; } ?>></td>
				<td>Enable the calendar</td>
			</tr>
			<tr>
				<td class="variable">uploadsizelimit</td>
				<td><input type="text" name="uploadsizelimit" value="<?=$GLOBALS['cfg']['uploadsizelimit']?>"size="30"></td>
				<td>Upload size limit in megabytes (MB). Current PHP upload filesize limit [upload_max_filesize] is <?=get_cfg_var('upload_max_filesize')?> and max POST size [post_max_size] is <?=get_cfg_var('post_max_size')?></td>
			</tr>
			<tr>
				<td class="variable">displayrecentstudies</td>
				<td><input type="checkbox" name="displayrecentstudies" value="1" <? if (!is_null($GLOBALS['cfg']['displayrecentstudies']) && $GLOBALS['cfg']['displayrecentstudies']) { echo "checked"; } ?>></td>
				<td>Display recently collected studies on the Home page</td>
			</tr>
			<tr>
				<td class="variable">displayrecentstudydays</td>
				<td><input type="text" name="displayrecentstudydays" value="<?=$GLOBALS['cfg']['displayrecentstudydays']?>"size="30"></td>
				<td>Number of days to display of recently collected studies on the Home page</td>
			</tr>

			<tr>
				<td colspan="4" class="heading"><br>Data Import/Export</td>
			</tr>
			<tr>
				<td class="variable">importchunksize</td>
				<td><input type="number" name="importchunksize" value="<?=$GLOBALS['cfg']['importchunksize']?>"size="30"></td>
				<td>Number of files checked by the import module before archiving begins. Default is 5000</td>
			</tr>
			<tr>
				<td class="variable">numretry</td>
				<td><input type="number" name="numretry" value="<?=$GLOBALS['cfg']['numretry']?>"size="30"></td>
				<td>Number of times to retry a failed network operation. Default is 5</td>
			</tr>
			<tr>
				<td class="variable">enablenfs</td>
				<td><input type="checkbox" name="enablenfs" value="1" <? if (!is_null($GLOBALS['cfg']['enablenfs']) && $GLOBALS['cfg']['enablenfs']) { echo "checked"; } ?>></td>
				<td>Display the NFS export options. Allow NiDB to write to NFS mount points</td>
			</tr>
			<tr>
				<td class="variable">enableftp</td>
				<td><input type="checkbox" name="enablenfs" value="1" <? if (!is_null($GLOBALS['cfg']['enablenfs']) && $GLOBALS['cfg']['enablenfs']) { echo "checked"; } ?>></td>
				<td>Display the FTP export options. Uncheck if this site does not have FTP, SCP, or other file transfer services enabled</td>
			</tr>
			<tr>
				<td class="variable">allowrawdicomexport</td>
				<td><input type="checkbox" name="allowrawdicomexport"  value="1" <? if (!is_null($GLOBALS['cfg']['allowrawdicomexport']) && $GLOBALS['cfg']['allowrawdicomexport']) { echo "checked"; } ?>></td>
				<td>Allow DICOM files to be downloaded from this server without being anonymized first. Unchecking this option removes the Download and 3D viewier icons on the study page</td>
			</tr>

			<tr>
				<td colspan="4" class="heading"><br>Quality Control</td>
			</tr>
			<tr>
				<td class="variable">fslbinpath</td>
				<td><input type="text" name="fslbinpath" required value="<?=$GLOBALS['cfg']['fslbinpath']?>"size="30"></td>
				<td>Path to FSL binaries. Example /opt/fsl/bin</td>
			</tr>

			<tr>
				<td colspan="4" class="heading"><br>Cluster</td>
			</tr>
			<tr>
				<td class="variable">usecluster</td>
				<td><input type="checkbox" name="usecluster" value="1" <? if (!is_null($GLOBALS['cfg']['usecluster']) && $GLOBALS['cfg']['usecluster']) { echo "checked"; } ?>></td>
				<td>Use a cluster to perform QC</td>
			</tr>
			<tr>
				<td class="variable">queuename</td>
				<td><input type="text" name="queuename" value="<?=$GLOBALS['cfg']['queuename']?>"size="30"></td>
				<td>Cluster queue name</td>
			</tr>
			<tr>
				<td class="variable">queueuser</td>
				<td><input type="text" name="queueuser" value="<?=$GLOBALS['cfg']['queueuser']?>"size="30"></td>
				<td>Linux username under which the QC cluster jobs are submitted</td>
			</tr>
			<tr>
				<td class="variable">clustersubmithost</td>
				<td><input type="text" name="clustersubmithost" value="<?=$GLOBALS['cfg']['clustersubmithost']?>"size="30"></td>
				<td>Hostname which QC jobs are submitted</td>
			</tr>
			<tr>
				<td class="variable">qsubpath</td>
				<td><input type="text" name="qsubpath" value="<?=$GLOBALS['cfg']['qsubpath']?>"size="30"></td>
				<td>Path to the qsub program. Use a full path to the executable, or just qsub if its already in the PATH environment variable</td>
			</tr>
			<tr>
				<td class="variable">clusteruser</td>
				<td><input type="text" name="clusteruser" value="<?=$GLOBALS['cfg']['clusteruser']?>"size="30"></td>
				<td>Username under which jobs will be submitted to the cluster for the pipeline system</td>
			</tr>
			<tr>
				<td class="variable">clusternidbpath</td>
				<td><input type="text" name="clusternidbpath" value="<?=$GLOBALS['cfg']['clusternidbpath']?>"size="30"></td>
				<td>Path to the directory comtaining the <i>nidb</i> executable (relative to the cluster itself) on the cluster</td>
			</tr>

			<tr>
				<td colspan="4" class="heading"><br>CAS Authentication</td>
			</tr>
			<tr>
				<td class="variable">enablecas</td>
				<td><input type="checkbox" name="enablecas" value="1" <? if (!is_null($GLOBALS['cfg']['enablecas']) && $GLOBALS['cfg']['enablecas']) { echo "checked"; } ?>></td>
				<td>Uses CAS authentication instead of locally stored usernames</td>
			</tr>
			<tr>
				<td class="variable">casserver</td>
				<td><input type="text" name="casserver" value="<?=$GLOBALS['cfg']['casserver']?>"size="30"></td>
				<td>CAS server</td>
			</tr>
			<tr>
				<td class="variable">casport</td>
				<td><input type="number" name="casport" value="<?=$GLOBALS['cfg']['casport']?>"size="30"></td>
				<td>CAS port, usually 443</td>
			</tr>
			<tr>
				<td class="variable">cascontext</td>
				<td><input type="text" name="cascontext" value="<?=$GLOBALS['cfg']['cascontext']?>"size="30"></td>
				<td>CAS context</td>
			</tr>
			
			<tr>
				<td colspan="4" class="heading"><br>FTP</td>
			</tr>
			<tr>
				<td class="variable">localftphostname</td>
				<td><input type="text" name="localftphostname" value="<?=$GLOBALS['cfg']['localftphostname']?>"size="30"></td>
				<td>If you allow data to be sent to the local FTP and have configured the FTP site, this will be the information displayed to users on how to access the FTP site.</td>
			</tr>
			<tr>
				<td class="variable">localftpusername</td>
				<td><input type="text" name="localftpusername" value="<?=$GLOBALS['cfg']['localftpusername']?>"size="30"></td>
				<td>Username for the locall access FTP account</td>
			</tr>
			<tr>
				<td class="variable">localftppassword</td>
				<td><input type="text" name="localftppassword" value="<?=$GLOBALS['cfg']['localftppassword']?>"size="30"></td>
				<td>Password for local access FTP account. This is displayed to the users in clear text.</td>
			</tr>

			<tr>
				<td colspan="4" class="heading"><br>Directories</td>
			</tr>
			<tr>
				<td class="variable"><b>scriptdir</b></td>
				<td><input type="text" name="scriptdir" required value="<?=$GLOBALS['cfg']['scriptdir']?>"size="30"></td>
				<!--<td><? if (!is_null($GLOBALS['cfg']['scriptdir']) && file_exists($GLOBALS['cfg']['scriptdir'])) { ?><span class="good"></span><? } else { ?><span class="bad"></span><? } ?></td>-->
				<td><b>Directory for programs and settings file (Backend)</b></td>
			</tr>
			<tr>
				<td class="variable"><b>webdir</b></td>
				<td><input type="text" name="webdir" required value="<?=$GLOBALS['cfg']['webdir']?>"size="30"></td>
				<!--<td><? if (!is_null($GLOBALS['cfg']['webdir']) && file_exists($GLOBALS['cfg']['webdir'])) { ?><span class="good"></span><? } else { ?><span class="bad"></span><? } ?></td>-->
				<td><b>Root of the website directory (Frontend)</b></td>
			</tr>
			<tr>
				<td class="variable">analysisdir</td>
				<td><input type="text" name="analysisdir" required value="<?=$GLOBALS['cfg']['analysisdir']?>"size="30"></td>
				<!--<td><? if (!is_null($GLOBALS['cfg']['analysisdir']) && file_exists($GLOBALS['cfg']['analysisdir'])) { ?><span class="good"></span><? } else { ?><span class="bad"></span><? } ?></td>-->
				<td>Pipeline analysis directory (full path, including any /mount prefixes specified in [mountdir]) for data stored in the <tt>/S1234ABC/<b>PipelineName</b>/1</tt> format</td>
			</tr>
			<tr>
				<td class="variable">analysisdirb</td>
				<td><input type="text" name="analysisdirb" required value="<?=$GLOBALS['cfg']['analysisdirb']?>"size="30"></td>
				<!--<td><? if (!is_null($GLOBALS['cfg']['analysisdirb']) && file_exists($GLOBALS['cfg']['analysisdirb'])) { ?><span class="good"></span><? } else { ?><span class="bad"></span><? } ?></td>-->
				<td>Pipeline analysis directory (full path, including any /mount prefixes specified in [mountdir]) for data stored in the <tt>/<b>PipelineName</b>/S1234ABC/1</tt> format</td>
			</tr>
			<tr>
				<td class="variable">clusteranalysisdir</td>
				<td><input type="text" name="clusteranalysisdir" required value="<?=$GLOBALS['cfg']['clusteranalysisdir']?>"size="30"></td>
				<!--<td><? if (!is_null($GLOBALS['cfg']['clusteranalysisdir']) && file_exists($GLOBALS['cfg']['clusteranalysisdir'])) { ?><span class="good"></span><? } else { ?><span class="bad"></span><? } ?></td>-->
				<td>Pipeline analysis directory as seen from the cluster (full path, including any /mount prefixes specified in [mountdir]) for data stored in the <tt>/S1234ABC/<b>PipelineName</b>/1</tt> format</td>
			</tr>
			<tr>
				<td class="variable">clusteranalysisdirb</td>
				<td><input type="text" name="clusteranalysisdirb" required value="<?=$GLOBALS['cfg']['clusteranalysisdirb']?>"size="30"></td>
				<!-- <td><? if (!is_null($GLOBALS['cfg']['clusteranalysisdirb']) && file_exists($GLOBALS['cfg']['clusteranalysisdirb'])) { ?><span class="good"></span><? } else { ?><span class="bad"></span><? } ?></td> -->
				<td>Pipeline analysis directory as seen from the cluster (full path, including any /mount prefixes specified in [mountdir]) for data stored in the <tt>/<b>PipelineName</b>/S1234ABC/1</tt> format</td>
			</tr>
			<tr>
				<td class="variable">groupanalysisdir</td>
				<td><input type="text" name="groupanalysisdir" required value="<?=$GLOBALS['cfg']['groupanalysisdir']?>"size="30"></td>
				<!-- <td><? if (!is_null($GLOBALS['cfg']['groupanalysisdir']) && file_exists($GLOBALS['cfg']['groupanalysisdir'])) { ?><span class="good"></span><? } else { ?><span class="bad"></span><? } ?></td> -->
				<td>Pipeline directory for group analyses (full path, including any /mount prefixes specified in [mountdir])</td>
			</tr>
			<tr>
				<td class="variable">archivedir</td>
				<td><input type="text" name="archivedir" required value="<?=$GLOBALS['cfg']['archivedir']?>"size="30"></td>
				<!-- <td><? if (!is_null($GLOBALS['cfg']['archivedir']) && file_exists($GLOBALS['cfg']['archivedir'])) { ?><span class="good"></span><? } else { ?><span class="bad"></span><? } ?></td> -->
				<td>Directory for archived data. All binary data is stored in this directory.</td>
			</tr>
			<tr>
				<td class="variable">backupdir</td>
				<td><input type="text" name="backupdir" required value="<?=$GLOBALS['cfg']['backupdir']?>"size="30"></td>
				<!-- <td><? if (!is_null($GLOBALS['cfg']['backupdir']) && file_exists($GLOBALS['cfg']['backupdir'])) { ?><span class="good"></span><? } else { ?><span class="bad"></span><? } ?></td> -->
				<td>All data is copied to this directory at the same time it is added to the archive directory. This can be useful if you want to use a tape backup and only copy out newer files from this directory to fill up a tape.</td>
			</tr>
			<tr>
				<td class="variable">ftpdir</td>
				<td><input type="text" name="ftpdir" required value="<?=$GLOBALS['cfg']['ftpdir']?>"size="30"></td>
				<!-- <td><? if (!is_null($GLOBALS['cfg']['ftpdir']) && file_exists($GLOBALS['cfg']['ftpdir'])) { ?><span class="good"></span><? } else { ?><span class="bad"></span><? } ?></td> -->
				<td>Downloaded data to be retreived by FTP is stored here</td>
			</tr>
			<tr>
				<td class="variable">incomingdir</td>
				<td><input type="text" name="incomingdir" required value="<?=$GLOBALS['cfg']['incomingdir']?>"size="30"></td>
				<!-- <td><? if (!is_null($GLOBALS['cfg']['incomingdir']) && file_exists($GLOBALS['cfg']['incomingdir'])) { ?><span class="good"></span><? } else { ?><span class="bad"></span><? } ?></td> -->
				<td>All data received from the DICOM receiver is placed in the root of this directory. All non-DICOM data is stored in numbered sub-directories of this directory.</td>
			</tr>
			<tr>
				<td class="variable">lockdir</td>
				<td><input type="text" name="lockdir" required value="<?=$GLOBALS['cfg']['lockdir']?>"size="30"></td>
				<!-- <td><? if (!is_null($GLOBALS['cfg']['lockdir']) && file_exists($GLOBALS['cfg']['lockdir'])) { ?><span class="good"></span><? } else { ?><span class="bad"></span><? } ?></td> -->
				<td>Lock directory for the programs</td>
			</tr>
			<tr>
				<td class="variable">logdir</td>
				<td><input type="text" name="logdir" required value="<?=$GLOBALS['cfg']['logdir']?>"size="30"></td>
				<!-- <td><? if (!is_null($GLOBALS['cfg']['logdir']) && file_exists($GLOBALS['cfg']['logdir'])) { ?><span class="good"></span><? } else { ?><span class="bad"></span><? } ?></td> -->
				<td>Log directory for the programs</td>
			</tr>
			<tr>
				<td class="variable">mountdir</td>
				<td><input type="text" name="mountdir" required value="<?=$GLOBALS['cfg']['mountdir']?>"size="30"></td>
				<!-- <td><? if (!is_null($GLOBALS['cfg']['mountdir']) && file_exists($GLOBALS['cfg']['mountdir'])) { ?><span class="good"></span><? } else { ?><span class="bad"></span><? } ?></td> -->
				<td>Directory in which user data directories are mounted and any directories which should be accessible from the NFS mount export option of the Search page. For example, if the user enters [/home/user1/data/testing] the mountdir will be prepended to point to the real mount point of [/mount/home/user1/data/testing]. This prevents users from writing data to the OS directories.</td>
			</tr>
			<tr>
				<td class="variable">qcmoduledir</td>
				<td><input type="text" name="qcmoduledir" required value="<?=$GLOBALS['cfg']['qcmoduledir']?>"size="30"></td>
				<!-- <td><? if (!is_null($GLOBALS['cfg']['qcmoduledir']) && file_exists($GLOBALS['cfg']['qcmoduledir'])) { ?><span class="good"></span><? } else { ?><span class="bad"></span><? } ?></td> -->
				<td>Directory containing QC modules. Usually a subdirectory of the programs directory</td>
			</tr>
			<tr>
				<td class="variable">problemdir</td>
				<td><input type="text" name="problemdir" required value="<?=$GLOBALS['cfg']['problemdir']?>"size="30"></td>
				<!-- <td><? if (!is_null($GLOBALS['cfg']['problemdir']) && file_exists($GLOBALS['cfg']['problemdir'])) { ?><span class="good"></span><? } else { ?><span class="bad"></span><? } ?></td> -->
				<td>Files which encounter problems during import/archiving are placed here</td>
			</tr>
			<tr>
				<td class="variable">webdownloaddir</td>
				<td><input type="text" name="webdownloaddir" required value="<?=$GLOBALS['cfg']['webdownloaddir']?>"size="30"></td>
				<!-- <td><? if (!is_null($GLOBALS['cfg']['webdownloaddir']) && file_exists($GLOBALS['cfg']['webdownloaddir'])) { ?><span class="good"></span><? } else { ?><span class="bad"></span><? } ?></td> -->
				<td>Directory within the webdir that will link to the physical download directory. Sometimes the downloads can be HUGE, and the default /var/www/html directory may be on a small partition. This directory should point to the real [downloaddir] on a filesystem with enough space to store the large downloads.</td>
			</tr>
			<tr>
				<td class="variable">downloaddir</td>
				<td><input type="text" name="downloaddir" required value="<?=$GLOBALS['cfg']['downloaddir']?>"size="30"></td>
				<!-- <td><? if (!is_null($GLOBALS['cfg']['downloaddir']) && file_exists($GLOBALS['cfg']['downloaddir'])) { ?><span class="good"></span><? } else { ?><span class="bad"></span><? } ?></td> -->
				<td>Directory which stores downloads available from the website</td>
			</tr>
			<tr>
				<td class="variable">uploadeddir</td>
				<td><input type="text" name="uploadeddir" required value="<?=$GLOBALS['cfg']['uploadeddir']?>"size="30"></td>
				<!-- <td><? if (!is_null($GLOBALS['cfg']['uploadeddir']) && file_exists($GLOBALS['cfg']['uploadeddir'])) { ?><span class="good"></span><? } else { ?><span class="bad"></span><? } ?></td> -->
				<td>Data received from the api.php and import pages is placed here</td>
			</tr>
			<tr>
				<td class="variable">tmpdir</td>
				<td><input type="text" name="tmpdir" required value="<?=$GLOBALS['cfg']['tmpdir']?>"size="30"></td>
				<!-- <td><? if (!is_null($GLOBALS['cfg']['tmpdir']) && file_exists($GLOBALS['cfg']['tmpdir'])) { ?><span class="good"></span><? } else { ?><span class="bad"></span><? } ?></td> -->
				<td>Directory used for temporary operations. Depending upon data sizes requested or processed, this directory may get very large, and may need to be outside of the OS drive.</td>
			</tr>
			<tr>
				<td class="variable">deleteddir</td>
				<td><input type="text" name="deleteddir" required value="<?=$GLOBALS['cfg']['deleteddir']?>"size="30"></td>
				<!-- <td><? if (!is_null($GLOBALS['cfg']['deleteddir']) && file_exists($GLOBALS['cfg']['deleteddir'])) { ?><span class="good"></span><? } else { ?><span class="bad"></span><? } ?></td> -->
				<td>Data is not usually deleted. It may be removed from the database and not appear on the website, but the data will end up in this directory.</td>
			</tr>
			
			
												<script>
													function CheckNFSPath() {
														var xhttp = new XMLHttpRequest();
														xhttp.onreadystatechange = function() {
															if (this.readyState == 4 && this.status == 200) {
																document.getElementById("pathcheckresult").innerHTML = this.responseText;
															}
														};
														var nfsdir = document.getElementById("nfsdir").value;
														//alert(nfsdir);
														xhttp.open("GET", "ajaxapi.php?action=validatepath&nfspath=" + nfsdir, true);
														xhttp.send();
													}
												</script>
												<!--<input type="radio" name="destination" id="destination" value="nfs" checked>Linux NFS Mount <input type="text" id="nfsdir" name="nfsdir" size="50" onKeyUp="CheckNFSPath()"> <span id="pathcheckresult"></span>-->
			
			
		</table>
		</form>
		<?
	}
?>
