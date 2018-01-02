<?
 // ------------------------------------------------------------------------------
 // NiDB api.php
 // Copyright (C) 2004 - 2018
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

	$nologin = true;
	
	require "functions.php";
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
	$instance = GetVariable("instance");
	$dataformat = GetVariable("dataformat");
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
		case 'UploadDICOM': UploadDICOM($uuid, $seriesnotes, $altuids, $anonymize, $dataformat, $numfiles, $equipmentid, $siteid, $projectid, $instanceid, $matchidonly, $transactionid); break;
		case 'getUID': GetUIDFromAltUID($altuid); break;
		case 'getInstanceList': GetInstanceList($u); break;
		case 'getProjectList': GetProjectList($u, $instance); break;
		case 'getSiteList': GetSiteList($u, $instance); break;
		case 'getEquipmentList': GetEquipmentList(); break;
		case 'startTransaction': StartTransaction($u); break;
		case 'endTransaction': EndTransaction($transactionid); break;
		default: echo "Welcome to NiDB v" . $GLOBALS['cfg']['version'];
	}
	
	/* -------------------------------------------- */
	/* ------- Authenticate ----------------------- */
	/* -------------------------------------------- */
	function Authenticate($username, $password) {
		$username = mysqli_real_escape_string($GLOBALS['linki'], $username);
		$password = mysqli_real_escape_string($GLOBALS['linki'], $password);
		
		//if ((AuthenticateUnixUser($username, $password)) && (!$GLOBALS['ispublic'])) {
		//	$sqlstring = "insert into remote_logins (username, ip, login_date, login_result) values ('$username', '" . $_SERVER['REMOTE_ADDR'] . "', now(), 'success')";
		//	$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		//	return true;
		//}
		//else {
			//echo "Not a UNIX account, trying standard account";
			if (AuthenticateStandardUser($username, $password)) {
				$sqlstring = "insert into remote_logins (username, ip, login_date, login_result) values ('$username', '" . $_SERVER['REMOTE_ADDR'] . "', now(), 'success')";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				return true;
			}
			else {
				$sqlstring = "insert into remote_logins (username, ip, login_date, login_result) values ('$username', '" . $_SERVER['REMOTE_ADDR'] . "', now(), 'failure')";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				return false;
			}
		//}
	}


	/* -------------------------------------------- */
	/* ------- AuthenticateStandardUser ----------- */
	/* -------------------------------------------- */
	function AuthenticateStandardUser($username, $password) {
		/* attempt to authenticate a standard user */
		$username = mysqli_real_escape_string($GLOBALS['linki'], $username);
		$password = mysqli_real_escape_string($GLOBALS['linki'], $password);
		
		if ((trim($username) == "") || (trim($password) == ""))
			return false;
			
		$sqlstring = "select user_id from users where (username = '$username' or username = sha1('$username')) and (password = sha1('$password') or password = '$password') and user_enabled = 1";
		//echo "[SQL: $sqlstring]";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0) {
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
		/* attempt to authenticate a unix user */
		$pwent = posix_getpwnam($username);
		$password_hash = $pwent["passwd"];
		//echo "User info for $username: {{$password_hash}}";
		//print_r($pwent);
		//if($pwent == false)
		//	return false;
			
		$autharray = explode(":",`ypmatch $username passwd`);
		if ($autharray[0] != $username) {
			return false;
		}
			
		//echo "<pre>blahablah";
		//print_r($autharray);
		//echo "</pre>";
		
		$cryptpw = crypt($password, $autharray[1]);
		
		if($cryptpw == $autharray[1])
			return true;
		return false;
	}

	
	/* -------------------------------------------- */
	/* ------- StartTransaction ------------------- */
	/* -------------------------------------------- */
	function StartTransaction($u, $source) {
		$sqlstring = "insert into import_transactions (transaction_startdate, transaction_source, transaction_status, transaction_username) values (now(), '$source', 'uploading', '$u')";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$tid = mysqli_insert_id($GLOBALS['linki']);
		echo $tid;
	}

	
	/* -------------------------------------------- */
	/* ------- EndTransaction --------------------- */
	/* -------------------------------------------- */
	function EndTransaction($tid) {
		$sqlstring = "update import_transactions set transaction_enddate = now(), transaction_status = 'uploadcomplete' where importtrans_id = $tid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		echo "Ok";
	}

	
	/* -------------------------------------------- */
	/* ------- GetUIDFromAltUID ------------------- */
	/* -------------------------------------------- */
	function GetUIDFromAltUID($altuid) {
		$altuid = mysqli_real_escape_string($GLOBALS['linki'], $altuid);

		$sqlstring = "select uid from subjects where subject_id in (select subject_id from subject_altuid where altuid = '$altuid')";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$uids[] = $row['uid'];
		}
		if (is_array($uids)) {
			echo implode(',',$uids);
		}
	}


	/* -------------------------------------------- */
	/* ------- GetInstanceList -------------------- */
	/* -------------------------------------------- */
	function GetInstanceList($u) {
		$u = mysqli_real_escape_string($GLOBALS['linki'], $u);

		$sqlstring = "select * from instance where instance_id in (select instance_id from user_instance where user_id = (select user_id from users where username = '$u')) order by instance_name";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$instanceuid = $row['instance_uid'];
			$instancename = $row['instance_name'];
			$instances[] = "$instanceuid|$instancename";
		}
		if (is_array($instances)) {
			echo implode(',',$instances);
		}
	}


	/* -------------------------------------------- */
	/* ------- GetProjectList --------------------- */
	/* -------------------------------------------- */
	function GetProjectList($u, $instance) {
		$u = mysqli_real_escape_string($GLOBALS['linki'], $u);
		$instance = mysqli_real_escape_string($GLOBALS['linki'], $instance);
		
		$sqlstring = "select * from projects a left join user_project b on a.project_id = b.project_id left join users c on b.user_id = c.user_id where c.username = '$u' and a.instance_id = (select instance_id from instance where instance_uid = '$instance') and (b.view_data = 1 or b.view_phi = 1 or b.write_data = 1 or b.write_phi = 1) order by a.project_name";
		//echo "$sqlstring";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$projectuid = $row['project_uid'];
			$projectname = $row['project_name'];
			$projects[] = "$projectuid|$projectname";
		}
		if (is_array($projects)) {
			echo implode(',',$projects);
		}
	}


	/* -------------------------------------------- */
	/* ------- GetSiteList ------------------------ */
	/* -------------------------------------------- */
	function GetSiteList($u, $instance) {
		$u = mysqli_real_escape_string($GLOBALS['linki'], $u);
		$instance = mysqli_real_escape_string($GLOBALS['linki'], $instance);
		
		$sqlstring = "select * from nidb_sites order by site_name";
		//echo "$sqlstring";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$siteuid = $row['site_uid'];
			$sitename = $row['site_name'];
			$sites[] = "$siteuid|$sitename";
		}
		if (is_array($sites)) {
			echo implode(',',$sites);
		}
	}


	/* -------------------------------------------- */
	/* ------- GetEquipmentList ------------------- */
	/* -------------------------------------------- */
	function GetEquipmentList() {
		$sqlstring = "select distinct(study_site) 'equipment' from studies where study_site <> '' order by study_site";
		//echo "$sqlstring";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$equipment = $row['equipment'];
			$sites[] = "$equipment|$equipment";
		}
		if (is_array($sites)) {
			echo implode(',',$sites);
		}
	}
	

	/* -------------------------------------------- */
	/* ------- UploadDICOM ------------------------ */
	/* -------------------------------------------- */
	function UploadDICOM($uuid, $seriesnotes, $altuids, $anonymize, $dataformat, $numfiles, $equipmentid, $siteid, $projectid, $instanceid, $matchidonly, $transactionid) {
		
		//print_r($_POST);
		//echo "\n";
		
		$uuid = mysqli_real_escape_string($GLOBALS['linki'], $uuid);
		$anonymize = mysqli_real_escape_string($GLOBALS['linki'], $anonymize);
		$dataformat = mysqli_real_escape_string($GLOBALS['linki'], $dataformat);
		$equipmentid = mysqli_real_escape_string($GLOBALS['linki'], $equipmentid);
		$siteid = mysqli_real_escape_string($GLOBALS['linki'], $siteid);
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], $projectid);
		$instanceid = mysqli_real_escape_string($GLOBALS['linki'], $instanceid);
		$matchidonly = mysqli_real_escape_string($GLOBALS['linki'], $matchidonly);
		$transactionid = mysqli_real_escape_string($GLOBALS['linki'], $transactionid);
		$seriesnotes = mysqli_real_escape_string($GLOBALS['linki'], $seriesnotes);
		$altuids = mysqli_real_escape_string($GLOBALS['linki'], $altuids);
		$numfiles = mysqli_real_escape_string($GLOBALS['linki'], $numfiles);
		
		$altuidlist = explode(',',$altuids);
		$altuidlist = array_unique($altuidlist);
		$altuids = implode(',',$altuidlist);
		
		/* get the instanceRowID */
		$sqlstring = "select instance_id from instance where instance_id = '$instanceid' or instance_uid = '$instanceid'";
		//echo "[[$sqlstring]]\n";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$instanceRowID = $row['instance_id'];
		if ($instanceRowID == "") {
			echo "ERROR_INVALID_INSTANCEID";
			exit(0);
		}
		
		/* get the projectRowID */
		$sqlstring = "select project_id from projects where project_id = '$projectid' or project_uid = '$projectid'";
		//echo "[[$sqlstring]]\n";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$projectRowID = $row['project_id'];
		if ($projectRowID == "") {
			echo "ERROR_INVALID_PROJECTID";
			exit(0);
		}
		
		/* get the siteRowID */
		$sqlstring = "select site_id from nidb_sites where site_id = '$siteid' or site_uid = '$siteid'";
		//echo "[[$sqlstring]]\n";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$siteRowID = $row['site_id'];
		if ($siteRowID == "") {
			echo "ERROR_INVALID_SITEID";
			exit(0);
		}
		
		/* clear out the older stuff */
		$sqlstring = "DELETE FROM import_received WHERE import_datetime < DATE_SUB(NOW(), INTERVAL 30 DAY)";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		/* check if there is anything in the FILES global variable */
		if (isset($_FILES['files'])){
			/* and check if we received at least 1 file */
			if (count($_FILES['files']) > 0) {
				/* get next import ID */
				$sqlstring = "insert into import_requests (import_transactionid, import_datatype, import_datetime, import_status, import_startdate, import_equipment, import_siteid, import_projectid, import_instanceid, import_uuid, import_seriesnotes, import_altuids, import_anonymize, import_permanent, import_matchidonly) values ('$transactionid', '$dataformat',now(),'uploading',now(),'$equipmentid','$siteRowID','$projectRowID', '$instanceRowID', '$uuid','$seriesnotes','$altuids','$anonymize','$permanent','$matchidonly')";
				//echo "[[$sqlstring]]\n";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				$uploadID = mysqli_insert_id($GLOBALS['linki']);
				
				$numfilessuccess = 0;
				$numfilesfail = 0;
				$numfilestotal = 0;
				$numbehsuccess = 0;
				$numbehfail = 0;
				$numbehtotal = 0;
				$report = "DateTime\tFilenameIn\tFilenameOutPath\tMD5\tFileSize\tStatusMessage\n";
				
				//echo "I'm still here\n";
				$savepath = $GLOBALS['cfg']['uploadeddir'] . "/$uploadID";
				$behsavepath = $GLOBALS['cfg']['uploadeddir'] . "/$uploadID/beh";
		
				/* go through all the files and save them */
				mkdir($savepath, 0777, true);
				chmod($savepath, 0777);
				foreach ($_FILES['files']['name'] as $i => $name) {
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
							//echo date('c') . "\n";
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
					
					/* record this received file in the import_received table */
					//$sqlstring = "insert into import_received (import_transactionid, import_uploadid, import_filename, import_filesize, import_datetime, import_md5, import_success, import_userid, import_instanceid, import_projectid, import_siteid, import_route) values ('$transactionid', '$uploadID', '$name', '$filesize', now(), '$filemd5', $success, '" . $GLOBALS['userid'] . "', '$instanceRowID', '$projectRowID', '$siteRowID', 'api.php-uploaddicom')";
					//echo "$sqlstring\n";
					//$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
				
				/* go through all the beh files and save them */
				if (isset($_FILES['behs'])){ 
					mkdir($behsavepath, 0777, true);
					chmod($behsavepath, 0777);
					foreach ($_FILES['behs']['name'] as $i => $name) {
						$numbehtotal++;
						if (move_uploaded_file($_FILES['behs']['tmp_name'][$i], "$behsavepath/$name")) {
							$numbehsuccess++;
							chmod("$behsavepath/$name", 0777);
							$filemd5 = strtoupper(md5_file("$behsavepath/$name"));
							$md5list[] = $filemd5;
							$filesize = filesize("$behsavepath/$name");
							$success = 1;
							
							$report .= date('c'). "\t$name\t$savepath/$name\t$filemd5\t$filesize\tFile successfully received\n";
						}
						else {
							$numbehfail++;
							$success = 0;

							$report .= date('c'). "\t$name\t$savepath/$name\t$filemd5\t$filesize\tUnable to copy file to output path\n";
						}
						/* record this received file in the import_received table */
						//$sqlstring = "insert into import_received (import_transactionid, import_uploadid, import_filename, import_filesize, import_datetime, import_md5, import_success, import_userid, import_instanceid, import_projectid, import_siteid, import_route) values ('$transactionid', '$uploadID', '$name', '$filesize', now(), '$filemd5', $success, '" . $GLOBALS['userid'] . "', '$instanceRowID', '$projectRowID', '$siteRowID', 'api.php-uploaddicom')";
						//$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					}
				}
				$report = mysqli_real_escape_string($GLOBALS['linki'], $report);
				$sqlstring = "update import_requests set import_status = 'pending', numfilestotal = '$numfilestotal', numfilessuccess = '$numfilessuccess', numfilesfail = '$numfilesfail', numbehtotal = '$numbehtotal', numbehsuccess = '$numbehsuccess', numbehfail = '$numbehfail', uploadreport = '$report' where importrequest_id = $uploadID";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
			else {
				echo "UPLOADERROR";
			}
		}
		echo "SUCCESS," . implode(",",$md5list);
	}
?>