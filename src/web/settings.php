<?
 // ------------------------------------------------------------------------------
 // NiDB settings.php
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

	/* check if they have permissions to this view page */
	if (!isSiteAdmin() && !$setup) {
		Warning("You do not have permissions to view this page");
		exit(0);
	}
	
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

    $c['modulebackupthreads'] = GetVariable("modulebackupthreads");
    $c['moduleexportnonimagingthreads'] = GetVariable("moduleexportnonimagingthreads");
    $c['moduleexportthreads'] = GetVariable("moduleexportthreads");
    $c['modulefileiothreads'] = GetVariable("modulefileiothreads");
    $c['moduleimportthreads'] = GetVariable("moduleimportthreads");
    $c['moduleimportuploadedthreads'] = GetVariable("moduleimportuploadedthreads");
    $c['moduleminipipelinethreads'] = GetVariable("moduleminipipelinethreads");
    $c['modulemriqathreads'] = GetVariable("modulemriqathreads");
    $c['modulepipelinethreads'] = GetVariable("modulepipelinethreads");
    $c['moduleqcthreads'] = GetVariable("moduleqcthreads");
    $c['moduleuploadthreads'] = GetVariable("moduleuploadthreads");
	
    $c['emaillib'] = GetVariable("emaillib");
    $c['emailusername'] = GetVariable("emailusername");
    $c['emailpassword'] = GetVariable("emailpassword");
    $c['emailserver'] = GetVariable("emailserver");
    $c['emailport'] = GetVariable("emailport");
    $c['emailfrom'] = GetVariable("emailfrom");
    $c['adminemail'] = GetVariable("adminemail");
    $c['emailonerror'] = GetVariable("emailonerror");
	
    $c['siteurl'] = GetVariable("siteurl");
    
    //$c['fslbinpath'] = GetVariable("fslbinpath");
    $c['fsldir'] = GetVariable("fsldir");
	
	$c['usecluster'] = GetVariable("usecluster");
    $c['queuename'] = GetVariable("queuename");
    $c['queueuser'] = GetVariable("queueuser");
    $c['clustersubmithost'] = GetVariable("clustersubmithost");
    $c['clustersubmituser'] = GetVariable("clustersubmituser");
    $c['qsubpath'] = GetVariable("qsubpath");
    $c['clusteruser'] = GetVariable("clusteruser");
    $c['clusternidbpath'] = GetVariable("clusternidbpath");
    $c['qcpath'] = GetVariable("qcpath");
    $c['clusterqcpath'] = GetVariable("clusterqcpath");

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

    $c['backupsize'] = GetVariable("backupsize");
    $c['backupstagingdir'] = GetVariable("backupstagingdir");
    $c['backupdevice'] = GetVariable("backupdevice");
    $c['backupserver'] = GetVariable("backupserver");

    $c['enablecsa'] = GetVariable("enablecsa");
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
    $c['archivedir'] = GetVariable("archivedir");
    $c['backupdir'] = GetVariable("backupdir");
    $c['deleteddir'] = GetVariable("deleteddir");
    $c['downloaddir'] = GetVariable("downloaddir");
    $c['exportdir'] = GetVariable("exportdir");
    $c['groupanalysisdir'] = GetVariable("groupanalysisdir");
    $c['importdir'] = GetVariable("importdir");
    $c['incoming2dir'] = GetVariable("incoming2dir");
    $c['incomingdir'] = GetVariable("incomingdir");
    $c['lockdir'] = GetVariable("lockdir");
    $c['logdir'] = GetVariable("logdir");
    $c['mountdir'] = GetVariable("mountdir");
    $c['nidbdir'] = GetVariable("nidbdir");
    $c['packageimportdir'] = GetVariable("packageimportdir");
    $c['problemdir'] = GetVariable("problemdir");
    $c['publicdownloaddir'] = GetVariable("publicdownloaddir");
    $c['publicwebdir'] = GetVariable("publicwebdir");
    $c['qcmoduledir'] = GetVariable("qcmoduledir");
    $c['tmpdir'] = GetVariable("tmpdir");
    $c['uploaddir'] = GetVariable("uploaddir");
    $c['uploadeddir'] = GetVariable("uploadeddir");
    $c['uploadstagingdir'] = GetVariable("uploadstagingdir");
    $c['webdir'] = GetVariable("webdir");
    $c['webdownloaddir'] = GetVariable("webdownloaddir");

	$systemmessage = GetVariable("systemmessage");
	$messageid = GetVariable("messageid");
	
	/* determine action */
	switch ($action) {
		case 'updateconfig':
			WriteConfig($c);
			DisplaySettings("settings");
			DisplayConfig();
			break;
		case 'testemail':
			TestEmail();
			DisplaySettings("settings");
			DisplayConfig();
			break;
		case 'setsystemmessage':
			SetSystemMessage($systemmessage);
			DisplaySettings("settings");
			DisplayConfig();
			break;
		case 'deletesystemmessage':
			DeleteSystemMessage($messageid);
			DisplaySettings("settings");
			DisplayConfig();
			break;
		default:
			DisplaySettings("settings");
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
		//$from = $GLOBALS['cfg']['adminemail'];
		$subject = "Testing email send from " . $GLOBALS['cfg']['sitename'] . " (" . $GLOBALS['cfg']['siteurl'] . ")";
		$body = "If you receive this message, your NiDB email is working";
		
		/* send the email */
		if (!SendEmail($to,$subject,$body, 1, 0)) {
			return "System error. Unable to send email!";
		}
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayConfig ---------------------- */
	/* -------------------------------------------- */
	function DisplayConfig() {

		?>
		
		<div class="ui container">
			<div class="ui fluid styled accordion">
				<div class="title">
					<i class="dropdown icon"></i> System Status Message
				</div>
				<div class="content">
					Current messages:
					<?
						$sqlstring = "select * from system_messages where message_status = 'active'";
						$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						if (mysqli_num_rows($result) > 0) {
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$messageid = $row['message_id'];
								$messagedate = $row['message_date'];
								$message = $row['message'];
								?><br><?=$messagedate?> - <b><?=$message?></b> <a class="ui red button" href="settings.php?action=deletesystemmessage&messageid=<?=$messageid?>" onclick="return confirm('Are you sure you want to delete the message?')">Delete</a><br><?
							}
						}
						else {
							echo " None";
						}
					?>
				<br><br>
				<form method="post" action="settings.php">
				<input type="hidden" name="action" value="setsystemmessage">
				<textarea name="systemmessage" style="width: 500px; height: 70px"></textarea><br>
				<input type="submit" value="Set message" class="ui primary button">
				</form>
			</div>
		</div>
		<?
	}
?>

<? include("footer.php") ?>