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
	ob_start(); /* buffer output so POST/Redirect/GET (a header('Location') redirect) works despite the HTML rendered below */
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
	$setup = false;
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
	/* every action changes state: POST + CSRF token required (GetSafeAction falls back to the read-only view otherwise) */
	$action = GetSafeAction(array("updateconfig", "testemail", "setsystemmessage", "deletesystemmessage"));
    $c['debug'] = GetVariable("debug");
    $c['hideerrors'] = GetVariable("hideerrors");
	
    $c['mysqlhost'] = GetVariable("mysqlhost");
    $c['mysqluser'] = GetVariable("mysqluser");
    $c['mysqlpassword'] = GetVariable("mysqlpassword");
    $c['mysqldatabase'] = GetVariable("mysqldatabase");
    $c['mysqlclusteruser'] = GetVariable("mysqlclusteruser");
    $c['mysqlclusterpassword'] = GetVariable("mysqlclusterpassword");

    $c['moduleauditthreads'] = GetVariable("moduleauditthreads");
    $c['modulebackupthreads'] = GetVariable("modulebackupthreads");
    $c['moduleexportnonimagingthreads'] = GetVariable("moduleexportnonimagingthreads");
    $c['moduleexportthreads'] = GetVariable("moduleexportthreads");
    $c['modulefileiothreads'] = GetVariable("modulefileiothreads");
    $c['moduleimportthreads'] = GetVariable("moduleimportthreads");
    $c['moduleimportuploadedthreads'] = GetVariable("moduleimportuploadedthreads");
    $c['modulemaintenancethreads'] = GetVariable("modulemaintenancethreads");
    $c['moduleminipipelinethreads'] = GetVariable("moduleminipipelinethreads");
    $c['modulemriqathreads'] = GetVariable("modulemriqathreads");
    $c['modulepipelinethreads'] = GetVariable("modulepipelinethreads");
    $c['moduleqcthreads'] = GetVariable("moduleqcthreads");
    $c['moduleuploadthreads'] = GetVariable("moduleuploadthreads");
	
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
    $c['allowsignup'] = GetVariable("allowsignup");

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
    $c['archivedir1'] = GetVariable("archivedir1");
    $c['archivedir2'] = GetVariable("archivedir2");
    $c['archivedir3'] = GetVariable("archivedir3");
    $c['archivedir4'] = GetVariable("archivedir4");
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
    $c['qcmoduledir'] = GetVariable("qcmoduledir");
    $c['tmpdir'] = GetVariable("tmpdir");
    $c['uploaddir'] = GetVariable("uploaddir");
    $c['uploadeddir'] = GetVariable("uploadeddir");
    $c['uploadstagingdir'] = GetVariable("uploadstagingdir");
    $c['webdir'] = GetVariable("webdir");

	$systemmessage = GetVariable("systemmessage");
	$messageid = GetVariable("messageid");
	
	/* determine action */
	switch ($action) {
		/* mutating actions use POST/Redirect/GET: run the handler, stash its message,
		   then redirect to a GET so a refresh/Back doesn't re-run it */
		case 'updateconfig':
			ob_start();
			WriteConfig($c);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("settings.php");
			break;
		case 'testemail':
			ob_start();
			TestEmail();
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("settings.php");
			break;
		case 'setsystemmessage':
			ob_start();
			SetSystemMessage($systemmessage);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("settings.php#sysmsg");
			break;
		case 'deletesystemmessage':
			ob_start();
			DeleteSystemMessage($messageid);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("settings.php#sysmsg");
			break;
		default:
			ShowFlashMessage();
			DisplaySettings("settings", array('sysmsg' => array('label' => 'System Status Message', 'render' => 'DisplaySystemMessages')));
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	/* -------------------------------------------- */
	/* ------- SetSystemMessage ------------------- */
	/* -------------------------------------------- */
	function SetSystemMessage($msg) {
		$msg = trim($msg ?? '');
		if ($msg == "") { Error("System message cannot be blank"); return; }

		/* message is stored as-is (menu.php renders it as HTML); bound, so no pre-escaping */
		$sqlstring = "insert into system_messages (message, message_date, message_status) values (?, now(), 'active')";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 's', $msg);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$msg]);
		mysqli_stmt_close($stmt);

		Notice("System message set");
	}


	/* -------------------------------------------- */
	/* ------- DeleteSystemMessage ---------------- */
	/* -------------------------------------------- */
	function DeleteSystemMessage($msgid) {
		if (!isInteger($msgid)) { Error("Invalid message ID [" . htmlspecialchars($msgid ?? '') . "]"); return; }
		$msgid = (int)$msgid;

		$sqlstring = "update system_messages set message_status = 'deleted' where message_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $msgid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$msgid]);
		mysqli_stmt_close($stmt);

		Notice("System message deleted");
	}


	/* -------------------------------------------- */
	/* ------- TestEmail -------------------------- */
	/* -------------------------------------------- */
	function TestEmail() {
		$to = $GLOBALS['cfg']['adminemail'] ?? '';
		//$from = $GLOBALS['cfg']['adminemail'] ?? '';
		$subject = "Testing email send from " . ($GLOBALS['cfg']['sitename'] ?? '') . " (" . ($GLOBALS['cfg']['siteurl'] ?? '') . ")";
		$body = "If you receive this message, your NiDB email is working";
		
		if (trim($to ?? '') == "") { Error("[adminemail] is not set. Save an admin email address first."); return; }

		/* send the email */
		if (!SendEmail($to,$subject,$body, 1, 0)) {
			Error("System error. Unable to send email!");
			return;
		}
		Notice("Test email sent to " . htmlspecialchars($to));
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplaySystemMessages -------------- */
	/* -------------------------------------------- */
	/* rendered as the 'System Status Message' tab inside DisplaySettings() */
	function DisplaySystemMessages() {

		?>
					Current messages:
					<?
						$sqlstring = "select * from system_messages where message_status = 'active'";
						$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						if (mysqli_num_rows($result) > 0) {
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$messageid = $row['message_id'];
								$messagedate = $row['message_date'];
								$message = $row['message'];
								/* the message is rendered as HTML by design (site admins write it, menu.php shows it the same way) */
								?><br><?=htmlspecialchars($messagedate ?? '')?> - <b><?=$message?></b>
								<form method="post" action="settings.php" style="display: inline">
									<?=CSRFTokenField()?>
									<input type="hidden" name="action" value="deletesystemmessage">
									<input type="hidden" name="messageid" value="<?=(int)$messageid?>">
									<button type="submit" class="ui red button" onclick="return confirm('Are you sure you want to delete the message?')">Delete</button>
								</form><br><?
							}
						}
						else {
							echo " None";
						}
					?>
				<br><br>
				<form method="post" action="settings.php">
				<?=CSRFTokenField()?>
				<input type="hidden" name="action" value="setsystemmessage">
				<textarea name="systemmessage" style="width: 500px; height: 70px"></textarea><br>
				<input type="submit" value="Set message" class="ui primary button">
				</form>
		<?
	}
?>

<? include("footer.php") ?>