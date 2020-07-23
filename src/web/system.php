<?
 // ------------------------------------------------------------------------------
 // NiDB system.php
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
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Settings</title>
	</head>

<body>
	<div id="wrapper">
<?
	/* check if the .cfg file exists */
	if ( (!file_exists('nidb.cfg')) && (!file_exists('../nidb.cfg')) && (!file_exists('../programs/nidb.cfg')) && (!file_exists('/home/nidb/programs/nidb.cfg')) && (!file_exists('/nidb/programs/nidb.cfg')) && (!file_exists('/nidb/nidb.cfg')) && (!file_exists('/nidb/bin/nidb.cfg')) ) {
		$setup = true;
		$nologin = true;
	}
	
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	if (!$setup) {
		require "menu.php";
	}

	/* kick them out if they are not a site admin, unless  */
	if (!$GLOBALS['issiteadmin'] && !$setup) {
		?><div width="100%">You are not a site admin, so cannot view this page</div><?
		exit(0);
	}
	//PrintVariable($_POST);
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
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
	
    $c['emaillib'] = GetVariable("emaillib");
    $c['emailusername'] = GetVariable("emailusername");
    $c['emailpassword'] = GetVariable("emailpassword");
    $c['emailserver'] = GetVariable("emailserver");
    $c['emailport'] = GetVariable("emailport");
    $c['emailfrom'] = GetVariable("emailfrom");
    $c['adminemail'] = GetVariable("adminemail");
	
    $c['siteurl'] = GetVariable("siteurl");
    
    //$c['fslbinpath'] = GetVariable("fslbinpath");
    $c['fsldir'] = GetVariable("fsldir");
	
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
    $c['redcapurl'] = GetVariable("redcapurl");
    $c['redcaptoken'] = GetVariable("redcaptoken");
	
    $c['enableremoteconn'] = GetVariable("enableremoteconn");
    $c['enablecalendar'] = GetVariable("enablecalendar");
    $c['uploadsizelimit'] = GetVariable("uploadsizelimit");
    $c['displayrecentstudies'] = GetVariable("displayrecentstudies");
    $c['displayrecentstudydays'] = GetVariable("displayrecentstudydays");

    $c['setupips'] = GetVariable("setupips");

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

	/* directories */
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
    $c['nidbdir'] = GetVariable("nidbdir");
    $c['webdir'] = GetVariable("webdir");
    $c['webdownloaddir'] = GetVariable("webdownloaddir");
    $c['downloaddir'] = GetVariable("downloaddir");
    $c['uploadeddir'] = GetVariable("uploadeddir");
    $c['tmpdir'] = GetVariable("tmpdir");
    $c['deleteddir'] = GetVariable("deleteddir");

	$systemmessage = GetVariable("systemmessage");
	$messageid = GetVariable("messageid");
	
	/* determine action */
	switch ($action) {
		case 'updateconfig':
			WriteConfig($c);
			DisplayConfig();
			break;
		case 'testemail':
			TestEmail();
			DisplayConfig();
			break;
		case 'setsystemmessage':
			SetSystemMessage($systemmessage);
			DisplayConfig();
			break;
		case 'deletesystemmessage':
			DeleteSystemMessage($messageid);
			DisplayConfig();
			break;
		default:
			DisplayConfig();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	/* -------------------------------------------- */
	/* ------- SetSystemMessage ------------------- */
	/* -------------------------------------------- */
	function SetSystemMessage($msg) {
		$msg = mysqli_real_escape_string($GLOBALS['linki'], $msg);
		
		$sqlstring = "insert into system_messages (message, message_date, message_status) values ('$msg', now(), 'active')";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}


	/* -------------------------------------------- */
	/* ------- DeleteSystemMessage ---------------- */
	/* -------------------------------------------- */
	function DeleteSystemMessage($msgid) {
		if (!isInteger($msgid)) { echo "Invalid message ID [$msgid]"; return; }
		
		$sqlstring = "update system_messages set message_status = 'deleted' where message_id = $msgid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}


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
# emaillib options (case-sensitive): Net-SMTP-TLS (default), Email-Send-SMTP-Gmail
[emaillib] = $emaillib
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
[enableremoteconn] = $enableremoteconn
[enablecalendar] = $enablecalendar
[uploadsizelimit] = $uploadsizelimit
[displayrecentstudies] = $displayrecentstudies
[displayrecentstudydays] = $displayrecentstudydays

# ----- security options -----
[setupips] = $setupips

# ----- import/export options -----
[importchunksize] = $importchunksize
[numretry] = $numretry
[enablenfs] = $enablenfs
[enableftp] = $enableftp
[allowrawdicomexport] = $allowrawdicomexport
[redcapurl] = $redcapurl
[redcaptoken] = $redcaptoken

# ----- qc -----
#[fslbinpath] = $fslbinpath
[fsldir] = $fsldir

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
[nidbdir] = $nidbdir
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
			?>Config file has been written to <code><?=$GLOBALS['cfg']['cfgpath']?></code><br><?
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
			?>Cluster config file has been written to <code><?=$clustercfgfile?></code><br><br><?
		}
		
	
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayConfig ---------------------- */
	/* -------------------------------------------- */
	function DisplayConfig() {

		/* load the actual .cfg file */
		$GLOBALS['cfg'] = LoadConfig();
	
		$dbconnect = true;
		$devdbconnect = true;
		$L = mysqli_connect($GLOBALS['cfg']['mysqlhost'],$GLOBALS['cfg']['mysqluser'],$GLOBALS['cfg']['mysqlpassword'],$GLOBALS['cfg']['mysqldatabase']) or $dbconnect = false;
		$Ldev = mysqli_connect($GLOBALS['cfg']['mysqldevhost'],$GLOBALS['cfg']['mysqldevuser'],$GLOBALS['cfg']['mysqldevpassword'],$GLOBALS['cfg']['mysqldevdatabase']) or $devdbconnect = false;
		
		?>
		
		<style>
			legend { font-weight: bold; padding: 8px; background-color: #444; color: #fff; border: 2px solid #000; font-size: 14pt; border-radius: 12px; }
			fieldset { background-color: #eee; border: 2px solid #444; border-radius: 10px; }
		</style>

		<fieldset>
			<legend>System status messages</legend>
			
			Current messages:
		<?
			$sqlstring = "select * from system_messages where message_status = 'active'";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			if (mysqli_num_rows($result) > 0) {
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$messageid = $row['message_id'];
					$messagedate = $row['message_date'];
					$message = $row['message'];
					?><br><?=$messagedate?> - <b><?=$message?></b> <a href="system.php?action=deletesystemmessage&messageid=<?=$messageid?>" class="adminbutton">Delete</a><br><?
				}
			}
			else {
				echo " None";
			}
		?>
			<br><br>
			<form method="post" action="system.php">
			<input type="hidden" name="action" value="setsystemmessage">
			<textarea name="systemmessage" style="width: 500px; height: 70px"></textarea><br>
			<input type="submit" value="Set message">
			</form>
		</fieldset>
		
		<br><br>
		
		<fieldset>
		<legend>NiDB Settings</legend>
		<div align="center">Reading from config file <code style="background-color: #ddd; padding:5px; border-radius: 4px">&nbsp;<?=$GLOBALS['cfg']['cfgpath']?>&nbsp;</code></div>
		<br><br>
		<form name="configform" method="post" action="system.php">
		<input type="hidden" name="action" value="updateconfig">
		<table class="entrytable">
			<thead>
				<tr>
					<th>Variable</th>
					<th>Value</th>
					<th>Valid?</th>
					<th>Description</th>
				</tr>
			</thead>
			<tr>
				<td colspan="4" class="heading"><br>Debug</td>
			</tr>
			<tr>
				<td class="variable">debug</td>
				<td><input type="checkbox" name="debug" value="1" <? if ($GLOBALS['cfg']['debug']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Enable debugging for the PHP pages. Will display all SQL statements.</td>
			</tr>
			<tr>
				<td class="variable">hideerrors</td>
				<td><input type="checkbox" name="hideerrors" value="1" <? if ($GLOBALS['cfg']['hideerrors']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Hide a SQL error if it occurs. Emails are always sent. Always leave checked on production systems for security purposes!</td>
			</tr>
			
			<tr>
				<td colspan="4" class="heading"><br>Database</td>
			</tr>
			<tr>
				<td class="variable">mysqlhost</td>
				<td><input type="text" name="mysqlhost" value="<?=$GLOBALS['cfg']['mysqlhost']?>" size="45"></td>
				<td><? if ($dbconnect) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Database hostname (should be <code>localhost</code> or <code>127.0.0.1</code> unless the database is running on a different server than the website)</td>
			</tr>
			<tr>
				<td class="variable">mysqluser</td>
				<td><input type="text" name="mysqluser" value="<?=$GLOBALS['cfg']['mysqluser']?>" size="45"></td>
				<td><? if ($dbconnect) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Database username</td>
			</tr>
			<tr>
				<td class="variable">mysqlpassword</td>
				<td><input type="password" name="mysqlpassword" value="<?=$GLOBALS['cfg']['mysqlpassword']?>" size="45"></td>
				<td><? if ($dbconnect) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Database password</td>
			</tr>
			<tr>
				<td class="variable">mysqldatabase</td>
				<td><input type="text" name="mysqldatabase" value="<?=$GLOBALS['cfg']['mysqldatabase']?>" size="45"></td>
				<td><? if ($dbconnect) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Database (default is <tt>nidb</tt>)</td>
			</tr>
			<tr>
				<td class="variable">mysqldevhost</td>
				<td><input type="text" name="mysqldevhost" value="<?=$GLOBALS['cfg']['mysqldevhost']?>"></td>
				<td><? if ($devdbconnect) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Development database hostname. This database will only be used if the website is accessed from port 8080 instead of 80. Example <code>http://localhost:8080</code></td>
			</tr>
			<tr>
				<td class="variable">mysqldevuser</td>
				<td><input type="text" name="mysqldevuser" value="<?=$GLOBALS['cfg']['mysqldevuser']?>"></td>
				<td><? if ($devdbconnect) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Development database username</td>
			</tr>
			<tr>
				<td class="variable">mysqldevpassword</td>
				<td><input type="password" name="mysqldevpassword" value="<?=$GLOBALS['cfg']['mysqldevpassword']?>"></td>
				<td><? if ($devdbconnect) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Development database password</td>
			</tr>
			<tr>
				<td class="variable">mysqldevdatabase</td>
				<td><input type="text" name="mysqldevdatabase" value="<?=$GLOBALS['cfg']['mysqldevdatabase']?>"></td>
				<td><? if ($devdbconnect) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Development database (default is <tt>nidb</tt>)</td>
			</tr>
			<tr>
				<td class="variable">mysqlclusteruser</td>
				<td><input type="text" name="mysqlclusteruser" value="<?=$GLOBALS['cfg']['mysqlclusteruser']?>"></td>
				<td><? if ($dbconnect) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Cluster database username -  this user has insert-only permissions for certain pipeline tables</td>
			</tr>
			<tr>
				<td class="variable">mysqlclusterpassword</td>
				<td><input type="password" name="mysqlclusterpassword" value="<?=$GLOBALS['cfg']['mysqlclusterpassword']?>"></td>
				<td><? if ($dbconnect) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Cluster database password</td>
			</tr>

			<tr>
				<td colspan="3" class="heading"><br>Modules</td>
				<td valign="bottom"><br><br>Maximum number of threads allowed. Some modules cannot be multi-threaded</td>
			</tr>
			<tr>
				<td class="variable">modulefileiothreads</td>
				<td><input type="number" name="modulefileiothreads" value="<?=$GLOBALS['cfg']['modulefileiothreads']?>"></td>
				<td></td>
				<td><b>fileio</b> module. Recommended is 2</td>
			</tr>
			<tr>
				<td class="variable">moduleexportthreads</td>
				<td><input type="number" name="moduleexportthreads" value="<?=$GLOBALS['cfg']['moduleexportthreads']?>"></td>
				<td></td>
				<td><b>export</b> module. Recommended is 2</td>
			</tr>
			<tr>
				<td class="variable">moduleimportthreads</td>
				<td><input type="number" name="moduleimportthreads" value="1" disabled></td>
				<td></td>
				<td><b>import</b> module. Not multi-threaded.</td>
			</tr>
			<tr>
				<td class="variable">modulemriqathreads</td>
				<td><input type="number" name="modulemriqathreads" value="<?=$GLOBALS['cfg']['modulemriqathreads']?>"></td>
				<td></td>
				<td><b>mriqa</b> module. Recommended is 4</td>
			</tr>
			<tr>
				<td class="variable">modulepipelinethreads</td>
				<td><input type="number" name="modulepipelinethreads" value="<?=$GLOBALS['cfg']['modulepipelinethreads']?>"></td>
				<td></td>
				<td><b>pipeline</b> module. Recommended is 4</td>
			</tr>
			<tr>
				<td class="variable">moduleimportuploadedthreads</td>
				<td><input type="number" name="moduleimportuploadedthreads" value="1" disabled></td>
				<td></td>
				<td><b>importuploaded</b> module. Not multi-threaded.</td>
			</tr>
			<tr>
				<td class="variable">moduleqcthreads</td>
				<td><input type="number" name="moduleqcthreads" value="<?=$GLOBALS['cfg']['moduleqcthreads']?>"></td>
				<td></td>
				<td><b>qc</b> module. Recommended is 2</td>
			</tr>

			<tr>
				<td class="heading"><br>Email</td>
				<td colspan="3" valign="bottom" title="Send a test email to the admin email account"><br><br><a href="system.php?action=testemail" style="background-color:yellow; padding: 2px 5px; border: 1px solid orange"> Send test email </a></td>
			</tr>
			<tr>
				<td class="variable">emaillib</td>
				<td><input type="text" name="emaillib" value="<?=$GLOBALS['cfg']['emaillib']?>" size="45"></td>
				<td></td>
				<td>Net-SMTP-TLS or Email-Send-SMTP-Gmail</td>
			</tr>
			<tr>
				<td class="variable">emailusername</td>
				<td><input type="text" name="emailusername" value="<?=$GLOBALS['cfg']['emailusername']?>" size="45"></td>
				<td></td>
				<td>Username to login to the gmail account. Used for sending emails only</td>
			</tr>
			<tr>
				<td class="variable">emailpassword</td>
				<td><input type="password" name="emailpassword" value="<?=$GLOBALS['cfg']['emailpassword']?>" size="45"></td>
				<td></td>
				<td>email account password</td>
			</tr>
			<tr>
				<td class="variable">emailserver</td>
				<td><input type="text" name="emailserver" value="<?=$GLOBALS['cfg']['emailserver']?>" size="45"></td>
				<td></td>
				<td>Email server for sending email. For gmail, it should be <code>tls://smtp.gmail.com</code></td>
			</tr>
			<tr>
				<td class="variable">emailport</td>
				<td><input type="number" name="emailport" value="<?=$GLOBALS['cfg']['emailport']?>" size="45"></td>
				<td></td>
				<td>Email server port. For gmail, it should be <tt>587</tt></td>
			</tr>
			<tr>
				<td class="variable">emailfrom</td>
				<td><input type="email" name="emailfrom" value="<?=$GLOBALS['cfg']['emailfrom']?>" size="45"></td>
				<td></td>
				<td>Email return address</td>
			</tr>
			<tr>
				<td colspan="4" class="heading"><br>Site options</td>
			</tr>
			<tr>
				<td class="variable">adminemail</td>
				<td><input type="text" name="adminemail" value="<?=$GLOBALS['cfg']['adminemail']?>" size="45"></td>
				<td></td>
				<td>Administrator's email. Displayed for error messages and other system activities</td>
			</tr>
			<tr>
				<td class="variable">siteurl</td>
				<td><input type="text" name="siteurl" value="<?=$GLOBALS['cfg']['siteurl']?>" size="45"></td>
				<td></td>
				<td>Full URL of the NiDB website</td>
			</tr>
			<tr>
				<td class="variable">version</td>
				<td><input type="text" name="version" value="<?=GetNiDBVersion()?>" size="45" readonly></td>
				<td></td>
				<td>NiDB version. Automatically populated</td>
			</tr>
			<tr>
				<td class="variable">sitename</td>
				<td><input type="text" name="sitename" value="<?=$GLOBALS['cfg']['sitename']?>" size="45"></td>
				<td></td>
				<td>Displayed on NiDB main page and some email notifications</td>
			</tr>
			<tr>
				<td class="variable">sitenamedev</td>
				<td><input type="text" name="sitenamedev" value="<?=$GLOBALS['cfg']['sitenamedev']?>" size="45"></td>
				<td></td>
				<td>Development site name</td>
			</tr>
			<tr>
				<td class="variable">sitecolor</td>
				<td><input type="color" name="sitecolor" value="<?=$GLOBALS['cfg']['sitecolor']?>" size="45"></td>
				<td></td>
				<td>Hex code for color in the upper left of the menu</td>
			</tr>
			<tr>
				<td class="variable">ispublic</td>
				<td><input type="checkbox" name="ispublic" value="1" <? if ($GLOBALS['cfg']['ispublic']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Selected if this installation is on a public server and only has port 80 open</td>
			</tr>
			<tr>
				<td class="variable">sitetype</td>
				<td><input type="text" name="sitetype" value="<?=$GLOBALS['cfg']['sitetype']?>" size="45"></td>
				<td></td>
				<td>Options are local, public, or commercial</td>
			</tr>
			<tr>
				<td class="variable">allowphi</td>
				<td><input type="checkbox" name="allowphi" value="1" <? if ($GLOBALS['cfg']['allowphi']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Checked to allow PHI (name, DOB) on server. Unchecked to remove all PHI by default (replace name with 'Anonymous' and DOB with only year)</td>
			</tr>
			<tr>
				<td class="variable">enableremoteconn</td>
				<td><input type="checkbox" name="enableremoteconn" value="1" <? if ($GLOBALS['cfg']['enableremoteconn']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Allow this server to send data to remote NiDB servers</td>
			</tr>
			<tr>
				<td class="variable">enablecalendar</td>
				<td><input type="checkbox" name="enablecalendar" value="1" <? if ($GLOBALS['cfg']['enablecalendar']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Enable the calendar</td>
			</tr>
			<tr>
				<td class="variable">uploadsizelimit</td>
				<td><input type="text" name="uploadsizelimit" value="<?=$GLOBALS['cfg']['uploadsizelimit']?>" size="45"></td>
				<td></td>
				<td>Upload size limit in megabytes (MB). Current PHP upload filesize limit [upload_max_filesize] is <?=get_cfg_var('upload_max_filesize')?> and max POST size [post_max_size] is <?=get_cfg_var('post_max_size')?></td>
			</tr>
			<tr>
				<td class="variable">displayrecentstudies</td>
				<td><input type="checkbox" name="displayrecentstudies" value="1" <? if ($GLOBALS['cfg']['displayrecentstudies']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Display recently collected studies on the Home page</td>
			</tr>
			<tr>
				<td class="variable">displayrecentstudydays</td>
				<td><input type="text" name="displayrecentstudydays" value="<?=$GLOBALS['cfg']['displayrecentstudydays']?>" size="45"></td>
				<td></td>
				<td>Number of days to display of recently collected studies on the Home page</td>
			</tr>

			<tr>
				<td colspan="4" class="heading"><br>Security</td>
			</tr>
			<tr>
				<td class="variable">setupips</td>
				<td><input type="text" name="setupips" value="<?=$GLOBALS['cfg']['setupips']?>" size="45"></td>
				<td></td>
				<td>Comma separated list of IP addresses from which the setup and update functionality can be accessed. Example <code>127.0.0.1, 10.24.1.1</code> Your current IP address is <code><?=$_SERVER['REMOTE_ADDR']?></code></td>
			</tr>

			<tr>
				<td colspan="4" class="heading"><br>Data Import/Export</td>
			</tr>
			<tr>
				<td class="variable">importchunksize</td>
				<td><input type="number" name="importchunksize" value="<?=$GLOBALS['cfg']['importchunksize']?>" size="45"></td>
				<td></td>
				<td>Number of files checked by the import module before archiving begins. Default is 5000</td>
			</tr>
			<tr>
				<td class="variable">numretry</td>
				<td><input type="number" name="numretry" value="<?=$GLOBALS['cfg']['numretry']?>" size="45"></td>
				<td></td>
				<td>Number of times to retry a failed network operation. Default is 5</td>
			</tr>
			<tr>
				<td class="variable">enablenfs</td>
				<td><input type="checkbox" name="enablenfs" value="1" <? if ($GLOBALS['cfg']['enablenfs']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Display the NFS export options. Allow NiDB to write to NFS mount points</td>
			</tr>
			<tr>
				<td class="variable">enableftp</td>
				<td><input type="checkbox" name="enablenfs" value="1" <? if ($GLOBALS['cfg']['enablenfs']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Display the FTP export options. Uncheck if this site does not have FTP, SCP, or other file transfer services enabled</td>
			</tr>
			<tr>
				<td class="variable">allowrawdicomexport</td>
				<td><input type="checkbox" name="allowrawdicomexport"  value="1" <? if ($GLOBALS['cfg']['allowrawdicomexport']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Allow DICOM files to be downloaded from this server without being anonymized first. Unchecking this option removes the Download and 3D viewier icons on the study page</td>
			</tr>
			<tr>
				<td class="variable">redcapurl</td>
				<td><input type="text" name="redcapurl" value="<?=$GLOBALS['cfg']['redcapurl']?>" size="45"></td>
				<td></td>
				<td>URL of the RedCap Database API to pull data from RedCap into NiDB</td>
			</tr>
			<tr>
				<td class="variable">redcaptoken</td>
				<td><input type="text" name="redcaptoken" value="<?=$GLOBALS['cfg']['redcaptoken']?>" size="45"></td>
				<td></td>
				<td>Token required to access RedCap</td>
			</tr>

			<tr>
				<td colspan="4" class="heading"><br>Quality Control</td>
			</tr>
			<!--<tr>
				<td class="variable">fslbinpath</td>
				<td><input type="text" name="fslbinpath" value="<?=$GLOBALS['cfg']['fslbinpath']?>" size="45"></td>
				<td></td>
				<td>Path to FSL binaries. Example /opt/fsl/bin</td>
			</tr>-->
			<tr>
				<td class="variable">fsldir</td>
				<td><input type="text" name="fsldir" value="<?=$GLOBALS['cfg']['fsldir']?>" size="45"></td>
				<td></td>
				<td>FSL_DIR environment variable. Example /opt/fsl/bin</td>
			</tr>

			<tr>
				<td colspan="4" class="heading"><br>Cluster</td>
			</tr>
			<tr>
				<td class="variable">usecluster</td>
				<td><input type="checkbox" name="usecluster" value="1" <? if ($GLOBALS['cfg']['usecluster']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Use a cluster to perform QC</td>
			</tr>
			<tr>
				<td class="variable">queuename</td>
				<td><input type="text" name="queuename" value="<?=$GLOBALS['cfg']['queuename']?>" size="45"></td>
				<td></td>
				<td>Cluster queue name</td>
			</tr>
			<tr>
				<td class="variable">queueuser</td>
				<td><input type="text" name="queueuser" value="<?=$GLOBALS['cfg']['queueuser']?>" size="45"></td>
				<td></td>
				<td>Linux username under which the QC cluster jobs are submitted</td>
			</tr>
			<tr>
				<td class="variable">clustersubmithost</td>
				<td><input type="text" name="clustersubmithost" value="<?=$GLOBALS['cfg']['clustersubmithost']?>" size="45"></td>
				<td></td>
				<td>Hostname which QC jobs are submitted</td>
			</tr>
			<tr>
				<td class="variable">qsubpath</td>
				<td><input type="text" name="qsubpath" value="<?=$GLOBALS['cfg']['qsubpath']?>" size="45"></td>
				<td></td>
				<td>Path to the <code>qsub</code> program. Use a full path to the executable, or just <code>qsub</code> if its already in the PATH environment variable</td>
			</tr>
			<tr>
				<td class="variable">clusteruser</td>
				<td><input type="text" name="clusteruser" value="<?=$GLOBALS['cfg']['clusteruser']?>" size="45"></td>
				<td></td>
				<td>Username under which jobs will be submitted to the cluster for the pipeline system</td>
			</tr>
			<tr>
				<td class="variable">clusternidbpath</td>
				<td><input type="text" name="clusternidbpath" value="<?=$GLOBALS['cfg']['clusternidbpath']?>" size="45"></td>
				<td></td>
				<td>Path to the directory containing the <i>nidb</i> executable (relative to the cluster itself) on the cluster</td>
			</tr>

			<tr>
				<td colspan="4" class="heading"><br>CAS Authentication</td>
			</tr>
			<tr>
				<td class="variable">enablecas</td>
				<td><input type="checkbox" name="enablecas" value="1" <? if ($GLOBALS['cfg']['enablecas']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Use CAS authentication</td>
			</tr>
			<tr>
				<td class="variable">casserver</td>
				<td><input type="text" name="casserver" value="<?=$GLOBALS['cfg']['casserver']?>" size="45"></td>
				<td></td>
				<td>CAS server</td>
			</tr>
			<tr>
				<td class="variable">casport</td>
				<td><input type="number" name="casport" value="<?=$GLOBALS['cfg']['casport']?>" size="45"></td>
				<td></td>
				<td>CAS port, usually 443</td>
			</tr>
			<tr>
				<td class="variable">cascontext</td>
				<td><input type="text" name="cascontext" value="<?=$GLOBALS['cfg']['cascontext']?>" size="45"></td>
				<td></td>
				<td>CAS context</td>
			</tr>
			
			<tr>
				<td colspan="4" class="heading"><br>FTP</td>
			</tr>
			<tr>
				<td class="variable">localftphostname</td>
				<td><input type="text" name="localftphostname" value="<?=$GLOBALS['cfg']['localftphostname']?>" size="45"></td>
				<td></td>
				<td>If you allow data to be sent to the local FTP and have configured the FTP site, this will be the information displayed to users on how to access the FTP site.</td>
			</tr>
			<tr>
				<td class="variable">localftpusername</td>
				<td><input type="text" name="localftpusername" value="<?=$GLOBALS['cfg']['localftpusername']?>" size="45"></td>
				<td></td>
				<td>Username for the locall access FTP account</td>
			</tr>
			<tr>
				<td class="variable">localftppassword</td>
				<td><input type="text" name="localftppassword" value="<?=$GLOBALS['cfg']['localftppassword']?>" size="45"></td>
				<td></td>
				<td>Password for local access FTP account. This is displayed to the users in clear text.</td>
			</tr>

			<tr>
				<td colspan="4" class="heading"><br>NiDB Directories<br><span class="tiny">Leave off trailing slashes</span></td>
			</tr>
			<tr>
				<td class="variable"><b>nidbdir</b></td>
				<td><input type="text" name="nidbdir" value="<?=$GLOBALS['cfg']['nidbdir']?>" size="45"></td>
				<td><? if (file_exists($GLOBALS['cfg']['nidbdir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td><b>Main NiDB installation directory</b></td>
			</tr>
			<tr>
				<td class="variable"><b>webdir</b></td>
				<td><input type="text" name="webdir" value="<?=$GLOBALS['cfg']['webdir']?>" size="45"></td>
				<td><? if (file_exists($GLOBALS['cfg']['webdir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td><b>Root of the website directory (Frontend)</b></td>
			</tr>
			<tr>
				<td class="variable">lockdir</td>
				<td><input type="text" name="lockdir" value="<?=$GLOBALS['cfg']['lockdir']?>" size="45"></td>
				<td><? if (file_exists($GLOBALS['cfg']['lockdir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Lock directory for the programs</td>
			</tr>
			<tr>
				<td class="variable">logdir</td>
				<td><input type="text" name="logdir" value="<?=$GLOBALS['cfg']['logdir']?>" size="45"></td>
				<td><? if (file_exists($GLOBALS['cfg']['logdir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Log directory for the programs</td>
			</tr>
			<tr>
				<td class="variable">mountdir</td>
				<td><input type="text" name="mountdir" value="<?=$GLOBALS['cfg']['mountdir']?>" size="45"></td>
				<td><? if (file_exists($GLOBALS['cfg']['mountdir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Directory in which user data directories are mounted and any directories which should be accessible from the NFS mount export option of the Search page. For example, if the user enters <code>/home/user1/data/testing</code> the mountdir will be prepended to point to the real mount point of <code>/mount/home/user1/data/testing</code>. This prevents users from writing data to the OS directories.</td>
			</tr>
			<tr>
				<td class="variable">qcmoduledir</td>
				<td><input type="text" name="qcmoduledir" value="<?=$GLOBALS['cfg']['qcmoduledir']?>" size="45"></td>
				<td><? if (file_exists($GLOBALS['cfg']['qcmoduledir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Directory containing QC modules. Usually a subdirectory of the programs directory</td>
			</tr>


			<tr>
				<td colspan="4" class="heading"><br>Data Directories<br><span class="tiny">Leave off trailing slashes</span></td>
			</tr>
			<tr>
				<td class="variable">archivedir</td>
				<td><input type="text" name="archivedir" value="<?=$GLOBALS['cfg']['archivedir']?>" size="45"></td>
				<td><? if (file_exists($GLOBALS['cfg']['archivedir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Directory for archived data. All binary data is stored in this directory.</td>
			</tr>
			<tr>
				<td class="variable">backupdir</td>
				<td><input type="text" name="backupdir" value="<?=$GLOBALS['cfg']['backupdir']?>" size="45"></td>
				<td><? if (file_exists($GLOBALS['cfg']['backupdir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>All data is copied to this directory at the same time it is added to the archive directory. This can be useful if you want to use a tape backup and only copy out newer files from this directory to fill up a tape.</td>
			</tr>
			<tr>
				<td class="variable">ftpdir</td>
				<td><input type="text" name="ftpdir" value="<?=$GLOBALS['cfg']['ftpdir']?>" size="45"></td>
				<td><? if (file_exists($GLOBALS['cfg']['ftpdir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Downloaded data to be retreived by FTP is stored here</td>
			</tr>
			<tr>
				<td class="variable">importdir</td>
				<td><input type="text" name="importdir" value="<?=$GLOBALS['cfg']['importdir']?>" size="45"></td>
				<td><? if (file_exists($GLOBALS['cfg']['importdir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Old method of importing data. Unused</td>
			</tr>
			<tr>
				<td class="variable">incomingdir</td>
				<td><input type="text" name="incomingdir" value="<?=$GLOBALS['cfg']['incomingdir']?>" size="45"></td>
				<td><? if (file_exists($GLOBALS['cfg']['incomingdir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>All data received from the DICOM receiver is placed in the root of this directory. All non-DICOM data is stored in numbered sub-directories of this directory.</td>
			</tr>
			<tr>
				<td class="variable">incoming2dir</td>
				<td><input type="text" name="incoming2dir" value="<?=$GLOBALS['cfg']['incoming2dir']?>" size="45"></td>
				<td><? if (file_exists($GLOBALS['cfg']['incoming2dir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Unused</td>
			</tr>
			<tr>
				<td class="variable">packageimportdir</td>
				<td><input type="text" name="packageimportdir" value="<?=$GLOBALS['cfg']['packageimportdir']?>" size="45"></td>
				<td><? if (file_exists($GLOBALS['cfg']['packageimportdir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>If using the data package export/import feature, packages to be imported should be placed here</td>
			</tr>
			<tr>
				<td class="variable">problemdir</td>
				<td><input type="text" name="problemdir" value="<?=$GLOBALS['cfg']['problemdir']?>" size="45"></td>
				<td><? if (file_exists($GLOBALS['cfg']['problemdir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Files which encounter problems during import/archiving are placed here</td>
			</tr>
			<tr>
				<td class="variable">webdownloaddir</td>
				<td><input type="text" name="webdownloaddir" value="<?=$GLOBALS['cfg']['webdownloaddir']?>" size="45"></td>
				<td><? if (file_exists($GLOBALS['cfg']['webdownloaddir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Directory within the webdir that will link to the physical download directory. Sometimes the downloads can be HUGE, and the default <code>/var/www/html</code> directory may be on a small partition. This directory should point to the real [downloaddir] on a filesystem with enough space to store the large downloads.</td>
			</tr>
			<tr>
				<td class="variable">downloaddir</td>
				<td><input type="text" name="downloaddir" value="<?=$GLOBALS['cfg']['downloaddir']?>" size="45"></td>
				<td><? if (file_exists($GLOBALS['cfg']['downloaddir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Directory which stores downloads available from the website</td>
			</tr>
			<tr>
				<td class="variable">uploadeddir</td>
				<td><input type="text" name="uploadeddir" value="<?=$GLOBALS['cfg']['uploadeddir']?>" size="45"></td>
				<td><? if (file_exists($GLOBALS['cfg']['uploadeddir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Data received from the api.php and import pages is placed here</td>
			</tr>
			<tr>
				<td class="variable">tmpdir</td>
				<td><input type="text" name="tmpdir" value="<?=$GLOBALS['cfg']['tmpdir']?>" size="45"></td>
				<td><? if (file_exists($GLOBALS['cfg']['tmpdir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Directory used for temporary operations. Depending upon data sizes requested or processed, this directory may get very large, and may need to be outside of the OS drive.</td>
			</tr>
			<tr>
				<td class="variable">deleteddir</td>
				<td><input type="text" name="deleteddir" value="<?=$GLOBALS['cfg']['deleteddir']?>" size="45"></td>
				<td><? if (file_exists($GLOBALS['cfg']['deleteddir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Data is not usually deleted. It may be removed from the database and not appear on the website, but the data will end up in this directory.</td>
			</tr>


			<tr>
				<td colspan="4" class="heading"><br>Cluster/pipeline Directories<br><span class="tiny">Leave off trailing slashes</span></td>
			</tr>
			<tr>
				<td class="variable">analysisdir</td>
				<td><input type="text" name="analysisdir" value="<?=$GLOBALS['cfg']['analysisdir']?>" size="45"></td>
				<td><? if (file_exists($GLOBALS['cfg']['analysisdir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Pipeline analysis directory (full path, including any /mount prefixes specified in [mountdir]) for data stored in the <code>/S1234ABC/<b>PipelineName</b>/1</code> format</td>
			</tr>
			<tr>
				<td class="variable">analysisdirb</td>
				<td><input type="text" name="analysisdirb" value="<?=$GLOBALS['cfg']['analysisdirb']?>" size="45"></td>
				<td><? if (file_exists($GLOBALS['cfg']['analysisdirb'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Pipeline analysis directory (full path, including any /mount prefixes specified in [mountdir]) for data stored in the <code>/<b>PipelineName</b>/S1234ABC/1</code> format</td>
			</tr>
			<tr>
				<td class="variable">clusteranalysisdir</td>
				<td><input type="text" name="clusteranalysisdir" value="<?=$GLOBALS['cfg']['clusteranalysisdir']?>" size="45"></td>
				<td><? if (file_exists($GLOBALS['cfg']['clusteranalysisdir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Pipeline analysis directory as seen from the cluster (full path, including any /mount prefixes specified in [mountdir]) for data stored in the <code>/S1234ABC/<b>PipelineName</b>/1</code> format</td>
			</tr>
			<tr>
				<td class="variable">clusteranalysisdirb</td>
				<td><input type="text" name="clusteranalysisdirb" value="<?=$GLOBALS['cfg']['clusteranalysisdirb']?>" size="45"></td>
				<td><? if (file_exists($GLOBALS['cfg']['clusteranalysisdirb'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Pipeline analysis directory as seen from the cluster (full path, including any /mount prefixes specified in [mountdir]) for data stored in the <code>/<b>PipelineName</b>/S1234ABC/1</code> format</td>
			</tr>
			<tr>
				<td class="variable">groupanalysisdir</td>
				<td><input type="text" name="groupanalysisdir" value="<?=$GLOBALS['cfg']['groupanalysisdir']?>" size="45"></td>
				<td><? if (file_exists($GLOBALS['cfg']['groupanalysisdir'])) { ?><span style="color:green">&#x2713;</span><? } else { ?><span style="color:red">&#x2717;</span><? } ?></td>
				<td>Pipeline directory for group analyses (full path, including any /mount prefixes specified in [mountdir])</td>
			</tr>
			<tr>
				<td colspan="3">
					<input type="submit" value="Update nidb.cfg">
				</td>
			</tr>
		</table>
		</form>
		</fieldset>
		
		<br><br>
		
		<fieldset>
		<legend>PHP Variables</legend>
		<table class="twocoltable">
			<thead>
				<tr>
					<th>PHP variable</th>
					<th>Current value</th>
				</tr>
			</thead>
			<tr>
				<td>max_input_vars</td>
				<td><?=get_cfg_var('max_input_vars')?></td>
			</tr>
			<tr>
				<td>post_max_size</td>
				<td><?=get_cfg_var('post_max_size')?></td>
			</tr>
			<tr>
				<td>upload_max_filesize</td>
				<td><?=get_cfg_var('upload_max_filesize')?></td>
			</tr>
			<tr>
				<td>max_file_uploads</td>
				<td><?=get_cfg_var('max_file_uploads')?></td>
			</tr>
		</table>
		</fieldset>
		<br><br>
		
		<fieldset>
		<legend>Cron</legend>
		Crontab for <?=system("whoami"); ?><br>
		<pre><?=system("crontab -l"); ?></pre>
		</fieldset>
		<?
	}
?>

<? include("footer.php") ?>