<?
 // ------------------------------------------------------------------------------
 // NiDB api2.php
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

 /* This is the secure (v2) public API for interaction with NiDB.

	Authentication is token based: a client calls the 'Authenticate' action with
	its API username and key (created on the My Account page) and receives a
	short-lived session ID. That session ID must be included with every subsequent
	request. All parameters are read from POST only, and all responses are JSON. */

	define("LEGIT_REQUEST", true);
	$nologin = true; /* authentication is handled here, not by includes_php.php */

	/* ----- session and security settings ----- */
	define("API_SESSION_IDLE_MINUTES", 10);  /* idle minutes before a session expires */
	define("API_SESSION_MAX_PER_USER", 2);   /* max concurrent sessions per API user */
	define("API_MAX_FAILED_LOGINS", 10);     /* max failed logins per IP within the window */
	define("API_FAILED_LOGIN_WINDOW", 15);   /* failed-login window, in minutes */
	define("API_ENFORCE_HTTPS", true);       /* require HTTPS (skipped on the dev server) */
	define("API_ALLOWED_ORIGIN", "");        /* set to a single origin to enable CORS; '' disables */
	define("API_MAX_UPLOAD_BYTES", 512 * 1024 * 1024);

	/* Buffer all output. The required files below can emit trailing whitespace after their
	   closing ?> tag; without buffering that would flush the headers early and prevent us
	   from setting the HTTP status. APIRespond() clears this buffer before writing the JSON. */
	ob_start();

	/* return a generic error for any uncaught exception, without leaking details to the client */
	set_exception_handler(function($e) {
		error_log("NiDB api2 unhandled exception: " . $e->getMessage());
		APIRespond(false, "Internal server error.", null, 500);
	});

	/* ----- security headers ----- */
	header("Content-Type: application/json; charset=UTF-8");
	header("X-Content-Type-Options: nosniff");
	header("X-Frame-Options: DENY");
	header("Referrer-Policy: no-referrer");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Pragma: no-cache");
	if (API_ALLOWED_ORIGIN != "") {
		header("Access-Control-Allow-Origin: " . API_ALLOWED_ORIGIN);
		header("Access-Control-Allow-Methods: POST");
		header("Access-Control-Allow-Headers: Content-Type");
	}

	require "functions.php";
	require "includes_php.php";

	/* ----- require HTTPS (except on the development server, which runs on http/:8080) ----- */
	if (API_ENFORCE_HTTPS && !$isdevserver) {
		$ishttps = (!empty($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] != "off"))
			|| (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && ($_SERVER['HTTP_X_FORWARDED_PROTO'] == "https"))
			|| (isset($_SERVER['SERVER_PORT']) && ((int)$_SERVER['SERVER_PORT'] == 443));
		if (!$ishttps)
			APIRespond(false, "HTTPS is required.", null, 403);
	}

	/* ----- only accept POST (all parameters, including tokens, are read from POST only) ----- */
	if ($_SERVER['REQUEST_METHOD'] == "OPTIONS") {
		http_response_code(204);
		exit(0);
	}
	if ($_SERVER['REQUEST_METHOD'] != "POST")
		APIRespond(false, "Only POST requests are accepted.", null, 405);

	/* ----- route the requested action ----- */
	$action = APIPost("action");
	switch ($action) {
		case 'Authenticate':         HandleAuthenticate(); break;
		case 'StartTransaction':     HandleStartTransaction(); break;
		case 'ImportObject':         HandleImportObject(); break;
		case 'EndTransaction':       HandleEndTransaction(); break;
		case 'GetProjectList':       HandleGetProjectList(); break;
		case 'GetAcceptedObjects':   HandleGetAcceptedObjects(); break;
		case 'GetNidbInfo':          HandleGetNidbInfo(); break;
		case 'GetSubjectList':       HandleGetSubjectList(); break;
		case 'GetUID':               HandleGetUID(); break;
		case 'GetInstanceList':      HandleGetInstanceList(); break;
		case 'GetSiteList':          HandleGetSiteList(); break;
		case 'GetEquipmentList':     HandleGetEquipmentList(); break;
		case 'GetTransactionStatus': HandleGetTransactionStatus(); break;
		case 'GetArchiveStatus':     HandleGetArchiveStatus(); break;
		default:                     APIRespond(false, "Unknown or missing action.", null, 400);
	}


	/* ------------------------------------ handlers ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- HandleAuthenticate ----------------- */
	/* -------------------------------------------- */
	/* Authenticate an API user and open a new session. Reads 'user' and 'token'
	   from POST and, on success, returns a 128-character session ID. */
	function HandleAuthenticate() {
		$username = APIPost("user");
		$apikey   = APIPost("token");
		$ip       = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "";

		if (($username == "") || ($apikey == ""))
			APIRespond(false, "Missing required parameters: user, token.", null, 400);

		/* throttle brute-force attempts from this IP */
		if (APIRecentFailedLogins($ip) >= API_MAX_FAILED_LOGINS)
			APIRespond(false, "Too many failed attempts. Please try again later.", null, 429);

		/* fetch the API user by username */
		$q = mysqli_prepare($GLOBALS['linki'], "select apiuser_id, credential, is_active from api_users where username = ? limit 1");
		mysqli_stmt_bind_param($q, 's', $username);
		$result = MySQLiBoundQuery($q, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($q);

		/* verify in all branches to avoid username enumeration via timing. When the user
		   does not exist we verify against a dummy hash built with the same algorithm the
		   server uses for real credentials, so the key-derivation cost is the same. */
		$hashtocheck = ($row) ? $row['credential'] : APIDummyHash();
		$keyisvalid  = password_verify($apikey, $hashtocheck);

		if ((!$row) || (!$row['is_active']) || (!$keyisvalid)) {
			APILogLogin($username, $ip, 'failure');
			/* single generic message: do not reveal which condition failed */
			APIRespond(false, "Authentication failed.", null, 401);
		}

		$apiuserid = (int)$row['apiuser_id'];

		/* remove idle-expired sessions for this user (lazy expiry) */
		$idle = (int)API_SESSION_IDLE_MINUTES;
		$q = mysqli_prepare($GLOBALS['linki'], "delete from api_sessions where apiuser_id = ? and last_access <= date_sub(now(), interval $idle minute)");
		mysqli_stmt_bind_param($q, 'i', $apiuserid);
		MySQLiBoundQuery($q, __FILE__, __LINE__);
		mysqli_stmt_close($q);

		/* enforce the per-user concurrent session cap */
		$q = mysqli_prepare($GLOBALS['linki'], "select count(*) 'numsessions' from api_sessions where apiuser_id = ?");
		mysqli_stmt_bind_param($q, 'i', $apiuserid);
		$result = MySQLiBoundQuery($q, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($q);
		if ($row['numsessions'] >= API_SESSION_MAX_PER_USER)
			APIRespond(false, "Maximum number of concurrent sessions reached.", null, 429);

		/* create the session, storing only the SHA-256 hash of the token (never the raw ID) */
		$sessionid   = APIGenerateSessionID();
		$sessionhash = hash('sha256', $sessionid);
		$q = mysqli_prepare($GLOBALS['linki'], "insert into api_sessions (apiuser_id, session_hash, ip_address, last_access, created_at) values (?, ?, ?, now(), now())");
		mysqli_stmt_bind_param($q, 'iss', $apiuserid, $sessionhash, $ip);
		MySQLiBoundQuery($q, __FILE__, __LINE__);
		mysqli_stmt_close($q);

		$q = mysqli_prepare($GLOBALS['linki'], "update api_users set login_count = login_count + 1, last_access = now() where apiuser_id = ?");
		mysqli_stmt_bind_param($q, 'i', $apiuserid);
		MySQLiBoundQuery($q, __FILE__, __LINE__);
		mysqli_stmt_close($q);

		APILogLogin($username, $ip, 'success');
		APIRespond(true, "Authenticated.", array("sessionID" => $sessionid));
	}


	/* -------------------------------------------- */
	/* ------- HandleStartTransaction ------------- */
	/* -------------------------------------------- */
	/* Start a new import transaction for the authenticated API user. */
	function HandleStartTransaction() {
		$session = APIRequireSession();
		$username = APIUsername($session['apiuser_id']);

		$q = mysqli_prepare($GLOBALS['linki'], "insert into import_transactions (transaction_startdate, transaction_source, transaction_status, transaction_username) values (now(), 'api2', 'uploading', ?)");
		mysqli_stmt_bind_param($q, 's', $username);
		MySQLiBoundQuery($q, __FILE__, __LINE__);
		$transactionid = mysqli_stmt_insert_id($q);
		mysqli_stmt_close($q);

		APIRespond(true, "Transaction started.", array("transactionID" => (int)$transactionid));
	}


	/* -------------------------------------------- */
	/* ------- HandleEndTransaction --------------- */
	/* -------------------------------------------- */
	/* Mark a transaction (started by this API user) as complete. */
	function HandleEndTransaction() {
		$session = APIRequireSession();
		$transactionid = APIRequireIntParam("transactionID");
		$username = APIUsername($session['apiuser_id']);

		$q = mysqli_prepare($GLOBALS['linki'], "update import_transactions set transaction_enddate = now(), transaction_status = 'uploadcomplete' where importtrans_id = ? and transaction_username = ?");
		mysqli_stmt_bind_param($q, 'is', $transactionid, $username);
		MySQLiBoundQuery($q, __FILE__, __LINE__);
		$affected = mysqli_stmt_affected_rows($q);
		mysqli_stmt_close($q);

		/* 0 rows means the transaction does not exist or belongs to a different user */
		if ($affected < 1)
			APIRespond(false, "Failed to end transaction.", null, 500);

		APIRespond(true, "Transaction ended.", array("transactionID" => $transactionid));
	}


	/* -------------------------------------------- */
	/* ------- HandleImportObject ----------------- */
	/* -------------------------------------------- */
	/* Import a squirrel (.sqrl) object file. The file is validated (extension, size,
	   MIME type) before any processing occurs. */
	function HandleImportObject() {
		APIRequireSession();

		if (empty($_FILES['squirrelObject']))
			APIRespond(false, "Missing required file: squirrelObject.", null, 400);

		$file = $_FILES['squirrelObject'];

		if ($file['error'] != UPLOAD_ERR_OK)
			APIRespond(false, "File upload error: " . APIUploadErrorMessage($file['error']), null, 400);

		/* only .sqrl files are accepted. The client-supplied name is used for validation
		   only and is never used as a filesystem path. */
		$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		if ($ext != "sqrl")
			APIRespond(false, "Invalid file type. Only .sqrl files are accepted.", null, 415);

		if ($file['size'] > API_MAX_UPLOAD_BYTES)
			APIRespond(false, "Uploaded file exceeds the maximum allowed size.", null, 400);

		$allowedmime = array("application/octet-stream", "application/zip", "application/gzip");
		$detectedmime = mime_content_type($file['tmp_name']);
		if (!in_array($detectedmime, $allowedmime))
			APIRespond(false, "File type not permitted.", null, 415);

		/* TODO: process the uploaded squirrel object and return per-object results */
		$results = array();

		APIRespond(true, "Objects received.", array("results" => $results));
	}


	/* -------------------------------------------- */
	/* ------- HandleGetProjectList --------------- */
	/* -------------------------------------------- */
	/* Return the projects belonging to an instance. */
	function HandleGetProjectList() {
		APIRequireSession();
		$instanceid = APIRequireIntParam("instanceID");

		$q = mysqli_prepare($GLOBALS['linki'], "select project_id, project_name from projects where instance_id = ? order by project_name");
		mysqli_stmt_bind_param($q, 'i', $instanceid);
		$result = MySQLiBoundQuery($q, __FILE__, __LINE__);
		$projects = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$projects[] = array("projectID" => (int)$row['project_id'], "projectName" => $row['project_name']);
		}
		mysqli_stmt_close($q);

		APIRespond(true, "Project list retrieved.", array("projects" => $projects));
	}


	/* -------------------------------------------- */
	/* ------- HandleGetSubjectList --------------- */
	/* -------------------------------------------- */
	/* Return the active subjects enrolled in a project. */
	function HandleGetSubjectList() {
		APIRequireSession();
		$projectid = APIRequireIntParam("projectID");

		$q = mysqli_prepare($GLOBALS['linki'], "select distinct s.subject_id, s.uid from subjects s join enrollment e on e.subject_id = s.subject_id where e.project_id = ? and s.isactive = 1 order by s.uid");
		mysqli_stmt_bind_param($q, 'i', $projectid);
		$result = MySQLiBoundQuery($q, __FILE__, __LINE__);
		$subjects = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$subjects[] = array("subjectID" => (int)$row['subject_id'], "subjectUID" => $row['uid']);
		}
		mysqli_stmt_close($q);

		APIRespond(true, "Subject list retrieved.", array("subjects" => $subjects));
	}


	/* -------------------------------------------- */
	/* ------- HandleGetUID ----------------------- */
	/* -------------------------------------------- */
	/* Return the NiDB UID(s) associated with an alternate UID. */
	function HandleGetUID() {
		APIRequireSession();
		$altuid = APIPost("altUID");
		if ($altuid == "")
			APIRespond(false, "Missing required parameter: altUID.", null, 400);

		$q = mysqli_prepare($GLOBALS['linki'], "select s.uid from subjects s join subject_altuid a on a.subject_id = s.subject_id where a.altuid = ?");
		mysqli_stmt_bind_param($q, 's', $altuid);
		$result = MySQLiBoundQuery($q, __FILE__, __LINE__);
		$uids = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$uids[] = $row['uid'];
		}
		mysqli_stmt_close($q);

		APIRespond(true, "UID lookup complete.", array("uids" => $uids));
	}


	/* -------------------------------------------- */
	/* ------- HandleGetInstanceList -------------- */
	/* -------------------------------------------- */
	/* Return all instances known to this NiDB server. */
	function HandleGetInstanceList() {
		APIRequireSession();

		$sqlstring = "select instance_id, instance_uid, instance_name from instance order by instance_name";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$instances = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$instances[] = array("instanceID" => (int)$row['instance_id'], "instanceUID" => $row['instance_uid'], "instanceName" => $row['instance_name']);
		}

		APIRespond(true, "Instance list retrieved.", array("instances" => $instances));
	}


	/* -------------------------------------------- */
	/* ------- HandleGetSiteList ------------------ */
	/* -------------------------------------------- */
	/* Return all sites configured on this NiDB server. */
	function HandleGetSiteList() {
		APIRequireSession();

		$sqlstring = "select site_id, site_name from nidb_sites order by site_name";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$sites = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$sites[] = array("siteID" => (int)$row['site_id'], "siteName" => $row['site_name']);
		}

		APIRespond(true, "Site list retrieved.", array("sites" => $sites));
	}


	/* -------------------------------------------- */
	/* ------- HandleGetEquipmentList ------------- */
	/* -------------------------------------------- */
	/* Return the distinct list of equipment (study sites) seen in studies. */
	function HandleGetEquipmentList() {
		APIRequireSession();

		$sqlstring = "select distinct(study_site) 'equipment' from studies where study_site <> '' order by study_site";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$equipment = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$equipment[] = $row['equipment'];
		}

		APIRespond(true, "Equipment list retrieved.", array("equipment" => $equipment));
	}


	/* -------------------------------------------- */
	/* ------- HandleGetTransactionStatus --------- */
	/* -------------------------------------------- */
	/* Return the import-request rows for a transaction. */
	function HandleGetTransactionStatus() {
		APIRequireSession();
		$transactionid = APIRequireIntParam("transactionID");

		$q = mysqli_prepare($GLOBALS['linki'], "select a.*, b.project_name, c.site_name, d.instance_name from import_requests a left join projects b on a.import_projectid = b.project_id left join nidb_sites c on a.import_siteid = c.site_id left join instance d on a.import_instanceid = d.instance_id where a.import_transactionid = ? order by a.import_datetime desc");
		mysqli_stmt_bind_param($q, 'i', $transactionid);
		$result = MySQLiBoundQuery($q, __FILE__, __LINE__);
		$requests = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$requests[] = $row;
		}
		mysqli_stmt_close($q);

		APIRespond(true, "Transaction status retrieved.", array("requests" => $requests));
	}


	/* -------------------------------------------- */
	/* ------- HandleGetArchiveStatus ------------- */
	/* -------------------------------------------- */
	/* Return per-series archive status (from importlogs) for a transaction. */
	function HandleGetArchiveStatus() {
		APIRequireSession();
		$transactionid = APIRequireIntParam("transactionID");

		/* collect the import-request IDs that belong to this transaction */
		$q = mysqli_prepare($GLOBALS['linki'], "select importrequest_id from import_requests where import_transactionid = ?");
		mysqli_stmt_bind_param($q, 'i', $transactionid);
		$result = MySQLiBoundQuery($q, __FILE__, __LINE__);
		$groupids = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$groupids[] = (int)$row['importrequest_id'];
		}
		mysqli_stmt_close($q);

		if (count($groupids) < 1)
			APIRespond(true, "Archive status retrieved.", array("series" => array()));

		/* these are integer IDs read straight from the database, so they are safe to inline */
		$grouplist = implode(",", $groupids);
		$sqlstring = "select *, timediff(max(importstartdate), min(importstartdate)) 'importtime', date_format(max(importstartdate), '%b %e, %Y %T') 'maximportdatetime', date_format(studydatetime_orig, '%b %e, %Y %T') 'studydatetime', date_format(seriesdatetime_orig, '%b %e, %Y %T') 'seriesdatetime', count(*) 'numfiles' from importlogs where importgroupid in ($grouplist) group by stationname_orig, studydatetime_orig, seriesnumber_orig order by studydatetime_orig desc, seriesdatetime_orig";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$series = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$series[] = $row;
		}

		APIRespond(true, "Archive status retrieved.", array("series" => $series));
	}


	/* -------------------------------------------- */
	/* ------- HandleGetNidbInfo ------------------ */
	/* -------------------------------------------- */
	/* Return version information for this NiDB instance. */
	function HandleGetNidbInfo() {
		APIRequireSession();

		$version = isset($GLOBALS['cfg']['version']) ? $GLOBALS['cfg']['version'] : "";

		APIRespond(true, "NiDB info retrieved.", array("NiDBVersion" => $version));
	}


	/* -------------------------------------------- */
	/* ------- HandleGetAcceptedObjects ----------- */
	/* -------------------------------------------- */
	/* Return the object types this server accepts. */
	function HandleGetAcceptedObjects() {
		APIRequireSession();

		/* TODO: return the object types this server accepts */
		$objecttypes = array();

		APIRespond(true, "Accepted object types retrieved.", array("objectTypes" => $objecttypes));
	}


	/* ------------------------------------ helpers ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- APIRequireSession ------------------ */
	/* -------------------------------------------- */
	/* Validate the sessionID from POST and slide its idle window forward. Returns the
	   session on success; emits a generic 401 and exits on any failure. */
	function APIRequireSession() {
		$sessionid = APIPost("sessionID");

		if ($sessionid == "")
			APIRespond(false, "Missing required parameter: sessionID.", null, 401);

		/* reject anything that cannot be a valid session ID before touching the database */
		if (!preg_match('/^[A-Za-z0-9]{128}$/', $sessionid))
			APIRespond(false, "Invalid or expired session.", null, 401);

		$sessionhash = hash('sha256', $sessionid);
		$idle = (int)API_SESSION_IDLE_MINUTES;

		$q = mysqli_prepare($GLOBALS['linki'], "select s.session_id, s.apiuser_id from api_sessions s join api_users u on u.apiuser_id = s.apiuser_id where s.session_hash = ? and u.is_active = 1 and s.last_access > date_sub(now(), interval $idle minute) limit 1");
		mysqli_stmt_bind_param($q, 's', $sessionhash);
		$result = MySQLiBoundQuery($q, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($q);

		/* single message for all failure modes: do not reveal whether the session existed,
		   has expired, or the user is disabled */
		if (!$row)
			APIRespond(false, "Invalid or expired session.", null, 401);

		/* slide the idle window forward on every authenticated request */
		$sessionrowid = (int)$row['session_id'];
		$q = mysqli_prepare($GLOBALS['linki'], "update api_sessions set last_access = now() where session_id = ?");
		mysqli_stmt_bind_param($q, 'i', $sessionrowid);
		MySQLiBoundQuery($q, __FILE__, __LINE__);
		mysqli_stmt_close($q);

		return array("sessionID" => $sessionid, "apiuser_id" => (int)$row['apiuser_id']);
	}


	/* -------------------------------------------- */
	/* ------- APIUsername ------------------------ */
	/* -------------------------------------------- */
	/* Return the username for an API user id (empty string if not found). */
	function APIUsername($apiuserid) {
		$apiuserid = (int)$apiuserid;
		$q = mysqli_prepare($GLOBALS['linki'], "select username from api_users where apiuser_id = ? limit 1");
		mysqli_stmt_bind_param($q, 'i', $apiuserid);
		$result = MySQLiBoundQuery($q, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($q);
		return ($row) ? $row['username'] : "";
	}


	/* -------------------------------------------- */
	/* ------- APIRequireIntParam ----------------- */
	/* -------------------------------------------- */
	/* Read a required, strictly-numeric POST parameter and return it as an int.
	   Emits a 400 and exits if it is missing or not all digits. */
	function APIRequireIntParam($key) {
		$val = APIPost($key);
		if ($val == "")
			APIRespond(false, "Missing required parameter: $key.", null, 400);
		if (!ctype_digit($val))
			APIRespond(false, "Invalid $key.", null, 400);
		return (int)$val;
	}


	/* -------------------------------------------- */
	/* ------- APIRecentFailedLogins -------------- */
	/* -------------------------------------------- */
	/* Count failed API logins from an IP within the configured window (brute-force throttle). */
	function APIRecentFailedLogins($ip) {
		$window = (int)API_FAILED_LOGIN_WINDOW;
		$q = mysqli_prepare($GLOBALS['linki'], "select count(*) 'n' from remote_logins where ip = ? and login_result = 'failure' and login_date > date_sub(now(), interval $window minute)");
		mysqli_stmt_bind_param($q, 's', $ip);
		$result = MySQLiBoundQuery($q, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($q);
		return (int)$row['n'];
	}


	/* -------------------------------------------- */
	/* ------- APILogLogin ------------------------ */
	/* -------------------------------------------- */
	/* Record an API authentication attempt (mirrors api.php's remote_logins auditing). */
	function APILogLogin($username, $ip, $loginresult) {
		$q = mysqli_prepare($GLOBALS['linki'], "insert into remote_logins (username, ip, login_date, login_result) values (?, ?, now(), ?)");
		mysqli_stmt_bind_param($q, 'sss', $username, $ip, $loginresult);
		MySQLiBoundQuery($q, __FILE__, __LINE__);
		mysqli_stmt_close($q);
	}


	/* -------------------------------------------- */
	/* ------- APIDummyHash ----------------------- */
	/* -------------------------------------------- */
	/* Build a throwaway password hash using the same algorithm the server uses for real
	   credentials (see RegenerateApiKey in users.php): argon2id where available, otherwise
	   bcrypt. Verifying a failed login against this performs the same key-derivation work
	   as a real one, so an unknown username cannot be distinguished by response time. */
	function APIDummyHash() {
		static $hash = null;
		if ($hash === null) {
			$algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : (defined('PASSWORD_ARGON2I') ? PASSWORD_ARGON2I : PASSWORD_BCRYPT);
			$hash = password_hash("invalid-placeholder-key", $algo);
		}
		return $hash;
	}


	/* -------------------------------------------- */
	/* ------- APIGenerateSessionID --------------- */
	/* -------------------------------------------- */
	/* Generate a 128-character (512-bit) cryptographically-random session ID. */
	function APIGenerateSessionID() {
		return bin2hex(random_bytes(64));
	}


	/* -------------------------------------------- */
	/* ------- APIPost ---------------------------- */
	/* -------------------------------------------- */
	/* Read a parameter from POST only (never GET), trimmed. Reading POST-only keeps
	   secrets such as tokens and session IDs out of URLs, query strings, and access logs. */
	function APIPost($key) {
		return isset($_POST[$key]) ? trim((string)$_POST[$key]) : "";
	}


	/* -------------------------------------------- */
	/* ------- APIUploadErrorMessage -------------- */
	/* -------------------------------------------- */
	/* Map a PHP upload error code to a safe, human-readable message (no internal paths). */
	function APIUploadErrorMessage($code) {
		switch ($code) {
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:  return "File exceeds the maximum allowed size.";
			case UPLOAD_ERR_PARTIAL:    return "File was only partially uploaded.";
			case UPLOAD_ERR_NO_FILE:    return "No file was uploaded.";
			case UPLOAD_ERR_NO_TMP_DIR: return "Temporary directory unavailable.";
			case UPLOAD_ERR_CANT_WRITE: return "Failed to write file to disk.";
			case UPLOAD_ERR_EXTENSION:  return "Upload blocked by server extension.";
			default:                    return "Unknown upload error.";
		}
	}


	/* -------------------------------------------- */
	/* ------- APIRespond ------------------------- */
	/* -------------------------------------------- */
	/* Emit a JSON response and terminate. */
	function APIRespond($success, $message, $data = null, $httpcode = 200) {
		/* discard any buffered output (e.g. stray whitespace from required files) so the
		   response body is exactly the JSON and the HTTP status can still be set */
		while (ob_get_level() > 0) { ob_end_clean(); }
		http_response_code($httpcode);

		$body = array("success" => (bool)$success, "message" => $message);
		if ($data !== null)
			$body['data'] = $data;

		$json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if ($json === false) {
			http_response_code(500);
			echo '{"success":false,"message":"Internal server error."}';
			exit(0);
		}

		echo $json;
		exit(0);
	}
?>
