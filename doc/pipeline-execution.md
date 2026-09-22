# How a pipeline runs, beginning to end

A walkthrough of the pipeline module: from the cron entry that starts it, through study selection and data download, to the cluster job and the check-ins that mark the analysis complete. Written 2026-09-15 from the code; **first draft, to be refined**. Sections marked *(verify)* are inferred and worth confirming.

Companion doc: `doc/modulePipeline-bugs.md` lists known bugs in this code path.

Source: `src/nidb/modulePipeline.{h,cpp}`, `src/nidb/moduleCluster.cpp`, `src/nidb/main.cpp`, `src/nidb/mainCluster.cpp`. Web views: `pipelines.php` (definition), `viewanalysis.php` (per-analysis log).

---

## 1. The cast

| Piece | Where it runs | Role |
|---|---|---|
| `nidb pipeline` module | NiDB server, from cron | Picks studies, downloads data, writes and submits the job file |
| Cluster job file (`sge.job` / `slurm.job`) | Cluster node | Runs the pipeline's script steps |
| `nidb cluster -u ...` sub-modules | Cluster node | Check in progress, insert results, mark completion |
| `pipelines.php` | Web | Defines the pipeline, its data steps and script steps |
| `viewanalysis.php` | Web | Displays the per-analysis log assembled from `analysis_log` |

Key tables: `pipelines`, `pipeline_steps` (script), `pipeline_data_def` (data steps), `analysis` (one row per pipeline+study), `analysis_log` (per-analysis events), `pipeline_history` (per-run events), `pipeline_procs` (running module processes), `pipeline_data` (per-series download record *(verify — the writer is currently dead code)*).

---

## 2. Startup

```
crontab: * * * * * cd /nidb/bin; ./nidb pipeline
```
Every minute (`src/setup/crontab.txt:13`). `main.cpp` parses the module name, creates the `nidb` object, connects to the database, takes a lock file so two copies don't overlap, and calls `modulePipeline::Run()` (`main.cpp:302`). On return it removes the log file (unless `keeplog`/`debug`) and checks the module out of the database.

`Run()` then:
1. `SetPipelineProcessStatus("started", 0, 0)` — inserts this PID into `pipeline_procs`.
2. `ClearPipelineHistory()` — deletes `pipeline_history` rows older than 2 days.
3. Selects candidate pipelines:
   ```sql
   select pipeline_id from pipelines
   where pipeline_status <> 'running' and (pipeline_enabled = 1 or pipeline_testing = 1)
   order by pipeline_laststart asc
   ```
   Longest-since-last-run first. **The `<> 'running'` filter is why a crashed run wedges a pipeline** — see `modulePipeline-bugs.md`.

The module loops over those pipelines, but a single run only does useful work for the ones it gets through before the next cron tick / the queue fills.

---

## 3. Per-pipeline preflight

For each pipeline (`Run()`, lines ~90–195):

- Build a `pipeline p(pipelineid, n)` object; skip if invalid.
- `RecordPipelineEvent(... "pipelineStarted" ...)` — this also assigns `runnum` (`max(run_num) + 1` for this pipeline), which tags every event for this run.
- Resolve the analysis root via `GetAnalysisLocalPath(p.dirStructure)`:
  - `"b"` → `cfg[analysisdirb]`, layout `<root>/<pipeline>/<UID>/<studynum>`
  - numeric → row in `analysisdirs` (its `dirformat` picks the layout)
  - otherwise → `cfg[analysisdir]`, layout `<root>/<UID>/<studynum>/<pipeline>`
- Mark `pipeline_lastcheck = now()`.
- Bail out (record event + `SetPipelineStopped`) if: no queue, no submit host, less than 1% free space on the analysis disk, or the pipeline is already `running`.
- `SetPipelineRunning(pipelineid, "Submitting jobs")`.
- Load the **data steps** (`GetPipelineDataDef` → `pipeline_data_def`) and the **script steps** (`GetPipelineSteps` → `pipeline_steps`). No script steps → stop.

**Levels.** Level 1 (per study) is the maintained path. Level 0 ("one shot") is deprecated: when `Run()` reaches the level 0 block it sets the pipeline to `stopped` ("Level 0 pipelines have been deprecated") and moves on to the next pipeline. Level 2 (group) was removed — it just logs a message.

---

## 4. Choosing studies — `GetStudyToDoList()`

The primary modality comes from the data steps: the step flagged `isPrimaryProtocol`, else the first step. A blank modality aborts the pipeline with an event.

Subtractive, then additive:

| Step | Meaning |
|---|---|
| A1 | All studies of that modality, active subject, `study_datetime` older than 6 hours, **with no `analysis` row for this pipeline** |
| A2 | If a parent pipeline is set: intersect with studies whose parent analysis is `complete` and not `isbad`. `depLevel = "subject"` matches any study of a subject that has a completed parent; otherwise the same study |
| A3 | If groups are set: intersect with `group_data` members |
| A4 | If projects are set: intersect with studies in those projects |
| B1 | **Add** studies whose analysis is flagged `analysis_rerunresults = 1` and `complete` |
| B2 | **Add** studies whose analysis is flagged `analysis_runsupplement = 1` and `complete` |

Every step writes a `getStudyToDoList` event into `pipeline_history`, including the study list when it's 50 or fewer — that's what the pipeline history page shows.

---

## 5. Per-study loop

For each study ID (`Run()`, lines ~313–736):

1. `SetPipelineProcessStatus("running", pipelineid, sid)` — heartbeat for the status page.
2. Load `study s(sid, n)`; skip if the study vanished.
3. **Queue check** — `IsQueueFilled()` counts `analysis` rows for this pipeline in `processing`/`started`/`submitted`/`pending` and compares with `pipeline_numproc`: `0` = room, `1` = full (sleep 60s and re-check), `2` = unusable limit (returns out of the module). Also re-checks that the pipeline and the module are still enabled.
4. Load `analysis a(pipelineid, sid, n)`. Work continues only if **no analysis exists**, or the existing one is flagged `rerunResults` or `runSupplement`.
5. If no analysis exists: re-check for a row (another process may have created one), else `insert into analysis (... 'processing' ...)` as a placeholder so no other process picks up the same study. The new `analysisRowID` is logged as `SetupCreateAnalysis`.
6. Work out the analysis path (`GetAnalysisLocalPath` with pipeline/UID/studynum) and, for a dependency, the nearest parent study in time (`studyNumNearest`, `dependencyanalysisid`).
7. **Dependency check** — subject-level just logs; study-level calls `CheckDependency()` and skips the study if the parent is missing/incomplete/bad.
8. **Download data** — `GetData(...)` (section 6), unless this is a rerun or supplement.
9. **Ok to run?** True if any of: series were downloaded, `rerunResults`, `runSupplement`, or (parent pipeline *and* study-level dependency). If not, the analysis is marked `NoMatchingSeries` and the loop moves on.
10. **Set up the directory** (new analyses only): create `<analysispath>/pipeline`, copy the parent pipeline's output in (`cp -aulL` hardlink / `cp -aus` softlink / `cp -au` copy, into a subdir or the root per `p.depDir`), delete the parent's log and job files, and `chmod -Rf 777`. The accumulated `setuplog` is written to `<analysispath>/pipeline/analysisSetup.log`.
11. **Write the job file** — `CreateClusterJobFile()` (section 7), named `<clustertype>.job`, `<clustertype>-supplement.job` or `<clustertype>rerunresults.job`.
12. **Submit** — `n->SubmitClusterJob(...)` over SSH to the submit host. On success the analysis goes to `submitted` with the job ID; on failure to `error`. Either way a `SetupSubmitToCluster` event is logged and a `pipeline_history` event is recorded.
13. Sleep 10 seconds, then the next study.

After the study loop: record `pipelineFinished`, `SetPipelineStopped(...)`, and — if no analysis for this pipeline has started in the last 60 days — `DisablePipeline()`.

At the end of `Run()`: `SetPipelineProcessStatus("complete", 0, 0)` deletes the `pipeline_procs` row.

---

## 6. `GetData()` — check, then download

Two passes over the data steps, both keyed on `pdd_order` (the step number shown in `viewanalysis.php`).

**Pass 1 — does the required data exist?** For each enabled, non-optional step: confirm the `<modality>_series` table exists, then search for series matching protocol, image type and (where set) the BOLD-reps comparison. Study-level steps search the current study; subject-level steps search the subject, narrowed by association type (`nearesttime`, `all`/`entiresubject`, or same `study_type`). Each step logs a `SetupDataStepCheck` event. The first miss sets `stepIsInvalid` and stops the pass.

- A missing step on a **subject-level dependency** → return false, nothing downloaded.
- A pipeline **with a parent** ignores `stepIsInvalid` entirely (the data may come from the parent).
- Otherwise a miss → return false.

Then a `SetupDataCheckSummary` event marks the overall check result.

**Pass 2 — copy the data.** For each enabled step, re-run the search (now honoring `pdd_seriescriteria`: `first`, `last`, `largestsize`, `smallestsize`, `usesizecriteria`) and for each matching series:

- Source: `<archivedir>/<UID>/<studynum>/<seriesnum>/<datatype>`, falling back to `.../<seriesnum>` if that doesn't exist.
- Destination: `<analysispath>/<pdd_location>`, plus `/<seriesnum>` (or a renumbered series) when `useseries` is set, plus a phase-encode subdirectory (`AP`/`PA`/`RL`/`LR`/…) when `usephasedir` is set.
- Copy method: straight `cp` (or `scp` when `dataCopyMethod == "scp"`) when the requested format is `dicom` or the archived data isn't DICOM/PAR-REC; otherwise convert via `imageIO::ConvertDicom` into a temp dir and copy the result.
- Behavioral data is copied to the path from `GetBehPath()` (per `pdd_behformat`: `behroot`, `behrootdir`, `behseries`, `behseriesdir`; `behnone` disables it).
- Everything is `chmod -Rf 777`'d; each step logs a `SetupDataStepDownload` event; `numdownloaded` counts series.

**BIDS export.** When the pipeline has `outputBIDS` set, pass 2 collects series IDs instead of copying, then calls `archiveIO::WriteBIDS()` once into `<analysispath>/<BIDSoutputDir>`.

Finally a `SetupDataDownloadSummary` event, and the accumulated `dlog` is returned as `datalog` and stored in `analysis.analysis_datalog` (shown in "Detailed log" in `viewanalysis.php`).

---

## 7. `CreateClusterJobFile()` — what the cluster actually runs

Header first — SLURM (`#SBATCH` partition, output/error paths, `--mem-per-cpu`, `--cpus-per-task`, walltime) or SGE (`#$ -N/-S/-j/-o/-V/-u`, `h_rt`, `LD_LIBRARY_PATH`).

Then, in order:

1. `echo Hostname/Username`.
2. First check-in: `nidb cluster -u pipelinecheckin -a <id> -s started|startedrerun| startedsupplement`.
3. `cd <analysispath>`; if `usetmpdir`, make `<tmpdir>/<pipeline>-<analysisid>` and copy the analysis into it.
4. **Each script step** (skipped entirely when this is a results-rerun):
   - supplement steps run only in supplement mode, and vice versa;
   - `FormatCommand()` substitutes `{analysisrootdir}`, `{analysisid}`, `{subjectuid}`, `{studynum}`, `{uidstudynum}`, `{studydatetime}`, `{pipelinename}`, `{workingdir}`, `{description}`, `{groups}`, `{uidstudynums…}`, `{numsubjects…}` (`{first_*_file}` is deprecated and no longer expanded);
   - flags in the command or description: `{NOLOG}` (no log file), `{NOCHECKIN}` (no check-in line), `{PROFILE}` (prefix `/usr/bin/time -v`);
   - a check-in line `-s processing -m 'processing step N of M'` precedes the command;
   - logged steps append stdout to `<analysispath>/pipeline/Step<N>` (or `SupplementStep<N>`) — this is the file `viewanalysis.php` shows in the Details modal for each step;
   - disabled steps are written out commented (`# `).
5. If `usetmpdir`: copy results back and delete the temp dir.
6. Result script: `FormatCommand(resultscript)` with output to `pipeline/stepResults.log`. It typically calls `nidb cluster -u resultinsert ...` to write rows into `analysis_results`.
7. Wrap-up check-ins: `updateanalysis` (refresh file list/size), `checkcompleteanalysis` (decide success from the expected-file list), then `-s complete` /`completesupplement` / `completererun`, and a final `chmod -Rf 777`.

The file is written locally, `chmod 777`'d, and submitted by path *as the cluster sees it* (`analysispath` with `/mount` stripped).

---

## 8. Cluster-side check-ins

`nidb cluster -u <submodule>` (`mainCluster.cpp`, `moduleCluster.cpp`) runs on the node and talks straight to the database:

| Sub-module | Effect |
|---|---|
| `pipelinecheckin -s started` | `analysis_status = 'started'`, sets `analysis_clusterstartdate`, `analysis_hostname`; logs `StatusAnalysisStarted` |
| `pipelinecheckin -s processing -m '…'` | Per-step progress; logged as `StatusAnalysisStepCheckin` (or `StatusResultScript` / `StatusUpdateFileList` / `StatusCheckSuccessFiles` based on the message text) |
| `pipelinecheckin -s complete` | `analysis_status = 'complete'`, sets `analysis_clusterenddate` |
| `pipelinecheckin -s completererun` | `complete`, clears `analysis_rerunresults` |
| `pipelinecheckin -s completesupplement` | `complete`, clears `analysis_rerunresults` and `analysis_runsupplement` |
| `resultinsert` | Writes a row into `analysis_results` (value/file/text/image) |
| `updateanalysis` | Recounts files and disk size for the analysis |
| `checkcompleteanalysis` | Marks the analysis successful based on the expected-file list |

Every check-in also writes an `analysis_log` row, which is exactly what the "Cluster" section of `viewanalysis.php` renders.

---

## 9. Status values seen along the way

`analysis.analysis_status`, roughly in order:

`processing` (placeholder row created) → `submitted` (accepted by the cluster) → `started` / `startedrerun` / `startedsupplement` (job began on a node) → `processing` (per-step check-ins) → `complete`.

Off-path: `error` (setup or submission failure), `NoMatchingSeries` (nothing to analyze), `OddDependencyStatus` (parent pipeline missing/incomplete/bad), `notcompleted` *(verify — set elsewhere)*.

### `pipelines.pipeline_status`

Column: `varchar(20) DEFAULT NULL` (`src/setup/nidb.sql:2193`). Only **two** values are ever written by live code, and both come in pairs with `pipeline_statusmessage`:

| Value | Written by | When |
|---|---|---|
| `running` | `modulePipeline::SetPipelineRunning()` (line 1798) | Preflight passed, the module is about to submit jobs. Also sets `pipeline_laststart = now()` |
| `stopped` | `modulePipeline::SetPipelineStopped()` (line 1773) | Finished submitting; or a preflight bail-out (no queue, no submit host, <1% disk, no data steps, no script steps); or the pipeline/module was disabled mid-run. Also sets `pipeline_lastfinish = now()` |
| `stopped` | `pipelines.php` `CreatePipeline()` (line 628) | Initial value for a new pipeline |
| `stopped` | `pipelines.php` copy/clone (line 730) | Initial value for the copy |
| `stopped` | `pipelines.php` `ResetPipeline()` (line 956) | The **reset** button — the manual way out of a wedged `running` state (`pipelines.php?action=reset`) |

Values that can also be present but are **not** written by current code:

| Value | Note |
|---|---|
| `NULL` / `''` | Column default is `NULL`; a row created outside the paths above (direct SQL, older import) never gets a status. No reader handles this specially — it simply isn't `running`, so the module treats it as runnable |
| `active` | Written only by `web/deprecated/adminpipelines.php:106`, which also selects `where pipeline_status = 'active'`. Legacy rows from that page may still carry it. Like `NULL`, it reads as "not running" |

Readers, and why the exact string matters:

- `modulePipeline::Run()` line 76 — `where pipeline_status <> 'running'` decides whether the pipeline is picked up at all. **Any value other than the literal `running` means runnable**, so `NULL`, `''`, `active` and a typo all behave the same.
- `modulePipeline::GetPipelineStatus()` (line 1821) — re-checked per pipeline in `Run()`; `== "running"` skips it.
- `pipeline_functions.php:170` — `DisplayPipelineStatus()` draws the Start/Running/Finish step widget (and shows the **reset** link) only when the status is exactly `running`.

There is currently **no `error` state**: a pipeline that died mid-run is indistinguishable from one that is genuinely running, apart from a stale `pipeline_lastcheck`. See `modulePipeline-bugs.md` for the proposal to add one.

Note `pipeline_status` is also the name of an unrelated table in the schema (`src/setup/nidb.sql:2350`) — it is not used by this module.

---

## 10. Where the module can get stuck

- A SQL error anywhere calls `exit(1)` (`nidb.cpp:497`). `SQLQuery()` can now return the error to the caller instead (optional `bool *success` argument), but `modulePipeline.cpp` doesn't use it yet. The pipeline keeps `pipeline_status = 'running'`, so the cron run skips it from then on. This is deliberate as an alert, but no error status or message is recorded, so the cause isn't visible. The manual reset is the "stop pipeline" action in `pipelines.php`.
- A study whose placeholder `analysis` row was created but which then hit a `continue` stays at `processing` forever, and still counts toward the concurrent-job limit.
- Stale `pipeline_procs` rows are only cleaned by `status.php` after 30 days.

---

## 11. Things to add to this doc later

- `moduleMiniPipeline` (runs every minute too) and how it differs.
- The expected-file / success-file list used by `checkcompleteanalysis`.
- `analysis_isbad`, and how reruns/supplements are flagged from the web UI.
- The `squirrel` export path, if pipelines touch it.
- A worked example: one pipeline definition, the resulting job file, and the log rows.
