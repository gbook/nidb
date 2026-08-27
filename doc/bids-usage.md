# BIDS import into NiDB

## Goal

Read an on-disk [BIDS](https://bids.neuroimaging.io/) dataset and **archive it into NiDB** (the MySQL-backed `subject → study → series` model), not into the squirrel package format. The work is split into two layers so the BIDS-parsing logic stays free of any NiDB/database coupling and can be tested in isolation.

```
  on-disk BIDS dataset
          │
          ▼
  ┌───────────────────────────────┐   Layer 1 — the reader (src/nidb/bids.{h,cpp})
  │  bids::Reader::readDataset()   │   • pure BIDS parsing, NO squirrel, NO DB
  │      → bids::BidsDataset      │   • resolves JSON inheritance into each
  └───────────────────────────────┘     Acquisition.resolvedMetadata
          │
          │  neutral in-memory model (bids::BidsDataset)
          ▼
  ┌───────────────────────────────┐   Layer 2 — the translator (src/nidb/bidsImport.{h,cpp}, TBD)
  │  bidsImport::Import()          │   • walks the BidsDataset
  │      → archiveIO calls         │   • the ONLY layer that touches NiDB / the DB
  └───────────────────────────────┘
          │
          ▼
  NiDB archive (subjects / studies / series rows + files on disk)
```

Layer 1 exists today. Layer 2 is sketched below — **interface only, no implementation yet**.

---

## Layer 1 — the reader (already implemented)

`src/nidb/bids.h` defines `namespace bids` with a neutral, dependency-light object model. It has no NiDB, squirrel, or SQL dependencies (only `QtCore`).

```cpp
bids::Reader reader;
bids::BidsDataset ds;
QString err;
if (!reader.readDataset("/path/to/bids", ds, err)) { /* handle err */ }
```

Key structures the translator consumes (see `bids.h` for full definitions):

| Structure | Holds |
|---|---|
| `BidsDataset` | `subjects` map, `participantRows`, `datasetDescription`, dataset name/version |
| `SubjectRecord` | `id`, `participantRow`, `sessions`, `acquisitionsWithoutSession`, subject-level `scansTable` |
| `SessionRecord` | `id`, `acquisitions`, session-level `scansTable` |
| `Acquisition` | `datatype`, `suffix`, `entities`, `primaryDataPath`, companion paths (`bvalPath`/`bvecPath`/`eventsPath`), `files`, **`resolvedMetadata` (merged JSON via BIDS inheritance)**, matched `scansRow` |

The reader has already applied the BIDS **inheritance principle**, so `Acquisition.resolvedMetadata` is the fully-merged sidecar metadata for that acquisition — the translator does not need to re-walk JSON files.

---

## Layer 2 — the translator (interface sketch)

Proposed as a new NiDB-side class in `src/nidb/bidsImport.{h,cpp}`. This is where all NiDB coupling lives; the reader stays clean. It drives NiDB's existing `archiveIO` API — the same one the DICOM/Nifti import paths use.

```cpp
// bidsImport.h  — NiDB-side; depends on nidb + archiveIO.
//                 Keep this OUT of bids.h so the reader stays DB-free.
#include "bids.h"        // Layer 1: the squirrel-free reader (namespace bids)
#include "nidb.h"
#include "archiveio.h"

/* Caller-supplied import policy. */
struct BidsImportOptions {
    int     projectRowID     = -1;      // destination NiDB project (required)
    QString subjectMatchCriteria;       // how participant_id maps to an existing
                                        //   subject (PatientID/AltUID/...), or create new
    QString defaultModality  = "MR";    // fallback when a datatype has no mapping
    bool    importDerivatives = true;   // derivatives/ -> pipelines + analyses (reader gap, see below)
    bool    importPhenotype   = true;   // phenotype/  -> subject observations   (reader gap, see below)
    bool    dryRun            = false;  // parse + plan, write nothing to the DB or disk
};

/* What the import did (for logging / the import module UI). */
struct BidsImportResult {
    int         subjectsTouched = 0;    // created or matched
    int         studiesCreated  = 0;
    int         seriesArchived  = 0;
    QStringList warnings;
    bool        success = false;
};

class bidsImport {
public:
    explicit bidsImport(nidb *n);

    /* Top level: read a BIDS directory (Layer 1) and archive it into NiDB (Layer 2). */
    bool Import(const QString &bidsDir,
                const BidsImportOptions &opts,
                BidsImportResult &result,
                QString &msg);

    /* Same, but against an already-parsed BidsDataset (lets a caller inspect/validate the
       BidsDataset first, or reuse one built elsewhere). */
    bool ImportDataset(const bids::BidsDataset &ds,
                     const BidsImportOptions &opts,
                     BidsImportResult &result,
                     QString &msg);

private:
    /* --- per-level translators over the neutral BidsDataset --- */

    /* participants.tsv row / sub-XX  ->  NiDB subject (+ enrollment in the project). */
    bool TranslateSubject(const bids::SubjectRecord &subj,
                          const BidsImportOptions &opts,
                          int &subjectRowID,
                          int &enrollmentRowID,
                          QString &msg);

    /* ses-YY (or the no-session bucket)  ->  NiDB study. */
    bool TranslateStudy(const bids::SessionRecord &sess,       // pass a synthetic record for no-session
                        int subjectRowID,
                        int enrollmentRowID,
                        const BidsImportOptions &opts,
                        int &studyRowID,
                        int &studyNum,
                        QString &msg);

    /* one Acquisition (primary + companions + resolvedMetadata)  ->  NiDB series. */
    bool TranslateSeries(const bids::Acquisition &acq,
                         int subjectRowID,
                         int studyRowID,
                         int seriesNum,
                         QString &msg);

    /* --- helpers --- */

    /* BIDS datatype dir (anat/func/dwi/eeg/...) -> NiDB modality (MR/EEG/PET/...). */
    QString ModalityForDatatype(const QString &datatype);

    /* Acquisition.resolvedMetadata (QJsonObject) -> flat series tags for archiveIO.
       Scalars pass through; integer-valued doubles drop the decimal; arrays/objects
       are kept as compact JSON so nothing is silently lost. */
    QHash<QString, QString> FlattenMetadata(const QJsonObject &meta);

    /* Pick the study/series datetime from scansRow.acq_time (or sessions.tsv). */
    QString AcqTimeFor(const bids::Acquisition &acq);

    nidb      *n;
    archiveIO *aio;
};
```

### How the translators map onto `archiveIO`

The translator is a thin adapter — it converts `bids::` structs into the arguments NiDB's archive API already expects:

| Step | `bids::` input | `archiveIO` call (existing API) | Output |
|---|---|---|---|
| Subject | `SubjectRecord` (+`participantRow` sex/age) | `GetSubject(...)` else `CreateSubject(PatientID, …, subjectRowID, subjectUID)` | `subjectRowID` |
| Enrollment | subject + `opts.projectRowID` | `GetOrCreateEnrollment(subjectRowID, projectRowID, enrollmentRowID)` | `enrollmentRowID` |
| Study | `SessionRecord` (+`acq_time`, `ses` label) | `GetStudy(...)` else `CreateStudy(subjectRowID, enrollmentRowID, StudyDateTime, …, Modality, …, studyRowID, studyNum)` | `studyRowID`, `studyNum` |
| Series (MR) | `Acquisition` + `FlattenMetadata(resolvedMetadata)` | `ArchiveNiftiSeries(subjectRowID, studyRowID, -1, seriesNum, tags, files)` | series row + files on disk |
| Series (non-MR) | `Acquisition` (eeg/meg/…) | `ArchiveEEGSeries(...)` / other `Archive*Series` paths | series row |

`ArchiveNiftiSeries`'s `QHash<QString,QString> tags` parameter is the natural home for the inherited BIDS metadata — `FlattenMetadata()` produces exactly that shape.

---

## BIDS → NiDB mapping reference

| BIDS concept | Reader (`BidsDataset`) | NiDB target |
|---|---|---|
| `sub-XX` / `participants.tsv` row | `SubjectRecord.id`, `.participantRow` | subject (+ project enrollment) |
| `ses-YY` directory | `SubjectRecord.sessions[YY]` | study, `VisitType = YY` |
| session-less subject | `SubjectRecord.acquisitionsWithoutSession` | a single study |
| datatype dir (`anat`,`func`,`dwi`,…) | `Acquisition.datatype` | study/series `Modality` via `ModalityForDatatype` |
| grouped acquisition (data + companions) | `Acquisition.primaryDataPath` + `.files` | series (+ archived files) |
| inherited JSON sidecars | `Acquisition.resolvedMetadata` (already merged) | series tags/params (flattened) |
| `scans.tsv` `acq_time` | `Acquisition.scansRow` | study/series datetime |
| `derivatives/` | **not parsed by the reader yet** | pipelines + analyses |
| `phenotype/` | **not parsed by the reader yet** | subject observations |

---

## Open questions for integration

These are decisions to settle when Layer 2 is actually built — not blockers for the sketch:

1. **Subject matching.** How does `participant_id` (e.g. `sub-01`) resolve to a NiDB subject — always create new, or match an existing subject by PatientID/alt-UID? This is `opts.subjectMatchCriteria` and mirrors the DICOM import's match logic.
2. **Non-MR modalities.** `ArchiveNiftiSeries` covers `anat/func/dwi/fmap/perf`. `eeg/ieeg/meg/pet/micr/motion/nirs/beh` need their own archive paths (some exist, some don't). Decide per-modality: real archive vs. store-as-files.
3. **Study datetime.** Prefer `scans.tsv acq_time`, fall back to `sessions.tsv acq_time`, else leave null — confirm the precedence.
4. **`derivatives/` and `phenotype/`.** The old squirrel reader handled these; the neutral reader does not. Either extend `bids::Reader` to read them into the `BidsDataset`, or handle them in a separate translator pass. (Recommendation: extend the reader so Layer 2 stays a pure adapter.)
5. **Idempotency / re-import.** Behavior when the same dataset is imported twice — match-and-skip, update, or duplicate. `GetStudy`/`GetSubject` give a natural hook for match-and-reuse.
6. **`dryRun` reporting.** In dry-run mode, `BidsImportResult` should still be fully populated (planned counts + warnings) so the import module can preview before committing.

---

*Layer 1 (`bids.{h,cpp}`) is squirrel-free and DB-free by design; keep it that way. All NiDB/`archiveIO`/SQL coupling belongs in Layer 2 (`bidsImport.{h,cpp}`).*
