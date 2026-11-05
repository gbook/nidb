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

Scope: one project only. Fix errors: never, except for the optional fixes of the *Missing demographics* check. All other issues are only reported, and must be fixed manually.

Any combination of the checks below can be selected (`audits.audit_options`).

### Duplicate subjects (`duplicatesubjectsproject`, `duplicatesubjects`)

Two versions of the same check, which differ only in which alternate IDs are compared:

| Option | Alternate IDs compared | Selected by default |
|---|---|---|
| `duplicatesubjectsproject` | Only alternate IDs recorded for an enrollment in this project (`subject_altuid.enrollment_id`) | Yes |
| `duplicatesubjects` | All of the subjects' alternate IDs, from any project | No |

If both are selected, only `duplicatesubjects` runs.

Every pair of subjects enrolled in the project is compared. Each pair is matched by its strongest rule. Pairs that share a subject are merged into one group (if A matches B, and B matches C, then A, B, and C are one group). Each group is one `duplicateSubject` issue, on the subject with the lowest ID. Every subject in the group is recorded in `audit_log_objects`, with the subjects it matched and the rule. The log records only UIDs and rules, not names or DOBs. Clicking the issue shows the group's subjects with their current name, DOB, sex, alternate IDs, enrollments, and studies.

A group is reported as *Likely duplicates* if any of its pairs matched a likely rule, otherwise as *Possible duplicates*.

Names (`subjects.name`) are compared as words: lowercase, accents removed, any non-letter (`^`, space, comma, hyphen) is a separator, and single letters (initials) are ignored. A DOB (`subjects.birthdate`) is only compared if it is present and valid (see *Invalid DOB*). Sex is `subjects.gender`.

| Rule | Strength |
|---|---|
| Same name, same DOB, same sex | Likely |
| Same name, same DOB, sex differs or missing | Likely |
| First/last name transposed, same DOB (with or without the same sex) | Likely |
| Same alternate ID (`subject_altuid.altuid`) | Likely |
| Similar name (edit distance ≤ 1, or ≤ 2 for names of 10+ letters), same full DOB (YYYY-MM-DD) | Possible |
| Same name (or transposed), DOB differs or missing | Possible |

If more than 25 subjects share a name or an alternate ID (probably a placeholder), each is linked to the first of them rather than to every other one, so they still form one group.

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
| `missingSex` | `subjects.sex`, `subjects.gender` | Either one is blank, or not one of M, F, O, U. One issue per subject covers both columns |
| `missingDOB` | `subjects.birthdate` | NULL, blank, or `0000-00-00` |

#### Fixes (`fixsex`, `fixname`, `fixdob`)

Each missing item can optionally be fixed. The fixes are shown under the *Missing demographics* check, are never selected by default, and are only used when the check is selected. They are stored in `audits.audit_options` with the checks.

| Option | Issue type | Fix |
|---|---|---|
| `fixsex` | `missingSex` | A blank `sex` or `gender` is set to the other, if the other is M, F, O, or U. An unrecognized value is never overwritten |
| `fixname` | `missingName` | `name` is set to `<ID>^` (last name only), where the ID is the subject's primary alternate ID for its enrollment in this project (`subject_altuid.isprimary = 1`), otherwise the UID |
| `fixdob` | `missingDOB` | `birthdate` is set to an estimated `YYYY-MM-00`, from the subject's earliest study, in any project, with both an age at scan (`studies.study_ageatscan`, more than 0 and less than 150) and a study date (`studies.study_datetime`, after 1900). The DOB is the study date minus the age. A whole-number age (ie, 45 from a DICOM PatientAge of `045Y`) means the subject was 45 to 46 years old, so 45.5 years is subtracted. If there is no such study, or the estimate is before 1900, the DOB is left blank |

A fixed issue has `issue_fixed = 1`, and `issue_fixedmessage` records the old and new values (and for the DOB, the study it was estimated from). An issue that couldn't be fixed (ie, sex and gender both blank) has `issue_fixed = 0`, and the reason. When the fixes aren't selected, `issue_fixed` is NULL.

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
