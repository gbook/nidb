# Post/Redirect/GET (PRG) tracking

> **Procedure:** see `doc/conversion-recipe.md` for the per-file workflow and PRG idiom.

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
| `admin.php` | 1 | ✅ |
| `adminemail.php` | 1 | ✅ |
| `admininstances.php` | 2 | ✅ |
| `adminmodalities.php` | 1 | ✅ |
| `adminprojectprotocols.php` | 1 | ✅ |
| `adminprojects.php` | 2 | ✅ |
| `adminqc.php` | 2 | ✅ |
| `adminremoteimports.php` | 1 | ✅ |
| `adminsites.php` | 1 | ✅ |
| `adminstorage.php` | 1 | ✅ |
| `adminusers.php` | 1 | ✅ |
| `analysis.php` | 4 | ✅ |
| `analysisbuilder.php` | 2 | ✅ |
| `batchupload.php` | 1 | ⬜ |
| `calendar.php` | 1 | ⬜ |
| `calendar_appointments.php` | 3 | ⬜ |
| `calendar_calendars.php` | 2 | ⬜ |
| `checklist.php` | 1 | ⬜ |
| `cleanup.php` | 4 | ⬜ |
| `clustersettings.php` | 2 | ⬜ |
| `datadictionary.php` | 4 | ⬜ |
| `datasetrequests.php` | 1 | ⬜ |
| `diagnosis.php` | 1 | ⬜ |
| `enrollment.php` | 1 | ⬜ |
| `experiment.php` | 1 | ⬜ |
| `functions.php` | 2 | ⬜ |
| `groups.php` | 4 | ⬜ |
| `import.php` | 6 | ⬜ |
| `importimaging.php` | 4 | ⬜ |
| `importmeasures.php` | 1 | ⬜ |
| `importnonimaging.php` | 2 | ⬜ |
| `importremote.php` | 2 | ⬜ |
| `instance.php` | 1 | ⬜ |
| `instruments.php` | 4 | ⬜ |
| `interventions.php` | 1 | ⬜ |
| `login.php` | 1 | ⬜ |
| `menu.php` | 1 | ⬜ |
| `merge.php` | 5 | ⬜ |
| `minipipeline.php` | 1 | ⬜ |
| `mrqcchecklist.php` | 2 | ⬜ |
| `nda.php` | 3 | ⬜ |
| `ndarequests.php` | 1 | ⬜ |
| `observations.php` | 1 | ⬜ |
| `packages.php` | 18 | ⬜ |
| `pipelines.php` | 5 | ⬜ |
| `projectchecklist.php` | 1 | ⬜ |
| `projects.php` | 9 | ⬜ |
| `ratings.php` | 1 | ⬜ |
| `register.php` | 2 | ⬜ |
| `remoteconnections.php` | 1 | ⬜ |
| `remoteimportmapping.php` | 2 | ⬜ |
| `search.php` | 5 | ⬜ |
| `settings.php` | 1 | ⬜ |
| `setup.php` | 1 | ⬜ |
| `signup.php` | 2 | ⬜ |
| `status.php` | 1 | ⬜ |
| `studies.php` | 14 | ⬜ |
| `subjects.php` | 10 | ⬜ |
| `system.php` | 1 | ⬜ |
| `templates.php` | 4 | ⬜ |
| `timeline.php` | 1 | ⬜ |
| `users.php` | 2 | ⬜ |
| `visualization.php` | 1 | ⬜ |

_Regenerated 2026-09-08 by `tools/track-php-conventions.sh`. Counts are a guide; the ✅/⬜ status is preserved across runs._
