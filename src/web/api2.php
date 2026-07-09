<?php
/**
 * @file    api.php
 * @brief   NiDB REST API entry point.
 *
 * Accepts POST requests with an \c action parameter.
 * All responses are JSON.
 *
 * Actions: Authenticate, StartTransaction, ImportObject,
 *          EndTransaction, GetProjectList, GetAcceptedObjects,
 *          GetNidbInfo, GetSubjectList
 */

declare(strict_types=1);

/// Idle timeout (minutes) before an inactive session is expired.
define('SESSION_IDLE_MINUTES', 10);

/// Maximum number of simultaneous sessions allowed per user.
define('SESSION_MAX_PER_USER',  2);

// ─── Global exception handler ─────────────────────────────────────────────────
// Catches any unhandled exception and returns a generic 500 without leaking
// stack traces, file paths, or query strings to the client.
set_exception_handler(function (Throwable $e) {
    error_log('NiDB API unhandled exception: ' . $e->getMessage());
    http_response_code(500);
    echo '{"success":false,"message":"Internal server error."}';
    exit;
});

// ─── Security headers ────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

/// Restrict to a single allowed origin. Set to '' to disable CORS headers.
define('ALLOWED_ORIGIN', '');   // e.g. 'https://your-frontend.example.com'
if (ALLOWED_ORIGIN !== '') {
    header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
    header('Access-Control-Allow-Methods: POST');
    header('Access-Control-Allow-Headers: Content-Type');
}

/// Deployment environment: 'production' enforces HTTPS; 'development' does not.
define('ENV', 'production');

// ─── HTTPS enforcement ────────────────────────────────────────────────────────
if (ENV === 'production') {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

    if (!$isHttps) {
        respond(false, 'HTTPS is required.', null, 403);
    }
}

// ─── Method enforcement ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Only POST requests are accepted.', null, 405);
}

// ─── Rate limiting (skeleton) ─────────────────────────────────────────────────
// TODO: implement a real rate limiter backed by a cache (e.g. Redis / APCu).
// checkRateLimit($_SERVER['REMOTE_ADDR']);

// ─── Parse and validate the action ───────────────────────────────────────────
$action = trimmedPost('action');
if ($action === '') {
    respond(false, 'Missing required parameter: action.', null, 400);
}

$allowedActions = [
    'Authenticate',
    'StartTransaction',
    'ImportObject',
    'EndTransaction',
    'GetProjectList',
    'GetAcceptedObjects',
    'GetNidbInfo',
    'GetSubjectList',
    'GetUID',
    'GetInstanceList',
    'GetSiteList',
    'GetEquipmentList',
    'GetTransactionStatus',
    'GetArchiveStatus',
];

if (!in_array($action, $allowedActions, true)) {
    respond(false, 'Unknown action.', null, 400);
}

// ─── Route ────────────────────────────────────────────────────────────────────
switch ($action) {
    case 'Authenticate':       handleAuthenticate();       break;
    case 'StartTransaction':   handleStartTransaction();   break;
    case 'ImportObject':       handleImportObject();       break;
    case 'EndTransaction':     handleEndTransaction();     break;
    case 'GetProjectList':     handleGetProjectList();     break;
    case 'GetAcceptedObjects': handleGetAcceptedObjects(); break;
    case 'GetNidbInfo':        handleGetNidbInfo();        break;
    case 'GetSubjectList':     handleGetSubjectList();     break;
    case 'GetUID':             handleGetUID();             break;
    case 'GetInstanceList':    handleGetInstanceList();    break;
    case 'GetSiteList':        handleGetSiteList();        break;
    case 'GetEquipmentList':   handleGetEquipmentList();   break;
    case 'GetTransactionStatus': handleGetTransactionStatus(); break;
    case 'GetArchiveStatus':   handleGetArchiveStatus();   break;
}


// ═════════════════════════════════════════════════════════════════════════════
//  Handlers
// ═════════════════════════════════════════════════════════════════════════════

/**
 * @brief Authenticates a user and opens a new session.
 *
 * Reads \c user and \c token from POST. On success, returns a 128-character
 * alphanumeric session ID that the client must include in every subsequent
 * request.
 *
 * Authentication flow:
 *  -# Look up the user row by username.
 *  -# Run password_verify() against the stored argon2id hash.
 *     The verify call always executes — even for unknown usernames — to
 *     prevent timing-based username enumeration.
 *  -# Delete stale sessions for this user (lazy expiry).
 *  -# Reject if the active session count is at SESSION_MAX_PER_USER.
 *  -# Generate a session ID with random_int() and store its SHA-256 hash;
 *     the raw ID is never written to the database.
 *  -# Return the raw session ID to the client (single and only exposure).
 *
 * @post action=Authenticate, user, token
 * @return void  Emits JSON { sessionID } on success or a 401/429 on failure.
 */
function handleAuthenticate(): void
{
    $username = trimmedPost('user');
    $apiKey   = trimmedPost('token');

    if ($username === '' || $apiKey === '') {
        respond(false, 'Missing required parameters: user, token.', null, 400);
    }

    $db = getDb();

    // Always fetch by username using a prepared statement.
    $stmt = $db->prepare(
        'SELECT apiuser_id, credential, is_active
           FROM api_users
          WHERE username = ?
          LIMIT 1'
    );
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Run password_verify() in all branches to prevent username-enumeration via
    // timing. When the user doesn't exist we verify against a throwaway hash built
    // with the SAME algorithm the server actually uses for credentials — argon2id
    // where available (PHP 7.3+/8 with libargon2), otherwise bcrypt. This mirrors
    // RegenerateApiKey() in users.php, so password_verify() performs a full KDF in
    // both branches (a hardcoded argon2id dummy would return instantly on a PHP
    // build without argon2 support, leaking whether the username exists).
    static $dummyHash = null;
    if ($dummyHash === null) {
        $dummyAlgo = defined('PASSWORD_ARGON2ID')
            ? PASSWORD_ARGON2ID
            : (defined('PASSWORD_ARGON2I') ? PASSWORD_ARGON2I : PASSWORD_BCRYPT);
        $dummyHash = password_hash('invalid-placeholder-key', $dummyAlgo);
    }
    $hashToCheck = ($user !== false) ? $user['credential'] : $dummyHash;
    $keyIsValid  = password_verify($apiKey, $hashToCheck);

    if ($user === false || !(bool)$user['is_active'] || !$keyIsValid) {
        // Single generic message — do not reveal which condition failed.
        respond(false, 'Authentication failed.', null, 401);
    }

    $userId = (int)$user['apiuser_id'];

    // Remove sessions that have already exceeded the idle timeout.
    $db->prepare(
        'DELETE FROM api_sessions
          WHERE apiuser_id = ?
            AND last_access <= DATE_SUB(NOW(), INTERVAL ' . SESSION_IDLE_MINUTES . ' MINUTE)'
    )->execute([$userId]);

    // Enforce the per-user session cap.
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM api_sessions WHERE apiuser_id = ?'
    );
    $stmt->execute([$userId]);
    $activeSessions = (int)$stmt->fetchColumn();

    if ($activeSessions >= SESSION_MAX_PER_USER) {
        respond(false, 'Maximum number of concurrent sessions reached.', null, 429);
    }

    // Generate a 128-character alphanumeric session ID.
    $sessionId   = generateSessionId();
    $sessionHash = hash('sha256', $sessionId);
    $ipAddress   = $_SERVER['REMOTE_ADDR'] ?? null;

    $db->prepare(
        'INSERT INTO api_sessions (apiuser_id, session_hash, ip_address, last_access, created_at)
         VALUES (?, ?, ?, NOW(), NOW())'
    )->execute([$userId, $sessionHash, $ipAddress]);

    $db->prepare(
        'UPDATE api_users
            SET login_count = login_count + 1,
                last_access = NOW()
          WHERE apiuser_id = ?'
    )->execute([$userId]);

    respond(true, 'Authenticated.', ['sessionID' => $sessionId]);
}

/**
 * @brief Starts a new transaction for the authenticated session.
 *
 * @post action=StartTransaction, sessionID
 * @return void  Emits JSON { transactionID } on success.
 */
function handleStartTransaction(): void
{
    $session = requireSession();

    $db = getDb();

    // Record the API user's username on the transaction for traceability and so
    // EndTransaction can confirm ownership.
    $stmt = $db->prepare('SELECT username FROM api_users WHERE apiuser_id = ? LIMIT 1');
    $stmt->execute([$session['apiuser_id']]);
    $username = (string)$stmt->fetchColumn();

    $stmt = $db->prepare(
        "INSERT INTO import_transactions
             (transaction_startdate, transaction_source, transaction_status, transaction_username)
         VALUES (NOW(), 'api2', 'uploading', ?)"
    );
    $stmt->execute([$username]);

    $transactionID = (int)$db->lastInsertId();

    respond(true, 'Transaction started.', ['transactionID' => $transactionID]);
}

/**
 * @brief Imports a .sqrl object file into NiDB.
 *
 * Accepts a multipart file upload named \c squirrelObject. The file must
 * have a \c .sqrl extension, be within the size limit, and pass MIME
 * type validation before any processing occurs.
 *
 * @post action=ImportObject, sessionID, squirrelObject (FILE)
 * @return void  Emits JSON { results[] } with per-object success/failure.
 */
function handleImportObject(): void
{
    $session = requireSession();

    if (empty($_FILES['squirrelObject'])) {
        respond(false, 'Missing required file: squirrelObject.', null, 400);
    }

    $file = $_FILES['squirrelObject'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        respond(false, 'File upload error: ' . uploadErrorMessage($file['error']), null, 400);
    }

    // Validate file extension — only .sqrl files are accepted.
    // strtolower normalises the extension; pathinfo operates on the client-supplied
    // name, which is used purely for validation and never used as a file path.
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'sqrl') {
        respond(false, 'Invalid file type. Only .sqrl files are accepted.', null, 415);
    }

    // Enforce a maximum upload size (adjust as needed).
    $maxBytes = 512 * 1024 * 1024; // 512 MB
    if ($file['size'] > $maxBytes) {
        respond(false, 'Uploaded file exceeds the maximum allowed size.', null, 400);
    }

    // Validate MIME type against an allowlist.
    // TODO: refine the allowlist for the squirrel object format.
    $allowedMimeTypes = [
        'application/octet-stream',
        'application/zip',
        'application/gzip',
    ];
    $detectedMime = mime_content_type($file['tmp_name']);
    if (!in_array($detectedMime, $allowedMimeTypes, true)) {
        respond(false, 'File type not permitted.', null, 415);
    }

    // TODO: process the uploaded file and return per-object results.
    $results = [];

    respond(true, 'Objects received.', ['results' => $results]);
}

/**
 * @brief Ends (marks complete) a transaction started by this session's API user.
 *
 * @post action=EndTransaction, sessionID, transactionID
 * @return void  Emits a success or 500 failure response.
 */
function handleEndTransaction(): void
{
    $session = requireSession();

    $transactionID = trimmedPost('transactionID');
    if ($transactionID === '') {
        respond(false, 'Missing required parameter: transactionID.', null, 400);
    }
    if (!ctype_digit($transactionID)) {
        respond(false, 'Invalid transactionID.', null, 400);
    }
    $transactionID = (int)$transactionID;

    $db = getDb();

    // Look up this API user's username so we only end transactions they own.
    $stmt = $db->prepare('SELECT username FROM api_users WHERE apiuser_id = ? LIMIT 1');
    $stmt->execute([$session['apiuser_id']]);
    $username = (string)$stmt->fetchColumn();

    $stmt = $db->prepare(
        "UPDATE import_transactions
            SET transaction_enddate = NOW(), transaction_status = 'uploadcomplete'
          WHERE importtrans_id = ? AND transaction_username = ?"
    );
    $stmt->execute([$transactionID, $username]);

    // rowCount() is 0 when the transaction does not exist or belongs to someone
    // else; a single generic failure avoids revealing which.
    if ($stmt->rowCount() === 0) {
        respond(false, 'Failed to end transaction.', null, 500);
    }

    respond(true, 'Transaction ended.', ['transactionID' => $transactionID]);
}

/**
 * @brief Returns the list of projects available for a given instance.
 *
 * @post action=GetProjectList, sessionID, instanceID
 * @return void  Emits JSON { projects: [ { projectID, projectName }, ... ] }.
 */
function handleGetProjectList(): void
{
    $session = requireSession();

    $instanceID = trimmedPost('instanceID');
    if ($instanceID === '') {
        respond(false, 'Missing required parameter: instanceID.', null, 400);
    }
    if (!ctype_digit($instanceID)) {
        respond(false, 'Invalid instanceID.', null, 400);
    }
    $instanceID = (int)$instanceID;

    $db   = getDb();
    $stmt = $db->prepare(
        'SELECT project_id, project_name
           FROM projects
          WHERE instance_id = ?
          ORDER BY project_name'
    );
    $stmt->execute([$instanceID]);

    $projects = [];
    foreach ($stmt->fetchAll() as $row) {
        $projects[] = [
            'projectID'   => (int)$row['project_id'],
            'projectName' => $row['project_name'],
        ];
    }

    respond(true, 'Project list retrieved.', ['projects' => $projects]);
}

/**
 * @brief Returns the list of object types accepted by this NiDB instance.
 *
 * @post action=GetAcceptedObjects, sessionID
 * @return void  Emits JSON { objectTypes: [ ... ] }.
 */
function handleGetAcceptedObjects(): void
{
    $session = requireSession();

    // TODO: return the object types this server accepts.
    $objectTypes = [];

    respond(true, 'Accepted object types retrieved.', ['objectTypes' => $objectTypes]);
}

/**
 * @brief Returns version information for this NiDB instance.
 *
 * @post action=GetNidbInfo, sessionID
 * @return void  Emits JSON { NiDBVersion }.
 */
function handleGetNidbInfo(): void
{
    requireSession();

    // requireSession() -> getDb() has already loaded $GLOBALS['cfg'].
    $nidbVersion = (string)($GLOBALS['cfg']['version'] ?? '');

    respond(true, 'NiDB info retrieved.', ['NiDBVersion' => $nidbVersion]);
}

/**
 * @brief Returns the list of subjects belonging to a given project.
 *
 * @post action=GetSubjectList, sessionID, projectID
 * @return void  Emits JSON { subjects: [ { subjectID, subjectUID }, ... ] }.
 */
function handleGetSubjectList(): void
{
    $session = requireSession();

    $projectID = trimmedPost('projectID');
    if ($projectID === '') {
        respond(false, 'Missing required parameter: projectID.', null, 400);
    }
    if (!ctype_digit($projectID)) {
        respond(false, 'Invalid projectID.', null, 400);
    }
    $projectID = (int)$projectID;

    $db   = getDb();
    $stmt = $db->prepare(
        'SELECT DISTINCT s.subject_id, s.uid
           FROM subjects   s
           JOIN enrollment e ON e.subject_id = s.subject_id
          WHERE e.project_id = ?
            AND s.isactive   = 1
          ORDER BY s.uid'
    );
    $stmt->execute([$projectID]);

    $subjects = [];
    foreach ($stmt->fetchAll() as $row) {
        $subjects[] = [
            'subjectID'  => (int)$row['subject_id'],
            'subjectUID' => $row['uid'],
        ];
    }

    respond(true, 'Subject list retrieved.', ['subjects' => $subjects]);
}

/**
 * @brief Looks up NiDB UID(s) for a given alternate UID.
 *
 * @post action=GetUID, sessionID, altUID
 * @return void  Emits JSON { uids: [ ... ] }.
 */
function handleGetUID(): void
{
    requireSession();

    $altUID = trimmedPost('altUID');
    if ($altUID === '') {
        respond(false, 'Missing required parameter: altUID.', null, 400);
    }

    $db   = getDb();
    $stmt = $db->prepare(
        'SELECT s.uid
           FROM subjects       s
           JOIN subject_altuid a ON a.subject_id = s.subject_id
          WHERE a.altuid = ?'
    );
    $stmt->execute([$altUID]);

    $uids = array_column($stmt->fetchAll(), 'uid');

    respond(true, 'UID lookup complete.', ['uids' => $uids]);
}

/**
 * @brief Returns the list of instances known to this NiDB server.
 *
 * API users are not scoped per instance, so every instance is returned.
 *
 * @post action=GetInstanceList, sessionID
 * @return void  Emits JSON { instances: [ { instanceID, instanceUID, instanceName }, ... ] }.
 */
function handleGetInstanceList(): void
{
    requireSession();

    $db   = getDb();
    $stmt = $db->query(
        'SELECT instance_id, instance_uid, instance_name
           FROM instance
          ORDER BY instance_name'
    );

    $instances = [];
    foreach ($stmt->fetchAll() as $row) {
        $instances[] = [
            'instanceID'   => (int)$row['instance_id'],
            'instanceUID'  => $row['instance_uid'],
            'instanceName' => $row['instance_name'],
        ];
    }

    respond(true, 'Instance list retrieved.', ['instances' => $instances]);
}

/**
 * @brief Returns the list of sites configured on this NiDB server.
 *
 * @post action=GetSiteList, sessionID
 * @return void  Emits JSON { sites: [ { siteID, siteName }, ... ] }.
 */
function handleGetSiteList(): void
{
    requireSession();

    $db   = getDb();
    $stmt = $db->query(
        'SELECT site_id, site_name
           FROM nidb_sites
          ORDER BY site_name'
    );

    $sites = [];
    foreach ($stmt->fetchAll() as $row) {
        $sites[] = [
            'siteID'   => (int)$row['site_id'],
            'siteName' => $row['site_name'],
        ];
    }

    respond(true, 'Site list retrieved.', ['sites' => $sites]);
}

/**
 * @brief Returns the distinct list of equipment (study sites) seen in studies.
 *
 * @post action=GetEquipmentList, sessionID
 * @return void  Emits JSON { equipment: [ ... ] }.
 */
function handleGetEquipmentList(): void
{
    requireSession();

    $db   = getDb();
    $stmt = $db->query(
        "SELECT DISTINCT study_site
           FROM studies
          WHERE study_site <> ''
          ORDER BY study_site"
    );

    $equipment = array_column($stmt->fetchAll(), 'study_site');

    respond(true, 'Equipment list retrieved.', ['equipment' => $equipment]);
}

/**
 * @brief Returns the import-request rows for a transaction.
 *
 * @post action=GetTransactionStatus, sessionID, transactionID
 * @return void  Emits JSON { requests: [ ... ] }.
 */
function handleGetTransactionStatus(): void
{
    requireSession();

    $transactionID = trimmedPost('transactionID');
    if ($transactionID === '') {
        respond(false, 'Missing required parameter: transactionID.', null, 400);
    }
    if (!ctype_digit($transactionID)) {
        respond(false, 'Invalid transactionID.', null, 400);
    }
    $transactionID = (int)$transactionID;

    $db   = getDb();
    $stmt = $db->prepare(
        'SELECT a.*, b.project_name, c.site_name, d.instance_name
           FROM import_requests a
           LEFT JOIN projects   b ON a.import_projectid  = b.project_id
           LEFT JOIN nidb_sites c ON a.import_siteid     = c.site_id
           LEFT JOIN instance   d ON a.import_instanceid = d.instance_id
          WHERE a.import_transactionid = ?
          ORDER BY a.import_datetime DESC'
    );
    $stmt->execute([$transactionID]);

    respond(true, 'Transaction status retrieved.', ['requests' => $stmt->fetchAll()]);
}

/**
 * @brief Returns per-series archive status for a transaction (from importlogs).
 *
 * @post action=GetArchiveStatus, sessionID, transactionID
 * @return void  Emits JSON { series: [ ... ] }.
 */
function handleGetArchiveStatus(): void
{
    requireSession();

    $transactionID = trimmedPost('transactionID');
    if ($transactionID === '') {
        respond(false, 'Missing required parameter: transactionID.', null, 400);
    }
    if (!ctype_digit($transactionID)) {
        respond(false, 'Invalid transactionID.', null, 400);
    }
    $transactionID = (int)$transactionID;

    $db = getDb();

    // Collect the import-request IDs that belong to this transaction.
    $stmt = $db->prepare(
        'SELECT importrequest_id FROM import_requests WHERE import_transactionid = ?'
    );
    $stmt->execute([$transactionID]);
    $groupIDs = array_map('intval', array_column($stmt->fetchAll(), 'importrequest_id'));

    if (count($groupIDs) === 0) {
        respond(true, 'Archive status retrieved.', ['series' => []]);
    }

    // Aggregate the per-series import-log entries for those requests.
    $placeholders = implode(',', array_fill(0, count($groupIDs), '?'));
    $stmt = $db->prepare(
        "SELECT *,
                timediff(max(importstartdate), min(importstartdate))     AS importtime,
                date_format(max(importstartdate), '%b %e, %Y %T')        AS maximportdatetime,
                date_format(studydatetime_orig, '%b %e, %Y %T')          AS studydatetime,
                date_format(seriesdatetime_orig, '%b %e, %Y %T')         AS seriesdatetime,
                count(*)                                                 AS numfiles
           FROM importlogs
          WHERE importgroupid IN ($placeholders)
          GROUP BY stationname_orig, studydatetime_orig, seriesnumber_orig
          ORDER BY studydatetime_orig DESC, seriesdatetime_orig"
    );
    $stmt->execute($groupIDs);

    respond(true, 'Archive status retrieved.', ['series' => $stmt->fetchAll()]);
}


// ═════════════════════════════════════════════════════════════════════════════
//  Helpers
// ═════════════════════════════════════════════════════════════════════════════

/**
 * @brief Validates the session ID from POST and refreshes the idle timer.
 *
 * Reads \c sessionID from POST, verifies its format, looks up its SHA-256
 * hash in \c api_sessions, and confirms the session has not exceeded the idle
 * timeout and that the owning user is active. On success, \c last_access is
 * updated so that every authenticated request — including StartTransaction
 * and EndTransaction — slides the idle window forward.
 *
 * A single generic 401 is returned for all failure modes (missing, malformed,
 * expired, or disabled user) to avoid leaking state to the caller.
 *
 * @return array{sessionID: string, apiuser_id: int}
 */
function requireSession(): array
{
    $sessionId = trimmedPost('sessionID');

    if ($sessionId === '') {
        respond(false, 'Missing required parameter: sessionID.', null, 401);
    }

    // Exact-length alphanumeric check — rejects anything that cannot be a
    // valid session ID before we ever touch the database.
    if (!preg_match('/^[A-Za-z0-9]{128}$/', $sessionId)) {
        respond(false, 'Invalid or expired session.', null, 401);
    }

    $sessionHash = hash('sha256', $sessionId);
    $db          = getDb();

    $stmt = $db->prepare(
        'SELECT s.session_id, s.apiuser_id
           FROM api_sessions  s
           JOIN api_users     u ON u.apiuser_id = s.apiuser_id
          WHERE s.session_hash = ?
            AND u.is_active    = 1
            AND s.last_access  > DATE_SUB(NOW(), INTERVAL ' . SESSION_IDLE_MINUTES . ' MINUTE)
          LIMIT 1'
    );
    $stmt->execute([$sessionHash]);
    $session = $stmt->fetch();

    if ($session === false) {
        // Single message for all failure modes — do not reveal whether the
        // session once existed, has expired, or the user is disabled.
        respond(false, 'Invalid or expired session.', null, 401);
    }

    // Slide the idle window forward on every authenticated request.
    $db->prepare(
        'UPDATE api_sessions SET last_access = NOW() WHERE session_id = ?'
    )->execute([$session['session_id']]);

    return [
        'sessionID'  => $sessionId,
        'apiuser_id' => (int)$session['apiuser_id'],
    ];
}

/**
 * @brief Returns a singleton PDO connection using the application's global config.
 *
 * Reads host, database name, username, and password from \c $GLOBALS['cfg'].
 * PDO exceptions propagate to the global handler, which returns a generic 500.
 *
 * @return PDO
 */
function getDb(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        // Bootstrap the application config ($GLOBALS['cfg']) if it isn't already loaded.
        // LoadConfig() lives in functions.php and reads nidb.cfg; functions.php only defines
        // functions on include, so pulling it in here does not emit any output.
        if (!isset($GLOBALS['cfg']) || !is_array($GLOBALS['cfg'])) {
            require_once __DIR__ . '/functions.php';
            LoadConfig(true);
        }
        $cfg = $GLOBALS['cfg'];

        // The application talks to a separate database on the development server
        // (reached on port 8080). Mirror the selection made in includes_php.php so
        // this API hits the same database as the rest of the site.
        $isDev = isset($_SERVER['HTTP_HOST']) && stripos($_SERVER['HTTP_HOST'], ':8080') !== false;
        if ($isDev) {
            $host = $cfg['mysqldevhost'];
            $name = $cfg['mysqldevdatabase'];
            $user = $cfg['mysqldevuser'];
            $pass = $cfg['mysqldevpassword'];
        } else {
            $host = $cfg['mysqlhost'];
            $name = $cfg['mysqldatabase'];
            $user = $cfg['mysqluser'];
            $pass = $cfg['mysqlpassword'];
        }

        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $name);
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,   // use native prepared statements
        ]);
    }

    return $pdo;
}

/**
 * @brief Generates a cryptographically random 128-character alphanumeric session ID.
 *
 * Uses random_int(), which draws from the OS entropy pool and is free of the
 * modulo bias present in naive random_bytes() + ord() approaches.
 *
 * @return string  128-character string drawn from [A-Za-z0-9].
 */
function generateSessionId(): string
{
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $id    = '';

    for ($i = 0; $i < 128; $i++) {
        $id .= $chars[random_int(0, 61)];
    }

    return $id;
}

/**
 * @brief Returns a trimmed value from \c $_POST, or an empty string if absent.
 *
 * @param  string $key  The POST field name.
 * @return string       Trimmed field value, or '' if the key is not present.
 */
function trimmedPost(string $key): string
{
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
}

/**
 * @brief Emits a JSON response and terminates the script.
 *
 * @param  bool        $success   Whether the request succeeded.
 * @param  string      $message   Human-readable status message.
 * @param  mixed|null  $data      Optional payload to include under the \c data key.
 * @param  int         $httpCode  HTTP status code (default 200).
 * @return never
 */
function respond(bool $success, string $message, $data = null, int $httpCode = 200)
{
    http_response_code($httpCode);

    $body = ['success' => $success, 'message' => $message];

    if ($data !== null) {
        $body['data'] = $data;
    }

    $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        http_response_code(500);
        echo '{"success":false,"message":"Internal server error."}';
        exit;
    }

    echo $json;
    exit;
}

/**
 * @brief Maps a PHP file-upload error code to a safe human-readable string.
 *
 * Internal server paths and technical details are never exposed.
 *
 * @param  int     $code  One of the \c UPLOAD_ERR_* constants.
 * @return string         Human-readable error description.
 */
function uploadErrorMessage(int $code): string
{
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:  return 'File exceeds the maximum allowed size.';
        case UPLOAD_ERR_PARTIAL:    return 'File was only partially uploaded.';
        case UPLOAD_ERR_NO_FILE:    return 'No file was uploaded.';
        case UPLOAD_ERR_NO_TMP_DIR: return 'Temporary directory unavailable.';
        case UPLOAD_ERR_CANT_WRITE: return 'Failed to write file to disk.';
        case UPLOAD_ERR_EXTENSION:  return 'Upload blocked by server extension.';
        default:                    return 'Unknown upload error.';
    }
}
