<?
 // ------------------------------------------------------------------------------
 // NiDB api.php
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

 /* This page is the public API for interaction with NiDB
	A valid username and sha1(password) hash is required for every transaction */
	//declare(strict_types = 1);
	define("LEGIT_REQUEST", true);

	$nologin = true;
	//$debug = true;
	
	require "functions.php";
	require "includes_php.php";
	require "nidbapi.php";

	/* ----- setup variables ----- */
	$u = GetVariable("u");
	$p = GetVariable("p");
	
	/* before even checking any more variables... (I know, they're already accepted by PHP)
	... into variables inside the program, authenticate */
	if (!Authenticate($u,$p)) {
		echo "LOGINERROR";
		exit(0);
	}
	
	/* assuming good authentication, continue */
	$action = GetVariable("action");
	$uuid = GetVariable("uuid");
	$anonymize = GetVariable("anonymize");
	$equipmentid = GetVariable("equipmentid");
	$siteid = GetVariable("siteid");
	$projectid = GetVariable("projectid");
	$instanceid = GetVariable("instanceid");
	$transactionid = GetVariable("transactionid");
	$altuid = GetVariable("altuid");
	$dob = GetVariable("dob"); /* format YYYY-MM-DD */
	$age = GetVariable("age"); /* double */
	$sex = GetVariable("sex"); /* single character */
	$instance = GetVariable("instance");
	$dataformat = GetVariable("dataformat");
	$modality = GetVariable("modality");
	$numfiles = GetVariable("numfiles");
	$matchidonly = GetVariable("matchidonly");
	$altuids = GetVariable("altuids");
	$seriesnotes = GetVariable("seriesnotes");
	$debug = GetVariable("debug");

	if ($debug) {
		print_r($_POST);
		echo "FILES: \n";
		print_r($_FILES);
	}
	
	switch($action) {
		case 'UploadDICOM': UploadDICOM($uuid, $dob, $age, $sex, $seriesnotes, $altuids, $anonymize, $dataformat, $modality, $numfiles, $equipmentid, $siteid, $projectid, $instanceid, $matchidonly, $transactionid); break;
		case 'getUID': GetUIDFromAltUID($altuid); break;
		case 'getInstanceList': GetInstanceList($u); break;
		case 'getProjectList': GetProjectList($u, $instance); break;
		case 'getSiteList': GetSiteList($u, $instance); break;
		case 'getEquipmentList': GetEquipmentList(); break;
		case 'startTransaction': StartTransaction($u); break;
		case 'endTransaction': EndTransaction($transactionid); break;
		case 'getTransactionStatus': GetTransactionStatus($transactionid); break;
		case 'getArchiveStatus': GetArchiveStatus($transactionid); break;
		default: echo "Welcome to NiDB v" . $GLOBALS['cfg']['version'];
	}
	
	/* -------------------------------------------- */
	/* ------- Authenticate ----------------------- */
	/* -------------------------------------------- */
	function Authenticate($username, $password) {
		if ((is_null($username)) || (is_null($password)))
			return false;
		
		$username = trim($username);
		$password = trim($password);

		if (($username == "") || ($password == "")) {
			return false;
		}
		
		/* only authenticate standard accounts */
		
		if (AuthenticateStandardUser($username, $password)) {
			$q = mysqli_stmt_init($GLOBALS['linki']);
			mysqli_stmt_prepare($q, "insert into remote_logins (username, ip, login_date, login_result) values (?, ?, now(), 'success')");
			mysqli_stmt_bind_param($q, 'ss', $username, $_SERVER['REMOTE_ADDR']);
			MySQLiBoundQuery($q, __FILE__, __LINE__);
			return true;
		}
		else {
			$q = mysqli_stmt_init($GLOBALS['linki']);
			mysqli_stmt_prepare($q, "insert into remote_logins (username, ip, login_date, login_result) values (?, ?, now(), 'failure')");
			mysqli_stmt_bind_param($q, 'ss', $username, $_SERVER['REMOTE_ADDR']);
			MySQLiBoundQuery($q, __FILE__, __LINE__);
			return false;
		}
	}


	/* -------------------------------------------- */
	/* ------- AuthenticateStandardUser ----------- */
	/* -------------------------------------------- */
	function AuthenticateStandardUser($username, $password) {
		/* attempt to authenticate a standard user */
		if ((is_null($username)) || (is_null($password)))
			return false;
		
		$username = trim($username);
		$password = trim($password);

		if (($username == "") || ($password == "")) {
			return false;
		}
		
		//$sqlstring = "select user_id from users where (username = '$username' or username = sha1('$username')) and (password = sha1('$password') or password = '$password') and user_enabled = 1";

		$q = mysqli_stmt_init($GLOBALS['linki']);
		mysqli_stmt_prepare($q, "select user_id from users where (username = ? or username = sha1(?)) and (password = sha1(?) or password = ?) and user_enabled = 1");
		mysqli_stmt_bind_param($q, 'ssss', $username, $username, $password, $password);
		$result = MySQLiBoundQuery($q, __FILE__, __LINE__);
		
		//echo "[SQL: $sqlstring]";
		//$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if ($result && (mysqli_num_rows($result) > 0)) {
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$GLOBALS['userid'] = $row['user_id'];
			return true;
		}
		else {
			return false;
		}
	}
	
	
	/* -------------------------------------------- */
	/* ------- AuthenticateUnixUser --------------- */
	/* -------------------------------------------- */
	function AuthenticateUnixUser($username, $password) {
		
		if (($username != "root") && ($username != "")) {
			
			/* attempt to authenticate a unix user */
			$pwent = posix_getpwnam($username);
			$password_hash = $pwent["passwd"];

			if (trim(shell_exec("command -v ypmatch")) != "") {
					
				$autharray = explode(":",shell_exec("ypmatch " . escapeshellarg($username) . " passwd"));
				if ($autharray[0] != $username) {
					return false;
				}
				
				$cryptpw = crypt($password, $autharray[1]);
				
				if($cryptpw == $autharray[1])
					return true;
			}
		}
		
		return false;
	}

	
	/* -------------------------------------------- */
	/* ------- StartTransaction ------------------- */
	/* -------------------------------------------- */
	function StartTransaction($u, $source="unknown") {
		$sqlstring = "insert into import_transactions (transaction_startdate, transaction_source, transaction_status, transaction_username) values (now(), ?, 'uploading', ?)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ss', $source, $u);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$source, $u]);
		$tid = mysqli_insert_id($GLOBALS['linki']);
		mysqli_stmt_close($stmt);
		echo $tid;
	}

	
	/* -------------------------------------------- */
	/* ------- EndTransaction --------------------- */
	/* -------------------------------------------- */
	function EndTransaction($tid) {
		$tid = (int)$tid;
		$stmt = mysqli_prepare($GLOBALS['linki'], "update import_transactions set transaction_enddate = now(), transaction_status = 'uploadcomplete' where importtrans_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $tid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);
		echo "Ok";
	}


	/* -------------------------------------------- */
	/* ------- GetTransactionStatus --------------- */
	/* -------------------------------------------- */
	function GetTransactionStatus($transactionid) {
		$transactionid = (int)$transactionid;
		
		$stmt = mysqli_prepare($GLOBALS['linki'], "select a.*, b.project_name, c.site_name, d.instance_name from import_requests a left join projects b on a.import_projectid = b.project_id left join nidb_sites c on a.import_siteid = c.site_id left join instance d on a.import_instanceid = d.instance_id where a.import_transactionid = ? order by import_datetime desc");
		mysqli_stmt_bind_param($stmt, 'i', $transactionid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);
		
		/* null (not an empty array) so an empty result still prints "null", as before */
		$a = null;
		while ($result && ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))) {
			$a[] = $row;
		}

		echo json_encode($a, JSON_FORCE_OBJECT);
	}


	/* -------------------------------------------- */
	/* ------- GetArchiveStatus ------------------- */
	/* -------------------------------------------- */
	function GetArchiveStatus($transactionid) {
		$transactionid = (int)$transactionid;

		$stmt = mysqli_prepare($GLOBALS['linki'], "select importrequest_id from import_requests where import_transactionid = ?");
		mysqli_stmt_bind_param($stmt, 'i', $transactionid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);
		
		$groupids = array();
		while ($result && ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))) {
			$groupids[] = (int)$row['importrequest_id'];
		}
		/* intval-sanitized ids from the database, safe to inline */
		$grouplist = implode2(',',$groupids);
		if ($grouplist == "") {
			$grouplist = 'null';
		}
		
		$sqlstring = "select *, timediff(max(importstartdate), min(importstartdate)) 'importtime', date_format(max(importstartdate), '%b %e, %Y %T') 'maximportdatetime', date_format(studydatetime_orig, '%b %e, %Y %T') 'studydatetime', date_format(seriesdatetime_orig, '%b %e, %Y %T') 'seriesdatetime', count(*) 'numfiles' from importlogs where importgroupid in ($grouplist) group by stationname_orig, studydatetime_orig, seriesnumber_orig order by studydatetime_orig desc, seriesdatetime_orig";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$a = null;
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$a[] = $row;
		}

		echo json_encode($a, JSON_FORCE_OBJECT);
	}

	
	/* -------------------------------------------- */
	/* ------- GetUIDFromAltUID ------------------- */
	/* -------------------------------------------- */
	function GetUIDFromAltUID($altuid) {
		$altuid = (string)$altuid;

		$stmt = mysqli_prepare($GLOBALS['linki'], "select uid from subjects where subject_id in (select subject_id from subject_altuid where altuid = ?)");
		mysqli_stmt_bind_param($stmt, 's', $altuid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);
		
		$uids = array();
		while ($result && ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))) {
			$uids[] = $row['uid'];
		}
		if (count($uids) > 0) {
			echo implode(',',$uids);
		}
	}


	/* -------------------------------------------- */
	/* ------- GetInstanceList -------------------- */
	/* -------------------------------------------- */
	function GetInstanceList($u) {
		$u = (string)$u;

		$stmt = mysqli_prepare($GLOBALS['linki'], "select * from instance where instance_id in (select instance_id from user_instance where user_id = (select user_id from users where username = ?)) order by instance_name");
		mysqli_stmt_bind_param($stmt, 's', $u);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);
		
		$instances = array();
		while ($result && ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))) {
			$instanceuid = $row['instance_uid'];
			$instancename = $row['instance_name'];
			$instances[] = "$instanceuid|$instancename";
		}
		if (count($instances) > 0) {
			echo implode(',',$instances);
		}
	}


	/* -------------------------------------------- */
	/* ------- GetProjectList --------------------- */
	/* -------------------------------------------- */
	function GetProjectList($u, $instance) {
		$u = (string)$u;
		$instance = (string)$instance;
		
		$stmt = mysqli_prepare($GLOBALS['linki'], "select * from projects a left join user_project b on a.project_id = b.project_id left join users c on b.user_id = c.user_id where c.username = ? and a.instance_id = (select instance_id from instance where instance_uid = ?) and (b.view_data = 1 or b.view_phi = 1 or b.write_data = 1 or b.write_phi = 1) order by a.project_name");
		mysqli_stmt_bind_param($stmt, 'ss', $u, $instance);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);
		
		$projects = array();
		while ($result && ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))) {
			$projectid = $row['project_id'];
			$projectname = $row['project_name'];
			$projects[] = "$projectid|$projectname";
		}
		if (count($projects) > 0) {
			echo implode(',',$projects);
		}
	}


	/* -------------------------------------------- */
	/* ------- GetSiteList ------------------------ */
	/* -------------------------------------------- */
	function GetSiteList($u, $instance) {
		/* parameterless. $u and $instance are currently unused */
		$sqlstring = "select * from nidb_sites order by site_name";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$sites = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$siteid = $row['site_id'];
			$sitename = $row['site_name'];
			$sites[] = "$siteid|$sitename";
		}
		if (count($sites) > 0) {
			echo implode(',',$sites);
		}
	}


	/* -------------------------------------------- */
	/* ------- GetEquipmentList ------------------- */
	/* -------------------------------------------- */
	function GetEquipmentList() {
		$sqlstring = "select distinct(study_site) 'equipment' from studies where study_site <> '' order by study_site";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$sites = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$equipment = $row['equipment'];
			$sites[] = "$equipment|$equipment";
		}
		if (count($sites) > 0) {
			echo implode(',',$sites);
		}
	}
	

	/* -------------------------------------------- */
	/* ------- LookupRowID ------------------------ */
	/* -------------------------------------------- */
	/* find a row ID from a value that may be either the numeric row ID or the UID.
	   $table, $idcol, $uidcol are hardcoded by the callers, never user input */
	function LookupRowID($table, $idcol, $uidcol, $value) {
		$value = (string)$value;
		if (isInteger($value)) {
			$intvalue = (int)$value;
			$stmt = mysqli_prepare($GLOBALS['linki'], "select `$idcol` from `$table` where `$idcol` = ? or `$uidcol` = ?");
			mysqli_stmt_bind_param($stmt, 'is', $intvalue, $value);
		}
		else {
			$stmt = mysqli_prepare($GLOBALS['linki'], "select `$idcol` from `$table` where `$uidcol` = ?");
			mysqli_stmt_bind_param($stmt, 's', $value);
		}
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);
		
		$row = $result ? mysqli_fetch_array($result, MYSQLI_ASSOC) : null;
		return $row[$idcol] ?? "";
	}
	

	/* -------------------------------------------- */
	/* ------- UploadDICOM ------------------------ */
	/* -------------------------------------------- */
	function UploadDICOM($uuid, $dob, $age, $sex, $seriesnotes, $altuids, $anonymize, $dataformat, $modality, $numfiles, $equipmentid, $siteid, $projectid, $instanceid, $matchidonly, $transactionid) {
		
		/* values are bound below, so no escaping */
		$uuid = (string)$uuid;
		$sex = (string)$sex;
		$anonymize = GetMySQLTinyInt($anonymize);
		$dataformat = (string)$dataformat;
		$modality = (string)$modality;
		$equipmentid = (string)$equipmentid;
		$matchidonly = GetMySQLTinyInt($matchidonly);
		$seriesnotes = (string)$seriesnotes;
		/* nullable columns: blank becomes SQL NULL */
		$transactionid = (trim((string)$transactionid) === '') ? null : (int)$transactionid;
		$dob = (trim((string)$dob) === '') ? null : (string)$dob;
		$age = (trim((string)$age) === '') ? null : (float)$age;
		
		$altuidlist = explode(',',(string)$altuids);
		$altuidlist = array_unique($altuidlist);
		$altuids = implode(',',$altuidlist);
		
		/* get the instanceRowID */
		$instanceRowID = LookupRowID('instance', 'instance_id', 'instance_uid', $instanceid);
		if ($instanceRowID == "") {
			echo "ERROR_INVALID_INSTANCEID";
			exit(0);
		}
		
		/* get the projectRowID */
		$projectRowID = LookupRowID('projects', 'project_id', 'project_uid', $projectid);
		if ($projectRowID == "") {
			echo "ERROR_INVALID_PROJECTID";
			exit(0);
		}
		
		/* get the siteRowID */
		$siteRowID = LookupRowID('nidb_sites', 'site_id', 'site_uid', $siteid);
		if ($siteRowID == "") {
			echo "ERROR_INVALID_SITEID";
			exit(0);
		}
		
		$md5list = array();
		
		/* check if there is anything in the FILES global variable */
		if (isset($_FILES['files'])){
			/* and check if we received at least 1 file */
			if (count($_FILES['files']) > 0) {
				/* get next import ID */
				$sqlstring = "insert into import_requests (import_transactionid, import_datatype, import_modality, import_datetime, import_status, import_startdate, import_equipment, import_siteid, import_projectid, import_instanceid, import_dob, import_sex, import_age, import_uuid, import_seriesnotes, import_altuids, import_anonymize, import_matchidonly) values (?, ?, ?, now(), 'uploading', now(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
				$params = [$transactionid, $dataformat, $modality, $equipmentid, $siteRowID, $projectRowID, $instanceRowID, $dob, $sex, $age, $uuid, $seriesnotes, $altuids, $anonymize, $matchidonly];
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'isssiiissdsssii', ...$params);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
				$uploadID = mysqli_insert_id($GLOBALS['linki']);
				mysqli_stmt_close($stmt);
				
				$numfilessuccess = 0;
				$numfilesfail = 0;
				$numfilestotal = 0;
				$numbehsuccess = 0;
				$numbehfail = 0;
				$numbehtotal = 0;
				$report = "DateTime\tFilenameIn\tFilenameOutPath\tMD5\tFileSize\tStatusMessage\n";
				
				$savepath = $GLOBALS['cfg']['uploadeddir'] . "/$uploadID";
				$behsavepath = $GLOBALS['cfg']['uploadeddir'] . "/$uploadID/beh";
		
				/* go through all the files and save them */
				mkdir($savepath, 0777, true);
				chmod($savepath, 0777);
				foreach ($_FILES['files']['name'] as $i => $name) {
					/* client-supplied filename: never allow directory components */
					$name = basename($name);
					$numfilestotal++;
					$filemd5 = "";
					$filesize = 0;
					error_reporting(E_ALL);
					if (move_uploaded_file($_FILES['files']['tmp_name'][$i], "$savepath/$name")) {
						$filesize = filesize("$savepath/$name");
						if ($filesize > 0) {
							if ($GLOBALS['debug'] == 1) echo "RECEIVED $savepath/$name\n";
							$numfilessuccess++;
							chmod("$savepath/$name", 0777);
							$filemd5 = strtoupper(md5_file("$savepath/$name"));
							$md5list[] = $filemd5;
							if ($GLOBALS['debug'] == 1) echo date('c') . " [MD5: $filemd5]\n";
							$success = 1;
							
							$report .= date('c'). "\t$name\t$savepath/$name\t$filemd5\t$filesize\tFile successfully received\n";
						}
						else {
							if ($GLOBALS['debug'] == 1) echo "ERROR filesize is 0 [$savepath/$name]\n";
							$numfilesfail++;
							$success = 0;
							
							$report .= date('c'). "\t$name\t$savepath/$name\t$filemd5\t$filesize\tFilesize is 0 bytes\n";
						}
					}
					else {
						if ($GLOBALS['debug'] == 1) echo "ERROR moving [" . $_FILES['files']['tmp_name'][$i] . "] to [$savepath/$name]\n";
						$numfilesfail++;
						$success = 0;
						
						$report .= date('c'). "\t$name\t$savepath/$name\t$filemd5\t$filesize\tUnable to copy file to output path\n";
					}
					
				}
				
				/* go through all the beh files and save them */
				if (isset($_FILES['behs'])){ 
					mkdir($behsavepath, 0777, true);
					chmod($behsavepath, 0777);
					foreach ($_FILES['behs']['name'] as $i => $name) {
						$name = basename($name);
						$numbehtotal++;
						$filemd5 = "";
						$filesize = 0;
						if (move_uploaded_file($_FILES['behs']['tmp_name'][$i], "$behsavepath/$name")) {
							$numbehsuccess++;
							chmod("$behsavepath/$name", 0777);
							$filemd5 = strtoupper(md5_file("$behsavepath/$name"));
							$md5list[] = $filemd5;
							$filesize = filesize("$behsavepath/$name");
							$success = 1;
							
							$report .= date('c'). "\t$name\t$behsavepath/$name\t$filemd5\t$filesize\tFile successfully received\n";
						}
						else {
							$numbehfail++;
							$success = 0;

							$report .= date('c'). "\t$name\t$behsavepath/$name\t$filemd5\t$filesize\tUnable to copy file to output path\n";
						}
					}
				}
				$sqlstring = "update import_requests set import_status = 'pending', numfilestotal = ?, numfilessuccess = ?, numfilesfail = ?, numbehtotal = ?, numbehsuccess = ?, numbehfail = ?, uploadreport = ? where importrequest_id = ?";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'iiiiiisi', $numfilestotal, $numfilessuccess, $numfilesfail, $numbehtotal, $numbehsuccess, $numbehfail, $report, $uploadID);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__);
				mysqli_stmt_close($stmt);
			}
			else {
				echo "UPLOADERROR";
			}
		}
		echo "SUCCESS," . implode(",",$md5list);
	}
?>