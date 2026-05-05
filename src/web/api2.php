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
set_exception_handler(function (Throwable $e): never {
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

    // Run password_verify() in all branches to prevent timing attacks.
    // If the user doesn't exist we verify against a static dummy hash so the
    // execution time is indistinguishable from a real (failed) verification.
    static $dummyHash = '$argon2id$v=19$m=65536,t=4,p=1$dummysaltdummysalt$dummyhashvaluedummyhashvaluedummy';
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

    // TODO: create a new transaction record tied to $session['apiuser_id'].
    $transactionID = '';

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
 * @brief Ends (commits or rolls back) the current transaction.
 *
 * @post action=EndTransaction, sessionID
 * @return void  Emits a success or 500 failure response.
 */
function handleEndTransaction(): void
{
    $session = requireSession();

    // TODO: commit or roll back the open transaction for $session['apiuser_id'].
    $committed = false;

    if (!$committed) {
        respond(false, 'Failed to end transaction.', null, 500);
    }

    respond(true, 'Transaction ended.');
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

    // TODO: query the database for projects belonging to $instanceID that are
    //       accessible to $session['apiuser_id']. Use prepared statements.
    $projects = [];
    // Expected shape: [['projectID' => 1, 'projectName' => 'Example'], ...]

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
    $session = requireSession();

    // TODO: replace with the real version string, e.g. read from a shared
    //       config constant or the application's version file.
    $nidbVersion = '';

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

    // TODO: query the database for subjects belonging to $projectID that are
    //       accessible to $session['apiuser_id']. Use prepared statements.
    $subjects = [];
    // Expected shape: [['subjectID' => 1, 'subjectUID' => 'S1234'], ...]

    respond(true, 'Subject list retrieved.', ['subjects' => $subjects]);
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
        $cfg = $GLOBALS['cfg'];
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $cfg['mysqlhost'], $cfg['mysqldatabase']
        );
        $pdo = new PDO($dsn, $cfg['mysqluser'], $cfg['mysqlpassword'], [
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
function respond(bool $success, string $message, mixed $data = null, int $httpCode = 200): never
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
    return match ($code) {
        UPLOAD_ERR_INI_SIZE,
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds the maximum allowed size.',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Temporary directory unavailable.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'Upload blocked by server extension.',
        default               => 'Unknown upload error.',
    };
}
