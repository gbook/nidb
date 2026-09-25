# Universal Import — design notes

Status: **brainstorm / living document**. Started 2026-09-25. Nothing here is implemented yet; this captures ideas for how a guided "import anything" feature could look to the user and fit into NiDB.

## 1. Goal

Let a user hand NiDB a pile of raw research data, in whatever format and folder layout they happen to have, and have NiDB:

1. **Identify** what the data is — its *modality*, *file type*, and *directory structure* — and from that, where the subject ID, study/session, and series live.
2. **Guide** the user through confirming or correcting those guesses, with a live preview of the resulting `subject → study → series` tree before anything is archived.
3. **Remember** what worked. A successful import becomes a reusable **import recipe**, so the next upload that looks similar is recognized and pre-filled automatically.

NiDB's storage model is already modality-agnostic (every modality has a `*_series` table under the same subject/study hierarchy). The gap is on the way in: today's import path mostly assumes DICOM (or squirrel/BIDS), and anything else relies on filename conventions hard-coded in `imageIO::GetImageFileTags()`.

### 1.1 Decisions

| # | Decision | Date | Consequence |
|---|---|---|---|
| D1 | The guided flow is a **new wizard page** (working name `importwizard.php`), not a rework of `importimaging.php`. | 2026-09-25 | The existing form stays as-is for current users; the wizard can later replace it. |
| D2 | The **initial scan runs on the web side (JavaScript in the browser + PHP), before upload** where possible, exploring the directory on the user's computer. | 2026-09-25 | Identification and mapping happen *before* any bytes are uploaded; only the files the user confirmed are sent. See §5 and §6.1. |
| D3 | **All data is archived as the original files. No format conversion** on import (no DICOM→NIfTI, PAR/REC→NIfTI, etc.). Conversion is left to pipelines. | 2026-09-25 | Recipes have no conversion options. Archive stage stores files byte-for-byte. |
| D5 | **No anonymization on import, for now.** Files (DICOM and non-DICOM) and filenames are archived exactly as uploaded. | 2026-09-25 | Wizard and recipes have no anonymization options. De-identification, if needed, happens before upload or later (e.g. at export). May be revisited. |
| D6 | **Patient info (PHI/PII) found by the survey triggers a warning only.** It never blocks the import, requires no acknowledgement, and has no per-project enforcement setting. | 2026-09-25 | What counts as PHI/PII, and what is acceptable, varies by institution and project and can change during a project, so NiDB reports what it saw and leaves the judgement to the user. See §8. |
| D7 | The JavaScript survey reads DICOM headers with an **existing DICOM parser library** (e.g. `dicomParser`), not a hand-written tag walker. | 2026-09-25 | Bundled with the web files like other JS libraries. See §6.2. |
| D8 | **Every universal import keeps a decision log in the database**, recording what NiDB guessed and what the user decided, in detail when the data didn't fit an existing recipe. Not in `[logdir]`, which is only for temporary log files from running modules. | 2026-09-25 | Gives a permanent audit record per import and feeds the learning in §7.3. See §6.5. |
| D9 | **Submitting user-created recipes to the central NiDB repo is deferred.** The long-term goal is still to collect them so they ship to everyone. | 2026-09-25 | Not designed for now. Recipes should stay shareable (no PHI, self-contained JSON) so this can be added later. See §7.4. |
| D10 | **Scope is imaging data** (anything stored in a `*_series` table) **plus basic subject demographics** (e.g. sex, DOB) found in headers or files like `participants.tsv`. Other non-imaging data stays with the existing `importnonimaging.php`. | 2026-09-25 | Measures, observations, interventions, etc. are out of scope. |
| D4 | **Every upload records the exact recipe version it used.** Recipes are versioned and old versions are never modified or deleted. | 2026-09-25 | Provenance/audit trail (21 CFR Part 11). See §6.3. |

## 2. What exists today (starting point)

| Piece | Where | Notes |
|---|---|---|
| Upload form | `src/web/importimaging.php` | User picks modality (or "Unknown — let NiDB guess"), plus subject/study/series matching criteria (radio buttons). |
| Upload record | `uploads` table | `upload_type` enum is `dicom`, `squirrel`, `auto`, `bids`. Criteria enums: `upload_subjectcriteria` (`patientid`, `namesexdob`, `specificpatientid`, `patientidfromdir`), `upload_studycriteria`, `upload_seriescriteria`. |
| Parse stage | `moduleUpload::ReadUploads()` / `ParseUploadedFiles()` | Copies to staging, unzips, reads each file's tags, groups into subject/study/series. |
| File identification | `imageIO::GetImageFileTags()` | DICOM via DCMTK; otherwise **by extension**: `.cnt/.dat/.3dd/.eeg` → EEG, `.edf` → ET, `.nii/.nii.gz/.hdr/.img` → NIFTI, `.par/.rec` → PARREC (header parsed), else `exiftool`. Subject ID for non-DICOM = first `_`-separated token of the filename. |
| Review stage | `upload_subjects` / `upload_studies` / `upload_series` | Parsed staging tree with `matching*id` columns pointing to existing NiDB objects. User selects series to import. |
| Archive stage | `moduleUpload::ArchiveSelectedFiles()` → `archiveIO` | Writes the real subject/study/series rows and files. |
| BIDS | `src/nidb/bids.{h,cpp}` + `ArchiveUploadedBIDS()` | Separate reader producing a neutral `bids::BidsDataset`; see `doc/bids-usage.md`. |
| Transport | tus resumable uploads (`tusupload.php`, `doc/tus.md`) | Large browser uploads. NFS path is the other source. |

**Key observation:** the *parse → review → archive* pipeline with staging tables is already the right shape. Universal Import mostly needs to (a) replace the fixed "read tags per file" parse step with pluggable **detectors** and a **mapping recipe**, and (b) put a guided UI in front of the review stage.

## 3. The three axes

Every import is classified along three independent axes. The combination (plus the identity-mapping rules in §4) is what a recipe records.

### 3.1 Modality

What was measured. Uses the existing `modalities` table codes (MR, CT, EEG, MEG, ET, VIDEO, AUDIO, TASK, GSR, ECG, …), each backed by its `<modality>_series` table.

- Sometimes certain from the file (DICOM `0008,0060`, PAR header `MRSERIES`, `.fif` → MEG, BrainVision → EEG).
- Sometimes ambiguous (`.edf` is used for both EEG and eye-tracking; `.nii.gz` could be MR, PET, or a derived map; `.mp4` could be a behavioral video or an ultrasound clip).
- Sometimes **mixed** in one upload (simultaneous EEG-fMRI, task logs alongside MR). The design must allow a modality per series, not just per upload.

### 3.2 File type

The container format, with optional **subtypes / companions** describing what travels with it.

| Primary type | Typical extensions / signature | Common companions (subtypes) |
|---|---|---|
| DICOM | `DICM` at byte 128; often no extension | DICOMDIR, enhanced/multi-frame, secondary capture, SR |
| PAR/REC | `.par` + `.rec` pair | `.xml` variant (XML/REC) |
| NIfTI / Analyze | `.nii`, `.nii.gz`, `.hdr/.img` | `.json` sidecar, `.bval/.bvec`, `_events.tsv` |
| EEG | `.vhdr/.vmrk/.eeg` (BrainVision), `.edf/.bdf`, `.set/.fdt` (EEGLAB), `.cnt`, `.mff` (dir) | electrode positions (`.3dd`, `.elc`), event/marker files |
| MEG | `.fif`, `.ds` (CTF dir), `.con/.sqd` | head shape, digitization |
| Eye tracking | `.edf` (EyeLink), `.asc`, `.tsv` | calibration logs |
| Video | `.mp4`, `.mov`, `.avi`, `.mkv` | timing/sync logs |
| Audio | `.wav`, `.flac`, `.mp3` | transcripts |
| Behavioral / task | `.csv`, `.tsv`, `.log` (Presentation, E-Prime `.edat`/`.txt`, PsychoPy) | stimulus files |
| Physio | `.acq` (BIOPAC), `.puls/.resp` (Siemens) | |
| Archive/package | `.zip`, `.tar.gz`, squirrel | (unpacked first, then re-detected) |
| Unknown binary | anything else | → `binary_series`/`ot_series` fallback |

A file-type **registry** (see §6.2) would hold this table as data rather than code, so new types can be added by an admin.

### 3.3 Directory structure (layout)

How files are organized on disk, which is usually where subject/session/series identity comes from when the file headers don't carry it.

| Layout | Recognized by | Identity source |
|---|---|---|
| **Flat DICOM dump** | Many DICOM files, arbitrary names/dirs | DICOM headers (current behavior) |
| **DICOMDIR** | `DICOMDIR` file at root | DICOMDIR records |
| **BIDS** | `dataset_description.json`, `sub-*/[ses-*/]<datatype>/` | Entities (`sub`, `ses`, `run`, `task`, `acq`, …) |
| **Subject/Study/Series tree** | Consistent depth, 3 levels of dirs | Directory names at each level |
| **NiDB export / squirrel** | squirrel package, or NiDB's own export naming | Package metadata |
| **Filename-encoded** | Flat dir, filenames with repeated token patterns (`S012_V2_rest.edf`) | Tokens in the filename |
| **Scanner/vendor native** | e.g. Siemens/GE/Philips export trees, EGI `.mff` bundles | Vendor-specific rules |
| **Custom** | None of the above | User-defined template (§4.2) |

## 4. Identity mapping — the heart of it

Once we know *what* the files are, we must answer, for every file: **which subject, which study, which series?** Each of the three levels is resolved from an ordered list of **sources**:

1. File header (DICOM tags, PAR header, EDF header patient field, JSON sidecar).
2. Directory path segment (e.g. level 1 = subject).
3. Filename token (e.g. regex group).
4. A constant the user types (e.g. "all of this is subject S1234, visit 2").
5. Derived / default (file mtime for date, auto-incremented series number).

The existing `upload_*criteria` enums are a narrow special case of this; they'd become presets inside the new model.

### 4.1 What NiDB needs per level

| Level | Required | Nice to have | Matching to existing NiDB records |
|---|---|---|---|
| Subject | an ID | name, sex, DOB (for `namesexdob` matching), alt IDs | by UID, alternate ID, or enrollment in the target project |
| Study | modality, date/time | study UID, description, visit/session label, site/equipment | by study UID, or modality+datetime, or session label |
| Series | number or ordering key, description/protocol | datetime, dimensions, TR/TE, etc. | by series UID, number, or datetime |

**Grouping rule:** a series is a *set* of files, not one file. Companions must stay together (`.vhdr+.vmrk+.eeg`, `.nii.gz+.json+.bval+.bvec`, `.par+.rec`, a 200-file DICOM series). The file-type registry defines companion rules (same basename, same dir, etc.).

### 4.2 Path template builder

For custom and filename-encoded layouts, the user shouldn't write regexes. Show them a sample path and let them **click segments to assign roles**:

```
 /upload/ProjectX/ S012 / visit2 / 2024-03-14_rest_eyesclosed.vhdr
                   ────   ──────   ────────── ───────────────
                   [Subject] [Study label] [Study date] [Series desc]  [ignore]
```

NiDB converts the selection into a template, e.g. `{subject}/{studylabel}/{studydate:%Y-%m-%d}_{seriesdesc}.{ext}`, generalizes the tokens into patterns (`S012` → `S\d{3}`), and immediately applies it to **every** file in the upload, highlighting files that don't match. This is the same idea as BIDS entities, but user-defined.

## 5. What the user sees

A wizard layered over the existing upload/review flow. Each step shows NiDB's best guess with its evidence, and the user only acts where something is wrong or uncertain.

### Step 1 — Choose data (nothing uploaded yet)

The user picks the destination project, then points the wizard at their data:

- **Local data (the main case):** "Choose folder" or drag-and-drop a folder onto the page. The browser gets read access to the file list and file contents **locally**; nothing is uploaded at this point.
- **Server-side data (NFS path):** the user types a path and PHP scans it directly on the server.

**No modality or criteria questions up front** — those come after the scan.

### Step 2 — Survey (automatic, fast, pre-upload)

For local data, JavaScript walks the chosen folder and builds a **data profile** from *metadata and small byte ranges only*:

- full relative path, size, and `lastModified` for every file (cheap — no content read)
- extension histogram, directory depth histogram
- magic-byte sniff: read the first few hundred bytes of a sample of each extension group with `File.slice()` (e.g. `DICM` at byte 128, `Brain Vision Data Exchange` header line, NIfTI `sizeof_hdr`, EDF header)
- small header reads on a few files per group (DICOM tags such as Modality/PatientID/StudyDate/SeriesNumber, PAR header, EDF header, NIfTI header, `.json` sidecars, `dataset_description.json`)
- directory/filename token analysis (which segments vary, which are constant, which look like IDs or dates)

The profile (JSON) is POSTed to PHP, which runs the **detectors** and **recipe matching** (§6.2, §7) and returns the guesses. For NFS data, PHP builds the same profile format itself, so everything after this point is identical for both sources.

Advantages of scanning before upload:

- the user confirms what they have **before** spending an hour uploading 50 GB
- files the recipe ignores (`.DS_Store`, scratch files, unrelated data) are **never uploaded**
- `lastModified` comes from the user's disk, before copying resets it, so it's a more trustworthy fallback study date
- mapping mistakes are fixed while the data is still on the user's machine, not after it lands in staging

### Step 3 — "Here's what we think you have"

```
┌─ Upload #4821 · 1,284 files · 3.2 GB ─────────────────────────────────────────┐
│                                                                               │
│  ★ Matches saved recipe "ONRC BrainVision EEG (subject/visit dirs)"  94%      │
│    last used 2026-08-02 by gbook · 37 successful imports    [Use] [Ignore]   │
│                                                                               │
│  Modality        EEG                 ●●●●○  .vhdr/.vmrk/.eeg triplets          │
│  File type       BrainVision         ●●●●●  header "Brain Vision Data Exchange"│
│    subtypes      + electrode .elc    ●●●○○  12 files                           │
│  Structure       subject/study/files ●●●●○  depth 3 in 98% of files            │
│                                                                               │
│  ⚠ 26 files don't fit: 24 × .txt (task logs?), 2 × .DS_Store                  │
│                  [Treat .txt as TASK series] [Ignore] [Decide later]         │
│                                                                               │
│                                         [Change any of these]  [Next →]      │
└───────────────────────────────────────────────────────────────────────────────┘
```

Each axis is an editable dropdown. Confidence dots + a one-line reason keep it explainable (no black box). Unrecognized groups get their own mini-decision instead of failing the whole upload.

### Step 4 — Map to subject / study / series

Show the path template builder (§4.2) pre-filled with the guess, and a **side-by-side live preview**:

```
 Your files                              →  NiDB
 ───────────────────────────────────────    ──────────────────────────────────────
 S012/visit2/rest_ec.vhdr (+2)              S012  (existing: S1234ABC, enrolled ✓)
 S012/visit2/rest_eo.vhdr (+2)                └─ EEG study 2024-03-14 "visit2"  NEW
 S012/visit2/oddball.vhdr (+2)                     ├─ 1  rest_ec
                                                    ├─ 2  rest_eo
                                                    └─ 3  oddball
 S013/visit1/rest_ec.vhdr (+2)              S013  (no match — will create)     NEW
 ...
```

Per-row status: **existing / new / ambiguous / conflict** (e.g. series already archived, same UID). Ambiguous subject matches are resolved here, reusing today's `matching*id` columns.

### Step 5 — Details & gaps

Only asks for what's still missing: e.g. "12 studies have no date in the header or path — use file modification time, or enter a date?" No format conversion is offered; files are archived as the originals (D3).

### Step 6 — Dry run, upload & import

Summary counts (N subjects new/existing, N studies, N series, N files to upload, N files ignored). On confirm:

1. The wizard saves the draft recipe (a new version, D4) and creates the `uploads` row with `upload_type = 'universal'` and `upload_recipeid` pointing at that version.
2. For local data, **only the included files** are uploaded via tus, keeping their relative paths. Progress is shown per file and overall; the upload is resumable.
3. When the upload completes, `moduleUpload` applies the recipe to the staged files (parse), then the normal review/archive stages run.

The parse re-reads headers on the server for **every** file, not just the survey sample, so anything the sample missed (e.g. one file with a different PatientID) shows up in the review stage as a conflict.

### Step 7 — Save what we learned

On success:

> This import worked. Save it as a recipe so similar uploads are set up automatically?  Name: `ONRC BrainVision EEG (subject/visit dirs)`  Scope: ○ just me ● this project ○ whole instance

If the upload used an existing recipe but the user corrected something, offer "Update recipe" vs "Save as new".

## 6. How it could work inside NiDB

### 6.1 Pipeline

```
 ─────────── browser + PHP (importwizard.php) ───────────   ───────────── C++ (moduleUpload) ─────────────
 choose ──► SURVEY ──► DETECT ──► user confirms ──► UPLOAD ──► PARSE ──► REVIEW ──► ARCHIVE ──► LEARN
 folder     JS builds  PHP runs   axes + mapping   included    applies   existing   existing    recipe use
            profile    detectors  → recipe version files only  recipe    UI         archiveIO   recorded
            JSON       + matching   saved (D4)     (tus)       → staging            (as-is, D3)
```

- **SURVEY** runs in the browser (JavaScript) for local folders, or in PHP for NFS paths. Both produce the same profile JSON.
- **DETECT** and recipe matching run in PHP on the profile. No files are needed on the server yet.
- **UPLOAD** sends only the files the recipe includes. The recipe version is already chosen at this point, so the `uploads` row has `upload_recipeid` from the start.
- **PARSE** becomes recipe-driven: instead of `GetImageFileTags()` + fixed criteria, it applies the recipe's grouping and identity rules to fill `upload_subjects/studies/series`. The DICOM and BIDS paths can later become built-in recipes rather than special cases.
- **REVIEW** and **ARCHIVE** are largely unchanged. `upload_series` needs a `modality` column (and maybe `filetype`) to allow mixed-modality uploads. Archive stores the original files (D3).

### 6.2 Detectors and header readers

Because of D2, the work splits into three places. Each has a clear job so logic isn't duplicated more than necessary:

| Layer | Language | Job | Reads |
|---|---|---|---|
| **Profiler** | JavaScript (browser); PHP twin for NFS paths | Walk the folder, record paths/sizes/dates, sniff magic bytes, pull a *small fixed set* of header fields from sample files | local files via `File`/`File.slice()` |
| **Detectors** | PHP | Classify the profile along the three axes, propose identity mapping, score recipes, produce evidence text | the profile JSON only |
| **Header readers** | C++ (`moduleUpload`) | Read full headers from every uploaded file during parse (DCMTK, PAR, EDF, NIfTI, BIDS reader, …) | staged files |

A PHP detector has a simple interface:

```php
interface ImportDetector {
    public function Name();
    /* returns a list of results, each:
       [ 'kind' => 'filetype'|'layout', 'code' => 'BrainVision', 'modality' => 'EEG',
         'confidence' => 0.0-1.0, 'evidence' => [ "header 'Brain Vision Data Exchange' in 12/12 sampled .vhdr files" ],
         'filegroups' => [...], 'identityhints' => [ 'subject' => 'path:1', ... ] ] */
    public function Detect($profile);
}
```

Layout detectors (BIDS, DICOMDIR, subject/study/series tree, filename-encoded) claim *structure*; file-type detectors claim *file groups*. The existing C++ code (`GetImageTagsDCMTK`, the PAR parser, `bids::Reader`) remains the authoritative reader at parse time.

The JavaScript profiler only needs **minimal header readers**: enough to answer "what is this, and does it carry subject/study/series identity?" For DICOM that's a DICOM parser library (D7, e.g. `dicomParser`) run over the first few KB of each sampled file; for PAR, EDF, BrainVision, NIfTI, and JSON the headers are simple text or fixed-layout binary.

A data-driven **file-type registry** table covers the simple cases (extension + magic bytes + default modality + companion rule + which header reader to use, with a "generic/no header" fallback). The registry is sent to the browser so the profiler knows what to sniff. Adding a new "extension X is modality Y, group by basename" type is then an admin form, not a code change. Only formats whose headers carry identity need new reader code.

### 6.2.1 Browser capabilities and limits

- `<input type="file" webkitdirectory>` and drag-and-drop folders (`DataTransferItem.webkitGetAsEntry()`) work in all current major browsers and give the relative path, size, and `lastModified` of every file, plus read access to contents via `File.slice()`. This is enough for the whole survey.
- The File System Access API (`showDirectoryPicker()`) is Chromium-only; not needed, but could be used where available.
- Folders with hundreds of thousands of files: listing is fast, but header sniffing must stay sampled and run in batches (or a Web Worker) so the page stays responsive.
- The browser can only read what the user explicitly selects. The profile sent to PHP holds file paths and a few header values, which may contain PHI (see §8).

### 6.3 Proposed tables (sketch)

```sql
-- Known file types (seeded, admin-editable)
import_filetypes (
  filetype_id, code, name, extensions,          -- 'vhdr,vmrk,eeg'
  magic_offset, magic_bytes,                    -- optional signature
  default_modality, companion_rule,             -- 'same_basename' | 'same_dir' | 'dir_bundle' | 'none'
  reader,                                       -- detector/readTags implementation name, or 'generic'
  enabled )

-- Saved import recipes (the "learned" knowledge)
import_recipes (
  recipe_id, name, description,
  scope enum('user','project','instance'), user_id, project_id,
  modality, filetype, filesubtypes, layout,     -- the three axes, denormalized for search/display
  recipe_json,                                  -- full rules (see §6.4)
  fingerprint_json,                             -- what matching uploads look like (see §7)
  recipe_family_id,                             -- groups all versions of one recipe
  version, parent_recipe_id,                    -- version number within the family; row it was derived from
  is_current,                                   -- the version offered for new uploads
  recipe_hash,                                  -- SHA-256 of recipe_json, for integrity checks
  created_by, created_date, last_used_date,
  use_count, success_count, correction_count,
  enabled )

-- Every use of a recipe, and what the user changed
import_recipe_uses (
  use_id, recipe_id, upload_id, user_id, date,
  match_score, outcome enum('success','partial','failed','abandoned'),
  corrections_json )                            -- diff between suggested and final mapping

-- On uploads
uploads.upload_type        += 'universal'
uploads.upload_recipeid    int  NULL  -- the exact recipe VERSION row used (D4)
uploads.upload_profile     longtext  -- survey result JSON
upload_series.uploadseries_modality, uploadseries_filetype  -- per-series, for mixed uploads
```

**Versioning rules (D4):**

- A recipe row is **immutable** once any upload references it. "Editing" a recipe inserts a new row in the same `recipe_family_id` with `version + 1`, `parent_recipe_id` = the old row, and moves `is_current` to the new row.
- Recipe rows are never deleted — only `enabled = 0` (hidden from matching). An upload can always show exactly which rules produced its subject/study/series mapping.
- A one-off tweak made in the wizard (without saving to the family) still creates an unnamed version row, so `upload_recipeid` is always set for universal imports.
- `recipe_hash` lets an audit confirm that the stored rules haven't been altered since use.
- Series archived through the wizard could carry the upload ID (already traceable via the upload) so provenance runs series → upload → recipe version.

### 6.4 Recipe format (sketch)

```json
{
  "recipe_version": 1,
  "axes": { "modality": "EEG", "filetype": "BrainVision", "subtypes": ["elc"], "layout": "custom" },
  "include": ["**/*.vhdr", "**/*.vmrk", "**/*.eeg", "**/*.elc"],
  "ignore":  ["**/.DS_Store", "**/Thumbs.db"],
  "extra_groups": [
    { "match": "**/*.txt", "modality": "TASK", "filetype": "text", "group": "same_dir" }
  ],
  "grouping": { "series": "same_basename" },
  "path_template": "{subject}/{studylabel}/{seriesdesc}.{ext}",
  "tokens": {
    "subject":    { "pattern": "S\\d{3}" },
    "studylabel": { "pattern": "visit\\d+" }
  },
  "identity": {
    "subject":    { "sources": ["path:subject"], "match": ["altuid", "uid"] },
    "study": {
      "datetime": { "sources": ["header:RecordingDate", "file:mtime"] },
      "label":    { "sources": ["path:studylabel"] },
      "match":    ["label", "modality+date"]
    },
    "series": {
      "number":   { "sources": ["order:header:RecordingDate", "order:filename"] },
      "desc":     { "sources": ["path:seriesdesc"] }
    }
  }
}
```

The existing DICOM form choices map directly onto this (e.g. `subjectcriteria=patientid` → `"subject": {"sources": ["header:PatientID"]}`), so the old form can be kept as a "quick DICOM" shortcut that just builds a recipe.

### 6.5 Import decision log (D8)

Each universal import keeps a decision log **in the database**, one row per event, appended as the wizard progresses. It is tied to the upload and is kept permanently as part of the import's audit record. (`[logdir]` is not used: it holds only temporary log files from running modules.)

```sql
import_decisions (
  decision_id    bigint,
  upload_id      int,              -- the upload this belongs to
  recipe_id      int NULL,         -- recipe version in effect when the event happened
  user_id        int NULL,         -- NULL for events generated by NiDB itself
  decision_date  datetime,
  step           enum('survey','detect','match','axes','mapping','details','phiwarning','upload','parse','review','archive','recipe','outcome'),
  source         enum('nidb','user'),  -- NiDB's guess vs. the user's decision
  target         varchar(255),     -- what it applies to, e.g. 'group:*.edf', 'path:segment2', 'subject:S012'
  old_value      text NULL,        -- e.g. 'ET' (NiDB's guess)
  new_value      text NULL,        -- e.g. 'EEG' (user's choice)
  detail_json    longtext NULL     -- evidence, confidence, scores, counts
)
```

This is separate from the existing free-text `upload_logs` (which stays for module progress messages). The structured columns make it easy to query, e.g. "how often do users change `.edf` from ET to EEG?" for the instance-level hints in §7.3.

The log records:

- the survey summary: file counts, extension groups, detected file types and layout, with confidence and evidence
- the recipe match result: which recipe versions were considered, their scores and dry-run coverage, and which one (if any) the user accepted
- **every user decision**, with timestamp and user: axis changes (e.g. modality of the `.edf` group changed from ET to EEG), path segment role assignments, date-source choices, groups included or ignored, and subject/study match resolutions
- PHI warnings that were shown (D6)
- the final recipe version used (D4), and whether it was saved as a new or updated recipe
- the outcome: files uploaded, subjects/studies/series created or matched, errors

When the data **didn't fit an existing recipe**, the log is the full record of how the new mapping was built step by step, which makes it the best raw material for improving detectors and built-in recipes. The `corrections_json` in `import_recipe_uses` (§6.3) can be derived from this table (rows with `source = 'user'`), or dropped in favor of querying it directly.

## 7. Learning from successful imports

Start simple and **explainable**: similarity scoring and recorded corrections, not a trained model.

### 7.1 Fingerprints

When a recipe is saved, store a fingerprint of the upload that produced it:

- extension histogram (normalized proportions)
- detected file types and layout
- directory depth distribution
- **generalized path shapes**: each path turned into a pattern by replacing digits/dates/UIDs (`S012/visit2/rest_ec.vhdr` → `S\d{3}/visit\d/<word>.vhdr`), keep the top N shapes
- selected header facts (manufacturer, model, software version, EDF recording field format)

### 7.2 Matching a new upload

After the survey, score each enabled recipe in scope (user → project → instance) against the new profile — e.g. weighted combination of extension-histogram similarity, path-shape overlap, and header-fact matches — and then **apply the candidate recipe in dry-run** to measure the fraction of files it maps cleanly. Recipes above a threshold are offered first ("★ Matches saved recipe … 94%"). The dry-run coverage is the most honest signal and is cheap because it only touches the profile, not file contents.

### 7.3 Learning from corrections

- Every override the user makes (changed modality for a group, reassigned a path segment, changed a date source) is recorded in `import_recipe_uses.corrections_json`.
- If the same correction recurs on a recipe (say, 3 times in a row), prompt the recipe owner: "Users keep changing `.txt` from *ignore* to *TASK*. Update the recipe?"
- **Instance-level hints** from aggregated history, independent of any one recipe: "In this NiDB, `.edf` was imported as EEG 41× and ET 3×" → used as the default modality prior for `.edf`, shown as evidence.
- `success_count` vs `correction_count` gives each recipe a visible reliability score; stale or unreliable recipes sink in the rankings.

### 7.4 Sharing

Recipes are JSON, so they can be exported/imported between NiDB instances, and a set of vetted **built-in recipes** (BIDS, flat DICOM, DICOMDIR, PAR/REC, NiDB squirrel, common EEG vendors) can ship with NiDB and be seeded by the installer.

Built-in recipes can live in the repo, e.g. `src/setup/recipes/*.json`, loaded by the installer as instance-scope recipes. They are versioned like any other (D4), so an upgrade adds a new version instead of changing one that existing uploads point to.

**Deferred (D9):** collecting user-created recipes into the central NiDB repo. To keep that possible later, a recipe export should be self-contained (recipe JSON, fingerprint, any file-type registry entries it depends on, the NiDB version that created it) and contain no PHI or site-specific literal values.

## 8. Edge cases and concerns

- **PHI in paths and filenames.** Directory names often contain patient names or MRNs. The survey must not display/log more than needed, and the recipe must never store sample values — only generalized patterns. Because there is no anonymization on import (D5), PHI in headers and filenames is archived as-is. Per D6, the wizard only **warns** when the survey sees likely patient info. The warning is factual and specific (what field, how many files, e.g. "DICOM PatientName is populated in 1,184 of 1,184 sampled files", "EDF patient field populated", "path segments look like names or dates of birth"), says nothing about whether that is acceptable, and never blocks or requires acknowledgement. The warning can also be written to the upload log so there is a record that it was shown.
- **Missing study dates.** NiDB studies need a datetime. Fallback order must be explicit and visible (header → path → sidecar → file mtime → user entry), and mtime should be flagged as unreliable since copying resets it.
- **One file, many series** (e.g. multi-run EEG in one file) and **many subjects in one file** — probably out of scope initially; detect and warn.
- **Mixed modality uploads** — require per-series modality in the staging tables.
- **Duplicates / re-imports** — hash files at parse time and flag series already archived.
- **Huge uploads** — the browser survey must sample (e.g. first K files per extension group per directory shape) and batch its reads; full header parsing happens only on the server after upload.
- **Survey sample vs. reality** — the survey sees a sample, so the server-side parse can find things the preview didn't (a different PatientID, a corrupt file). These must surface as conflicts in review, not silently diverge from what the user approved.
- **PHI leaving the browser** — the profile includes paths and a few header values. Send only what detection needs, over the existing authenticated session. The profile and the decision log (§6.5) may contain paths and header values; they are stored in the database with the upload, and only the generalized fingerprint is kept with a recipe.
- **Upload interrupted** — tus resumes, but the wizard state (recipe version, file list) must survive a page reload so the user can re-select the same folder and continue.
- **Zips inside zips / vendor bundles that are directories** (`.mff`, `.ds`) — the registry needs a "directory is the file" concept.
- **Permissions** — who can create instance-scope recipes (admins only?), and project-scope (project admins?).
- **PHP 7.2 / 8 compatibility and PRG + prepared statements** for any new pages, per project conventions.

## 9. Possible phasing

1. **Survey + detect only.** `importwizard.php` with the JavaScript profiler and PHP detectors, showing "what we think you have" for a chosen folder, read-only, no upload. Low risk, immediately useful, and generates data for tuning detectors.
2. **Recipe-driven upload and parse** for non-DICOM types with the path template builder, selective tus upload, and recipe versions recorded on the upload; DICOM/BIDS stay on current code paths.
3. **Save/match recipes** (fingerprints, scoped recipes, dry-run coverage scoring).
4. **Fold DICOM/BIDS/squirrel into built-in recipes**; retire the special-cased parse paths and the per-extension logic in `GetImageFileTags()`.
5. **Learning from corrections**, instance-level priors, recipe export/import.

## 10. Open questions

Resolved questions have moved to §1.1 Decisions.

No open questions right now.

**Deferred:** logging/auditing details, such as how long decision logs and recipe versions are kept, what happens to them if uploads are purged, and whether archived series link back to their upload. These will be covered later.
