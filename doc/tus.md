# Resumable uploads (tus) in NiDB

NiDB receives large browser uploads using the [tus 1.0.0 resumable upload protocol](https://tus.io/protocols/resumable-upload). Files are sent in chunks, so they can be larger than any single HTTP request limit, and an interrupted upload continues from the last byte the server received instead of starting over.

| Piece | File | Role |
|---|---|---|
| Library | `src/web/tus_functions.php` | The tus server (core protocol + `creation` + `termination` extensions) and helper functions for pages. No dependencies; PHP 7.2 and 8 compatible. Nothing runs when it is included |
| Endpoint | `src/web/tusupload.php` | tus endpoint for imaging imports. The context is an `upload_id` |
| Endpoint | `src/web/tuscontext.php` | General-purpose tus endpoint. The context is a random token under `[tmpdir]/tus/` |
| Client | `src/web/scripts/tus.min.js` | [tus-js-client](https://github.com/tus/tus-js-client) 4.x, exposes the global `tus` |
| Page | `src/web/importimaging.php` | Creates the import, shows the Upload Files page, and finalizes the upload |

The tus-php library in `src/web/vendor/` is **not** used. It requires PHP 8.1 or newer and brings in about 30 dependencies.

## Concepts

- **Context:** one batch of uploaded files. It is a directory holding a `.tus/` subdirectory, and `.tus/owner` records the only user allowed to upload into it. A **context ID** is a short string that the endpoint turns into that directory:
  - in `tusupload.php` the ID is the `upload_id`, and the directory is `upload_datapath`;
  - in `tuscontext.php` the ID is a 32-hex-character token, and the directory is `[tmpdir]/tus/<token>`.
- **Upload URL:** each file gets one, of the form `<endpoint>?id=<context>-<32 hex>`. tus-js-client uses it to resume the file (HEAD, then PATCH) or to discard it (DELETE).
- **Endpoint:** a small page that loads the NiDB includes and calls `TusServe()` with its own settings. The most important setting is a `resolve` callback that turns a context ID into a directory. `TusServe()` always checks `.tus/owner` itself, so an endpoint cannot skip the ownership check.
- **The browser never sends a path.** It only sends the context ID and the filename. The ID must match the endpoint's `contextpattern` regex, and the filename is reduced to a bare name (see 2.3).

---

## 1. Request flow through the PHP pages (imaging imports)

### 1.1 Creating the import (`importimaging.php`)

1. `importimaging.php?action=newimportform` shows the New Import form: data location, project, file format, modality, and matching criteria. The form has no file picker.
2. The form POSTs `action=newimport` (with a CSRF token) to `importimaging.php`.
3. `NewImport()` does the following:
   - inserts an `uploads` row with `upload_source='web'` and `upload_status='uploading'`;
   - calls `TusInitDir()` to create `[uploaddir]/<YmdHisv>_<uploadid>/.tus/` and write `.tus/owner` with the user's `user_id`;
   - sets `upload_datapath` to that directory.
4. Post/Redirect/GET redirects to `importimaging.php?action=uploadfiles&uploadid=<id>`.

NFS imports skip all of this. They are inserted directly with `upload_status='uploadcomplete'` and `upload_datapath=<nfs path>`, then redirected to the import list.

### 1.2 The Upload Files page (`action=uploadfiles`)

`DisplayUploadFiles()` does the following:
- calls `GetResumableUploadPath()`. The upload must exist, have `upload_status='uploading'` and `upload_source='web'`, and `TusReadOwner()` must return the logged-in user. Otherwise the page shows an error.
- calls `TusGetFileStates($savepath, "tusupload.php", $uploadid)`, which reads every `.tus/<hex>.json` and returns two lists:
  - **complete** files: filename, length, url;
  - **incomplete** files: filename, length, bytes received so far, url.
- writes both lists into the page as JSON (`serverComplete`, `serverIncomplete`) and renders the file picker, a progress table, and the buttons.

When the user selects files, each file is matched by **name and size** against the server lists:
- A file that matches a **complete** entry is skipped ("Already uploaded").
- A file that matches an **incomplete** entry gets `uploadUrl` set to that entry's URL, so tus-js-client resumes it instead of creating it again.
- Any other file is queued as a new upload with `metadata: { filename, context: <uploadid> }`.

The resume information comes from the server, not from the browser's localStorage (`storeFingerprintForResuming: false`). That makes it possible to resume from a different browser or computer, or after the browser's storage has been cleared.

Up to `MAXPARALLEL` (3) files upload at once, in `CHUNKSIZE` (50 MB) chunks. Each chunk is well under Apache's default 1 GiB `LimitRequestBody`. Failed requests are retried on the schedule in `retryDelays`. A file that still fails is marked red, and the **Step 1 - Upload** button becomes **Step 1 - Retry Failed Files**.

### 1.3 tus requests (`tusupload.php` → `TusServe()`)

`tusupload.php` only does the page setup and sets these options:

```php
	TusServe(array(
		'endpoint' => 'tusupload.php',
		'contextpattern' => '/^[1-9][0-9]*$/',  /* upload_id */
		'resolve' => 'ImportTusResolve',        /* upload_datapath, if status 'uploading' and source 'web' */
		'log' => 'ImportTusLog'                 /* writes to upload_logs */
	));
```

On every request, `TusServe()` does the following:
1. Reads `$_SESSION['userid']` and `validlogin`, then calls `session_write_close()` straight away. Without this, a long PATCH would hold the session lock and block every other page the user opens. The endpoint sets `$nologin = true` before including `includes_php.php`, so a user who isn't logged in gets an HTTP status instead of a redirect to `login.php`.
2. Answers `OPTIONS` immediately. Every other method needs `Tus-Resumable: 1.0.0` (412 if missing) and a valid login (403 if not).
3. Selects the handler by method. A POST can carry `X-HTTP-Method-Override` to stand in for another method.

Every handler except `OPTIONS` then goes through `TusResolve()`:
- The context ID must match `contextpattern`. On creation it comes from the `context` metadata; otherwise it comes from `?id=`, parsed by `TusParseKey()`.
- `resolve($context, $userid, $failcode)` must return a directory. `ImportTusResolve()` sends its own message when the upload is missing or not in `uploading`.
- `TusReadOwner()` of that directory must equal the logged-in user.
- If any check fails, the response is 403 on creation, or 410 on HEAD, PATCH and DELETE. A 410 tells tus-js-client that the upload is gone.

| Method | Handler | What it does | Success response |
|---|---|---|---|
| `OPTIONS` | — | Reports what the server supports | `204` with `Tus-Version: 1.0.0`, `Tus-Extension: creation,termination`, and `Tus-Max-Size` if `maxsize` is set |
| `POST` | `TusCreate()` | Needs `Upload-Length` (digits only; deferred length is not supported) and `Upload-Metadata` containing `context` and `filename`. Checks `maxsize` and free disk space (413 if exceeded). Creates `<hex>.json` and an empty `<hex>.part`. A zero-length file is completed immediately | `201` with a `Location` header holding the upload URL |
| `HEAD` | `TusHead()` | Reports how much of the file has arrived. The offset is the size of `<hex>.part`, or `length` once the file is complete | `200` with `Upload-Offset` and `Upload-Length` |
| `PATCH` | `TusPatch()` | Appends one chunk (details below) | `204` with the new `Upload-Offset` |
| `DELETE` | `TusDelete()` | Removes the `.part`, the `.json`, and the finished file if there is one | `204` |

**How a PATCH is handled:**
- `Content-Type` must be `application/offset+octet-stream` (415 otherwise).
- `Upload-Offset` must equal the current size of the `.part` file (409 otherwise). **That size is the authoritative offset.** The `.json` file never records progress.
- The `.part` file is locked with `flock(LOCK_EX | LOCK_NB)`. If another request is already writing to it, the response is 423 and the client retries.
- The endpoint sets `ignore_user_abort(true)` and `set_time_limit(0)`. It then streams `php://input` onto the end of the `.part` file, capped at `length - offset` bytes.
- Whatever arrived before a disconnect stays in the file, and the next HEAD reports it.
- When `offset == length`, `TusCompleteFile()` finishes the file (see 2.2).

The `log` callback is called when a file is created, completed, renamed, or discarded. `ImportTusLog()` writes these messages to `upload_logs` with the prefix `tusupload.php`.

### 1.4 Finalizing (`action=finalizeupload`)

When every queued file has finished, the page submits `finalizeform`, a POST with a CSRF token. The user clicks **Step 2 - Import**, or it happens automatically if the "Start the import automatically" checkbox is ticked (unticked by default).

`FinalizeUpload()` does the following:
1. Calls `GetResumableUploadPath()` again to repeat the ownership and status checks.
2. Calls `TusGetFileStates()` and refuses if any file is still incomplete, listing those files.
3. Calls `TusListFiles()` to get the final filenames, which may include a `_1`-style suffix. It refuses if there are no files.
4. Calls `TusRemoveState()` to delete `.tus/`.
5. Runs `update uploads set upload_status='uploadcomplete', upload_enddate=now(), upload_originalfilelist=? where upload_id=? and upload_status='uploading'`.
6. Redirects with PRG to `action=displayimport`.

From here on, the nidb binary's `moduleUpload` takes over. It only selects uploads whose status is `uploadcomplete`, so it never sees an upload that is still receiving files.

### 1.5 Resuming and cancelling

- An import still at `uploading` has an **Upload / Resume Files** link in the import list and a button on its details page. Both go to `action=uploadfiles`.
- `action=cancel` sets the status to `cancelled`. After that, `ImportTusResolve()` refuses all requests for the upload. Files already received stay on disk.
- An import created before this feature existed has no `.tus/owner`, so it cannot be resumed. The page explains this.

### 1.6 Sequence

```
Browser                                  importimaging.php / tusupload.php          DB / disk
-------                                  ---------------------------------          ---------
POST importimaging.php action=newimport  NewImport()                                insert uploads (uploading)
                                                                                    TusInitDir: <dir>/.tus/owner
<- 302 ?action=uploadfiles&uploadid=N
GET  ?action=uploadfiles                 DisplayUploadFiles()                        TusGetFileStates: .tus/*.json
(select files)
POST tusupload.php  (Upload-Length,      TusServe -> TusCreate()                     .tus/<hex>.json, .tus/<hex>.part
     Upload-Metadata context,filename)
<- 201 Location: tusupload.php?id=N-hex
PATCH ...?id=N-hex (Upload-Offset: 0)    TusServe -> TusPatch()                      append .tus/<hex>.part
PATCH ...?id=N-hex (Upload-Offset: 50MB) ...
   (connection drops)
HEAD  ...?id=N-hex                       TusServe -> TusHead()                       size of .part
<- 200 Upload-Offset: X
PATCH ...?id=N-hex (Upload-Offset: X)    TusPatch() -> TusCompleteFile()             rename .part -> <dir>/<name>
POST importimaging.php action=finalizeupload  FinalizeUpload()                       TusRemoveState, status uploadcomplete
<- 302 ?action=displayimport                                                        moduleUpload picks it up
```

---

## 2. File flow

### 2.1 Directory layout during an upload

The context directory depends on the endpoint:
- `tusupload.php`: `[uploaddir]/<YmdHisv>_<uploadid>/`. `[uploaddir]` comes from `/nidb/nidb.cfg`; the default is `/nidb/data/upload`.
- `tuscontext.php`: `[tmpdir]/tus/<token>/`. The default `[tmpdir]` is `/nidb/data/tmp`.

Either way, the layout inside is the same:

```
<context directory>/                         chmod 0777
├── scan001.dcm                              completed files, under their (sanitized) original names
├── scan002.dcm
├── scan002_1.dcm                            second file named scan002.dcm; renamed so neither is overwritten
└── .tus/                                    resumable-upload state. Removed by TusRemoveState()
    ├── owner                                user_id of the only user allowed to upload here (TusInitDir)
    ├── finalize.lock                        flock target that serializes "pick a free name + rename"
    ├── 3f9c...e1.json                       state for one file (see below)
    ├── 3f9c...e1.part                       bytes received so far for that file (removed when complete)
    └── 3f9c...e1.json.tmp                   short-lived: .json is written to .tmp and then renamed
```

Each `<hex>.json` looks like this:

```json
{"filename": "scan002.dcm", "length": 52428800, "created": "2026-09-24T16:55:02-04:00", "complete": true, "finalname": "scan002_1.dcm"}
```

- `filename` is the sanitized name the client sent.
- `finalname` is the name the file actually got in the context directory. It is set only when `complete` is true.
- Progress is **not** stored in the JSON. For an incomplete file, the size of `<hex>.part` is the offset.

### 2.2 Life of one file

1. **Create (POST).** Makes `.tus/<hex>.json` (`complete: false`) and an empty `.tus/<hex>.part`.
2. **Upload (PATCH, repeated).** Appends bytes to `.tus/<hex>.part`. Nothing else changes.
3. **Complete (the last PATCH).** Takes the lock on `.tus/finalize.lock`, then:
   - `TusUniqueFilename()` picks `name`, `name_1.ext`, `name_2.ext`, and so on;
   - `rename()` moves `.tus/<hex>.part` to `<dir>/<finalname>`, which is instant because both are on the same filesystem;
   - the file is set to `chmod 0777`;
   - the JSON is rewritten with `complete: true` and `finalname`.
4. **Discard (DELETE).** Removes `.part`, `.json`, and `<dir>/<finalname>` if the file was complete.
5. **Finish.** The page takes over:
   - imports call `TusRemoveState()`, which leaves only the data files for `moduleUpload`;
   - general contexts use the files, then call `TusDeleteContext()`.

### 2.3 Filename sanitizing

`TusSanitizeFilename()` does the following:
- turns `\` into `/` and keeps only the `basename`, so no directory parts get through;
- strips control characters;
- trims whitespace and strips leading dots. This means no hidden files, and a file can never land in `.tus`;
- keeps only the last 200 characters of a long name;
- uses `file` as the name if nothing is left.

For example, `../../evil/.scan 1.dcm` becomes `scan 1.dcm`. Folder structure from the client is not kept; every file lands flat in the context directory.

### 2.4 Files that never finish

These are not cleaned up yet. An import abandoned at `uploading`, or a general context that is never collected, keeps its directory (including the `.part` files) until someone deletes it.

**Files that never started are not tracked.** The server only learns about a file when its upload starts (its POST), and the Upload Files page runs 3 uploads at a time. If a user selects 500 files and leaves after 40 have finished, the page shows the 40 received files and the few that were in progress as "did not finish uploading". The files that never started are not mentioned anywhere, and **Step 2 - Import** will import the files that have arrived. The same applies to a file whose upload was refused at creation (for example, not enough disk space). Checking that every file arrived is left to the user, by comparing the received count with what they selected. If this needs closing later, the page could register every queued file (POST only) when **Step 1 - Upload** is clicked, then send the data. Every selected file would then be listed as unfinished until it arrives, and would block Step 2.

---

## 3. Using tus elsewhere, in place of a normal file upload

There are two ways to do this:
- **3.1 `tuscontext.php`:** no server code at all. This is the right choice when the page just needs the files on disk, and then processes or moves them.
- **3.2 Your own endpoint:** a few lines of setup plus a `resolve` function. Use this when files must go straight into a specific directory, or when you need extra rules (as `tusupload.php` has for import status).

### 3.1 Using the general endpoint (`tuscontext.php`)

The flow has three steps:
1. The page calls `TusCreateContext($userid)` and gets a token. This creates `[tmpdir]/tus/<token>/.tus/owner`.
2. The browser uploads to `tuscontext.php` with `metadata: { context: token, filename: file.name }`.
3. When the uploads are done, the page posts the token back. The handler calls `TusGetContextFiles($token, $userid)`, processes the files, then calls `TusDeleteContext($token, $userid)`.

The page, `mypage.php`:

```php
<?
	define("LEGIT_REQUEST", true);
	session_start();
	ob_start();
?>
<html>
	<head><title>NiDB - My Upload</title></head>
<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "tus_functions.php";
	require "menu.php";

	$action = GetVariable("action");

	ShowFlashMessage();

	switch ($action) {
		case 'processuploads':
			ob_start();
			if (VerifyCSRFToken())
				ProcessUploads(GetVariable("token"), $userid);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("mypage.php");
			break;
		default:
			DisplayUploadForm($userid);
	}

	/* -------------------------------------------- */
	function DisplayUploadForm($userid) {
		$token = TusCreateContext($userid);
		if ($token == "") {
			Error("Unable to create upload directory under [tmpdir]");
			return;
		}
		?>
		<script src="scripts/tus.min.js"></script>

		<input type="file" id="myfiles" multiple>
		<div id="mystatus"></div>

		<form method="post" action="mypage.php" name="doneform">
			<input type="hidden" name="action" value="processuploads">
			<input type="hidden" name="token" value="<?=$token?>">
			<?=CSRFTokenField()?>
		</form>

		<script>
			var token = '<?=$token?>';

			document.getElementById('myfiles').addEventListener('change', function() {
				var files = Array.from(this.files);
				var remaining = files.length;
				files.forEach(function(file) {
					var upload = new tus.Upload(file, {
						endpoint: 'tuscontext.php',
						chunkSize: 50 * 1024 * 1024,
						retryDelays: [0, 1000, 3000, 5000, 10000, 30000],
						metadata: { filename: file.name, context: token },
						storeFingerprintForResuming: false,
						onProgress: function(sent, total) {
							document.getElementById('mystatus').textContent = file.name + ': ' + (100 * sent / total).toFixed(1) + '%';
						},
						onError: function(err) {
							var msg = (err.originalResponse && err.originalResponse.getBody()) ? err.originalResponse.getBody() : err.message;
							alert('Upload of ' + file.name + ' failed: ' + msg);
						},
						onSuccess: function() {
							if (--remaining == 0) document.doneform.submit();
						}
					});
					upload.start();
				});
			});
		</script>
		<?
	}

	/* -------------------------------------------- */
	function ProcessUploads($token, $userid) {
		$files = TusGetContextFiles($token, $userid);
		if ($files === null) {
			Error("Uploads are missing, incomplete, or belong to another user");
			return;
		}
		foreach ($files as $path) {
			/* ... use, copy, or rename() $path to its destination ... */
		}
		TusDeleteContext($token, $userid);
		Notice("Processed " . count($files) . " file(s)");
	}
?>
```

Things to know about this example:
- **Retries** are automatic within a page load. tus-js-client keeps the upload URL and resumes after network errors, following `retryDelays`.
- **Resuming after the page is reloaded or closed** needs the same token, and each file matched to its upload URL. Do what `importimaging.php` does:
  - keep the token (for example in the URL, `mypage.php?token=...`) instead of calling `TusCreateContext()` again;
  - check it with `TusContextPath($token)` and `TusReadOwner()`;
  - pass `TusGetFileStates(TusContextPath($token), "tuscontext.php", $token)` to the page;
  - set `uploadUrl` for each selected file whose name and size match an incomplete entry.
- **Processing large files.** If `ProcessUploads()` will take a long time, queue the work (set a status that a module picks up) instead of doing it during the request. Moving a file to another filesystem with `rename()` copies the whole file.
- **Only the owner can see a context.** `TusGetContextFiles()` and `TusDeleteContext()` return `null` or `false` for anyone but the owner. A posted token from another user gets nothing.

### 3.2 Writing your own endpoint

Copy `tuscontext.php` and change the options passed to `TusServe()`:

```php
<?
	define("LEGIT_REQUEST", true);
	session_start();
	ob_start();                 /* TusRespond() discards any output from the includes */

	$nologin = true;            /* TusServe() sends 403 itself instead of redirecting to login.php */
	require "functions.php";
	require "includes_php.php";
	require "tus_functions.php";

	TusServe(array(
		'endpoint' => 'myendpoint.php',            /* the URL tus-js-client uses, relative to the page */
		'contextpattern' => '/^[1-9][0-9]*$/',     /* anchored; must not allow '/' or '..' */
		'resolve' => 'MyResolve',                  /* context ID -> directory */
		'log' => 'MyLog',                          /* optional */
		'maxsize' => 20 * 1024 * 1024 * 1024,      /* optional, bytes. 0 = no limit */
		'checkdiskspace' => true                   /* optional, default true */
	));

	/* return the directory for this context, or "" to refuse. The directory must already have been
	   set up with TusInitDir($dir, $userid) by the page that created it. May call
	   TusRespond($failcode, array(), "message") to send a more specific error */
	function MyResolve($context, $userid, $failcode) {
		/* e.g. look up a row by (int)$context and check its status */
		return "/some/dir/$context";
	}

	function MyLog($context, $message) {
		/* e.g. insert into a log table */
	}
?>
```

The page that uses your endpoint follows the same pattern as 3.1:
- `TusInitDir($dir, $userid)` when the context is created;
- tus-js-client with `endpoint: 'myendpoint.php'` and `metadata: { context: <id>, filename: file.name }`;
- `TusGetFileStates()` to show progress or resume, and `TusListFiles()` to get the finished files;
- `TusRemoveState()` (keep the files) or `TusDeleteTree()` (remove everything) at the end.

What each option means:

| Option | Required | Meaning |
|---|---|---|
| `endpoint` | yes | URL of the endpoint, used to build upload URLs (`<endpoint>?id=<context>-<hex>`). The endpoint must not use its own `id` query parameter |
| `contextpattern` | yes | Anchored regex for context IDs, checked on every request before `resolve` is called |
| `resolve` | yes | Callable `($context, $userid, $failcode)` returning the context directory, or `""`. Ownership is checked after it returns, so `resolve` only has to find the directory and apply your own rules |
| `log` | no | Callable `($context, $message)`, called when a file is created, completed, renamed, or discarded |
| `maxsize` | no | Largest file accepted, in bytes. Reported as `Tus-Max-Size`; larger files get 413 |
| `checkdiskspace` | no | Default `true`: refuse (413) a file larger than the free space in the context directory |

### 3.3 Function reference (`tus_functions.php`)

Server, used by endpoints:

| Function | Purpose |
|---|---|
| `TusServe($config)` | Handle one tus request and exit. See 3.2 |
| `TusRespond($code, $headers, $body)` | Send a tus response and exit. `resolve` callbacks can use it to report specific errors |

Helpers for any context directory, used by pages:

| Function | Returns | Purpose |
|---|---|---|
| `TusInitDir($dir, $userid)` | bool | Create `$dir/.tus/` and write `owner`. Also creates `$dir` if needed |
| `TusReadOwner($dir)` | int or null | Owner's `user_id`, or null if `$dir` has no tus state |
| `TusGetFileStates($dir, $endpoint, $context)` | `array($complete, $incomplete)` | Every started file, with `filename`, `length`, `offset`, and `url` (for `uploadUrl` or DELETE). Complete files also have `finalname` (the name saved in `$dir`) and `received` (Unix time the last byte was written, or null) |
| `TusListFiles($dir)` | array of names | Completed files in `$dir` (non-hidden regular files) |
| `TusRemoveState($dir)` | bool | Delete `.tus/`, keeping the files. The directory stops accepting uploads |
| `TusDeleteTree($path)` | bool | Recursive delete. Symlinks are removed, not followed |
| `TusSanitizeFilename($name)` | string | See 2.3 |
| `TusUniqueFilename($dir, $name)` | string | `$name`, or `name_1.ext`, `name_2.ext`... if taken |

Helpers for general contexts, used with `tuscontext.php`:

| Function | Returns | Purpose |
|---|---|---|
| `TusCreateContext($userid)` | token or `""` | Create `[tmpdir]/tus/<token>/`, owned by `$userid` |
| `TusContextPath($token)` | path or `""` | Directory for a well-formed token. Does not check the owner (it is also `tuscontext.php`'s `resolve`) |
| `TusGetContextFiles($token, $userid, $allowincomplete = false)` | array of full paths, or null | null if the context is missing, not owned by `$userid`, or (unless `$allowincomplete`) still has unfinished files |
| `TusDeleteContext($token, $userid)` | bool | Delete the context and its files, only if owned by `$userid` |

### 3.4 Things to keep in mind

- **Never let the client choose a directory.** The client sends only a context ID. The ID is checked against `contextpattern`, `resolve` turns it into a directory, and `TusServe()` checks `.tus/owner`. Keep `contextpattern` strict, and never build a directory from an ID that the pattern has not checked.
- **Session locking.** `TusServe()` closes the session before it reads the request body. Don't reopen the session in `resolve` or `log`.
- **Clean up.** Contexts that are never finished stay on disk. A cron job or a module should remove `[tmpdir]/tus/*` directories older than a few days.
- **Request limits.** Chunks of 50 MB stay under Apache's 1 GiB `LimitRequestBody` default and avoid long-running requests. Each request only carries one chunk, so the total file size no longer depends on `upload_max_filesize`, `post_max_size`, or timeouts. Keep `post_max_size` above the chunk size in case PHP applies it to PATCH bodies.
- **Same filesystem.** Completed files are moved out of `.tus/` with `rename()`, which is instant because `.tus/` is inside the context directory. Keep `.tus/` inside the context directory; don't put it on a different filesystem.
