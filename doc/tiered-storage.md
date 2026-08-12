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
resolver (§3.4).

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
