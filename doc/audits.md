# Audits

Audits are started from **Admin → Audits** (`adminaudits.php`) and run in the background by the `audit` module (`moduleAudit.cpp`). Each issue found is written to `audit_log` with an issue type, the object it belongs to (subject, enrollment, study, series, or analysis), and a description.

Only active subjects (`subjects.isactive = 1`) are audited.

## Series files (`seriesfiles`)

Scope: one project or all projects. Fix errors: optional (updates the database to match the disk).

For each MR series, compares the stored values to the files in the archive (`archivedir/<UID>/<studynum>/<seriesnum>/`):

| Files | Directory | Database fields | Issue types |
|---|---|---|---|
| Data | `<data_type>/` (not recursive) | `mr_series.numfiles`, `mr_series.series_size` | `fileCountMismatch`, `fileSizeMismatch` |
| Behavioral | `beh/` (not recursive) | `mr_series.numfiles_beh`, `mr_series.beh_size` | `fileCountMismatch`, `fileSizeMismatch` |
| QC/QA | `qa/` and `qc/` (recursive) | `mr_series.qc_numfiles`, `mr_series.qc_size` | `fileCountMismatch`, `fileSizeMismatch` |

A NULL stored value with no files on disk is not an issue. Series with a blank UID, study number, or series number are skipped. Data files aren't checked when `data_type` is blank or invalid.

## Analysis files (`analysisfiles`)

Scope: one project or all projects. Fix errors: optional.

For each analysis, compares `analysis.analysis_numfiles` and `analysis.analysis_disksize` to the files in the analysis directory (recursive). Issue types: `fileCountMismatch`, `fileSizeMismatch`. Analyses that are still running (`pending`, `processing`, `submitted`, `started`, `rerunresults`) are skipped.

## Quality control (`qualitycontrol`)

Scope: one project only. Fix errors: never. Issues are only reported, and must be fixed manually.

Any combination of the checks below can be selected (`audits.audit_options`).

### Duplicate subjects (`duplicatesubjects`)

Compares every pair of subjects enrolled in the project. Each pair is reported once, under the strongest matching rule, as a `duplicateSubject` issue on the subject with the lower ID. The log records only the UIDs and the rule, not names or DOBs.

Names (`subjects.name`) are compared as words: lowercase, accents removed, any non-letter (`^`, space, comma, hyphen) is a separator, and single letters (initials) are ignored. A DOB (`subjects.birthdate`) is only compared if it is present and valid (see *Invalid DOB*). Sex is `subjects.gender`.

| Rule | Reported as |
|---|---|
| Same name, same DOB, same sex | Likely duplicate |
| Same name, same DOB, sex differs or missing | Likely duplicate |
| First/last name transposed, same DOB (with or without the same sex) | Likely duplicate |
| Same alternate ID (`subject_altuid.altuid`) | Likely duplicate |
| Similar name (edit distance ≤ 1, or ≤ 2 for names of 10+ letters), same full DOB (YYYY-MM-DD) | Possible duplicate |
| Same name (or transposed), DOB differs or missing | Possible duplicate |

If more than 25 subjects share the same name (probably a placeholder name), one issue lists them instead of every pair.

### Duplicate studies (`duplicatestudies`)

Finds studies in the project that also exist under a different subject, in this or any other project. Each matching set of studies is reported once, as a `duplicateStudy` issue on the first of its studies in the project.

| Rule | Fields |
|---|---|
| Same StudyInstanceUID | `studies.study_uid` (non-blank) |
| Same modality and study date/time | `studies.study_modality`, `studies.study_datetime` (after 1900, and the time is not 00:00:00) |

### Empty subjects, enrollments, and studies (`empty`)

| Issue type | Object | Rule |
|---|---|---|
| `emptyStudy` | study | The study has no rows in any series table (`<modality>_series.study_id`) |
| `emptyEnrollment` | enrollment | The subject's enrollment in this project has no studies, observations, or interventions (`studies`, `observations`, `interventions` by `enrollment_id`) |
| `emptySubject` | subject | Every enrollment of the subject, in any project, is empty. Reported instead of `emptyEnrollment` |

### Missing demographics (`missingdemographics`)

| Issue type | Field | Rule |
|---|---|---|
| `missingName` | `subjects.name` | Blank, or only `^` and spaces |
| `missingSex` | `subjects.gender` | Blank, or not one of M, F, O, U |
| `missingDOB` | `subjects.birthdate` | NULL, blank, or `0000-00-00` |

### Invalid DOB (`invaliddob`)

Checks `subjects.birthdate` when it is present. Issue type: `invalidDOB`. Year-only (`YYYY-00-00`) and year-month (`YYYY-MM-00`) DOBs are valid, and are compared at the precision they have.

A DOB is invalid if it is:

- A placeholder date: `0000-01-01`, `0001-01-01`, `1000-01-01`, `1776-07-04`, `1899-12-31`, `1900-01-01`
- A day without a month (`YYYY-00-DD`)
- Month greater than 12
- Not a real calendar date (ie, `2001-02-30`)
- Before 1900
- In the future
- After the subject's first study in the project (`studies.study_datetime`)

### DICOM metadata (`dicommetadata`)

Reads the header of every `.dcm` file in each series' `dicom/` directory (`<archivedir or storage tier>/<UID>/<studynum>/<seriesnum>/dicom/`), for every series table with a `series_num`. MR series whose `data_type` is not `dicom` are skipped, as are series with no `.dcm` files. File counts aren't checked (see *Series files*).

One issue per series per field is logged, giving how many files don't match (ie, *1 of 101 files*), the values found, and example filenames.

| Issue type | DICOM tag | Must match |
|---|---|---|
| `dicomPatientIDMismatch` | PatientID (0010,0020) | The subject's `subjects.uid`, or any of its `subject_altuid.altuid` (or the SHA1 of the PatientID). Not checked when every file in the series has a blank PatientID (anonymized) |
| `dicomStudyDateTimeMismatch` | StudyDate (0008,0020) + StudyTime (0008,0030) | `studies.study_datetime`, to the second |
| `dicomSeriesNumberMismatch` | SeriesNumber (0020,0011) | `<modality>_series.series_num` |
| `dicomUnreadable` | | A `.dcm` file that can't be read as DICOM |
