# Non-imaging search/export (`nonimaging.php`) — design

Status: phases 1–5 built (CSV long and wide layouts, Timeseries, Files zip). See *Build phases*.

## Flow

The page is one page with a step bar: **Data type → Format → Observations → Subjects → Preview → Download**. Selections are kept in the browser; the server provides AJAX endpoints for the lists, the preview, and the download.

1. **Data type** — Observations or Interventions. The user can view/export only one at a time. Only observations are built for now; interventions are shown as disabled ("coming soon").
2. **Format** — the user picks one export format before choosing observations. Each format card shows how many observations/items it has.
3. **Observations** — choose which observations to export, limited to those compatible with the chosen format. See *Finding observations of another format* below.
4. **Subjects** — "All subjects", or choose from a grid of the project's enrolled subjects (UID, alternate UIDs, enrollment group, number of matching observations) with checkbox selection, a filter box, and paste-a-list-of-UIDs.
5. **Date range** — optional start/end date filter on `observation_startdate`.
6. **Preview** — the server re-validates the whole request (permissions, every selected item matches the format, subjects belong to the project) and returns totals and a sample of the export.
7. **Download** — the selection can be thousands of names, so it's sent by POST. This is read-only, so Post/Redirect/GET isn't needed.

## Schema mapping

- Project scope: `observations` has no `project_id`; it's always joined through `enrollment.project_id`.
- **Instrument observations**: `observations.instrumentitem_id` → `instrument_items.item_type` → `instruments.project_id`.
- **Unaffiliated observations**: `instrumentitem_id` is null or 0, or points to an instrument item that no longer exists (its type is unknown, so it's assumed to be a regular value). These are always treated as CSV (plain values, never files or timeseries) and are grouped by `observation_name`.
- Observations from deleted surveys (`observation_surveys.survey_status = 7`) are always excluded. There is no user-facing survey status filter.

## Formats

| Format | `item_type` | Export |
|--------|-------------|--------|
| CSV | `enum`, `int`, `double`, `string`, `datetime`, blank, and all unaffiliated observations | `.csv`, long or wide layout |
| Timeseries | `timeseries` | `.zip` of `.csv` files, per subject or per observation (data points from `timeseries` by `observation_id`) |
| Files | `image`, `csv`, `json` | `.zip` of the files (`files` by `observation_fileid`) |

### CSV long layout

One row per observation. Columns: UID, alternate UID, instrument, observation name, value, start date (UTC), end date (UTC), timezone offset, duration, rater, notes, survey visit, survey instance.

### CSV wide layout

One column per selected observation. Instrument item columns are named `<instrument>.<item>`, because two instruments can have items with the same name; unaffiliated columns use the observation name. Columns are ordered by instrument and item order, then unaffiliated names alphabetically. Duplicate headers get ` (2)`, ` (3)`, ... added. Every selected observation gets a column, even if it has no data.

A **repeated observations** setting controls the rows:

- **All**: one row per subject per survey (`observationsurvey_id`); observations without a survey are grouped by date (UTC). Fixed columns: UID, AltUID, SurveyVisit, SurveyInstance, DateUTC (the survey start date, or the observation date). If the same observation is repeated within one survey/date, the extra values go in additional rows for that survey/date. Rows are ordered by subject, then date.
- **First** / **Last**: one row per subject (only subjects with data), with each observation's earliest/latest value by observation start date. Fixed columns: UID, AltUID. Optionally a `<column>.DateUTC` column after each observation with that value's start date.

The preview shows the number of rows and columns, and warns above Excel's limit of 16,384 columns. For **All**, the row count is calculated exactly in SQL (for each subject's survey/date, the count of its most repeated observation).

### Timeseries (zip of csv files)

The data points are in the `timeseries` table (`observation_id`, `time`, and one of `value_double`, `value_int`, `value_string`). The export is a `.zip` with one `.csv` file per subject or per observation, plus `manifest.csv`. In every file the first column is `DateTimeUTC` (`YYYY-MM-DD HH:MM:SS.fff`), and there is one row per time, in time order.

- **Per subject**: one file per subject with data, `<UID>.csv`. One column per selected observation, named `<instrument>.<item>`, in instrument and item order. Every selected observation has a column, even if the subject has no data for it.
- **Per observation**: one file per selected observation with data, `<instrument>.<item>.csv`. One column per subject with data, named by UID, in UID order. Rows only line up where subjects have points at exactly the same time, so this file is mostly empty cells unless the recordings share clock times.
- A subject can have several observations of the same item (for example, recordings on different days). They share the item's column. If two points land in the same column at the same time, the extra points go in additional rows with the same time.
- `manifest.csv` has one row per exported observation: File, Column, UID, AltUID, Instrument, Observation, StartDateUTC, EndDateUTC, TimezoneOffset, SurveyVisit, SurveyInstance, DataPoints.
- File names are made safe the same way as the Files format, and duplicates get `_2`, `_3`, ... added.
- The date range filters observations by `observation_startdate`, like the other formats. It doesn't trim the data points within an observation.
- `timeseries.time` is a `TIMESTAMP`, which MariaDB returns in the session time zone, so the export sets the session time zone to UTC first. That also keeps paging by time correct across daylight saving changes.
- **How it's read**: each file's observations are merged in time order (a k-way merge with a heap). Each observation's points are read a page at a time using the unique `(observation_id, time)` key (`time > <last time read> order by time limit <page>`), so only one page per observation is in memory. The page size is 500,000 points divided by the number of observations in the file, between 100 and 10,000.
- The preview shows the number of files, data points, observations, and subjects with data; the list of files with their columns, observations, data points, and time range; and the first 500 rows of the first file with data.

### Files (zip) layout

`<UID>/<instrument>/<observation name>/<YYYYMMDD_HHMMSS>_<original filename>`, plus a `manifest.csv` at the root listing every file with its metadata.

- Each path part has characters that aren't allowed on Windows/macOS/Linux (`/ \ : * ? " < > |`, control characters) replaced with `_`, and leading/trailing dots and spaces removed, so a file name can't escape its folder. Observations with no start date use `nodate` as the prefix.
- Duplicate paths (not case sensitive) get `_2`, `_3`, ... added before the extension.
- Observations with no file, or whose file is missing, aren't in the zip, but are listed in `manifest.csv` with the status `missing file`.
- `manifest.csv` columns: Path, Status, UID, AltUID, Instrument, Observation, ItemType, StartDateUTC, EndDateUTC, TimezoneOffset, SurveyVisit, SurveyInstance, OriginalFilename, ContentType, SizeBytes, Value, Notes.
- Each file's date in the zip is its observation start date (UTC).
- The zip is written by a small streaming zip writer in `nonimaging.php` (`ZipStreamWriter`), not `ZipArchive` or the `zip` command. The files are blobs in the `files` table, so those would first need every file written to disk, and nothing would be sent until the whole zip was built (risking proxy timeouts). The writer streams each file as it's read from the database, in 64 MB chunks, so memory and disk use stay small. It uses data descriptors, deflates csv/json/text files and stores images, and writes ZIP64 records when the zip is over 4 GB or has more than 65,535 files. Tested with `unzip`, Python `zipfile`, and `7z`, including a zip over 4 GB and one with 70,000 files.
- The preview shows the number of files, the total uncompressed size, the number of missing files, subjects with data, and the first 500 files with their paths in the zip.

### Dates

All dates are exported in UTC, with the timezone offset (`observation_tz_offset`) in its own column.

## Finding observations of another format

Users may not know what observations exist or what format each one is. To make format-first workable:

- **Find box on the Format step**: a search box that searches *all* observation names in the project across all formats. Each result shows the name, instrument, and format. Clicking a result selects that format and that observation.
- **Cross-format hints in the Observations step**: when the filter box matches nothing (or fewer items) in the current format but matches observations in other formats, show a hint such as "4 matches in Files format — switch?".
- **Per-format selections**: selections are kept separately for each format, so switching format to look around doesn't lose what the user already picked. Only the active format's selection is previewed/exported.

## Export implementation

- The request (format, layout, selected instrument item IDs and unaffiliated names, subjects, dates) is POSTed as one JSON string, because a selection can be thousands of names, more than PHP's `max_input_vars`.
- The server re-validates everything: the format and layout, that the instrument items exist and have a type that fits the format, the dates, and that the chosen subjects are still enrolled (missing ones are skipped with a warning).
- Only active subjects (`subjects.isactive = 1`) are listed and exported.
- Data is queried in batches of subjects (100 per query for rows, 1000 for counts), in UID order. `MySQLiBoundQuery` buffers each result, so batching keeps memory bounded, and the download streams each batch to the browser.
- **Preview** returns the totals (rows, subjects with data, observation names with data), warnings for selected observations with no data, and the first 500 rows. It runs the same row generator as the download.
- **Download** is only enabled after a preview without errors, for the same selection. It's a normal form POST, so the browser streams the file to disk. The file name is `<project>_observations_<long|wide>_<YYYYMMDD_HHMMSS>.csv` (UTC), `<project>_observations_files_<YYYYMMDD_HHMMSS>.zip`, or `<project>_observations_timeseries_<subject|observation>_<YYYYMMDD_HHMMSS>.zip`.

## Permissions

View Data on the project. Viewing and exporting are treated as the same permission.

## Downloads

All exports are built in the browser request for now. A background job for large exports may be added after performance testing.

## Performance metrics

Built in so performance can be measured on production data (millions of rows), during development only. Shown only to siteadmins, and only in the browser (nothing is written to disk):

- Every AJAX endpoint (observation list, subject list, preview) returns a `perf` object for siteadmins: total server time, peak PHP memory, and for each query a label, the query time, the fetch time, and the number of rows.
- The page adds the client-side timings (request round trip, render time) and shows everything in a collapsible "Performance" panel at the bottom of the page.
- A streamed download can't report back to the page, so siteadmins get a **dry run** button: the server builds the whole export without sending it, and returns the row count, size, and timings.
- Queries run in batches are combined into one row in the panel, with the number of calls.

## Build phases

1. **Done**: step layout, format cards with counts, search across all formats, observation picker (instrument accordion, unaffiliated grid, paste a list, selected tray, cross-format hints), subject picker (all or chosen, paste UIDs), date range, performance panel.
2. **Done**: CSV long layout preview and download.
3. **Done**: CSV wide layout.
4. **Done**: Files (zip).
5. **Done**: Timeseries (zip of csv files, per subject or per observation).

## Open questions

- None at the moment.
