# Prepared-statements tracking

> **Procedure:** see `doc/conversion-recipe.md` for the per-file workflow and bind idioms.

Goal: convert SQL queries that include user input to prepared/bound statements
(`mysqli_prepare` + `mysqli_stmt_bind_param` + `MySQLiBoundQuery`). Parameterless
queries may stay on `MySQLiQuery`. See CLAUDE.md for the exact idiom.

`Direct` = number of `MySQLiQuery(` calls (candidates to review/convert).
`Prepared` = number of `MySQLiBoundQuery(` calls already in the file.
`Done` = ✅ when every query that includes user input is bound. It is auto-set to ✅ when
`Direct` is 0; when `Direct` > 0 it is a MANUAL flag preserved across runs — set it ✅ by hand
once you've confirmed the remaining direct queries are parameterless (a direct query with no
user input is fine as-is).

| File | Direct | Prepared | Done |
|------|-------:|---------:|:----:|
| `admin.php` | 1 | 2 | ✅ |
| `adminaudits.php` | 2 | 1 | ✅ |
| `adminemail.php` | 1 | 0 | ✅ |
| `adminerrorlogs.php` | 1 | 0 | ✅ |
| `admininstances.php` | 3 | 12 | ✅ |
| `adminmodalities.php` | 6 | 13 | ✅ |
| `adminmodules.php` | 1 | 11 | ✅ |
| `adminprojectprotocols.php` | 1 | 3 | ✅ |
| `adminprojects.php` | 3 | 13 | ✅ |
| `adminqc.php` | 1 | 5 | ✅ |
| `adminremoteimports.php` | 2 | 4 | ✅ |
| `adminsites.php` | 1 | 4 | ✅ |
| `adminstorage.php` | 1 | 5 | ✅ |
| `adminusers.php` | 4 | 27 | ✅ |
| `ajaxapi.php` | 17 | 64 | ✅ |
| `analysis.php` | 11 | 24 | ⬜ |
| `analysisbuilder.php` | 29 | 0 | ⬜ |
| `api.php` | 16 | 3 | ⬜ |
| `api2.php` | 4 | 17 | ⬜ |
| `audit.php` | 6 | 0 | ⬜ |
| `backup.php` | 3 | 0 | ⬜ |
| `batchupload.php` | 2 | 0 | ⬜ |
| `beh.php` | 15 | 0 | ⬜ |
| `calendar.php` | 6 | 0 | ⬜ |
| `calendar_appointments.php` | 16 | 0 | ⬜ |
| `calendar_calendars.php` | 1 | 4 | ⬜ |
| `calendar_select.php` | 2 | 0 | ⬜ |
| `checklist.php` | 2 | 26 | ⬜ |
| `cleanup.php` | 16 | 0 | ⬜ |
| `cluster.php` | 3 | 0 | ⬜ |
| `clustersettings.php` | 10 | 0 | ⬜ |
| `datadictionary.php` | 13 | 0 | ⬜ |
| `datasetrequests.php` | 11 | 0 | ⬜ |
| `diagnosis.php` | 0 | 4 | ✅ |
| `dicomimport.php` | 4 | 1 | ⬜ |
| `download.php` | 1 | 0 | ⬜ |
| `downloads.php` | 1 | 0 | ⬜ |
| `enrollment.php` | 2 | 20 | ⬜ |
| `experiment.php` | 15 | 0 | ⬜ |
| `filesio.php` | 2 | 0 | ⬜ |
| `footer.php` | 3 | 0 | ⬜ |
| `functions.php` | 67 | 5 | ⬜ |
| `getfile.php` | 0 | 1 | ✅ |
| `groups.php` | 19 | 22 | ⬜ |
| `icd10.php` | 0 | 1 | ✅ |
| `import.php` | 28 | 0 | ⬜ |
| `importimaging.php` | 27 | 3 | ⬜ |
| `importlog.php` | 9 | 0 | ⬜ |
| `importmeasures.php` | 13 | 0 | ⬜ |
| `importnonimaging.php` | 21 | 0 | ⬜ |
| `importremote.php` | 1 | 16 | ⬜ |
| `includes_php.php` | 2 | 0 | ⬜ |
| `index.php` | 4 | 10 | ⬜ |
| `instance.php` | 10 | 0 | ⬜ |
| `instruments.php` | 0 | 15 | ✅ |
| `interventions.php` | 0 | 4 | ✅ |
| `login.php` | 2 | 6 | ⬜ |
| `longqc.php` | 7 | 0 | ⬜ |
| `managefiles.php` | 3 | 0 | ⬜ |
| `menu.php` | 4 | 0 | ⬜ |
| `merge.php` | 11 | 0 | ⬜ |
| `minipipeline.php` | 18 | 0 | ⬜ |
| `mriqc.php` | 3 | 0 | ⬜ |
| `mrqcchecklist.php` | 25 | 0 | ⬜ |
| `mrseriesqa.php` | 3 | 0 | ⬜ |
| `nda.php` | 1 | 20 | ⬜ |
| `ndarequests.php` | 16 | 0 | ⬜ |
| `nidbapi.php` | 1 | 0 | ⬜ |
| `niiview.php` | 1 | 0 | ⬜ |
| `observations.php` | 4 | 4 | ⬜ |
| `packages.php` | 74 | 2 | ⬜ |
| `pd.php` | 2 | 0 | ⬜ |
| `pipeline_functions.php` | 6 | 0 | ⬜ |
| `pipeline_history.php` | 2 | 0 | ⬜ |
| `pipeline_performance.php` | 11 | 0 | ⬜ |
| `pipelines.php` | 98 | 0 | ⬜ |
| `projectchecklist.php` | 3 | 14 | ⬜ |
| `projectreport.php` | 13 | 0 | ⬜ |
| `projects.php` | 99 | 2 | ⬜ |
| `publicdatasets.php` | 3 | 0 | ⬜ |
| `publicdownloads.php` | 3 | 0 | ⬜ |
| `qa.php` | 1 | 0 | ⬜ |
| `ratings.php` | 4 | 0 | ⬜ |
| `redcap_functions.php` | 0 | 1 | ✅ |
| `redcap_import.php` | 0 | 14 | ✅ |
| `register.php` | 9 | 0 | ⬜ |
| `remoteconnections.php` | 5 | 0 | ⬜ |
| `remoteimportmapping.php` | 0 | 16 | ✅ |
| `reports.php` | 7 | 0 | ⬜ |
| `requeststatus.php` | 16 | 3 | ⬜ |
| `search.php` | 67 | 0 | ⬜ |
| `settings.php` | 3 | 0 | ⬜ |
| `setup.php` | 9 | 0 | ⬜ |
| `signup.php` | 9 | 0 | ⬜ |
| `stats.php` | 26 | 0 | ⬜ |
| `status.php` | 4 | 2 | ⬜ |
| `studies.php` | 83 | 0 | ⬜ |
| `subjects.php` | 74 | 6 | ⬜ |
| `system.php` | 3 | 0 | ⬜ |
| `tags.php` | 12 | 0 | ⬜ |
| `templates.php` | 30 | 0 | ⬜ |
| `timeline.php` | 4 | 1 | ⬜ |
| `upload.php` | 4 | 0 | ⬜ |
| `users.php` | 18 | 3 | ⬜ |
| `v.php` | 4 | 0 | ⬜ |
| `viewanalysis.php` | 25 | 0 | ⬜ |
| `viewimage.php` | 1 | 0 | ⬜ |

_Regenerated 2026-09-08 by `tools/track-php-conventions.sh`. Counts are a guide; a direct query with no user input needs no change._
