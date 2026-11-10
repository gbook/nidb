# Post/Redirect/GET (PRG) tracking

Goal: every PHP page that handles a **mutating** form submission should use the
Post/Redirect/GET pattern, so a browser refresh or Back button does not re-submit
the form. See `functions.php` `RedirectTo()` / `ShowFlashMessage()` and `adminusers.php`
for the reference implementation.

**Pattern per page:**
1. `ob_start();` near the top (before any output).
2. Wrap each mutating action: `ob_start(); Handler(...); $_SESSION['flash'] = ob_get_clean(); RedirectTo("thispage.php");`
3. `ShowFlashMessage();` at the top of the page's display/list render.

`POST forms` = number of `method="post"` forms in the file (the PRG surface).
`PRG` = ✅ done · ⬜ not yet. This status is manual and preserved across regenerations.

| File | POST forms | PRG |
|------|-----------:|:---:|
| `admin.php` | 2 | ✅ |
| `adminaudits.php` | 3 | ✅ |
| `admindicomae.php` | 2 | ✅ |
| `adminemail.php` | 1 | ✅ |
| `admininstances.php` | 2 | ✅ |
| `adminmodalities.php` | 2 | ✅ |
| `adminprojects.php` | 2 | ✅ |
| `adminqc.php` | 2 | ✅ |
| `adminremoteimports.php` | 1 | ✅ |
| `adminsites.php` | 1 | ✅ |
| `adminstorage.php` | 1 | ✅ |
| `adminusers.php` | 2 | ✅ |
| `analysis.php` | 3 | ✅ |
| `analysisbuilder.php` | 2 | ✅ |
| `backup.php` | 1 | ✅ |
| `batchupload.php` | 1 | ✅ |
| `calendar.php` | 2 | ✅ |
| `calendar_appointments.php` | 3 | ✅ |
| `calendar_calendars.php` | 3 | ✅ |
| `checklist.php` | 1 | ✅ |
| `cleanup.php` | 4 | ✅ |
| `clustersettings.php` | 6 | ✅ |
| `datadictionary.php` | 4 | ✅ |
| `datasetrequests.php` | 2 | ✅ |
| `diagnosis.php` | 2 | ✅ |
| `enrollment.php` | 3 | ✅ |
| `experiment.php` | 2 | ✅ |
| `filesio.php` | 1 | ✅ |
| `functions.php` | 4 | ✅ |
| `groups.php` | 6 | ✅ |
| `import.php` | 1 | ✅ |
| `importimaging.php` | 5 | ✅ |
| `importnonimaging.php` | 2 | ✅ |
| `importremote.php` | 3 | ✅ |
| `importupload_functions.php` | 1 | ✅ |
| `importwizard.php` | 1 | ✅ |
| `instruments.php` | 6 | ✅ |
| `interventions.php` | 2 | ✅ |
| `login.php` | 1 | ✅ |
| `managefiles.php` | 2 | ✅ |
| `menu.php` | 5 | ✅ |
| `merge.php` | 2 | ✅ |
| `minipipeline.php` | 3 | ✅ |
| `mrqcchecklist.php` | 3 | ✅ |
| `nda.php` | 3 | ✅ |
| `nonimaging.php` | 1 | ✅ |
| `observations.php` | 2 | ✅ |
| `packages.php` | 19 | ✅ |
| `pipelinecontainers.php` | 2 | ✅ |
| `pipelines.php` | 4 | ✅ |
| `projectchecklist.php` | 3 | ✅ |
| `projects.php` | 8 | ✅ |
| `ratings.php` | 2 | ✅ |
| `remoteconnections.php` | 2 | ✅ |
| `remoteimportmapping.php` | 2 | ✅ |
| `search.php` | 5 | ✅ |
| `settings.php` | 2 | ✅ |
| `setup.php` | 1 | ✅ |
| `signup.php` | 3 | ✅ |
| `status.php` | 1 | ✅ |
| `studies.php` | 14 | ✅ |
| `subjects.php` | 11 | ✅ |
| `templates.php` | 6 | ✅ |
| `users.php` | 3 | ✅ |

_Regenerated 2026-11-10 by `tools/track-php-conventions.sh`. Counts are a guide; the ✅/⬜ status is preserved across runs._
