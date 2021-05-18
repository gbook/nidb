<?
 // ------------------------------------------------------------------------------
 // NiDB system.php
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
    $c['moduleuploadthreads'] = GetVariable("moduleuploadthreads");
	
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
    $c['enablepipelines'] = GetVariable("enablepipelines");
    $c['enabledatamenu'] = GetVariable("enabledatamenu");
    $c['enablerdoc'] = GetVariable("enablerdoc");
    $c['enablepublicdownloads'] = GetVariable("enablepublicdownloads");
    $c['enablewebexport'] = GetVariable("enablewebexport");
			
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
    $c['uploaddir'] = GetVariable("uploaddir");
    $c['uploadeddir'] = GetVariable("uploadeddir");
    $c['uploadstagingdir'] = GetVariable("uploadstagingdir");
    $c['tmpdir'] = GetVariable("tmpdir");
    $c['deleteddir'] = GetVariable("deleteddir");

	$systemmessage = GetVariable("systemmessage");
	$messageid = GetVariable("messageid");
	
	/* determine action */
	switch ($action) {
		case 'updateconfig':
			WriteConfig($c);
			DisplayConfig();
			DisplaySettings("settings");
			break;
		case 'testemail':
			TestEmail();
			DisplayConfig();
			DisplaySettings("settings");
			break;
		case 'setsystemmessage':
			SetSystemMessage($systemmessage);
			DisplayConfig();
			DisplaySettings("settings");
			break;
		case 'deletesystemmessage':
			DeleteSystemMessage($messageid);
			DisplayConfig();
			DisplaySettings("settings");
			break;
		default:
			DisplayConfig();
			DisplaySettings("settings");
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
	/* ------- DisplayConfig ---------------------- */
	/* -------------------------------------------- */
	function DisplayConfig() {

		$systemstring = "curl --silent \"https://api.github.com/repos/gbook/nidb/releases/latest\" | grep '\"tag_name\":'";
		$latestnidb = shell_exec($systemstring);
		$latestnidb = str_replace("\"tag_name\": \"","", $latestnidb);
		$latestnidb = str_replace("\",","", $latestnidb);
		$latestnidb = str_replace("v","", $latestnidb);
		
		$currentnidb = GetNiDBVersion();
		if ($currentnidb != $latestnidb) {
		?>
			<div class="ui inverted orange segment">
				<h3 class="ui header">New NiDB version available</h3>
				Current NiDB version <?=GetNiDBVersion();?><br>
				Latest NiDB version <b><?=$latestnidb;?></b>
			</div>
		<? } ?>
		
		<div class="ui top attached grey inverted segment">
			<h2>System status messages</h2>
		</div>
		<div class="ui bottom attached segment">
			Current messages:
		<?
			$sqlstring = "select * from system_messages where message_status = 'active'";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			if (mysqli_num_rows($result) > 0) {
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$messageid = $row['message_id'];
					$messagedate = $row['message_date'];
					$message = $row['message'];
					?><br><?=$messagedate?> - <b><?=$message?></b> <a class="ui red button" href="system.php?action=deletesystemmessage&messageid=<?=$messageid?>" onclick="return confirm('Are you sure you want to delete the message?')">Delete</a><br><?
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
			<input type="submit" value="Set message" class="ui primary button">
			</form>
		</div>
		
		<br><br>
	<?
	}
?>

<? include("footer.php") ?>