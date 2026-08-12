# Tiered Storage — Planning

Goal: allow NiDB to store **imaging** data in user-defined storage **tiers** (e.g. `active`,
`intermediate`, `archive`) assigned at the **study** level, with methods to migrate a study's
data between tiers.

> **Config reference moved.** The earlier config-key inventory and PHP/C++ usage-frequency tables
> that lived in this document are now maintained in [`config-variables.md`](config-variables.md) —
> the authoritative list of every `nidb.cfg` variable and its reference counts. This document is
> the tiered-storage **design**, and tracks what has been implemented so far.

Status at a glance:
- **Implemented:** storage-tier config keys (`archivedir1`–`archivedir4`), the `storage_tiers` DB
  table, and the `adminstorage.php` management page (§1, §3.2, §3.3).
- **Planned:** the path resolver refactor, per-study tier assignment, and the migration engine
  (§2, §3.4 onward).

---

## 1. Path construction is decentralized (the core refactor challenge)

Imaging study data lives under the archive root in a fixed layout:

```
{archiveroot}/{UID}/{studynum}/{seriesnum}/{datatype|beh|qa|qc|nifti|dicom|parrec}
```

Centralized at the domain-object layer:
- `subject.cpp` → `_subjectpath = {archivedir}/{uid}`
- `study.cpp`   → `_studypath   = {archivedir}/{uid}/{studynum}`
- `series.cpp`  → `seriespath   = {archivedir}/{uid}/{studynum}/{seriesnum}`

**But** the path is *also* rebuilt inline from `cfg["archivedir"]` at many sites across several C++
files (`archiveio.cpp`, `moduleExport.cpp`, `modulePipeline.cpp`, `subject/study/series.cpp`,
`moduleMRIQA.cpp`, `moduleQC.cpp`) plus many PHP sites, rather than through a single accessor.
(Current `archivedir` reference counts: see [`config-variables.md`](config-variables.md) — it is by
far the most-referenced data path.) **Path construction is decentralized.**

Implication: tiering cannot be achieved by changing one config value. It requires a **single point
of truth** for "where does study N physically live," which today does not exist. The
`subject`/`study`/`series` classes are the natural seam (they already own path building), but the
inline `cfg["archivedir"]` usages in the modules and all of PHP must be routed through the same
resolver (§2.4).

### 1.1 Reference inventory (per file)

Every location of the **`archivedir`** config read (the base archive root only — `archivedir1`–`archivedir4` excluded), with line numbers and a snippet. Top-level files only; no subdirectories. These are the sites the resolver refactor (§2.4) must convert. Lines marked *(×2)* contain the reference twice.

**C++ (`src/nidb`) — `cfg["archivedir"]`: 29 references across 26 lines in 8 files**

`archiveio.cpp` (9)
- L708 — `if (!SafeDeletePath(newfile, n->cfg["archivedir"], m))`
- L852 — `QString outbehdir = QString("%1/%2/%3/%4/beh").arg(n->cfg["archivedir"]).arg(subjectUID).arg(studynum).arg(...`
- L1558 — `QString outdir = QString("%1/%2/%3/%4/parrec").arg(n->cfg["archivedir"]).arg(subjectUID).arg(studynum).arg(...`
- L1586 — `QString systemstring = QString("chmod -Rf 777 %1/%2/%3/%4").arg(n->cfg["archivedir"]).arg(subjectUID).arg(s...`
- L1901 — `QString outdir = QString("%1/%2/%3/%4/%5").arg(n->cfg["archivedir"]).arg(subjectUID).arg(studynum).arg(Seri...`
- L1923 — `QString systemstring = QString("chmod -Rf 777 %1/%2/%3/%4").arg(n->cfg["archivedir"]).arg(subjectUID).arg(s...`
- L3885 — `QString datadir = QString("%1/%2/%3/%4/%5").arg(n->cfg["archivedir"]).arg(uid).arg(studynum).arg(seriesnum)...`
- L3886 — `QString behdir = QString("%1/%2/%3/%4/beh").arg(n->cfg["archivedir"]).arg(uid).arg(studynum).arg(seriesnum);`
- L3887 — `QString qcdir = QString("%1/%2/%3/%4/qa").arg(n->cfg["archivedir"]).arg(uid).arg(studynum).arg(seriesnum);`

`moduleExport.cpp` (5)
- L320 — `QString datadir = QString("%1/%2/%3/%4/%5").arg(n->cfg["archivedir"]).arg(uid).arg(studynum).arg(seriesnum)...`
- L321 — `QString behdir = QString("%1/%2/%3/%4/beh").arg(n->cfg["archivedir"]).arg(uid).arg(studynum).arg(seriesnum);`
- L322 — `QString qcdir = QString("%1/%2/%3/%4/qa").arg(n->cfg["archivedir"]).arg(uid).arg(studynum).arg(seriesnum);`
- L1792 — `QString inDirPath = QString("%1/%2/%3/%4/%5").arg(n->cfg["archivedir"]).arg(uid).arg(studynum).arg(seriesnu...`
- L1793 — `QString behindir = QString("%1/%2/%3/%4/beh").arg(n->cfg["archivedir"]).arg(uid).arg(studynum).arg(seriesnum);`

`modulePipeline.cpp` (3)
- L1311 — `QString indir = QString("%1/%2/%3/%4/%5").arg(n->cfg["archivedir"]).arg(uid).arg(localstudynum).arg(seriesn...`
- L1312 — `QString behindir = QString("%1/%2/%3/%4/beh").arg(n->cfg["archivedir"]).arg(uid).arg(localstudynum).arg(ser...`
- L1316 — `indir = QString("%1/%2/%3/%4").arg(n->cfg["archivedir"]).arg(uid).arg(localstudynum).arg(seriesnum);`

`series.cpp` (3)
- L82 *(×2)* — `if ((n->cfg["archivedir"] == "") || (n->cfg["archivedir"] == "/")) { msgs << "cfg->archivedir was invalid";...`
- L87 — `seriespath = QString("%1/%2/%3/%4").arg(n->cfg["archivedir"]).arg(uid).arg(studynum).arg(seriesnum);`

`study.cpp` (3)
- L149 *(×2)* — `if ((n->cfg["archivedir"] == "") || (n->cfg["archivedir"] == "/")) { msgs << "cfg->archivedir was invalid";...`
- L153 — `_studypath = QString("%1/%2/%3").arg(n->cfg["archivedir"]).arg(_uid).arg(_studynum);`

`subject.cpp` (3)
- L179 *(×2)* — `if ((n->cfg["archivedir"] == "") || (n->cfg["archivedir"] == "/")) { msgs << "cfg->archivedir was invalid";...`
- L182 — `_subjectpath = QString("%1/%2").arg(n->cfg["archivedir"]).arg(_uid);`

`moduleMRIQA.cpp` (2)
- L144 — `QString qapath = QString("%1/%2/%3/%4/qa").arg(n->cfg["archivedir"]).arg(uid).arg(studynum).arg(seriesnum);`
- L161 — `QString systemstring = QString("cp -v %1/%2/%3/%4/nifti/* %5").arg(n->cfg["archivedir"]).arg(uid).arg(study...`

`moduleQC.cpp` (1)
- L193 — `QString qcpath = QString("%1/%2/%3/%4/qc/%5").arg(n->cfg["archivedir"]).arg(uid).arg(studynum).arg(seriesnu...`

**PHP (`/var/www/html`) — `$cfg[archivedir]`: 58 references across 57 lines in 19 files**

`studies.php` (19)
- L448 — `echo "<b>Moving all studies to study ID [$newstudyid] Num [$lowestStudyNum]. Moving data into [" . $GLOBALS...`
- L464 *(×2)* — `$systemstring = "mkdir -p " . $GLOBALS['cfg']['archivedir'] . "/$uid/$lowestStudyNum/$newseries; mv -v $dat...`
- L563 — `$oldpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$oldstudynum/$seriesnum";`
- L564 — `$newpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$newstudynum/$seriesnum";`
- L565 — `$oldpathrenamed = $GLOBALS['cfg']['archivedir'] . "/$uid/$oldstudynum/$seriesnum-" . GenerateRandomString(10);`
- L1221 — `$archivepath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num";`
- L1225 — `rename($archivepath, $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num-$datetime");`
- L1291 — `$savepath = $GLOBALS['cfg']['archivedir'] . "/$uid/$studynum/$seriesnum/$modality";`
- L1295 — `$systemstring = "chmod -R 777 " . $GLOBALS['cfg']['archivedir'] . "/$uid";`
- L2033 — `$studypath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num";`
- L2271 — `$behs = glob($GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/beh/*");`
- L2337 — `$thumbpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/thumb.png";`
- L2338 — `$gifthumbpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/thumb.gif";`
- L2339 — `$realignpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/MotionCorrection.txt";`
- L2848 — `$thumbpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/thumb.png";`
- L2849 — `$realignpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/MotionCorrection.txt";`
- L2938 — `$seriespath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num";`
- L2942 — `$newpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num-$datetime";`

`functions.php` (10)
- L836 — `$path = $GLOBALS['cfg']['archivedir'] . "/$uid/$studynum/$seriesnum/$datatype";`
- L837 — `$seriespath = $GLOBALS['cfg']['archivedir'] . "/$uid/$studynum/$seriesnum";`
- L838 — `$qapath = $GLOBALS['cfg']['archivedir'] . "/$uid/$studynum/$seriesnum/qa";`
- L856 — `$path = $GLOBALS['cfg']['archivedir'] . "/$uid/$studynum";`
- L880 — `$path = $GLOBALS['cfg']['archivedir'] . "/$uid/$studynum";`
- L915 — `$path = $GLOBALS['cfg']['archivedir'] . "/$uid/$studynum";`
- L2131 — `$oldpath = $GLOBALS['cfg']['archivedir'] . "/$olduid/$oldstudynum";`
- L2132 — `$newpath = $GLOBALS['cfg']['archivedir'] . "/$newuid/$newstudynum";`
- L3213 — `if (($GLOBALS['cfg']['archivedir'] != "") && (isset($GLOBALS['cfg']['archivedir']))) { $archivedir = $GLOBA...`
- L3869 — `<td class="center aligned"><? if (file_exists($GLOBALS['cfg']['archivedir'])) { ?><i class="large green che...`

`search.php` (6)
- L2001 — `$thumbpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/thumb.png";`
- L2002 — `$gifthumbpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/thumb.gif";`
- L2307 — `$files = glob($GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/dicom/*.dcm");`
- L2617 — `$thumbpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/thumb.png";`
- L2618 — `$gifthumbpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/thumb.gif";`
- L3548 — `$thumbpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$studynum/$seriesnum/thumb.png";`

`upload.php` (3)
- L217 — `$uploadpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/beh/";`
- L230 — `$uploadpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/" . strtolower($modality) . "/";`
- L266 — `$systemstring = "cp -R " . $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/* $backupdir";`

`cleanup.php` (2)
- L176 — `$archivepath = $GLOBALS['cfg']['archivedir'] . "/$uid/$studynum";`
- L261 — `$archivepath = $GLOBALS['cfg']['archivedir'] . "/$uid/$studynum";`

`download.php` (2)
- L80 — `$datapath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/beh";`
- L84 — `$datapath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/$datatype";`

`managefiles.php` (2)
- L81 — `$path = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/$datatype";`
- L83 — `$path = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/" . strtolower($datatype);`

`mrseriesqa.php` (2)
- L60 — `$thumbpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/thumb.png";`
- L61 — `$qapath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/qa";`

`qa.php` (2)
- L42 — `$thumbpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/thumb.png";`
- L43 — `$qapath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/qa";`

`adminstorage.php` (1)
- L306 — `$defaultpath = $GLOBALS['cfg']['archivedir'] ?? '';`

`audit.php` (1)
- L37 — `$archivedir = $GLOBALS['cfg']['archivedir'];`

`dicom.php` (1)
- L127 — `$archivepath = realpath($GLOBALS['cfg']['archivedir']);`

`getfile.php` (1)
- L64 — `$archivePath = $GLOBALS['cfg']['archivedir'];`

`mrqcchecklist.php` (1)
- L515 — `$thumbpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$studynum/$seriesnum/thumb.png";`

`niiview.php` (1)
- L63 — `$datapath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/$datatype";`

`projects.php` (1)
- L925 — `$archivepath = $GLOBALS['cfg']['archivedir'] . "/$uid/$studynum/$seriesnum/$datatype";`

`viewfile.php` (1)
- L36 — `$archivePath = $GLOBALS['cfg']['archivedir'];`

`viewimage.php` (1)
- L63 — `$datapath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/$datatype";`

`viewimagefile.php` (1)
- L37 — `$archivePath = $GLOBALS['cfg']['archivedir'];`

### 1.2 Ambiguous / non-path mentions (per file)

Lines that mention `archivedir` but are **not** a clear config read. Called out so the resolver
refactor neither misses a real usage nor needlessly touches config-management code.

- **`audit.php` (PHP) — real usage via indirection.** Beyond its 1 clear read, it traverses the
  archive at **7 sites** through a local `$archivedir` variable (`scandir($archivedir)`,
  `glob("$archivedir/$subject/$study/$series/dicom/*.dcm")`, etc.). A plain `cfg['archivedir']`
  grep misses these — the resolver work must catch the local-variable form here.
- **`functions.php` (PHP) — config management, not path construction.** The Settings form field
  (`name="archivedir"`), the `WriteConfig()` template line (`[archivedir] = $archivedir`), and the
  default-setter (`if ($archivedir == "") …`) belong to config handling and stay as-is.
- **`settings.php`, `setup.php`, `system.php` (PHP) — settings save handlers.** Each has one
  `$c['archivedir'] = GetVariable("archivedir")` (writing config). Not archive reads; not
  tiering-relevant.
- **`adminstorage.php` (PHP) — display only.** Besides its 1 read, the remaining mentions are the
  default-row label and a comment.
- **`getfile.php` (PHP) — comment only** (a security note); the 1 read is counted above.
- **`moduleFileIO.cpp` (C++) — log text only.** Two mentions are inside log-message strings
  ("moving DICOM files from archivedir to incomingdir"); no path is built from the config here.

---

## 2. Tiered storage design — DETAILED PLAN

### 2.0 Decisions locked in
- **Registry model: Hybrid.** Tier **roots** (filesystem paths) are defined in `nidb.cfg` — kept in
  ops control, not editable through the web. Tier **metadata** (type, credentials, capacity,
  enabled) lives in the database and is managed by `adminstorage.php`. Tier **assignment per study**
  and **migration state** (planned) will also live in the database. *(Tier roots + metadata + admin
  UI are implemented; per-study assignment + migration are still to build.)*
- **Migration engine: Queued module worker.** A new `module*` worker (modeled on
  `moduleExport`/`moduleBackup`) drains a migration queue table. No large copies run inside a web
  request.
- **Granularity:** imaging **study** level. Subjects/series inherit their study's tier (a study
  owns `{UID}/{studynum}/…`). Non-imaging data and pipeline analysis outputs are out of scope
  (see §2.9).
- **Scope: `archivedir` only.** Tiering applies **solely** to the imaging archive root
  (`archivedir` and its alternates `archivedir1`–`archivedir4`). Every other config directory
  (`analysisdir*`, `backupdir`, `exportdir`, `incomingdir`, `uploaddir*`, `downloaddir*`, `tmpdir`,
  `deleteddir`, etc.) is left exactly as it is today. "Tier root" is therefore synonymous with "an
  `archivedir`-family path."

### 2.1 Concept
Generalize the single `archivedir` into a small fixed set of named tiers. Each study records which
tier holds its data; its on-disk layout **within** a tier root is unchanged
(`{tierroot}/{UID}/{studynum}/…`). Migration = copy a study subtree from one tier root to another,
verify, flip the study's recorded tier, then remove the source.

### 2.2 Config format (tier roots) — IMPLEMENTED
Rather than the originally-planned arbitrary `datatier_*` prefix scheme, tiering uses **four fixed
alternate archive roots** alongside the existing base archive, all flat `[key] = value` entries in
`nidb.cfg`:

```
# ----- Directories -----
[archivedir]  = /nidb/data/archive    # base / default ("active") tier
[archivedir1] = /mnt/tier1/archive    # optional alternate tier
[archivedir2] = /mnt/tier2/archive    # optional
[archivedir3] =                       # optional
[archivedir4] =                       # optional
```

- `archivedir1`–`archivedir4` are written by `WriteConfig()`, editable on the **Settings** page,
  and **blank (opt-in) by default**. See [`config-variables.md`](config-variables.md).
- **Back-compat:** with `archivedir1`–`archivedir4` blank, only the base `archivedir` tier exists
  and installs behave exactly as today. `archivedir` remains the physical root of the default tier.
- Both the C++ binary and PHP read the same `nidb.cfg`, so both get the tier roots for free.
- **The path lives only in `nidb.cfg`** (not editable through the DB) so it is still known if the
  database is unavailable. The DB mirror (§2.3) is kept in sync *from* config, never the reverse.

### 2.3 Data model
1. **`storage_tiers`** — **IMPLEMENTED.** One row per tier config key (`archivedir1`–`archivedir4`),
   holding per-tier metadata:
   ```
   storagetier_id PK, storage_configname ('archivedir1'..'archivedir4'),
   storage_type ENUM('nfs','s3','gcs') NULL, storage_path TEXT,
   storage_username, storage_password, storage_token, storage_capacity (GB)
   ```
   - `storage_type = NULL` marks the tier as **not configured**.
   - `storage_path` is **mirrored from `nidb.cfg`** (config-authoritative, read-only in the UI).
   - Exactly 4 rows, auto-created and kept in sync by the admin page.
   - Managed via **`adminstorage.php`** (Admin → Storage tiers): edit type / credentials / capacity;
     view the config path, whether it exists on disk, and live `df` disk usage; mark a tier
     configured or not. This is the "hybrid" split — roots in config, metadata in the DB.
2. **`studies.study_datatier`** — **PLANNED.** `VARCHAR(32) NOT NULL DEFAULT 'archivedir'`. Holds
   the tier config-key name a study's data lives in. The default makes every existing study resolve
   to the base `archivedir`. (Name, not FK, keeps it decoupled from a DB tier table and robust to
   config-defined roots.)
3. **`storage_tier_migration`** — **PLANNED.** The queue + history table:
   ```
   migration_id PK, study_id FK, from_tier, to_tier,
   status ENUM('pending','copying','verifying','flipping','cleanup','completed','error','cancelled'),
   bytes_total, bytes_done, file_count, verify_ok,
   requested_by, request_date, start_date, end_date, error_msg, next_state
   ```
   One in-flight row per study (unique partial index on `study_id` where status not terminal).
   Serves as both work queue and audit log.
4. **`module_procs`** integration + a new `[moduledatatierthreads]` config key, matching the other
   `module*threads` workers — **PLANNED.**

All new queries use prepared/bound statements (`MySQLiBoundQuery` in PHP; parameterized `QSqlQuery`
in C++).

### 2.4 Path resolution — the core refactor
Introduce **one** resolver and route every archive-path construction through it.

- **C++:** add `Study::ArchiveRoot()` returning `tierRoot(study_datatier)` (falls back to
  `cfg["archivedir"]`). `Study::StudyPath()`, `Subject::SubjectPath()`, `Series::SeriesPath()`
  already centralize layout — extend them to source the root from the study's tier instead of
  `cfg["archivedir"]`. Subject path is tricky (a subject can span studies in different tiers);
  since tiering is per-study, subject-level operations must resolve per-study, not once.
- **PHP:** add `GetStudyArchiveRoot($studyid)` / `GetStudyPath($studyid)` helpers; replace the
  inline `cfg['archivedir']` sites.
- **Triage order** (from §1 — the decentralized C++ + PHP sites; see `config-variables.md` for
  counts):
  1. Domain classes `study.cpp` / `series.cpp` / `subject.cpp` (the seam).
  2. `archiveio.cpp` — write path at archive time (sets initial tier = default).
  3. `moduleExport.cpp`, `modulePipeline.cpp`, `moduleMRIQA.cpp`, `moduleQC.cpp` — read paths; must
     resolve per-study.
  4. All PHP read sites (downloads, views, exports).
- **Guard:** a code-review rule that new code must not reference `cfg["archivedir"]` directly —
  always go through the resolver.

### 2.5 Migration module (`moduleDataTier`)
New worker following the established module contract (`ModuleRunningCheckIn()`,
`ModuleCheckIfActive()`, `module_procs`, thread count from `cfg["moduledatatierthreads"]`).

**Per job (one `storage_tier_migration` row), state machine (resumable via `next_state`):**
1. `pending` → claim row, re-check study is idle (not being archived/QC'd/pipelined — see §2.6).
   Acquire a per-study lock (`lockdir`).
2. `copying` → copy `{fromroot}/{UID}/{studynum}` → `{toroot}/{UID}/{studynum}` with `rsync -a`
   (cross-filesystem safe; tiers may be different mounts, so **no `rename()`**). Update
   `bytes_done`/`file_count` for progress.
3. `verifying` → **count + size** check, source vs. dest, and set `verify_ok`. No separate
   checksum pass: rsync already verifies each file's content in-flight (block + whole-file
   checksums during transfer, re-sending anything that mismatches), so bytes are confirmed correct
   as they land; the count+size pass just confirms completeness. Abort to `error` on mismatch
   (dest left for inspection, source untouched).
4. `flipping` → **atomic commit point:** in one transaction, set `studies.study_datatier = to_tier`
   and `status='cleanup'`. After this the resolver serves the new location.
5. `cleanup` → delete source subtree `{fromroot}/{UID}/{studynum}` (clean removal — no symlink
   left behind); `status='completed'`.
6. `error` at any step → stop, record `error_msg`, keep both copies where safe, release lock.

**Cancellation:** allowed while `pending`/`copying`/`verifying` (before the flip). The UI sets a
cancel request; the worker (or the enqueue guard, if still pending) stops, deletes the **partial
destination** subtree, leaves the source untouched, releases the lock, and sets `cancelled`. Safe
because the DB tier pointer never moved — the study still resolves to the intact source, and a
partial dest can never be read as live data. Once `flipping` starts, cancel is refused and the job
runs to completion.

**Safety properties:**
- The flip is the single source-of-truth switch; a crash before it → resolver still points at
  source (safe, re-runnable); after it → source deletion is idempotent (re-runnable).
- Per-study lock + "study idle" precondition prevents racing the archive/QC/pipeline modules.
- Cross-mount aware; verify-before-flip; source retained until after successful flip.

### 2.6 Concurrency with other modules
- A study is **migratable only when idle.** Define idle = not currently referenced by an active
  job in the archive/QC/MRIQA/pipeline/export queues. The module checks these before claiming and
  holds the per-study lock for the duration.
- Conversely, those modules must **refuse/defer** work on a study whose migration row is in a
  non-terminal state (add a cheap guard: check `storage_tier_migration` before operating).
- `moduleManager` already reaps stale modules via `module_procs`; `datatier` joins that mechanism
  (with a long/without-checkin allowance like backup/export, since big copies are slow).

### 2.7 Web UI / API
- **Study view:** show current tier (badge) and a "Move to tier…" action → enqueues a
  `storage_tier_migration` row (`pending`). Disabled if a migration is already in-flight.
- **Bulk:** project- or criteria-level "move selected studies to tier X" → bulk enqueue.
- **Status view:** a queue/history page (like the remote-import batch list) showing progress,
  bytes, and errors; allow cancel any time before the flip (`pending`/`copying`/`verifying`) —
  worker deletes the partial dest, source stays intact (§2.5).
- **Target list:** the "Move to tier…" picker offers only **configured** tiers (`storage_type` not
  NULL); a study currently on a now-unconfigured tier still shows/reads normally and can be moved
  off it.
- **Admin:** `adminstorage.php` manages the tier metadata (type, credentials, capacity) —
  implemented. A future "enabled/disabled as migration target" flag would live here too.
- All server-side inserts/updates use bound statements.

### 2.8 Scheduled tier movement (later phase)
Optional rules engine (e.g. "studies with no access in N days → `archive`"), implemented as a
periodic enqueue step feeding the same queue. Deferred to a later phase; not required for MVP.

### 2.9 Out of scope (explicit)
- Non-imaging data (observations, timeseries, instruments).
- Pipeline **analysis outputs** (`analysisdir`/`analysisdirb`/`groupanalysisdir`): they stay put.
  Note pipelines *read* study inputs from the archive — those reads must go through the resolver so
  they still find data after a study moves tiers.
- Backup (`backupdir`) / export (`exportdir`) destinations are unchanged, but both **read** study
  paths and so must use the resolver (§2.4 triage item 3).

### 2.10 Implementation phases
1. **Foundation (no behavior change):**
   - **Done:** tier config keys `archivedir1`–`archivedir4` (with `archivedir` as the default
     tier), the `storage_tiers` table, and the `adminstorage.php` management UI.
   - **To do:** `study_datatier` column (default `archivedir`); the C++ + PHP resolver; convert the
     domain classes and highest-traffic sites. Ships invisibly — one default tier == today.
2. **Read-path conversion:** convert remaining C++ modules + all PHP archive-path sites to the
   resolver; add the "no direct `archivedir`" review rule.
3. **Migration engine:** `storage_tier_migration` table, `moduleDataTier` worker, per-study
   locking + idle checks, module_procs/threads wiring.
4. **UI/API:** study + bulk enqueue, tier badges, status/history page.
5. **Scheduling (optional):** rule-based auto-migration.

### 2.11 Risks & constraints
- Dual **PHP 7.2 / 8**; prepared statements throughout.
- **Cross-filesystem** copies (no `rename()`); verify before flip; retain source until flip.
- **Decentralized paths** (§1) — the refactor is the largest/riskiest part; phases 1–2 de-risk it
  before any migration exists.
- **Subject spanning tiers:** subject-level ops must resolve per study, not once.
- Free-space checks on the destination tier before copy; disk-full handling.

### 2.12 Resolved decisions
1. **Verification depth:** count + size only, relying on rsync's in-transfer verification for
   byte-level integrity. No separate/opt-in checksum pass (a second full read of a cold tier can
   nearly double migration time on large small-file studies for narrow benefit).
2. **Source cleanup:** **clean removal** — delete the source subtree after the flip. The resolver
   points every reader at the new tier, so no symlink is needed; this frees the space (and inodes)
   that tiering exists to reclaim. Depends on phases 1–2 having converted all path sites so nothing
   bypasses the resolver.
3. **Unconfigured tier (`storage_tiers.storage_type IS NULL`):** hidden from new-study assignment
   and rejected as a migration **target**, but reads and migrating **out** stay allowed — supporting
   the "stop using, then drain, then decommission a mount" workflow. Reads are never blocked (the
   data physically lives there).
4. **Cancel:** allowed **any time before the flip** (`pending`/`copying`/`verifying`). On cancel:
   stop the worker, delete the partial destination subtree, leave the source untouched, mark
   `cancelled`. Safe because the DB tier pointer never moved, so the study still resolves to the
   intact source and a partial dest can never be mistaken for live data. Once `flipping` begins the
   job runs to completion.
