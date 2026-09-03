# BIDS import into NiDB

## Goal

Read an on-disk [BIDS](https://bids.neuroimaging.io/) dataset and **archive it into NiDB** (the MySQL-backed `subject → study → series` model), not into the squirrel package format. The work is split into two layers so the BIDS-parsing logic stays free of any NiDB/database coupling and can be tested in isolation.

```
  on-disk BIDS dataset
          │
          ▼
  ┌───────────────────────────────┐   Layer 1 — the reader (src/nidb/bids.{h,cpp})
  │  bids::Reader::readDataset()   │   • pure BIDS parsing, NO squirrel, NO DB
  │      → bids::BidsDataset       │   • resolves JSON inheritance into each
  └───────────────────────────────┘     Acquisition.resolvedMetadata
          │
          │  neutral in-memory model (bids::BidsDataset)
          ▼
  ┌───────────────────────────────┐   Layer 2 — your import (you write this)
  │  your import functions         │   • walk the BidsDataset
  │      → archiveIO calls         │   • the ONLY layer that touches NiDB / the DB
  └───────────────────────────────┘
          │
          ▼
  NiDB archive (subjects / studies / series rows + files on disk)
```

Layer 1 exists today. Section 2 below shows how to traverse a `BidsDataset` so you can write Layer 2 (your own import) against it.

---

## Layer 1 — the reader (already implemented)

`src/nidb/bids.h` defines `namespace bids` with a neutral, dependency-light object model. It has no NiDB, squirrel, or SQL dependencies (only `QtCore`).

```cpp
bids::Reader reader;
bids::BidsDataset ds;
QString err;
if (!reader.readDataset("/path/to/bids", ds, err)) { /* handle err */ }
```

Key structures you will consume (see `bids.h` for full definitions):

| Structure | Holds |
|---|---|
| `BidsDataset` | `subjects` map, `participantRows`, `datasetDescription`, dataset name/version, `topLevelFiles`, `bidsCompliant` + `complianceIssues` |
| `SubjectRecord` | `id`, `participantRow`, `sessions`, `acquisitionsWithoutSession`, subject-level `scansTable`, `looseFiles` |
| `SessionRecord` | `id`, `acquisitions`, session-level `scansTable`, `looseFiles` |
| `Acquisition` | `datatype`, `suffix`, `entities`, `primaryDataPath`, companion paths (`bvalPath`/`bvecPath`/`eventsPath`), `files`, **`resolvedMetadata` (merged JSON via BIDS inheritance)**, matched `scansRow` |

The reader has already applied the BIDS **inheritance principle**, so `Acquisition.resolvedMetadata` is the fully-merged sidecar metadata for that acquisition — you do not need to re-walk JSON files.

---

## 2. Traversing a BidsDataset

You are writing your own BIDS → NiDB import against the `bids::BidsDataset` produced by Layer 1. This section shows how to walk it and reach **every piece of metadata and every file (with full paths)**. All containers are `QtCore` types, and `QMap` iterates in sorted key order, so traversal is deterministic.

### Dataset-level fields

```cpp
const bids::BidsDataset &ds = /* filled by reader.readDataset(...) */;

ds.rootPath;             // absolute dataset root
ds.name;                 // dataset_description.json "Name"
ds.bidsVersion;          // "BIDSVersion"
ds.datasetDescription;   // QJsonObject: the full dataset_description.json

// participants.tsv as a table
ds.participantColumns;                          // QStringList of column names
for (const QVariantMap &row : ds.participantRows)
    row.value("participant_id").toString();     // e.g. "sub-01"

// files sitting at the dataset root (dataset_description.json, README, ...)
for (const bids::FileRecord &f : ds.topLevelFiles)
    f.absolutePath;                             // full path on disk
```

### Compliance — importing "BIDS-like" datasets

The reader is **tolerant by design**: a dataset with a correct `sub-XX/ses-YY` layout and valid sidecars, but missing root metadata, still parses so it can be imported. `readDataset()` returns `false` only on a hard read failure, not on a compliance deviation — so check the compliance fields, not just the return value.

```cpp
ds.bidsCompliant;        // bool: false if a BIDS-REQUIRED element is missing/invalid
ds.complianceIssues;     // QStringList: every deviation found (see below)
```

- `bidsCompliant` is set to `false` only when a **BIDS-required** element is missing or invalid. Currently that means `dataset_description.json` is absent/unparseable, or lacks its required `Name` / `BIDSVersion`.
- `complianceIssues` lists **every** deviation found, including **RECOMMENDED-but-absent** files (e.g. `participants.tsv`). A recommended-only deviation is recorded here **without** flipping `bidsCompliant` to `false`.

So a dataset can be `bidsCompliant == true` and still have entries in `complianceIssues`. Decide in your import how strict to be — e.g. import anything that parses, but surface `complianceIssues` to the user, and perhaps gate on `bidsCompliant` for a "strict" mode. `bids::PrintDataset(ds)` prints both the flag and the issue list.

### Iterating subjects

`ds.subjects` is a `QMap<QString, SubjectRecord>` keyed by the **bare** subject id (`"01"`, no `sub-` prefix).

```cpp
for (auto it = ds.subjects.constBegin(); it != ds.subjects.constEnd(); ++it) {
    const QString &subid          = it.key();     // "01"
    const bids::SubjectRecord &subj = it.value();

    subj.id;                                       // "01"

    // demographics from participants.tsv (empty QVariantMap if no row matched)
    if (!subj.participantRow.isEmpty()) {
        subj.participantRow.value("age").toString();
        subj.participantRow.value("sex").toString();
    }

    // subject-level scans.tsv, if present
    subj.scansTable.rows;                          // QList<QVariantMap>

    // files under sub-XX/ that were not groupable into an acquisition
    for (const bids::FileRecord &f : subj.looseFiles)
        f.absolutePath;
}
```

### Subject → sessions → acquisitions

**Session vs. sessionless datasets.** In BIDS the `ses-<label>` directory level is *optional*. A **session dataset** inserts it — `sub-01/ses-1/anat/…`, `sub-01/ses-2/anat/…` — to group data acquired across multiple visits/scanning sessions for one subject. A **sessionless dataset** omits it entirely — `sub-01/anat/…` — which is the common single-visit case. The choice is per-subject (a subject either has `ses-` directories or does not; BIDS does not allow data files at both levels for the same subject), but different subjects — and different datasets — may differ, so import code must handle both. In NiDB terms, **each session maps to one study** (`VisitType = ses` label), and a **sessionless subject maps to a single study**.

The reader mirrors that split, so acquisitions live in **two** places: sessionless subjects put them in `subj.acquisitionsWithoutSession`; session subjects put them under each `subj.sessions[ses].acquisitions`. Handle both.

> **The reader routes per file, not per dataset or per subject — and enforces nothing.** A single `BidsDataset` freely mixes session and sessionless *subjects* (e.g. `sub-01` has `ses-` directories while `sub-02` does not); each file lands in the right bucket independently, and there is no dataset- or subject-level "has sessions" flag. The per-subject exclusivity above is a **BIDS spec expectation, not a `BidsDataset` invariant**: if one subject has data both under `ses-*/` and not, the reader will populate *both* `subj.sessions` and `subj.acquisitionsWithoutSession` for that subject without validating or warning. So always walk both containers for **every** subject — never assume a dataset, or even a subject, is uniformly one shape.

```
  session dataset                     sessionless dataset
  sub-01/                             sub-01/
    ses-1/                              anat/  sub-01_T1w.nii.gz
      anat/  sub-01_ses-1_T1w.nii.gz    func/  sub-01_task-rest_bold.nii.gz
      func/  sub-01_ses-1_task-...      →  subj.acquisitionsWithoutSession
    ses-2/
      anat/  sub-01_ses-2_T1w.nii.gz
  →  subj.sessions["1"], subj.sessions["2"]
```

```cpp
// sessionless acquisitions (no ses-XX directories)
for (const bids::Acquisition &acq : subj.acquisitionsWithoutSession)
    handleAcq(QString(), acq);                     // your function; "" = no session

// per-session acquisitions
for (auto sit = subj.sessions.constBegin(); sit != subj.sessions.constEnd(); ++sit) {
    const bids::SessionRecord &sess = sit.value();
    sess.id;                     // "1" (bare, no ses- prefix)  — maps to a NiDB study
    sess.scansTable.rows;        // session-level scans.tsv, if any
    for (const bids::Acquisition &acq : sess.acquisitions)
        handleAcq(sess.id, acq);
}
```

> Note: range-based `for` over a Qt `QMap` yields the **values**; use the iterator form (`it.key()` / `it.value()`) when you also need the key.

### An acquisition's metadata

An `Acquisition` groups one data file with its sidecars — the natural unit for a NiDB series.

```cpp
acq.key;         // canonical id, e.g. "sub-01_ses-1_task-rest_run-1_bold"
acq.datatype;    // "anat", "func", "dwi", "eeg", ...  (≈ modality)
acq.suffix;      // "T1w", "bold", "dwi", ...
acq.entities;    // QMap<QString,QString>: {"sub":"01","task":"rest","run":"1",...}
acq.entities.value("task");
acq.entities.value("run");

// merged JSON metadata — BIDS inheritance already applied (root + datatype + sidecar)
const QJsonObject &meta = acq.resolvedMetadata;
meta.value("RepetitionTime").toDouble();
meta.value("EchoTime").toDouble();
for (const QString &k : meta.keys())
    meta.value(k);              // a QJsonValue: scalar, array, or object

// matching row from scans.tsv (acq_time, etc.); empty QVariantMap if none matched
acq.scansRow.value("acq_time").toString();
```

### Flattening metadata to DICOM-style tags

`resolvedMetadata` is a `QJsonObject`. `Acquisition::ResolvedMetadataAsMap()` flattens it into a `QHash<QString,QString>` of DICOM-style tag/value pairs — the same type as the `tags` argument of `ArchiveNiftiSeries`, so it plugs straight in:

- nested objects are flattened with dot-joined keys (`Foo.Bar`)
- array values are joined into a single DICOM multi-valued (VM) string with backslash (`\`) separators, preserving element order

```cpp
QHash<QString, QString> tags = acq.ResolvedMetadataAsMap();

tags.value("RepetitionTime");   // "2"       (2.0 prints without a trailing ".0")
tags.value("EchoTime");         // "0.03"
tags.value("ImageType");        // "ORIGINAL\PRIMARY\M\ND"   (array -> DICOM VM)

// plugs straight into NiDB's series archiving
io->ArchiveNiftiSeries(subjectRowID, studyRowID, -1, seriesNum, tags, files);
```

> Best fit for the flat, scalar / scalar-array shape of MR sidecars. Deeply nested structures — arrays of arrays, or arrays of objects, more common in EEG/MEG/microscopy — are collapsed into a single `\`-joined string and cannot be losslessly reconstructed; for those, read the raw `acq.resolvedMetadata` `QJsonObject` directly.

### An acquisition's files (full paths)

`acq.files` holds **every** file in the group (primary + all sidecars) as `FileRecord`s, each with a full `absolutePath` — this is what you copy/archive. The `primaryDataPath` / `bvalPath` / … pointers are dataset-**relative**, so resolve them against `ds.rootPath`.

```cpp
// every file in the acquisition, full path
for (const bids::FileRecord &f : acq.files) {
    f.absolutePath;   // full path on disk   <-- use this for archiving/copying
    f.extension;      // ".nii.gz", ".json", ".bval", ...
    f.suffix;         // "bold", "events", ...
}

// specific roles (relative paths -> resolve to full paths)
const QString primary = QDir(ds.rootPath).filePath(acq.primaryDataPath);   // main data file
if (!acq.bvalPath.isEmpty())   QDir(ds.rootPath).filePath(acq.bvalPath);
if (!acq.bvecPath.isEmpty())   QDir(ds.rootPath).filePath(acq.bvecPath);
if (!acq.eventsPath.isEmpty()) QDir(ds.rootPath).filePath(acq.eventsPath);
```

### One loop over every acquisition in the dataset

The common case — visit all acquisitions with their subject/session context:

```cpp
for (auto sit = ds.subjects.constBegin(); sit != ds.subjects.constEnd(); ++sit) {
    const bids::SubjectRecord &subj = sit.value();

    for (const bids::Acquisition &acq : subj.acquisitionsWithoutSession)
        importSeries(subj, QString(), acq);        // your import function

    for (auto ssit = subj.sessions.constBegin(); ssit != subj.sessions.constEnd(); ++ssit)
        for (const bids::Acquisition &acq : ssit.value().acquisitions)
            importSeries(subj, ssit.value().id, acq);
}
```

### Quick dump

To eyeball a parsed dataset — the full hierarchy, merged metadata, and every file path — use the built-in:

```cpp
qDebug().noquote() << bids::PrintDataset(ds);
```

---

## BIDS → NiDB mapping reference

A suggested mapping from the parsed structures onto NiDB, regardless of how you structure the import code:

| BIDS concept | Reader (`BidsDataset`) | NiDB target |
|---|---|---|
| `sub-XX` / `participants.tsv` row | `SubjectRecord.id`, `.participantRow` | subject (+ project enrollment) |
| `ses-YY` directory | `SubjectRecord.sessions[YY]` | study, `VisitType = YY` |
| session-less subject | `SubjectRecord.acquisitionsWithoutSession` | a single study |
| datatype dir (`anat`,`func`,`dwi`,…) | `Acquisition.datatype` | study/series `Modality` |
| grouped acquisition (data + companions) | `Acquisition.primaryDataPath` + `.files` | series (+ archived files) |
| inherited JSON sidecars | `Acquisition.resolvedMetadata` (already merged) | series tags/params |
| `scans.tsv` `acq_time` | `Acquisition.scansRow` | study/series datetime |
| `derivatives/` | **not parsed by the reader** (excluded) | pipelines + analyses (future) |
| `phenotype/` | **not parsed by the reader** (excluded) | subject observations (future) |

The relevant `archiveIO` entry points on the NiDB side are `GetSubject`/`CreateSubject`, `GetOrCreateEnrollment`, `GetStudy`/`CreateStudy`, and `ArchiveNiftiSeries(subjectRowID, studyRowID, -1, seriesNum, tags, files)` — whose `QHash<QString,QString> tags` argument is the natural home for the flattened `resolvedMetadata` (see `Acquisition::ResolvedMetadataAsMap()` above).

---

## Things to decide in your import

Not blockers for traversal — decisions that shape the import:

1. **Subject matching.** How does `participant_id` (e.g. `sub-01`) resolve to a NiDB subject — always create new, or match an existing subject by PatientID/alt-UID?
2. **Non-MR modalities.** `ArchiveNiftiSeries` covers `anat/func/dwi/fmap/perf`. `eeg/ieeg/meg/pet/micr/motion/nirs/beh` need their own archive paths (some exist, some don't). Decide per-modality: real archive vs. store-as-files.
3. **Study datetime.** Prefer `scans.tsv acq_time` (`acq.scansRow`), fall back to `sessions.tsv acq_time`, else leave null.
4. **`derivatives/` and `phenotype/`.** The reader deliberately excludes these from the raw index. If you need them, either extend `bids::Reader` to read them into the `BidsDataset`, or handle them in a separate pass.
5. **Idempotency / re-import.** Behavior when the same dataset is imported twice — match-and-skip, update, or duplicate. `GetStudy`/`GetSubject` give a natural hook for match-and-reuse.

---

*Layer 1 (`bids.{h,cpp}`) is squirrel-free and DB-free by design; keep it that way. All NiDB / `archiveIO` / SQL coupling belongs in your import layer, never in `bids.{h,cpp}`.*
