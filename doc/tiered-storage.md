# Tiered Storage — Implementation Plan

Store imaging data in up to five storage tiers, assigned per study, and move a study's data between tiers.

Every `nidb.cfg` variable and its reference counts are listed in [`config-variables.md`](config-variables.md).

**Status**
- **Done:** tier config keys (§2), the `storage_tiers` table (§3.1), and the `adminstorage.php` admin page.
- **To do:** the `study_datatier` column, the path resolver (§4), the migration worker (§5), and the study/bulk UI (§7).

---

## 1. Design

- **Tiers** are the base archive root `archivedir` (the default tier) plus up to four alternate roots, `archivedir1`–`archivedir4`.
- **Tier paths live only in `nidb.cfg`**, so they're known even when the database is down. They can't be edited through the web.
- **Tier metadata** (type, credentials, capacity) lives in the database and is managed on `adminstorage.php`.
- **Granularity is the imaging study.** Each study records its tier. Its layout inside the tier root is unchanged: `{tierroot}/{UID}/{studynum}/{seriesnum}/…`. Series inherit their study's tier.
- **A migration** copies a study's directory to the new tier root, verifies it, switches the study's recorded tier, then deletes the source.
- **Migrations run in a queued module worker**, never in a web request.
- **Only `archivedir` is tiered.** All other directories (`analysisdir*`, `backupdir`, `exportdir`, `incomingdir`, `uploaddir*`, `downloaddir*`, `tmpdir`, `deleteddir`, …) are unchanged.

**Out of scope**
- Non-imaging data (observations, interventions).
- Pipeline analysis outputs (`analysisdir`, `analysisdirb`, `groupanalysisdir`). Pipelines *read* study data from the archive, so those reads must use the resolver.
- Backup and export destinations. Both read study paths, so they must use the resolver.

## 2. Configuration — done

```
[archivedir]  = /nidb/data/archive    # default tier
[archivedir1] = /mnt/tier1/archive    # optional
[archivedir2] = /mnt/tier2/archive    # optional
[archivedir3] =                       # optional
[archivedir4] =                       # optional
```

- `archivedir1`–`archivedir4` are blank by default, written by `WriteConfig()`, and editable on the Settings page.
- With them blank, only `archivedir` exists and NiDB behaves as it does today.
- The C++ binary and PHP read the same `nidb.cfg`.

## 3. Data model

All new queries use bound statements (`MySQLiBoundQuery` in PHP, parameterized `QSqlQuery` in C++).

### 3.1 `storage_tiers` — done

One row per alternate tier (`archivedir1`–`archivedir4`). The default `archivedir` tier has no row and is always configured.

```
storagetier_id PK, storage_configname ('archivedir1'..'archivedir4'),
storage_type ENUM('nfs','s3','gcs') NULL, storage_path TEXT,
storage_username, storage_password, storage_token, storage_capacity (GB)
```

- `storage_type = NULL` means the tier is not configured.
- `storage_path` is copied from `nidb.cfg` on every page load and is read-only in the UI.
- `adminstorage.php` creates the 4 rows if missing. It shows each tier's path, whether the path exists, and its disk usage. Type, credentials, and capacity are edited there.

### 3.2 `studies.study_datatier` — to do

`VARCHAR(32) NOT NULL DEFAULT 'archivedir'`: the config key of the tier holding the study. The default puts every existing study on `archivedir`. It stores the key name rather than a foreign key, because the default tier has no `storage_tiers` row.

### 3.3 `storage_tier_migration` — to do

Work queue and history, one row per migration.

```
migration_id PK, study_id FK, from_tier, to_tier,
status ENUM('pending','copying','verifying','flipping','cleanup','completed','error','cancelled'),
bytes_total, bytes_done, file_count, verify_ok, cancel_requested,
requested_by, request_date, start_date, end_date, error_msg, next_state
```

Only one active migration is allowed per study. MariaDB has no partial indexes, so enforce this with a generated column: `active_study_id = IF(status IN ('completed','error','cancelled'), NULL, study_id)` with a `UNIQUE` index on it. Multiple NULLs are allowed.

### 3.4 Module wiring — to do

Add a `module_procs` entry and a `[moduledatatierthreads]` config key, matching the other `module*threads` workers.

## 4. Path resolver

Today the archive path is built from `cfg["archivedir"]` in many places, not through one function (Appendix A). Every one of these must go through a resolver that returns the study's tier root.

- **C++:** add `Study::ArchiveRoot()`, returning the root for `study_datatier` (falling back to `cfg["archivedir"]`). Change `Study::StudyPath()`, `Series::SeriesPath()`, and `Subject::SubjectPath()` to use it.
- **Subjects can span tiers**, because tiers are per study. Subject-level operations must resolve the path per study, not once per subject.
- **PHP:** add `GetStudyArchiveRoot($studyid)` and `GetStudyPath($studyid)` to `functions.php`, and replace the inline `$GLOBALS['cfg']['archivedir']` reads.
- **Review rule:** new code must not read `cfg["archivedir"]` directly.

**Conversion order**
1. `study.cpp`, `series.cpp`, `subject.cpp`.
2. `archiveio.cpp`, which sets the initial tier (default) at archive time.
3. `moduleExport.cpp`, `modulePipeline.cpp`, `moduleMRIQA.cpp`, `moduleQC.cpp`.
4. All PHP sites.

**Watch for**
- `audit.php` walks the archive through a local `$archivedir` variable in 7 places (`scandir($archivedir)`, `glob("$archivedir/$subject/…")`). A grep for `cfg['archivedir']` won't find them.
- These references are config management, not path building, and stay as they are:
  - The `functions.php` Settings form field, `WriteConfig()` line, and default setter.
  - The save handlers in `settings.php`, `setup.php`, and `system.php`.
  - The `adminstorage.php` default-row display.
  - Comments in `getfile.php`.
  - Log messages in `moduleFileIO.cpp`.

## 5. Migration worker (`moduleDataTier`)

The worker follows the standard module contract: `ModuleRunningCheckIn()`, `ModuleCheckIfActive()`, `module_procs`, and a thread count from `cfg["moduledatatierthreads"]`.

Each job is a state machine that can resume from `next_state` after a crash:

1. **`pending`:** claim the row. Confirm the study is idle (§6) and take an exclusive lock in `object_locks` (owner type `tiermigration`; see [`data-transfer.md`](data-transfer.md) §9.1).
2. **`copying`:** `rsync -a {fromroot}/{UID}/{studynum}/ {toroot}/{UID}/{studynum}/`. Never use `rename()`, because tiers may be on different filesystems. Update `bytes_done` and `file_count`.
3. **`verifying`:** compare file count and total size between source and destination, and set `verify_ok`. rsync already checksums each file during transfer, so there's no separate checksum pass. On a mismatch, go to `error`, leaving the source untouched and the destination in place for inspection.
4. **`flipping`:** in one transaction, set `studies.study_datatier = to_tier` and `status = 'cleanup'`. This is the commit point: the resolver now returns the new location.
5. **`cleanup`:** delete the source directory (no symlink left behind), set `status = 'completed'`, and release the lock.
6. **`error`** (from any step): stop, record `error_msg`, keep both copies, and release the lock.

**Cancel** is allowed in `pending`, `copying`, and `verifying`. The worker stops, deletes the partial destination, leaves the source untouched, releases the lock, and sets `cancelled`. This is safe because `study_datatier` hasn't changed. Once `flipping` starts, the job runs to completion.

**Crash safety.** A crash before the flip leaves the study pointing at the intact source, and the job can be re-run. A crash after the flip leaves the study pointing at the complete destination; re-running cleanup is safe.

**Before copying**, check that the destination has enough free space for `bytes_total` plus a margin.

## 6. Concurrency

- A study can migrate only when it is **idle**: no active job in the archive, QC, MRIQA, pipeline, or export queues. The worker checks this before claiming the job and holds the study lock until it finishes.
- Those modules must skip or defer a study that has an exclusive lock. `object_locks` is shared with NiDB-to-NiDB transfers, so one lock check covers both features.
- `moduleManager` already clears stale modules through `module_procs`. The tier worker gets the same long check-in allowance as backup and export, because large copies are slow.

## 7. Web UI

- **Study page:** a tier badge and a "Move to tier…" action, which adds a `pending` migration. The action is disabled while a migration for that study is active.
- **Bulk:** "Move selected studies to tier X" from project or search results.
- **Migration status page:** queue and history with progress, bytes, and errors, plus a Cancel button before the flip.
- **Target list:** only configured tiers (`storage_type` not NULL) can be migration targets. A study already on an unconfigured tier can still be read and moved off it. This lets an admin stop using a mount, drain it, then remove it.
- **Admin:** `adminstorage.php` (done).

## 8. Phases

1. **Foundation (no behavior change):** add `study_datatier`, the C++ and PHP resolvers, and convert the domain classes. With one tier this behaves exactly as today. *(Config keys, `storage_tiers`, and `adminstorage.php` are done.)*
2. **Convert read paths:** the remaining C++ modules and all PHP sites, then adopt the review rule.
3. **Migration worker:** the `storage_tier_migration` table, `moduleDataTier`, locking and idle checks, and module wiring.
4. **UI:** study and bulk enqueue, tier badges, and the status page.
5. **Scheduled movement (optional):** rules such as "no access in N days → archive tier", feeding the same queue.

Phases 1–2 must be finished before migration ships. A study whose source is deleted after a move becomes unreadable to any code that still builds paths from `archivedir`.

## 9. Risks and constraints

- The resolver refactor (phases 1–2) is the largest and riskiest part.
- Code must run on both PHP 7.2 and PHP 8.
- Every tier, including cold/archive tiers, must be encrypted at rest (see [`nist-800-171.md`](nist-800-171.md) 3.13.16).
- Copies cross filesystems; the worker must handle a full disk.

## 10. Open decisions

1. **S3/GCS tiers.** `storage_type` allows `s3` and `gcs`, but the worker (rsync) and the resolver (filesystem paths) only work with `nfs`. Choose between restricting migration to `nfs` tiers for now and designing an object-storage path (mounted via s3fs/gcsfuse, or native API copies with reads staged locally).
2. **How "idle" is detected (§6).** Decide whether to query each module's queue tables or rely on those modules taking `object_locks` themselves. `data-transfer.md` T20 defers lock checks in other modules.
3. **Scheduled movement rules (phase 5):** which criteria, and where they are configured.

---

## Appendix A. `archivedir` references to convert

Count of `archivedir` config reads per file (`archivedir1`–`archivedir4` excluded, top-level files only). A line with two references counts as two. Verified 2026-09-09.

**C++ (`src/nidb`), `cfg["archivedir"]`: 29 references in 8 files**

| File | References |
|------|-----------:|
| `archiveio.cpp` | 9 |
| `moduleExport.cpp` | 5 |
| `modulePipeline.cpp` | 3 |
| `series.cpp` | 3 |
| `study.cpp` | 3 |
| `subject.cpp` | 3 |
| `moduleMRIQA.cpp` | 2 |
| `moduleQC.cpp` | 1 |

**PHP (`/var/www/html`), `$GLOBALS['cfg']['archivedir']`: 60 references in 19 files**

| File | References |
|------|-----------:|
| `studies.php` | 19 |
| `functions.php` | 12 |
| `search.php` | 6 |
| `upload.php` | 3 |
| `cleanup.php` | 2 |
| `download.php` | 2 |
| `managefiles.php` | 2 |
| `mrseriesqa.php` | 2 |
| `qa.php` | 2 |
| `adminstorage.php` | 1 |
| `audit.php` | 1 |
| `dicom.php` | 1 |
| `getfile.php` | 1 |
| `mrqcchecklist.php` | 1 |
| `niiview.php` | 1 |
| `projects.php` | 1 |
| `viewfile.php` | 1 |
| `viewimage.php` | 1 |
| `viewimagefile.php` | 1 |
