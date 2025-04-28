<?
 // ------------------------------------------------------------------------------
 // NiDB setup.php
 // Copyright (C) 2004 - 2022
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

	/* allow the page to run for 2 minutes. necessary for the schema update */
	set_time_limit(120);
	
	define("LEGIT_REQUEST", true);
	session_start();
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Setup</title>
	</head>
	
	<style>
		.e { font-weight: bold; color: #444; }
	</style>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_html.php";

	/* check if the .cfg file exists, and what type of installation this is: setup/upgrade */
	$cfgexists = false;
	$installtype = "install";
	if ( (file_exists('/nidb/nidb.cfg')) || (file_exists('nidb.cfg')) || (file_exists('../nidb.cfg')) || (file_exists('../programs/nidb.cfg')) || (file_exists('/home/nidb/programs/nidb.cfg')) || (file_exists('/nidb/programs/nidb.cfg')) ) {
		/* if so, load the config, but still treat the page as a setup */
		$cfg = LoadConfig();
		if ($cfg != null) {
			$cfgexists = true;
			/* probably need to prompt the user if the nidbdir variable is blank */
			if ($cfg['nidbdir'] == null)
				if (file_exists("/nidb")) {
					$cfg['nidbdir'] = "/nidb";
					$installtype = "upgrade";
				}
			else
				if (file_exists($cfg['nidbdir']))
					$installtype = "upgrade";
		}
	}
	
	/* check if the client can run this page. ie, is it in the list of safe IPs */
	$cfg['setupips'] .= ",::1,127.0.0.1,localhost";
	/* if this is a first time install, allow the requestor's IP address */
	if ($installtype == "install") {
		$cfg['setupips'] .= "," . $_SERVER['REMOTE_ADDR'];
	}
	
	if ($cfg['setupips'] != "") {
		$valid = false;
		$iplist = explode(",", $cfg['setupips']);

		foreach ($iplist as $ip) {
			//echo "Checking IP from list [$ip] against REMOTE_ADDR [" . $_SERVER['REMOTE_ADDR'] . "]<br>";
			
			if (trim($ip) == $_SERVER['REMOTE_ADDR'])
				$valid = true;
		}
		if (!$valid) {
			echo "<br><br>";
			Notice("<b>You are not allowed to access this page.</b> Setup/upgrade functionality is only available to localhost and specified IP addresses. Your IP is " . $_SERVER['REMOTE_ADDR']);
			exit(0);
		}
	}
	
	
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
	$c['moduleuploadthreads'] = GetVariable("moduleuploadthreads");
	$c['modulebackupthreads'] = GetVariable("modulebackupthreads");
	$c['moduleminipipelinethreads'] = GetVariable("moduleminipipelinethreads");
	
	$c['emaillib'] = GetVariable("emaillib");
	$c['emailusername'] = GetVariable("emailusername");
	$c['emailpassword'] = GetVariable("emailpassword");
	$c['emailserver'] = GetVariable("emailserver");
	$c['emailport'] = GetVariable("emailport");
	$c['emailfrom'] = GetVariable("emailfrom");
	
	$c['adminemail'] = GetVariable("adminemail");
	$c['siteurl'] = GetVariable("siteurl");
	$c['version'] = GetVariable("version");
	$c['sitename'] = GetVariable("sitename");
	$c['sitenamedev'] = GetVariable("sitenamedev");
	$c['sitecolor'] = GetVariable("sitecolor");
	$c['ispublic'] = GetVariable("ispublic");
	$c['sitetype'] = GetVariable("sitetype");
	$c['allowphi'] = GetVariable("allowphi");
	$c['uploadsizelimit'] = GetVariable("uploadsizelimit");
	$c['displayrecentstudies'] = GetVariable("displayrecentstudies");
	$c['displayrecentstudydays'] = GetVariable("displayrecentstudydays");

	$c['enableremoteconn'] = GetVariable("enableremoteconn");
	$c['enablecalendar'] = GetVariable("enablecalendar");
	$c['enablepipelines'] = GetVariable("enablepipelines");
	$c['enabledatamenu'] = GetVariable("enabledatamenu");
	$c['enablerdoc'] = GetVariable("enablerdoc");
	$c['enablepublicdownloads'] = GetVariable("enablepublicdownloads");
	$c['enablewebexport'] = GetVariable("enablewebexport");

	$c['setupips'] = GetVariable("setupips");
	
	$c['enablecsa'] = GetVariable("enablecsa");
	$c['importchunksize'] = GetVariable("importchunksize");
	$c['numretry'] = GetVariable("numretry");
	$c['enablenfs'] = GetVariable("enablenfs");
	$c['enableftp'] = GetVariable("enableftp");
	$c['allowrawdicomexport'] = GetVariable("allowrawdicomexport");

	$c['fsldir'] = GetVariable("fsldir");
	
	$c['usecluster'] = GetVariable("usecluster");
	$c['queuename'] = GetVariable("queuename");
	$c['queueuser'] = GetVariable("queueuser");
	$c['clustersubmithost'] = GetVariable("clustersubmithost");
	$c['qsubpath'] = GetVariable("qsubpath");
	$c['clusteruser'] = GetVariable("clusteruser");
	$c['clusternidbpath'] = GetVariable("clusternidbpath");

	$c['enablecas'] = GetVariable("enablecas");
	$c['casserver'] = GetVariable("casserver");
	$c['casport'] = GetVariable("casport");
	$c['cascontext'] = GetVariable("cascontext");
	
	$c['localftphostname'] = GetVariable("localftphostname");
	$c['localftpusername'] = GetVariable("localftpusername");
	$c['localftppassword'] = GetVariable("localftppassword");

	$c['nidbdir'] = GetVariable("nidbdir");
	$c['webdir'] = GetVariable("webdir");
	$c['lockdir'] = GetVariable("lockdir");
	$c['logdir'] = GetVariable("logdir");
	$c['mountdir'] = GetVariable("mountdir");
	$c['qcmoduledir'] = GetVariable("qcmoduledir");

	$c['archivedir'] = GetVariable("archivedir");
	$c['backupdir'] = GetVariable("backupdir");
	$c['backupstagingdir'] = GetVariable("backupstagingdir");
	$c['ftpdir'] = GetVariable("ftpdir");
	$c['importdir'] = GetVariable("importdir");
	$c['incomingdir'] = GetVariable("incomingdir");
	$c['incoming2dir'] = GetVariable("incoming2dir");
	$c['packageimportdir'] = GetVariable("packageimportdir");
	$c['problemdir'] = GetVariable("problemdir");
	$c['webdownloaddir'] = GetVariable("webdownloaddir");
	$c['downloaddir'] = GetVariable("downloaddir");
	$c['uploadeddir'] = GetVariable("uploadeddir");
	$c['uploadstagingdir'] = GetVariable("uploadstagingdir");
	$c['tmpdir'] = GetVariable("tmpdir");
	$c['deleteddir'] = GetVariable("deleteddir");
	
	$c['analysisdir'] = GetVariable("analysisdir");
	$c['analysisdirb'] = GetVariable("analysisdirb");
	$c['clusteranalysisdir'] = GetVariable("clusteranalysisdir");
	$c['clusteranalysisdirb'] = GetVariable("clusteranalysisdirb");
	$c['groupanalysisdir'] = GetVariable("groupanalysisdir");

	$noconfig = GetVariable("noconfig");
	$systemmessage = GetVariable("systemmessage");
	$messageid = GetVariable("messageid");

	$rootpassword = GetVariable("rootpassword");
	$rowlimit = GetVariable("rowlimit");
	$debugonly = GetVariable("debugonly");
	//$userpassword = GetVariable("userpassword");
	//$userpassword2 = GetVariable("userpassword2");
	
	/* determine the setup step */
	switch ($step) {
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
			DisplayDatabase2Page($rootpassword, $rowlimit, $debugonly); //, $userpassword, $userpassword2);
			break;
		case 'config':
			DisplayConfigPage();
			break;
		case 'updateconfig':
			DisplaySetupCompletePage();
			break;
		case 'setupcomplete':
			WriteConfig($c, "setup");
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
	/* ------- DisplayWelcomePage ----------------- */
	/* -------------------------------------------- */
	function DisplayWelcomePage() {
		
		$disabled = "";
		$backupfile = "/nidb/data/nidb-backup-" . date('Y-m-d') . ".sql";
		
		$color1 = "warning";
			
		if (file_exists($backupfile)) {
			$color2 = "success";
			$icon2 = "check circle";
		}
		else {
			$disabled = "disabled";
			$color2 = "error";
			$icon2 = "exclamation circle";
		}
		
		?>
		<? =DisplaySetupMenu("welcome")?>
		<br><br><br><br>
		<script>
			function CopyToClipboard(id) {
				/* Get the text field */
				var copyText = document.getElementById(id);

				/* Select the text field */
				copyText.select();
				copyText.setSelectionRange(0, 99999); /* For mobile devices */

				/* Copy the text inside the text field */
				navigator.clipboard.writeText(copyText.value);

				/* Alert the copied text */
				alert("Text copied!");
			}
		</script>
		<div class="ui container">
			<div class="ui segment" style="border: 2px solid #222">
				<h1>Welcome to the NiDB setup</h1>
				The following pages will guide you through the NiDB setup/upgrade process
				
				<br><br><br>
				
				<? if ($installtype == "install") {
					$disabled = "";
				?>
				<div class="ui message">
					<h3>This is a new installation</h3>
				</div>
				
				<?
				} else {
				?>
				
				<div class="ui message">
					<h3>Perform the following before continuing with the setup</h3>
					
					<div class="ui <? =$color1?> message">
						<i class="large exclamation circle icon"></i> <b>Disable access to NiDB during the upgrade</b>
						<p style="color: black">This can be done by setting the config file <code><? =$GLOBALS['cfg']['cfgpath']?></code> variable <code>[offline] = 1</code>. Change it back to 0 to enable NiDB.</p>
					</div>
						
					<div class="ui <? =$color2?> message" style="text-align: left">
						<i class="large <? =$icon2?> icon"></i> <b>Backup your database</b>
						<p style="color: black"> Upgrade cannot continue until the backup <code><? =$backupfile?></code> exists (yes, even during the initial install. This will make sure you are familiar with the database backup process). Use the following command to backup your database. Replace PASSWORD with the <tt>nidb</tt> account password. This will be <tt>password</tt> for the initial install.</p>
						<div class="ui fluid action input">
							<input type="text" value="mysqldump --max_allowed_packet=1G --single-transaction --compact -u<? =$GLOBALS['cfg']['mysqluser']?> -pPASSWORD <? =$GLOBALS['cfg']['mysqldatabase']?> &gt; <? =$backupfile?>" style="font-family: monospace" id="backuptxt">
							<button class="ui button" onClick="CopyToClipboard('backuptxt')" title="Copy only works when HTTPS is enabled :("><i class="copy icon"></i> Copy</button>
						</div>
						<p style="color: black">Run the above command, then come back to this page and refresh.</p>
					</div>
				</div>
				<? } ?>
			</div>
		</div>
		
		<div class="ui bottom fixed inverted huge menu">
			<div class="ui inverted huge right menu">
				<div class="item">
					<div class="ui huge right pointing orange label">
						<? if ($disabled == "disabled") { ?>
						Cannot continue upgrade until database is backed up
						<? } else { ?>
						Click Next to continue
						<? } ?>
					</div>
					<a class="ui inverted <? =$disabled?> huge button" href="setup.php?step=systemcheck">Next <i class="arrow alternate circle right icon"></i></a>
				</div>
			</div>
		</div>
		<?
		
	}


	/* -------------------------------------------- */
	/* ------- DisplaySystemCheckPage ------------- */
	/* -------------------------------------------- */
	function DisplaySystemCheckPage() {
		
		?>
		<? =DisplaySetupMenu("systemcheck")?>
		<br><br><br><br><br>
		<div class="ui container">
			<div class="ui segment" style="border: 2px solid #222">

				<h2>Linux pre-requistites</h2><br>
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
				$httpd = $_SERVER['SERVER_SOFTWARE'];
				if (is_null($httpd))
					$httpdver = "<span style='color:red'>Not Installed</span> ... how are you viewing this?";
				else
					$httpdver = str_replace("Apache/", "", preg_split('/\s+/', $httpd)[0]);
				
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
						<td><? =php_uname();?></td>
					</tr>
					<tr>
						<td align="right">CPU Cores</td>
						<td><? =$cores;?></td>
					</tr>
					<tr>
						<td align="right">System memory</td>
						<td><? =$memory;?> GB</td>
					</tr>
					<tr>
						<td align="right">Apache&nbsp;(httpd)</td>
						<td><b><? =$httpdver?></b> &nbsp; <span class="tiny"><? =$httpd?></span></td>
					</tr>
					<tr>
						<td align="right">MariaDB&nbsp;(mysql)</td>
						<td><b><? =$mariadbver?></b> &nbsp; <span class="tiny"><? =$mariadb?></span></td>
					</tr>
					<tr>
						<td align="right">PHP</td>
						<td><b><? =$phpver?></b></td>
					</tr>
					<tr>
						<td align="right">ImageMagick</td>
						<td><b><? =$imagemagickver?></b> &nbsp; <span class="tiny"><? =$imagemagick?></span></td>
					</tr>
				</table>
				<br><br>
				If any packages are missing or the incorrect version, install them using yum or other methods. Then come back and refresh this page.
				<br><br>
				<h2>NiDB</h2><br>
				<?
				
				if ($GLOBALS['cfgexists']) {
					if (is_null($GLOBALS['cfg']['nidbdir'])) {
						?>
						The NiDB root directory was not defined in the config file. Go to <a href="settings.php">NiDB Settings</a> to update the <code>nidbrootdir</code> variable to reflect the installation directory of NiDB. This should be something similar to <code>/nidb</code>. The new <code>nidb.sql</code> schema file should then be located in that directory.
						<?
					}
					else {
						if (file_exists($GLOBALS['cfg']['nidbdir'])) {
							?>
							An existing NiDB installation was found at <code><? =$GLOBALS['cfg']['nidbdir']?></code> and valid config file was found at <code><? =$GLOBALS['cfg']['cfgpath']?></code>
							<br><br>
							<div class="ui orange message"><h3 class="ui header">The existing installation will be upgraded</h3></div>
							<?
						}
					}
				}
				elseif (file_exists("/nidb")) {
					?>
					Config file not found but an NiDB installation directory was found at <code>/nidb</code>. You are most likely in the middle of the first-time setup process.
					<br>
					<div class="ui orange message"><h3 class="ui header">A new installation will be configured</h3></div>
					<?
				}
				else {
					?>
					No config file or NiDB installation directory found.
					<br><br>
					<b>First-time Setup</b> via the website can only be run after the NiDB .rpm has been installed. Please install the .rpm first then come back to this page.
					<br>
					<b>Upgrade</b> can only be run on an existing installation.
					<?
				}
				
				?>
			</div>
		</div>

		<div class="ui bottom fixed inverted huge menu">
			<div class="ui inverted huge right menu">
				<div class="item">
					<a class="ui inverted large button" href="setup.php"><i class="arrow alternate circle left icon"></i>&nbsp;Back</a>
				</div>
				<div class="item">
					<a class="ui inverted huge button" href="setup.php?step=database1">Next <i class="arrow alternate circle right icon"></i></a>
				</div>
			</div>
		</div>
		<?
		
	}


	/* -------------------------------------------- */
	/* ------- DisplayDatabase1Page --------------- */
	/* -------------------------------------------- */
	function DisplayDatabase1Page() {
		?>
		<? =DisplaySetupMenu("database1")?>
		<br><br><br><br><br>
		<div class="ui container">
			<div class="ui segment" style="border: 2px solid #222">
				<h2>Database connection parameters</h2>
				<br>
				<br>
				<form method="post" action="setup.php" name="theform">
				<input type="hidden" name="step" value="database2">
				<table class="t">
					<tr>
						<td>MariaDB server</td>
						<td>
							<? if ( ($GLOBALS['cfg']['mysqlhost'] == "") || ($GLOBALS['cfg']['mysqlhost'] == null) ) { ?>
							localhost
							<? } else { ?>
							<span style="color: darkred; font-weight: bold"><? =$GLOBALS['cfg']['mysqlhost']?></span><br>
							<span class="tiny">Loaded from config file</span>
							<? }?>
						</td>
					</tr>
					<tr>
						<td>Database name</td>
						<td>
							<? if ( ($GLOBALS['cfg']['mysqldatabase'] == "") || ($GLOBALS['cfg']['mysqldatabase'] == null) ) { ?>
							nidb
							<? } else { ?>
							<span style="color: darkred; font-weight: bold"><? =$GLOBALS['cfg']['mysqldatabase']?></span><br>
							<span class="tiny">Loaded from config file</span>
							<? }?>
						</td>
					</tr>
					<tr>
						<td>MariaDB root password<br><span class="tiny" style="font-weight: normal">root access to DB required to setup tables</span></td>
						<td>
							<? if ( ($GLOBALS['cfg']['mysqlpassword'] == "") || ($GLOBALS['cfg']['mysqlpassword'] == null) || ($GLOBALS['cfg']['mysqluser'] != 'root') ) { ?>
							<input type="password" required name="rootpassword"><br><span class="tiny">Password is <tt>password</tt> if this is the <u>first</u> NiDB installation.<br>Otherwise enter the current MariaDB root password</span>
							<? } else {
								$len = strlen($GLOBALS['cfg']['mysqlpassword']);
								$pwstars = str_repeat("*",$len);
								?>
							<span style="color: darkred; font-weight: bold"><? =$pwstars?></span><br>
							<span class="tiny">Loaded from config file</span>
							<? } ?>
						</td>
					</tr>
					<tr>
						<td>MariaDB username<br><span class="tiny" style="font-weight: normal">NiDB will run as this user</span></td>
						<td>
							<? if ( ($GLOBALS['cfg']['mysqluser'] == "") || ($GLOBALS['cfg']['mysqluser'] == null) ) { ?>
							nidb
							<? } else { ?>
							<span style="color: darkred; font-weight: bold"><? =$GLOBALS['cfg']['mysqluser']?></span><br>
							<span class="tiny">Loaded from config file</span>
							<? }?>
						</td>
					</tr>
					<tr>
						<td colspan="2"><br>Upgrade Options</td>
					</tr>
					<tr>
						<td>Row Limit<br><span class="tiny" style="font-weight: normal">Do not update tables with more than N rows.<br>Leave at 0 to update all tables</span></td>
						<td><input type="number" value="0" name="rowlimit"></td>
					</tr>
					<tr>
						<td>Debug Only<br><span class="tiny" style="font-weight: normal">This will not update the database</span></td>
						<td><input type="checkbox" value="1" name="debugonly"></td>
					</tr>
				</table>
				</form>
				<br><br>
				<?
				$schemafile = "";
				if (file_exists("/nidb/setup/nidb.sql"))
					$schemafile = "/nidb/setup/nidb.sql";
				elseif (file_exists("/nidb/nidb.sql"))
					$schemafile = "/nidb/nidb.sql";
				
				if ($schemafile == "") {
					?>
					<br><br>
					<div class="ui warning message">
					<code>nidb.sql</code> not found in <code>/nidb</code> or <code>/nidb/setup</code>... <b><? =$GLOBALS['installtype']?> cannot proceed</b>
					</div>
					<br><br>
					<?
					return;
				}
				else {
					?>
					<div class="ui message">
					Found SQL schema <code><? =$schemafile?></code> with file date of <? =date('Y-m-d H:i:s', filemtime($schemafile))?>
					</div>
					<?
				}
				?>
			</div>
		</div>
		
		<div class="ui bottom fixed inverted huge menu">
			<div class="ui inverted huge right menu">
				<div class="item">
					<a class="ui inverted large button" href="setup.php?step=systemcheck"><i class="arrow alternate circle left icon"></i>&nbsp;Back</a>
				</div>
				<div class="item">
					<button class="ui inverted huge button" onClick="document.theform.submit()" title="This will make changes to the database. Have you backed up the original database?">Configure database <i class="arrow alternate circle right icon"></i></button>
				</div>
			</div>
		</div>
		<?
		
	}


	/* -------------------------------------------- */
	/* ------- DisplayDatabase2Page --------------- */
	/* -------------------------------------------- */
	function DisplayDatabase2Page($rootpassword, $rowlimit, $debugonly) {

		if ( ($GLOBALS['cfg']['mysqlpassword'] != "") && ($GLOBALS['cfg']['mysqluser'] == "root") )
			$rootpassword = $GLOBALS['cfg']['mysqlpassword'];
		
		$schemafile = "/nidb/setup/nidb.sql";
		$sqldatafile = "/nidb/setup/nidb-data.sql";
		
		if ( (is_null($GLOBALS['cfg']['mysqldatabase'])) || ($GLOBALS['cfg']['mysqldatabase'] == "") ) {
			$database = "nidb";
		}
		else {
			$database = $GLOBALS['cfg']['mysqldatabase'];
		}
		
		$ignoredtables = array();
		?>
		<? =DisplaySetupMenu("database2")?>
		<br><br><br><br><br>
		<div class="ui container">
			<div class="ui segment" style="border: 2px solid #222; overflow: auto;">
				<h2>Performing database setup... <? if ($debugonly) { echo "DEBUG only. No database changes"; } ?></h2>

				<?
				
				if (is_null($rootpassword)) {
					?>
					<div class="ui error message">
						<h3>Unable to connect to database</h3>
						<p>
						root MySQL password was blank
						</p>
					</div>
					<?
				}
				else {
					
					$GLOBALS['linki'] = mysqli_connect('localhost', 'root', $rootpassword);
					
					if (!$GLOBALS['linki']) {
						?>
						<div class="ui error message"><h3>Unable to connect to database</h3>
							<p>
							Error number: <? =mysqli_connect_errno()?><br>
							Error message: <? =mysqli_connect_error()?>
							</p>
						</div>
						<?
					}
					else {
						?>
						<div class="ui success message"><i class="check circle icon"></i> Successfully connected to the database server</div>
						<?
						
						/* check if the database itself exists */
						$sqlstring = "show databases like '$database'";
						$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
						if (mysqli_num_rows($result) > 0) {
							?><div class="ui success message"><i class="check circle icon"></i> Database '<? =$database?>' exists</div><?
							
							/* check if there are any tables */
							$sqlstring = "SELECT COUNT(DISTINCT `table_name`) FROM `information_schema`.`columns` WHERE `table_schema` = '$database'";
							$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
							if (mysqli_num_rows($result) > 0) {
								?>
								<div class="ui success message"><i class="check circle icon"></i> Existing tables found in '<? =$database?>' database. Upgrading SQL schema</div>
								<?
								list($ignoredtables, $errors) = UpgradeDatabase($GLOBALS['linki'], $database, $schemafile, $rowlimit, $debugonly);
								//PrintVariable($errors);
								
								if (count($errors) > 0) {
									?>
									<script>
										$(document).ready(function() {
											$('body').toast({
												displayTime: 0,
												class: 'error',
												position: 'bottom right',
												message: "Upgrade encountered errors. Scroll to bottom of this page to see errors. (Click this message to close it)"
											});
										});
									</script>
									
									<div class="ui error message" style="text-align: left !important;">
										<div class="header"><i class="exclamation circle icon"></i>Upgrade Errors</div>
										Fix these errors then refresh this page
										
										<ul>
											<?
												foreach ($errors as $err) {
													echo "<li>$err\n";
												}
											?>
										</ul>
									</div>
									<?
								}
								else {
									
								}
								
								if (file_exists($sqldatafile)) {
									$systemstring = "mysql -uroot -p$rootpassword $database < $sqldatafile";
									shell_exec($systemstring);
								}
								else {
									?><div class="ui error message"><code><? =$sqldatafile?></code> not found. This file should have been provided by the installer</div><?
								}
							}
							else {
								?><li>No tables found in '<? =$database?>' database. Running full SQL script<?
								/* load the sql file(s) */
								if (file_exists($schemafile)) {
									$systemstring = "mysql -uroot -p$rootpassword $database < $schemafile";
									shell_exec($systemstring);
									
									if (file_exists($sqldatafile)) {
										$systemstring = "mysql -uroot -p$rootpassword $database < $sqldatafile";
										shell_exec($systemstring);
									}
									else {
										?><div class="ui error message"><code><? =$sqldatafile?></code> not found. This file should have been provided by the installer</div><?
									}
								}
								else {
									?><div class="ui error message"><code><? =$schemafile?></code> not found. This file should have been provided by the installer</div><?
								}

							}
						}
						else {
							$sqlstring = "create database `$database`";
							$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
							?><div class="ui success message">Created database '<? =$database?>'</div><?
							
							/* load the sql file(s) */
							if (file_exists($schemafile)) {
								$systemstring = "mysql -uroot -p$rootpassword $database < $schemafile";
								shell_exec($systemstring);
								
								if (file_exists($sqldatafile)) {
									$systemstring = "mysql -uroot -p$rootpassword $database < $sqldatafile";
									shell_exec($systemstring);
								}
								else {
									?><div class="ui error message"><code><? =$sqldatafile?></code> not found. This file should have been provided by the installer</div><?
								}
							}
							else {
								?><div class="ui error message"><code><? =$schemafile?></code> not found. This file should have been provided by the installer</div><?
							}
						}
					}
				}
				?>
				</ol>
				<? if (count($ignoredtables) > 0) {?>
				<br>
				<b>Ignored tables</b><br>
				The following tables were not updated because they have too many rows. They must be upgraded manually via phpMyAdmin.<br>
				<?
					echo implode2("<br>", $ignoredtables);
				?>
				<? } ?>
			</div>
		</div>
		<br><br><br><br><br>

		<div class="ui bottom fixed inverted huge menu">
			<div class="ui inverted huge right menu">
				<div class="item">
					<a class="ui inverted large button" href="setup.php?step=database1"><i class="arrow alternate circle left icon"></i>&nbsp;Back</a>
				</div>
				<div class="item">
					<a class="ui inverted huge button" href="setup.php?step=config">Next <i class="arrow alternate circle right icon"></i></a>
				</div>
			</div>
		</div>
		<?
		
	}


	/* -------------------------------------------- */
	/* ------- DisplayConfigPage ------------------ */
	/* -------------------------------------------- */
	function DisplayConfigPage() {
		
		?>
		<? =DisplaySetupMenu("config")?>
		<br><br><br><br><br>
		<div class="ui container">
			<? DisplaySettings("setup"); ?>
		</div>

		<br><br><br><br><br>

		<div class="ui bottom fixed inverted huge menu">
			<div class="ui inverted huge right menu">
				<div class="item">
					<a class="ui inverted large button" href="setup.php?step=database1"><i class="arrow alternate circle left icon"></i>&nbsp;Back</a>
				</div>
				<div class="item">
					<button class="ui inverted large button" onclick="document.configform.submit();">Write Config <i class="arrow alternate circle right icon"></i></a>
				</div>
			</div>
		</div>
		<?
		
	}


	/* -------------------------------------------- */
	/* ------- DisplaySetupCompletePage ----------- */
	/* -------------------------------------------- */
	function DisplaySetupCompletePage() {
		
		?>
		<? =DisplaySetupMenu("config")?>

		<br><br><br><br><br>

		<div class="ui container">
			<div class="ui segment" style="border: 2px solid #222; overflow: auto;">
				<h2>Setup Complete</h2>
				Visit the <b>Admin</b> menu option to administer this instance of NiDB
			</div>
		</div>
		<br><br><br><br><br>

		<div class="ui bottom fixed inverted huge menu">
			<div class="ui inverted huge right menu">
				<div class="item">
					<a class="ui inverted large button" href="setup.php?step=config"><i class="arrow alternate circle left icon"></i>&nbsp;Back</a>
				</div>
				<div class="item">
					<a class="ui blue inverted large button" href="index.php">Done</a>
				</div>
			</div>
		</div>
		<?
		
		/* remove /nidb/setup/dbupgrade file */
		unlink("/nidb/setup/dbupgrade");
		
	}


	/* -------------------------------------------- */
	/* ------- DisplaySetupMenu ------------------- */
	/* -------------------------------------------- */
	function DisplaySetupMenu($step) {
		?>
		<div class="ui huge inverted top fixed menu">
			<? if ($step == "welcome") { ?>
				<div class="active item">Welcome to NiDB Setup</div>
			<? } else if (in_array($step, array("systemcheck","database1","database2","config","setupcomplete"))) { ?>
				<div class="item"><i class="inverted check circle icon"></i> Welcome to NiDB Setup</div>
			<? } else { ?>
				<div class="item">Welcome to NiDB Setup</div>
			<? } ?>
			
			<? if ($step == "systemcheck") { ?>
				<div class="active item">System Check</div>
			<? } else if (in_array($step, array("database1","database2","config","setupcomplete"))) { ?>
				<div class="item"><i class="inverted check circle icon"></i> System Check</div>
			<? } else { ?>
				<div class="item">System Check</div>
			<? } ?>
			
			<? if ($step == "database") { ?>
				<div class="active item">Database</div>
			<? } else if (in_array($step, array("config","setupcomplete"))) { ?>
				<div class="item"><i class="inverted check circle icon"></i> Database</div>
			<? } else { ?>
				<div class="item">Database</div>
			<? } ?>

			<? if ($step == "config") { ?>
				<div class="active item">Settings</div>
			<? } else if (in_array($step, array("setupcomplete"))) { ?>
				<div class="item"><i class="inverted check circle icon"></i> Settings</div>
			<? } else { ?>
				<div class="item">Settings</div>
			<? } ?>

			<? if ($step == "setupcomplete") { ?>
				<div class="active item"><i class="inverted check circle icon"></i> Setup Complete</div>
			<? } else { ?>
				<div class="item">Setup Complete</div>
			<? } ?>
		</div>		
		<?
	}


	/* -------------------------------------------- */
	/* ------- SQLQuery --------------------------- */
	/* -------------------------------------------- */
	function SQLQuery($sqlstring, $debug, $file, $line) {
		$ret = "";
		
		if ($debug)
			echo "<code>$sqlstring</code><br>";
		else {
			$result = MySQLiQuery($sqlstring, $file, $line, true);
			if ($result['error'] == 1) {
				$ret = $result['errormsg'] . " (" . $result['sql'] . ")";
			}
		}
		
		return $ret;
	}
	

	/* -------------------------------------------- */
	/* ------- UpgradeDatabase -------------------- */
	/* -------------------------------------------- */
	function UpgradeDatabase($linki, $database, $sqlfile, $rowlimit, $debug) {
		?>
		<div class="ui message">
		<?
		
		if (!file_exists($sqlfile)) {
			echo "[$sqlfile] not found<br>";
			return false;
		}
		
		if (!mysqli_select_db($linki, $database)) {
			echo "Unable to select database [$database]<br>";
			return false;
		}
		
		if ($rowlimit > 0) {
			echo "Tables with more than $rowlimit rows will not be upgraded<br>";
		}
		
		$ignoredtables = array();
		$err = array();
		
		/* disable strict mode to prevent truncation errors */
		$sqlstring = "SET @@global.sql_mode= ''";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__, true);

		/* load the file, loop through the lines */
		$lines = file($sqlfile);
		$table = "";
		$tablerowcount = 0;
		$lastcolumn = "";
		$tableexists = false;
		$indextable = "";
		$createindex = "";
		$lastline = false;
		$sqlstring = "";
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
				
				/* check if this table exists */
				$sqlstring = "show tables like '$table'";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__, true);
				
				if (mysqli_num_rows($result) > 0) {
					$tableexists = true;
					/* get the table row count */
					$sqlstring = "select count(*) 'count' from $table";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__, true);
					$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
					$tablerowcount = intval($row['count']);
				}
				else {
					$tableexists = false;
					/* start putting together the create table statement */
					$createtable = $line;
				}
				$createindex = "";
				
				echo "Table <span class='e'>$table</span> - " . number_format($tablerowcount, 0) . " rows<br>";
				
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
				
				if (($tablerowcount >= $rowlimit) && ($rowlimit > 0)) {
					echo "Table <tt class='e'>$table</tt> has $tablerowcount rows. Skipping upgrade<br>";
					$ignoredtables[] = $table;
				}
				else {
					if ($createtable != "") {
						echo "Table <tt class='e'>$table</tt> did not exist, creating";
						echo "<tt><pre>$createtable</pre></tt>";
						
						/* create the table */
						$sqlstring = $createtable;
						$err[] = SQLQuery($sqlstring, $debug, __FILE__, __LINE__);
					}
				}
				
				$table = "";
				$tablerowcount = 0;
				$previouscol = "";
				$tableexists = false;
				$createtable = "";
				$createindex = "";
				$sqlstring = "";
			}
			
			/* regular column to be added/updated for the current table */
			if (($table != "") && ($createtable == "") && (substr($line,0,1) == "`")) {
				if (($tablerowcount >= $rowlimit) && ($rowlimit > 0)) {
					//echo "Table <tt class='e'>$table</tt> has $tablerowcount rows. Skipping upgrade<br>";
				}
				else {
					$parts = preg_split('/`/', $line);
					$column = trim($parts[1]);
					$properties = trim($parts[2]);
					$properties = rtrim($properties,",");
					
					$parts2 = explode(" ", $properties);
					$file_type = trim($parts2[0]);
					if (contains(strtolower($properties), "on update current_timestamp()")) {
						$file_type = $file_type . " on update current_timestamp()";
					}
					elseif (contains(strtolower($properties), "unsigned zerofill")) {
						$file_type = $file_type . " unsigned zerofill";
					}
					elseif (strtolower($parts2[1]) == "unsigned") {
						$file_type = $file_type . " unsigned";
					}
					elseif (strtolower($parts2[1]) == "binary") {
						$file_type = $file_type . " binary";
					}
					
					
					$file_null = false;
					$file_default = "";

					if (contains($properties, "DEFAULT NULL")) {
						$file_default = "null";
						$file_null = true;
					}
					
					/* check if the column exists */
					$sqlstringA = "show columns from `$table` where Field = '$column'";
					$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__, true);
					if (mysqli_num_rows($resultA) > 0) {
						$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
						$type = $rowA['Type'];
						$key = $rowA['Key'];
						$null = $rowA['Null']; /* possible values YES, NO */
						$default = $rowA['Default']; /* possible values: anything, or NULL */
						$extra = $rowA['Extra']; /* possible values: on update current_timestamp() */
						
						/* figure out what the default value should look like, to compare it to the  */
						if (is_null($default)) {
							if ($null == "YES")
								$default = "DEFAULT NULL";
							else
								$default = "";
						}
						else
							if (is_numeric($default))
								$default = "DEFAULT $default";
							else
								if ($default == "current_timestamp()")
									$default = "DEFAULT $default";
								else
									$default = "DEFAULT '$default'";
						
						if (strtolower($extra) == "on update current_timestamp()") {
							$type = $type . " on update current_timestamp()";
						}
						
						/* check if the column is different */
						if ( (($default == "") || (contains($properties, $default)) ) && (strtolower($file_type) == strtolower($type)) ) {
							//echo "Column <tt>$column</tt> is unchanged, skipping<br>";
						}
						else {

							echo "Column <tt>$column</tt> is different. Current [$type $null $default $extra] new [$file_type $properties]<br>";
							
							/* change the column if it already exists */
							$sqlstring = "alter ignore table `$table` change column if exists `$column` `$column` $properties";
							if ($previouscol != "") {
								$sqlstring .= " after `$previouscol`";
							}
							/* if there is an issue with this column, it will be an error, so no need to check warnings */
							$err[] = SQLQuery($sqlstring, $debug, __FILE__, __LINE__);

							echo " &nbsp; <tt style='font-size: smaller;'><i class='green check circle icon'></i> $column</tt> modified.<br>";
						}
					}
					else {
						/* add the column if it does not exist */
						$sqlstring = "alter table `$table` add column if not exists `$column` $properties";
						if ($previouscol != "") {
							$sqlstring .= " after `$previouscol`";
						}
						$err[] = SQLQuery($sqlstring, $debug, __FILE__, __LINE__);

						echo " &nbsp; Column <tt style='font-size: smaller;'><i class='green check circle icon'></i> $column</tt> added.<br>";
					}
				}
				
				$previouscol = $column;
				$createindex = "";
				$sqlstring = "";
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
					//echo "<tt span style='font-size: smaller;'><pre>$createindex</pre></tt>";
					$err[] = SQLQuery($createindex, $debug, __FILE__, __LINE__);
				}
				
				$indextable = str_replace("`", "", preg_split('/\s+/', $line)[2]);
				echo "<br>Index/autoincrement for table <span class='e'>$indextable</span>";
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
				$err[] = SQLQuery($createtable, $debug, __FILE__, __LINE__);
			}
			
			/* this is the end of the file. If there are any indexes/autoincrements to create, create them now */
			if (($lastline) && ($createindex != "")) {
				$createindex = str_replace("ALTER TABLE", "ALTER IGNORE TABLE", $createindex);
				$createindex = str_replace("ADD UNIQUE KEY", "ADD UNIQUE KEY IF NOT EXISTS", $createindex);
				$createindex = str_replace("ADD PRIMARY KEY", "ADD PRIMARY KEY IF NOT EXISTS", $createindex);
				$createindex = str_replace("ADD KEY", "ADD KEY IF NOT EXISTS", $createindex);
				
				//echo "<tt span style='font-size: smaller;'><pre>$createindex</pre></tt>";
				$err[] = SQLQuery($createindex, $debug, __FILE__, __LINE__);
			}
		}
		
		?>
		</div>
		<?
		
		return array(array_unique($ignoredtables), array_filter($err));
	}
?>
