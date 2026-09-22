# NiDB config variable usage

Reference count of every configuration variable against the PHP and C++ code bases.

## Method

- **Variable list** is derived solely from the config writer `WriteConfig()` in `src/web/functions.php` (the `[key] = $value` template lines) — not from any deployed `nidb.cfg`. `LoadConfig()` reads keys dynamically, so the writer is the canonical definition of the available variables.
- **PHP references** count literal `cfg[...]['key']` / `cfg[...]["key"]` accesses across the live web tree (`/var/www/html`, excluding `vendor/`, `deprecated/`, `scripts/`).
- **C++ references** count `cfg["key"]` accesses across `src/nidb/*.cpp` and `*.h`.
- Counts measure the config-access pattern only; a variable surfaced solely as a form field in `DisplaySettings()` still counts PHP references even if nothing consumes it at runtime (see Notes).

## Summary

| Metric | Value |
|---|---:|
| Config variables defined in writer | 110 |
| Total references | 751 (PHP 477 / C++ 274) |
| Used in both PHP and C++ | 61 |
| PHP-only | 49 |
| C++-only | 0 |
| Unused in both trees | 0 |

## Full table

Sorted alphabetically. Scope: **both** = used in PHP and C++; **php** = PHP only; **cpp** = C++ only; **none** = referenced in neither.

| Variable | PHP refs | C++ refs | Scope | Notes |
|---|---:|---:|:--:|---|
| `adminemail` | 13 | 2 | both |  |
| `allowrawdicomexport` | 5 | 0 | php |  |
| `analysisdir` | 15 | 4 | both |  |
| `analysisdirb` | 12 | 3 | both |  |
| `archivedir` | 57 | 29 | both |  |
| `archivedir1` | 1 | 0 | php | Optional storage tier; reserved for tiered storage (later addition). |
| `archivedir2` | 1 | 0 | php | Optional storage tier; reserved for tiered storage (later addition). |
| `archivedir3` | 1 | 0 | php | Optional storage tier; reserved for tiered storage (later addition). |
| `archivedir4` | 1 | 0 | php | Optional storage tier; reserved for tiered storage (later addition). |
| `backupdevice` | 1 | 1 | both |  |
| `backupdir` | 3 | 6 | both |  |
| `backupserver` | 1 | 1 | both |  |
| `backupsize` | 1 | 1 | both |  |
| `backupstagingdir` | 2 | 2 | both |  |
| `cascontext` | 3 | 0 | php |  |
| `casport` | 3 | 0 | php |  |
| `casserver` | 3 | 0 | php |  |
| `clusteranalysisdir` | 3 | 2 | both |  |
| `clusteranalysisdirb` | 3 | 2 | both |  |
| `clusternidbpath` | 1 | 24 | both |  |
| `clusterqcpath` | 1 | 0 | php |  |
| `clustersubmithost` | 9 | 4 | both |  |
| `clustersubmituser` | 1 | 1 | both |  |
| `clusteruser` | 1 | 4 | both |  |
| `debug` | 4 | 10 | both |  |
| `deleteddir` | 4 | 5 | both |  |
| `displayrecentstudies` | 2 | 0 | php |  |
| `displayrecentstudydays` | 2 | 0 | php |  |
| `downloaddir` | 4 | 0 | php |  |
| `emailfrom` | 3 | 1 | both |  |
| `emailonerror` | 2 | 0 | php |  |
| `emailpassword` | 3 | 1 | both |  |
| `emailport` | 1 | 1 | both |  |
| `emailserver` | 4 | 1 | both |  |
| `emailusername` | 3 | 1 | both |  |
| `enablebackup` | 1 | 3 | both |  |
| `enablecalendar` | 3 | 0 | php |  |
| `enablecas` | 5 | 0 | php |  |
| `enablecsa` | 1 | 5 | both |  |
| `enabledatamenu` | 3 | 0 | php |  |
| `enableftp` | 1 | 0 | php |  |
| `enablenfs` | 2 | 0 | php |  |
| `enablepipelines` | 3 | 0 | php |  |
| `enablepublicdownloads` | 3 | 0 | php |  |
| `enablerdoc` | 2 | 0 | php |  |
| `enableremoteconn` | 2 | 0 | php |  |
| `enablewebexport` | 3 | 0 | php |  |
| `exportdir` | 6 | 10 | both |  |
| `fsldir` | 1 | 1 | both |  |
| `groupanalysisdir` | 9 | 1 | both |  |
| `hideerrors` | 4 | 0 | php |  |
| `importchunksize` | 1 | 2 | both |  |
| `importdir` | 2 | 0 | php |  |
| `incoming2dir` | 2 | 0 | php |  |
| `incomingdir` | 9 | 8 | both |  |
| `ispublic` | 4 | 0 | php |  |
| `localftphostname` | 2 | 0 | php |  |
| `localftppassword` | 2 | 0 | php |  |
| `localftpusername` | 2 | 0 | php |  |
| `lockdir` | 3 | 9 | both |  |
| `logdir` | 4 | 2 | both |  |
| `modulebackupthreads` | 1 | 0 | php |  |
| `moduleexportnonimagingthreads` | 1 | 2 | both |  |
| `moduleexportthreads` | 1 | 2 | both |  |
| `modulefileiothreads` | 1 | 2 | both |  |
| `moduleimportthreads` | 1 | 0 | php |  |
| `moduleimportuploadedthreads` | 1 | 0 | php |  |
| `moduleminipipelinethreads` | 1 | 2 | both |  |
| `modulemriqathreads` | 1 | 2 | both |  |
| `modulepipelinethreads` | 1 | 2 | both |  |
| `moduleqcthreads` | 1 | 2 | both |  |
| `moduleuploadthreads` | 1 | 2 | both |  |
| `mountdir` | 32 | 5 | both |  |
| `mysqlclusterpassword` | 1 | 1 | both |  |
| `mysqlclusteruser` | 1 | 1 | both |  |
| `mysqldatabase` | 26 | 2 | both |  |
| `mysqldevdatabase` | 3 | 0 | php |  |
| `mysqldevhost` | 3 | 0 | php |  |
| `mysqldevpassword` | 3 | 0 | php |  |
| `mysqldevuser` | 3 | 0 | php |  |
| `mysqlhost` | 10 | 1 | both |  |
| `mysqlpassword` | 12 | 2 | both |  |
| `mysqluser` | 13 | 2 | both |  |
| `nidbdir` | 10 | 16 | both |  |
| `numretry` | 1 | 2 | both |  |
| `offline` | 1 | 0 | php | Gates the whole site in includes_php.php. |
| `packageimportdir` | 2 | 0 | php |  |
| `problemdir` | 2 | 9 | both |  |
| `publicdownloaddir` | 3 | 1 | both |  |
| `publicwebdir` | 2 | 0 | php |  |
| `qcmoduledir` | 2 | 2 | both |  |
| `qcpath` | 2 | 0 | php |  |
| `qsubpath` | 1 | 5 | both |  |
| `queuename` | 1 | 1 | both |  |
| `queueuser` | 1 | 4 | both |  |
| `setupips` | 5 | 0 | php |  |
| `sitecolor` | 2 | 0 | php |  |
| `sitename` | 10 | 1 | both |  |
| `sitenamedev` | 2 | 0 | php |  |
| `sitetype` | 3 | 0 | php |  |
| `siteurl` | 14 | 0 | php |  |
| `tmpdir` | 6 | 35 | both |  |
| `uploaddir` | 5 | 7 | both |  |
| `uploadeddir` | 9 | 3 | both |  |
| `uploadsizelimit` | 4 | 0 | php |  |
| `uploadstagingdir` | 2 | 3 | both |  |
| `usecluster` | 1 | 1 | both |  |
| `version` | 4 | 0 | php |  |
| `webdir` | 6 | 0 | php |  |
| `webdownloaddir` | 4 | 7 | both |  |

## Storage tiers

`archivedir1` through `archivedir4` were added as optional tiered-storage locations (reserved for a later addition). They default to blank (opt-in) and are wired through `WriteConfig()`, the `DisplaySettings()` form, and the `settings.php` save handler. Their C++ ref count is currently 0 — the tiered-storage consumers are not yet implemented.

## Settings-form-only variables

Editable in the settings form and written to the config, but with no runtime consumer in either tree: `allowphi`, `emaillib`, `redcapurl`, `redcaptoken`. (RedCap connection info is read per-module from the DB `redcap_*` columns, not these global keys.)

_Generated 2026-08-11. Regenerate after changing `WriteConfig()` or config access patterns._
